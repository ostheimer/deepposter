<?php
/**
 * Main dashboard template for DeepPoster
 *
 * @package DeepPoster
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get saved settings
$api_provider = get_option('deepposter_api_provider', 'openai');
$active_prompt = get_option('deepposter_active_prompt', 0);

// Stelle sicher, dass die WordPress-Funktionen verfügbar sind
if (!defined('ABSPATH')) {
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Lade die benötigten JavaScript-Dateien
wp_enqueue_script('jquery');
wp_enqueue_script(
    'deepposter-admin',
    plugins_url('assets/js/deepposter-admin.js', dirname(dirname(__FILE__))),
    array('jquery'),
    '1.0.0',
    true
);

// Lokalisiere das Script
wp_localize_script(
    'deepposter-admin',
    'deepposterAdmin',
    array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('deepposter_nonce'),
        'debug' => defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG
    )
);

// Stelle sicher, dass die Skripte geladen werden
do_action('admin_enqueue_scripts');
?>

<div class="wrap">
    <h1><?php echo esc_html__('DeepPoster Generator', 'deepposter'); ?></h1>
    
    <form id="deepposterSettingsForm" method="post">
        <?php wp_nonce_field('deepposter_nonce', 'deepposter_nonce'); ?>
        
        <div class="form-group">
            <label for="promptSelect"><?php echo esc_html__('Gespeicherte Prompts:', 'deepposter'); ?></label>
            <select id="promptSelect" name="prompt_id" style="width: 100%; margin-top: 10px;">
                <option value=""><?php echo esc_html__('Prompt auswählen', 'deepposter'); ?></option>
            </select>
            <p class="description"><?php echo esc_html__('Wählen Sie einen gespeicherten Prompt aus oder erstellen Sie einen neuen.', 'deepposter'); ?></p>
        </div>

        <div class="form-group">
            <label for="promptText"><?php echo esc_html__('Prompt Vorschau & Anpassung:', 'deepposter'); ?></label>
            <textarea id="promptText" name="prompt" rows="10" style="width: 100%; margin-top: 10px;"><?php
                $default_prompt = __(
                    'Du bist ein professioneller Content-Ersteller für WordPress-Blogs. ' .
                    'Erstelle einen gut strukturierten Artikel in der Kategorie \'[KATEGORIE]\'. ' .
                    'Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein. ' .
                    'Formatiere den Artikel mit WordPress-kompatiblem HTML und strukturiere ihn mit Überschriften (h2, h3). ' .
                    'Beginne mit dem Titel in der ersten Zeile, gefolgt von einer Leerzeile und dann dem Artikelinhalt.',
                    'deepposter'
                );
                echo esc_textarea($default_prompt);
            ?></textarea>
            <input type="hidden" id="promptId" name="prompt_id" value="">
            <p class="description"><?php echo esc_html__('Hier können Sie den Prompt anpassen, der an die KI gesendet wird.', 'deepposter'); ?></p>
            <div class="button-group">
                <button type="button" id="savePrompt" class="button button-secondary"><?php echo esc_html__('Prompt speichern', 'deepposter'); ?></button>
                <button type="button" id="deletePrompt" class="button button-secondary" style="color: #a00; margin-left: 10px; display: none;"><?php echo esc_html__('Prompt löschen', 'deepposter'); ?></button>
            </div>
        </div>

        <div class="form-group">
            <label for="categorySelect"><?php echo esc_html__('Kategorie auswählen:', 'deepposter'); ?></label>
            <select id="categorySelect" name="category" required>
                <option value=""><?php echo esc_html__('Kategorie wählen', 'deepposter'); ?></option>
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
            <label for="articleCount"><?php echo esc_html__('Anzahl Artikel:', 'deepposter'); ?></label>
            <select id="articleCount" name="count">
                <?php for ($i = 1; $i <= 10; $i++) : ?>
                    <option value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" id="publishImmediately" name="publish">
                <?php echo esc_html__('Sofort veröffentlichen', 'deepposter'); ?>
            </label>
        </div>

        <div class="form-group">
            <input type="hidden" name="action" value="deepposter_generate">
            <button type="submit" id="generateButton" class="button button-primary"><?php echo esc_html__('Artikel generieren', 'deepposter'); ?></button>
        </div>
    </form>

    <div id="generationResults"></div>
</div>

<style>
.ai-generator {
    max-width: 800px;
    margin-top: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group select,
.form-group textarea {
    width: 100%;
    margin-bottom: 10px;
}

.form-group .description {
    color: #666;
    font-style: italic;
    margin-top: 5px;
}

#generationResults {
    margin-top: 20px;
}

#generationResults .notice {
    margin: 5px 0 15px;
}

#generationResults ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

#generationResults li {
    margin-bottom: 15px;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
}

#generationResults .button {
    margin-right: 10px;
    margin-top: 5px;
}

.notice {
    margin: 20px 0;
    padding: 10px;
    border-radius: 4px;
}

.notice-error {
    border-left: 4px solid #dc3232;
    background: #fef7f7;
}

.notice-success {
    border-left: 4px solid #46b450;
    background: #f7fff7;
}

.notice-info {
    border-left: 4px solid #00a0d2;
    background: #f7fcfe;
}
</style>

<?php
// Deutsche Übersetzung der Hauptseite
$translations = array(
    'DeepPoster' => 'DeepPoster',
    'Saved Prompts:' => 'Gespeicherte Prompts:',
    'Select a prompt' => 'Prompt auswählen',
    'Select a saved prompt or create a new one.' => 'Wählen Sie einen gespeicherten Prompt aus oder erstellen Sie einen neuen.',
    'Prompt Preview & Customization:' => 'Prompt Vorschau & Anpassung:',
    'Here you can customize the prompt that will be sent to the AI.' => 'Hier können Sie das Prompt anpassen, das an die KI gesendet wird.',
    'The placeholders [CATEGORY] will be replaced automatically.' => 'Die Platzhalter [KATEGORIE] werden automatisch ersetzt.',
    'Save Prompt' => 'Prompt speichern',
    'Select Category:' => 'Kategorie auswählen:',
    'Choose category' => 'Kategorie wählen',
    'Number of Articles:' => 'Anzahl Artikel:',
    'Publish Immediately' => 'Sofort veröffentlichen',
    'Generate Articles' => 'Artikel generieren'
);
?> 