<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2>Einstellungen</h2>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('deepposter_settings');
        do_settings_sections('deepposter_settings');
        submit_button();
        ?>
    </form>
</div> 