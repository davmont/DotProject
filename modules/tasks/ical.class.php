<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once $AppUI->getSystemClass('libmail');

/**
 * Calendar invitations for tasks: "Synchronize with the external calendar of assigned
 * user" on the task form mails an iCalendar (RFC 5545) invitation to the assignees, and
 * a cancellation when the task is deleted or the option is turned off. The event's UID
 * and sequence number are kept in tasks_ical so calendars update the same event.
 */
class CTaskIcal
{
	/**
	 * Sends the invitation ($cancel false) or the cancellation of task $task_id.
	 * $action ('added', 'updated' or 'deleted') only words the message. Returns the number of
	 * messages sent.
	 */
	static function send($task_id, $cancel, $action)
	{
		global $AppUI;

		$q = new DBQuery();
		$q->addTable('tasks', 't');
		$q->addJoin('projects', 'p', 'p.project_id = t.task_project');
		$q->addJoin('users', 'u', 'u.user_id = t.task_creator');
		$q->addJoin('contacts', 'c', 'c.contact_id = u.user_contact');
		$q->addQuery('t.task_id, t.task_name, t.task_description, t.task_start_date, t.task_end_date'
			. ', p.project_name, c.contact_first_name, c.contact_last_name, c.contact_email');
		$q->addWhere('t.task_id = ' . (int)$task_id);
		$task = $q->loadHash();
		$q->clear();
		if (!$task || !self::stamp($task['task_start_date'])) {
			return 0;
		}

		$q->addTable('tasks_ical');
		$q->addQuery('UID, created, sequence');
		$q->addWhere('task_id = ' . (int)$task_id);
		$ical = $q->loadHash();
		$q->clear();
		if ($cancel && !$ical) {
			return 0; // no invitation was ever sent
		}
		if ($ical) {
			$ical['sequence'] = (int)$ical['sequence'] + 1;
		} else {
			$ical = array('UID' => 't' . (int)$task_id . '-' . gmdate('YmdHis') . '@dp', 'created' => gmdate('Ymd\THis\Z'), 'sequence' => 0);
		}

		$q->addTable('user_tasks', 'ut');
		$q->addJoin('users', 'u', 'u.user_id = ut.user_id');
		$q->addJoin('contacts', 'c', 'c.contact_id = u.user_contact');
		$q->addQuery('c.contact_first_name, c.contact_last_name, c.contact_email');
		$q->addWhere('ut.task_id = ' . (int)$task_id);
		$attendees = array();
		foreach ($q->loadList() as $row) {
			if (trim((string)$row['contact_email']) !== '') {
				$attendees[] = $row;
			}
		}
		$q->clear();
		// Without assignees the invitation goes to the task's creator.
		$recipients = $attendees ? $attendees : (trim((string)$task['contact_email']) !== '' ? array($task) : array());

		$ics = self::build($task, $ical, $attendees, $cancel);
		$message = $AppUI->_('The Task', UI_OUTPUT_RAW) . ' "' . $task['task_name'] . '" '
			. $AppUI->_('of Project', UI_OUTPUT_RAW) . ' "' . htmlspecialchars_decode((string)$task['project_name']) . '" '
			. $AppUI->_('has been', UI_OUTPUT_RAW) . ' ' . $AppUI->_($action, UI_OUTPUT_RAW) . '.';
		$sent = 0;
		foreach ($recipients as $to) {
			$mail = new Mail;
			$mail->Subject(htmlspecialchars_decode((string)$task['project_name']) . '::' . $task['task_name'] . ' '
				. $AppUI->_($cancel ? 'Cancelled' : 'Calendar', UI_OUTPUT_RAW), 'UTF-8');
			$mail->Body($message, 'UTF-8');
			$mail->From('"' . trim($AppUI->user_first_name . ' ' . $AppUI->user_last_name) . '" <' . $AppUI->user_email . '>');
			$mail->To($to['contact_email'], true);
			$mail->AttachString($ics, $cancel ? 'cancel.ics' : 'invite.ics',
				'text/calendar; method=' . ($cancel ? 'CANCEL' : 'REQUEST') . '; charset=UTF-8');
			if ($mail->Send()) {
				$sent++;
			}
		}

		if ($cancel) {
			$q->setDelete('tasks_ical');
			$q->addWhere('task_id = ' . (int)$task_id);
			$q->exec();
		} else {
			$q->addTable('tasks_ical');
			if ($ical['sequence'] == 0) {
				$q->addInsert('task_id', (int)$task_id);
				$q->addInsert('UID', $ical['UID']);
				$q->addInsert('created', $ical['created']);
				$q->addInsert('sequence', 0);
			} else {
				$q->addUpdate('sequence', $ical['sequence']);
				$q->addWhere('task_id = ' . (int)$task_id);
			}
			$q->exec();
		}
		$q->clear();
		return $sent;
	}

