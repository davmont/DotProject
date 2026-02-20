<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once DP_BASE_DIR . '/classes/query.class.php';

class DBQueryTest extends TestCase {
	function testAddWhere() {
		$q = new DBQuery();
		$q->addWhere("a=1");
		$this->assertEquals(array("a=1"), $q->where);
		$this->assertEquals(array(), $q->w_params);
	}

	function testAddWhereWithParams() {
		$q = new DBQuery();
		$q->addWhere("a=?", 1);
		$this->assertEquals(array("a=?"), $q->where);
		$this->assertEquals(array(1), $q->w_params);
	}

	function testAddWhereWithArrayParams() {
		$q = new DBQuery();
		$q->addWhere("a=?", array(1));
		$this->assertEquals(array("a=?"), $q->where);
		$this->assertEquals(array(1), $q->w_params);
	}

	function testAddWhereEmpty() {
		$q = new DBQuery();
		$q->addWhere('');
		$this->assertEquals(array(''), $q->where);
	}

	function testAddWhereNullParams() {
		$q = new DBQuery();
		$q->addWhere('a=?', null);
		$this->assertEquals(array('a=?'), $q->where);
		// If params is null, !is_array(null) is true. params becomes array(null).
		// foreach params as p -> p is null. w_params[] = null.
		$this->assertEquals(array(null), $q->w_params);
	}
}
?>
