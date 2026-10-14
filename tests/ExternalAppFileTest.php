<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/includes/main_functions.php';

/**
 * dPexternalAppFile(): the path check for files that gallery2 includes and eventum reads.
 */
class ExternalAppFileTest extends TestCase {
    function testExistingFileOutsideUploadsIsAccepted() {
        $this->assertEquals(realpath(DP_BASE_DIR . '/includes/config-dist.php'),
            dPexternalAppFile(DP_BASE_DIR . '/includes/', 'config-dist.php'));
    }

    function testFilesUnderUploadDirectoryAreRefused() {
        $this->assertEquals(false, dPexternalAppFile(DP_BASE_DIR . '/files', 'index.html'));
        $this->assertEquals(false, dPexternalAppFile(DP_BASE_DIR . '/includes/../files', 'index.html'));
    }

    function testMissingFilesAndUrlsAreRefused() {
        $this->assertEquals(false, dPexternalAppFile(DP_BASE_DIR . '/includes', 'no-such-file.php'));
        $this->assertEquals(false, dPexternalAppFile(DP_BASE_DIR, 'includes'));
        $this->assertEquals(false, dPexternalAppFile('http://example.com', 'embed.php'));
    }
}
?>
