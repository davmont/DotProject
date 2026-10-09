<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* ID: taskimport.php 2007/04/10 12:46 weboholic */
$perms =& $AppUI->acl();
$pid = dPgetParam( $_POST,'project_id',0 );
$act = dPgetParam( $_POST,'bug_action',0 );
$canCreateTask = $perms->checkModule( 'tasks','add' );

if( $perms->checkModule( 'mantis','access' ) ) {
	// get usernam and password (a hash value only) of the currently browsing user
	$sql = new DBQuery();
	$sql -> addTable('users');
	$sql -> addQuery('user_username,user_password');
	$sql -> addWhere('user_id='.$AppUI->user_id);
	$sql -> exec();
	$row = $sql -> fetchRow();
	$username = $row[0];
	$password = $row[1];

	require_once( 'mantis.config.php' );
	$mantis = new PHPXMLRPCClient();
	$mantis->createClient( $cnf['mantisxmlrpc'] .'dpserver.php',$cnf['mantishost'],$cnf['mantisport'],$cnf['mantismethod'] );
	if( $cnf['mantismethod'] == 'https' ) require_once( 'mantis.config.ssl.php' );
	$mantis->setXMLRPCDebug(0);

	$mantis->resetRequest();
	$mantis->setFunction( 'MantisRPC' );
	$mantis->addArg( array($username,$password) );
	$mantis->addArg( 'getUserAccessLevel' );
	$mantis->addArg( $username );
	$level = $mantis->call();

	if( file_exists( dPgetConfig('root_dir')."/modules/tasks/tasks.class.php" ) && $act == 'task' && $canCreateTask ) {
		if( !($perms->checkModule('tasks', 'add')) ) $AppUI->redirect( "m=public&a=access_denied&err=noedit" );
		function createTask( $obj ) {
			// Include any files for handling module-specific requirements
			foreach( findTabModules( 'tasks','addedit' ) as $mod ) {
				$fname = dPgetConfig('root_dir') .'/modules/$mod/tasks_dosql.addedit.php';
				dprint(__FILE__, __LINE__, 3, "checking for $fname");
				if ( file_exists($fname) ) require_once $fname;
			}

			// If we have an array of pre_save functions, perform them in turn.
			if ( isset($pre_save) ) foreach( $pre_save as $pre_save_function ) $pre_save_function();
			else dprint( __FILE__,__LINE__,1,'No pre_save functions.' );

			$msg = $obj->store();
			if( $msg ) return false;
			if( isset($post_save) ) foreach( $post_save as $post_save_function ) $post_save_function();
			if( $notify ) if ( $msg = $obj->notify($comment) ) $AppUI->setMsg( $msg,UI_MSG_ERROR );

			return true;
		}

		require_once( dPgetConfig('root_dir').'/modules/tasks/tasks.class.php' );

		$count = count($_POST['bugs']);
		if( $count == 0 ) $AppUI->redirect( 'm=projects&a=view&project_id='. $pid );
		if( $pid == '' || $pid == 0 ) $AppUI->redirect( 'm=projects&a=view&project_id='. $pid );

		foreach( $_POST['bugs'] as $b ) {
			$mantis->resetRequest();
			$mantis->setFunction( 'MantisRPC' );
			$mantis->addArg( array($username,$password) );
			$mantis->addArg( 'getMantisBugById' );
			$mantis->addArg($b);
			$result = $mantis->call();
			if( ERROR::isError($result) ) die($result->getErrstr());

			$obj = new CTask();
			$obj->task_id = false;
			$obj->task_name = $result['summary'];
			$obj->task_project = $_POST['project_id'];
			$obj->task_start_date = date( "Y-m-d h:i:s", time() );
			$obj->task_end_date = date( "Y-m-d h:i:s", time() + (7*24*60*60) );
			$obj->task_description = $result['description'];
			$obj->task_owner = $AppUI->user_id;
			$obj->task_milestone = false;
			$obj->task_type = 3;
			$result = createTask($obj);
			if( !$result ) die( 'Task not added!' );
		}
		$AppUI->redirect( 'm=tasks&a=addedit&task_id='. $obj->task_id );
	} elseif( !$canCreateTask ) {
		$AppUI->redirect('m=public&a=access_denied');
	} else {
		die( 'Module "Tasks" not found!' );
	}
} else {
	$AppUI->redirect('m=public&a=access_denied');
}
?>