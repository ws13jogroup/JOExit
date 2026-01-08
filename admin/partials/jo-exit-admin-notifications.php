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
    <h1><?php echo esc_html__('JOb Exit - Notifications', 'job-exit-plugin'); ?></h1>

    <div class="jo-exit-admin-notifications">
        <div class="jo-exit-admin-notifications-header">
            <button id="jo-exit-add-notification" class="button button-primary"><?php echo esc_html__('Add New Notification', 'job-exit-plugin'); ?></button>
        </div>

        <div class="jo-exit-admin-notifications-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Title', 'job-exit-plugin'); ?></th>
                        <th><?php echo esc_html__('Date', 'job-exit-plugin'); ?></th>
                        <th><?php echo esc_html__('Actions', 'job-exit-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody id="jo-exit-notifications-table-body">
                    <tr>
                        <td colspan="3"><?php echo esc_html__('Loading notifications...', 'job-exit-plugin'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notification Form Modal -->
    <div id="jo-exit-notification-modal" class="jo-exit-modal">
        <div class="jo-exit-modal-content">
            <span class="jo-exit-modal-close">&times;</span>
            <h2 id="jo-exit-notification-modal-title"><?php echo esc_html__('Add New Notification', 'job-exit-plugin'); ?></h2>

            <form id="jo-exit-notification-form">
                <input type="hidden" id="jo-exit-notification-id" name="notification_id" value="">

                <div class="jo-exit-form-group">
                    <label for="jo-exit-notification-title"><?php echo esc_html__('Title', 'job-exit-plugin'); ?></label>
                    <input type="text" id="jo-exit-notification-title" name="title" required>
                </div>

                <div class="jo-exit-form-group">
                    <label for="jo-exit-notification-content"><?php echo esc_html__('Content', 'job-exit-plugin'); ?></label>
                    <?php
                    wp_editor('', 'jo-exit-notification-content', array(
                        'media_buttons' => true,
                        'textarea_name' => 'content',
                        'textarea_rows' => 10,
                        'teeny' => false,
                    ));
                    ?>
                </div>

                <div class="jo-exit-form-actions">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Save Notification', 'job-exit-plugin'); ?></button>
                    <button type="button" class="button jo-exit-modal-cancel"><?php echo esc_html__('Cancel', 'job-exit-plugin'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="jo-exit-delete-modal" class="jo-exit-modal">
        <div class="jo-exit-modal-content">
            <span class="jo-exit-modal-close">&times;</span>
            <h2><?php echo esc_html__('Delete Notification', 'job-exit-plugin'); ?></h2>

            <p><?php echo esc_html__('Are you sure you want to delete this notification? This action cannot be undone.', 'job-exit-plugin'); ?></p>

            <div class="jo-exit-form-actions">
                <button type="button" id="jo-exit-confirm-delete" class="button button-primary" data-id=""><?php echo esc_html__('Delete', 'job-exit-plugin'); ?></button>
                <button type="button" class="button jo-exit-modal-cancel"><?php echo esc_html__('Cancel', 'job-exit-plugin'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load notifications
    function loadNotifications() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jo_exit_get_notifications'
            },
            success: function(response) {
                if (response.success) {
                    displayNotifications(response.data.notifications);
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error loading notifications.', 'job-exit-plugin')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while loading notifications.', 'job-exit-plugin')); ?>');
            }
        });
    }

    // Display notifications in the table
    function displayNotifications(notifications) {
        var tableBody = $('#jo-exit-notifications-table-body');
        tableBody.empty();

        if (notifications.length === 0) {
            tableBody.append('<tr><td colspan="3"><?php echo esc_js(__('No notifications found.', 'job-exit-plugin')); ?></td></tr>');
            return;
        }

        $.each(notifications, function(index, notification) {
            var date = new Date(notification.created_at);
            var formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();

            var row = $('<tr></tr>');
            row.append('<td>' + notification.title + '</td>');
            row.append('<td>' + formattedDate + '</td>');
            row.append('<td>' +
                '<button class="button jo-exit-edit-notification" data-id="' + notification.id + '"><?php echo esc_js(__('Edit', 'job-exit-plugin')); ?></button> ' +
                '<button class="button jo-exit-delete-notification" data-id="' + notification.id + '"><?php echo esc_js(__('Delete', 'job-exit-plugin')); ?></button>' +
                '</td>');

            tableBody.append(row);
        });
    }

    // Add new notification button
    $('#jo-exit-add-notification').on('click', function() {
        // Reset form
        $('#jo-exit-notification-id').val('');
        $('#jo-exit-notification-title').val('');
        var editor = tinyMCE.get('jo-exit-notification-content');
        if (editor) {
            editor.setContent('');
        } else {
            $('#jo-exit-notification-content').val('');
        }

        // Update modal title
        $('#jo-exit-notification-modal-title').text('<?php echo esc_js(__('Add New Notification', 'job-exit-plugin')); ?>');

        // Show modal
        $('#jo-exit-notification-modal').show();
    });

    // Edit notification button
    $(document).on('click', '.jo-exit-edit-notification', function() {
        var notificationId = $(this).data('id');

        // Get notification data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jo_exit_get_notification',
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    var notification = response.data.notification;

                    // Fill form
                    $('#jo-exit-notification-id').val(notification.id);
                    $('#jo-exit-notification-title').val(notification.title);
                    var editor = tinyMCE.get('jo-exit-notification-content');
                    if (editor) {
                        editor.setContent(notification.content);
                    } else {
                        $('#jo-exit-notification-content').val(notification.content);
                    }

                    // Update modal title
                    $('#jo-exit-notification-modal-title').text('<?php echo esc_js(__('Edit Notification', 'job-exit-plugin')); ?>');

                    // Show modal
                    $('#jo-exit-notification-modal').show();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error loading notification.', 'job-exit-plugin')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while loading notification.', 'job-exit-plugin')); ?>');
            }
        });
    });

    // Delete notification button
    $(document).on('click', '.jo-exit-delete-notification', function() {
        var notificationId = $(this).data('id');

        // Set notification ID in delete confirmation modal
        $('#jo-exit-confirm-delete').data('id', notificationId);

        // Show delete confirmation modal
        $('#jo-exit-delete-modal').show();
    });

    // Confirm delete button
    $('#jo-exit-confirm-delete').on('click', function() {
        var notificationId = $(this).data('id');

        // Delete notification
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jo_exit_delete_notification',
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#jo-exit-delete-modal').hide();

                    // Reload notifications
                    loadNotifications();

                    // Show success message
                    alert(response.data.message || '<?php echo esc_js(__('Notification deleted successfully.', 'job-exit-plugin')); ?>');
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error deleting notification.', 'job-exit-plugin')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while deleting notification.', 'job-exit-plugin')); ?>');
            }
        });
    });

    // Save notification form
    $('#jo-exit-notification-form').on('submit', function(e) {
        e.preventDefault();

        // Get form data
        var notificationId = $('#jo-exit-notification-id').val();
        var title = $('#jo-exit-notification-title').val();
        var content;
        var editor = tinyMCE.get('jo-exit-notification-content');
        if (editor) {
            content = editor.getContent();
        } else {
            content = $('#jo-exit-notification-content').val();
        }

        // Validate form
        if (!title || !content) {
            alert('<?php echo esc_js(__('Title and content are required.', 'job-exit-plugin')); ?>');
            return;
        }

        // Save notification
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jo_exit_save_notification',
                notification_id: notificationId,
                title: title,
                content: content
            },
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#jo-exit-notification-modal').hide();

                    // Reload notifications
                    loadNotifications();

                    // Show success message
                    alert(response.data.message || '<?php echo esc_js(__('Notification saved successfully.', 'job-exit-plugin')); ?>');
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error saving notification.', 'job-exit-plugin')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while saving notification.', 'job-exit-plugin')); ?>');
            }
        });
    });

    // Close modal buttons
    $('.jo-exit-modal-close, .jo-exit-modal-cancel').on('click', function() {
        $(this).closest('.jo-exit-modal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('jo-exit-modal')) {
            $('.jo-exit-modal').hide();
        }
    });

    // Load notifications on page load
    loadNotifications();
});
</script>

<style>
.jo-exit-admin-notifications {
    margin-top: 20px;
}

.jo-exit-admin-notifications-header {
    margin-bottom: 20px;
}

.jo-exit-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}

.jo-exit-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    position: relative;
}

.jo-exit-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.jo-exit-modal-close:hover,
.jo-exit-modal-close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.jo-exit-form-group {
    margin-bottom: 15px;
}

.jo-exit-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.jo-exit-form-group input[type="text"] {
    width: 100%;
    padding: 8px;
}

.jo-exit-form-actions {
    margin-top: 20px;
    text-align: right;
}

.jo-exit-form-actions button {
    margin-left: 10px;
}
</style>
