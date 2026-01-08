<?php
/**
 * User management functionality for the JO Exit plugin.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/public
 */

/**
 * User management functionality for the JO Exit plugin.
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/public
 * @author     Your Name <email@example.com>
 */
class Jo_Exit_User {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_jo_exit_logout_user', array($this, 'ajax_logout_user'));
        add_action('wp_ajax_jo_exit_update_profile', array($this, 'ajax_update_profile'));
        add_action('wp_ajax_jo_exit_get_user_voting_data', array($this, 'ajax_get_user_voting_data'));
        add_action('wp_ajax_jo_exit_upload_avatar', array($this, 'ajax_upload_avatar'));
        add_action('wp_ajax_jo_exit_check_cooldown', array($this, 'ajax_check_cooldown'));
        add_action('wp_ajax_jo_exit_set_cooldown', array($this, 'ajax_set_cooldown'));
        add_action('wp_ajax_jo_exit_get_exit_votes_count', array($this, 'ajax_get_exit_votes_count'));
        add_action('wp_ajax_jo_exit_delete_vote', array($this, 'ajax_delete_vote'));

        add_action('wp_ajax_nopriv_jo_exit_login_user', array($this, 'ajax_login_user'));
        add_action('wp_ajax_nopriv_jo_exit_register_user', array($this, 'ajax_register_user'));
    }

    /**
     * AJAX handler for user login.
     *
     * @since    1.0.0
     */
    public function ajax_login_user() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'job-exit-plugin')));
        }

        // Get login credentials
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        // Validate input
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => esc_html__('Username and password are required.', 'job-exit-plugin')));
        }

        // Attempt to log in
        $credentials = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        );

        $user = wp_signon($credentials, false);

        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => esc_html__('Invalid username or password.', 'job-exit-plugin')));
        } else {
            wp_send_json_success(array('message' => esc_html__('Login successful.', 'job-exit-plugin')));
        }
    }

    /**
     * AJAX handler for user registration.
     *
     * @since    1.0.0
     */
    public function ajax_register_user() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'job-exit-plugin')));
        }

        // Get registration data
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $avatar = isset($_POST['avatar']) ? $_POST['avatar'] : '';

        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => esc_html__('All fields are required.', 'job-exit-plugin')));
        }

        // Check if username already exists
        if (username_exists($username)) {
            wp_send_json_error(array('message' => esc_html__('Username already in use.', 'job-exit-plugin')));
        }

        // Check if email already exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => esc_html__('Email already in use.', 'job-exit-plugin')));
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        } else {
            // Set user role
            $user = new WP_User($user_id);
            $user->set_role('subscriber');

            // Save avatar if provided
            if (!empty($avatar)) {
                // Check if it's a base64 image
                if (strpos($avatar, 'data:image/') === 0) {
                    // It's a base64 image, store it directly
                    update_user_meta($user_id, 'jo_exit_avatar', $avatar);
                } else {
                    // It's a URL, store it as is
                    update_user_meta($user_id, 'jo_exit_avatar', esc_url_raw($avatar));
                }
            }

            // Log the user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);

            wp_send_json_success(array('message' => esc_html__('Registration completed successfully.', 'job-exit-plugin')));
        }
    }

    /**
     * AJAX handler for user logout.
     *
     * @since    1.0.0
     */
    public function ajax_logout_user() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'job-exit-plugin')));
        }

        // Log the user out
        wp_logout();

        wp_send_json_success(array('message' => esc_html__('Logout successful.', 'job-exit-plugin')));
    }

    /**
     * AJAX handler for updating user profile.
     *
     * @since    1.0.0
     */
    public function ajax_update_profile() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'job-exit-plugin')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to update your profile.', 'job-exit-plugin')));
        }

        // Get current user
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        // Get profile data
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $avatar = isset($_POST['avatar']) ? $_POST['avatar'] : '';

        // Validate email
        if (empty($email)) {
            wp_send_json_error(array('message' => esc_html__('Email is required.', 'job-exit-plugin')));
        }

        // Check if email is already in use by another user
        if ($email !== $user->user_email && email_exists($email) && email_exists($email) !== $user_id) {
            wp_send_json_error(array('message' => esc_html__('Email already in use by another user.', 'job-exit-plugin')));
        }

        // Update user data
        $userdata = array(
            'ID' => $user_id,
            'user_email' => $email,
        );

        // Update password if provided
        if (!empty($password)) {
            $userdata['user_pass'] = $password;
        }

        // Update user
        $result = wp_update_user($userdata);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            // Update avatar if provided
            if (!empty($avatar)) {
                // Check if it's a base64 image
                if (strpos($avatar, 'data:image/') === 0) {
                    // It's a base64 image, store it directly
                    update_user_meta($user_id, 'jo_exit_avatar', $avatar);
                } else {
                    // It's a URL, store it as is
                    update_user_meta($user_id, 'jo_exit_avatar', esc_url_raw($avatar));
                }
            }

            wp_send_json_success(array('message' => esc_html__('Profile updated successfully.', 'job-exit-plugin')));
        }
    }

    /**
     * AJAX handler for getting user voting data.
     *
     * @since    1.0.0
     */
    public function ajax_get_user_voting_data() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed.', 'job-exit-plugin')));
                return;
            }

            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => esc_html__('You must be logged in to view voting data.', 'job-exit-plugin')));
                return;
            }

            // Get current user
            $user_id = get_current_user_id();
            error_log('Jo_Exit: Getting voting data for user ID: ' . $user_id);

            // SIMPLIFIED APPROACH: Read votes directly from user meta data
            // Questo è più affidabile perché contiene i punti corretti
            $user_votes = get_user_meta($user_id, 'jo_exit_votes', true);
            if (!is_array($user_votes)) {
                $user_votes = array();
            }
            error_log('Jo_Exit: Found ' . count($user_votes) . ' votes in user meta for user ID: ' . $user_id);

            // Calculate total points
            $total_points = 0;
            $voted_employees = array();

            // Process votes from user meta data
            foreach ($user_votes as $employee_id => $vote_data) {
                // Get employee data
                $employee = Jo_Exit_DB::get_employee($employee_id);

                if (!$employee) {
                    error_log('Jo_Exit: Employee ID ' . $employee_id . ' not found');
                    continue;
                }

                // Include only employees that are not in 'exit' status
                if ($employee->status !== 'exit') {
                    // Get points and vote type
                    $points = isset($vote_data['points']) ? intval($vote_data['points']) : 0;
                    $vote_type = isset($vote_data['vote']) ? $vote_data['vote'] : 'unknown';

                    error_log('Jo_Exit: Processing vote for employee ID ' . $employee_id . ' with vote_type ' . $vote_type . ' and points ' . $points);

                    // Include only 'exit' votes
                    if ($vote_type === 'exit') {
                        $total_points += $points;

                        $voted_employees[] = array(
                            'id' => $employee_id,
                            'name' => $employee->first_name . ' ' . $employee->last_name,
                            'photo_url' => $employee->photo_url,
                            'points' => $points,
                            'vote_type' => $vote_type,
                        );

                        error_log('Jo_Exit: Added employee ID ' . $employee_id . ' to voted_employees with points ' . $points . ' and vote_type ' . $vote_type);
                    }
                } else {
                    error_log('Jo_Exit: Employee ID ' . $employee_id . ' is in exit status, skipping');
                }
            }

            error_log('Jo_Exit: Found ' . count($voted_employees) . ' active voted employees for user ID: ' . $user_id);

            // Sort voted employees by points (descending)
            usort($voted_employees, function($a, $b) {
                return $b['points'] - $a['points'];
            });

            wp_send_json_success(array(
                'total_points' => $total_points,
                'voted_employees' => $voted_employees,
            ));
        } catch (Exception $e) {
            error_log('Jo_Exit: Exception in ajax_get_user_voting_data: ' . $e->getMessage());
            wp_send_json_error(array('message' => esc_html__('An error occurred while loading voting data.', 'job-exit-plugin')));
        }
    }

    /**
     * AJAX handler for uploading avatar.
     *
     * @since    1.0.0
     */
    public function ajax_upload_avatar() {
        // This function is no longer used as we're using base64 encoding directly in JavaScript
        wp_send_json_error(array('message' => esc_html__('This method is no longer supported. Use direct upload via base64.', 'job-exit-plugin')));
    }

    /**
     * AJAX handler for deleting user account.
     *
     * @since    1.0.0
     */
    public function ajax_delete_account() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'job-exit-plugin')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to delete your account.', 'job-exit-plugin')));
        }

        // Get current user
        $user_id = get_current_user_id();

        // Check if user is an administrator
        if (current_user_can('administrator')) {
            wp_send_json_error(array('message' => esc_html__('Administrators cannot delete their account from this interface.', 'job-exit-plugin')));
        }

        // Delete user votes
        delete_user_meta($user_id, 'jo_exit_votes');

        // Delete user avatar
        delete_user_meta($user_id, 'jo_exit_avatar');

        // Delete the user
        $result = wp_delete_user($user_id);

        if ($result) {
            wp_send_json_success(array('message' => esc_html__('Account deleted successfully.', 'job-exit-plugin')));
        } else {
            wp_send_json_error(array('message' => esc_html__('An error occurred while deleting the account.', 'job-exit-plugin')));
        }
    }

    /**
     * Calculate user experience points.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   int                The total experience points.
     */
    public static function calculate_user_exp($user_id) {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        // Verifica se la tabella esiste
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
        if (!$table_exists) {
            error_log('Jo_Exit: Player scores table does not exist');
            return 0;
        }

        // Ottieni tutti i punteggi player per questo utente
        $query = $wpdb->prepare(
            "SELECT SUM(player_points) as total_exp
             FROM $player_scores_table
             WHERE user_id = %d",
            $user_id
        );

        $result = $wpdb->get_var($query);

        // Se non ci sono risultati, restituisci 0
        if ($result === null) {
            return 0;
        }

        return intval($result);
    }

    /**
     * Count active 'exit' votes for a user.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   int                The number of active 'exit' votes.
     */
    public static function count_exit_votes($user_id) {
        // Get user votes
        $votes = get_user_meta($user_id, 'jo_exit_votes', true);

        error_log('Jo_Exit_User: Counting exit votes for user ' . $user_id);
        error_log('Jo_Exit_User: User votes: ' . print_r($votes, true));

        if (empty($votes)) {
            error_log('Jo_Exit_User: No votes found, returning 0');
            return 0;
        }

        // Count only active 'exit' votes
        $count = 0;
        foreach ($votes as $employee_id => $vote_data) {
            error_log('Jo_Exit_User: Checking vote for employee ' . $employee_id . ': ' . print_r($vote_data, true));

            if (isset($vote_data['vote']) && $vote_data['vote'] === 'exit') {
                // Check if the vote has points > 0
                $points = isset($vote_data['points']) ? intval($vote_data['points']) : 0;

                if ($points > 0) {
                    error_log('Jo_Exit_User: Found exit vote with ' . $points . ' points for employee ' . $employee_id);

                    // Get employee data to check if it's still active
                    $employee = Jo_Exit_DB::get_employee($employee_id);
                    if ($employee && $employee->status === 'active') {
                        $count++;
                        error_log('Jo_Exit_User: Employee ' . $employee_id . ' is active, incrementing count to ' . $count);
                    } else {
                        error_log('Jo_Exit_User: Employee ' . $employee_id . ' is not active or not found');
                    }
                } else {
                    error_log('Jo_Exit_User: Skipping exit vote for employee ' . $employee_id . ' because it has 0 points');
                }
            } else {
                error_log('Jo_Exit_User: Not an exit vote for employee ' . $employee_id);
            }
        }

        error_log('Jo_Exit_User: Final exit votes count for user ' . $user_id . ': ' . $count);
        return $count;
    }

    /**
     * Get the maximum number of allowed 'exit' votes.
     *
     * @since    1.0.0
     * @return   int    The maximum number of allowed 'exit' votes.
     */
    public static function get_max_exit_votes() {
        return 5; // Maximum 5 'exit' votes allowed
    }

    /**
     * Store user vote in user meta.
     *
     * @since    1.0.0
     * @param    int    $employee_id    The ID of the employee.
     * @param    string $vote_type      The type of vote ('exit' or 'nope').
     * @return   array                  Result array with 'success' (bool) and 'message' (string).
     */
    public static function store_user_vote($employee_id, $vote_type) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return array('success' => false, 'message' => 'User not logged in');
        }

        // Get current user
        $user_id = get_current_user_id();

        // Get user votes
        $votes = get_user_meta($user_id, 'jo_exit_votes', true);

        if (empty($votes)) {
            $votes = array();
        }

        // Check if this is a new 'exit' vote or changing from 'nope' to 'exit'
        $is_new_exit_vote = false;
        if ($vote_type === 'exit') {
            if (!isset($votes[$employee_id]) || $votes[$employee_id]['vote'] !== 'exit') {
                $is_new_exit_vote = true;
            }
        }

        // If this is a new 'exit' vote, check if we've reached the limit
        if ($is_new_exit_vote && $vote_type === 'exit') {
            $current_exit_votes = self::count_exit_votes($user_id);
            $max_exit_votes = self::get_max_exit_votes();

            error_log('Jo_Exit_User: User ' . $user_id . ' has ' . $current_exit_votes . ' exit votes, max allowed: ' . $max_exit_votes);

            if ($current_exit_votes >= $max_exit_votes) {
                // We've reached the limit, need to remove the oldest 'exit' vote
                error_log('Jo_Exit_User: User has reached the maximum number of exit votes, removing oldest vote');

                // Find the oldest 'exit' vote
                $oldest_timestamp = PHP_INT_MAX;
                $oldest_employee_id = null;

                foreach ($votes as $emp_id => $vote_data) {
                    if (isset($vote_data['vote']) && $vote_data['vote'] === 'exit' && isset($vote_data['timestamp'])) {
                        // Skip the current employee if it's already in the list
                        if ($emp_id == $employee_id) {
                            continue;
                        }

                        // Get employee data to check if it's still active
                        $employee = Jo_Exit_DB::get_employee($emp_id);
                        if ($employee && $employee->status === 'active' && $vote_data['timestamp'] < $oldest_timestamp) {
                            $oldest_timestamp = $vote_data['timestamp'];
                            $oldest_employee_id = $emp_id;
                        }
                    }
                }

                // Remove the oldest vote
                if ($oldest_employee_id) {
                    error_log('Jo_Exit_User: Removing oldest exit vote for employee ' . $oldest_employee_id);
                    unset($votes[$oldest_employee_id]);

                    // Also remove the vote from the database
                    Jo_Exit_DB::remove_vote($oldest_employee_id, $user_id);
                }
            }
        }

        // SIMPLIFIED APPROACH
        error_log('Jo_Exit_User: APPROCCIO SEMPLIFICATO - Storing vote for employee ' . $employee_id . ' with type ' . $vote_type);

        // Check if a vote already exists for this employee
        $existing_vote = isset($votes[$employee_id]['vote']) ? $votes[$employee_id]['vote'] : null;
        $existing_points = isset($votes[$employee_id]['points']) ? intval($votes[$employee_id]['points']) : 0;

        // Flag to indicate if we're incrementing an existing vote
        $is_increment = false;

        error_log('Jo_Exit_User: Existing vote: ' . $existing_vote . ', Existing points: ' . $existing_points);

        // Handle vote based on type
        if ($vote_type === 'exit') {
            if ($existing_vote === 'exit') {
                // If already voted exit, increment points
                $new_points = $existing_points + 1;
                $votes[$employee_id] = array(
                    'vote' => 'exit',
                    'points' => $new_points,
                    'timestamp' => time(),
                    'incremented' => true // Add a flag to indicate it was manually incremented
                );
                // Set increment flag
                $is_increment = true;
                error_log('Jo_Exit_User: VOTO EXIT SUCCESSIVO - Incrementing points from ' . $existing_points . ' to ' . $new_points);
            } else {
                // If not voted or voted nope, set to 1
                $votes[$employee_id] = array(
                    'vote' => 'exit',
                    'points' => 1, // 1 point for the first exit vote
                    'timestamp' => time()
                );
                error_log('Jo_Exit_User: PRIMO VOTO EXIT - Setting 1 point');
            }
        } else { // vote_type === 'nope'
            // Check if this was previously an 'exit' vote
            $was_exit_vote = isset($votes[$employee_id]['vote']) && $votes[$employee_id]['vote'] === 'exit';

            // If nope vote, reset completely
            $votes[$employee_id] = array(
                'vote' => 'nope',
                'points' => 0,
                'timestamp' => time()
            );
            error_log('Jo_Exit_User: VOTO NOPE - Resetting points to 0');

            // If this was previously an 'exit' vote, decrement the session votes counter
            if ($was_exit_vote) {
                // Get current session votes
                $session_votes = self::get_session_votes($user_id);

                // Decrement the count, but don't go below 0
                if ($session_votes > 0) {
                    $session_votes--;
                    update_user_meta($user_id, 'jo_exit_session_votes', $session_votes);
                    error_log('Jo_Exit_User: Decremented session votes for user ' . $user_id . ' to ' . $session_votes . ' after changing vote from exit to nope');
                }
            }
        }

        // Final log for debugging
        error_log('Jo_Exit_User: Final vote for employee ' . $employee_id . ': ' . $votes[$employee_id]['vote'] . ' with ' . $votes[$employee_id]['points'] . ' points');

        // Add or update vote in user meta
        update_user_meta($user_id, 'jo_exit_votes', $votes);

        // If this is an 'exit' vote, handle session votes counter
        if ($vote_type === 'exit') {
            // Only increment session votes counter for new exit votes or when incrementing an existing exit vote
            if ($is_new_exit_vote || $is_increment) {
                // Increment session votes counter
                $session_votes = self::increment_session_votes($user_id);
                error_log('Jo_Exit_User: Incremented session votes counter to ' . $session_votes . ' (new vote: ' . ($is_new_exit_vote ? 'yes' : 'no') . ', increment: ' . ($is_increment ? 'yes' : 'no') . ')');

                // We no longer set cooldown when reaching the session limit
                // Just log that we've reached the limit
                if ($session_votes >= self::get_max_exit_votes()) {
                    error_log('Jo_Exit_User: User has reached the maximum number of session votes, but NOT setting cooldown');
                }
            } else {
                // Get current session votes without incrementing
                $session_votes = self::get_session_votes($user_id);
                error_log('Jo_Exit_User: Current session votes: ' . $session_votes . ' (not incremented)');
            }

            // Return session votes count with the response
            return array('success' => true, 'message' => 'Vote stored', 'session_votes' => $session_votes);
        }

        // We don't need to update user meta again since we already did it above

        return array('success' => true, 'message' => 'Vote stored');
    }

    /**
     * Set the last vote timestamp for a user.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   bool               True on success, false on failure.
     */
    public static function set_last_vote_timestamp($user_id) {
        // Set the current timestamp
        $timestamp = time();
        error_log('Jo_Exit_User: Setting last vote timestamp for user ' . $user_id . ' to ' . $timestamp);

        // Update user meta
        $result = update_user_meta($user_id, 'jo_exit_last_vote_timestamp', $timestamp);
        error_log('Jo_Exit_User: Update result: ' . ($result ? 'success' : 'failed'));

        return $result;
    }

    /**
     * Get the last vote timestamp for a user.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   int                The timestamp of the last vote, or 0 if not set.
     */
    public static function get_last_vote_timestamp($user_id) {
        // Get the timestamp from user meta
        $timestamp = get_user_meta($user_id, 'jo_exit_last_vote_timestamp', true);
        error_log('Jo_Exit_User: Getting last vote timestamp for user ' . $user_id . ': ' . ($timestamp ? $timestamp : 'not set'));

        // Return the timestamp or 0 if not set
        return empty($timestamp) ? 0 : intval($timestamp);
    }

    /**
     * Check if a user is in cooldown period.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   array              Array with 'in_cooldown' (bool) and 'remaining_time' (int) in seconds.
     */
    public static function check_cooldown($user_id) {
        // Get the last vote timestamp
        $last_vote_timestamp = self::get_last_vote_timestamp($user_id);
        error_log('Jo_Exit_User: Last vote timestamp for user ' . $user_id . ': ' . $last_vote_timestamp);

        // If no timestamp, user is not in cooldown
        if (empty($last_vote_timestamp)) {
            error_log('Jo_Exit_User: No timestamp found, user is not in cooldown');
            return array(
                'in_cooldown' => false,
                'remaining_time' => 0
            );
        }

        // Calculate time elapsed since last vote
        $now = time();
        $time_elapsed = $now - $last_vote_timestamp;
        error_log('Jo_Exit_User: Current time: ' . $now . ', time elapsed: ' . $time_elapsed . ' seconds');

        // Cooldown period is 8 hours (28800 seconds)
        $cooldown_period = 28800; // 8 hours = 8 * 60 * 60 = 28800 seconds
        error_log('Jo_Exit_User: Cooldown period: ' . $cooldown_period . ' seconds');

        // Check if user is still in cooldown
        if ($time_elapsed < $cooldown_period) {
            // User is in cooldown, calculate remaining time
            $remaining_time = $cooldown_period - $time_elapsed;
            error_log('Jo_Exit_User: User is in cooldown, remaining time: ' . $remaining_time . ' seconds');

            return array(
                'in_cooldown' => true,
                'remaining_time' => $remaining_time
            );
        }

        // User is not in cooldown - we no longer reset session votes counter
        error_log('Jo_Exit_User: User is not in cooldown, cooldown period has passed - NOT resetting session votes counter');

        return array(
            'in_cooldown' => false,
            'remaining_time' => 0
        );
    }

    /**
     * AJAX handler for checking user cooldown.
     *
     * @since    1.0.0
     */
    public function ajax_check_cooldown() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            error_log('Jo_Exit_User: User not logged in, cannot check cooldown');
            wp_send_json_error(array('message' => esc_html__('You must be logged in to check cooldown.', 'job-exit-plugin')));
            return;
        }

        // Get current user ID
        $user_id = get_current_user_id();
        error_log('Jo_Exit_User: Checking cooldown for user ' . $user_id);

        // Check cooldown status
        $cooldown = self::check_cooldown($user_id);
        error_log('Jo_Exit_User: Cooldown check result - in_cooldown: ' . ($cooldown['in_cooldown'] ? 'true' : 'false') . ', remaining_time: ' . $cooldown['remaining_time']);

        // We no longer reset session votes counter when not in cooldown
        // This allows the counter to persist between sessions
        error_log('Jo_Exit_User: Not resetting session votes counter in ajax_check_cooldown');

        // Send response
        wp_send_json_success(array(
            'in_cooldown' => $cooldown['in_cooldown'],
            'remaining_time' => $cooldown['remaining_time'],
            'formatted_time' => self::format_time($cooldown['remaining_time']),
            'session_votes' => self::get_session_votes($user_id)
        ));
    }

    /**
     * Format time in seconds to HH:MM:SS format.
     *
     * @since    1.0.0
     * @param    int    $seconds    Time in seconds.
     * @return   string             Formatted time string.
     */
    public static function format_time($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remaining_seconds = $seconds % 60;

        if ($hours > 0) {
            // Format with hours (HH:MM:SS)
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining_seconds);
        } else {
            // Format without hours (MM:SS)
            return sprintf('%02d:%02d', $minutes, $remaining_seconds);
        }
    }

    /**
     * AJAX handler for setting user cooldown.
     *
     * @since    1.0.0
     */
    public function ajax_set_cooldown() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to set cooldown.', 'job-exit-plugin')));
            return;
        }

        // Get current user ID
        $user_id = get_current_user_id();

        // Set the cooldown timestamp
        $result = self::set_last_vote_timestamp($user_id);

        // Reset session votes counter
        self::reset_session_votes($user_id);

        // Log the action
        error_log('Jo_Exit_User: Setting cooldown for user ' . $user_id . ', result: ' . ($result ? 'success' : 'failed') . ' and reset session votes counter');

        // Send response
        if ($result) {
            wp_send_json_success(array(
                'message' => esc_html__('Cooldown set successfully.', 'job-exit-plugin')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Error setting cooldown.', 'job-exit-plugin')
            ));
        }
    }

    /**
     * Get all users with their cooldown status.
     *
     * @since    1.0.0
     * @return   array    Array of users with cooldown status.
     */
    public static function get_users_cooldown_status() {
        // Get all users
        $users = get_users(array(
            'fields' => array('ID', 'user_login', 'display_name')
        ));

        $users_cooldown = array();

        foreach ($users as $user) {
            // Get cooldown status for each user
            $cooldown = self::check_cooldown($user->ID);

            // Add user to the array with cooldown status
            $users_cooldown[] = array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'in_cooldown' => $cooldown['in_cooldown'],
                'remaining_time' => $cooldown['remaining_time'],
                'formatted_time' => self::format_time($cooldown['remaining_time']),
                'last_vote_timestamp' => self::get_last_vote_timestamp($user->ID)
            );
        }

        return $users_cooldown;
    }

    /**
     * Reset cooldown for a user.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   bool               True on success, false on failure.
     */
    public static function reset_cooldown($user_id) {
        // Delete the last vote timestamp
        return delete_user_meta($user_id, 'jo_exit_last_vote_timestamp');
    }

    /**
     * Reset cooldown for all users.
     *
     * @since    1.0.0
     * @return   int    Number of users affected.
     */
    public static function reset_all_cooldowns() {
        global $wpdb;

        // Delete all last vote timestamps
        $result = $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => 'jo_exit_last_vote_timestamp')
        );

        return $result;
    }

    /**
     * Get session votes count for a user.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   int                The number of votes in the current session.
     */
    public static function get_session_votes($user_id) {
        // Get session votes from user meta
        $session_votes = get_user_meta($user_id, 'jo_exit_session_votes', true);

        error_log('Jo_Exit_User: Getting session votes for user ' . $user_id . ': ' . ($session_votes ? $session_votes : '0'));

        // Return the count or 0 if not set
        return empty($session_votes) ? 0 : intval($session_votes);
    }

    /**
     * Increment session votes count for a user.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   int                The new session votes count.
     */
    public static function increment_session_votes($user_id) {
        // Get current session votes
        $session_votes = self::get_session_votes($user_id);

        // Increment the count
        $session_votes++;

        // Update user meta
        update_user_meta($user_id, 'jo_exit_session_votes', $session_votes);

        error_log('Jo_Exit_User: Incremented session votes for user ' . $user_id . ' to ' . $session_votes);

        return $session_votes;
    }

    /**
     * Reset session votes count for a user.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   bool               True on success, false on failure.
     */
    public static function reset_session_votes($user_id) {
        // Delete the session votes count
        $result = delete_user_meta($user_id, 'jo_exit_session_votes');

        error_log('Jo_Exit_User: Reset session votes for user ' . $user_id . ', result: ' . ($result ? 'success' : 'failed'));

        return $result;
    }

    /**
     * AJAX handler for deleting a vote.
     *
     * @since    1.0.0
     */
    public function ajax_delete_vote() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'jo-exit')));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to delete a vote.', 'jo-exit')));
            return;
        }

        // Get employee ID
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        if ($employee_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid employee ID.', 'jo-exit')));
            return;
        }

        // Get current user ID
        $user_id = get_current_user_id();

        // Get user votes
        $votes = get_user_meta($user_id, 'jo_exit_votes', true);
        if (empty($votes)) {
            $votes = array();
        }

        // Check if the user has voted for this employee
        if (!isset($votes[$employee_id])) {
            wp_send_json_error(array('message' => __('You have not voted for this employee.', 'jo-exit')));
            return;
        }

        // Check if this was an 'exit' vote and get the points
        $was_exit_vote = isset($votes[$employee_id]['vote']) && $votes[$employee_id]['vote'] === 'exit';
        $points_to_remove = isset($votes[$employee_id]['points']) ? intval($votes[$employee_id]['points']) : 0;

        error_log('Jo_Exit_User: Deleting vote for employee ID ' . $employee_id . ' with ' . $points_to_remove . ' points');

        // If this was an 'exit' vote, update the employee's score in the database
        if ($was_exit_vote && $points_to_remove > 0) {
            global $wpdb;
            $employees_table = $wpdb->prefix . 'jo_exit_employees';

            // Get the current score for this employee
            $current_score = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT score FROM $employees_table WHERE id = %d",
                    $employee_id
                )
            );

            error_log('Jo_Exit_User: Current employee score: ' . $current_score);

            // Calculate new score
            $new_score = max(0, intval($current_score) - $points_to_remove);
            error_log('Jo_Exit_User: New score will be: ' . $new_score . ' after removing ' . $points_to_remove . ' points');

            // Update the score
            $score_result = $wpdb->update(
                $employees_table,
                array('score' => $new_score),
                array('id' => $employee_id)
            );

            if ($score_result === false) {
                error_log('Jo_Exit_User: Failed to update employee score: ' . $wpdb->last_error);
            } else {
                error_log('Jo_Exit_User: Successfully updated employee score from ' . $current_score . ' to ' . $new_score);
            }
        }

        // Remove the vote from user meta
        unset($votes[$employee_id]);

        // Update user meta
        update_user_meta($user_id, 'jo_exit_votes', $votes);

        // If this was an 'exit' vote, decrement the session votes counter
        if ($was_exit_vote) {
            // Get current session votes
            $session_votes = self::get_session_votes($user_id);

            // Decrement the count, but don't go below 0
            if ($session_votes > 0) {
                $session_votes--;
                update_user_meta($user_id, 'jo_exit_session_votes', $session_votes);
                error_log('Jo_Exit_User: Decremented session votes for user ' . $user_id . ' to ' . $session_votes . ' after deleting exit vote');
            }
        }

        // Send success response
        wp_send_json_success(array(
            'message' => __('Vote deleted successfully.', 'jo-exit'),
            'employee_id' => $employee_id
        ));
    }

    /**
     * AJAX handler for getting exit votes count.
     *
     * @since    1.0.0
     */
    public function ajax_get_exit_votes_count() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jo_exit_public_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'job-exit-plugin')));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to get vote count.', 'job-exit-plugin')));
            return;
        }

        // Get current user ID
        $user_id = get_current_user_id();

        // We no longer call fix_user_votes because it interferes with the correct point counting
        // Read votes directly from user database

        // Get exit votes count
        $count = self::count_exit_votes($user_id);
        $max = self::get_max_exit_votes();

        // Get session votes count
        $session_votes = self::get_session_votes($user_id);
        error_log('Jo_Exit_User: User ' . $user_id . ' has ' . $session_votes . ' session votes');

        // Send response
        wp_send_json_success(array(
            'count' => $count,
            'max' => $max,
            'session_votes' => $session_votes
        ));
    }

    /**
     * Fix user votes to ensure exit votes have EXACTLY the correct points.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the user.
     * @return   bool               True if votes were fixed, false otherwise.
     */
    public static function fix_user_votes($user_id) {
        // Get user votes
        $votes = get_user_meta($user_id, 'jo_exit_votes', true);

        if (empty($votes)) {
            return false;
        }

        $fixed = false;

        // Get votes from database for comparison
        global $wpdb;
        $votes_table = $wpdb->prefix . 'jo_exit_votes';
        $db_votes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT employee_id, vote_type FROM $votes_table WHERE user_identifier = %s",
                $user_id
            ),
            OBJECT_K
        );

        error_log('Jo_Exit_User: fix_user_votes - Found ' . count($db_votes) . ' votes in database for user ' . $user_id);

        // Fix only votes that have issues
        foreach ($votes as $employee_id => $vote_data) {
            if (isset($vote_data['vote']) && $vote_data['vote'] === 'exit') {
                // Check if a vote exists in the database for this employee
                $db_vote_exists = isset($db_votes[$employee_id]) && $db_votes[$employee_id]->vote_type === 'exit';

                // Make sure exit votes have at least 1 point
                if (!isset($vote_data['points']) || $vote_data['points'] < 1) {
                    $votes[$employee_id]['points'] = 1;
                    $fixed = true;
                    error_log('Jo_Exit_User: CORREZIONE - Employee ID ' . $employee_id . ' points corrected to 1 because it was less than 1');
                }
                // Fix the double counting points problem
                // If it's the first exit vote, make sure it has exactly 1 point
                // Note: we no longer reset points to 1 if they are greater than 1 and don't have the 'incremented' flag
                // This is because they could be legitimate votes incremented before the flag was introduced

                // Add a flag to indicate this vote has been checked
                $votes[$employee_id]['checked'] = true;
            }
        }

        // Check if there are votes in the database that are not in user meta
        foreach ($db_votes as $employee_id => $db_vote) {
            if ($db_vote->vote_type === 'exit' && !isset($votes[$employee_id])) {
                // Add the missing vote
                $votes[$employee_id] = array(
                    'vote' => 'exit',
                    'points' => 1,
                    'timestamp' => time(),
                    'checked' => true
                );
                $fixed = true;
                error_log('Jo_Exit_User: CORREZIONE - Added missing vote for Employee ID ' . $employee_id);
            }
        }

        // Update user meta if votes were fixed
        if ($fixed) {
            update_user_meta($user_id, 'jo_exit_votes', $votes);
            error_log('Jo_Exit_User: RESET COMPLETO - Fixed votes for user ' . $user_id);
        }

        return $fixed;
    }

    /**
     * Fix all user votes to ensure exit votes have EXACTLY the correct points.
     *
     * @since    1.0.0
     * @return   int    Number of users whose votes were fixed.
     */
    public static function fix_all_user_votes() {
        // Get all users
        $users = get_users(array(
            'fields' => array('ID')
        ));

        $fixed_count = 0;

        foreach ($users as $user) {
            $fixed = self::fix_user_votes($user->ID);
            if ($fixed) {
                $fixed_count++;
            }
        }

        error_log('Jo_Exit_User: Fixed votes for ' . $fixed_count . ' users out of ' . count($users));

        return $fixed_count;
    }
}
