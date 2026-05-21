<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

/**
 * A simple file-based rate limiter to prevent brute-force attacks.
 *
 * NOTE: This is a basic implementation. For high-traffic environments,
 * a more robust solution using an in-memory store like Redis or Memcached is recommended.
 */
class RateLimiter {
    private $ip;
    private $log_file;
    private $max_attempts;
    private $decay_seconds;

    public function __construct($identifier, $max_attempts = 10, $decay_seconds = 600) {
        // Use a combination of IP and the action to create a unique identifier
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->log_file = DP_BASE_DIR . '/files/temp/ratelimit_' . md5($identifier . '_' . $this->ip) . '.log';
        $this->max_attempts = $max_attempts;
        $this->decay_seconds = $decay_seconds;
    }

    /**
     * Checks if the current request is allowed.
     * @return bool True if allowed, false if rate-limited.
     */
    public function isAllowed() {
        $attempts = $this->getAttempts();
        return ($attempts < $this->max_attempts);
    }

    /**
     * Records a new attempt.
     */
    public function recordAttempt() {
        $attempts = $this->getAttempts();
        $attempts[] = time();
        file_put_contents($this->log_file, json_encode($attempts));
    }

    /**
     * Fetches and prunes the list of attempts.
     * @return array A list of timestamps of recent attempts.
     */
    private function getAttempts() {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $attempts = json_decode(file_get_contents($this->log_file), true);
        if (!is_array($attempts)) {
            return [];
        }

        // Purge old attempts
        $current_time = time();
        $valid_attempts = [];
        foreach ($attempts as $timestamp) {
            if ($current_time - $timestamp < $this->decay_seconds) {
                $valid_attempts[] = $timestamp;
            }
        }

        return $valid_attempts;
    }
}
?>
