<?php
defined('ABSPATH') || exit;

class DeepPosterScheduler {
    private static $instance = null;
    private $debug;

    private function __construct() {
        $this->debug = DeepPosterDebug::get_instance();
        
        // Registriere den täglichen Cron-Job
        if (!wp_next_scheduled('deepposter_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'deepposter_daily_maintenance');
        }
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function schedule_post($post_id, $publish_date) {
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error('permission_denied', 'Keine Berechtigung');
        }

        $timestamp = strtotime($publish_date);
        if ($timestamp === false) {
            return new WP_Error('invalid_date', 'Ungültiges Datum');
        }

        // Speichere das geplante Datum als Post-Meta
        update_post_meta($post_id, '_deepposter_scheduled', $timestamp);
        
        $this->debug->log(sprintf(
            'Artikel %d für Veröffentlichung am %s geplant',
            $post_id,
            date('Y-m-d H:i:s', $timestamp)
        ));

        return true;
    }

    public function unschedule_post($post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error('permission_denied', 'Keine Berechtigung');
        }

        delete_post_meta($post_id, '_deepposter_scheduled');
        
        $this->debug->log(sprintf(
            'Planung für Artikel %d aufgehoben',
            $post_id
        ));

        return true;
    }

    public function check_scheduled_posts() {
        global $wpdb;

        // Hole alle geplanten Posts
        $scheduled_posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, pm.meta_value as publish_date
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_deepposter_scheduled'
            AND p.post_status = 'draft'
        "));

        if (!$scheduled_posts) {
            $this->debug->log('Keine geplanten Posts gefunden');
            return;
        }

        $now = time();
        foreach ($scheduled_posts as $post) {
            if ($now >= intval($post->publish_date)) {
                // Veröffentliche den Post
                wp_update_post([
                    'ID' => $post->ID,
                    'post_status' => 'publish'
                ]);

                // Entferne das Scheduling-Meta
                delete_post_meta($post->ID, '_deepposter_scheduled');

                $this->debug->log(sprintf(
                    'Artikel "%s" (ID: %d) wurde veröffentlicht',
                    $post->post_title,
                    $post->ID
                ));
            }
        }
    }

    public function get_scheduled_posts() {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, pm.meta_value as publish_date
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_deepposter_scheduled'
            AND p.post_status = 'draft'
            ORDER BY pm.meta_value ASC
        "));
    }
}

// Initialisiere den Scheduler
add_action('init', function() {
    DeepPosterScheduler::get_instance();
}); 