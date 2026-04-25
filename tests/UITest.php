<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/ui.class.php';

if (!function_exists('dPgetConfig')) {
    function dPgetConfig($key, $default = null) {
        return $default;
    }
}

if (!function_exists('dPfindImage')) {
    function dPfindImage($name, $module = null) {
        return "path/to/$name";
    }
}

if (!function_exists('dPshowImage')) {
    function dPshowImage($src, $wid = '', $hgt = '', $alt = '', $title = '') {
        return "<img src=\"$src\" />";
    }
}

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

    // Override translation function for tests to prevent relying on global state
    function _($str, $flags = 0) {
        return $str;
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

    function testSetMsg() {
        $ui = new TestCAppUI();
        // Since we bypassed the constructor, manually initialize the properties
        $ui->msg = '';
        $ui->msgNo = 0;

        // Initial state
        $this->assertEquals('', $ui->msg);
        $this->assertEquals(0, $ui->msgNo);

        // Set a message
        $ui->setMsg('Test Message');
        $this->assertEquals('Test Message', $ui->msg);
        $this->assertEquals(0, $ui->msgNo);

        // Overwrite message
        $ui->setMsg('New Message');
        $this->assertEquals('New Message', $ui->msg);
        $this->assertEquals(0, $ui->msgNo);

        // Append message
        $ui->setMsg('Appended', 0, true);
        $this->assertEquals('New Message Appended', $ui->msg);
        $this->assertEquals(0, $ui->msgNo);

        // Set message with type
        $ui->setMsg('Error Occurred', UI_MSG_ERROR);
        $this->assertEquals('Error Occurred', $ui->msg);
        $this->assertEquals(UI_MSG_ERROR, $ui->msgNo);
    }

    function testGetMsg() {
        $ui = new TestCAppUI();

        // Empty message
        $this->assertEquals('', $ui->getMsg());

        // Simple message (default type)
        $ui->setMsg('Test Message');
        $expected = '<table cellspacing="0" cellpadding="1" border="0"><tr><td></td><td class="message">Test Message</td></tr></table>';
        $this->assertEquals($expected, $ui->getMsg(false));

        // Test UI_MSG_OK
        $ui->setMsg('Success', UI_MSG_OK);
        $expected = '<table cellspacing="0" cellpadding="1" border="0"><tr><td><img src="path/to/stock_ok-16.png" /></td><td class="message">Success</td></tr></table>';
        $this->assertEquals($expected, $ui->getMsg(false));

        // Test UI_MSG_ALERT
        $ui->setMsg('Alert', UI_MSG_ALERT);
        $expected = '<table cellspacing="0" cellpadding="1" border="0"><tr><td><img src="path/to/rc-gui-status-downgr.png" /></td><td class="message">Alert</td></tr></table>';
        $this->assertEquals($expected, $ui->getMsg(false));

        // Test UI_MSG_WARNING
        $ui->setMsg('Warning', UI_MSG_WARNING);
        $expected = '<table cellspacing="0" cellpadding="1" border="0"><tr><td><img src="path/to/rc-gui-status-downgr.png" /></td><td class="warning">Warning</td></tr></table>';
        $this->assertEquals($expected, $ui->getMsg(false));

        // Test UI_MSG_ERROR
        $ui->setMsg('Error', UI_MSG_ERROR);
        $expected = '<table cellspacing="0" cellpadding="1" border="0"><tr><td><img src="path/to/stock_cancel-16.png" /></td><td class="error">Error</td></tr></table>';
        $this->assertEquals($expected, $ui->getMsg(false));

        // Test resetting (true by default)
        $ui->setMsg('Test Reset');
        $msg = $ui->getMsg(true);
        $this->assert($msg != '', "Message should not be empty");
        $this->assertEquals('', $ui->msg);
        $this->assertEquals(0, $ui->msgNo);
        $this->assertEquals('', $ui->getMsg());
    }
}
?>
