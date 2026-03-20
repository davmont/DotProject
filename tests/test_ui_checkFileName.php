<?php
// Define DP_BASE_DIR if not already defined
if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
}

// Silence warnings from legacy code
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

// Minimal Test Framework
class TestCase {
    var $failures = array();
    var $tests = 0;
    var $assertions = 0;

    function assertEquals($expected, $actual, $message = '') {
        $this->assertions++;
        if ($expected !== $actual) {
            $this->failures[] = "Failure: " . ($message ? $message : '') . "\nExpected: " . print_r($expected, true) . "\nActual: " . print_r($actual, true);
        }
    }

    function assertTrue($condition, $message = '') {
        $this->assertions++;
        if ($condition !== true) {
            $this->failures[] = "Failure: " . ($message ? $message : 'Condition expected to be true');
        }
    }

    function assertFalse($condition, $message = '') {
        $this->assertions++;
        if ($condition !== false) {
            $this->failures[] = "Failure: " . ($message ? $message : 'Condition expected to be false');
        }
    }

    function setUp() {}
    function tearDown() {}

    function run() {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                $this->tests++;
                $this->setUp();
                $this->$method();
                $this->tearDown();
            }
        }
        $this->report();
    }

    function report() {
        echo "Ran {$this->tests} tests with {$this->assertions} assertions.\n";
        if (count($this->failures) > 0) {
            echo count($this->failures) . " failures:\n";
            foreach ($this->failures as $failure) {
                echo "--------------------------------------------------\n";
                echo $failure . "\n";
            }
            echo "--------------------------------------------------\n";
            echo "FAIL\n";
            exit(1);
        } else {
            echo "OK\n";
            exit(0);
        }
    }
}

// Mock global functions used by CAppUI
function dPgetConfig($name, $default = null) {
    if ($name == 'host_locale') return 'en';
    if ($name == 'locale_warn') return false;
    if ($name == 'locale_alert') return '^';
    return $default;
}

function dPshowImage($url, $w=0, $h=0, $alt='') { return ''; }
function dPfindImage($name, $module='') { return ''; }
function dPcontextHelp($img, $ref) { return ''; }
function getPermission($mod, $perm) { return true; }
function dPsanitiseHTML($str) { return $str; }
function getauth($method) { return null; } // Mock getauth if needed

// Include the class to test
require_once DP_BASE_DIR . '/classes/ui.class.php';

// Mock CAppUI to override redirect
class MockAppUI extends CAppUI {
    var $redirected = false;
    var $redirect_params = '';

    function redirect($params='', $hist='') {
        $this->redirected = true;
        $this->redirect_params = $params;
        // Do not exit
    }
}

class TestCheckFileName extends TestCase {
    var $appUI;

    function setUp() {
        global $AppUI;
        // Instantiate MockAppUI
        $this->appUI = new MockAppUI();
        $AppUI = $this->appUI;
    }

    function testSafeFileName() {
        global $AppUI;
        // A file name without bad chars and without dots should be safe
        $file = 'safe_file';
        $result = $AppUI->checkFileName($file);
        $this->assertEquals($file, $result);
        $this->assertFalse($AppUI->redirected, "Should not redirect for safe file: $file");
    }

    function testBadChars() {
        global $AppUI;
        // Bad chars: ;/\\\'()"$
        $bad_chars = array(';', '/', '\\', '\'', '(', ')', '"', '$');

        foreach ($bad_chars as $char) {
            // Reset redirection status
            $AppUI->redirected = false;

            $file = 'bad' . $char . 'file';
            $result = $AppUI->checkFileName($file);

            // It returns the file but redirects
            $this->assertEquals($file, $result);
            $this->assertTrue($AppUI->redirected, "Should redirect for char: $char in $file");
        }
    }

    function testDotInFileName() {
        global $AppUI;
        // The current implementation replaces bad chars with dots, then checks if result contains dot.
        // So if the file originally contains a dot, it should also redirect.

        $file = 'test.php';
        $AppUI->redirected = false;
        $result = $AppUI->checkFileName($file);

        $this->assertEquals($file, $result);
        $this->assertTrue($AppUI->redirected, "Should redirect for dot in filename: $file");

        $file = 'directory.name';
        $AppUI->redirected = false;
        $result = $AppUI->checkFileName($file);
        $this->assertTrue($AppUI->redirected, "Should redirect for dot in directory name: $file");
    }

    function testEmptyFileName() {
        global $AppUI;
        $file = '';
        $AppUI->redirected = false;
        $result = $AppUI->checkFileName($file);

        $this->assertEquals($file, $result);
        $this->assertFalse($AppUI->redirected, "Should not redirect for empty file name");
    }
}

// Run the tests
$test = new TestCheckFileName();
$test->run();

?>
