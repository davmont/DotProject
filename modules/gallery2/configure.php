<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

##
## Configure gallery2 integration 
##
## Sensorlink AS (c) 2006
## Vegard Fiksdal (fiksdal@sensorlink.no)
##

// Update database if we are saving
$action = @$_REQUEST["action"];
$q = new DBQuery();
if($action) {
        $gallery_uri= $_POST["gallery_uri"];
        $gallery_folder = $_POST["gallery_folder"];
        $gallery_user = $_POST["gallery_user"];

        if( $action == "add" ) {        	
        	$q -> addTable('gallery2');
        	$q -> addUpdate('gallery_uri',$gallery_uri);
        	$q ->exec();
        	
        	$q -> clear();
        	$q -> addTable('gallery2');
        	$q -> addUpdate('gallery_folder', $gallery_folder);
        	$q -> exec();
        	
        	$q -> clear();
        	$q -> addTable('gallery2');
        	$q -> addUpdate('gallery_user', $gallery_user);
        	$q -> exec();
     
		$AppUI->setMsg( db_error() );
		$AppUI->redirect();
	}
}

// Load database settings
$q -> clear();
$q -> addTable('gallery2');
$q -> addQuery('gallery_uri');
$gallery_uri=$q ->loadResult();

$q -> clearQuery();
$q -> addQuery('gallery_folder');
$gallery_folder = $q -> loadResult();

$q -> clearQuery();
$q -> addQuery('gallery_user');
$gallery_user= $q -> loadResult();

// Override if database failed
if(!$gallery_folder){
	$gallery_folder='/var/www/gallery2/';
}
if(!$gallery_uri){
	$gallery_uri='http://gallery.example.com/';
}

?>

<form name="ConfigureGallery" method="post">				
<table width="100%" border="0" cellpadding="0" cellspacing="1">
<input name="action" type="hidden" value="add"">
<tr>
	<td><img src="./images/icons/tasks.gif" alt="" border="0"></td>
	<td align='left' nowrap='nowrap' width='100%'><h1>Configure Gallery2 integration</h1></td>
</tr>
</table>

<table width='100%' border='0' cellpadding='1' cellspacing='1' class='std'>
<tr>
	<td nowrap="nowrap" align="right"><?php echo $AppUI->_( 'Gallery2 URI:' );?></td>
	<td nowrap="nowrap" align="left"><input type="text" class="text" size="100%" name="gallery_uri" value="<?php echo dPhtml($gallery_uri);?>"></td>
</tr>
<tr>
	<td nowrap="nowrap" align="right"><?php echo $AppUI->_( 'Gallery2 Local Folder:' );?></td>
	<td nowrap="nowrap" align="left"><input type="text" class="text" size="100%" name="gallery_folder" value="<?php echo dPhtml($gallery_folder);?>"></td>
</tr>
<tr>
	<td nowrap="nowrap" align="right"><?php echo $AppUI->_( 'Gallery2 Username:' );?></td>
	<td nowrap="nowrap" align="left"><input type="text" class="text" size="100%" name="gallery_user" value="<?php echo dPhtml($gallery_user);?>"></td>
</tr>
</table>	

<table border="0" cellspacing="0" cellpadding="3" width="100%">
<tr>
	<td height="40" width="30%">&nbsp;</td>
	<td  height="40" width="35%" align="right">
		<table>
		<tr>
			<td>
				<input class="button" type="button" name="save" value="<?php echo $AppUI->_('save'); ?>" onClick="submit()">
			</td>
		</tr>
		</table>
	</td>
</tr>
	
</table>
</form>		
</body>
</html>
