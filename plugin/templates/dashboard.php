<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2>DeepPoster</h2>
    
    <form id="aiGeneratorForm" class="deepposter-form" method="post" action="javascript:void(0);">
        <div class="deepposter-settings-field">
            <label for="promptText">Prompt Vorschau & Anpassung:</label>
            <textarea id="promptText" name="prompt" rows="10" style="width: 100%; margin-top: 10px;"><?php
                echo esc_textarea(
                    'Du bist ein professioneller Content-Ersteller für WordPress-Blogs. ' .
                    'Erstelle einen gut strukturierten Artikel in der Kategorie \'[KATEGORIE]\'. ' .
                    'Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein. ' .
                    'Formatiere den Artikel mit WordPress-kompatiblem HTML und strukturiere ihn mit Überschriften (h2, h3). ' .
                    'Beginne mit dem Titel in der ersten Zeile, gefolgt von einer Leerzeile und dann dem Artikelinhalt.'
                );
            ?></textarea>
            <p class="description">Hier können Sie das Prompt anpassen, das an die KI gesendet wird. Die Platzhalter [KATEGORIE] werden automatisch ersetzt.</p>
        </div>

        <div class="deepposter-settings-field">
            <label for="categorySelect">Kategorie auswählen:</label>
            <select id="categorySelect" name="category">
                <option value="">Kategorie wählen</option>
                <?php
                $categories = get_categories(['hide_empty' => false]);
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->term_id) . '">' . 
                         esc_html($category->name) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="deepposter-settings-field">
            <label for="articleCount">Anzahl Artikel:</label>
            <select id="articleCount" name="count">
                <?php for($i = 1; $i <= 10; $i++) : ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="deepposter-settings-field checkbox-field">
            <label>
                <input type="checkbox" id="publishImmediately" name="publish">
                Sofort veröffentlichen
            </label>
        </div>

        <div class="deepposter-settings-field">
            <button type="submit" class="button button-primary">Artikel generieren</button>
        </div>

        <?php wp_nonce_field('deepposter_nonce', 'deepposter_nonce'); ?>
    </form>

    <div id="generationResults" class="generation-results"></div>
</div>

<style>
    /* Übernehme das Design-System von den Einstellungen */
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
    .deepposter-settings-field.checkbox-field {
        margin-left: 150px;
    }
    .deepposter-settings-field.checkbox-field label {
        min-width: auto;
        font-weight: normal;
    }
    .deepposter-settings-field button {
        margin-left: 150px !important;
        height: 40px !important;
        padding: 0 20px !important;
        font-size: 14px !important;
    }
    .generation-results {
        margin-top: 30px;
    }
    .generation-item {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .generation-item h4 {
        margin: 0 0 10px 0;
        font-size: 16px;
    }
    .generation-meta {
        color: #646970;
        margin-bottom: 10px;
    }
    .generation-actions {
        margin-top: 10px;
    }
    .generation-actions .button {
        margin-right: 10px;
    }
    .notice {
        margin: 15px 0;
        padding: 12px;
        border-radius: 4px;
    }
    .notice-info {
        border-left: 4px solid #72aee6;
        background: #f0f6fc;
    }
    .notice-error {
        border-left: 4px solid #d63638;
        background: #fcf0f1;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Prompt bei Kategorieänderung aktualisieren
    $('#categorySelect').on('change', function() {
        const category = $(this).find('option:selected').text();
        const currentPrompt = $('#promptText').val();
        const updatedPrompt = currentPrompt.replace(/\[KATEGORIE\]/g, category);
        $('#promptText').val(updatedPrompt);
    });

    // Formular absenden
    $('#aiGeneratorForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $resultContainer = $('#generationResults');
        
        // Deaktiviere Submit-Button
        $submitButton.prop('disabled', true);
        $submitButton.text('Generiere...');
        
        // Zeige Lade-Animation
        $resultContainer.html('<div class="notice notice-info"><p>Generiere Artikel...</p></div>');
        
        // Sammle Formulardaten
        const formData = {
            action: 'deepposter_generate',
            nonce: $('#deepposter_nonce').val(),
            category: $('#categorySelect').val(),
            count: $('#articleCount').val(),
            publish: $('#publishImmediately').is(':checked'),
            prompt: $('#promptText').val()
        };
        
        // Sende AJAX-Request
        $.post(ajaxurl, formData)
            .done(function(response) {
                if (response.success) {
                    // Zeige generierte Artikel an
                    let html = '<div class="notice notice-success"><p>Artikel erfolgreich generiert:</p>';
                    response.data.forEach(function(post) {
                        html += `
                            <div class="generation-item">
                                <h4>${post.title}</h4>
                                <div class="generation-meta">
                                    Kategorie: ${post.category}
                                    Status: ${post.status}
                                </div>
                                <div class="generation-actions">
                                    <a href="${post.editUrl}" class="button" target="_blank">Bearbeiten</a>
                                    <a href="${post.viewUrl}" class="button" target="_blank">Ansehen</a>
                                </div>
                            </div>`;
                    });
                    html += '</div>';
                    $resultContainer.html(html);
                } else {
                    // Zeige Fehlermeldung
                    $resultContainer.html(`<div class="notice notice-error"><p>${response.data}</p></div>`);
                }
            })
            .fail(function(xhr, status, error) {
                // Zeige Fehlermeldung bei AJAX-Fehler
                $resultContainer.html(`<div class="notice notice-error"><p>Ein Fehler ist aufgetreten: ${error}</p></div>`);
            })
            .always(function() {
                // Aktiviere Submit-Button wieder
                $submitButton.prop('disabled', false);
                $submitButton.text('Artikel generieren');
            });
    });
});
</script> 