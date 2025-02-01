<?php

class Deepposter_Settings {
    public function render_max_tokens_field() {
        $value = get_option('deepposter_max_tokens', 10000);
        ?>
        <div class="deepposter-settings-field">
            <label for="max_tokens">Maximale Tokens</label>
            <input 
                type="number" 
                id="max_tokens" 
                name="deepposter_max_tokens" 
                value="<?php echo esc_attr($value); ?>"
                min="1"
                max="128000"
                step="1"
            >
            <p class="description">
                Maximale Anzahl der Tokens pro Anfrage. Empfohlene Werte:
                <br>- GPT-4 Modelle: bis zu 128000 Tokens
                <br>- GPT-3.5-Turbo-16k: bis zu 16000 Tokens
                <br>- Andere Modelle: bis zu 8000 Tokens
            </p>
        </div>

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