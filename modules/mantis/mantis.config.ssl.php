<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* ID: mantis.config.ssl.php 2007/04/10 12:46 weboholic */

// To be used with SSL enabled httpd
// Advanced understanding is required
// For full tweaking, analyze /xmlrpc/phpxmlrpc/xmlrpc.inc.php
//
// Comments copy/pasted from xmlrpc.php

// This configuration is only triggered if HTTPS is enabled, see mantis.config.php
//
// Example:
// Our Mantis server is on different host and is secured by SSL. But the server
// certificate is not  "officialy" signed.
// So we use this option (line 55). If this option is not set, returned payload will
// always be fault, because XMLRPC won't accept the certificate.
//
// Note: to enable this advanced tweaking xmlrpc/phpxmlrpc/client.php has been 
// modified
//
// Nikola Ivanov, n.ivanov@mgtraining.com, 10.04.2007


/*
* Add some http BASIC AUTH credentials, used by the client to authenticate
* @param string $u username
* @param string $p password
* @param integer $t auth type. See curl_setopt man page for supported auth types. Defaults to CURLAUTH_BASIC (basic auth)
* @access public
*/
//$mantis->setOption( 'setCredentials','$u,$p,$t=1' );

/*
* Add a client-side https certificate
* @param string $cert
* @param string $certpass
* @access public
*/
//$mantis->setOption( 'setCertificate','$cert,$certpass' );

/*
* Add a CA certificate to verify server with (see man page about
* CURLOPT_CAINFO for more details
* @param string $cacert certificate file name (or dir holding certificates)
* @param bool $is_dir set to true to indicate cacert is a dir. defaults to false
* @access public
*/
//$mantis->setOption( 'setCaCertificate','$cacert,$is_dir=false' );

/*
* @param string $key     The name of a file containing a private SSL key
* @param string $keypass The secret password needed to use the private SSL key
* @access public
* NB: does not work in older php/curl installs
* Thanks to Daniel Convissor
*/
//$mantis->setKey($key, $keypass);

/*
* @param $bool  enable/diable verification of peer certificate
* @access public
*/
//$mantis->setOption( 'setSSLVerifyPeer','false' );

/*
* @access public
*/
//$mantis->setOption( 'setSSLVerifyHost','$bool' );

/**
* Set proxy info
*
* @param    string $proxyhost
* @param    string $proxyport Defaults to 8080 for HTTP and 443 for HTTPS
* @param    string $proxyusername Leave blank if proxy has public access
* @param    string $proxypassword Leave blank if proxy has public access
* @param    int    $proxyauthtype set to constant CURLAUTH_MTLM to use NTLM auth with proxy
* @access   public
*/
//$mantis->setOption( 'setProxy','$proxyhost,$proxyport,$proxyusername = "",$proxypassword = "",$proxyauthtype = 1' );

/**
* Enables/disables reception of compressed xmlrpc responses.
* Note that enabling reception of compressed responses merely adds some standard
* http headers to xmlrpc requests. It is up to the xmlrpc server to return
* compressed responses when receiving such requests.
* @param string $compmethod either 'gzip', 'deflate', 'any' or ''
* @access   public
*/
//$mantis->setOption( 'setAcceptedCompression','$compmethod' );

/**
* Enables/disables http compression of xmlrpc request.
* Take care when sending compressed requests: servers might not support them
* (and automatic fallback to uncompressed requests is not yet implemented)
* @param string $compmethod either 'gzip', 'deflate' or ''
* @access   public
*/
//$mantis->setOption( 'setRequestCompression','$compmethod' );

/**
* Adds a cookie to list of cookies that will be sent to server.
* NB: setting any param but name and value will turn the cookie into a 'version 1' cookie:
* do not do it unless you know what you are doing
* @param string $name
* @param string $value
* @param string $path
* @param string $domain
* @param string $port
* @access   public
*
* @todo check correctness of urlencoding cookie value (copied from php way of doing it...)
*/
//$mantis->setOption( 'setCookie','$name,$value="",$path="",$domain="",$port=null' );
?>