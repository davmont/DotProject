<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* ID: createproject.php 2007/04/10 12:46 weboholic */
global $AppUI;
$perms =& $AppUI->acl();
if( $perms->checkModule( 'mantis','access' ) ) {
	function syncMantis( $bool,$pid,$old_pname,$pdescr ) {
		global $db,$AppUI;
		require_once( 'mantis.config.php' );

		$mantis = new PHPXMLRPCClient();
		$mantis->createClient( $cnf['mantisxmlrpc'] .'dpserver.php',$cnf['mantishost'],$cnf['mantisport'],$cnf['mantismethod'] );
		if( $cnf['mantismethod'] == 'https' ) require_once( 'mantis.config.ssl.php' );
		$mantis->setXMLRPCDebug(0);
		$sql = new DBQuery();
		$sql -> addTable('users');
		$sql -> addQuery('user_username,user_password');
		$sql -> addWhere('user_id='.$AppUI->user_id );
		$sql -> exec();
		$row = $sql->fetchRow();
		$username = $row[0];
		$password = $row[1];

		$mantis->resetRequest();
		$mantis->setFunction( 'MantisRPC' );
		$mantis->addArg( array($username,$password) );
		$mantis->addArg( 'getUserAccessLevel' );
		$mantis->addArg( $username );
		$level = $mantis->call();

		if( $level >= 90 ) {
			$db->setFetchMode(ADODB_FETCH_NUM);
			if( $pid > 0 ) {
                               $sql ->clear();
                               $sql -> addTable('projects');
                               $sql -> addQuery('project_name,project_description');
                               $sql -> addWhere('project_id = '. $pid );
                               $sql -> exec();
				$row = $sql -> fetchRow();
				$project_name = $row[0];
				$project_description = $row[1];
			} else {
				$project_name = $old_pname;
				$project_description = stripslashes( $pdescr );
			}

			$mantis->resetRequest();
			$mantis->setFunction( 'MantisRPC' );
			$mantis->addArg( array($username,$password) );
			if( !$bool ) {
				$mantis->addArg( 'project_create' );
				$mantis->addArg( $project_name );
				$mantis->addArg( $project_description );
				$mantis->addArg( '10' );
			} else {
				$mantis->addArg( 'addEditProjectByName' );
				$mantis->addArg( $old_pname );
				$mantis->addArg( $project_name );
				$mantis->addArg( $project_description );
			}
			$result = $mantis->call();
			if( ERROR::isError($result) ) die( $result->getErrstr() );
		} else {
			$AppUI->redirect('m=public&a=access_denied');
		}
	}
}
?>