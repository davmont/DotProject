<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* ID: index.php 2007/04/10 12:46 weboholic */

/*
 *	@(c) 		2007 MG Training GmbH <http://www.mgtraining.com>
 *	@license		GNU GPL
 *				This software works only with dotProject <http://dotproject.net> and
 *	 			as such is published under the license of dotProject itself - GNU GPL
 *
 *				This module is distributed in the hope that it will be useful,
 *				but WITHOUT ANY WARRANTY; without even the implied warranty
 *				of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *				See the GNU General Public License for more details.
 *
 *				This module has evolved from this dP module:
 *				http://blogs.lib.ncsu.edu/page/web?entry=mantis_and_dotproject_connection_module
 *				It has this licening information distrubuted with it:
 *				///////////////////////////////////////////////////////////////////////////////////////////
 *				This license is governed by United States copyright law, and with respect to 				//
 *				matters of tort, contract, and other causes of action it is governed by North 				//
 *				Carolina law, without regard to North Carolina choice of law provisions.  The 				//
 *				forum for any dispute resolution shall be in Wake County, North Carolina.					//
 *																							//
 *				Redistribution and use in source and binary forms, with or without modification, 				//
 *				are permitted provided that the following conditions are met:							//
 *				1. Redistributions of source code must retain the above copyright notice, this 				//
 *				list of conditions and the following disclaimer.										//
 *				2. Redistributions in binary form must reproduce the above copyright notice, 				//
 *				this list of conditions and the following disclaimer in the documentation and/or 				//
 *				other materials provided with the distribution.										//
 *				3. The name of the author may not be used to endorse or promote products derived 			//
 *				from this software without specific prior written permission.							//
 *																							//
 *				THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS 			//
 *				OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 		//
 *				WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 		//
 *				ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY 			//
 *				DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 		//
 *				DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 		//
 *				GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 				//
 *				INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, 			//
 *				WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 	//
 *				OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, 		//
 *				EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.						//
 *				///////////////////////////////////////////////////////////////////////////////////////////
 *
 *
 *	@autor		Nikola Ivanov <execute | weboholic> | n.ivanov@mgtraining.com
 *
 */



