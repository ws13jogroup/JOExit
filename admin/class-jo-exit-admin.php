<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 */
class Jo_Exit_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Register AJAX handlers
        add_action('wp_ajax_jo_exit_save_theme_settings', array($this, 'ajax_save_theme_settings'));
        add_action('wp_ajax_jo_exit_get_user_votes', array($this, 'ajax_get_user_votes'));
        add_action('wp_ajax_jo_exit_update_user_vote', array($this, 'ajax_update_user_vote'));
        add_action('wp_ajax_jo_exit_reset_user_votes', array($this, 'ajax_reset_user_votes'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/jo-exit-admin.css', array(), $this->version, 'all');
        // Add Font Awesome for the exit icon
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', array(), '6.4.2', 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/jo-exit-admin.js', array('jquery'), $this->version, false);

        // Add localized script data
        wp_localize_script($this->plugin_name, 'jo_exit_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jo_exit_admin_nonce'),
            'theme_color' => get_option('jo_exit_theme_color', '#ff4757'),
            // Translatable strings
            'select_photo' => esc_html__('Select Employee Photo', 'job-exit-plugin'),
            'use_photo' => esc_html__('Use this photo', 'job-exit-plugin'),
            'update_employee' => esc_html__('Update Employee', 'job-exit-plugin'),
            'add_employee' => esc_html__('Add Employee', 'job-exit-plugin'),
            'confirm_delete' => esc_html__('Are you sure you want to delete this employee?', 'job-exit-plugin'),
            'confirm_reset_all_cooldowns' => esc_html__('Are you sure you want to reset cooldown for all users?', 'job-exit-plugin'),
            'error_save' => esc_html__('An error occurred while saving the employee.', 'job-exit-plugin'),
            'error_load' => esc_html__('Unable to load employee data.', 'job-exit-plugin'),
            'error_load_data' => esc_html__('An error occurred while loading employee data.', 'job-exit-plugin'),
            'error_delete' => esc_html__('An error occurred while deleting the employee.', 'job-exit-plugin'),
            'error_mark_exit' => esc_html__('An error occurred while marking the employee as exited.', 'job-exit-plugin'),
            'error_reset_votes' => esc_html__('An error occurred while resetting votes.', 'job-exit-plugin'),
            'error_reset_cooldown' => esc_html__('An error occurred while resetting cooldown.', 'job-exit-plugin'),
            'error_reset_all_cooldowns' => esc_html__('An error occurred while resetting cooldown for all users.', 'job-exit-plugin'),
            'error_update_exp' => esc_html__('An error occurred while updating experience points.', 'job-exit-plugin'),
            'confirm_delete_from_leaderboard' => esc_html__('Are you sure you want to remove {name} from the leaderboard? All their experience points will be deleted.', 'job-exit-plugin'),
            'error_load_users' => esc_html__('An error occurred while loading users.', 'job-exit-plugin'),
            'no_users_found' => esc_html__('No users found.', 'job-exit-plugin'),
            'no_users_in_leaderboard' => esc_html__('No users found in the leaderboard.', 'job-exit-plugin'),
            'confirm_reactivate' => esc_html__('Are you sure you want to reactivate {name}?', 'job-exit-plugin'),
            'error_reactivate' => esc_html__('An error occurred while reactivating the employee.', 'job-exit-plugin'),
            'in_cooldown' => esc_html__('In Cooldown', 'job-exit-plugin'),
            'no_cooldown' => esc_html__('No Cooldown', 'job-exit-plugin'),
            'edit_employee' => esc_html__('Edit Employee', 'job-exit-plugin'),
            'mark_as_exited' => esc_html__('Mark as Exited', 'job-exit-plugin'),
            'delete_employee' => esc_html__('Delete Employee', 'job-exit-plugin'),
            'no_employees_found' => esc_html__('No employees found.', 'job-exit-plugin'),
            'no_exited_employees_found' => esc_html__('No exited employees found.', 'job-exit-plugin'),
            'reactivate_employee' => esc_html__('Reactivate Employee', 'job-exit-plugin'),
            'error_load_employees' => esc_html__('An error occurred while loading employees.', 'job-exit-plugin'),
            'error_load_exited_employees' => esc_html__('An error occurred while loading exited employees.', 'job-exit-plugin'),
            'error_delete_from_leaderboard' => esc_html__('An error occurred while removing the user from the leaderboard.', 'job-exit-plugin'),
            'confirm_reset_all_exp_points' => esc_html__('Are you sure you want to reset all experience points? This action cannot be undone.', 'job-exit-plugin'),
            'error_reset_exp_points' => esc_html__('An error occurred while resetting experience points.', 'job-exit-plugin'),
            'confirm_delete_all_exp_points' => esc_html__('Are you sure you want to delete all experience points? This action cannot be undone and will remove all users from the leaderboard.', 'job-exit-plugin'),
            'error_delete_exp_points' => esc_html__('An error occurred while deleting experience points.', 'job-exit-plugin'),
            'error_save_theme_settings' => esc_html__('An error occurred while saving theme settings.', 'job-exit-plugin'),
            'theme_settings_saved' => esc_html__('Theme settings saved successfully.', 'job-exit-plugin'),
            'theme_color_reset' => esc_html__('Theme color has been reset to default.', 'job-exit-plugin'),
            'upload_logo_title' => esc_html__('Select or Upload Logo', 'job-exit-plugin'),
            'upload_logo_button' => esc_html__('Use this logo', 'job-exit-plugin'),
            'error_not_image' => esc_html__('Please select an image file.', 'job-exit-plugin'),
            'logo_reset' => esc_html__('Logo has been reset to default.', 'job-exit-plugin'),
            'default_logo_url' => plugin_dir_url(dirname(__FILE__)) . 'public/img/jo-exit-logo-white.png',
            // User votes page strings
            'no_user_votes_found' => esc_html__('No user votes found.', 'job-exit-plugin'),
            'error_load_user_votes' => esc_html__('An error occurred while loading user votes.', 'job-exit-plugin'),
            'error_update_vote' => esc_html__('An error occurred while updating the vote.', 'job-exit-plugin'),
            'error_reset_user_votes' => esc_html__('An error occurred while resetting user votes.', 'job-exit-plugin'),
            'confirm_reset_user_votes' => esc_html__('Are you sure you want to reset all votes for this user?', 'job-exit-plugin'),
            'vote_updated' => esc_html__('Vote updated successfully.', 'job-exit-plugin'),
            'user_votes_reset' => esc_html__('User votes reset successfully.', 'job-exit-plugin'),
            'exit_text' => esc_html__('Exit', 'job-exit-plugin'),
            'nope_text' => esc_html__('Nope', 'job-exit-plugin'),
            'edit_vote' => esc_html__('Edit Vote', 'job-exit-plugin'),
            'reset_all_votes' => esc_html__('Reset All Votes', 'job-exit-plugin'),
        ));

        // Add media uploader scripts
        wp_enqueue_media();
    }

    /**
     * Add menu items to the admin dashboard.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Force update database structure
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php';
        Jo_Exit_Activator::update_database_structure();

        // Main menu item
        add_menu_page(
            'JOb Exit',
            'JOb Exit',
            'manage_options',
            'jo-exit',
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-groups',
            26
        );

        // Employees submenu
        add_submenu_page(
            'jo-exit',
            'Manage Employees',
            'Employees',
            'manage_options',
            'jo-exit',
            array($this, 'display_plugin_admin_dashboard')
        );

        // Exited employees submenu
        add_submenu_page(
            'jo-exit',
            'Exited Employees',
            'Exited Employees',
            'manage_options',
            'jo-exit-exited',
            array($this, 'display_exited_employees')
        );

        // User Votes submenu
        add_submenu_page(
            'jo-exit',
            'User Votes',
            'User Votes',
            'manage_options',
            'jo-exit-user-votes',
            array($this, 'display_user_votes')
        );

        // Notifications submenu
        add_submenu_page(
            'jo-exit',
            'Notifications',
            'Notifications',
            'manage_options',
            'jo-exit-notifications',
            array($this, 'display_notifications')
        );

        // Changelog submenu
        add_submenu_page(
            'jo-exit',
            'Changelog',
            'Changelog',
            'manage_options',
            'jo-exit-changelog',
            array($this, 'display_changelog')
        );

        // Settings submenu
        add_submenu_page(
            'jo-exit',
            'Settings',
            'Settings',
            'manage_options',
            'jo-exit-settings',
            array($this, 'display_plugin_settings')
        );
    }

    /**
     * Display the main admin dashboard.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_dashboard() {
        include_once 'partials/jo-exit-admin-display.php';
    }

    /**
     * Display the exited employees page.
     *
     * @since    1.0.0
     */
    public function display_exited_employees() {
        include_once 'partials/jo-exit-admin-exited.php';
    }

    /**
     * Display the user votes page.
     *
     * @since    1.0.0
     */
    public function display_user_votes() {
        include_once 'partials/jo-exit-admin-user-votes.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_plugin_settings() {
        include_once 'partials/jo-exit-admin-settings.php';
    }

    /**
     * Display the notifications page.
     *
     * @since    1.0.0
     */
    public function display_notifications() {
        include_once 'partials/jo-exit-admin-notifications.php';
    }

    /**
     * Display the changelog page.
     *
     * @since    1.0.0
     */
    public function display_changelog() {
        include_once 'partials/jo-exit-admin-changelog.php';
    }

    /**
     * AJAX handler for saving an employee.
     *
     * @since    1.0.0
     */
    public function ajax_save_employee() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Permission denied', 'job-exit-plugin'));
        }

        // Validate and sanitize input
        $employee_data = array(
            'id' => isset($_POST['id']) ? intval($_POST['id']) : null,
            'first_name' => wp_unslash(sanitize_text_field($_POST['first_name'])),
            'last_name' => wp_unslash(sanitize_text_field($_POST['last_name'])),
            'role' => 'employee', // Default role
            'company_code' => wp_unslash(sanitize_text_field($_POST['company_code'])),
            'photo_url' => esc_url_raw($_POST['photo_url']),
        );

        // Add hire_year if provided
        if (isset($_POST['hire_year'])) {
            $hire_year = intval($_POST['hire_year']);
            // Validate year is reasonable
            if ($hire_year >= 1900 && $hire_year <= date('Y')) {
                $employee_data['hire_year'] = $hire_year;
            }
        }

        // Add score if provided
        if (isset($_POST['score'])) {
            $employee_data['score'] = intval($_POST['score']);
        }

        // Validate required fields
        if (empty($employee_data['first_name']) || empty($employee_data['last_name']) ||
            empty($employee_data['company_code']) || empty($employee_data['photo_url'])) {
            wp_send_json_error(esc_html__('All fields are required', 'job-exit-plugin'));
        }

        // Save employee
        $result = Jo_Exit_DB::save_employee($employee_data);

        if ($result) {
            wp_send_json_success(array(
                'message' => esc_html__('Employee saved successfully', 'job-exit-plugin'),
                'employee_id' => $result,
            ));
        } else {
            wp_send_json_error(esc_html__('Unable to save employee', 'job-exit-plugin'));
        }
    }

    /**
     * AJAX handler for deleting an employee.
     *
     * @since    1.0.0
     */
    public function ajax_delete_employee() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $employee_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($employee_id <= 0) {
            wp_send_json_error('ID dipendente non valido');
        }

        // Delete employee
        $result = Jo_Exit_DB::delete_employee($employee_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Dipendente eliminato con successo',
            ));
        } else {
            wp_send_json_error('Impossibile eliminare il dipendente');
        }
    }

    /**
     * AJAX handler for getting employees.
     *
     * @since    1.0.0
     */
    public function ajax_get_employees() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Get employee ID if specified
        $employee_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($employee_id > 0) {
            // Get specific employee
            $employee = Jo_Exit_DB::get_employee($employee_id);

            if ($employee) {
                wp_send_json_success(array(
                    'employees' => array($employee),
                ));
            } else {
                wp_send_json_error('Dipendente non trovato');
            }
        } else {
            // Get all active employees
            $employees = Jo_Exit_DB::get_active_employees();

            wp_send_json_success(array(
                'employees' => $employees,
            ));
        }
    }

    /**
     * AJAX handler for resetting votes for an employee.
     *
     * @since    1.0.0
     */
    public function ajax_reset_votes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $employee_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($employee_id <= 0) {
            wp_send_json_error('ID dipendente non valido');
        }

        // Reset votes
        $result = Jo_Exit_DB::reset_votes($employee_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Voti azzerati con successo',
            ));
        } else {
            wp_send_json_error('Impossibile azzerare i voti');
        }
    }

    /**
     * AJAX handler for marking an employee as exited.
     *
     * @since    1.0.0
     */
    public function ajax_mark_exit() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Force update database structure
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php';
        Jo_Exit_Activator::update_database_structure();

        // Validate input
        $employee_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $exit_date = isset($_POST['exit_date']) ? sanitize_text_field($_POST['exit_date']) : date('Y-m-d');

        if ($employee_id <= 0) {
            wp_send_json_error('ID dipendente non valido');
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $exit_date)) {
            $exit_date = date('Y-m-d');
        }

        // Mark as exited
        $result = Jo_Exit_DB::mark_exit($employee_id, $exit_date);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Dipendente segnato come uscito con successo',
            ));
        } else {
            wp_send_json_error('Impossibile segnare il dipendente come uscito');
        }
    }

    /**
     * AJAX handler for reactivating an exited employee.
     *
     * @since    1.0.0
     */
    public function ajax_reactivate_employee() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $employee_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($employee_id <= 0) {
            wp_send_json_error('ID dipendente non valido');
        }

        // Reactivate employee
        $result = Jo_Exit_DB::reactivate_employee($employee_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Dipendente riattivato con successo',
            ));
        } else {
            wp_send_json_error('Impossibile riattivare il dipendente');
        }
    }

    /**
     * AJAX handler for saving theme settings.
     *
     * @since    1.0.0
     */
    public function ajax_save_theme_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Permission denied', 'job-exit-plugin'));
        }

        // Validate and sanitize theme color input
        $theme_color = isset($_POST['theme_color']) ? sanitize_hex_color($_POST['theme_color']) : '#ff4757';

        // If not a valid hex color, use default
        if (!$theme_color) {
            $theme_color = '#ff4757';
        }

        // Validate and sanitize logo URL input
        $logo_url = isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '';
        $default_logo_url = plugin_dir_url(dirname(__FILE__)) . 'public/img/jo-exit-logo-white.png';

        // If empty, use default logo
        if (empty($logo_url)) {
            $logo_url = $default_logo_url;
        }

        // Save theme color
        update_option('jo_exit_theme_color', $theme_color);

        // Save logo URL
        update_option('jo_exit_logo_url', $logo_url);

        wp_send_json_success(array(
            'message' => esc_html__('Theme settings saved successfully', 'job-exit-plugin'),
            'theme_color' => $theme_color,
            'logo_url' => $logo_url
        ));
    }

    /**
     * AJAX handler for getting exited employees.
     *
     * @since    1.0.0
     */
    public function ajax_get_exited() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Get exited employees
        $exited_employees = Jo_Exit_DB::get_exited_employees();

        // Calculate final score for each employee if not already set
        if (!empty($exited_employees)) {
            foreach ($exited_employees as $key => $employee) {
                // If final_score is not set or is 0, calculate it
                if (!isset($employee->final_score) || intval($employee->final_score) <= 0) {
                    // Calculate final score (exit points + seniority)
                    $exit_points = intval($employee->score);
                    $years = 0;

                    // Calculate years of seniority if hire_year is available
                    if (!empty($employee->hire_year) && is_numeric($employee->hire_year)) {
                        $current_year = intval(date('Y'));
                        $hire_year = intval($employee->hire_year);

                        if ($hire_year > 1900 && $hire_year <= $current_year) {
                            $years = $current_year - $hire_year;
                        }
                    }

                    // Calculate final score
                    $final_score = $exit_points + $years;

                    // Update employee in database
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'jo_exit_employees';
                    $wpdb->update(
                        $table_name,
                        array('final_score' => $final_score),
                        array('id' => $employee->id)
                    );

                    // Update employee in the array
                    $exited_employees[$key]->final_score = $final_score;
                }
            }
        }

        wp_send_json_success(array(
            'employees' => $exited_employees,
        ));
    }

    /**
     * AJAX handler for editing an employee.
     *
     * @since    1.0.0
     */
    public function ajax_edit_employee() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $employee_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $first_name = isset($_POST['first_name']) ? wp_unslash(sanitize_text_field($_POST['first_name'])) : '';
        $last_name = isset($_POST['last_name']) ? wp_unslash(sanitize_text_field($_POST['last_name'])) : '';
        // Role field removed
        $company_code = isset($_POST['company_code']) ? wp_unslash(sanitize_text_field($_POST['company_code'])) : '';
        $hire_year = isset($_POST['hire_year']) ? intval($_POST['hire_year']) : null;

        if ($employee_id <= 0) {
            wp_send_json_error('ID dipendente non valido');
        }

        if (empty($first_name) || empty($last_name)) {
            wp_send_json_error('Nome e cognome sono obbligatori');
        }

        // Validate hire_year if provided
        if ($hire_year !== null && ($hire_year < 1900 || $hire_year > date('Y'))) {
            wp_send_json_error('Anno di assunzione non valido');
        }

        // Update employee
        $result = Jo_Exit_DB::update_employee($employee_id, $first_name, $last_name, $company_code, $hire_year);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Dipendente aggiornato con successo',
            ));
        } else {
            wp_send_json_error('Impossibile aggiornare il dipendente');
        }
    }

    /**
     * AJAX handler for getting users cooldown status.
     *
     * @since    1.0.0
     */
    public function ajax_get_users_cooldown() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Include the user class
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-jo-exit-user.php';

        // Get users cooldown status
        $users_cooldown = Jo_Exit_User::get_users_cooldown_status();

        wp_send_json_success(array(
            'users' => $users_cooldown,
        ));
    }

    /**
     * AJAX handler for resetting user cooldown.
     *
     * @since    1.0.0
     */
    public function ajax_reset_user_cooldown() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if ($user_id <= 0) {
            wp_send_json_error('ID utente non valido');
        }

        // Include the user class
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-jo-exit-user.php';

        // Reset user cooldown
        $result = Jo_Exit_User::reset_cooldown($user_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Cooldown azzerato con successo',
            ));
        } else {
            wp_send_json_error('Impossibile azzerare il cooldown');
        }
    }

    /**
     * AJAX handler for resetting all users cooldown.
     *
     * @since    1.0.0
     */
    public function ajax_reset_all_cooldowns() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Include the user class
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-jo-exit-user.php';

        // Reset all cooldowns
        $result = Jo_Exit_User::reset_all_cooldowns();

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Cooldown azzerato per tutti gli utenti',
                'count' => $result,
            ));
        } else {
            wp_send_json_error('Impossibile azzerare il cooldown per tutti gli utenti');
        }
    }

    /**
     * AJAX handler for getting users with experience points.
     *
     * @since    1.0.0
     */
    public function ajax_get_users_exp_points() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Get users with experience points
        $users = Jo_Exit_DB::get_all_users_with_exp_points();

        wp_send_json_success(array(
            'users' => $users,
        ));
    }

    /**
     * AJAX handler for updating user experience points.
     *
     * @since    1.0.0
     */
    public function ajax_update_user_exp_points() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $exp_points = isset($_POST['exp_points']) ? intval($_POST['exp_points']) : 0;

        if ($user_id <= 0) {
            wp_send_json_error('ID utente non valido');
        }

        if ($exp_points < 0) {
            wp_send_json_error('I punti esperienza non possono essere negativi');
        }

        // Update user experience points
        $result = Jo_Exit_DB::update_user_exp_points($user_id, $exp_points);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Punti esperienza aggiornati con successo',
            ));
        } else {
            wp_send_json_error('Impossibile aggiornare i punti esperienza');
        }
    }

    /**
     * AJAX handler for deleting user from leaderboard.
     *
     * @since    1.0.0
     */
    public function ajax_delete_user_from_leaderboard() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if ($user_id <= 0) {
            wp_send_json_error('ID utente non valido');
        }

        // Delete user from leaderboard
        $result = Jo_Exit_DB::delete_user_from_leaderboard($user_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Utente rimosso dalla classifica con successo',
            ));
        } else {
            wp_send_json_error('Impossibile rimuovere l\'utente dalla classifica');
        }
    }

    /**
     * AJAX handler for resetting all experience points.
     *
     * @since    1.0.0
     */
    public function ajax_reset_all_exp_points() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Reset all experience points
        $result = Jo_Exit_DB::reset_all_exp_points();

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Tutti i punti esperienza sono stati azzerati',
            ));
        } else {
            wp_send_json_error('Impossibile azzerare i punti esperienza');
        }
    }

    /**
     * AJAX handler for deleting all experience points.
     *
     * @since    1.0.0
     */
    public function ajax_delete_all_exp_points() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Delete all experience points
        $result = Jo_Exit_DB::delete_all_exp_points();

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Tutti i punti esperienza sono stati eliminati',
            ));
        } else {
            wp_send_json_error('Impossibile eliminare i punti esperienza');
        }
    }

    /**
     * AJAX handler for getting user votes.
     *
     * @since    1.0.0
     */
    public function ajax_get_user_votes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Get user ID if specified
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

        // Get user votes
        $votes = Jo_Exit_DB::get_user_votes($user_id, $employee_id);

        wp_send_json_success(array(
            'votes' => $votes,
        ));
    }

    /**
     * AJAX handler for updating a user vote.
     *
     * @since    1.0.0
     */
    public function ajax_update_user_vote() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        $vote_type = isset($_POST['vote_type']) ? sanitize_text_field($_POST['vote_type']) : '';
        $points = isset($_POST['points']) ? intval($_POST['points']) : 0;

        if ($user_id <= 0 || $employee_id <= 0 || !in_array($vote_type, array('exit', 'nope'))) {
            wp_send_json_error('Parametri non validi');
        }

        // Update vote
        $result = Jo_Exit_DB::update_user_vote($user_id, $employee_id, $vote_type, $points);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Voto aggiornato con successo',
            ));
        } else {
            wp_send_json_error('Impossibile aggiornare il voto');
        }
    }

    /**
     * AJAX handler for resetting user votes.
     *
     * @since    1.0.0
     */
    public function ajax_reset_user_votes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_admin_nonce')) {
            wp_send_json_error('Controllo di sicurezza fallito');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }

        // Validate input
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if ($user_id <= 0) {
            wp_send_json_error('ID utente non valido');
        }

        // Reset user votes
        $result = Jo_Exit_DB::reset_user_votes($user_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Voti dell\'utente azzerati con successo',
            ));
        } else {
            wp_send_json_error('Impossibile azzerare i voti dell\'utente');
        }
    }

    // The ajax_fix_all_user_votes function has been removed because it is no longer needed

    /**
     * AJAX handler for getting all notifications.
     *
     * @since    1.0.0
     */
    public function ajax_get_notifications() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'job-exit-plugin')));
        }

        // Get all notifications
        $notifications = Jo_Exit_Notifications::get_all_notifications();

        wp_send_json_success(array('notifications' => $notifications));
    }

    /**
     * AJAX handler for getting a single notification.
     *
     * @since    1.0.0
     */
    public function ajax_get_notification() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'job-exit-plugin')));
        }

        // Check if notification ID is provided
        if (!isset($_POST['notification_id'])) {
            wp_send_json_error(array('message' => esc_html__('Notification ID is required.', 'job-exit-plugin')));
        }

        $notification_id = intval($_POST['notification_id']);

        // Get the notification
        $notification = Jo_Exit_Notifications::get_notification($notification_id);

        if (!$notification) {
            wp_send_json_error(array('message' => esc_html__('Notification not found.', 'job-exit-plugin')));
        }

        wp_send_json_success(array('notification' => $notification));
    }

    /**
     * AJAX handler for saving a notification.
     *
     * @since    1.0.0
     */
    public function ajax_save_notification() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'job-exit-plugin')));
        }

        // Check if required fields are provided
        if (!isset($_POST['title']) || !isset($_POST['content'])) {
            wp_send_json_error(array('message' => esc_html__('Title and content are required.', 'job-exit-plugin')));
        }

        $data = array(
            'title' => stripslashes($_POST['title']),
            'content' => stripslashes($_POST['content']),
        );

        // Check if we're updating an existing notification
        if (isset($_POST['notification_id']) && !empty($_POST['notification_id'])) {
            $notification_id = intval($_POST['notification_id']);
            $result = Jo_Exit_Notifications::update_notification($notification_id, $data);

            if (!$result) {
                wp_send_json_error(array('message' => esc_html__('Failed to update notification.', 'job-exit-plugin')));
            }

            wp_send_json_success(array(
                'message' => esc_html__('Notification updated successfully.', 'job-exit-plugin'),
                'notification_id' => $notification_id,
            ));
        } else {
            // Create a new notification
            $notification_id = Jo_Exit_Notifications::create_notification($data);

            if (!$notification_id) {
                wp_send_json_error(array('message' => esc_html__('Failed to create notification.', 'job-exit-plugin')));
            }

            wp_send_json_success(array(
                'message' => esc_html__('Notification created successfully.', 'job-exit-plugin'),
                'notification_id' => $notification_id,
            ));
        }
    }

    /**
     * AJAX handler for deleting a notification.
     *
     * @since    1.0.0
     */
    public function ajax_delete_notification() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'job-exit-plugin')));
        }

        // Check if notification ID is provided
        if (!isset($_POST['notification_id'])) {
            wp_send_json_error(array('message' => esc_html__('Notification ID is required.', 'job-exit-plugin')));
        }

        $notification_id = intval($_POST['notification_id']);

        // Delete the notification
        $result = Jo_Exit_Notifications::delete_notification($notification_id);

        if (!$result) {
            wp_send_json_error(array('message' => esc_html__('Failed to delete notification.', 'job-exit-plugin')));
        }

        wp_send_json_success(array('message' => esc_html__('Notification deleted successfully.', 'job-exit-plugin')));
    }

    /**
     * AJAX handler for getting the changelog.
     *
     * @since    1.0.0
     */
    public function ajax_get_changelog() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'job-exit-plugin')));
        }

        // Get the changelog content
        $content = Jo_Exit_Notifications::get_changelog();

        wp_send_json_success(array('content' => $content));
    }

    /**
     * AJAX handler for saving the changelog.
     *
     * @since    1.0.0
     */
    public function ajax_save_changelog() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'job-exit-plugin')));
        }

        // Check if content is provided
        if (!isset($_POST['content'])) {
            wp_send_json_error(array('message' => esc_html__('Content is required.', 'job-exit-plugin')));
        }

        $content = stripslashes($_POST['content']);

        // Update the changelog
        $result = Jo_Exit_Notifications::update_changelog($content);

        if (!$result) {
            wp_send_json_error(array('message' => esc_html__('Failed to update changelog.', 'job-exit-plugin')));
        }

        wp_send_json_success(array('message' => esc_html__('Changelog updated successfully.', 'job-exit-plugin')));
    }
}
