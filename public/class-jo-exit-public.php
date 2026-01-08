<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 */
class Jo_Exit_Public {

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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Register AJAX handlers
        add_action('wp_ajax_jo_exit_toggle_dark_mode', array($this, 'ajax_toggle_dark_mode'));
    }

    /**
     * Convert hex color to RGB
     *
     * @since    1.0.0
     * @param    string    $hex    Hex color code
     * @return   string           RGB color values
     */
    private function hex2rgb($hex) {
        $hex = str_replace('#', '', $hex);

        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return $r . ',' . $g . ',' . $b;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/jo-exit-public.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_name . '-in-team', plugin_dir_url(__FILE__) . 'css/jo-exit-in-team.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_name . '-card-counter', plugin_dir_url(__FILE__) . 'css/jo-exit-card-counter.css', array(), $this->version, 'all');
        // Add Font Awesome for the exit icon
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', array(), '6.4.2', 'all');
        // We're using Hammer.js for swipe functionality, no need for Swiper CSS

        // Add WordPress media uploader styles if user is logged in
        if (is_user_logged_in()) {
            wp_enqueue_media();
        }

        // Get theme color from options
        $theme_color = get_option('jo_exit_theme_color', '#ff4757');

        // Add inline CSS for theme color
        $custom_css = "
            .jo-exit-header { background-color: {$theme_color}; }
            .jo-exit-nav-item.active { color: {$theme_color}; }
            .jo-exit-leaderboard-rank { background-color: {$theme_color}; }
            .jo-exit-leaderboard-score { color: {$theme_color}; }
            .jo-exit-exited-winner-label { color: {$theme_color}; }
            .jo-exit-exited-player-score-value { color: {$theme_color}; }
            .jo-exit-exited-score { color: {$theme_color}; }
            .jo-exit-leaderboard-exp { color: {$theme_color} !important; }
            .jo-exit-account-exp { color: {$theme_color} !important; }
            /* Base color for exit votes */
            .jo-exit-voted-employee-points { color: {$theme_color} !important; }
            /* Override for nope votes */
            .jo-exit-voted-exit .jo-exit-voted-employee-points { color: #28a745 !important; }
            .jo-exit-update-profile-button { background-color: {$theme_color}; border-color: {$theme_color}; }
            .jo-exit-update-profile-button:hover { background-color: {$theme_color}; opacity: 0.9; }
            .jo-exit-login-button, .jo-exit-register-button { background-color: {$theme_color}; border-color: {$theme_color}; }
            .jo-exit-login-button:hover, .jo-exit-register-button:hover { background-color: {$theme_color}; opacity: 0.9; }
            .jo-exit-access-required-icon { color: {$theme_color}; }
            .jo-exit-login-register-button { background-color: {$theme_color}; border-color: {$theme_color}; }
            .jo-exit-login-register-button:hover { background-color: {$theme_color}; opacity: 0.9; }
            .jo-exit-countdown { color: {$theme_color}; }
            .jo-exit-countdown-value { color: {$theme_color}; }
            a { color: {$theme_color}; }
            a:hover { color: {$theme_color}; opacity: 0.8; }
            .jo-exit-form-submit { background-color: {$theme_color}; border-color: {$theme_color}; }
            .jo-exit-form-submit:hover { background-color: {$theme_color}; opacity: 0.9; }
            .jo-exit-cooldown-icon { color: {$theme_color}; }
            .jo-exit-cooldown-timer { color: {$theme_color}; }
            .jo-exit-login-icon { color: {$theme_color}; }
            .jo-exit-spinner { border-top-color: {$theme_color}; }
            .jo-exit-reload-button { background-color: {$theme_color}; }
            .jo-exit-reload-button:hover { background-color: {$theme_color}; opacity: 0.9; }
            /* Ironic comment styles are handled directly in JavaScript */
            .jo-exit-info-container h2 { color: {$theme_color}; border-bottom-color: {$theme_color}; }
            .jo-exit-info-container strong { color: {$theme_color}; }
            .jo-exit-info-container ul li:before { color: {$theme_color}; }
            .jo-exit-info-section { border-left-color: {$theme_color}; }
            .jo-exit-info-section:nth-child(3), .jo-exit-info-section:nth-child(6) { border-left-color: {$theme_color}; }
            .jo-exit-button-edit { background-color: {$theme_color}; }
            .jo-exit-button-edit:hover { background-color: {$theme_color}; opacity: 0.9; }
            .jo-exit-button-logout { background-color: {$theme_color}; }
            .jo-exit-button-logout:hover { background-color: {$theme_color}; opacity: 0.9; }
            .jo-exit-account-points-total { color: {$theme_color}; }
            .jo-exit-exit-indicator { background-color: {$theme_color}; border-color: {$theme_color}; }
            .jo-exit-leaderboard-trophy-header { background-color: {$theme_color}; }
            .jo-exit-leaderboard-title { color: {$theme_color}; }
            .jo-exit-exit-table-container h2 { color: {$theme_color}; }

            /* Mantieni il colore originale per i pulsanti EXIT e like */
            .jo-exit-like-button { background-color: #ff4757 !important; }
            .jo-exit-swipe-button.jo-exit-like-button { background-color: #ff4757 !important; }

            /* Mantieni il colore originale per il pulsante di eliminazione account */
            .jo-exit-button-danger { background-color: #ff4757 !important; }
            .jo-exit-button-danger:hover { background-color: #ff4757 !important; opacity: 0.9; }

            /* Applica il colore del tema al pulsante generico */
            .jo-exit-button { background-color: {$theme_color}; }
            .jo-exit-button:hover { background-color: {$theme_color}; opacity: 0.9; }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Add Hammer.js for touch/drag handling
        wp_enqueue_script('hammerjs', 'https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js', array(), '2.0.8', true);

        // Add jQuery UI Touch Punch for better touch support
        wp_enqueue_script('jquery-ui-touch-punch', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js', array('jquery'), '0.2.3', true);

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/jo-exit-public.js', array('jquery', 'hammerjs', 'jquery-ui-touch-punch'), $this->version, true);

        // Add the redirect script for handling the exited screen
        wp_enqueue_script($this->plugin_name . '-redirect', plugin_dir_url(__FILE__) . 'js/jo-exit-redirect.js', array('jquery'), $this->version, true);

        // Get the plugin URL for the custom AJAX handler
        $plugin_url = plugin_dir_url(__FILE__);

        // Add localized script data
        // Get user dark mode preference
        $dark_mode = 'off';
        if (is_user_logged_in()) {
            $dark_mode = get_user_meta(get_current_user_id(), 'jo_exit_dark_mode', true);
            if (empty($dark_mode)) {
                $dark_mode = 'off';
            }
        }

        wp_localize_script($this->plugin_name, 'jo_exit_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'custom_ajax_url' => $plugin_url . 'ajax-handler.php',
            'plugin_url' => $plugin_url,
            'home_url' => home_url('/'),
            'nonce' => wp_create_nonce('jo_exit_public_nonce'),
            'debug' => true,
            'use_custom_ajax' => true, // Use the custom AJAX handler
            'is_user_logged_in' => is_user_logged_in(),
            'dark_mode' => $dark_mode,
            'is_admin' => current_user_can('administrator'),
            'max_upload_size' => wp_max_upload_size(),
            'wp_version' => get_bloginfo('version'),
            'theme_color' => get_option('jo_exit_theme_color', '#ff4757'),
            // Translatable strings
            'loading_text' => esc_html__('Loading employees...', 'job-exit-plugin'),
            'no_employees_text' => esc_html__('No employees available for voting. Check back later!', 'job-exit-plugin'),
            'no_valid_employees_text' => esc_html__('No valid employees available for voting. Check back later!', 'job-exit-plugin'),
            'error_loading_employees' => esc_html__('An error occurred while loading employees.', 'job-exit-plugin'),
            'seniority_label' => esc_html__('Seniority', 'job-exit-plugin'),
            'year_singular' => esc_html__('year', 'job-exit-plugin'),
            'year_plural' => esc_html__('years', 'job-exit-plugin'),
            'not_available' => esc_html__('N/A', 'job-exit-plugin'),
            'access_required' => esc_html__('Access Required', 'job-exit-plugin'),
            'login_required_message' => esc_html__('To vote for employees, you need to log in or register.', 'job-exit-plugin'),
            'login_register_button' => esc_html__('Login / Register', 'job-exit-plugin'),
            'cooldown_title' => esc_html__('Wait before voting again', 'job-exit-plugin'),
            'cooldown_message' => esc_html__('You have voted for all employees. You can vote again in:', 'job-exit-plugin'),
            'seniority' => esc_html__('Seniority:', 'job-exit-plugin'),
            'exited_on' => esc_html__('Exited on:', 'job-exit-plugin'),
            'your_score' => esc_html__('Your score:', 'job-exit-plugin'),
            'points' => esc_html__('points', 'job-exit-plugin'),
            'voted_nope' => esc_html__('Voted NOPE', 'job-exit-plugin'),
            'no_voted_employees' => esc_html__('You have not voted for any employees yet.', 'job-exit-plugin'),
            'loading' => esc_html__('Loading...', 'job-exit-plugin'),
            'loading_scores' => esc_html__('Loading scores...', 'job-exit-plugin'),
            'loading_exited_employees' => esc_html__('Loading exited employees...', 'job-exit-plugin'),
            'loading_global_leaderboard' => esc_html__('Loading global leaderboard...', 'job-exit-plugin'),
            'checking_status' => esc_html__('Checking status...', 'job-exit-plugin'),
            'verifying_url' => esc_html__('Verifying URL...', 'job-exit-plugin'),
            'no_employees_in_leaderboard' => esc_html__('No employees in the score leaderboard.', 'job-exit-plugin'),
            'error_loading_scores' => esc_html__('An error occurred while loading scores.', 'job-exit-plugin'),
            'error_response_format' => esc_html__('Error in server response format.', 'job-exit-plugin'),
            'error_loading_exited' => esc_html__('An error occurred while loading exited employees.', 'job-exit-plugin'),
            'error_loading_data' => esc_html__('Error loading data.', 'job-exit-plugin'),
            'error_loading_account' => esc_html__('Error loading account screen. Please try again later.', 'job-exit-plugin'),
            'limit_message' => esc_html__('You have reached the maximum number of EXIT votes. New votes will replace the oldest ones.', 'job-exit-plugin'),
            'vote_all_message' => esc_html__('To confirm your score, you need to vote for all employees. Keep voting!', 'job-exit-plugin'),
            'error_loading_register' => esc_html__('Error loading registration screen. Please try again later.', 'job-exit-plugin'),
            'error_loading_profile' => esc_html__('Error loading profile screen. Please try again later.', 'job-exit-plugin'),
            'error_updating_profile' => esc_html__('An error occurred while updating your profile. Please try again later.', 'job-exit-plugin'),
            'error_deleting_account' => esc_html__('An error occurred while deleting your account. Please try again later.', 'job-exit-plugin'),
            'deleting_account' => esc_html__('Deleting account...', 'job-exit-plugin'),
            'confirm_delete_account' => esc_html__('Are you sure you want to delete your account? This action is irreversible and all your data will be permanently deleted.', 'job-exit-plugin'),
            'confirm_delete_vote' => esc_html__('Are you sure you want to delete this vote?', 'job-exit-plugin'),
            'deleting_vote' => esc_html__('Deleting vote...', 'job-exit-plugin'),
            'error_deleting_vote' => esc_html__('Error deleting vote', 'job-exit-plugin'),
            'error_deleting_vote_try_again' => esc_html__('Error deleting vote. Please try again later.', 'job-exit-plugin'),
            'all_fields_required' => esc_html__('All fields are required.', 'job-exit-plugin'),
            'email_required' => esc_html__('Email is required.', 'job-exit-plugin'),
            'invalid_url' => esc_html__('Please enter a valid URL.', 'job-exit-plugin'),
            'invalid_url_format' => esc_html__('Please enter a valid URL starting with http:// or https://.', 'job-exit-plugin'),
            'invalid_image_url' => esc_html__('The URL does not seem to be a valid image. Please try another URL.', 'job-exit-plugin'),
            'file_too_large' => esc_html__('The file is too large. Maximum allowed size is 1MB.', 'job-exit-plugin'),
            'unsupported_file_type' => esc_html__('Unsupported file type. Only JPG, PNG and GIF are allowed.', 'job-exit-plugin'),
            'loading_avatar' => esc_html__('Loading avatar...', 'job-exit-plugin'),
            'error_reading_file' => esc_html__('Error reading file. Please try another file or use a URL.', 'job-exit-plugin'),
            'unknown_employee' => esc_html__('Unknown employee', 'job-exit-plugin'),
            'unknown_date' => esc_html__('Unknown date', 'job-exit-plugin'),
            'exited' => esc_html__('Exited', 'job-exit-plugin'),
            'no_exited_employees' => esc_html__('No employees have exited yet.', 'job-exit-plugin'),
            'ajax_config_invalid' => esc_html__('Invalid AJAX configuration.', 'job-exit-plugin'),
            'request_timeout' => esc_html__('Request timed out. Reload the page to try again.', 'job-exit-plugin'),
            'data_unavailable' => esc_html__('Data unavailable.', 'job-exit-plugin'),
            'invalid_response_format' => esc_html__('Invalid response format.', 'job-exit-plugin'),
            'unexpected_error' => esc_html__('An unexpected error occurred.', 'job-exit-plugin'),
            'year_label' => esc_html__('year', 'job-exit-plugin'),
            'years_label' => esc_html__('years', 'job-exit-plugin'),
            'in_team_label' => esc_html__('In Team', 'job-exit-plugin'),
            'card_counter_label' => esc_html__('Card', 'job-exit-plugin'),
            'cards_counter_of' => esc_html__('of', 'job-exit-plugin'),
            // Ironic comments for EXIT votes
            'exit_comments' => array(
                esc_html__('It will be a great loss... or maybe not 😅', 'job-exit-plugin'),
                esc_html__('Another talent leaving... towards the exit! 😏', 'job-exit-plugin'),
                esc_html__('Promotion pending... to the door! 🙄', 'job-exit-plugin'),
                esc_html__('Someone prepare the reference letter... very short! 😉', 'job-exit-plugin'),
                esc_html__('Their JOby is already deactivated! 😜', 'job-exit-plugin'),
                esc_html__('Their desk is already free! 🙋', 'job-exit-plugin'),
                esc_html__('Goodbye, we won\'t miss you... much! 😊', 'job-exit-plugin'),
                esc_html__('This is why you should never get too attached to colleagues! 😂', 'job-exit-plugin'),
                esc_html__('Let\'s see who will be next... 😈', 'job-exit-plugin'),
                esc_html__('One less problem for the HR office! 😎', 'job-exit-plugin')
            ),
            // Ironic comments for NOPE votes
            'nope_comments' => array(
                esc_html__('Saved by a hair... this time! 🙌', 'job-exit-plugin'),
                esc_html__('Survived for another day! 😍', 'job-exit-plugin'),
                esc_html__('Their luck won\'t last forever... 😝', 'job-exit-plugin'),
                esc_html__('Stays... for now! 😎', 'job-exit-plugin'),
                esc_html__('Today is their lucky day! 😉', 'job-exit-plugin'),
                esc_html__('Escaped firing... for this time! 😁', 'job-exit-plugin'),
                esc_html__('Someone up there is protecting them! 😊', 'job-exit-plugin'),
                esc_html__('Will continue to occupy their desk... for now! 😬', 'job-exit-plugin'),
                esc_html__('Still safe... but we\'ll keep an eye on them! 🙂', 'job-exit-plugin'),
                esc_html__('Avoided the worst... at least for today! 😏', 'job-exit-plugin')
            )
        ));
    }

    /**
     * Register shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('job_exit_app', array($this, 'render_app'));
        add_shortcode('job_exit_home', array($this, 'render_home'));
        add_shortcode('job_exit_leaderboard', array($this, 'render_leaderboard'));
        add_shortcode('job_exit_exited', array($this, 'render_exited'));
        add_shortcode('job_exit_account', array($this, 'render_account'));
        add_shortcode('job_exit_register', array($this, 'render_register'));
        add_shortcode('job_exit_edit_profile', array($this, 'render_edit_profile'));
        add_shortcode('job_exit_global_leaderboard', array($this, 'render_global_leaderboard'));

        // Keep old shortcodes for backward compatibility
        add_shortcode('jo_exit_app', array($this, 'render_app'));
        add_shortcode('jo_exit_home', array($this, 'render_home'));
        add_shortcode('jo_exit_leaderboard', array($this, 'render_leaderboard'));
        add_shortcode('jo_exit_exited', array($this, 'render_exited'));
        add_shortcode('jo_exit_account', array($this, 'render_account'));
        add_shortcode('jo_exit_register', array($this, 'render_register'));
        add_shortcode('jo_exit_edit_profile', array($this, 'render_edit_profile'));
        add_shortcode('jo_exit_global_leaderboard', array($this, 'render_global_leaderboard'));
    }

    /**
     * Render the full app.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function render_app($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'theme_color' => '#ff4757',
        ), $atts, 'job_exit_app');

        ob_start();
        include_once 'partials/jo-exit-app.php';
        return ob_get_clean();
    }

    /**
     * Render the home screen.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function render_home($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'theme_color' => '#ff4757',
        ), $atts, 'job_exit_home');

        ob_start();
        include_once 'partials/jo-exit-home.php';
        return ob_get_clean();
    }

    /**
     * Render the Exit Points screen.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function render_leaderboard($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'theme_color' => '#ff4757',
        ), $atts, 'job_exit_leaderboard');

        ob_start();
        include_once 'partials/jo-exit-points.php';
        return ob_get_clean();
    }

    /**
     * Render the exited employees screen.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function render_exited($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'theme_color' => '#ff4757',
        ), $atts, 'job_exit_exited');

        ob_start();
        include_once 'partials/jo-exit-exited.php';
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting employees.
     *
     * @since    1.0.0
     */
    public function ajax_get_employees() {
        // Debug: Log the nonce
        error_log('Received nonce: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'not set'));
        error_log('Expected nonce for jo_exit_public_nonce: ' . wp_create_nonce('jo_exit_public_nonce'));

        // Disabilitiamo temporaneamente la verifica del nonce per debug
        /*
        if (isset($_POST['nonce'])) {
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_get_employees');
                wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                return;
            }
        }
        */

        // Get all active employees
        // No filtering by votes - users can vote multiple times for the same employee
        $employees = Jo_Exit_DB::get_active_employees();

        // Log the number of active employees for debugging
        error_log('Total active employees: ' . count($employees));

        wp_send_json_success(array(
            'employees' => $employees,
        ));
    }

    /**
     * AJAX handler for getting the leaderboard.
     *
     * @since    1.0.0
     */
    public function ajax_get_leaderboard() {
        // Disabilitiamo temporaneamente la verifica del nonce per debug
        /*
        if (isset($_POST['nonce'])) {
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_get_leaderboard');
                wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                return;
            }
        }
        */
        // Get leaderboard
        $leaderboard = Jo_Exit_DB::get_leaderboard();

        wp_send_json_success(array(
            'employees' => $leaderboard,
        ));
    }

    /**
     * AJAX handler for toggling dark mode.
     *
     * @since    1.0.0
     */
    public function ajax_toggle_dark_mode() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(esc_html__('You must be logged in to change settings.', 'job-exit-plugin'));
            return;
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
            wp_send_json_error(esc_html__('Security check failed.', 'job-exit-plugin'));
            return;
        }

        // Get dark mode setting
        $dark_mode = isset($_POST['dark_mode']) ? sanitize_text_field($_POST['dark_mode']) : 'off';

        // Save to user meta
        update_user_meta(get_current_user_id(), 'jo_exit_dark_mode', $dark_mode);

        wp_send_json_success(array(
            'dark_mode' => $dark_mode,
            'message' => esc_html__('Dark mode preference saved.', 'job-exit-plugin')
        ));
    }

    /**
     * AJAX handler for getting exited employees.
     *
     * @since    1.0.0
     */
    public function ajax_get_exited() {
        try {
            // Disabilitiamo temporaneamente la verifica del nonce per debug
            // Questo permetterà di far funzionare la schermata exit
            /*
            if (isset($_POST['nonce'])) {
                $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
                if (!$nonce_verified) {
                    error_log('Jo_Exit: Nonce verification failed in ajax_get_exited');
                    wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                    return;
                }
            }
            */

            // Get exited employees
            $exited = Jo_Exit_DB::get_exited_employees();

            // Ensure $exited is an array
            if (!is_array($exited)) {
                $exited = array();
                error_log('Jo_Exit: get_exited_employees did not return an array');
            }

            // Debug log
            error_log('Jo_Exit: Getting exited employees. Count: ' . count($exited));
            if (count($exited) > 0) {
                error_log('Jo_Exit: First exited employee: ' . print_r($exited[0], true));
            } else {
                error_log('Jo_Exit: No exited employees found');
            }

            // Calcola il punteggio finale per ogni dipendente se non è già impostato
            foreach ($exited as $key => $employee) {
                // Se final_score non è impostato o è 0, calcolalo
                if (!isset($employee->final_score) || intval($employee->final_score) <= 0) {
                    // Calcola il punteggio finale (exit points + anzianità)
                    $exit_points = intval($employee->score);
                    $years = 0;

                    // Calcola gli anni di anzianità se disponibili
                    if (!empty($employee->hire_year) && is_numeric($employee->hire_year)) {
                        $current_year = intval(date('Y'));
                        $hire_year = intval($employee->hire_year);

                        if ($hire_year > 1900 && $hire_year <= $current_year) {
                            $years = $current_year - $hire_year;
                        }
                    }

                    // Calcola il punteggio finale
                    $final_score = $exit_points + $years;

                    // Aggiorna il dipendente nel database
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'jo_exit_employees';
                    $wpdb->update(
                        $table_name,
                        array('final_score' => $final_score),
                        array('id' => $employee->id)
                    );

                    // Aggiorna il dipendente nell'array
                    $exited[$key]->final_score = $final_score;
                }
            }

            // Invia la risposta con i dipendenti usciti
            wp_send_json_success(array(
                'employees' => $exited,
            ));
        } catch (Exception $e) {
            error_log('Jo_Exit: Exception in ajax_get_exited: ' . $e->getMessage());
            wp_send_json_error(esc_html__('An error occurred while loading exited employees.', 'job-exit-plugin'));
        }
    }

    /**
     * AJAX handler for voting.
     *
     * @since    1.0.0
     */
    public function ajax_vote() {
        // Disabilitiamo temporaneamente la verifica del nonce per debug
        /*
        if (isset($_POST['nonce'])) {
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_vote');
                wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                return;
            }
        }
        */

        // Validate input
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        $vote_type = isset($_POST['vote_type']) ? sanitize_text_field($_POST['vote_type']) : '';
        $all_voted = isset($_POST['all_voted']) ? filter_var($_POST['all_voted'], FILTER_VALIDATE_BOOLEAN) : false;

        if ($employee_id <= 0 || !in_array($vote_type, array('exit', 'nope'))) {
            wp_send_json_error(esc_html__('Invalid input', 'job-exit-plugin'));
        }

        // Get user identifier (IP address or user ID if logged in)
        $user_identifier = is_user_logged_in() ? get_current_user_id() : $_SERVER['REMOTE_ADDR'];

        // No voting limits - users can vote multiple times for the same employee

        // Record vote
        $result = Jo_Exit_DB::record_vote($employee_id, $user_identifier, $vote_type);

        // Store vote in user meta if user is logged in
        $cooldown_set = false;
        if (is_user_logged_in()) {
            // Get current exit votes count before storing the new vote
            $user_id = get_current_user_id();
            $current_exit_votes = Jo_Exit_User::count_exit_votes($user_id);
            $max_exit_votes = Jo_Exit_User::get_max_exit_votes();

            error_log('Jo_Exit: BEFORE storing vote, user ' . $user_id . ' has ' . $current_exit_votes . ' exit votes out of ' . $max_exit_votes . ' allowed');

            // We no longer set cooldown when reaching the 5th exit vote
            if ($vote_type === 'exit' && $current_exit_votes + 1 >= $max_exit_votes) {
                error_log('Jo_Exit: This vote will be the 5th exit vote, but NOT forcing cooldown');
            }

            // Store the vote
            $store_result = Jo_Exit_User::store_user_vote($employee_id, $vote_type);

            error_log('Jo_Exit: store_user_vote result: ' . print_r($store_result, true));

            // Check if cooldown was set in the result
            if (isset($store_result['cooldown']) && $store_result['cooldown']) {
                $cooldown_set = true;
                error_log('Jo_Exit: Cooldown set for user ' . $user_id . ' after reaching 5 exit votes');
            }

            // We no longer need to set cooldown when all employees are voted
            // as we're removing that logic
        }

        if ($result) {
            $response_data = array(
                'message' => 'Vote recorded successfully',
                'cooldown' => $cooldown_set,
                'all_employees_voted' => $all_voted
            );

            error_log('Jo_Exit: Sending AJAX response with cooldown=' . ($cooldown_set ? 'true' : 'false') . ', all_employees_voted=' . ($all_voted ? 'true' : 'false'));
            error_log('Jo_Exit: Full response data: ' . print_r($response_data, true));

            wp_send_json_success($response_data);
        } else {
            error_log('Jo_Exit: Failed to record vote');
            wp_send_json_error(esc_html__('Failed to record vote', 'job-exit-plugin'));
        }
    }

    /**
     * AJAX handler for loading the info screen.
     *
     * @since    1.0.0
     */
    public function ajax_load_info() {
        // Disabilitiamo temporaneamente la verifica del nonce per debug
        /*
        if (isset($_POST['nonce'])) {
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_load_info');
                wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                return;
            }
        }
        */

        ob_start();
        include_once 'partials/jo-exit-info.php';
        $content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $content,
        ));
    }

    /**
     * AJAX handler for loading the account screen content.
     *
     * @since    1.0.0
     */
    public function ajax_load_account() {
        try {
            // Disabilitiamo temporaneamente la verifica del nonce per debug
            /*
            if (isset($_POST['nonce'])) {
                $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
                if (!$nonce_verified) {
                    error_log('Jo_Exit: Nonce verification failed in ajax_load_account');
                    wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                    return;
                }
            }
            */
            error_log('Jo_Exit: Loading account screen content');

            // Assicuriamoci che la classe Jo_Exit_User sia inclusa
            if (!class_exists('Jo_Exit_User')) {
                require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-jo-exit-user.php';
            }

            ob_start();
            include_once 'partials/jo-exit-account-content.php';
            $content = ob_get_clean();

            if (empty($content)) {
                error_log('Jo_Exit: Empty account content');
                throw new Exception('Empty account content');
            }

            error_log('Jo_Exit: Account content loaded successfully');

            wp_send_json_success(array(
                'content' => $content,
            ));
        } catch (Exception $e) {
            error_log('Jo_Exit: Exception in ajax_load_account: ' . $e->getMessage());
            wp_send_json_error(esc_html__('Error loading account screen. Please try again later.', 'job-exit-plugin'));
        }
    }

    /**
     * AJAX handler for loading the register screen content.
     *
     * @since    1.0.0
     */
    public function ajax_load_register() {
        // Disabilitiamo temporaneamente la verifica del nonce per debug
        /*
        if (isset($_POST['nonce'])) {
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_load_register');
                wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                return;
            }
        }
        */

        ob_start();
        include_once 'partials/jo-exit-register-content.php';
        $content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $content,
        ));
    }

    /**
     * AJAX handler for loading the edit profile screen content.
     *
     * @since    1.0.0
     */
    public function ajax_load_edit_profile() {
        // Disabilitiamo temporaneamente la verifica del nonce per debug
        /*
        if (isset($_POST['nonce'])) {
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_load_edit_profile');
                wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                return;
            }
        }
        */

        ob_start();
        include_once 'partials/jo-exit-edit-profile-content.php';
        $content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $content,
        ));
    }

    /**
     * AJAX handler for getting the player leaderboard.
     *
     * @since    1.0.0
     */
    public function ajax_get_player_leaderboard() {
        // Enable error logging
        error_log('Jo_Exit: ajax_get_player_leaderboard called');

        // Disabilitiamo temporaneamente la verifica del nonce per debug
        /*
        if (isset($_POST['nonce'])) {
            error_log('Jo_Exit: Nonce provided: ' . $_POST['nonce']);
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_get_player_leaderboard');
                wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                return;
            }
        } else {
            error_log('Jo_Exit: No nonce provided');
            wp_send_json_error(esc_html__('Nonce not provided', 'job-exit-plugin'));
            return;
        }
        */

        // Force update database structure
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php';
        Jo_Exit_Activator::update_database_structure();

        // Get exited employees with their top player scores
        $exited_employees = Jo_Exit_DB::get_exited_employees_with_scores();

        // Debug log
        error_log('Jo_Exit: Getting player leaderboard. Exited employees count: ' . count($exited_employees));
        if (count($exited_employees) > 0) {
            error_log('Jo_Exit: First exited employee: ' . print_r($exited_employees[0], true));
            if (isset($exited_employees[0]->top_scores)) {
                error_log('Jo_Exit: Top scores count: ' . count($exited_employees[0]->top_scores));
                if (count($exited_employees[0]->top_scores) > 0) {
                    error_log('Jo_Exit: First top score: ' . print_r($exited_employees[0]->top_scores[0], true));
                }
            } else {
                error_log('Jo_Exit: No top_scores property found');
            }
        }

        // Send response
        $response = array(
            'success' => true,
            'data' => array(
                'employees' => $exited_employees,
            ),
        );
        error_log('Jo_Exit: Sending response: ' . json_encode($response));

        wp_send_json($response);
    }

    /**
     * AJAX handler for getting the user leaderboard.
     *
     * @since    1.0.0
     */
    public function ajax_get_user_leaderboard() {
        // Enable error logging
        error_log('Jo_Exit: ajax_get_user_leaderboard called');
        error_log('Jo_Exit: POST data: ' . print_r($_POST, true));

        // Disabilitiamo temporaneamente la verifica del nonce per debug
        /*
        if (isset($_POST['nonce'])) {
            error_log('Jo_Exit: Nonce provided: ' . $_POST['nonce']);
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_get_user_leaderboard');
                wp_send_json_error('Controllo di sicurezza fallito');
                return;
            }
        } else {
            error_log('Jo_Exit: No nonce provided');
            wp_send_json_error('Nonce non fornito');
            return;
        }
        */

        // Force update database structure
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php';
        Jo_Exit_Activator::update_database_structure();

        // Get the limit parameter (default: 20)
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;

        // Check if player_scores table exists and has data
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");

        if (!$table_exists) {
            error_log('Jo_Exit: Player scores table does not exist');

            // Create the table
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php';
            Jo_Exit_Activator::update_database_structure();

            // Non aggiungiamo più record di test automaticamente
            error_log('Jo_Exit: Player scores table created');
        }

        // Get user leaderboard
        $user_leaderboard = Jo_Exit_DB::get_user_leaderboard($limit);

        // Debug log
        error_log('Jo_Exit: Getting user leaderboard. Users count: ' . count($user_leaderboard));
        if (count($user_leaderboard) > 0) {
            error_log('Jo_Exit: First user: ' . print_r($user_leaderboard[0], true));
        } else {
            error_log('Jo_Exit: No users found in leaderboard');

            // Non aggiungiamo più record di test automaticamente quando la classifica è vuota
            error_log('Jo_Exit: No users found in leaderboard, but we won\'t add test records anymore');
        }

        // Send response
        $response = array(
            'success' => true,
            'data' => array(
                'users' => $user_leaderboard,
            ),
        );
        error_log('Jo_Exit: Sending response: ' . json_encode($response));

        wp_send_json($response);
    }

    /**
     * AJAX handler for getting the current exit votes count.
     *
     * @since    1.0.0
     */
    public function ajax_get_exit_votes_count() {
        // Verify nonce
        if (isset($_POST['nonce'])) {
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce');
            if (!$nonce_verified) {
                error_log('Jo_Exit: Nonce verification failed in ajax_get_exit_votes_count');
                wp_send_json_error(esc_html__('Security check failed', 'job-exit-plugin'));
                return;
            }
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(esc_html__('User not logged in', 'job-exit-plugin'));
            return;
        }

        // Get user ID
        $user_id = get_current_user_id();

        // Get exit votes count
        $exit_votes_count = Jo_Exit_User::count_exit_votes($user_id);
        $max_exit_votes = Jo_Exit_User::get_max_exit_votes();

        error_log('Jo_Exit: User ' . $user_id . ' has ' . $exit_votes_count . ' exit votes out of ' . $max_exit_votes . ' allowed');

        // Send response
        wp_send_json_success(array(
            'count' => $exit_votes_count,
            'max' => $max_exit_votes
        ));
    }

    /**
     * Render the account screen.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function render_account($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'theme_color' => '#ff4757',
        ), $atts, 'jo_exit_account');

        ob_start();
        include_once 'partials/jo-exit-account.php';
        return ob_get_clean();
    }

    /**
     * Render the registration screen.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function render_register($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'theme_color' => '#ff4757',
        ), $atts, 'jo_exit_register');

        ob_start();
        include_once 'partials/jo-exit-register.php';
        return ob_get_clean();
    }

    /**
     * Render the edit profile screen.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function render_edit_profile($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'theme_color' => '#ff4757',
        ), $atts, 'jo_exit_edit_profile');

        ob_start();
        include_once 'partials/jo-exit-edit-profile.php';
        return ob_get_clean();
    }

    /**
     * Render the global leaderboard screen.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function render_global_leaderboard($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'theme_color' => '#ff4757',
        ), $atts, 'jo_exit_global_leaderboard');

        ob_start();
        include_once 'partials/jo-exit-global-leaderboard.php';
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting unread notifications.
     *
     * @since    1.0.0
     */
    public function ajax_get_unread_notifications() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to view notifications.', 'job-exit-plugin')));
            return;
        }

        // Get unread notifications for the current user
        $user_id = get_current_user_id();
        $notifications = Jo_Exit_Notifications::get_unread_notifications($user_id);

        // Non sanitizziamo il contenuto per evitare problemi con i caratteri speciali

        wp_send_json_success(array('notifications' => $notifications));
    }

    /**
     * AJAX handler for marking a notification as read.
     *
     * @since    1.0.0
     */
    public function ajax_mark_notification_read() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to mark notifications as read.', 'job-exit-plugin')));
            return;
        }

        // Check if notification ID is provided
        if (!isset($_POST['notification_id'])) {
            wp_send_json_error(array('message' => esc_html__('Notification ID is required.', 'job-exit-plugin')));
            return;
        }

        $notification_id = intval($_POST['notification_id']);
        $user_id = get_current_user_id();

        // Mark the notification as read
        $result = Jo_Exit_Notifications::mark_notification_as_read($notification_id, $user_id);

        if (!$result) {
            wp_send_json_error(array('message' => esc_html__('Failed to mark notification as read.', 'job-exit-plugin')));
            return;
        }

        wp_send_json_success(array('message' => esc_html__('Notification marked as read.', 'job-exit-plugin')));
    }

    /**
     * AJAX handler for getting a notification's content.
     *
     * @since    1.0.0
     */
    public function ajax_get_notification_content() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to view notification content.', 'job-exit-plugin')));
            return;
        }

        // Check if notification ID is provided
        if (!isset($_POST['notification_id'])) {
            wp_send_json_error(array('message' => esc_html__('Notification ID is required.', 'job-exit-plugin')));
            return;
        }

        ob_start();
        include_once 'partials/jo-exit-notification-content.php';
        $content = ob_get_clean();

        wp_send_json_success(array('content' => $content));
    }

    /**
     * AJAX handler for getting the count of unread notifications.
     *
     * @since    1.0.0
     */
    public function ajax_get_unread_count() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to get unread count.', 'job-exit-plugin')));
            return;
        }

        $user_id = get_current_user_id();

        // Get the count of unread notifications
        $count = Jo_Exit_Notifications::get_unread_count($user_id);

        wp_send_json_success(array('count' => $count));
    }

    /**
     * AJAX handler for loading the changelog.
     *
     * @since    1.0.0
     */
    public function ajax_load_changelog() {
        ob_start();
        include_once 'partials/jo-exit-changelog-content.php';
        $content = ob_get_clean();

        wp_send_json_success(array('content' => $content));
    }


}
