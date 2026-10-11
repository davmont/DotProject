<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// We don't really need a class, more a collection of functions.

class CDataImport extends CDpObject {

  var $diconfig_id = null;
  var $diconfig_name = null;
  var $diconfig_field_sep = null;
  var $diconfig_quoted = null;
  var $diconfig_quote_char = null;
  var $diconfig_default = null;

  function CDataImport() 
  {
    $this->CDpObject('dataimport_config', 'diconfig_id');
  }

  function check()
  {
    if (! isset($this->diconfig_id))
      return 'Config ID is NULL';

    $this->diconfig_quoted = intval($this->diconfig_quoted);
    $this->diconfig_default = intval($this->diconfig_default);
    return null;
  }

  function getConfigList($name)
  {
    global $AppUI;
    $q = new DBQuery;
    $q->addTable('dataimport_config');
    $q->addOrder('diconfig_name');
    $q->exec( ADODB_FETCH_ASSOC );
    $count = 0;
    $result = '<select name="' . $name . '">';
    while ($row = $q->fetchRow()) {
      $count++;
      $result .= "\n";
      $result .= '<option value="' . $row['diconfig_id'] . '"';
      if ($row['diconfig_default'])
	$result .= ' selected="selected"';
      $result .= '>' . $AppUI->_($row['diconfig_name']) . '</option>';
    }
    $result .= "\n" . '</select>';
    $q->clear();
    if (! $count)
      return false;
    else
      return $result;
  }

  function getDataMap($index = false)
  {
    $q = new DBQuery;
    $q->addTable('dataimport_map');
    $q->addWhere('dimap_config_id = ' . (int)$this->diconfig_id);
    if ($index)
      return $q->loadHashList('dimap_source_field');
    else
      return $q->loadList();
  }

  function deleteDataMap($id)
  {
    $msg = '';
    $q = new DBQuery;
    $q->setDelete('dataimport_map');
    $q->addWhere('dimap_config_id = ' . (int)$id);
    if ( ! $q->exec()) {
      $msg = $GLOBALS['db']->ErrorMsg();
    }
    $q->clear();
    return $msg;
  }

  function saveDataMap($id, &$array)
  {
    // First off, delete anything that exists.
    $this->deleteDataMap($id);
    $q = new DBQuery;
    foreach ($array['dimap_source_field'] as $key => $val) {
      if ($val && @$array['dimap_target_table'][$key] && @$array['dimap_target_field'][$key]) {
	$q->addTable('dataimport_map');
	$q->addInsert('dimap_config_id', $id);
	$q->addInsert('dimap_source_field', $val);
	$q->addInsert('dimap_target_table', $array['dimap_target_table'][$key]);
	$q->addInsert('dimap_target_field', $array['dimap_target_field'][$key]);
	$q->exec();
	$q->clear();
      }
    }
  }

} // End of class


function diBuildSelect($name, $id, &$data, $default = '', $addSelect = false, $onChange = '')
{
  global $AppUI;

  $result = '<select class="text" name="'.$name.'['.$id.']" id="'.$name.'^'.$id.'"';
  if ($onChange) {
    $result .= ' onchange="'. $onChange . '"';
  }
  $result .= '>';
  if ($addSelect) {
    $result .= '<option value="">' . $AppUI->_(' -- Select From List -- ') . '</option>';
  }
  foreach ($data as $elem) {
    $result .= '<option value="' . $elem . '"';
    if ($elem == $default)
      $result .= ' selected="selected"';
    $result .= '>' . $elem . '</option>';
  }

  $result .= '</select>';
  return $result;
}



?>
