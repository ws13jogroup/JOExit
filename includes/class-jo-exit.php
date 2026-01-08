<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 */
class Jo_Exit {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Jo_Exit_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = JO_EXIT_PLUGIN_VERSION;
        $this->plugin_name = 'job-exit-plugin';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        // Force update database structure on plugin load
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-jo-exit-activator.php';
        Jo_Exit_Activator::update_database_structure();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once JO_EXIT_PLUGIN_PATH . 'includes/class-jo-exit-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once JO_EXIT_PLUGIN_PATH . 'admin/class-jo-exit-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once JO_EXIT_PLUGIN_PATH . 'public/class-jo-exit-public.php';

        /**
         * The class responsible for user management functionality.
         */
        require_once JO_EXIT_PLUGIN_PATH . 'public/class-jo-exit-user.php';

        /**
         * The class responsible for defining all database operations.
         */
        require_once JO_EXIT_PLUGIN_PATH . 'includes/class-jo-exit-db.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once JO_EXIT_PLUGIN_PATH . 'includes/class-job-exit-i18n.php';

        /**
         * The class responsible for notifications management.
         */
        require_once JO_EXIT_PLUGIN_PATH . 'includes/class-jo-exit-notifications.php';

        $this->loader = new Jo_Exit_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Job_Exit_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Job_Exit_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Jo_Exit_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Add menu items
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Add AJAX handlers for admin
        $this->loader->add_action('wp_ajax_jo_exit_save_employee', $plugin_admin, 'ajax_save_employee');
        $this->loader->add_action('wp_ajax_jo_exit_delete_employee', $plugin_admin, 'ajax_delete_employee');
        $this->loader->add_action('wp_ajax_jo_exit_mark_exit', $plugin_admin, 'ajax_mark_exit');
        $this->loader->add_action('wp_ajax_jo_exit_get_employees', $plugin_admin, 'ajax_get_employees');
        $this->loader->add_action('wp_ajax_jo_exit_reset_votes', $plugin_admin, 'ajax_reset_votes');
        $this->loader->add_action('wp_ajax_jo_exit_reactivate_employee', $plugin_admin, 'ajax_reactivate_employee');
        $this->loader->add_action('wp_ajax_jo_exit_edit_employee', $plugin_admin, 'ajax_edit_employee');
        $this->loader->add_action('wp_ajax_jo_exit_get_exited', $plugin_admin, 'ajax_get_exited');

        // Cooldown management
        $this->loader->add_action('wp_ajax_jo_exit_get_users_cooldown', $plugin_admin, 'ajax_get_users_cooldown');
        $this->loader->add_action('wp_ajax_jo_exit_reset_user_cooldown', $plugin_admin, 'ajax_reset_user_cooldown');
        $this->loader->add_action('wp_ajax_jo_exit_reset_all_cooldowns', $plugin_admin, 'ajax_reset_all_cooldowns');

        // Global leaderboard management
        $this->loader->add_action('wp_ajax_jo_exit_get_users_exp_points', $plugin_admin, 'ajax_get_users_exp_points');
        $this->loader->add_action('wp_ajax_jo_exit_update_user_exp_points', $plugin_admin, 'ajax_update_user_exp_points');
        $this->loader->add_action('wp_ajax_jo_exit_delete_user_from_leaderboard', $plugin_admin, 'ajax_delete_user_from_leaderboard');
        $this->loader->add_action('wp_ajax_jo_exit_reset_all_exp_points', $plugin_admin, 'ajax_reset_all_exp_points');
        $this->loader->add_action('wp_ajax_jo_exit_delete_all_exp_points', $plugin_admin, 'ajax_delete_all_exp_points');

        // Notifications management
        $this->loader->add_action('wp_ajax_jo_exit_get_notifications', $plugin_admin, 'ajax_get_notifications');
        $this->loader->add_action('wp_ajax_jo_exit_save_notification', $plugin_admin, 'ajax_save_notification');
        $this->loader->add_action('wp_ajax_jo_exit_delete_notification', $plugin_admin, 'ajax_delete_notification');
        $this->loader->add_action('wp_ajax_jo_exit_get_notification', $plugin_admin, 'ajax_get_notification');

        // Changelog management
        $this->loader->add_action('wp_ajax_jo_exit_get_changelog', $plugin_admin, 'ajax_get_changelog');
        $this->loader->add_action('wp_ajax_jo_exit_save_changelog', $plugin_admin, 'ajax_save_changelog');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Jo_Exit_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Add shortcodes
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');

        // Add AJAX handlers for public
        $this->loader->add_action('wp_ajax_jo_exit_vote', $plugin_public, 'ajax_vote');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_vote', $plugin_public, 'ajax_vote');

