<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/ui.class.php';

class TestCAppUI extends CAppUI {
    var $redirected = false;
    var $redirect_params = '';

    function __construct() {
        // Bypass parent constructor
    }

    function redirect($params='', $hist='') {
        $this->redirected = true;
        $this->redirect_params = $params;
        // Do not exit
    }
}

class UITest extends TestCase {
    function testCheckFileName() {
        global $AppUI;
        // Save the original AppUI global
        $originalAppUI = $AppUI;

        // Set up our mock
        $ui = new TestCAppUI();
        $AppUI = $ui;

        // Test safe file
        $file = 'safe_file';
        $ui->redirected = false;
        $result = $ui->checkFileName($file);
        $this->assertEquals($file, $result);
        $this->assert(!$ui->redirected, "Should not redirect for safe file: $file");

        // Test bad characters
        $bad_chars = array(';', '/', '\\', '\'', '(', ')', '"', '$');
        foreach ($bad_chars as $char) {
            $ui->redirected = false;
            $file = 'bad' . $char . 'file';
            $result = $ui->checkFileName($file);
            $this->assertEquals($file, $result);
            $this->assert($ui->redirected, "Should redirect for char: $char in $file");
            $this->assertEquals('m=public&a=access_denied', $ui->redirect_params);
        }

        // Test dot in filename
        $ui->redirected = false;
        $file = 'test.php';
        $result = $ui->checkFileName($file);
        $this->assertEquals($file, $result);
        $this->assert($ui->redirected, "Should redirect for dot in filename: $file");

        // Test empty filename
        $ui->redirected = false;
        $file = '';
        $result = $ui->checkFileName($file);
        $this->assertEquals($file, $result);
        $this->assert(!$ui->redirected, "Should not redirect for empty file name");

        // Restore original AppUI
        $AppUI = $originalAppUI;
    }

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
