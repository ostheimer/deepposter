<?php defined('ABSPATH') || exit; 
global $wpdb; // Für MySQL Version
?>

<div class="wrap">
    <h2>System Status</h2>
    
    <div class="deepposter-status">
        <div class="status-section">
            <h3>Plugin Information</h3>
            <ul class="status-list">
                <li>Version: <?php echo esc_html(DEEPPOSTER_VERSION); ?></li>
                <li>Debug Mode: <?php echo DEEPPOSTER_DEBUG ? 'Aktiviert' : 'Deaktiviert'; ?></li>
            </ul>
        </div>

        <div class="status-section">
            <h3>WordPress Umgebung</h3>
            <ul class="status-list">
                <li>WordPress Version: <?php echo esc_html(get_bloginfo('version')); ?></li>
                <li>PHP Version: <?php echo esc_html(phpversion()); ?></li>
                <li>MySQL Version: <?php echo esc_html($wpdb->db_version()); ?></li>
            </ul>
        </div>

        <?php if (DEEPPOSTER_DEBUG): ?>
        <div class="status-section">
            <h3>Debug Information</h3>
            <pre><?php 
                $debug_info = [
                    'Plugin Path' => DEEPPOSTER_PLUGIN_DIR,
                    'WordPress Path' => ABSPATH,
                    'Memory Limit' => ini_get('memory_limit'),
                    'Max Execution Time' => ini_get('max_execution_time')
                ];
                echo esc_html(print_r($debug_info, true));
            ?></pre>
        </div>
        <?php endif; ?>
    </div>
</div> 