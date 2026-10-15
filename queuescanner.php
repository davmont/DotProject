<?php
	// $Id: queuescanner.php 5962 2010-03-19 17:51:09Z merlinyoda $

/*
All files in this work, except the modules/ticketsmith directory, are now
covered by the following copyright notice.  The ticketsmith module is
under the Voxel Public License.  See modules/ticketsmith/LICENSE
for details. Please note that included libraries in lib may have their
own license.

Copyright (c) 2003-2005 The dotProject Development Team <core-developers@dotproject.net>

    This file is part of dotProject.

    dotProject is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    dotProject is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with dotProject; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

The full text of the GPL is in the COPYING file.
*/

	// Function to scan the event queue and execute any functions required.
	//
	// Runs from cron on the command line only, for example in /etc/cron.d/dotproject:
	//   */5 * * * * www-data php /path/to/dotproject/queuescanner.php --base-url=https://pm.example.com
	// See docs/deployment-security.md.

	if (PHP_SAPI !== 'cli') {
		http_response_code(404);
		exit;
	}

	// Reminder mails link back to the site, and a command-line run has no request to
	// derive the URL from (it came out as "http://."). Take it from --base-url=...
	// or the DP_BASE_URL environment variable.
	$cliBaseUrl = (string)getenv('DP_BASE_URL');
	foreach (array_slice($argv, 1) as $arg) {
		if (strpos($arg, '--base-url=') === 0) {
			$cliBaseUrl = substr($arg, strlen('--base-url='));
		}
	}
	$cliBaseUrl = rtrim($cliBaseUrl, '/');
	if (!preg_match('#^https?://[^/\s]+(/\S*)?$#', $cliBaseUrl)) {
		fwrite(STDERR, "Usage: php queuescanner.php --base-url=https://your-host/dotproject\n"
			. "       (or set the DP_BASE_URL environment variable)\n");
		exit(2);
	}
	define('DP_BASE_URL', $cliBaseUrl);

	require_once 'base.php';
	require_once DP_BASE_DIR.'/includes/config.php';
	require_once DP_BASE_DIR.'/includes/main_functions.php';
	require_once DP_BASE_DIR.'/includes/db_connect.php';
	require_once DP_BASE_DIR.'/classes/ui.class.php';
	require_once DP_BASE_DIR.'/classes/event_queue.class.php';
	require_once DP_BASE_DIR.'/classes/query.class.php';

	// Overlapping runs (a slow mail server, a short cron interval) would send the
	// same reminders twice.
	$lockFile = DP_BASE_DIR.'/files/temp/queuescanner.lock';
	$lock = @fopen($lockFile, 'c');
	if (!$lock) {
		fwrite(STDERR, "Cannot open $lockFile: run as the web server user, or fix its permissions.\n");
		exit(1);
	}
	if (!flock($lock, LOCK_EX | LOCK_NB)) {
		echo "Another queue scan is still running.\n";
		exit(0);
	}

	$AppUI = new CAppUI;
	$AppUI->setUserLocale();
	$perms =& $AppUI->acl();

	echo "Scanning Queue ...\n";
	$queue = new EventQueue;
	$queue->scan();
	echo 'Done, '.$queue->event_count.' events processed'."\n";
