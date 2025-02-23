<?php
/**
 * Template for the DeepPoster settings page
 */

// If no direct access
if (!defined('WPINC')) {
    die;
}

// Ensure WordPress functions are available
if (!defined('ABSPATH')) {
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Load required JavaScript files
wp_enqueue_script('jquery');
wp_enqueue_script(
    'deepposter-admin',
    plugins_url('assets/js/deepposter-admin.js', dirname(dirname(__FILE__))),
    array('jquery'),
    '1.0.0',
    true
);

// Localize the script
wp_localize_script(
    'deepposter-admin',
    'deepposterAdmin',
    array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('deepposter_nonce'),
        'debug' => defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG
    )
);

// Ensure scripts are loaded
do_action('admin_enqueue_scripts');
?>

<div class="wrap">
    <h1>DeepPoster Settings</h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('deepposter_settings');
        do_settings_sections('deepposter_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api_provider">AI Provider</label>
                </th>
                <td>
                    <select name="deepposter_api_provider" id="api_provider">
                        <option value="openai" <?php selected(get_option('deepposter_api_provider'), 'openai'); ?>>OpenAI</option>
                        <option value="deepseek" <?php selected(get_option('deepposter_api_provider'), 'deepseek'); ?>>DeepSeek</option>
                    </select>
                    <p class="description">Select your AI provider</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="openai_key">OpenAI API Key</label>
                </th>
                <td>
                    <input type="password" 
                           id="openai_key" 
                           name="deepposter_openai_key" 
                           value="<?php echo esc_attr(get_option('deepposter_openai_key')); ?>" 
                           class="regular-text">
                    <p class="description">Enter your OpenAI API key</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="deepseek_key">DeepSeek API Key</label>
                </th>
                <td>
                    <input type="password" 
                           id="deepseek_key" 
                           name="deepposter_deepseek_key" 
                           value="<?php echo esc_attr(get_option('deepposter_deepseek_key')); ?>" 
                           class="regular-text">
                    <p class="description">Enter your DeepSeek API key</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="model_selection">Model Selection</label>
                </th>
                <td>
                    <select name="deepposter_model" id="model_selection">
                        <option value="gpt-4" <?php selected(get_option('deepposter_model'), 'gpt-4'); ?>>GPT-4</option>
                        <option value="gpt-3.5-turbo" <?php selected(get_option('deepposter_model'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                        <option value="deepseek-chat" <?php selected(get_option('deepposter_model'), 'deepseek-chat'); ?>>DeepSeek Chat</option>
                    </select>
                    <p class="description">Select the AI model to use</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_tokens">Max Tokens</label>
                </th>
                <td>
                    <input type="number" 
                           id="max_tokens" 
                           name="deepposter_max_tokens" 
                           value="<?php echo esc_attr(get_option('deepposter_max_tokens', 10000)); ?>"
                           min="1" 
                           max="128000" 
                           step="1">
                    <p class="description">Maximum number of tokens per request (1-128000)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="temperature">Temperature</label>
                </th>
                <td>
                    <input type="number" 
                           id="temperature" 
                           name="deepposter_temperature" 
                           value="<?php echo esc_attr(get_option('deepposter_temperature', 0.7)); ?>"
                           min="0" 
                           max="1" 
                           step="0.1">
                    <p class="description">AI creativity level (0 = conservative, 1 = creative)</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Save Settings'); ?>
    </form>
</div>

<style>
.form-table th {
    width: 200px;
}
.form-table input[type="number"] {
    width: 100px;
}
.form-table select {
    min-width: 200px;
}
.description {
    margin-top: 5px;
    color: #666;
}
</style>

<?php
// Deutsche Übersetzung der Einstellungsseite
$translations = array(
    'DeepPoster Settings' => 'DeepPoster Einstellungen',
    'AI Provider' => 'KI-Anbieter',
    'Select your AI provider' => 'Wählen Sie Ihren KI-Anbieter',
    'Enter your OpenAI API key' => 'Geben Sie Ihren OpenAI API-Schlüssel ein',
    'Enter your DeepSeek API key' => 'Geben Sie Ihren DeepSeek API-Schlüssel ein',
    'Model Selection' => 'Modellauswahl',
    'Select the AI model to use' => 'Wählen Sie das zu verwendende KI-Modell',
    'Max Tokens' => 'Maximale Tokens',
    'Maximum number of tokens per request (1-128000)' => 'Maximale Anzahl der Tokens pro Anfrage (1-128000)',
    'Temperature' => 'Temperatur',
    'AI creativity level (0 = conservative, 1 = creative)' => 'KI-Kreativitätslevel (0 = konservativ, 1 = kreativ)',
    'Save Settings' => 'Einstellungen speichern'
);
?> 