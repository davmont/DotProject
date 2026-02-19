<?php // $Id: db.php 4955 2007-05-26 01:35:42Z caseydk $
/** {{{
 * @license		http://www.gnu.org/licenses/gpl.txt GNU Public License (GPL)
 * @copyright	2003-2005 The dotProject Development Team <core-developers@dotproject.net>
 * 
 * @package		dotProject/install
 * @version		CVS: $Id: db.php 4955 2007-05-26 01:35:42Z caseydk $
 * }}}
 */
$baseDir = dirname(dirname(__FILE__));
define('DP_BASE_DIR', $baseDir);
?>
<html>
<head>
	<title>dotProject Installer</title>
	<meta name="Description" content="dotProject Installer" />
 	<link rel="stylesheet" type="text/css" href="../style/default/main.css" />
</head>
<body>
<h1><img src="dp.png" align="middle" alt="dotProject Logo" />&nbsp;dotProject Installer</h1>
<?php
if ($_POST['mode'] == 'upgrade') {
	@include_once '../includes/config.php';
} elseif (is_file("../includes/config.php")) {
	require_once 'check_upgrade.php';
	@include_once "../includes/config.php";
	if (dPcheckExistingDB($dPconfig)) {
		die('dotProject appears to already be installed, aborting install.');
	}
} else {
	@include_once "../includes/config-dist.php";
}
?>
<form name="instFrm" action="do_install_db.php" method="post">
<input type="hidden" name="mode" value="<?php echo htmlspecialchars($_POST['mode'], ENT_QUOTES); ?>" />
<table cellspacing="0" cellpadding="3" border="0" class="tbl" width="100%" align="center">
	<tr>
		<td class="title" colspan="2">Database Settings</td>
	</tr>
	<tr>
		<td class="item">Database Server Type <span class="warning">Note - currently only MySQL is known to work correctly</span></td>
		<td align="left">
		<select name="dbtype" size="1" style="width:200px;" class="text">
<?php
	if (strstr('WIN', strtoupper(PHP_OS)) !== false):
?>
			<option value="access" <?php if("access"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>MS Access</option>
			<option value="ado" <?php if("ado"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>Generic ADO</option>
			<option value="ado_access" <?php if("ado_access"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>ADO to MS Access Backend</option>
			<option value="ado_mssql" <?php if("ado_mssql"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>ADO to MS SQL Server</option>

			<option value="vfp" <?php if("vfp"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>MS Visual FoxPro</option>
			<option value="fbsql" <?php if("fbsql"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>FrontBase</option>
<?php
endif;
?>
			<option value="mysqli" <?php if("mysql"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>MySQL - Recommended</option>
						
			<option value="mysqlt" <?php if("mysqlt"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>MySQL With Transactions</option>
			<option value="maxsql" <?php if("maxsql"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>MySQL MaxDB</option>
			
			<option value="postgres" <?php if("postgres"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>Generic PostgreSQL</option>
			<option value="postgres64" <?php if("postgres64"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>PostreSQL 6.4 and earlier</option>
			<option value="postgres7" <?php if("postgres7"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>PostgreSQL 7</option>
			<option value="postgres8" <?php if("postgres8"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>PostgreSQL 8</option>
			<option value="postgres9" <?php if("postgres9"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>PostgreSQL 9</option>
			
			</select>
		</td>
	</tr>
	<tr>
		<td class="item">Database Host Name</td>
		<td align="left"><input class="button" type="text" name="dbhost" value="<?php echo htmlspecialchars($dPconfig['dbhost'], ENT_QUOTES); ?>" title="The Name of the Host the Database Server is installed on" /></td>
	</tr>
	<tr>
		<td class="item">Database Name</td>
		<td align="left"><input class="button" type="text" name="dbname" value="<?php echo htmlspecialchars($dPconfig['dbname'], ENT_QUOTES); ?>" title="The Name of the Database dotProject will use and/or install" /></td>
	</tr>
	<tr>
		<td class="item">Database Prefix</td>
		<td align="left"><input class="button" type="text" name="dbprefix" value="<?php echo htmlspecialchars($dPconfig['dbprefix'], ENT_QUOTES); ?>" title="The Prefix for the tables dotProject will use and/or install" /></td>
	</tr>
	<tr>
		<td class="item">Database User Name</td>
		<td align="left"><input class="button" type="text" name="dbuser" value="<?php echo htmlspecialchars($dPconfig['dbuser'], ENT_QUOTES); ?>" title="The Database User that dotProject uses for Database Connection" /></td>
	</tr>
	<tr>
		<td class="item">Database User Password</td>
		<td align="left"><input class="button" type="password" name="dbpass" value="<?php echo htmlspecialchars($dPconfig['dbpass'], ENT_QUOTES); ?>" title="The Password according to the above User." /></td>
	</tr>
	<tr>
		<td class="item">Use Persistent Connection?</td>
		<td align="left"><input type="checkbox" name="dbpersist" value="1" <?php echo ($dPconfig['dbpersist']==true) ? 'checked="checked"' : ''; ?> title="Use a persistent Connection to your Database Server." /></td>
	</tr>
<?php if ($_POST['mode'] == 'install'): ?>
	<tr>
		<td class="item">Drop Existing Database?</td>
		<td align="left"><input type="checkbox" name="dbdrop" value="1" title="Deletes an existing Database before installing a new one. This deletes all data in the given database. Data cannot be restored." /><span class="item"> If checked, existing Data will be lost!</span></td>
	</tr>
<?php endif; ?>
	</tr>
	<tr>
		<td class="title" colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td class="title" colspan="2">Download existing Data (Recommended)</td>
	</tr>
	<tr>
		<td class="item" colspan="2">Download a XML Schema File containing all Tables for the database entered above
		by clicking on the Button labeled 'Download XML' below. This file can be used with the Backup module to restore a previous system. Depending on database size and system environment this process can take some time.
		<br/>PLEASE CHECK THE RECEIVED FILE IMMEDIATELY FOR CONTENT AND CONSISTENCY AS ERROR MESSAGES ARE PRINTED INTO THIS FILE.<br/><br /><b>THIS FILE CAN ONLY BE RESTORED WITH A WORKING DOTPROJECT 2.x SYSTEM WITH THE BACKUP MODULE INSTALLED. DO NOT RELY ON THIS AS YOUR ONLY BACKUP.</b></td>
	</tr>
	<tr>
		<td class="item">Receive XML Backup Schema File</td>
		<td align="left"><input class="button" type="submit" name="dobackup" value="Download XML" title="Click here to retrieve a XML file containing your data that can be stored on your local system." /></td>
	</tr>
	<tr>
		<td align="left"><br /><input class="button" type="submit" name="do_db" value="<?php echo htmlspecialchars($_POST['mode'], ENT_QUOTES); ?> db only" title="Try to set up the database with the given information." />
		&nbsp;<input class="button" type="submit" name="do_cfg" value="write config file only" title="Write a config file with the details only." /></td>
		<td align="right" class="item"><br />(Recommended) &nbsp;<input class="button" type="submit" name="do_db_cfg" value="<?php echo htmlspecialchars($_POST['mode'], ENT_QUOTES); ?> db & write cfg" title="Write config file and setup the database with the given information." /></td>
	</tr>
</table>
</form>
</body>
</html>
