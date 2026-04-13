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

        // Additional types (null, int, float)
        $this->assertEquals('', $q->sanitise(null));
        $this->assertEquals('123', $q->sanitise(123));
        $this->assertEquals('0', $q->sanitise(0));
        $this->assertEquals('1.5', $q->sanitise(1.5));
    }
}
?>
