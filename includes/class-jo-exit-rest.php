<?php
/**
 * REST API Controller for the JO Exit plugin.
 */
class Jo_Exit_Rest extends WP_REST_Controller
{

    protected $namespace = 'jo-exit/v1';

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/employees', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_employees'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/leaderboard', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_leaderboard'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/exited', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_exited'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/vote', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'vote'),
                'permission_callback' => array($this, 'get_permissions_check'),
                'args' => array(
                    'employee_id' => array(
                        'required' => true,
                        'validate_callback' => function ($param, $request, $key) {
                            return is_numeric($param);
                        }
                    ),
                    'vote_type' => array(
                        'required' => true,
                        'validate_callback' => function ($param, $request, $key) {
                            return in_array($param, array('exit', 'nope'));
                        }
                    ),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/init', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_initial_data'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/cooldown', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'set_cooldown'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));
    }

    /**
     * Check if a given request has access to read items.
     */
    public function get_permissions_check($request)
    {
        return true; // Public access for now, but sensitive operations like voting check login inside
    }

    /**
     * Get initial data (combined request for speed)
     */
    public function get_initial_data($request)
    {
        $user_id = get_current_user_id();
        $user_votes = array();
        $exit_votes_count = 0;
        $max_exit_votes = 5;

        if ($user_id > 0) {
            $user_votes = get_user_meta($user_id, 'jo_exit_votes', true) ?: array();
            require_once JO_EXIT_PLUGIN_PATH . 'public/class-jo-exit-user.php';
            $exit_votes_count = Jo_Exit_User::count_exit_votes($user_id);
            $max_exit_votes = Jo_Exit_User::get_max_exit_votes();
        }

        return new WP_REST_Response(array(
            'employees' => Jo_Exit_DB::get_active_employees(),
            'user_votes' => $user_votes,
            'exit_votes_count' => $exit_votes_count,
            'max_exit_votes' => $max_exit_votes,
            'is_user_logged_in' => (bool) $user_id,
        ), 200);
    }

    /**
     * Get active employees.
     */
    public function get_employees($request)
    {
        $employees = Jo_Exit_DB::get_active_employees();
        return new WP_REST_Response($employees, 200);
    }

    /**
     * Get leaderboard.
     */
    public function get_leaderboard($request)
    {
        $leaderboard = Jo_Exit_DB::get_leaderboard();
        return new WP_REST_Response($leaderboard, 200);
    }

    /**
     * Get exited employees.
     */
    public function get_exited($request)
    {
        $exited = Jo_Exit_DB::get_exited_employees();
        return new WP_REST_Response($exited, 200);
    }

    /**
     * Set cooldown for the current user.
     */
    public function set_cooldown($request)
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('rest_forbidden', 'You must be logged in to set a cooldown.', array('status' => 401));
        }

        require_once JO_EXIT_PLUGIN_PATH . 'public/class-jo-exit-user.php';
        $result = Jo_Exit_User::set_last_vote_timestamp($user_id);

        // Use reflection or just public method if available to reset session votes
        if (method_exists('Jo_Exit_User', 'reset_session_votes')) {
            Jo_Exit_User::reset_session_votes($user_id);
        }

        if ($result) {
            return new WP_REST_Response(array('success' => true), 200);
        }

        return new WP_Error('rest_cooldown_failed', 'Failed to set cooldown', array('status' => 500));
    }
}
