<?php
if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
}

// Mock global functions
$GLOBALS['mock_sysvals'] = array();

function dPgetSysVal($title) {
    global $mock_sysvals;
    return isset($mock_sysvals[$title]) ? $mock_sysvals[$title] : array();
}

function dPformSafe($txt) {
    if (is_array($txt)) {
        foreach ($txt as $k => $v) {
            $txt[$k] = dPformSafe($v);
        }
        return $txt;
    }
    // Simple mock for testing, real one does more
    return htmlspecialchars($txt);
}

function arraySelect($arr, $name, $attribs, $selected) {
    // Basic mock implementation of arraySelect matching main_functions.php logic
    // keys are values, values are labels
    $out = "\n" . '<select name="' . $name . '" ' . $attribs . '>';
    $did_selected = 0;
    foreach ($arr as $k => $v) {
        $sel = '';
        if ($k == $selected && !$did_selected) {
            $sel = ' selected="selected"';
            $did_selected = 1;
        }
        $out .= "\n\t" . '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
    }
    $out .= "\n</select>\n";
    return $out;
}

// Mock DBQuery
if (!class_exists('DBQuery')) {
    class DBQuery {
        var $tables = array();
        var $query = array();
        var $where = array();

        function addTable($table) { $this->tables[] = $table; }
        function addQuery($field) { $this->query[] = $field; }
        function addWhere($where) { $this->where[] = $where; }
        function loadResult() { return ''; } // Return empty for now
        function quote($str) { return "'" . addslashes($str) . "'"; }
    }
}

// Mock AppUI
if (!class_exists('CAppUI')) {
    class CAppUI {
        function setMsg($msg) {}
        function _($txt) { return $txt; }
    }
}
if (!isset($GLOBALS['AppUI'])) {
    $GLOBALS['AppUI'] = new CAppUI();
}
?>
