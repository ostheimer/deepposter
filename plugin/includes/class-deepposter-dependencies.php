<?php
/**
 * WordPress-Abhängigkeiten für DeepPoster
 */

// Prüfe ob WordPress geladen ist
if (!defined('ABSPATH')) {
    exit;
}

if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
    error_log('DeepPoster Debug - Lade Abhängigkeiten');
}

// WordPress-Funktionen
$required_files = array(
    ABSPATH . 'wp-admin/includes/post.php',
    ABSPATH . 'wp-admin/includes/taxonomy.php',
    ABSPATH . 'wp-includes/post.php',
    ABSPATH . 'wp-includes/pluggable.php',
    ABSPATH . 'wp-includes/category.php',
    ABSPATH . 'wp-includes/formatting.php',
    ABSPATH . 'wp-includes/link-template.php',
    ABSPATH . 'wp-includes/option.php',
    ABSPATH . 'wp-includes/http.php',
    ABSPATH . 'wp-includes/kses.php'
);

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Abhängigkeit nicht gefunden: ' . $file);
        }
        throw new Exception('Erforderliche WordPress-Datei nicht gefunden: ' . basename($file));
    }

    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - Lade Datei: ' . $file);
    }

    require_once($file);
}

// Prüfe ob wichtige Funktionen verfügbar sind
$required_functions = array(
    'wp_insert_post',
    'get_category',
    'get_edit_post_link',
    'get_permalink',
    'wp_remote_post',
    'wp_remote_get',
    'wp_remote_retrieve_body',
    'wp_remote_retrieve_response_code',
    'wp_remote_retrieve_headers',
    'is_wp_error',
    'wp_kses',
    'get_option',
    'plugin_dir_path'
);

foreach ($required_functions as $function) {
    if (!function_exists($function)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Funktion nicht gefunden: ' . $function);
        }
        throw new Exception('Erforderliche WordPress-Funktion nicht gefunden: ' . $function);
    }
}

if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
    error_log('DeepPoster Debug - Alle Abhängigkeiten geladen');
} 