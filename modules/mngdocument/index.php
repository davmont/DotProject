<?php /* FILES $Id: index.php,v 1.2 2004/08/31 14:58:03 edeisoft Exp $ */
$AppUI->savePlace();

$ListaAbierta = dPgetParam( $_GET, 'ListaAbierta', '' );
$open = (int)dPgetParam( $_GET, 'open', '' );
$close = (int)dPgetParam( $_GET, 'close', '' );

require_once( $AppUI->getModuleClass( 'projects' ) );


$project = new CProject();
$projects = $project->getAllowedRecords( $AppUI->user_id, 'project_id,project_name', 'project_name', null, $extra );
$projects = arrayMerge( array( '0'=>$AppUI->_('All') ), $projects );

$CanEdit=!getDenyEdit( 'mngdocument' );

// setup the title block
$titleBlock = new CTitleBlock( 'Document Management System', 'folder5.png', $m, "$m.$a" );
if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new file').'">', '',
		'<form name="nuevodocumento" action="?m=mngdocument&a=addedit&accion=newdocument&padre=' .$open .'" method="post">', '</form>'
	);
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new directory').'">', '',
		'<form name="nuevodocumento" action="?m=mngdocument&a=addedit&accion=newdirectory&padre=' .$open .'" method="post">', '</form>'
	);
}
$titleBlock->show();

?>
<form name="mngdocument">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
</tr>
<tr>
	<td>
<table border="0" width="100%">
 <th><font size="1">Nombre</font></th>
 <th><font size="1">Tipo</font></th>
 <th><font size="1">Fecha</font></th>
 <th><font size="1">Comentario</font></th>

