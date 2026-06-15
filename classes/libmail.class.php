<?php /* CLASSES $Id: libmail.class.php 6154 2012-05-03 02:33:21Z ajdonnison $ */
/**
 *  @package dotproject
 *  @subpackage utilites
 */

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

use PHPMailer\PHPMailer\PHPMailer as PM;
use PHPMailer\PHPMailer\Exception as PMException;

/**
 *  Thin wrapper around PHPMailer that preserves the legacy Mail class API.
 *
 *  Public interface is unchanged from the original libmail implementation so
 *  all callers (sendpass.php, calendar.class.php, do_user_aed.php, etc.) work
 *  without modification.  The raw-socket SMTP code and inline base64 attachment
 *  builder have been removed; PHPMailer handles both.
 *
 *  @author  Original: Leo West / Emiliano Gabrielli / Adam Donnison
 *  @author  PHPMailer port: davmont fork
 */
class Mail
{
	/** @var array  To addresses */
	var $ato = array();
	/** @var array */
	var $acc = array();
	/** @var array */
	var $abcc = array();
	/** @var array  Paths of attached files */
	var $aattach = array();
	/** @var array  MIME types per attachment */
	var $actype = array();
	/** @var array  Dispositions per attachment */
	var $adispo = array();
	/** @var array  Assembled RFC headers (for Get()) */
	var $xheaders = array();
	/** @var string  Stringified headers (for Get()) */
	var $headers = '';
	/** @var array */
	var $priorities = array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');
	/** @var string */
	var $charset;
	/** @var string */
	var $ctencoding;
	/** @var int */
	var $receipt = 0;
	/** @var bool */
	var $useRawAddress = TRUE;
	var $host;
	var $port;
	var $sasl;
	var $tls;
	var $username;
	var $password;
	var $transport;
	var $defer;
	var $response;
	var $err     = false;
	var $last_error = false;
	var $body    = '';
	var $fullBody = '';
	var $boundary = '';
	var $timeout = 0;
	var $checkAddress = true;
	var $canEncode = false;
	var $hasMbStr = false;

