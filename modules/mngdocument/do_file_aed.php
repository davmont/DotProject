<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* FILES $Id: do_file_aed.php,v 1.1 2004/08/30 19:02:41 jcgonz Exp $ */

require_once("modules/mngdocument/files.class.php");

$file_id = intval( dPgetParam( $_POST, 'file_id', 0 ) );
$del = intval( dPgetParam( $_POST, 'del', 0 ) );
$padre = intval( dPgetParam( $_POST, 'padre', 0 ) );
$descripcion = dPgetParam( $_POST, 'descripcion', '' );
$dirname = dPgetParam( $_POST, 'dirname', '' );
$accion = dPgetParam( $_POST, 'accion', '' );
$actual = dPgetIntParam( $_POST, 'actual', 0 );
$delall = dPgetParam( $_POST, 'delall', '' );



$obj = new CFile("SGD","SGD_id");

$log = new CFileLog ("SGD_Logs","SGD_Log_id");

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'File' );
// delete the file
if ($del) {
	$obj->load( $file_id );
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} 
}

set_time_limit( 600 );
ignore_user_abort( 1 );

$log->SGD_Logs_user_id = $AppUI->user_id;
$log->SGD_Logs_date = Date("d/m/Y H:i");
$log->SGD_Logs_action = $accion;

switch ($accion)
{
 case "newdocument":
  $upload = null;
  if (isset( $_FILES['formfile'] )) 
  {
   $upload = $_FILES['formfile'];
   if ($upload['size'] < 1)
   {
    if (!$file_id)
    {
     $AppUI->setMsg( 'Upload file size is zero. Process aborted.', UI_MSG_ERROR );
     $AppUI->redirect();
    }
   }
   else
   {
 // store file with a unique name
    $obj->SGD_name = $upload['name'];
    $obj->SGD_type = 0;
    $obj->SGD_date= db_unix2dateTime( time() );
    $obj->SGD_parent = $padre;
    $obj->SGD_description = $descripcion;
    $res = $obj->moveTemp( $upload );
    if (!$res)
    {
     $AppUI->setMsg( 'File could not be written', UI_MSG_ERROR );
     $AppUI->redirect();
    }
   }
  }
 break;
 case "newdirectory":
  if (Trim($dirname)=="")
  {
   $AppUI->setMsg( "Debe de introducir un nombre para el directorio a crear.", UI_MSG_ERROR );
   $AppUI->redirect();
  }

  $obj->SGD_name = $dirname;
  $obj->SGD_type = 1;
  $obj->SGD_date= db_unix2dateTime( time() );
  $obj->SGD_parent = $padre;
  $obj->SGD_description = $descripcion;
 break;
 case "deldirectory":
  $sql="select SGD_id,SGD_type,SGD_name from SGD where SGD_parent=$actual";
  $resultado=db_exec($sql);
  if ($delall<>1 and db_num_rows($resultado)>0)
    $AppUI->setMsg( "El directorio contiene ficheros y/o subdirectorios. Imposible eliminar.", UI_MSG_ERROR );
  else
    BorrarDirectorio($actual,$log);
  $AppUI->redirect();
 break;
 case "deldocument":
  if ($delall==1)
  {
   $sql="select SGD_name from SGD where SGD_id=$actual";
   $resultado=db_exec($sql);
   $row=db_fetch_row($resultado);
   $obj->SGD_name=$row[0];
   $obj->SGD_id=$actual;
   $obj->deletefile();
   $log->SGD_Logs_document_name=$row[0];
   if (($msg = $log->store()))
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
  }
  $AppUI->redirect();
 break;
}

function BorrarDirectorio($actual,$log)
{

 $obj = new CFile();
 $obj->_tbl="SGD";
 $obj->_tbl_key="codigo";

 $sql="select SGD_id,SGD_type,SGD_name from SGD where SGD_parent=$actual";
 $resultado=db_exec($sql);
 while ($row=db_fetch_row($resultado))
  BorrarDirectorio($row[0],$log);
 
 $obj->SGD_id=$actual;
 $sql="select SGD_type,SGD_name from SGD where SGD_id=$actual";
 $resultado=db_exec($sql);
 $row=db_fetch_row($resultado);
 $log->SGD_Logs_document_name=$row[1];
 if ($row[0]==0)
 {
  $obj->SGD_name=$row[1];
  $obj->deletefile();
 }
 else
  $obj->deletedirectory();
 if (($msg = $log->store()))
   $AppUI->setMsg( $msg, UI_MSG_ERROR );
}

if (($msg = $obj->store())) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
} else {
	$obj->load($obj->file_id);
	$AppUI->setMsg( $file_id ? 'updated' : 'added', UI_MSG_OK, true );
        $log->SGD_Logs_document_name = $obj->SGD_name;
        $log->store();
}
$AppUI->redirect();
?>
