<?php
/**
 * Reparatur-Skript für duplizierte IDs im Custom Post Type deepposter_prompt
 * 
 * Dieses Skript identifiziert und korrigiert duplizierte IDs in der WordPress-Datenbank,
 * die zu Fehlern beim Laden der Prompts führen können.
 */

// Sicherheitscheck: Nur im Admin-Bereich ausführen
if (!defined('ABSPATH') || !is_admin()) {
    die('Direkter Zugriff nicht erlaubt.');
}

/**
 * Klasse zum Reparieren von duplizierten IDs
 */
class DeepPoster_Repair_Duplicate_IDs {
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Füge Admin-Menüpunkt hinzu
        add_action('admin_menu', array($this, 'add_repair_menu'));
        
        // Registriere AJAX-Handler
        add_action('wp_ajax_deepposter_repair_duplicate_ids', array($this, 'repair_duplicate_ids'));
    }
    
    /**
     * Fügt einen Menüpunkt zum Reparieren der Datenbank hinzu
     */
    public function add_repair_menu() {
        add_submenu_page(
            'deepposter',
            'Datenbank reparieren',
            'Datenbank reparieren',
            'manage_options',
            'deepposter-repair',
            array($this, 'render_repair_page')
        );
    }
    
    /**
     * Rendert die Reparatur-Seite
     */
    public function render_repair_page() {
        ?>
        <div class="wrap">
            <h1>DeepPoster - Datenbank reparieren</h1>
            
            <p>Dieses Tool repariert duplizierte IDs im Custom Post Type "deepposter_prompt", die zu Fehlern führen können.</p>
            
            <div class="notice notice-warning">
                <p><strong>Wichtig:</strong> Bitte erstellen Sie ein Backup Ihrer Datenbank, bevor Sie fortfahren.</p>
            </div>
            
            <button id="repairButton" class="button button-primary">Datenbank reparieren</button>
            
            <div id="repairResults" style="margin-top: 20px; padding: 15px; background: #f8f8f8; border: 1px solid #ddd; display: none;">
                <h3>Ergebnisse:</h3>
                <div id="repairOutput"></div>
            </div>
            
            <script>
                jQuery(document).ready(function($) {
                    $('#repairButton').on('click', function() {
                        $(this).prop('disabled', true).text('Repariere...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'deepposter_repair_duplicate_ids',
                                nonce: '<?php echo wp_create_nonce('deepposter_repair_nonce'); ?>'
                            },
                            success: function(response) {
                                $('#repairResults').show();
                                
                                if (response.success) {
                                    $('#repairOutput').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                                    if (response.data.details) {
                                        $('#repairOutput').append('<pre>' + response.data.details + '</pre>');
                                    }
                                } else {
                                    $('#repairOutput').html('<div class="notice notice-error"><p>Fehler: ' + response.data.message + '</p></div>');
                                }
                            },
                            error: function() {
                                $('#repairResults').show();
                                $('#repairOutput').html('<div class="notice notice-error"><p>Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.</p></div>');
                            },
                            complete: function() {
                                $('#repairButton').prop('disabled', false).text('Datenbank reparieren');
                            }
                        });
                    });
                });
            </script>
        </div>
        <?php
    }
    
    /**
     * AJAX-Handler zum Reparieren duplizierter IDs
     */
    public function repair_duplicate_ids() {
        // Überprüfe Nonce
        check_ajax_referer('deepposter_repair_nonce', 'nonce');
        
        // Überprüfe Berechtigungen
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        global $wpdb;
        $log = array();
        $fixed_count = 0;
        
        try {
            // Finde duplizierte Post-IDs
            $query = "
                SELECT post_name, COUNT(*) as count
                FROM {$wpdb->posts}
                WHERE post_type = 'deepposter_prompt'
                GROUP BY post_name
                HAVING COUNT(*) > 1
            ";
            
            $duplicates = $wpdb->get_results($query);
            $log[] = "Gefundene duplizierte Post-Namen: " . count($duplicates);
            
            if (empty($duplicates)) {
                $log[] = "Keine duplizierten Post-Namen gefunden.";
            } else {
                foreach ($duplicates as $duplicate) {
                    $log[] = "Repariere duplizierte Post-Namen: {$duplicate->post_name} ({$duplicate->count} Duplikate)";
                    
                    // Hole alle Posts mit diesem Namen
                    $posts = $wpdb->get_results($wpdb->prepare(
                        "SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'deepposter_prompt' AND post_name = %s",
                        $duplicate->post_name
                    ));
                    
                    // Behalte den ersten, ändere die anderen
                    $first = true;
                    foreach ($posts as $post) {
                        if ($first) {
                            $first = false;
                            continue;
                        }
                        
                        // Generiere einen neuen eindeutigen Namen
                        $new_name = $post->post_name . '-' . uniqid();
                        
                        // Aktualisiere den Post
                        $wpdb->update(
                            $wpdb->posts,
                            array('post_name' => $new_name),
                            array('ID' => $post->ID)
                        );
                        
                        $log[] = "Post ID {$post->ID}: Name geändert von {$post->post_name} zu {$new_name}";
                        $fixed_count++;
                    }
                }
            }
            
            // Finde duplizierte Meta-Einträge
            $query = "
                SELECT post_id, meta_key, COUNT(*) as count
                FROM {$wpdb->postmeta}
                WHERE post_id IN (
                    SELECT ID FROM {$wpdb->posts} WHERE post_type = 'deepposter_prompt'
                )
                GROUP BY post_id, meta_key
                HAVING COUNT(*) > 1
            ";
            
            $duplicate_meta = $wpdb->get_results($query);
            $log[] = "Gefundene duplizierte Meta-Einträge: " . count($duplicate_meta);
            
            if (!empty($duplicate_meta)) {
                foreach ($duplicate_meta as $meta) {
                    $log[] = "Repariere duplizierte Meta-Einträge: Post ID {$meta->post_id}, Meta-Key {$meta->meta_key} ({$meta->count} Duplikate)";
                    
                    // Hole alle Meta-Einträge
                    $meta_entries = $wpdb->get_results($wpdb->prepare(
                        "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
                        $meta->post_id, $meta->meta_key
                    ));
                    
                    // Behalte den ersten, lösche die anderen
                    $first = true;
                    foreach ($meta_entries as $entry) {
                        if ($first) {
                            $first = false;
                            continue;
                        }
                        
                        // Lösche den duplizierten Meta-Eintrag
                        $wpdb->delete(
                            $wpdb->postmeta,
                            array('meta_id' => $entry->meta_id)
                        );
                        
                        $log[] = "Meta ID {$entry->meta_id} gelöscht";
                        $fixed_count++;
                    }
                }
            }
            
            // Bereinige die Datenbank
            $log[] = "Bereinige die Datenbank...";
            wp_cache_flush();
            
            if ($fixed_count > 0) {
                $message = "{$fixed_count} Probleme wurden behoben. Bitte laden Sie die Seite neu.";
            } else {
                $message = "Keine Probleme gefunden, die behoben werden müssten.";
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'details' => implode("\n", $log),
                'fixed_count' => $fixed_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Fehler bei der Reparatur: ' . $e->getMessage(),
                'details' => implode("\n", $log)
            ));
        }
    }
}

// Initialisiere die Reparatur-Klasse
new DeepPoster_Repair_Duplicate_IDs(); 