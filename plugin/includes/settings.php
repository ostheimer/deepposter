<?php
defined('ABSPATH') || exit;

add_action('admin_init', function() {
    // API Provider Settings
    register_setting('deepposter_settings', 'deepposter_api_provider');
    register_setting('deepposter_settings', 'deepposter_openai_key');
    register_setting('deepposter_settings', 'deepposter_deepseek_key');
    
    // Model Settings
    register_setting('deepposter_settings', 'deepposter_model', array(
        'default' => 'gpt-4'
    ));
    register_setting('deepposter_settings', 'deepposter_max_tokens', array(
        'default' => 10000
    ));
    register_setting('deepposter_settings', 'deepposter_temperature', array(
        'default' => 0.7
    ));

    add_settings_section(
        'provider_settings',
        'AI Provider Einstellungen',
        function() {
            echo '<p>Konfigurieren Sie Ihre KI-API-Zugangsdaten</p>';
        },
        'deepposter_settings'
    );

    // Füge CSS für einheitliches Design hinzu
    add_action('admin_head', function() {
        ?>
        <style>
            .deepposter-settings-field {
                margin-bottom: 20px;
            }
            .deepposter-settings-field label {
                display: inline-block;
                min-width: 150px;
                margin-bottom: 5px;
                font-weight: 600;
            }
            .deepposter-settings-field input[type="text"],
            .deepposter-settings-field input[type="password"],
            .deepposter-settings-field input[type="number"],
            .deepposter-settings-field select {
                width: 350px !important;
                height: 40px !important;
                padding: 8px 12px !important;
                font-size: 14px !important;
                line-height: 1.5 !important;
                border: 1px solid #8c8f94 !important;
                border-radius: 4px !important;
                background-color: #fff !important;
            }
            .deepposter-settings-field input:focus,
            .deepposter-settings-field select:focus {
                border-color: #2271b1 !important;
                box-shadow: 0 0 0 1px #2271b1 !important;
                outline: none !important;
            }
            .deepposter-settings-field .description {
                margin-top: 8px;
                color: #646970;
                font-size: 13px;
            }
            .deepposter-settings-field input[type="number"]::-webkit-inner-spin-button,
            .deepposter-settings-field input[type="number"]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .deepposter-settings-field input[type="number"] {
                -moz-appearance: textfield;
            }
            .model-selection-wrapper {
                display: flex;
                align-items: center;
                gap: 10px;
                width: 100%;
                max-width: 600px;
            }
            .model-selection-wrapper select {
                flex: 1;
                min-width: 350px;
            }
            #loading-models {
                display: none;
                height: 40px;
                line-height: 40px;
                padding: 0 12px;
                background: #f0f0f1;
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
            #refresh-models {
                height: 40px;
                margin-left: 10px;
            }
        </style>
        <?php
    });

    // API Provider Selection
    add_settings_field(
        'api_provider',
        'KI-Anbieter',
        function() {
            $provider = get_option('deepposter_api_provider', 'openai');
            echo '<div class="deepposter-settings-field">';
            echo '<select name="deepposter_api_provider" id="api_provider">
                    <option value="openai" '.selected($provider, 'openai', false).'>OpenAI</option>
                    <option value="deepseek" '.selected($provider, 'deepseek', false).'>DeepSeek</option>
                    <option value="anthropic" '.selected($provider, 'anthropic', false).'>Anthropic</option>
                  </select>';
            echo '</div>';
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
            echo '<div class="deepposter-settings-field">';
            echo '<input type="password" name="deepposter_openai_key" value="'.$value.'">';
            echo '</div>';
        },
        'deepposter_settings',
        'provider_settings'
    );

    add_settings_field(
        'deepseek_key',
        'DeepSeek API Key',
        function() {
            $value = esc_attr(get_option('deepposter_deepseek_key'));
            echo '<div class="deepposter-settings-field">';
            echo '<input type="password" name="deepposter_deepseek_key" value="'.$value.'">';
            echo '</div>';
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
            echo '<div class="deepposter-settings-field">';
            echo '<div class="model-selection-wrapper">';
            echo '<select name="deepposter_model" id="model_selection">
                    <option value="' . esc_attr($model) . '">' . esc_html($model) . '</option>
                  </select>';
            echo '<div id="loading-models" class="loading-indicator">Lade Modelle...</div>';
            echo '<button type="button" id="refresh-models" class="button">Modelle aktualisieren</button>';
            echo '</div>';
            echo '<p class="description">Aktuelle Modellauswahl: <strong>' . esc_html($model) . '</strong></p>';
            echo '</div>';
        },
        'deepposter_settings',
        'provider_settings'
    );

    add_settings_field(
        'max_tokens',
        'Maximale Tokens',
        function() {
            $value = get_option('deepposter_max_tokens', 10000);
            echo '<div class="deepposter-settings-field">';
            echo '<input type="number" id="max_tokens" name="deepposter_max_tokens" 
                  min="1" max="128000" step="1" value="'.$value.'">';
            echo '<p class="description">
                    Maximale Anzahl der Tokens pro Anfrage. Empfohlene Werte:
                    <br>- GPT-4 Modelle: bis zu 128000 Tokens
                    <br>- GPT-3.5-Turbo-16k: bis zu 16000 Tokens
                    <br>- Andere Modelle: bis zu 8000 Tokens
                  </p>';
            echo '</div>';
        },
        'deepposter_settings',
        'provider_settings'
    );

    add_settings_field(
        'temperature',
        'Temperature',
        function() {
            $value = get_option('deepposter_temperature', 0.7);
            echo '<div class="deepposter-settings-field">';
            echo '<input type="number" id="temperature" name="deepposter_temperature" 
                  min="0" max="1" step="0.1" value="'.$value.'">';
            echo '<p class="description">
                    Die Temperatur für die Generierung von Antworten.
                    <br>0 = Kreativität
                    <br>1 = Konservativität
                  </p>';
            echo '</div>';
        },
        'deepposter_settings',
        'provider_settings'
    );
}); 