	/** The VCALENDAR text for the task. */
	static function build($task, $ical, $attendees, $cancel)
	{
		$start = self::stamp($task['task_start_date']);
		$end = self::stamp($task['task_end_date']);
		$organizer = trim($task['contact_first_name'] . ' ' . $task['contact_last_name']);
		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//dotPulse//Tasks//EN',
			'METHOD:' . ($cancel ? 'CANCEL' : 'REQUEST'),
			'BEGIN:VEVENT',
			'UID:' . $ical['UID'],
			'SEQUENCE:' . (int)$ical['sequence'],
			'DTSTAMP:' . gmdate('Ymd\THis\Z'),
			'CREATED:' . $ical['created'],
			'DTSTART:' . $start,
			'DTEND:' . ($end && $end > $start ? $end : $start),
			'SUMMARY:' . self::text($task['task_name'] . ' (' . htmlspecialchars_decode((string)$task['project_name']) . ')'),
			'DESCRIPTION:' . self::text((string)$task['task_description']),
			'LOCATION:' . self::text((string)dPgetConfig('company_name', '')),
		);
		if (trim((string)$task['contact_email']) !== '') {
			$lines[] = 'ORGANIZER;CN=' . self::param($organizer) . ':mailto:' . $task['contact_email'];
		}
		foreach ($attendees as $a) {
			$lines[] = 'ATTENDEE;ROLE=REQ-PARTICIPANT;CN=' . self::param(trim($a['contact_first_name'] . ' ' . $a['contact_last_name']))
				. ':mailto:' . $a['contact_email'];
		}
		$lines[] = 'STATUS:' . ($cancel ? 'CANCELLED' : 'CONFIRMED');
		$lines[] = 'TRANSP:TRANSPARENT';
		$lines[] = 'END:VEVENT';
		$lines[] = 'END:VCALENDAR';
		return implode("\r\n", array_map(array('CTaskIcal', 'fold'), $lines)) . "\r\n";
	}

	/** "2026-10-30 09:00:00" as a floating local time (20261030T090000), '' if unset. */
	static function stamp($datetime)
	{
		if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/', (string)$datetime, $m) || $m[1] === '0000') {
			return '';
		}
		return $m[1] . $m[2] . $m[3] . 'T' . (isset($m[4]) ? $m[4] : '00') . (isset($m[5]) ? $m[5] : '00') . (isset($m[6]) ? $m[6] : '00');
	}

	/** A TEXT value: backslash, semicolon, comma and line breaks escaped. */
	static function text($value)
	{
		$value = str_replace(array("\r\n", "\r"), "\n", (string)$value);
		return strtr($value, array('\\' => '\\\\', ';' => '\;', ',' => '\,', "\n" => '\n'));
	}

	/** A parameter value (CN=...): quoted, without the characters a quoted value cannot hold. */
	static function param($value)
	{
		return '"' . str_replace(array('"', "\r", "\n"), array("'", ' ', ' '), (string)$value) . '"';
	}

	/** Folds a content line at 75 octets without splitting a UTF-8 character. */
	static function fold($line)
	{
		$out = '';
		$width = 75;
		while (strlen($line) > $width) {
			$cut = $width;
			while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
				$cut--;
			}
			$out .= substr($line, 0, $cut) . "\r\n ";
			$line = substr($line, $cut);
			$width = 74; // the leading space counts
		}
		return $out . $line;
	}
}
