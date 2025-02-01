<?php
/**
 * WordPress-Abhängigkeiten für DeepPoster
 */

// Prüfe ob WordPress geladen ist
if (!defined('ABSPATH')) {
    exit;
}

// WordPress-Funktionen
require_once(ABSPATH . 'wp-admin/includes/post.php');
require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');
require_once(ABSPATH . 'wp-includes/post.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-includes/category.php');
require_once(ABSPATH . 'wp-includes/formatting.php');
require_once(ABSPATH . 'wp-includes/link-template.php');
require_once(ABSPATH . 'wp-includes/option.php');
require_once(ABSPATH . 'wp-includes/http.php'); 