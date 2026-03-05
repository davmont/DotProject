<?php
define('DP_BASE_DIR', __DIR__);

require_once DP_BASE_DIR . '/tests/bootstrap.php';

// Mock some things CustomOptionList needs
function db_escape($str) {
    return addslashes($str);
}

class MockDB {
    public $queries = 0;
    public $insert_id = 1;
    function GenID($seq, $start) {
        return $this->insert_id++;
    }
    function ErrorMsg() { return ''; }
    function Execute($sql) {
        $this->queries++;
        return true;
    }
}
$db = new MockDB();

class RealDBQuery {
    var $type = 'select';
    var $table_list = null;
    var $where = array();
    var $update_list = array();
    var $value_list = array();

    function addTable($name) { $this->table_list = $name; }
    function addWhere($q) { $this->where[] = $q; }
    function addOrder($q) {}
    function setDelete($table) { $this->type = 'delete'; $this->table_list = $table; }
    function addInsert($field, $value) {
        $this->type = 'insert';
        $this->value_list[$field] = $value;
    }

    function exec() {
        global $db;
        $db->queries++;

        if ($this->type == 'select') {
            return new MockResultSet();
        }
        return true;
    }

    function clear() {
        $this->type = 'select';
        $this->table_list = null;
        $this->where = array();
        $this->update_list = array();
        $this->value_list = array();
    }
    function fetchRow() { return false; }
}

class MockResultSet {
    function fetchRow() { return false; }
}

class CustomOptionList {
    public $field_id;
    public $options;

    function __construct($field_id) {
        $this->field_id = $field_id;
        $this->options = array();
    }

    // ORIGINAL STORE METHOD
    function store() {
        global $db;

        if (!is_array($this->options)) {
            $this->options = array();
        }

        //load the dbs options and compare them with the options
        $q = new RealDBQuery;
        $q->addTable('custom_fields_lists');
        $q->addWhere('field_id = ' . $this->field_id);
        $q->addOrder('list_value');
        if (!$rs = $q->exec()) {
            $q->clear();
            return $db->ErrorMsg();
        }

        $dboptions = array();
        while ($opt_row = $q->fetchRow()) {
            $dboptions[$opt_row['list_option_id']] = $opt_row['list_value'];
        }
        $q->clear();

        $newoptions = array();
        $newoptions = array_diff($this->options, $dboptions);
        $deleteoptions = array_diff($dboptions, $this->options);

        $insert_error = '';
        $delete_error = '';

        //insert the new options
        foreach ($newoptions as $opt) {
            $optid = $db->GenID('custom_fields_option_id', 1);

            $q = new RealDBQuery;
            $q->addTable('custom_fields_lists');
            $q->addInsert('field_id', $this->field_id);
            $q->addInsert('list_option_id', $optid);
            $q->addInsert('list_value', db_escape($opt));

            if (!$q->exec()) {
                $insert_error .= $db->ErrorMsg();
            }
            $q->clear();
        }
        //delete the deleted options
        foreach ($deleteoptions as $opt => $value) {
            $q = new RealDBQuery;
            $q->setDelete('custom_fields_lists');
            $q->addWhere('list_option_id = ' . $opt);

            if (!$q->exec()) {
                $delete_error .= $db->ErrorMsg();
            }
            $q->clear();
        }

        return $insert_error . ' ' . $delete_error;
    }

    // OPTIMIZED STORE METHOD
    function store_optimized() {
        global $db;

        if (!is_array($this->options)) {
            $this->options = array();
        }

        //load the dbs options and compare them with the options
        $q = new RealDBQuery;
        $q->addTable('custom_fields_lists');
        $q->addWhere('field_id = ' . $this->field_id);
        $q->addOrder('list_value');
        if (!$rs = $q->exec()) {
            $q->clear();
            return $db->ErrorMsg();
        }

        $dboptions = array();
        while ($opt_row = $q->fetchRow()) {
            $dboptions[$opt_row['list_option_id']] = $opt_row['list_value'];
        }
        $q->clear();

        $newoptions = array();
        $newoptions = array_diff($this->options, $dboptions);
        $deleteoptions = array_diff($dboptions, $this->options);

        $insert_error = '';
        $delete_error = '';

        // insert the new options in batch
        if (count($newoptions) > 0) {
            $sql = "INSERT INTO custom_fields_lists (field_id, list_option_id, list_value) VALUES ";
            $values = [];
            foreach ($newoptions as $opt) {
                $optid = $db->GenID('custom_fields_option_id', 1);
                $values[] = "({$this->field_id}, {$optid}, '" . db_escape($opt) . "')";
            }
            $sql .= implode(', ', $values);
            if (!$db->Execute($sql)) {
                $insert_error .= $db->ErrorMsg();
            }
        }

        // delete the deleted options
        foreach ($deleteoptions as $opt => $value) {
            $q = new RealDBQuery;
            $q->setDelete('custom_fields_lists');
            $q->addWhere('list_option_id = ' . $opt);

            if (!$q->exec()) {
                $delete_error .= $db->ErrorMsg();
            }
            $q->clear();
        }

        return $insert_error . ' ' . $delete_error;
    }
}

$field_id = 999;
$list = new CustomOptionList($field_id);

$new_options = [];
for ($i = 0; $i < 500; $i++) {
    $new_options[] = "Option_" . $i;
}
$list->options = $new_options;

// Measure time original
$db->queries = 0;
$start = microtime(true);
$list->store();
$time1 = microtime(true) - $start;
$queries1 = $db->queries;

// Measure time optimized
$db->queries = 0;
$start = microtime(true);
$list->store_optimized();
$time2 = microtime(true) - $start;
$queries2 = $db->queries;

echo "Original (500 items):   {$time1}s | {$queries1} queries\n";
echo "Optimized (500 items):  {$time2}s | {$queries2} queries\n";
