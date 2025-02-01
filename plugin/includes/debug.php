<?php
defined('ABSPATH') || exit;

class DeepPosterDebug {
    private static $instance = null;
    private $log_file;

    private function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/deepposter-debug.log';
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($message, $level = 'info') {
        if (!DEEPPOSTER_DEBUG) {
            return;
        }

        $timestamp = current_time('mysql');
        $formatted = sprintf(
            "[%s] [%s] %s\n",
            $timestamp,
            strtoupper($level),
            is_array($message) || is_object($message) ? print_r($message, true) : $message
        );

        error_log($formatted, 3, $this->log_file);
    }

    public function get_log_contents($lines = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $logs = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$logs) {
            return [];
        }

        return array_slice($logs, -$lines);
    }

    public function clear_log() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }

    public function get_system_info() {
        global $wpdb;
        
        return [
            'WordPress Version' => get_bloginfo('version'),
            'PHP Version' => phpversion(),
            'MySQL Version' => $wpdb->db_version(),
            'Plugin Version' => DEEPPOSTER_VERSION,
            'Debug Mode' => DEEPPOSTER_DEBUG ? 'Aktiviert' : 'Deaktiviert',
            'Memory Limit' => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time'),
            'Upload Max Filesize' => ini_get('upload_max_filesize'),
            'Post Max Size' => ini_get('post_max_size'),
            'Max Input Vars' => ini_get('max_input_vars'),
            'Display Errors' => ini_get('display_errors')
        ];
    }
}

// Globale Hilfsfunktion für einfaches Logging
function deepposter_log($message, $level = 'info') {
    DeepPosterDebug::get_instance()->log($message, $level);
} 