<?php
/**
 * Registriert Custom Post Types für DeepPoster
 */
class DeepPoster_Post_Types {
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Registriere die Post Types nur im init Hook
        add_action('init', array($this, 'register_post_types'));
    }

    /**
     * Registriert die Custom Post Types
     */
    public function register_post_types() {
        // Debug-Logging
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Registriere Post Types');
        }

        // Prompt Post Type
        $labels = array(
            'name'                  => 'Prompts',
            'singular_name'         => 'Prompt',
            'menu_name'            => 'Prompts',
            'name_admin_bar'       => 'Prompt',
            'add_new'              => 'Neuer Prompt',
            'add_new_item'         => 'Neuen Prompt erstellen',
            'new_item'             => 'Neuer Prompt',
            'edit_item'            => 'Prompt bearbeiten',
            'view_item'            => 'Prompt ansehen',
            'all_items'            => 'Alle Prompts',
            'search_items'         => 'Prompts durchsuchen',
            'not_found'            => 'Keine Prompts gefunden.',
            'not_found_in_trash'   => 'Keine Prompts im Papierkorb gefunden.'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'editor'),
            'has_archive'         => true,
            'rewrite'            => array('slug' => 'prompts'),
            'query_var'          => true,
            'show_in_rest'       => true,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-format-chat'
        );

        // Registriere den Post Type
        register_post_type('deepposter_prompt', $args);

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Post Type registriert');
        }

        // Prompt-Kategorien
        $tax_labels = array(
            'name'              => 'Prompt-Kategorien',
            'singular_name'     => 'Prompt-Kategorie',
            'search_items'      => 'Prompt-Kategorien durchsuchen',
            'all_items'         => 'Alle Prompt-Kategorien',
            'parent_item'       => 'Übergeordnete Prompt-Kategorie',
            'parent_item_colon' => 'Übergeordnete Prompt-Kategorie:',
            'edit_item'         => 'Prompt-Kategorie bearbeiten',
            'update_item'       => 'Prompt-Kategorie aktualisieren',
            'add_new_item'      => 'Neue Prompt-Kategorie hinzufügen',
            'new_item_name'     => 'Neue Prompt-Kategorie',
            'menu_name'         => 'Kategorien',
        );

        $tax_args = array(
            'hierarchical'      => true,
            'labels'            => $tax_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'show_in_rest'      => false,
            'rewrite'          => false
        );

        register_taxonomy('deepposter_prompt_cat', array('deepposter_prompt'), $tax_args);
    }
} 