<?php
//Aquí se muestra la tabla de la estructura de directorios y ficheros
//Mostramos la estructura de directorios creada a partir del directorio SGD
//Primero los hijos de SGD

 function MostrarObjeto($Codigo,$Ident,$CodigoOpen,$ListaAbierta,$Repositorio)
 {
  $q = new DBQuery();
  $q -> addTable('SGD');
  $q -> addQuery('SGD_id,SGD_name,SGD_type,SGD_date,SGD_description');
  $q -> addWhere('SGD_parent = '.$Codigo);
  $q -> addOrder('SGD_type asc');

  $q -> exec();
  $blancos=str_repeat("&nbsp;&nbsp;&nbsp;",$Ident);
  while ($row=$q -> fetchRow())
  {
   $CanEdit=!getDenyEdit( 'mngdocument',$row[0] );
   $CanRead=!getDenyRead( 'mngdocument',$row[0] );

   if ($row[2]==0)
   {
    $s="<tr><font size=\"1\"><td>$blancos";
    if ($CanRead)
     $s.="<a href=\"\" onClick=\"window.open(" .dPjsAttr("./" .$AppUI->cfg['root_dir'] ."/modules/mngdocument/documentview.php?document_name=" .rawurlencode($row[1])) .");return false;\">" .dPhtml($row[1]) ."</a>";
    else
     $s.=dPhtml($row[1]);
    if ($CanEdit)
     $s.="&nbsp;<a href=\"?m=mngdocument&a=addedit&accion=deldocument&actual=$row[0]\"><img src=\"." .$AppUI->cfg['root_dir'] ."/modules/mngdocument/images/del.gif\" border=\"0\" width=\"7\"></a>";
    $s.="</td>";
    echo $s;
    echo "<td>F</td>";
    echo "<td>" .dPhtml($row[3]) ."</td>";
    echo "<td>" .dPhtml($row[4]) ."</td>";
    echo "</font></tr>";
   }
   else
   {
    $ListaAbiertaN="";
    foreach ($ListaAbierta as $Lista)
    {
     $ListaAbiertaN.="$Lista-";
    }
    if ($row[0]==$CodigoOpen)
    {
     array_push($ListaAbierta,$row[0]);
     $s="<tr><font size=\"1\"><td>$blancos";
     if ($CanRead)
      $s.="<a href=\"?m=mngdocument&ListaAbierta=$ListaAbiertaN&close=$row[0]\"><img src=\"." .$AppUI->cfg['root_dir'] ."/modules/mngdocument/images/open.png\" border=\"0\"></a>" .dPhtml($row[1]);
     else
      $s.=dPhtml($row[1]);
     if ($CanEdit)
      $s.="&nbsp;<a href=\"?m=mngdocument&a=addedit&accion=deldirectory&actual=$row[0]\"><img src=\"." .$AppUI->cfg['root_dir'] ."/modules/mngdocument/images/del.gif\" border=\"0\" width=\"7\"></a>";
     $s="</td>";
     echo $s;
     echo "<td>D</td>";
     echo "<td>" .dPhtml($row[3]) ."</td>";
     echo "<td>" .dPhtml($row[4]) ."</td>";
     echo "</font></tr>";

     MostrarObjeto($row[0],$Ident+1,$CodigoOpen,$ListaAbierta,$Repositorio);
    }
    else
    {
     if (in_array($row[0],$ListaAbierta))
     {
      $s="<tr><font size=\"1\"><td>$blancos";
      if ($CanRead)
       $s.="<a href=\"?m=mngdocument&ListaAbierta=$ListaAbiertaN&close=$row[0]\"><img src=\"." .$AppUI->cfg['root_dir'] ."/modules/mngdocument/images/open.png\" border=\"0\"></a>" .dPhtml($row[1]);
      else
       $s.=dPhtml($row[1]);
      if ($CanEdit)
       $s.="&nbsp;<a href=\"?m=mngdocument&a=addedit&accion=deldirectory&actual=$row[0]\"><img src=\"." .$AppUI->cfg['root_dir'] ."/modules/mngdocument/images/del.gif\" border=\"0\" width=\"7\"></a>";
      $s.="</td>";
      echo $s;
      echo "<td>D</td>";
      echo "<td>" .dPhtml($row[3]) ."</td>";
      echo "<td>" .dPhtml($row[4]) ."</td>";
      echo "</font></tr>";

      MostrarObjeto($row[0],$Ident+1,$CodigoOpen,$ListaAbierta,$Repositorio);
     }
     else
     {
      $s="<tr><font size=\"1\"><td>$blancos";
      if ($CanRead)
       $s.="<a href=\"?m=mngdocument&ListaAbierta=$ListaAbiertaN&open=$row[0]\"><img src=\"." .$AppUI->cfg['root_dir'] ."/modules/mngdocument/images/close.png\" border=\"0\"></a>" .dPhtml($row[1]);
      else
       $s.=dPhtml($row[1]);
      if ($CanEdit)
       $s.="&nbsp;<a href=\"?m=mngdocument&a=addedit&accion=deldirectory&actual=$row[0]\"><img src=\"." .$AppUI->cfg['root_dir'] ."/modules/mngdocument/images/del.gif\" border=\"0\" width=\"7\"></a>";
      $s.="</td>";
      echo $s;
      echo "<td>D</td>";
      echo "<td>" .dPhtml($row[3]) ."</td>";
      echo "<td>" .dPhtml($row[4]) ."</td>";
      echo "</font></tr>";
     }
    }
   }
  }
  return $ListaAbierta;
 }


 $Ident=0;
 if (!isset($ListaAbierta))
   $ListaAbierta=array();
 else
 {
  $ListaAbiertaN=array();
  while (strlen($ListaAbierta)>0)
  {
   $pos=strpos($ListaAbierta,"-");
   array_push($ListaAbiertaN,intval(substr($ListaAbierta,0,$pos)));
   $ListaAbierta=substr($ListaAbierta,$pos+1);
  }
  $ListaAbierta=$ListaAbiertaN;
 }



 if (isset($open))
   array_push($ListaAbierta,$open);

 if (isset($close))
 {
  $ListaAbiertaN=array();
  foreach ($ListaAbierta as $Lista)
  {
   if ($Lista!=$close)
    array_push($ListaAbiertaN,$Lista);
  }
  $ListaAbierta=$ListaAbiertaN;
 }


 $ListaAbierta=MostrarObjeto(0,$Ident,0,$ListaAbierta,$AppUI->cfg['base_url']);

?>
</table>
	</td>
</tr>
</table>
</form>
