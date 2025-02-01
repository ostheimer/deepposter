<?php

class Deepposter_Settings {
    public function render_max_tokens_field() {
        $value = get_option('deepposter_max_tokens', 10000);
        ?>
        <label for="max_tokens">Maximale Tokens</label>
        <input 
            type="number" 
            id="max_tokens" 
            name="deepposter_max_tokens" 
            value="<?php echo esc_attr($value); ?>"
            min="1"
            max="128000"
            step="1"
            style="width: 150px; padding: 5px;"
        >
        <p class="description">
            Maximale Anzahl der Tokens pro Anfrage. Empfohlene Werte:
            <br>- GPT-4 Modelle: bis zu 128000 Tokens
            <br>- GPT-3.5-Turbo-16k: bis zu 16000 Tokens
            <br>- Andere Modelle: bis zu 8000 Tokens
        </p>

        <style>
            #max_tokens {
                width: 150px !important;
                padding: 5px !important;
            }
            /* Entferne Browser-spezifische Validierung */
            #max_tokens::-webkit-inner-spin-button,
            #max_tokens::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            #max_tokens[type=number] {
                -moz-appearance: textfield;
            }
            label[for="max_tokens"] {
                display: inline-block;
                margin-bottom: 5px;
                font-weight: 600;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Entferne die HTML5-Validierung
                $('#max_tokens').attr('type', 'text')
                    .on('input', function() {
                        // Erlaube nur Zahlen
                        this.value = this.value.replace(/[^0-9]/g, '');
                        
                        // Validiere den Bereich
                        let val = parseInt(this.value) || 0;
                        if (val > 128000) {
                            this.value = '128000';
                        }
                    });
            });
        </script>
        <?php
    }
} 