        $this->loader->add_action('wp_ajax_jo_exit_get_employees', $plugin_public, 'ajax_get_employees');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_get_employees', $plugin_public, 'ajax_get_employees');

        $this->loader->add_action('wp_ajax_jo_exit_get_leaderboard', $plugin_public, 'ajax_get_leaderboard');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_get_leaderboard', $plugin_public, 'ajax_get_leaderboard');

        $this->loader->add_action('wp_ajax_jo_exit_get_exited', $plugin_public, 'ajax_get_exited');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_get_exited', $plugin_public, 'ajax_get_exited');

        $this->loader->add_action('wp_ajax_jo_exit_load_account', $plugin_public, 'ajax_load_account');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_load_account', $plugin_public, 'ajax_load_account');

        $this->loader->add_action('wp_ajax_jo_exit_load_register', $plugin_public, 'ajax_load_register');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_load_register', $plugin_public, 'ajax_load_register');

        $this->loader->add_action('wp_ajax_jo_exit_load_edit_profile', $plugin_public, 'ajax_load_edit_profile');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_load_edit_profile', $plugin_public, 'ajax_load_edit_profile');

        // Initialize user management
        $plugin_user = new Jo_Exit_User();

        // Register user management AJAX handlers
        $this->loader->add_action('wp_ajax_jo_exit_login_user', $plugin_user, 'ajax_login_user');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_login_user', $plugin_user, 'ajax_login_user');

        $this->loader->add_action('wp_ajax_jo_exit_register_user', $plugin_user, 'ajax_register_user');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_register_user', $plugin_user, 'ajax_register_user');

        $this->loader->add_action('wp_ajax_jo_exit_logout_user', $plugin_user, 'ajax_logout_user');

        $this->loader->add_action('wp_ajax_jo_exit_update_profile', $plugin_user, 'ajax_update_profile');

        $this->loader->add_action('wp_ajax_jo_exit_get_user_voting_data', $plugin_user, 'ajax_get_user_voting_data');

        $this->loader->add_action('wp_ajax_jo_exit_upload_avatar', $plugin_user, 'ajax_upload_avatar');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_upload_avatar', $plugin_user, 'ajax_upload_avatar');

        $this->loader->add_action('wp_ajax_jo_exit_delete_account', $plugin_user, 'ajax_delete_account');

        // Cooldown endpoints
        $this->loader->add_action('wp_ajax_jo_exit_check_cooldown', $plugin_user, 'ajax_check_cooldown');
        $this->loader->add_action('wp_ajax_jo_exit_set_cooldown', $plugin_user, 'ajax_set_cooldown');

        // Hide admin bar for subscribers
        add_action('after_setup_theme', function() {
            if (is_user_logged_in() && !current_user_can('edit_posts')) {
                show_admin_bar(false);
            }
        });

        $this->loader->add_action('wp_ajax_jo_exit_load_info', $plugin_public, 'ajax_load_info');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_load_info', $plugin_public, 'ajax_load_info');

        // Player leaderboard
        $this->loader->add_action('wp_ajax_jo_exit_get_player_leaderboard', $plugin_public, 'ajax_get_player_leaderboard');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_get_player_leaderboard', $plugin_public, 'ajax_get_player_leaderboard');

        // User leaderboard
        $this->loader->add_action('wp_ajax_jo_exit_get_user_leaderboard', $plugin_public, 'ajax_get_user_leaderboard');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_get_user_leaderboard', $plugin_public, 'ajax_get_user_leaderboard');

        // Register AJAX endpoint for getting exit votes count
        $this->loader->add_action('wp_ajax_jo_exit_get_exit_votes_count', $plugin_public, 'ajax_get_exit_votes_count');

        // Notifications endpoints
        $this->loader->add_action('wp_ajax_jo_exit_get_unread_notifications', $plugin_public, 'ajax_get_unread_notifications');
        $this->loader->add_action('wp_ajax_jo_exit_mark_notification_read', $plugin_public, 'ajax_mark_notification_read');
        $this->loader->add_action('wp_ajax_jo_exit_get_notification_content', $plugin_public, 'ajax_get_notification_content');
        $this->loader->add_action('wp_ajax_jo_exit_get_unread_count', $plugin_public, 'ajax_get_unread_count');

        // Changelog endpoint
        $this->loader->add_action('wp_ajax_jo_exit_load_changelog', $plugin_public, 'ajax_load_changelog');
        $this->loader->add_action('wp_ajax_nopriv_jo_exit_load_changelog', $plugin_public, 'ajax_load_changelog');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Jo_Exit_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
