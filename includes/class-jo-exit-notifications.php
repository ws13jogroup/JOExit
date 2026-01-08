<?php
/**
 * Notifications management for the JO Exit plugin.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/includes
 */

/**
 * Notifications management for the JO Exit plugin.
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/includes
 * @author     Your Name <email@example.com>
 */
class Jo_Exit_Notifications {

    /**
     * Get all notifications
     *
     * @since    1.0.0
     * @return   array    Array of notification objects
     */
    public static function get_all_notifications() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_notifications';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Getting all notifications');

        // Verify if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            error_log('Jo_Exit_Notifications: Table ' . $table_name . ' does not exist');

            // Force table creation
            require_once(plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php');
            Jo_Exit_Activator::activate();

            // Check again if the table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if (!$table_exists) {
                error_log('Jo_Exit_Notifications: Failed to create table ' . $table_name);
                return array();
            }

            error_log('Jo_Exit_Notifications: Table ' . $table_name . ' created successfully');
        }

        // Get all notifications ordered by creation date (newest first)
        $query = "SELECT * FROM $table_name ORDER BY created_at DESC";
        $notifications = $wpdb->get_results($query);

        if ($wpdb->last_error) {
            error_log('Jo_Exit_Notifications: Database error: ' . $wpdb->last_error);
            return array();
        }

        return $notifications;
    }

    /**
     * Get a notification by ID
     *
     * @since    1.0.0
     * @param    int      $id    Notification ID
     * @return   object|false    Notification object or false if not found
     */
    public static function get_notification($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_notifications';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Getting notification with ID: ' . $id);

        // Get the notification
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
        $notification = $wpdb->get_row($query);

        if ($wpdb->last_error) {
            error_log('Jo_Exit_Notifications: Database error: ' . $wpdb->last_error);
            return false;
        }

        return $notification;
    }

    /**
     * Create a new notification
     *
     * @since    1.0.0
     * @param    array    $data    Notification data
     * @return   int|false         Notification ID or false on failure
     */
    public static function create_notification($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_notifications';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Creating new notification');

        // Validate required fields
        if (empty($data['title']) || empty($data['content'])) {
            error_log('Jo_Exit_Notifications: Missing required fields');
            return false;
        }

        // Insert the notification
        $result = $wpdb->insert(
            $table_name,
            array(
                'title' => wp_unslash($data['title']),
                'content' => wp_unslash($data['content']),
            )
        );

        if (!$result) {
            error_log('Jo_Exit_Notifications: Failed to create notification: ' . $wpdb->last_error);
            return false;
        }

        $notification_id = $wpdb->insert_id;
        error_log('Jo_Exit_Notifications: Created notification with ID: ' . $notification_id);

        return $notification_id;
    }

    /**
     * Update a notification
     *
     * @since    1.0.0
     * @param    int      $id      Notification ID
     * @param    array    $data    Notification data
     * @return   bool              True on success, false on failure
     */
    public static function update_notification($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_notifications';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Updating notification with ID: ' . $id);

        // Validate required fields
        if (empty($data['title']) || empty($data['content'])) {
            error_log('Jo_Exit_Notifications: Missing required fields');
            return false;
        }

        // Update the notification
        $result = $wpdb->update(
            $table_name,
            array(
                'title' => wp_unslash($data['title']),
                'content' => wp_unslash($data['content']),
            ),
            array('id' => $id)
        );

        if ($result === false) {
            error_log('Jo_Exit_Notifications: Failed to update notification: ' . $wpdb->last_error);
            return false;
        }

        error_log('Jo_Exit_Notifications: Updated notification with ID: ' . $id);
        return true;
    }

    /**
     * Delete a notification
     *
     * @since    1.0.0
     * @param    int      $id    Notification ID
     * @return   bool           True on success, false on failure
     */
    public static function delete_notification($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_notifications';
        $reads_table = $wpdb->prefix . 'jo_exit_notification_reads';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Deleting notification with ID: ' . $id);

        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Delete notification reads first
        $wpdb->delete($reads_table, array('notification_id' => $id));
        if ($wpdb->last_error) {
            error_log('Jo_Exit_Notifications: Failed to delete notification reads: ' . $wpdb->last_error);
            $wpdb->query('ROLLBACK');
            return false;
        }

        // Delete the notification
        $result = $wpdb->delete($table_name, array('id' => $id));
        if (!$result) {
            error_log('Jo_Exit_Notifications: Failed to delete notification: ' . $wpdb->last_error);
            $wpdb->query('ROLLBACK');
            return false;
        }

        // Commit transaction
        $wpdb->query('COMMIT');

        error_log('Jo_Exit_Notifications: Deleted notification with ID: ' . $id);
        return true;
    }

    /**
     * Mark a notification as read for a user
     *
     * @since    1.0.0
     * @param    int      $notification_id    Notification ID
     * @param    int      $user_id            User ID
     * @return   bool                         True on success, false on failure
     */
    public static function mark_notification_as_read($notification_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_notification_reads';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Marking notification ' . $notification_id . ' as read for user ' . $user_id);

        // Check if the notification is already marked as read
        $query = $wpdb->prepare(
            "SELECT id FROM $table_name WHERE notification_id = %d AND user_id = %d",
            $notification_id,
            $user_id
        );
        $existing = $wpdb->get_var($query);

        if ($existing) {
            error_log('Jo_Exit_Notifications: Notification already marked as read');
            return true;
        }

        // Insert the read record
        $result = $wpdb->insert(
            $table_name,
            array(
                'notification_id' => $notification_id,
                'user_id' => $user_id,
            )
        );

        if (!$result) {
            error_log('Jo_Exit_Notifications: Failed to mark notification as read: ' . $wpdb->last_error);
            return false;
        }

        error_log('Jo_Exit_Notifications: Marked notification as read');
        return true;
    }

    /**
     * Check if a notification is read by a user
     *
     * @since    1.0.0
     * @param    int      $notification_id    Notification ID
     * @param    int      $user_id            User ID
     * @return   bool                         True if read, false if not
     */
    public static function is_notification_read($notification_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_notification_reads';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Checking if notification ' . $notification_id . ' is read by user ' . $user_id);

        // Check if the notification is marked as read
        $query = $wpdb->prepare(
            "SELECT id FROM $table_name WHERE notification_id = %d AND user_id = %d",
            $notification_id,
            $user_id
        );
        $existing = $wpdb->get_var($query);

        return !empty($existing);
    }

    /**
     * Get unread notifications for a user
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID
     * @return   array                Array of notification objects
     */
    public static function get_unread_notifications($user_id) {
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'jo_exit_notifications';
        $reads_table = $wpdb->prefix . 'jo_exit_notification_reads';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Getting unread notifications for user ' . $user_id);

        // Get notifications that are not marked as read by the user
        $query = $wpdb->prepare(
            "SELECT n.* FROM $notifications_table n
            WHERE n.id NOT IN (
                SELECT r.notification_id FROM $reads_table r
                WHERE r.user_id = %d
            )
            ORDER BY n.created_at DESC",
            $user_id
        );
        $notifications = $wpdb->get_results($query);

        if ($wpdb->last_error) {
            error_log('Jo_Exit_Notifications: Database error: ' . $wpdb->last_error);
            return array();
        }

        return $notifications;
    }

    /**
     * Get the count of unread notifications for a user
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID
     * @return   int                  Count of unread notifications
     */
    public static function get_unread_count($user_id) {
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'jo_exit_notifications';
        $reads_table = $wpdb->prefix . 'jo_exit_notification_reads';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Getting unread count for user ' . $user_id);

        // Count notifications that are not marked as read by the user
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $notifications_table n
            WHERE n.id NOT IN (
                SELECT r.notification_id FROM $reads_table r
                WHERE r.user_id = %d
            )",
            $user_id
        );
        $count = $wpdb->get_var($query);

        if ($wpdb->last_error) {
            error_log('Jo_Exit_Notifications: Database error: ' . $wpdb->last_error);
            return 0;
        }

        return intval($count);
    }

    /**
     * Get the changelog content
     *
     * @since    1.0.0
     * @return   string    Changelog content
     */
    public static function get_changelog() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_changelog';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Getting changelog');

        // Verify if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            error_log('Jo_Exit_Notifications: Table ' . $table_name . ' does not exist');

            // Force table creation
            require_once(plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php');
            Jo_Exit_Activator::activate();

            // Check again if the table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if (!$table_exists) {
                error_log('Jo_Exit_Notifications: Failed to create table ' . $table_name);
                return '';
            }

            error_log('Jo_Exit_Notifications: Table ' . $table_name . ' created successfully');
        }

        // Get the changelog content (there should be only one record)
        $query = "SELECT content FROM $table_name ORDER BY id ASC LIMIT 1";
        $content = $wpdb->get_var($query);

        if ($wpdb->last_error) {
            error_log('Jo_Exit_Notifications: Database error: ' . $wpdb->last_error);
            return '';
        }

        return $content ?: '';
    }

    /**
     * Update the changelog content
     *
     * @since    1.0.0
     * @param    string    $content    Changelog content
     * @return   bool                  True on success, false on failure
     */
    public static function update_changelog($content) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_changelog';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_Notifications: Updating changelog');

        // Get the first record ID (there should be only one record)
        $id = $wpdb->get_var("SELECT id FROM $table_name ORDER BY id ASC LIMIT 1");

        if ($id) {
            // Update the existing record
            $result = $wpdb->update(
                $table_name,
                array('content' => wp_unslash($content)),
                array('id' => $id)
            );
        } else {
            // Insert a new record
            $result = $wpdb->insert(
                $table_name,
                array('content' => wp_unslash($content))
            );
        }

        if ($result === false) {
            error_log('Jo_Exit_Notifications: Failed to update changelog: ' . $wpdb->last_error);
            return false;
        }

        error_log('Jo_Exit_Notifications: Updated changelog');
        return true;
    }
}
