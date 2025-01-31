<?php
add_action('admin_init', function() {
    register_setting('ai_blog_generator_settings', 'openai_api_key', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    add_settings_section(
        'ai_blog_generator_api',
        'API-Einstellungen',
        function() {
            echo '<p>API-Zugangsdaten konfigurieren</p>';
        },
        'ai-blog-generator'
    );

    add_settings_field(
        'openai_api_key_field',
        'OpenAI API Key',
        function() {
            $value = esc_attr(get_option('openai_api_key'));
            echo '<input type="password" name="openai_api_key" value="'.$value.'" class="regular-text">';
            echo '<p class="description">Erhalten Sie Ihren Key von <a href="https://platform.openai.com/" target="_blank">platform.openai.com</a></p>';
        },
        'ai-blog-generator',
        'ai_blog_generator_api'
    );
});

add_action('admin_menu', function() {
    add_submenu_page(
        'ai-blog-generator',
        'Einstellungen',
        'Einstellungen',
        'manage_options',
        'ai-blog-generator-settings',
        function() {
            ?>
            <div class="wrap">
                <h1>AI Blog Generator Einstellungen</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ai_blog_generator_settings');
                    do_settings_sections('ai-blog-generator');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
    );
});
