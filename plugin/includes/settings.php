<?php
defined('ABSPATH') || exit;

add_action('admin_init', function() {
    // API Provider Settings
    register_setting('deepposter_settings', 'deepposter_api_provider');
    register_setting('deepposter_settings', 'deepposter_openai_key');
    register_setting('deepposter_settings', 'deepposter_deepseek_key');
    
    // Model Settings
    register_setting('deepposter_settings', 'deepposter_model');
    register_setting('deepposter_settings', 'deepposter_max_tokens');

    add_settings_section(
        'provider_settings',
        'AI Provider Einstellungen',
        function() {
            echo '<p>Konfigurieren Sie Ihre KI-API-Zugangsdaten</p>';
        },
        'deepposter_settings'
    );

    // API Provider Selection
    add_settings_field(
        'api_provider',
        'KI-Anbieter',
        function() {
            $provider = get_option('deepposter_api_provider', 'openai');
            echo '<select name="deepposter_api_provider" id="api_provider">
                    <option value="openai" '.selected($provider, 'openai', false).'>OpenAI</option>
                    <option value="deepseek" '.selected($provider, 'deepseek', false).'>DeepSeek</option>
                    <option value="anthropic" '.selected($provider, 'anthropic', false).'>Anthropic</option>
                  </select>';
        },
        'deepposter_settings',
        'provider_settings'
    );

    // API Keys
    add_settings_field(
        'openai_key',
        'OpenAI API Key',
        function() {
            $value = esc_attr(get_option('deepposter_openai_key'));
            echo '<input type="password" name="deepposter_openai_key" value="'.$value.'" class="regular-text">';
        },
        'deepposter_settings',
        'provider_settings'
    );

    add_settings_field(
        'deepseek_key',
        'DeepSeek API Key',
        function() {
            $value = esc_attr(get_option('deepposter_deepseek_key'));
            echo '<input type="password" name="deepposter_deepseek_key" value="'.$value.'" class="regular-text">';
        },
        'deepposter_settings',
        'provider_settings'
    );

    // Model Configuration
    add_settings_field(
        'model_selection',
        'Modellauswahl',
        function() {
            $model = get_option('deepposter_model', 'gpt-4');
            echo '<div class="model-selection-wrapper">
                    <select name="deepposter_model" id="model_selection" style="display: none;">
                        <option value="">Lade Modelle...</option>
                    </select>
                    <div id="loading-models" class="loading-indicator">Lade Modelle<span class="dots">...</span></div>
                  </div>';
        },
        'deepposter_settings',
        'provider_settings'
    );

    add_settings_field(
        'max_tokens',
        'Maximale Tokens',
        function() {
            $value = get_option('deepposter_max_tokens', 10000);
            echo '<input type="number" id="max_tokens" name="deepposter_max_tokens" 
                  min="1" max="128000" step="1" value="'.$value.'" 
                  style="width: 150px; padding: 5px;">';
            echo '<p class="description">
                    Maximale Anzahl der Tokens pro Anfrage. Empfohlene Werte:
                    <br>- GPT-4 Modelle: bis zu 128.000 Tokens
                    <br>- GPT-3.5-Turbo-16k: bis zu 16.000 Tokens
                    <br>- Andere Modelle: bis zu 8.000 Tokens
                  </p>';
        },
        'deepposter_settings',
        'provider_settings'
    );
}); 