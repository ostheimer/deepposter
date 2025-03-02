<div class="deepposter-generator-section">
    <h2><?php _e('Prompt Vorschau & Anpassung:', 'deepposter'); ?></h2>
    
    <!-- Neues Titelfeld für den Prompt -->
    <div class="deepposter-prompt-title-container">
        <label for="promptTitle"><?php _e('Prompt-Titel:', 'deepposter'); ?></label>
        <input type="text" id="promptTitle" name="promptTitle" placeholder="<?php _e('Geben Sie einen aussagekräftigen Titel für diesen Prompt ein', 'deepposter'); ?>" />
        <p class="description"><?php _e('Dieser Titel wird im Dropdown-Menü angezeigt und hilft Ihnen, Ihre Prompts leichter zu identifizieren.', 'deepposter'); ?></p>
    </div>
    
    <textarea id="promptText" name="promptText" rows="10" cols="50"><?php echo esc_textarea($default_prompt); ?></textarea>
    <p><?php _e('Hier können Sie den Prompt anpassen, der an die KI gesendet wird.', 'deepposter'); ?></p>
    
    <button id="savePrompt" class="button button-secondary"><?php _e('Prompt speichern', 'deepposter'); ?></button>
</div> 