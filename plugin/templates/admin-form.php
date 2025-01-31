<div class="wrap ai-generator">
    <h1>AI Blog Generator</h1>
    
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
                <?php foreach (range(1, 5) as $num): ?>
                    <option value="<?= $num ?>"><?= $num ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" id="publishImmediately" name="publish">
                Sofort veröffentlichen
            </label>
        </div>
        
        <?php submit_button('Artikel generieren'); ?>
    </form>
    
    <div id="generationResults"></div>
</div>
