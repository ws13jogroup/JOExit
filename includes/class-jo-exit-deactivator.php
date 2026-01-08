<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 */
class Jo_Exit_Deactivator {

    /**
     * Plugin deactivation tasks.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // We don't delete the tables on deactivation to preserve data
        // If you want to delete tables, uncomment the code below
        
        /*
        global $wpdb;
        $table_name_employees = $wpdb->prefix . 'jo_exit_employees';
        $table_name_votes = $wpdb->prefix . 'jo_exit_votes';
        
        $wpdb->query("DROP TABLE IF EXISTS $table_name_votes");
        $wpdb->query("DROP TABLE IF EXISTS $table_name_employees");
        
        delete_option('jo_exit_plugin_version');
        */
    }
}
