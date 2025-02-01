<?php defined('ABSPATH') || exit; ?>

<div class="wrap ai-generator">
    <h1>DeepPoster</h1>
    
    <form id="aiGeneratorForm">
        <div class="form-group">
            <label for="categorySelect">Kategorie auswählen:</label>
            <?php wp_dropdown_categories([
                'id' => 'categorySelect',
                'name' => 'category',
                'hide_empty' => 0,
                'hierarchical' => true,
                'orderby' => 'name',
                'show_option_none' => 'Kategorie wählen'
            ]); ?>
        </div>
        
        <div class="form-group">
            <label for="articleCount">Anzahl Artikel:</label>
            <select id="articleCount" name="count" class="regular-text">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" id="publishImmediately" name="publish">
                Sofort veröffentlichen
            </label>
        </div>
        
        <?php 
        $api_key = get_option('deepposter_openai_key');
        if (!$api_key) {
            echo '<div class="notice notice-warning"><p>Bitte zuerst den OpenAI API Key in den <a href="' . 
                 admin_url('admin.php?page=deepposter-settings') . 
                 '">Einstellungen</a> hinterlegen.</p></div>';
        } else {
            submit_button('Artikel generieren');
        }
        ?>
    </form>
    
    <div id="generationResults"></div>
</div> 