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

// Get the changelog content
$changelog_content = Jo_Exit_Notifications::get_changelog();

// If the changelog is empty, show a default message
if (empty($changelog_content)) {
    $changelog_content = file_get_contents(plugin_dir_path(dirname(dirname(__FILE__))) . 'CHANGELOG.txt');
}
?>

<style>
.jo-exit-changelog-container {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

.jo-exit-changelog-text {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
    margin: 0;
    padding: 0;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}
</style>

<div class="jo-exit-changelog-container">
    <?php if (!empty($changelog_content)) : ?>
        <div class="jo-exit-changelog-text"><?php echo nl2br($changelog_content); ?></div>
    <?php else : ?>
        <p><?php echo esc_html__('No changelog available.', 'job-exit-plugin'); ?></p>
    <?php endif; ?>
</div>
