<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="jo-exit-changelog-screen">
    <div class="jo-exit-changelog-header">
        <div class="jo-exit-changelog-title"><?php echo esc_html__('Changelog', 'job-exit-plugin'); ?></div>
        <div class="jo-exit-changelog-close"><i class="fas fa-times"></i></div>
    </div>
    
    <div class="jo-exit-changelog-content">
        <?php echo esc_html__('Loading changelog...', 'job-exit-plugin'); ?>
    </div>
</div>
