<?php
/**
 * Admin-Ansicht für DeepPoster
 */

// Wenn kein direkter Zugriff
if (!defined('WPINC')) {
    die;
}

// WordPress-Funktionen
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Stelle sicher, dass die WordPress-Funktionen verfügbar sind
require_once ABSPATH . 'wp-includes/formatting.php';
require_once ABSPATH . 'wp-includes/category.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
?>

<div class="wrap">
    <h1>DeepPoster</h1>
    
    <form id="aiGeneratorForm" class="ai-generator" method="post" onsubmit="return false;">
        <div class="form-group">
            <label for="promptText">Prompt Vorschau & Anpassung:</label>
            <textarea id="promptText" name="prompt" rows="10" style="width: 100%; margin-top: 10px;"><?php
                $default_prompt = 'Du bist ein professioneller Content-Ersteller für WordPress-Blogs. ' .
                    'Erstelle einen gut strukturierten Artikel in der Kategorie \'[KATEGORIE]\'. ' .
                    'Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein. ' .
                    'Formatiere den Artikel mit WordPress-kompatiblem HTML und strukturiere ihn mit Überschriften (h2, h3). ' .
                    'Beginne mit dem Titel in der ersten Zeile, gefolgt von einer Leerzeile und dann dem Artikelinhalt.';
                echo wp_kses_post($default_prompt);
            ?></textarea>
            <p class="description">Hier können Sie das Prompt anpassen, das an die KI gesendet wird. Die Platzhalter [KATEGORIE] werden automatisch ersetzt.</p>
        </div>

        <div class="form-group">
            <label for="categorySelect">Kategorie auswählen:</label>
            <select id="categorySelect" name="category" required>
                <option value="">Kategorie wählen</option>
                <?php
                $categories = get_categories(array('hide_empty' => false));
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        printf(
                            '<option value="%s">%s</option>',
                            esc_attr($category->term_id),
                            esc_html($category->name)
                        );
                    }
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="articleCount">Anzahl Artikel:</label>
            <select id="articleCount" name="count">
                <?php for ($i = 1; $i <= 10; $i++) : ?>
                    <option value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" id="publishImmediately" name="publish">
                Sofort veröffentlichen
            </label>
        </div>

        <div class="form-group">
            <?php wp_nonce_field('deepposter_nonce', 'deepposter_nonce'); ?>
            <input type="hidden" name="action" value="deepposter_generate">
            <button type="submit" id="generateButton" class="button button-primary">Artikel generieren</button>
        </div>
    </form>

    <div id="generationResults"></div>
</div>

<script>
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

jQuery(document).ready(function($) {
    // Debug-Logging aktivieren
    const DEBUG = true;
    function log(message, data = null) {
        if (DEBUG) {
            console.log('DeepPoster Debug -', message);
            if (data) console.log(data);
        }
    }

    // Prompt bei Kategorieänderung aktualisieren
    $('#categorySelect').on('change', function() {
        const category = $(this).find('option:selected').text();
        const currentPrompt = $('#promptText').val();
        const updatedPrompt = currentPrompt.replace(/\[KATEGORIE\]/g, category);
        $('#promptText').val(updatedPrompt);
    });

    // AJAX-Formular-Submit
    $('#aiGeneratorForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $('#generateButton');
        const $results = $('#generationResults');
        
        // Validiere Eingaben
        const category = $('#categorySelect').val();
        if (!category) {
            $results.html('<div class="notice notice-error"><p>Bitte wählen Sie eine Kategorie aus.</p></div>');
            return false;
        }

        // Deaktiviere Button
        $button.prop('disabled', true).text('Generiere...');
        
        // Zeige Ladeanimation
        $results.html('<div class="notice notice-info"><p>Generiere Artikel...</p></div>');
        
        // Sammle Formulardaten
        const formData = new FormData($form[0]);
        
        // Debug-Ausgabe
        log('Sende AJAX-Anfrage:', {
            url: ajaxurl,
            formData: Object.fromEntries(formData)
        });
        
        // Sende AJAX Request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                log('AJAX-Antwort erhalten:', response);
                if (response.success) {
                    let html = '<div class="notice notice-success"><p>Artikel erfolgreich generiert:</p><ul>';
                    response.data.forEach(function(post) {
                        html += `<li>
                            <strong>${post.title}</strong><br>
                            Kategorie: ${post.category}<br>
                            Status: ${post.status}<br>
                            <a href="${post.editUrl}" target="_blank" class="button">Bearbeiten</a>
                            <a href="${post.viewUrl}" target="_blank" class="button">Ansehen</a>
                        </li>`;
                    });
                    html += '</ul></div>';
                    $results.html(html);
                } else {
                    $results.html(`<div class="notice notice-error"><p>${response.data}</p></div>`);
                }
            },
            error: function(xhr, status, error) {
                log('AJAX-Fehler:', {xhr, status, error});
                $results.html(`<div class="notice notice-error"><p>Ein Fehler ist aufgetreten: ${error}</p></div>`);
            },
            complete: function() {
                // Aktiviere Button wieder
                $button.prop('disabled', false).text('Artikel generieren');
            }
        });
        
        return false;
    });
});
</script> 