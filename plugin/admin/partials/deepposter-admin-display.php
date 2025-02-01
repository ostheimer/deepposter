<?php
/**
 * Admin-Ansicht für DeepPoster
 */

// Wenn kein direkter Zugriff
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>DeepPoster</h1>
    
    <form id="aiGeneratorForm" method="post">
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
                $categories = get_categories(array('hide_empty' => false));
                foreach ($categories as $category) {
                    printf(
                        '<option value="%s">%s</option>',
                        esc_attr($category->term_id),
                        esc_html($category->name)
                    );
                }
                ?>
            </select>
        </div>

        <div class="deepposter-settings-field">
            <label for="articleCount">Anzahl Artikel:</label>
            <select id="articleCount" name="count">
                <?php for ($i = 1; $i <= 10; $i++) : ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="deepposter-settings-field">
            <label>
                <input type="checkbox" id="publishImmediately" name="publish">
                Sofort veröffentlichen
            </label>
        </div>

        <div class="deepposter-settings-field">
            <?php wp_nonce_field('deepposter_nonce', 'deepposter_nonce'); ?>
            <button type="submit" class="button button-primary">Artikel generieren</button>
        </div>

        <div id="generationResults"></div>
    </form>
</div> 