<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 */
class Jo_Exit_Activator {

    /**
     * Create the necessary database tables on plugin activation.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table for employees
        $table_name_employees = $wpdb->prefix . 'jo_exit_employees';
        $sql_employees = "CREATE TABLE $table_name_employees (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            role varchar(100) DEFAULT '' NOT NULL,
            company_code varchar(50) NOT NULL,
            photo_url varchar(255) DEFAULT '' NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            exit_date date DEFAULT NULL,
            hire_year year(4) DEFAULT NULL,
            score int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Table for votes
        $table_name_votes = $wpdb->prefix . 'jo_exit_votes';
        $sql_votes = "CREATE TABLE $table_name_votes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            employee_id mediumint(9) NOT NULL,
            user_identifier varchar(100) NOT NULL,
            vote_type varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY employee_id (employee_id),
            KEY user_identifier (user_identifier)
        ) $charset_collate;";

        // Table for player scores
        $table_name_player_scores = $wpdb->prefix . 'jo_exit_player_scores';
        $sql_player_scores = "CREATE TABLE $table_name_player_scores (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            employee_id mediumint(9) NOT NULL,
            player_points int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_employee (user_id, employee_id),
            KEY employee_id (employee_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Table for notifications
        $table_name_notifications = $wpdb->prefix . 'jo_exit_notifications';
        $sql_notifications = "CREATE TABLE $table_name_notifications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Table for notification reads
        $table_name_notification_reads = $wpdb->prefix . 'jo_exit_notification_reads';
        $sql_notification_reads = "CREATE TABLE $table_name_notification_reads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            notification_id mediumint(9) NOT NULL,
            user_id mediumint(9) NOT NULL,
            read_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY notification_user (notification_id, user_id),
            KEY notification_id (notification_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Table for changelog
        $table_name_changelog = $wpdb->prefix . 'jo_exit_changelog';
        $sql_changelog = "CREATE TABLE $table_name_changelog (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            content text NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_employees);
        dbDelta($sql_votes);
        dbDelta($sql_player_scores);
        dbDelta($sql_notifications);
        dbDelta($sql_notification_reads);
        dbDelta($sql_changelog);

        // Insert default changelog entry if it doesn't exist
        $changelog_table = $wpdb->prefix . 'jo_exit_changelog';
        $changelog_count = $wpdb->get_var("SELECT COUNT(*) FROM $changelog_table");
        if ($changelog_count == 0) {
            $wpdb->insert(
                $changelog_table,
                array(
                    'content' => '<h2>Changelog</h2><p>Welcome to JOb Exit! This is the changelog where you can find information about updates and new features.</p>',
                )
            );
        }

        // Add version to options
        add_option('jo_exit_plugin_version', JO_EXIT_PLUGIN_VERSION);

        // Force update database structure
        self::update_database_structure();
    }

    /**
     * Update database structure
     *
     * @since    1.0.0
     */
    public static function update_database_structure() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Updating database structure');

        // Check if the hire_year column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'hire_year'");

        if (empty($column_exists)) {
            // Add the column
            $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN hire_year year(4) DEFAULT NULL AFTER exit_date");
            error_log('Added hire_year column: ' . ($result !== false ? 'success' : 'failed - ' . $wpdb->last_error));
        }

        // Check if the final_score column exists
        $final_score_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'final_score'");

        if (empty($final_score_exists)) {
            // Add the column
            $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN final_score int(11) DEFAULT 0 AFTER score");
            error_log('Added final_score column: ' . ($result !== false ? 'success' : 'failed - ' . $wpdb->last_error));
        }

        // Update role column to have a default value
        try {
            $result = $wpdb->query("ALTER TABLE {$table_name} MODIFY role varchar(100) DEFAULT 'employee' NOT NULL");
            error_log('Modified role column: ' . ($result !== false ? 'success' : 'failed - ' . $wpdb->last_error));
        } catch (Exception $e) {
            error_log('Error modifying role column: ' . $e->getMessage());
        }

        // Update existing records to have a role value
        try {
            $result = $wpdb->query("UPDATE {$table_name} SET role = 'employee' WHERE role = '' OR role IS NULL");
            error_log('Updated empty roles: ' . ($result !== false ? $result . ' rows affected' : 'failed - ' . $wpdb->last_error));
        } catch (Exception $e) {
            error_log('Error updating roles: ' . $e->getMessage());
        }

        // Verifica se la tabella player_scores esiste
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");

        // Crea la tabella solo se non esiste
        if (!$table_exists) {
            error_log('Player scores table does not exist, creating it');
            $charset_collate = $wpdb->get_charset_collate();
            $sql_player_scores = "CREATE TABLE $player_scores_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                employee_id bigint(20) NOT NULL,
                player_points int(11) NOT NULL DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_employee (user_id, employee_id),
                KEY employee_id (employee_id),
                KEY user_id (user_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_player_scores);
        } else {
            error_log('Player scores table already exists, skipping creation');
        }

        // Check if table was created successfully
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
        if (!$table_exists) {
            error_log('Failed to create player scores table: ' . $wpdb->last_error);
        } else {
            error_log('Player scores table created successfully');

            // Verify table structure
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $player_scores_table");
            error_log('Player scores table structure: ' . print_r($columns, true));

            // Check if table has data
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $player_scores_table");
            error_log('Player scores table has ' . $count . ' rows');

            // We don't need to add test records automatically
            // This was causing errors when is_user_logged_in() was not available
        }

        // Check if notifications table exists
        $notifications_table = $wpdb->prefix . 'jo_exit_notifications';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$notifications_table'");

        if (!$table_exists) {
            error_log('Notifications table does not exist, creating it');
            $charset_collate = $wpdb->get_charset_collate();
            $sql_notifications = "CREATE TABLE $notifications_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                content text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_notifications);
        }

        // Check if notification reads table exists
        $notification_reads_table = $wpdb->prefix . 'jo_exit_notification_reads';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$notification_reads_table'");

        if (!$table_exists) {
            error_log('Notification reads table does not exist, creating it');
            $charset_collate = $wpdb->get_charset_collate();
            $sql_notification_reads = "CREATE TABLE $notification_reads_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                notification_id mediumint(9) NOT NULL,
                user_id mediumint(9) NOT NULL,
                read_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY notification_user (notification_id, user_id),
                KEY notification_id (notification_id),
                KEY user_id (user_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_notification_reads);
        }

        // Check if changelog table exists
        $changelog_table = $wpdb->prefix . 'jo_exit_changelog';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$changelog_table'");

        if (!$table_exists) {
            error_log('Changelog table does not exist, creating it');
            $charset_collate = $wpdb->get_charset_collate();
            $sql_changelog = "CREATE TABLE $changelog_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                content text NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_changelog);

            // Insert default changelog entry
            $wpdb->insert(
                $changelog_table,
                array(
                    'content' => '<h2>Changelog</h2><p>Welcome to JOb Exit! This is the changelog where you can find information about updates and new features.</p>',
                )
            );
        }
    }
}
