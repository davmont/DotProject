<?php
class DBQueryTest extends TestCase {
    function testSanitise() {
        $q = new DBQuery('test_');

        // Happy path
        $this->assertEquals('cleanstring', $q->sanitise('cleanstring'));

        // Edge cases
        $this->assertEquals('cleanstring', $q->sanitise("clean'string"));
        $this->assertEquals('cleanstring', $q->sanitise('clean"string'));
        $this->assertEquals('cleanstring', $q->sanitise('clean(string'));
        $this->assertEquals('cleanstring', $q->sanitise('clean)string'));
        $this->assertEquals('cleanstring', $q->sanitise('clean;string'));
        $this->assertEquals('cleanstring', $q->sanitise('clean--string'));

        // Combined
        $this->assertEquals('cleanstring', $q->sanitise("c'l\"e(a)n;s--tring"));

        // SQL injection attempts
        // The sanitise function only removes specific characters, it doesn't remove keywords.
        $this->assertEquals('DELETE FROM users', $q->sanitise('DELETE FROM users'));
        $this->assertEquals('OR 1=1', $q->sanitise('OR 1=1'));

        // It removes quote and double dash
        $this->assertEquals('admin ', $q->sanitise("admin' --"));

        // It removes quote, semicolon and double dash
        $this->assertEquals('admin DROP TABLE users ', $q->sanitise('admin"; DROP TABLE users; --'));
    }

    function testLimit() {
        $q = new DBQuery('test_');

        // Initial state
        $this->assertEquals(null, $q->limit);
        $this->assertEquals(-1, $q->offset);

        // setLimit with only limit
        $q->setLimit(10);
        $this->assertEquals(10, $q->limit);
        $this->assertEquals(-1, $q->offset);

        // setLimit with limit and offset
        $q->setLimit(20, 5);
        $this->assertEquals(20, $q->limit);
        $this->assertEquals(5, $q->offset);

        // addLimit with limit and offset
        $q->addLimit(30, 15);
        $this->assertEquals(30, $q->limit);
        $this->assertEquals(15, $q->offset);

        // addLimit with only limit (default start is 0)
        $q->addLimit(50);
        $this->assertEquals(50, $q->limit);
        $this->assertEquals(0, $q->offset);
    }
}
?>