$perms =& $AppUI->acl();
if( $perms->checkModule( 'mantis','access' ) ) {
	// module configuration array,
	require_once( 'mantis.config.php' );
	GLOBAL $project_id,$task_id,$db;

	// create new XMLRPC client
	$mantis = new PHPXMLRPCClient();
	$mantis->createClient( $cnf['mantisxmlrpc'] .'dpserver.php',$cnf['mantishost'],$cnf['mantisport'],$cnf['mantismethod'] );
	if( $cnf['mantismethod'] == 'https' ) require_once( 'mantis.config.ssl.php' );

	// DEBUG option, enable if problems occure
	$mantis->setXMLRPCDebug(2);

	// draw page title in dotproject
	$AppUI->savePlace();
	$titleBlock = new CTitleBlock( 'Mantis Integrated','mantis_logo_button.gif',$m,"$m.$a" );
	$titleBlock->show();

	// get project_id if any
	$project_id = ( empty($_REQUEST['project_id']) ) ? 0 : $_REQUEST['project_id'];

	// get username, password (a hash value only) and email of the currently browsing user
	$sql = new DBQuery();
	$sql -> addTable('users','users');
	$sql -> addTable('contacts');
	$sql -> addQuery('user_username,user_password');
	$sql -> addWhere('user_id = '. $AppUI->user_id);
	$sql -> exec();
	$row = $sql->fetchRow();
	$username = $row[0];
	$password = $row[1];

	// get project names and ids
	$sql -> clear();
	$sql -> addTable('projects');
	$sql -> addQuery('project_name, project_id');

	if ( $project_id != 0 ){
          $sql -> addWhere('project_id='. (int) mysql_real_escape_string($project_id));
	}
	$res = $sql -> exec();
	while ( $row = $sql ->fetchRow() ) {
		$dpprojects[$row[1]] = trim( strtolower($row[0]) );
		if( $row[1] == $project_id ) $project_name = $row[0];
	}

	// we don't want to ignore mantis user permissions
	if( !empty($project_name) ) {
		$mantis->resetRequest();
		$mantis->setFunction( 'MantisRPC' );
		$mantis->addArg( array($username,$password) );
		$mantis->addArg( 'checkUserPermForProject' );
		$mantis->addArg( array($username,$project_name) );
		$res = $mantis->call();
	}

	if( $res == 2 ) {
		echo '<br /><br />Mantis returned an access denied code.';
		echo '<br /><br />Please contact the administrator.';
		die();
	}

	$mantis->resetRequest();
	$mantis->setFunction( 'MantisRPC' );
	$mantis->addArg( array($username,$password) );
	if( !empty($project_name) ) {
		$mantis->addArg( 'getMantisBugByProjectName' );
		$mantis->addArg( $project_name );
	} else {
		$mantis->addArg( 'getMantisBugByProjectId' );
		$mantis->addArg( '' );
	}
	$bugs = $mantis->call();
	if( ERROR::isError($bugs) ) die($bugs->getErrstr());

	// mantis user level is important!!!
	// we don't want to ignore mantis user permissions
	$mantis->resetRequest();
	$mantis->setFunction( 'MantisRPC' );
	$mantis->addArg( array($username,$password) );
	$mantis->addArg( 'getUserAccessLevel' );
	$mantis->addArg( $username );
	$level = $mantis->call();

	if( $bugs == 0 ) {
		if( $project_id == 0 ) {
			echo '<br /><br />Since no project was selected and Mantis result set was empty, I assume that either:';
			echo '<br /> - there are no projets created in Mantis or';
			echo '<br /> - You don\'t have enough privileges to view any project in Mantis.';
			echo '<br /><br />';
		} else {
			echo '<br /><br /><img src="./images/obj/error.gif" border="0" />  This project was not found in Mantis.';
			if( $level >= 90 ) {
				echo '<br /><br />';
				echo 'You can create the project in Mantis by <a href="./index.php?m=projects&a=addedit&project_id='.$project_id.'">re-enabling</a> the integration!';
			}
			echo '<br /><br />';
		}
	} else {
		if( is_array($bugs) ) {
			foreach($bugs as $key => $bug){
				if( in_array( trim( strtolower($bug['project_name']) ),$dpprojects ) ) {
					$bug['dp_project_id'] = array_search( trim( strtolower($bug['project_name']) ),$dpprojects );
					unset($bugs[$key]);
					$bugs[$key] = $bug;
				}
			}
		}
		if( is_array($bugs) && count($bugs) > 0 ) {
			$canCreateTask = $perms->checkModule( 'tasks','add' );
			// mantis user level is important!!!
			// we don't want to ignore mantis user permissions
			$canUpdateStatus = ( $level >= 55 ) ? true : false;
			$bool = ( ($canUpdateStatus || $canCreateTask) && $project_id != '' ) ? true : false;
			?>
			<br />
			<?php if( $bool ) { ?>
					<script language="javascript" type="text/javascript">
						function toggle_chckBoxes(that) {
							var d 	= document;
							var bxs	= d.getElementsByName('bugs[]');
							for( var i = 0; i < bxs.length; i++ ) {
								if( that.checked ) bxs[i].style.display = 'none';
								else bxs[i].style.display = 'block';
							}
							that.style.display = 'block';
						}

						function ctrlChck() {
							var d = document;
							var bxs = d.getElementsByName('bugs[]');
							for( var i = 0; i < bxs.length; i++ ) {
								if( bxs[i].checked ) {
									return i;
									break;
								}
							}
							alert('<?php echo $AppUI->_( 'Please select a issue first!' ); ?>');
							return 'false';
						}
						<?php if($canUpdateStatus) { ?>
						function updatestatus( bool ) {
							var d 	= document;
							var bxs = d.getElementsByName('bugs[]');
							d.getElementById('mantisframe').style.display = 'none';

							var i = ctrlChck();
							if( i == 'false' ) return false;

							if( bool ) {
								d.getElementById('loadbug').value = bxs[i].value;
								var old = d.getElementsByName('bugstatus[]')[i].value;
								var bxs = d.getElementsByName('newstatus[]');
								for( var i = 0; i < bxs.length; i++ ) {
									if( bxs[i].value == old ) {
										bxs[i].checked = true;
										bxs[i].disabled = true;
										break;
									}
								}
							} else {
								d.getElementById('loadbug').value = ''
								var bxs = d.getElementsByName('newstatus[]');
								for( var i = 0; i < bxs.length; i++ ) {
									bxs[i].checked = false;
									bxs[i].disabled = false;
								}
							}

							var ta1	= d.getElementById( 'mantis_buglisting' );
							var ta2	= d.getElementById( 'mantis_bugbuttoning' );
							var ta3	= d.getElementById( 'mantis_bugupdateing' );
							if( bool ) {
								ta1.style.display = 'none';
								ta2.style.visibility = 'hidden';
								ta3.style.visibility = 'visible';
							} else {
								ta1.style.display = 'block';
								ta2.style.visibility = 'visible';
								ta3.style.visibility = 'hidden';
							}
						}

						function noteform(that) {
							var d = document;
							var c = d.getElementsByName('btnCancel');
							for( var i = 0; i < c.length; i++ ) {
								c[i].style.display = 'none';
							}
							var f = d.getElementById('mantisframe');
							var muri = "<?php echo $cnf['mantismethod'].'://'.$cnf['mantishost'].'/'.$cnf['mantisuri'].'bug_change_status_page.php'; ?>";
							var bid = d.getElementById('loadbug').value;
							var nst = that.value;
							muri = muri + '?new_status=' + nst + '&bug_id=' + bid;
							f.src = muri;
							f.style.display = 'block';
						}
						<?php } ?>
					</script>
			<?php } ?>

			<?php if( $bool ) echo '<form method="POST" action="?m=mantis&a=taskimport" name="frmMantis">'; ?>
			<?php if( $bool ) echo '<input type="hidden" name="project_id" value="'. $project_id .'">'; ?>

			<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl" id="mantis_buglisting">
			<thead>
				<tr>
					<th><?php echo $AppUI->_('ID'); ?></th>					
					<th><?php echo $AppUI->_('Project'); ?></th>
					<th><?php echo $AppUI->_('User'); ?></th>
					<th><?php echo $AppUI->_('Severity');?>
					<th><?php echo $AppUI->_('Priority');?>
					<th><?php echo $AppUI->_('Status'); ?></th>
					<th><?php echo $AppUI->_('Reproducibility'); ?></th>
					<th><?php echo $AppUI->_('Resolution'); ?></th>
					<?php if( $bool ) { ?>
						<th><?php echo $AppUI->_('Selection'); ?></th>
					<?php } ?>
				</tr>
			</thead>
				<?php
					foreach( $bugs as $bug ) {
						$bugid = str_pad( $bug['bug_text_id'],7,'0',STR_PAD_LEFT );
						$bugprojname = $bug['project_name'];
						$bugsummary = $bug['summary'];
						$bugdesc = $bug['description'];
						$bugsev  = $bug['severity_name'];
						$bugpri  = $bug['priority_name'];
						$buguser = $bug['reporter_name'];						
						$bugstat = $bug['status_name'];
						$bugres  = $bug['resolution_name'];
						$bugrep  = $bug['reproducibility_name'];
						$dpprojid = $bug['dp_project_id'];
						
						?>
					<tbody>
						<tr>
							<td align="right" valign="top"><a href="<?php echo 'http://'. $cnf['mantishost'].'/'.$cnf['mantisuri'] .'view.php?id='. (int) $bugid; ?>" target="_blank" ><?php echo $bugid; ?></a></td>							
							<td align="left" valign="top"><?php echo $bugprojname; ?></td>
							<td align="left" valign="top"><?php echo $buguser; ?></td>
							<td align="left" valign="top"><?php echo $bugsev; ?>
							<td align="left" valign="top"><?php echo $bugpri; ?>
							<td align="left" style="background-color:<?php echo $status_color; ?>" valign="top"><strong><b><?php echo $bugstat ?></b></strong></td>
							<td align="left" valign="top"><?php echo $bugrep; ?>							
							<td align="left" valign="top"><?php echo $bugres; ?>
							<?php if( $bool ) { ?>
								<td align="center" valign="top">
									<input type="hidden" name="bugstatus[]" value="<?php echo $bugstat; ?>" />
									<input type="checkbox" name="bugs[]" value="<?php echo $bugid ?>" onclick="toggle_chckBoxes(this);">
								</td>
							<?php } ?>
						</tr>
						<?php
					} // end foreach
					?>
				</tbody>
			</table>
			<?php if( $bool ) { ?>
			<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl" id="mantis_bugbuttoning">
				<tr>
					<td align="right">
						<p align="right">
							<input type="hidden" name="bug_action" id="bug_action" value="" />
							<?php
								if( $canCreateTask ) {
									$msg = $AppUI->_( 'Due to technical restrictions, already imported issues will be dublicated!\nContinue?' );
									?>
									<input type="button" class="button" name="btnCreateTask" value="Import Task" onclick="if(ctrlChck() == 'false' ){return false;} document.getElementById('bug_action').value='task';if( confirm('<?php echo $msg; ?>') ) { submit(); } else { return false; }" />
									<?php
								}
								if( $canUpdateStatus ) {
									?>
									<input type="button" class="button" name="btnUpdateStatus" value="Change Status" onclick="updatestatus(true);" />
									<?php
								}
							?>
						</p>
					</td>
				</tr>
			</table>
			<?php } ?>
<?php if($canUpdateStatus) { ?>
			<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl" id="mantis_bugupdateing" style="visibility: hidden;">
			<tbody>
				<tr>
					<td align="right" valign="top" colspan="7">
						<input type="button" class="button" name="btnCancel" value="<?php echo $AppUI->_('Cancel'); ?>" onclick="updatestatus(false);" />
						&nbsp;
						<input type="button" class="button" name="btnReloadPage" value="<?php echo $AppUI->_( 'Load Changes to Overview' ); ?>" onclick="window.location.reload();" />
					</td>
				</tr>
				<tr>
					<th align="center" width="14%">New</th>
					<th align="center" width="14%">Feedback</th>
					<th align="center" width="15%">Acknowledged</th>
					<th align="center" width="15%">Confirmed</th>
					<th align="center" width="14%">Assigned</th>
					<th align="center" width="14%">Resolved</th>
					<th align="center" width="14%">Closed</th>
				</tr>
				<tr>
					<td align="center"><input type="checkbox" name="newstatus[]" value="10" onclick="noteform(this);" /></td>
					<td align="center"><input type="checkbox" name="newstatus[]" value="20" onclick="noteform(this);" /></td>
					<td align="center"><input type="checkbox" name="newstatus[]" value="30" onclick="noteform(this);" /></td>
					<td align="center"><input type="checkbox" name="newstatus[]" value="40" onclick="noteform(this);" /></td>
					<td align="center"><input type="checkbox" name="newstatus[]" value="50" onclick="noteform(this);" /></td>
					<td align="center"><input type="checkbox" name="newstatus[]" value="80" onclick="noteform(this);" /></td>
					<td align="center"><input type="checkbox" name="newstatus[]" value="90" onclick="noteform(this);" /></td>
				</tr>

				<tr><td align="right" valign="top" colspan="7" style="height: 20px;">&nbsp;</td></tr>

				<tr>
					<td colspan="7" align="center">
						<iframe name="mantisframe" id="mantisframe" src="" width="96%" height="500" frameborder="0" style="display: none;"></iframe>
					</td>
				</tr>

				<tr><td align="right" valign="top" colspan="7" style="height: 20px;">&nbsp;</td></tr>

				<tr>
					<td align="right" valign="top" colspan="7">
						<input type="button" class="button" name="btnCancel" value="<?php echo $AppUI->_('Cancel'); ?>" onclick="updatestatus(false);" />
						&nbsp;
						<input type="button" class="button" name="btnReloadPage" value="<?php echo $AppUI->_( 'Load Changes to Overview' ); ?>" onclick="window.location.reload();" />
					</td>
				</tr>
			</tbody>
			</table>
			<input type="hidden" name="loadbug" id="loadbug" value="" />
<?php } ?>
			</form>
			<?php
		} else {
			echo '<br /><br />Mantis result set was empty, I assume that either:';
			echo '<br /> - there are no bugs for this project or';
			echo '<br /> - You don\'t have enough privileges to view this project in Mantis.';
			echo '<br /><br />';
		}
	}
} else {
	$AppUI->redirect('m=public&a=access_denied');
}
?>
