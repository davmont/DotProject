<?php


$diconfig_id = dPgetParam($_REQUEST, 'diconfig_id', 0);
$chgconfig = dPgetParam($_REQUEST, 'chgconfig', '');

if ($chgconfig != '')
  $AppUI->redirect('m=dataimport&a=config&diconfig_id=' . $diconfig_id);

// Get down to it.
$obj = new CDataImport;
if (! $obj->load($diconfig_id)) {
  $AppUI->setMsg('invalid format specified', UI_MSG_ERROR);
  $AppUI->redirect();
}

$datamap = $obj->getDataMap(true);

// First things first, see if we can open the given file
if (! isset($_FILES['importFile'])) {
  $AppUI->setMsg('No import file', UI_MSG_ERROR);
  $AppUI->redirect();
}

$upload_tmp = $_FILES['importFile']['tmp_name'];
$file_contents = file($upload_tmp);
if (! $file_contents || count($file_contents) < 2) {
  $AppUI->setMsg('Invalid import file', UI_MSG_ERROR);
  $AppUI->redirect();
}

// Now we have the file, the first line should be our field names.
$headers = explode($obj->diconfig_field_sep, $file_contents[0]);
if ($obj->diconfig_quoted) {
  foreach ($headers as $key => $val) {
    if ($obj->diconfig_quote_char) {
      $val = str_replace($obj->diconfig_quote_char, '', $val);
    }
    $headers[$key] = trim($val, " \"'\r\n");
  }
}
$head_offsets = array_flip($headers);

// See if we have a mapping that will match and collect together
// individual tables - as we will need to break up the query by
// table.
$tables = array();
foreach ($headers as $src) {
  if (isset($datamap[$src])) {
    $table = $datamap[$src]['dimap_target_table'];
    if (! isset($tables[$table]))
      $tables[$table] = array();
    $tables[$table][] = $src;
  }
}

// Determine what classes we need for each of the tables.
// If the allowedTable list is updated in index.php, this will need
// to be updated as well.
// If there is no class available it will try and create a direct
// query.  Otherwise it tries to use the class provided and its store
// method.
$classList = array(
  'projects' => array( 'projects', 'CProject'),
  'tasks' => array ( 'tasks', 'CTask')
);

// Now build each query for each row, and save it.
// May need to extend this logic to include class functions for checking
// data, etc.
$row_count = count($file_contents);
$q = new DBQuery;
$rows_done = 0;

$batch_size = 500;
$batches = array();
$dbprefix = dPgetConfig('dbprefix', '');

for ($i = 1; $i < $row_count; $i++) {
  $data = explode($obj->diconfig_field_sep, $file_contents[$i]);
  foreach ($tables as $table => $keys) {
    if (isset($classList[$table])) {
      list ($mod, $class) = $classList[$table];
      require_once $AppUI->getModuleClass($mod);
      $classobj = new $class;
      foreach ($keys as $key) {
	$field = $datamap[$key]['dimap_target_field'];
	$classobj->$field = clean_import_data($data[$head_offsets[$key]], $obj);
      }
      if ($msg = $classobj->store()) {
	$AppUI->setMsg($msg, UI_MSG_ERROR);
	$AppUI->redirect();
      }
    } else {
      if (!isset($batches[$table])) {
        $batches[$table] = array(
          'fields' => array(),
          'placeholders' => '(',
          'params' => array(),
          'count' => 0
        );
        $fields = array();
        $placeholders = array();
        foreach ($keys as $key) {
          $fields[] = $datamap[$key]['dimap_target_field'];
          $placeholders[] = '?';
        }
        $batches[$table]['fields'] = $fields;
        $batches[$table]['placeholders'] = '(' . implode(',', $placeholders) . ')';
      }

      foreach ($keys as $key) {
        $batches[$table]['params'][] = clean_import_data($data[$head_offsets[$key]], $obj);
      }
      $batches[$table]['count']++;

      if ($batches[$table]['count'] >= $batch_size) {
        $sql = "INSERT INTO " . $dbprefix . $table . " (" . implode(',', $batches[$table]['fields']) . ") VALUES ";
        $sql .= implode(',', array_fill(0, $batches[$table]['count'], $batches[$table]['placeholders']));
        if (!$GLOBALS['db']->Execute($sql, $batches[$table]['params'])) {
          $AppUI->setMsg(array('Error in import: ', $GLOBALS['db']->ErrorMsg()), UI_MSG_ERROR);
          $AppUI->redirect();
        }
        $batches[$table]['params'] = array();
        $batches[$table]['count'] = 0;
      }
    }
  }
  $rows_done++;
}

// flush remaining
foreach ($batches as $table => $batch) {
  if ($batch['count'] > 0) {
    $sql = "INSERT INTO " . $dbprefix . $table . " (" . implode(',', $batch['fields']) . ") VALUES ";
    $sql .= implode(',', array_fill(0, $batch['count'], $batch['placeholders']));
    if (!$GLOBALS['db']->Execute($sql, $batch['params'])) {
      $AppUI->setMsg(array('Error in import: ', $GLOBALS['db']->ErrorMsg()), UI_MSG_ERROR);
      $AppUI->redirect();
    }
  }
}
$row_count--;
$AppUI->setMsg("$rows_done of $row_count imported", UI_MSG_OK);
$AppUI->redirect();

function clean_import_data($data, &$obj)
{
  if ($obj->diconfig_quoted) {
    $data = trim($data, " \"'\r\n");
    if ($obj->diconfig_quote_char) {
      $data = str_replace($obj->diconfig_quote_char, '', $data);
    }
  } else {
    $data = trim($data);
  }
  return $data;
}
?>
