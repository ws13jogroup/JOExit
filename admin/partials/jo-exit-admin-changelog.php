<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('JOb Exit - Changelog', 'job-exit-plugin'); ?></h1>

    <div class="jo-exit-admin-changelog">
        <p><?php echo esc_html__('Edit the changelog content below. This will be displayed to users in the app.', 'job-exit-plugin'); ?></p>

        <form id="jo-exit-changelog-form">
            <div class="jo-exit-form-group">
                <?php
                wp_editor('', 'jo-exit-changelog-content', array(
                    'media_buttons' => true,
                    'textarea_name' => 'content',
                    'textarea_rows' => 20,
                    'teeny' => false,
                ));
                ?>
            </div>

            <div class="jo-exit-form-actions">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Save Changelog', 'job-exit-plugin'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load changelog
    function loadChangelog() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jo_exit_get_changelog'
            },
            success: function(response) {
                if (response.success) {
                    // Set content in editor
                    var editor = tinyMCE.get('jo-exit-changelog-content');
                    if (editor) {
                        editor.setContent(response.data.content);
                    } else {
                        $('#jo-exit-changelog-content').val(response.data.content);
                    }
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error loading changelog.', 'job-exit-plugin')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while loading changelog.', 'job-exit-plugin')); ?>');
            }
        });
    }

    // Save changelog form
    $('#jo-exit-changelog-form').on('submit', function(e) {
        e.preventDefault();

        // Get content from editor
        var content;
        var editor = tinyMCE.get('jo-exit-changelog-content');
        if (editor) {
            content = editor.getContent();
        } else {
            content = $('#jo-exit-changelog-content').val();
        }

        // Save changelog
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jo_exit_save_changelog',
                content: content
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(response.data.message || '<?php echo esc_js(__('Changelog saved successfully.', 'job-exit-plugin')); ?>');
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error saving changelog.', 'job-exit-plugin')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while saving changelog.', 'job-exit-plugin')); ?>');
            }
        });
    });

    // Load changelog on page load
    loadChangelog();
});
</script>

<style>
.jo-exit-admin-changelog {
    margin-top: 20px;
}

.jo-exit-form-group {
    margin-bottom: 15px;
}

.jo-exit-form-actions {
    margin-top: 20px;
}
</style>
