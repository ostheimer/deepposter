<?php
/**
 * Template für die DeepPoster Status-Seite
 */

// Direktzugriff verhindern
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('DeepPoster System Status', 'deepposter'); ?></h1>
    
    <div class="deepposter-status-section">
        <h2><?php echo esc_html__('System Information', 'deepposter'); ?></h2>
        <table class="widefat" style="margin-bottom: 20px;">
            <tbody>
                <tr>
                    <td><strong><?php echo esc_html__('PHP Version', 'deepposter'); ?></strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('WordPress Version', 'deepposter'); ?></strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('DeepPoster Version', 'deepposter'); ?></strong></td>
                    <td><?php echo DEEPPOSTER_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Debug Mode', 'deepposter'); ?></strong></td>
                    <td><?php echo defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG ? 'Aktiviert' : 'Deaktiviert'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="deepposter-status-section">
        <h2><?php echo esc_html__('API Status', 'deepposter'); ?></h2>
        <table class="widefat" style="margin-bottom: 20px;">
            <tbody>
                <tr>
                    <td><strong><?php echo esc_html__('API Provider', 'deepposter'); ?></strong></td>
                    <td><?php echo esc_html(get_option('deepposter_api_provider', 'openai')); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('API Key konfiguriert', 'deepposter'); ?></strong></td>
                    <td><?php 
                        $api_key = get_option('deepposter_openai_key');
                        echo !empty($api_key) ? '✅ Ja' : '❌ Nein';
                    ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Ausgewähltes Modell', 'deepposter'); ?></strong></td>
                    <td><?php echo esc_html(get_option('deepposter_model', 'gpt-4')); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="deepposter-status-section">
        <h2><?php echo esc_html__('Plugin Status', 'deepposter'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php echo esc_html__('Custom Post Types', 'deepposter'); ?></strong></td>
                    <td><?php 
                        $post_types = get_post_types(['_builtin' => false], 'names');
                        echo in_array('deepposter_prompt', $post_types) ? '✅ Registriert' : '❌ Nicht registriert';
                    ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Gespeicherte Prompts', 'deepposter'); ?></strong></td>
                    <td><?php 
                        $prompt_count = wp_count_posts('deepposter_prompt');
                        echo esc_html($prompt_count->publish . ' Prompts');
                    ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Berechtigungen', 'deepposter'); ?></strong></td>
                    <td><?php 
                        echo current_user_can('manage_options') ? '✅ Administrator' : '❌ Eingeschränkt';
                    ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.deepposter-status-section {
    margin-bottom: 30px;
}
.widefat td {
    padding: 12px;
}
.widefat td:first-child {
    width: 200px;
}
</style>

<?php
// Deutsche Übersetzungen
$translations = array(
    'DeepPoster System Status' => 'DeepPoster System Status',
    'System Information' => 'System Informationen',
    'PHP Version' => 'PHP Version',
    'WordPress Version' => 'WordPress Version',
    'DeepPoster Version' => 'DeepPoster Version',
    'Debug Mode' => 'Debug-Modus',
    'API Status' => 'API Status',
    'API Provider' => 'API Anbieter',
    'API Key konfiguriert' => 'API-Schlüssel konfiguriert',
    'Ausgewähltes Modell' => 'Ausgewähltes Modell',
    'Plugin Status' => 'Plugin Status',
    'Custom Post Types' => 'Benutzerdefinierte Beitragstypen',
    'Gespeicherte Prompts' => 'Gespeicherte Prompts',
    'Berechtigungen' => 'Berechtigungen'
);
?> 