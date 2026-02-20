<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/ui.class.php';

class TestCAppUI extends CAppUI {
    function __construct() {
        // Bypass parent constructor
    }
}

class TestUI extends TestCase {
    function testMakeFileNameSafe() {
        $ui = new TestCAppUI();

        // Happy path
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('file.txt'));
        $this->assertEquals('folder/file.txt', $ui->makeFileNameSafe('folder/file.txt'));

        // Single traversal
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('../file.txt'));
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('..\\file.txt'));

        // Multiple traversal
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('../../file.txt'));
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('..\\..\\file.txt'));

        // Mixed traversal
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('../..\\file.txt'));

        // In the middle
        $this->assertEquals('folder/file.txt', $ui->makeFileNameSafe('folder/../file.txt'));

        // Absolute path (not blocked by this function)
        $this->assertEquals('/etc/passwd', $ui->makeFileNameSafe('/etc/passwd'));
        $this->assertEquals('/file.txt', $ui->makeFileNameSafe('/../file.txt'));

        // Edge cases
        $this->assertEquals('', $ui->makeFileNameSafe(''));
        $this->assertEquals('', $ui->makeFileNameSafe('../'));

        // Tricky cases (documenting current behavior)
        // '....//' -> '../' because str_replace is not recursive
        $this->assertEquals('../', $ui->makeFileNameSafe('....//'));
    }
}
?>
