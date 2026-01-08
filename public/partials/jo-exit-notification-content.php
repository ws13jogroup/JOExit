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

// Get the notification ID from the request
$notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

// Get the notification
$notification = Jo_Exit_Notifications::get_notification($notification_id);

// Mark the notification as read
if ($notification && is_user_logged_in()) {
    Jo_Exit_Notifications::mark_notification_as_read($notification_id, get_current_user_id());
}
?>

<style>
.jo-exit-notification-content-popup {
    display: flex;
    flex-direction: column;
    max-height: 80vh;
    overflow: hidden;
}

.jo-exit-notification-content-container {
    display: flex;
    flex-direction: column;
    width: 100%;
    box-sizing: border-box;
    padding: 15px 15px 0 15px;
    overflow-y: auto;
    flex: 1;
    background-color: white;
    color: #333;
}

.jo-exit-notification-content-title {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #e1e4e8;
}

.jo-exit-notification-content-date {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 15px;
}

.jo-exit-notification-content-body {
    margin-bottom: 20px;
    line-height: 1.5;
    color: #333;
}

.jo-exit-notification-content-actions {
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: white;
    padding: 15px;
    border-top: 1px solid #e1e4e8;
    z-index: 10;
    margin-top: auto;
}

.jo-exit-notification-mark-read-button {
    background-color: #ff4757;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    width: 100%;
    max-width: 250px;
    margin: 0 auto;
}

.jo-exit-notification-mark-read-button:hover {
    background-color: #e03e4e;
}

.jo-exit-notification-content-error {
    color: #721c24;
    background-color: #f8d7da;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
}

/* Ensure dark mode doesn't affect notification popup */
.jo-exit-dark-mode .jo-exit-notification-content-container,
.jo-exit-dark-mode .jo-exit-notification-content-title,
.jo-exit-dark-mode .jo-exit-notification-content-body,
.jo-exit-dark-mode .jo-exit-notification-content-actions {
    background-color: white;
    color: #333;
}

.jo-exit-dark-mode .jo-exit-notification-content-title {
    border-bottom-color: #e1e4e8;
}

.jo-exit-dark-mode .jo-exit-notification-content-date {
    color: #6c757d;
}

.jo-exit-dark-mode .jo-exit-notification-content-actions {
    border-top-color: #e1e4e8;
}
</style>

<script>
// Assicurati che il popup abbia l'altezza corretta
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.querySelector('.jo-exit-notification-content-popup');
    if (popup) {
        popup.style.display = 'flex';
    }
});
</script>

<div class="jo-exit-notification-content-popup">
    <?php if ($notification) : ?>
        <div class="jo-exit-notification-content-container">
            <div class="jo-exit-notification-content-title"><?php echo stripslashes($notification->title); ?></div>
            <div class="jo-exit-notification-content-date">
                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->created_at)); ?>
            </div>
            <div class="jo-exit-notification-content-body">
                <?php echo stripslashes($notification->content); ?>
            </div>
        </div>
        <div class="jo-exit-notification-content-actions">
            <button class="jo-exit-notification-mark-read-button" data-id="<?php echo esc_attr($notification_id); ?>">
                <?php echo esc_html__('Mark as read', 'job-exit-plugin'); ?>
            </button>
        </div>
    <?php else : ?>
        <div class="jo-exit-notification-content-container">
            <div class="jo-exit-notification-content-error">
                <?php echo esc_html__('Notification not found.', 'job-exit-plugin'); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