	/** Raw (un-encoded) values passed through to PHPMailer */
	private $__rawFrom    = '';
	private $__rawSubject = '';
	private $__rawReplyTo = '';
	private $__rawOrg     = '';

// ---------------------------------------------------------------------------
// Construction
// ---------------------------------------------------------------------------

function __construct() {
	require_once DP_BASE_DIR . '/vendor/autoload.php';

	$this->autoCheck(TRUE);
	$this->boundary = '--' . md5(uniqid('dPboundary'));

	$this->transport = dPgetConfig('mail_transport', 'php');
	$this->host      = dPgetConfig('mail_host', 'localhost');
	$this->port      = dPgetConfig('mail_port', '25');
	$this->sasl      = dPgetConfig('mail_auth', FALSE);
	$this->tls       = dPgetConfig('mail_smtp_tls', FALSE);
	$this->username  = dPgetConfig('mail_user');
	$this->password  = dPgetConfig('mail_pass');
	$this->defer     = dPgetConfig('mail_defer');
	$this->timeout   = dPgetConfig('mail_timeout', 0);

	$this->charset     = isset($GLOBALS['locale_char_set'])
	                     ? mb_strtolower($GLOBALS['locale_char_set']) : 'us-ascii';
	$this->ctencoding  = $this->charset !== 'us-ascii' ? '8bit' : '7bit';
	$this->canEncode   = 'us-ascii' !== $this->charset;
	$this->hasMbStr    = function_exists('mb_substr');
}

// ---------------------------------------------------------------------------
// Address validation
// ---------------------------------------------------------------------------

function autoCheck($bool) {
	$this->checkAddress = (bool) $bool;
}

function ValidEmail($address) {
	if (preg_match('/^(.*)\<(.+)\>$/D', $address, $regs)) {
		$address = $regs[2];
	}
	return (bool) preg_match('/^[^@ ]+@([-a-zA-Z0-9..]+)$/D', $address);
}

function CheckAdresses($aad) {
	foreach ($aad as $ad) {
		if (!$this->ValidEmail($ad)) {
			echo ('Class Mail, method Mail : invalid address ' . $ad);
			exit;
		}
	}
	return TRUE;
}

function CheckAddresses($aad) {
	return $this->CheckAdresses($aad);
}

// ---------------------------------------------------------------------------
// Header setters
// ---------------------------------------------------------------------------

function Subject($subject, $charset = '') {
	if (!empty($charset)) {
		$this->charset = mb_strtolower($charset);
	}
	$subject = dPgetConfig('email_prefix') . ' ' . $subject;
	$this->__rawSubject = $subject;

	// Keep encoded version for Get() compatibility.
	$subject = strtr($subject, "\x0B\0\t\r\n\f", '      ');
	$subject = $this->_wordEncode($subject, mb_strlen('Subject: '));
	$this->xheaders['Subject'] = $subject;
}

function From($from) {
	if (!is_string($from)) {
		return FALSE;
	}
	$this->__rawFrom = $from;
	$from = strtr($from, "\x0B\0\t\r\n\f", '      ');
	$this->xheaders['From'] = $this->_addressEncode($from, mb_strlen('From: '));
}

function ReplyTo($address) {
	if (!is_string($address)) {
		return FALSE;
	}
	$this->__rawReplyTo = $address;
	$address = strtr($address, "\x0B\0\t\r\n\f", '      ');
	$this->xheaders['Reply-To'] = $this->_addressEncode($address, mb_strlen('Reply-To: '));
}

function Receipt() {
	$this->receipt = 1;
}

function To($to, $reset = FALSE) {
	if (is_array($to)) {
		$to = array_map(function ($s) { return strtr($s, "\x0B\0\t\r\n\f", '      '); }, $to);
		$this->ato = $to;
	} else {
		$to = strtr($to, "\x0B\0\t\r\n\f", '      ');
		if ($this->useRawAddress) {
			if (preg_match('/^(.*)\<(.+)\>$/D', $to, $regs)) {
				$to = $regs[2];
			}
		}
		if ($reset) {
			$this->ato = array();
		}
		$this->ato[] = $to;
	}
	if ($this->checkAddress == TRUE) {
		$this->CheckAdresses($this->ato);
	}
}

function Cc($cc) {
	if (is_array($cc)) {
		$cc = array_map(function ($s) { return strtr($s, "\x0B\0\t\r\n\f", '      '); }, $cc);
		$this->acc = $cc;
	} else {
		$cc = strtr($cc, "\x0B\0\t\r\n\f", '      ');
		$this->acc = explode(',', $cc);
	}
	if ($this->checkAddress == TRUE) {
		$this->CheckAdresses($this->acc);
	}
}

function Bcc($bcc) {
	if (is_array($bcc)) {
		$bcc = array_map(function ($s) { return strtr($s, "\x0B\0\t\r\n\f", '      '); }, $bcc);
		$this->abcc = $bcc;
	} else {
		$bcc = strtr($bcc, "\x0B\0\t\r\n\f", '      ');
		$this->abcc = explode(',', $bcc);
	}
	if ($this->checkAddress == TRUE) {
		$this->CheckAdresses($this->abcc);
	}
}

function Body($body, $charset = '') {
	$this->body = $body;
	if (!empty($charset)) {
		$this->charset = mb_strtolower($charset);
		if ($this->charset !== 'us-ascii') {
			$this->ctencoding = '8bit';
		}
	}
}

function Organization($org) {
	if (trim($org) !== '') {
		$this->__rawOrg = $org;
		$this->xheaders['Organization'] = $this->_wordEncode($org, mb_strlen('Organization: '));
	}
}

function Priority($priority) {
	if (!intval($priority)) {
		return FALSE;
	}
	if (!isset($this->priorities[$priority - 1])) {
		return FALSE;
	}
	$this->xheaders['X-Priority'] = $this->priorities[$priority - 1];
	return TRUE;
}

function Attach($filename, $filetype = '', $disposition = 'inline') {
	if (empty($filetype)) {
		$filetype = 'application/x-unknown-content-type';
	}
	$this->aattach[] = $filename;
	$this->actype[]  = $filetype;
	$this->adispo[]  = $disposition;
}

// ---------------------------------------------------------------------------
// Sending
// ---------------------------------------------------------------------------

function Send() {
	$this->BuildMail(); // populates fullBody + xheaders for Get() / QueueMail()

	if ($this->defer) {
		return $this->QueueMail();
	}
	return $this->_phpmailerSend();
}

/**
 *  Queue mail via the dotProject event queue.
 */
function QueueMail() {
	global $AppUI;
	require_once $AppUI->getSystemClass('event_queue');
	$ec   = new EventQueue;
	$vars = get_object_vars($this);
	return $ec->add(array('Mail', 'SendQueuedMail'), $vars, 'libmail', TRUE);
}

/**
 *  Called by the queue manager to dequeue and send.
 */
function SendQueuedMail($mod, $type, $originator, $owner, &$args) {
	foreach ($args as $k => $v) {
		if (isset($this->$k) || property_exists($this, $k)) {
			$this->$k = $v;
		}
	}
	return $this->_phpmailerSend();
}

/**
 *  Build and send via PHPMailer (both SMTP and php-mail transports).
 */
private function _phpmailerSend() {
	$pm = new PM(true);

	if ($this->transport === 'smtp') {
		$pm->isSMTP();
		$pm->Host     = $this->host;
		$pm->Port     = (int) $this->port;
		$pm->SMTPAuth = (bool) $this->sasl;
		if ($this->sasl && $this->username) {
			$pm->Username = $this->username;
			$pm->Password = $this->password;
		}
		if ($this->tls) {
			$pm->SMTPSecure = PM::ENCRYPTION_STARTTLS;
		}
		if ($this->timeout) {
			$pm->Timeout = (int) $this->timeout;
		}
	} else {
		$pm->isMail();
	}

	$pm->CharSet = $this->charset && $this->charset !== 'us-ascii'
	               ? $this->charset : 'UTF-8';
	$pm->XMailer = isset($GLOBALS['AppUI'])
	               ? 'dotProject v' . $GLOBALS['AppUI']->getVersion() : 'dotProject';

	// From
	$rawFrom = $this->__rawFrom ?: ($this->xheaders['From'] ?? '');
	if (preg_match('/^(.*?)\s*<([^>]+)>$/i', $rawFrom, $m)) {
		$pm->setFrom(trim($m[2]), trim($m[1]));
	} else {
		$pm->setFrom(trim($rawFrom) ?: 'noreply@localhost');
	}

	// Subject
	$pm->Subject = $this->__rawSubject ?: ($this->xheaders['Subject'] ?? '');

	// Reply-To
	if ($this->__rawReplyTo) {
		if (preg_match('/^(.*?)\s*<([^>]+)>$/i', $this->__rawReplyTo, $m)) {
			$pm->addReplyTo(trim($m[2]), trim($m[1]));
		} else {
			$pm->addReplyTo(trim($this->__rawReplyTo));
		}
	}

	// Recipients
	foreach ($this->ato as $addr) {
		$addr = trim($addr);
		if ($addr) { $pm->addAddress($addr); }
	}
	foreach ($this->acc as $addr) {
		$addr = trim($addr);
		if ($addr) { $pm->addCC($addr); }
	}
	foreach ($this->abcc as $addr) {
		$addr = trim($addr);
		if ($addr) { $pm->addBCC($addr); }
	}

	// Body (plain text)
	$pm->isHTML(false);
	$pm->Body = $this->body ?? '';

	// Attachments
	if (!empty($this->aattach)) {
		for ($i = 0, $n = count($this->aattach); $i < $n; $i++) {
			$disp = ($this->adispo[$i] ?? 'attachment') === 'attachment'
			        ? 'attachment' : 'inline';
			$pm->addAttachment(
				$this->aattach[$i],
				basename($this->aattach[$i]),
				'base64',
				$this->actype[$i] ?? 'application/octet-stream',
				$disp
			);
		}
	}

	// Extras
	if ($this->__rawOrg) {
		$pm->addCustomHeader('Organization', $this->__rawOrg);
	}
	if (!empty($this->xheaders['X-Priority'])) {
		$pm->Priority = (int) $this->xheaders['X-Priority'][0];
	}
	if ($this->receipt) {
		$ref = $this->__rawReplyTo ?: $this->__rawFrom;
		if ($ref) {
			$pm->ConfirmReadingTo = preg_match('/^(.*?)\s*<([^>]+)>$/i', $ref, $m)
			                        ? trim($m[2]) : trim($ref);
		}
	}

	try {
		return $pm->send();
	} catch (PMException $e) {
		global $AppUI;
		if (isset($AppUI)) {
			$AppUI->setMsg('Failed to send email: ' . $e->getMessage(), UI_MSG_ERROR);
		}
		return false;
	}
}

// ---------------------------------------------------------------------------
// Get() — returns the full RFC-formatted message (for display / logging)
// ---------------------------------------------------------------------------

function BuildMail() {
	global $AppUI;

	if (count($this->ato) > 0) {
		$this->_addressesEncode($this->ato, 'To');
	}
	if (count($this->acc) > 0) {
		$this->_addressesEncode($this->acc, 'CC');
	}
	if (count($this->abcc) > 0) {
		$this->_addressesEncode($this->abcc, 'BCC');
	}

	if ($this->receipt) {
		$this->xheaders['Disposition-Notification-To'] =
			isset($this->xheaders['Reply-To'])
			? $this->xheaders['Reply-To'] : $this->xheaders['From'];
	}

	if (!empty($this->charset)) {
		$this->xheaders['Mime-Version']              = '1.0';
		$this->xheaders['Content-Type']              = 'text/plain; charset=' . $this->charset;
		$this->xheaders['Content-Transfer-Encoding'] = $this->ctencoding;
	}

	$this->xheaders['X-Mailer'] = 'dotProject v' . $AppUI->getVersion();
	$this->headers = '';
	foreach ($this->xheaders as $h => $v) {
		$this->headers .= $h . ': ' . trim($v) . "\r\n";
	}

	if (count($this->aattach) > 0) {
		$this->_build_attachement();
	} else {
		$sep = "\r\n";
		$arr = preg_split('/(\r?\n)|\r/', $this->body);
		$this->fullBody = implode($sep, $arr);
	}
}

function Get() {
	$this->BuildMail();
	return $this->headers . "\r\n\r\n" . $this->fullBody;
}

// ---------------------------------------------------------------------------
// Internal helpers (kept for BuildMail / Get compatibility)
// ---------------------------------------------------------------------------

function _build_attachement() {
	$this->xheaders['Content-Type'] =
		"multipart/mixed;\r\n boundary=\"" . $this->boundary . '"';

	$this->fullBody  = "This is a multi-part message in MIME format.\r\n--"
	                   . $this->boundary . "\r\n";
	$this->fullBody .= 'Content-Type: text/plain; charset=' . $this->charset
	                   . "\r\nContent-Transfer-Encoding: " . $this->ctencoding . "\r\n\r\n";

	$sep  = "\r\n";
	$body = preg_split('/\r?\n/', $this->body);
	$this->fullBody .= implode($sep, $body) . "\r\n";

	$ata = array();
	$k   = 0;
	for ($i = 0, $cnt = count($this->aattach); $i < $cnt; $i++) {
		$filename = $this->aattach[$i];
		$basename = basename($filename);
		$ctype    = $this->actype[$i];
		$dispo    = $this->adispo[$i];

		if (!file_exists($filename)) {
			echo "Class Mail, method attach : file $filename can't be found";
			exit;
		}
		$subhdr  = '--' . $this->boundary . "\r\nContent-type: " . $ctype . ";\r\n"
		         . ' name="' . $basename . '"' . "\r\n"
		         . "Content-Transfer-Encoding: base64\r\n"
		         . 'Content-Disposition: ' . $dispo . ";\r\n"
		         . '  filename="' . $basename . '"' . "\r\n";
		$ata[$k++] = $subhdr;
		$linesz    = filesize($filename) + 1;
		$fp        = fopen($filename, 'rb');
		$ata[$k++] = chunk_split(base64_encode(fread($fp, $linesz)));
		fclose($fp);
	}
	$this->fullBody .= implode($sep, $ata);
}

function _addressEncode($addr, $offset = 0) {
	if (!$this->canEncode) {
		return $addr;
	}
	$matches = NULL;
	$mail    = '';
	$txt     = '';
	if (!@preg_match('/^(.*)\s?(<[^@]+@[a-z0-9\._-]+>)$/Di', $addr, $matches)) {
		return $addr;
	}
	$txt  = $matches[1];
	$mail = $matches[2];
	$txt  = $this->_wordEncode(trim($txt), $offset);
	return (($offset + $this->_strlen($txt . $mail) > 76)
	        ? ($txt . "\r\n " . $mail) : ($txt . $mail));
}

function _wordEncode($str, $offset = 0) {
	if (!$this->canEncode) {
		return $str;
	}
	$cs   = $this->charset;
	$qstr = $this->_utfToQuotedPrintable($str, $offset);
	$s    = "=?$cs?Q?";
	$e    = '?=';
	return ($s . implode($e . "\r\n\t" . $s, $qstr) . $e);
}

function _utfToQuotedPrintable($str, $offset = 0) {
	$l      = 72 - $offset;
	$result = array();
	$x      = 0;
	$s      = '';
	for ($i = 0, $len = strlen($str); $i < $len; $i++) {
		$ord = ord($str[$i]);
		if ($ord > 32 && $ord < 127 && $str[$i] !== '?' && $str[$i] !== '=') {
			$s .= $str[$i];
			$x++;
		} elseif (($ord & 0xE0) == 0xC0) {
			$s .= sprintf('=%02X=%02X', $ord, ord($str[++$i]));
			$x += 6;
		} elseif (($ord & 0xF0) == 0xE0) {
			$s .= sprintf('=%02X=%02X=%02X', $ord, ord($str[++$i]), ord($str[++$i]));
			$x += 9;
		} elseif (($ord & 0xF8) == 0xF0) {
			$s .= sprintf('=%02X=%02X=%02X=%02X', $ord, ord($str[++$i]), ord($str[++$i]), ord($str[++$i]));
			$x += 12;
		} elseif (($ord & 0xFC) == 0xF8) {
			$s .= sprintf('=%02X=%02X=%02X=%02X=%02X', $ord, ord($str[++$i]), ord($str[++$i]), ord($str[++$i]), ord($str[++$i]));
			$x += 15;
		} elseif (($ord & 0xFE) == 0xFC) {
			$s .= sprintf('=%02X=%02X=%02X=%02X=%02X=%02X', $ord, ord($str[++$i]), ord($str[++$i]), ord($str[++$i]), ord($str[++$i]), ord($str[++$i]));
			$x += 18;
		} else {
			$s .= sprintf('=%02X', $ord);
			$x += 3;
		}
		if ($x >= $l) {
			$result[] = $s;
			$s        = '';
			$x        = 0;
			$l        = 72;
		}
	}
	if ($x) {
		$result[] = $s;
	}
	return $result;
}

function _addressesEncode(&$aaddr, $hdr) {
	$n = count($aaddr);
	$this->xheaders[$hdr] = $this->_addressEncode($aaddr[0], mb_strlen("$hdr: "));
	for ($i = 1; $i < $n; ++$i) {
		$val = trim($this->_addressEncode($aaddr[$i], 8));
		if ($val) {
			$this->xheaders[$hdr] .= (",\r\n " . $val);
		}
	}
}

function _strpos($str, $start, $offset = 0) {
	return $this->hasMbStr
	       ? mb_strpos($str, $start, $offset, $this->charset)
	       : strpos($str, $start, $offset);
}

function _substr($str, $start, $len = null) {
	if ($len === null) { $len = $this->_strlen($str); }
	return $this->hasMbStr
	       ? mb_substr($str, $start, $len, $this->charset)
	       : substr($str, $start, $len);
}

function _strlen($str) {
	return $this->hasMbStr ? mb_strlen($str, $this->charset) : strlen($str);
}

} // class Mail
?>
