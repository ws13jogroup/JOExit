<?php
/**
 * Database operations for the plugin.
 *
 * @since      1.0.0
 */
class Jo_Exit_DB
{

    /**
     * Get all active employees
     *
     * @since    1.0.0
     * @return   array    Array of employee objects
     */
    public static function get_active_employees()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';

        $query = "SELECT * FROM $table_name WHERE status = 'active' ORDER BY score DESC";
        return $wpdb->get_results($query);
    }

    /**
     * Get all exited employees
     *
     * @since    1.0.0
     * @return   array    Array of employee objects
     */
    public static function get_exited_employees()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_DB: Getting exited employees');

        // Verifica se la tabella esiste
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            error_log('Jo_Exit_DB: Table ' . $table_name . ' does not exist');

            // Forza la creazione della tabella
            require_once(plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php');
            Jo_Exit_Activator::activate();

            // Verifica nuovamente se la tabella esiste
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if (!$table_exists) {
                error_log('Jo_Exit_DB: Failed to create table ' . $table_name);
                return array();
            }

            error_log('Jo_Exit_DB: Table ' . $table_name . ' created successfully');
        }

        $query = "SELECT * FROM $table_name WHERE status = 'exit' ORDER BY exit_date DESC";
        error_log('Jo_Exit_DB: Query: ' . $query);

        $results = $wpdb->get_results($query);

        if ($wpdb->last_error) {
            error_log('Jo_Exit_DB: Database error: ' . $wpdb->last_error);
            return array();
        }

        error_log('Jo_Exit_DB: Found ' . count($results) . ' exited employees');

        return $results;
    }

    /**
     * Get leaderboard (active employees sorted by score)
     *
     * @since    1.0.0
     * @return   array    Array of employee objects
     */
    public static function get_leaderboard()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';

        $query = "SELECT * FROM $table_name WHERE status = 'active' ORDER BY score DESC";
        return $wpdb->get_results($query);
    }

    /**
     * Get a single employee by ID
     *
     * @since    1.0.0
     * @param    int      $id    Employee ID
     * @return   object   Employee object
     */
    public static function get_employee($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';

        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
        return $wpdb->get_row($query);
    }

    /**
     * Save an employee (insert or update)
     *
     * @since    1.0.0
     * @param    array    $data    Employee data
     * @return   int|false         Employee ID or false on failure
     */
    public static function save_employee($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Saving employee data: ' . print_r($data, true));

        // Check if we're updating or inserting
        if (isset($data['id']) && !empty($data['id'])) {
            // Update
            $update_data = array(
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'company_code' => $data['company_code'],
                'photo_url' => $data['photo_url'],
                'role' => isset($data['role']) ? $data['role'] : 'employee', // Use 'employee' as default role
            );

            // Add hire_year if provided
            if (isset($data['hire_year'])) {
                $update_data['hire_year'] = $data['hire_year'];
            }

            // Add score if provided
            if (isset($data['score'])) {
                $update_data['score'] = intval($data['score']);
            }

            $result = $wpdb->update(
                $table_name,
                $update_data,
                array('id' => $data['id'])
            );

            if ($result === false) {
                error_log('Update error: ' . $wpdb->last_error);
            }

            return $result !== false ? $data['id'] : false;
        } else {
            // Insert
            $insert_data = array(
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'company_code' => $data['company_code'],
                'photo_url' => $data['photo_url'],
                'role' => isset($data['role']) ? $data['role'] : 'employee', // Use 'employee' as default role
                'status' => 'active',
                'score' => isset($data['score']) ? intval($data['score']) : 0,
            );

            // Add hire_year if provided
            if (isset($data['hire_year'])) {
                $insert_data['hire_year'] = $data['hire_year'];
            }

            error_log('Insert data: ' . print_r($insert_data, true));

            $result = $wpdb->insert(
                $table_name,
                $insert_data
            );

            if (!$result) {
                error_log('Insert error: ' . $wpdb->last_error);
            }

            return $result ? $wpdb->insert_id : false;
        }
    }

    // The delete_employee method has been moved and improved with transaction support

    /**
     * Mark an employee as exited
     *
     * @since    1.0.0
     * @param    int      $id         Employee ID
     * @param    string   $exit_date  Exit date (YYYY-MM-DD)
     * @return   bool                 True on success, false on failure
     */
    /**
     * Mark an employee as exited and calculate player scores
     *
     * @since    1.0.0
     * @param    int      $id              Employee ID
     * @param    string   $exit_date       Exit date (YYYY-MM-DD format)
     * @return   bool                      True on success, false on failure
     */
    public static function mark_exit($id, $exit_date)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';
        $votes_table = $wpdb->prefix . 'jo_exit_votes';
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Marking employee as exited: ' . $id . ', exit date: ' . $exit_date);

        // Validate input parameters
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            error_log('Invalid employee ID: ' . $id);
            return false;
        }

        // Validate exit date format
        if (empty($exit_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $exit_date)) {
            error_log('Invalid exit date format: ' . $exit_date . ', using current date');
            $exit_date = date('Y-m-d');
        }

        // Check if employee exists and is not already exited
        $employee = self::get_employee($id);
        if (!$employee) {
            error_log('Employee not found: ' . $id);
            return false;
        }

        error_log('Employee data: ' . print_r($employee, true));

        if ($employee->status === 'exit') {
            error_log('Employee is already exited: ' . $id);
            return true; // Already exited, consider it a success
        }

        // Force update database structure to ensure player_scores table exists
        require_once(plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php');
        Jo_Exit_Activator::update_database_structure();

        // Check if player_scores table exists after forced update
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
        error_log('Player scores table exists after forced update: ' . ($table_exists ? 'Yes' : 'No'));

        if (!$table_exists) {
            error_log('Failed to create player scores table even after forced update');
            return false;
        }

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');
            error_log('Transaction started');

            // Calcola il punteggio finale del dipendente (exit points + anzianità)
            $final_score = intval($employee->score); // Exit points attuali
            $years = 0;

            // Calcola gli anni di anzianità se disponibili
            if (!empty($employee->hire_year) && is_numeric($employee->hire_year)) {
                $current_year = intval(date('Y'));
                $hire_year = intval($employee->hire_year);

                // Validate hire year is reasonable
                if ($hire_year > 1900 && $hire_year <= $current_year) {
                    $years = $current_year - $hire_year;
                    error_log('Employee hire year: ' . $hire_year . ', years of seniority: ' . $years);
                } else {
                    error_log('Invalid hire year: ' . $employee->hire_year);
                }
            } else {
                error_log('Employee has no hire year');
            }

            // Aggiungi gli anni di anzianità al punteggio finale
            $final_score += $years;
            error_log('Calculated final score: exit_points=' . $employee->score . ' + years=' . $years . ' = ' . $final_score);

            // Update employee status and final score
            $result = $wpdb->update(
                $table_name,
                array(
                    'status' => 'exit',
                    'exit_date' => $exit_date,
                    'final_score' => $final_score, // Aggiungi il punteggio finale
                ),
                array('id' => $id)
            );

            if ($result === false) {
                error_log('Failed to update employee status: ' . $wpdb->last_error);
                $wpdb->query('ROLLBACK');
                return false;
            }

            error_log('Employee status updated successfully with final score: ' . $final_score);

            // Verify the employee status was updated
            $updated_employee = self::get_employee($id);
            if (!$updated_employee || $updated_employee->status !== 'exit') {
                error_log('Failed to verify employee status update: ' . ($updated_employee ? $updated_employee->status : 'employee not found'));
                $wpdb->query('ROLLBACK');
                return false;
            }

            error_log('Verified employee status was updated to exit');

            // Get all users who voted 'exit' for this employee
            $exit_voters_query = $wpdb->prepare(
                "SELECT DISTINCT user_identifier FROM $votes_table WHERE employee_id = %d AND vote_type = 'exit' AND user_identifier REGEXP '^[0-9]+$'",
                $id
            );
            error_log('Exit voters query: ' . $exit_voters_query);

            $exit_voters = $wpdb->get_results($exit_voters_query);

            if ($exit_voters === null || $exit_voters === false) {
                error_log('Error executing exit voters query: ' . $wpdb->last_error);
                $wpdb->query('ROLLBACK');
                return false;
            }

            error_log('Found ' . count($exit_voters) . ' exit voters');

            // Debug the exit voters
            if (count($exit_voters) > 0) {
                error_log('Exit voters: ' . print_r($exit_voters, true));
            } else {
                error_log('No exit voters found, but continuing with the transaction');
            }

            // Process each voter
            $success_count = 0;
            $failure_count = 0;

            foreach ($exit_voters as $voter) {
                if (!isset($voter->user_identifier) || empty($voter->user_identifier)) {
                    error_log('Voter has no user identifier, skipping');
                    continue;
                }

                $user_id = intval($voter->user_identifier);
                error_log('Processing voter: ' . $user_id);

                if ($user_id <= 0) {
                    error_log('Invalid user ID: ' . $user_id . ', skipping');
                    continue;
                }

                // Check if user exists
                $user = get_userdata($user_id);
                if (!$user) {
                    error_log('User ' . $user_id . ' does not exist, skipping');
                    continue;
                }

                error_log('User exists: ' . $user->display_name . ' (ID: ' . $user_id . ')');

                // Calculate player points
                $player_points = self::calculate_player_points($user_id, $id);
                error_log('Calculated player points: ' . $player_points . ' for user ' . $user_id . ' and employee ' . $id);

                if ($player_points <= 0) {
                    error_log('No points to award to user ' . $user_id . ', skipping');
                    continue;
                }

                // Save player score
                $score_result = self::save_player_score($user_id, $id, $player_points);

                if (!$score_result) {
                    error_log('Failed to save player score for user ' . $user_id . ' and employee ' . $id);
                    $failure_count++;
                } else {
                    error_log('Successfully saved player score for user ' . $user_id . ' and employee ' . $id);
                    $success_count++;

                    // Verify the score was saved
                    $verify_query = $wpdb->prepare(
                        "SELECT * FROM $player_scores_table WHERE user_id = %d AND employee_id = %d",
                        $user_id,
                        $id
                    );
                    $saved_score = $wpdb->get_row($verify_query);

                    if ($saved_score) {
                        error_log('Verified saved score: ' . print_r($saved_score, true));
                    } else {
                        error_log('Failed to verify saved score: ' . $wpdb->last_error);
                        $failure_count++;
                        $success_count--;
                    }
                }
            }

            error_log('Player scores processing complete: ' . $success_count . ' successes, ' . $failure_count . ' failures');

            // Commit the transaction even if some player scores failed
            // The primary goal is to mark the employee as exited
            $wpdb->query('COMMIT');
            error_log('Transaction committed successfully');
            error_log('Successfully marked employee as exited: ' . $id . ' with final score: ' . $final_score);

            return true;
        } catch (Exception $e) {
            error_log('Exception in mark_exit: ' . $e->getMessage());
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Record a vote for an employee
     *
     * @since    1.0.0
     * @param    int      $employee_id      Employee ID
     * @param    string   $user_identifier  User identifier (IP or user ID)
     * @param    string   $vote_type        Vote type ('exit' or 'nope')
     * @return   bool                       True on success, false on failure
     */
    public static function record_vote($employee_id, $user_identifier, $vote_type)
    {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'jo_exit_votes';
        $employees_table = $wpdb->prefix . 'jo_exit_employees';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Recording vote for employee ID: ' . $employee_id . ', user: ' . $user_identifier . ', vote type: ' . $vote_type);

        // Check if user is logged in and is a numeric user ID
        $user_id = 0;
        if (is_numeric($user_identifier)) {
            $user_id = intval($user_identifier);
        }



        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Get user votes from user meta if user is logged in
        $user_votes = array();
        if ($user_id > 0) {
            $user_votes = get_user_meta($user_id, 'jo_exit_votes', true);
            if (!is_array($user_votes)) {
                $user_votes = array();
            }
        }

        // Get the current score for this employee
        $current_score = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT score FROM $employees_table WHERE id = %d",
                $employee_id
            )
        );

        error_log('Current employee score: ' . $current_score);

        // Get user's points for this employee from user meta
        $user_points = 0;
        if (isset($user_votes[$employee_id])) {
            $user_points = intval($user_votes[$employee_id]['points']);
        }

        error_log('User points for this employee: ' . $user_points);

        // Get the most recent vote
        $most_recent_vote = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, vote_type FROM $votes_table WHERE employee_id = %d AND user_identifier = %s ORDER BY id DESC LIMIT 1",
                $employee_id,
                $user_identifier
            )
        );

        if ($most_recent_vote) {
            // User has already voted - update the vote
            $previous_vote_type = $most_recent_vote->vote_type;
            error_log('Previous vote type: ' . $previous_vote_type);

            // Update the vote type
            $vote_result = $wpdb->update(
                $votes_table,
                array('vote_type' => $vote_type),
                array('id' => $most_recent_vote->id)
            );

            if ($vote_result === false) {
                error_log('Failed to update vote: ' . $wpdb->last_error);
                $wpdb->query('ROLLBACK');
                return false;
            }

            // Handle score changes based on vote type changes
            if ($previous_vote_type === 'exit' && $vote_type === 'nope') {
                // Changed from exit to nope - remove all user's points
                error_log('Changing from exit to nope, removing ' . $user_points . ' points');

                // Calculate new score
                $new_score = max(0, $current_score - $user_points);
                error_log('New score will be: ' . $new_score);

                // Update the score directly
                $score_result = $wpdb->update(
                    $employees_table,
                    array('score' => $new_score),
                    array('id' => $employee_id)
                );

                if ($score_result === false) {
                    error_log('Failed to update score: ' . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                    return false;
                }

                // Update all previous votes to nope
                $update_all_votes = $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $votes_table SET vote_type = 'nope' WHERE employee_id = %d AND user_identifier = %s",
                        $employee_id,
                        $user_identifier
                    )
                );

                if ($update_all_votes === false) {
                    error_log('Failed to update all votes: ' . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                    return false;
                }
            } else if ($previous_vote_type === 'nope' && $vote_type === 'exit') {
                // Changed from nope to exit - add a point
                error_log('Changing from nope to exit, adding 1 point');

                // Calculate new score
                $new_score = $current_score + 1;
                error_log('New score will be: ' . $new_score);

                // Update the score directly
                $score_result = $wpdb->update(
                    $employees_table,
                    array('score' => $new_score),
                    array('id' => $employee_id)
                );

                if ($score_result === false) {
                    error_log('Failed to update score: ' . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                    return false;
                }
            } else if ($vote_type === 'exit') {
                // Already voted exit, voting exit again - add another point
                error_log('Voting exit again, adding 1 point');

                // Calculate new score
                $new_score = $current_score + 1;
                error_log('New score will be: ' . $new_score);

                // Update the score directly
                $score_result = $wpdb->update(
                    $employees_table,
                    array('score' => $new_score),
                    array('id' => $employee_id)
                );

                if ($score_result === false) {
                    error_log('Failed to update score: ' . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                    return false;
                }
            }
        } else {
            // New vote - insert it
            error_log('New vote');
            $vote_result = $wpdb->insert(
                $votes_table,
                array(
                    'employee_id' => $employee_id,
                    'user_identifier' => $user_identifier,
                    'vote_type' => $vote_type,
                )
            );

            if (!$vote_result) {
                error_log('Failed to insert vote: ' . $wpdb->last_error);
                $wpdb->query('ROLLBACK');
                return false;
            }

            // Update employee score only for 'exit' votes
            if ($vote_type === 'exit') {
                error_log('New exit vote, adding 1 point');

                // Calculate new score
                $new_score = $current_score + 1;
                error_log('New score will be: ' . $new_score);

                // Update the score directly
                $score_result = $wpdb->update(
                    $employees_table,
                    array('score' => $new_score),
                    array('id' => $employee_id)
                );

                if ($score_result === false) {
                    error_log('Failed to update score: ' . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                    return false;
                }
            }
        }

        // Get the final score for logging
        $final_score = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT score FROM $employees_table WHERE id = %d",
                $employee_id
            )
        );
        error_log('Final employee score: ' . $final_score);

        $wpdb->query('COMMIT');
        return true;
    }

    /**
     * Check if a user has already voted for an employee
     *
     * @since    1.0.0
     * @param    int      $employee_id      Employee ID
     * @param    string   $user_identifier  User identifier (IP or user ID)
     * @return   bool                       True if voted, false if not
     */
    public static function has_voted($employee_id, $user_identifier)
    {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'jo_exit_votes';

        $existing_vote = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $votes_table WHERE employee_id = %d AND user_identifier = %s",
                $employee_id,
                $user_identifier
            )
        );

        return !empty($existing_vote);
    }

    /**
     * Remove a specific vote for an employee by a user
     *
     * @since    1.0.0
     * @param    int      $employee_id      Employee ID
     * @param    string   $user_identifier  User identifier (user ID)
     * @return   bool                       True on success, false on failure
     */
    public static function remove_vote($employee_id, $user_identifier)
    {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'jo_exit_votes';
        $employees_table = $wpdb->prefix . 'jo_exit_employees';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Removing vote for employee ID: ' . $employee_id . ', user: ' . $user_identifier);

        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Get the current score for this employee
        $current_score = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT score FROM $employees_table WHERE id = %d",
                $employee_id
            )
        );

        error_log('Current employee score: ' . $current_score);

        // Get all votes by this user for this employee
        $votes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, vote_type FROM $votes_table WHERE employee_id = %d AND user_identifier = %s",
                $employee_id,
                $user_identifier
            )
        );

        if (empty($votes)) {
            error_log('No votes found for employee ID: ' . $employee_id . ', user: ' . $user_identifier);
            $wpdb->query('ROLLBACK');
            return false;
        }

        // Count exit votes to subtract from score
        $exit_votes_count = 0;
        foreach ($votes as $vote) {
            if ($vote->vote_type === 'exit') {
                $exit_votes_count++;
            }
        }

        error_log('Found ' . count($votes) . ' votes, ' . $exit_votes_count . ' exit votes');

        // Delete all votes for this employee by this user
        $delete_result = $wpdb->delete(
            $votes_table,
            array(
                'employee_id' => $employee_id,
                'user_identifier' => $user_identifier
            )
        );

        if ($delete_result === false) {
            error_log('Failed to delete votes: ' . $wpdb->last_error);
            $wpdb->query('ROLLBACK');
            return false;
        }

        // Update employee score if there were exit votes
        if ($exit_votes_count > 0) {
            // Calculate new score
            $new_score = max(0, $current_score - $exit_votes_count);
            error_log('New score will be: ' . $new_score);

            // Update the score
            $score_result = $wpdb->update(
                $employees_table,
                array('score' => $new_score),
                array('id' => $employee_id)
            );

            if ($score_result === false) {
                error_log('Failed to update score: ' . $wpdb->last_error);
                $wpdb->query('ROLLBACK');
                return false;
            }
        }

        $wpdb->query('COMMIT');
        return true;
    }

    /**
     * Reset votes for an employee
     *
     * @since    1.0.0
     * @param    int      $employee_id      Employee ID
     * @return   bool                       True on success, false on failure
     */
    public static function reset_votes($employee_id)
    {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'jo_exit_votes';

        // Delete all votes for this employee
        $result = $wpdb->delete(
            $votes_table,
            array('employee_id' => $employee_id)
        );

        return $result !== false;
    }

    /**
     * Reactivate an exited employee
     *
     * @since    1.0.0
     * @param    int      $employee_id      Employee ID
     * @return   bool                       True on success, false on failure
     */
    public static function reactivate_employee($employee_id)
    {
        global $wpdb;
        $employees_table = $wpdb->prefix . 'jo_exit_employees';

        // Update employee status
        $result = $wpdb->update(
            $employees_table,
            array(
                'status' => 'active',
                'exit_date' => null
            ),
            array('id' => $employee_id)
        );

        return $result !== false;
    }

    /**
     * Delete an employee
     *
     * @since    1.0.0
     * @param    int      $employee_id      Employee ID
     * @return   bool                       True on success, false on failure
     */
    public static function delete_employee($employee_id)
    {
        global $wpdb;
        $employees_table = $wpdb->prefix . 'jo_exit_employees';
        $votes_table = $wpdb->prefix . 'jo_exit_votes';

        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Delete votes for this employee
        $wpdb->delete($votes_table, array('employee_id' => $employee_id));

        // Delete employee
        $result = $wpdb->delete($employees_table, array('id' => $employee_id));

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        $wpdb->query('COMMIT');
        return true;
    }

    /**
     * Update an employee
     *
     * @since    1.0.0
     * @param    int      $employee_id      Employee ID
     * @param    string   $first_name       First name
     * @param    string   $last_name        Last name
     * @param    string   $company_code     Company code
     * @param    int      $hire_year        Hire year (optional)
     * @return   bool                       True on success, false on failure
     */
    public static function update_employee($employee_id, $first_name, $last_name, $company_code, $hire_year = null)
    {
        global $wpdb;
        $employees_table = $wpdb->prefix . 'jo_exit_employees';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Updating employee ID: ' . $employee_id);

        // Prepare update data
        $update_data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'company_code' => $company_code,
            'role' => 'employee' // Default role
        );

        // Add hire_year if provided
        if ($hire_year !== null) {
            $update_data['hire_year'] = $hire_year;
        }

        error_log('Update data: ' . print_r($update_data, true));

        // Update employee
        $result = $wpdb->update(
            $employees_table,
            $update_data,
            array('id' => $employee_id)
        );

        if ($result === false) {
            error_log('Update error: ' . $wpdb->last_error);
        }

        return $result !== false;
    }

    /**
     * Calculate player points for an employee
     *
     * @since    1.0.0
     * @param    int      $user_id          User ID
     * @param    int      $employee_id      Employee ID
     * @return   int                        Player points
     */
    public static function calculate_player_points($user_id, $employee_id)
    {
        global $wpdb;
        $employees_table = $wpdb->prefix . 'jo_exit_employees';
        $votes_table = $wpdb->prefix . 'jo_exit_votes';

        // Enable error logging
        $wpdb->show_errors();
        error_log('NEW IMPLEMENTATION: Calculating player points for user ' . $user_id . ' and employee ' . $employee_id);

        // Validate input parameters
        if (empty($user_id) || !is_numeric($user_id) || $user_id <= 0) {
            error_log('Invalid user ID: ' . $user_id);
            return 0;
        }

        if (empty($employee_id) || !is_numeric($employee_id) || $employee_id <= 0) {
            error_log('Invalid employee ID: ' . $employee_id);
            return 0;
        }

        // Get employee data
        $employee = self::get_employee($employee_id);

        if (!$employee) {
            error_log('Employee not found: ' . $employee_id);
            return 0; // Employee not found
        }

        error_log('Employee data: ' . print_r($employee, true));

        if ($employee->status !== 'exit') {
            error_log('Employee is not exited: ' . $employee_id . ', status: ' . $employee->status);
            return 0; // Employee not exited
        }

        // Check if user has voted 'exit' for this employee
        $vote_count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $votes_table WHERE employee_id = %d AND user_identifier = %s AND vote_type = 'exit'",
            $employee_id,
            $user_id
        );
        error_log('Vote count query: ' . $vote_count_query);

        $vote_count = $wpdb->get_var($vote_count_query);

        if (!$vote_count || intval($vote_count) === 0) {
            error_log('No exit votes found for user ' . $user_id . ' and employee ' . $employee_id);
            return 0; // No votes found
        }

        error_log('Found ' . $vote_count . ' exit votes for user ' . $user_id . ' and employee ' . $employee_id);

        // Calculate points: 5 * years of seniority + exit points
        $years = 0;
        if (!empty($employee->hire_year) && is_numeric($employee->hire_year)) {
            $current_year = intval(date('Y'));
            $hire_year = intval($employee->hire_year);

            // Validate hire year is reasonable
            if ($hire_year > 1900 && $hire_year <= $current_year) {
                $years = $current_year - $hire_year;
                error_log('Employee hire year: ' . $hire_year . ', years of seniority: ' . $years);
            } else {
                error_log('Invalid hire year: ' . $employee->hire_year);
            }
        } else {
            error_log('Employee has no hire year');
        }

        // Calculate base points (5 points per year of seniority)
        $base_points = 5 * max(0, $years); // Ensure non-negative

        // Get exit points (score from the employee record)
        $exit_points = !empty($employee->score) && is_numeric($employee->score) ? intval($employee->score) : 0;

        // Calculate total points
        $total_points = $base_points + $exit_points;

        error_log('Calculated points: base_points=' . $base_points . ', exit_points=' . $exit_points . ', total_points=' . $total_points);

        return max(0, $total_points); // Ensure non-negative return value
    }

    /**
     * Save player score for an exited employee
     *
     * @since    1.0.0
     * @param    int      $user_id          User ID
     * @param    int      $employee_id      Employee ID
     * @param    int      $player_points    Player points
     * @return   bool                       True on success, false on failure
     */
    public static function save_player_score($user_id, $employee_id, $player_points)
    {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        // Enable error logging
        $wpdb->show_errors();
        error_log('NEW IMPLEMENTATION: Saving player score: user_id=' . $user_id . ', employee_id=' . $employee_id . ', player_points=' . $player_points);

        // Validate input parameters
        if (empty($user_id) || !is_numeric($user_id) || $user_id <= 0) {
            error_log('Invalid user ID: ' . $user_id);
            return false;
        }

        if (empty($employee_id) || !is_numeric($employee_id) || $employee_id <= 0) {
            error_log('Invalid employee ID: ' . $employee_id);
            return false;
        }

        if (!is_numeric($player_points)) {
            error_log('Invalid player points: ' . $player_points);
            $player_points = 0; // Default to 0 if invalid
        }

        // Ensure player_points is non-negative
        $player_points = max(0, intval($player_points));

        // Check if player_scores table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
        if (!$table_exists) {
            error_log('Player scores table does not exist, forcing database structure update');

            // Force update database structure
            require_once(plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php');
            Jo_Exit_Activator::update_database_structure();

            // Check again if table was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
            if (!$table_exists) {
                error_log('Failed to create player scores table even after forced update');
                return false;
            }
        }

        // Verify user exists
        $user = get_userdata($user_id);
        if (!$user) {
            error_log('User with ID ' . $user_id . ' does not exist');
            return false;
        }

        // Verify employee exists and is exited
        $employee = self::get_employee($employee_id);
        if (!$employee) {
            error_log('Employee with ID ' . $employee_id . ' does not exist');
            return false;
        }

        if ($employee->status !== 'exit') {
            error_log('Employee with ID ' . $employee_id . ' is not exited (status: ' . $employee->status . ')');
            return false;
        }

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Check if score already exists
            $existing_score_query = $wpdb->prepare(
                "SELECT id FROM $player_scores_table WHERE user_id = %d AND employee_id = %d",
                $user_id,
                $employee_id
            );
            error_log('Existing score query: ' . $existing_score_query);

            $existing_score = $wpdb->get_row($existing_score_query);

            if ($existing_score) {
                error_log('Updating existing score: ' . $existing_score->id);
                // Update existing score
                $result = $wpdb->update(
                    $player_scores_table,
                    array(
                        'player_points' => $player_points,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $existing_score->id)
                );

                if ($result === false) {
                    error_log('Failed to update player score: ' . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                    return false;
                } else {
                    error_log('Successfully updated player score');
                }
            } else {
                error_log('Inserting new score');
                // Insert new score
                $result = $wpdb->insert(
                    $player_scores_table,
                    array(
                        'user_id' => $user_id,
                        'employee_id' => $employee_id,
                        'player_points' => $player_points,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    )
                );

                if (!$result) {
                    error_log('Failed to insert player score: ' . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                    return false;
                } else {
                    error_log('Successfully inserted player score with ID: ' . $wpdb->insert_id);
                }
            }

            // Verify the score was saved correctly
            $verify_query = $wpdb->prepare(
                "SELECT * FROM $player_scores_table WHERE user_id = %d AND employee_id = %d",
                $user_id,
                $employee_id
            );
            $saved_score = $wpdb->get_row($verify_query);

            if (!$saved_score) {
                error_log('Failed to verify player score was saved');
                $wpdb->query('ROLLBACK');
                return false;
            }

            error_log('Verified player score was saved: ' . print_r($saved_score, true));

            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            error_log('Exception in save_player_score: ' . $e->getMessage());
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Get top player scores for an exited employee
     *
     * @since    1.0.0
     * @param    int      $employee_id      Employee ID
     * @param    int      $limit            Number of top scores to return (default: 3)
     * @return   array                      Array of player score objects
     */
    public static function get_top_player_scores($employee_id, $limit = 3)
    {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        // Enable error logging
        $wpdb->show_errors();
        error_log('NEW IMPLEMENTATION: Getting top player scores for employee: ' . $employee_id . ', limit: ' . $limit);

        // Validate input parameters
        if (empty($employee_id) || !is_numeric($employee_id) || $employee_id <= 0) {
            error_log('Invalid employee ID: ' . $employee_id);
            return array();
        }

        // Ensure limit is a positive integer
        $limit = max(1, intval($limit));

        // Force update database structure to ensure player_scores table exists
        require_once(plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php');
        Jo_Exit_Activator::update_database_structure();

        // Check if player_scores table exists after forced update
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
        error_log('Player scores table exists after forced update: ' . ($table_exists ? 'Yes' : 'No'));

        if (!$table_exists) {
            error_log('Failed to create player scores table even after forced update');
            return array();
        }

        // Check if there are any scores for this employee
        $count_query = $wpdb->prepare("SELECT COUNT(*) FROM $player_scores_table WHERE employee_id = %d", $employee_id);
        $score_count = $wpdb->get_var($count_query);
        error_log('Found ' . $score_count . ' scores for employee ' . $employee_id . ' in the database');

        if ($score_count == 0) {
            error_log('No scores found for employee ' . $employee_id . ', returning empty array');
            return array();
        }

        try {
            // Get the top scores with user information
            $scores_query = $wpdb->prepare(
                "SELECT ps.*, u.display_name, u.user_login
                FROM $player_scores_table ps
                JOIN {$wpdb->users} u ON ps.user_id = u.ID
                WHERE ps.employee_id = %d
                ORDER BY ps.player_points DESC
                LIMIT %d",
                $employee_id,
                $limit
            );
            error_log('Scores query: ' . $scores_query);

            $scores = $wpdb->get_results($scores_query);

            if ($scores === null || $scores === false) {
                error_log('Error executing scores query: ' . $wpdb->last_error);
                return array();
            }

            error_log('Found ' . count($scores) . ' scores with the query');

            // If no scores found, return empty array
            if (empty($scores)) {
                error_log('No scores found with the query, returning empty array');
                return array();
            }

            // Add avatar URLs and ensure player_points is numeric
            foreach ($scores as $score) {
                // Get avatar URL from user meta
                $avatar_url = get_user_meta($score->user_id, 'jo_exit_avatar', true);

                // If no custom avatar, use default WordPress avatar
                if (empty($avatar_url)) {
                    $avatar_url = get_avatar_url($score->user_id);
                    error_log('Using default avatar for user: ' . $score->user_id);
                } else {
                    error_log('Using custom avatar for user: ' . $score->user_id . ', avatar: ' . $avatar_url);
                }

                $score->avatar_url = $avatar_url;

                // Ensure player_points is numeric
                if (!isset($score->player_points) || !is_numeric($score->player_points)) {
                    $score->player_points = 0;
                }

                // Ensure display_name is set
                if (empty($score->display_name)) {
                    $score->display_name = $score->user_login ?: ('User ' . $score->user_id);
                }
            }

            error_log('Returning ' . count($scores) . ' scores for employee ' . $employee_id);
            error_log('First score: ' . print_r($scores[0], true));

            return $scores;
        } catch (Exception $e) {
            error_log('Exception in get_top_player_scores: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Get all exited employees with their top player scores
     *
     * @since    1.0.0
     * @param    int      $limit_per_employee  Number of top scores per employee (default: 3)
     * @return   array                         Array of employee objects with top_scores property
     */
    public static function get_exited_employees_with_scores($limit_per_employee = 3)
    {
        global $wpdb;

        // Enable error logging
        $wpdb->show_errors();
        error_log('NEW IMPLEMENTATION: Getting all exited employees with scores, limit per employee: ' . $limit_per_employee);

        // Ensure limit is a positive integer
        $limit_per_employee = max(1, intval($limit_per_employee));

        // Force update database structure to ensure player_scores table exists
        require_once(plugin_dir_path(dirname(__FILE__)) . 'includes/class-jo-exit-activator.php');
        Jo_Exit_Activator::update_database_structure();

        // Check if player_scores table exists after forced update
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
        error_log('Player scores table exists after forced update: ' . ($table_exists ? 'Yes' : 'No'));

        if (!$table_exists) {
            error_log('Failed to create player scores table even after forced update');
            return array();
        }

        try {
            // Get all exited employees
            $exited_employees = self::get_exited_employees();

            if (!is_array($exited_employees)) {
                error_log('get_exited_employees did not return an array');
                return array();
            }

            error_log('Found ' . count($exited_employees) . ' exited employees');

            // If no exited employees, return empty array
            if (empty($exited_employees)) {
                error_log('No exited employees found');
                return array();
            }

            // Debug the first exited employee
            if (count($exited_employees) > 0) {
                error_log('First exited employee: ' . print_r($exited_employees[0], true));
            }

            // Get top scores for each employee
            foreach ($exited_employees as $employee) {
                if (!isset($employee->id) || empty($employee->id)) {
                    error_log('Employee has no ID, skipping');
                    continue;
                }

                $employee_name = isset($employee->first_name) && isset($employee->last_name) ?
                    $employee->first_name . ' ' . $employee->last_name : 'Unknown';

                error_log('Getting top scores for employee: ' . $employee->id . ' (' . $employee_name . ')');

                // Get top scores for this employee
                $employee->top_scores = self::get_top_player_scores($employee->id, $limit_per_employee);

                if (!is_array($employee->top_scores)) {
                    error_log('get_top_player_scores did not return an array for employee: ' . $employee->id);
                    $employee->top_scores = array();
                }

                error_log('Found ' . count($employee->top_scores) . ' top scores for employee: ' . $employee->id);

                // Debug the first top score if available
                if (count($employee->top_scores) > 0) {
                    error_log('First top score for employee ' . $employee->id . ': ' . print_r($employee->top_scores[0], true));
                }
            }

            // Debug the final result
            error_log('Returning ' . count($exited_employees) . ' exited employees with scores');

            return $exited_employees;
        } catch (Exception $e) {
            error_log('Exception in get_exited_employees_with_scores: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Get user leaderboard based on experience points
     *
     * @since    1.0.0
     * @param    int      $limit    Maximum number of users to return (default: 20)
     * @return   array    Array of user objects with experience points
     */
    public static function get_user_leaderboard($limit = 20)
    {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';
        $users = array();

        try {
            // Enable error logging
            $wpdb->show_errors();
            error_log('Jo_Exit_DB: Getting user leaderboard');

            // Check if player_scores table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
            if (!$table_exists) {
                error_log('Jo_Exit_DB: Player scores table does not exist');
                return array();
            }

            // Log the table structure
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $player_scores_table");
            error_log('Jo_Exit_DB: Player scores table structure: ' . print_r($columns, true));

            // Check if table has data
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $player_scores_table");
            error_log('Jo_Exit_DB: Player scores table has ' . $count . ' rows');

            // If table is empty, return empty array
            if ($count == 0) {
                error_log('Jo_Exit_DB: Player scores table is empty');
                return array();
            }

            // Get users with their total experience points
            $query = $wpdb->prepare(
                "SELECT ps.user_id, u.display_name, u.user_login, SUM(ps.player_points) as exp_points,
                 (SELECT um.meta_value FROM {$wpdb->usermeta} um WHERE um.user_id = ps.user_id AND um.meta_key = 'jo_exit_avatar' LIMIT 1) as avatar
                 FROM $player_scores_table ps
                 JOIN {$wpdb->users} u ON ps.user_id = u.ID
                 GROUP BY ps.user_id
                 ORDER BY exp_points DESC
                 LIMIT %d",
                $limit
            );

            error_log('Jo_Exit_DB: Executing query: ' . $query);
            $users = $wpdb->get_results($query);

            if ($wpdb->last_error) {
                error_log('Jo_Exit_DB: Database error: ' . $wpdb->last_error);
                return array();
            }

            // Log the results
            error_log('Jo_Exit_DB: Query returned ' . count($users) . ' users');
            if (count($users) > 0) {
                error_log('Jo_Exit_DB: First user: ' . print_r($users[0], true));
            }
        } catch (Exception $e) {
            error_log('Jo_Exit_DB: Exception in get_user_leaderboard: ' . $e->getMessage());
            return array();
        }

        // Process avatar URLs
        foreach ($users as $key => $user) {
            // If no custom avatar, use WordPress default avatar
            if (empty($user->avatar)) {
                $users[$key]->avatar = get_avatar_url($user->user_id);
            }

            // Ensure exp_points is an integer
            $users[$key]->exp_points = intval($user->exp_points);

            // Debug log
            error_log('Jo_Exit_DB: User ' . $user->user_id . ' has ' . $users[$key]->exp_points . ' exp points');
        }

        error_log('Jo_Exit_DB: Found ' . count($users) . ' users for leaderboard');

        return $users;
    }

    /**
     * Get all users with their experience points for admin management
     *
     * @since    1.0.0
     * @return   array    Array of user objects with experience points
     */
    public static function get_all_users_with_exp_points()
    {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';
        $users = array();

        try {
            // Check if player_scores table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
            if (!$table_exists) {
                return array();
            }

            // Get all users with their total experience points
            $query = "SELECT ps.user_id, u.display_name, u.user_login, SUM(ps.player_points) as exp_points,
                     (SELECT um.meta_value FROM {$wpdb->usermeta} um WHERE um.user_id = ps.user_id AND um.meta_key = 'jo_exit_avatar' LIMIT 1) as avatar
                     FROM $player_scores_table ps
                     JOIN {$wpdb->users} u ON ps.user_id = u.ID
                     GROUP BY ps.user_id
                     ORDER BY exp_points DESC";

            $users = $wpdb->get_results($query);

            // Process avatar URLs and ensure exp_points is an integer
            foreach ($users as $key => $user) {
                // If no custom avatar, use WordPress default avatar
                if (empty($user->avatar)) {
                    $users[$key]->avatar = get_avatar_url($user->user_id);
                }

                // Ensure exp_points is an integer
                $users[$key]->exp_points = intval($user->exp_points);
            }
        } catch (Exception $e) {
            return array();
        }

        return $users;
    }

    /**
     * Update user experience points
     *
     * @since    1.0.0
     * @param    int      $user_id       The user ID
     * @param    int      $exp_points    The new experience points value
     * @return   bool                    True on success, false on failure
     */
    public static function update_user_exp_points($user_id, $exp_points)
    {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        try {
            // Enable error logging
            $wpdb->show_errors();
            error_log('Updating experience points for user ID: ' . $user_id . ', new points: ' . $exp_points);

            // Check if player_scores table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
            if (!$table_exists) {
                error_log('Player scores table does not exist');
                return false;
            }

            // Get all player scores for this user
            $scores = $wpdb->get_results($wpdb->prepare(
                "SELECT id, player_points FROM $player_scores_table WHERE user_id = %d",
                $user_id
            ));

            error_log('Found ' . count($scores) . ' score records for user ID: ' . $user_id);

            // If user has no scores yet, create a dummy score entry
            if (empty($scores)) {
                error_log('No existing scores found for user ID: ' . $user_id . ', creating a new score entry');

                // Insert a new score entry with the specified points
                $result = $wpdb->insert(
                    $player_scores_table,
                    array(
                        'user_id' => $user_id,
                        'employee_id' => 0, // Dummy employee ID
                        'player_points' => $exp_points,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%d', '%s')
                );

                if ($result) {
                    error_log('Successfully created new score entry for user ID: ' . $user_id);
                    return true;
                } else {
                    error_log('Failed to create new score entry: ' . $wpdb->last_error);
                    return false;
                }
            }

            // Calculate total current points
            $total_points = 0;
            foreach ($scores as $score) {
                $total_points += intval($score->player_points);
            }

            error_log('Current total points: ' . $total_points . ', new total points: ' . $exp_points);

            // If total is zero, but we want to set non-zero points, use equal distribution
            if ($total_points == 0 && $exp_points > 0) {
                error_log('Current total is zero but new total is positive, using equal distribution');
                $points_per_entry = max(1, round($exp_points / count($scores)));

                // Begin transaction
                $wpdb->query('START TRANSACTION');

                // Update each score with equal points
                $success = true;
                foreach ($scores as $score) {
                    $result = $wpdb->update(
                        $player_scores_table,
                        array('player_points' => $points_per_entry),
                        array('id' => $score->id),
                        array('%d'),
                        array('%d')
                    );

                    if ($result === false) {
                        error_log('Failed to update score ID: ' . $score->id . ', error: ' . $wpdb->last_error);
                        $success = false;
                        break;
                    }
                }

                // Commit or rollback based on success
                if ($success) {
                    $wpdb->query('COMMIT');
                    error_log('Successfully updated all scores with equal distribution');
                    return true;
                } else {
                    $wpdb->query('ROLLBACK');
                    error_log('Failed to update scores, transaction rolled back');
                    return false;
                }
            }

            // Calculate ratio for new points
            $ratio = $exp_points / $total_points;
            error_log('Ratio for proportional update: ' . $ratio);

            // Begin transaction
            $wpdb->query('START TRANSACTION');

            // Update each score proportionally
            $success = true;
            foreach ($scores as $score) {
                $new_points = round($score->player_points * $ratio);
                // Ensure minimum of 1 point
                if ($new_points < 1)
                    $new_points = 1;

                error_log('Updating score ID: ' . $score->id . ', old points: ' . $score->player_points . ', new points: ' . $new_points);

                $result = $wpdb->update(
                    $player_scores_table,
                    array('player_points' => $new_points),
                    array('id' => $score->id),
                    array('%d'),
                    array('%d')
                );

                if ($result === false) {
                    error_log('Failed to update score ID: ' . $score->id . ', error: ' . $wpdb->last_error);
                    $success = false;
                    break;
                }
            }

            // Commit or rollback based on success
            if ($success) {
                $wpdb->query('COMMIT');
                error_log('Successfully updated all scores proportionally');
                return true;
            } else {
                $wpdb->query('ROLLBACK');
                error_log('Failed to update scores, transaction rolled back');
                return false;
            }
        } catch (Exception $e) {
            error_log('Exception in update_user_exp_points: ' . $e->getMessage());
            if (isset($wpdb) && $wpdb) {
                $wpdb->query('ROLLBACK');
            }
            return false;
        }
    }

    /**
     * Delete user from leaderboard (remove all experience points)
     *
     * @since    1.0.0
     * @param    int      $user_id    The user ID
     * @return   bool                 True on success, false on failure
     */
    public static function delete_user_from_leaderboard($user_id)
    {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        try {
            // Check if player_scores table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
            if (!$table_exists) {
                return false;
            }

            // Delete all scores for this user
            $result = $wpdb->delete(
                $player_scores_table,
                array('user_id' => $user_id),
                array('%d')
            );

            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Reset all user experience points to zero
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure
     */
    public static function reset_all_exp_points()
    {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        try {
            // Check if player_scores table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
            if (!$table_exists) {
                return false;
            }

            // Update all scores to 0 (zero value)
            $result = $wpdb->query("UPDATE $player_scores_table SET player_points = 0");

            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete all user experience points (empty leaderboard)
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure
     */
    public static function delete_all_exp_points()
    {
        global $wpdb;
        $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

        try {
            // Check if player_scores table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
            if (!$table_exists) {
                return false;
            }

            // Delete all scores
            $result = $wpdb->query("TRUNCATE TABLE $player_scores_table");

            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get user votes with employee details
     *
     * @since    1.0.0
     * @param    int      $user_id       User ID (0 for all users)
     * @param    int      $employee_id   Employee ID (0 for all employees)
     * @return   array                   Array of vote objects with employee and user details
     */
    public static function get_user_votes($user_id = 0, $employee_id = 0)
    {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'jo_exit_votes';
        $employees_table = $wpdb->prefix . 'jo_exit_employees';
        $users_table = $wpdb->users;

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_DB: Getting user votes for user ID: ' . $user_id . ', employee ID: ' . $employee_id);

        // Build the query
        $query = "SELECT v.*,
                  e.first_name, e.last_name, e.photo_url, e.company_code, e.status as employee_status,
                  u.display_name, u.user_login
                  FROM $votes_table v
                  JOIN $employees_table e ON v.employee_id = e.id
                  JOIN $users_table u ON v.user_identifier = u.ID
                  WHERE v.user_identifier REGEXP '^[0-9]+$'";

        $params = array();

        // Add user filter if specified
        if ($user_id > 0) {
            $query .= " AND v.user_identifier = %d";
            $params[] = $user_id;
        }

        // Add employee filter if specified
        if ($employee_id > 0) {
            $query .= " AND v.employee_id = %d";
            $params[] = $employee_id;
        }

        // Order by user, employee, and creation date
        $query .= " ORDER BY u.display_name ASC, e.first_name ASC, e.last_name ASC, v.created_at DESC";

        // Prepare the query if we have parameters
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        error_log('Jo_Exit_DB: Executing query: ' . $query);
        $votes = $wpdb->get_results($query);

        if ($wpdb->last_error) {
            error_log('Jo_Exit_DB: Database error: ' . $wpdb->last_error);
            return array();
        }

        // Get user meta votes for points information
        $user_meta_votes = array();
        $processed_votes = array();

        foreach ($votes as $vote) {
            $vote_user_id = intval($vote->user_identifier);

            // Get user votes from meta if not already loaded for this user
            if (!isset($user_meta_votes[$vote_user_id])) {
                $user_meta_votes[$vote_user_id] = get_user_meta($vote_user_id, 'jo_exit_votes', true);
                if (!is_array($user_meta_votes[$vote_user_id])) {
                    $user_meta_votes[$vote_user_id] = array();
                }
            }

            // Add points information from user meta
            $vote_employee_id = intval($vote->employee_id);
            $vote->points = 0;

            if (isset($user_meta_votes[$vote_user_id][$vote_employee_id]['points'])) {
                $vote->points = intval($user_meta_votes[$vote_user_id][$vote_employee_id]['points']);
            }

            // If the vote is 'exit' but has 0 points, or if it's 'nope', don't show it in the table
            if (($vote->vote_type === 'exit' && $vote->points === 0) || $vote->vote_type === 'nope') {
                error_log('Jo_Exit_DB: Skipping vote for employee ID ' . $vote_employee_id . ' with ' . $vote->points . ' points and vote_type ' . $vote->vote_type);
                continue;
            }

            // Add to processed votes
            $processed_votes[] = $vote;
        }

        error_log('Jo_Exit_DB: Found ' . count($processed_votes) . ' votes');
        return $processed_votes;
    }

    /**
     * Update a user vote
     *
     * @since    1.0.0
     * @param    int      $user_id       User ID
     * @param    int      $employee_id   Employee ID
     * @param    string   $vote_type     Vote type ('exit' or 'nope')
     * @param    int      $points        Points for the vote
     * @return   bool                    True on success, false on failure
     */
    public static function update_user_vote($user_id, $employee_id, $vote_type, $points)
    {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'jo_exit_votes';
        $employees_table = $wpdb->prefix . 'jo_exit_employees';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_DB: Updating vote for user ID: ' . $user_id . ', employee ID: ' . $employee_id . ', vote type: ' . $vote_type . ', points: ' . $points);

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Get current employee score
            $current_score = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT score FROM $employees_table WHERE id = %d",
                    $employee_id
                )
            );

            if ($current_score === null) {
                error_log('Jo_Exit_DB: Employee not found: ' . $employee_id);
                $wpdb->query('ROLLBACK');
                return false;
            }

            error_log('Jo_Exit_DB: Current employee score: ' . $current_score);

            // Get user votes from meta
            $user_votes = get_user_meta($user_id, 'jo_exit_votes', true);
            if (!is_array($user_votes)) {
                $user_votes = array();
            }

            // Get current vote data
            $current_vote_type = isset($user_votes[$employee_id]['vote']) ? $user_votes[$employee_id]['vote'] : '';
            $current_points = isset($user_votes[$employee_id]['points']) ? intval($user_votes[$employee_id]['points']) : 0;

            error_log('Jo_Exit_DB: Current vote type: ' . $current_vote_type . ', current points: ' . $current_points);

            // Calculate score adjustment
            $score_adjustment = 0;

            // If changing from 'nope' to 'exit'
            if ($current_vote_type === 'nope' && $vote_type === 'exit') {
                $score_adjustment = $points;
            }
            // If changing from 'exit' to 'nope'
            else if ($current_vote_type === 'exit' && $vote_type === 'nope') {
                $score_adjustment = -$current_points;
            }
            // If staying as 'exit' but changing points
            else if ($current_vote_type === 'exit' && $vote_type === 'exit' && $current_points !== $points) {
                $score_adjustment = $points - $current_points;
            }

            error_log('Jo_Exit_DB: Score adjustment: ' . $score_adjustment);

            // Update the vote in the database
            $vote_result = $wpdb->update(
                $votes_table,
                array('vote_type' => $vote_type),
                array('employee_id' => $employee_id, 'user_identifier' => $user_id)
            );

            if ($vote_result === false) {
                error_log('Jo_Exit_DB: Failed to update vote: ' . $wpdb->last_error);
                $wpdb->query('ROLLBACK');
                return false;
            }

            // Update user meta
            $user_votes[$employee_id] = array(
                'vote' => $vote_type,
                'points' => $points,
                'timestamp' => time()
            );

            $meta_result = update_user_meta($user_id, 'jo_exit_votes', $user_votes);

            if (!$meta_result) {
                error_log('Jo_Exit_DB: Failed to update user meta');
                $wpdb->query('ROLLBACK');
                return false;
            }

            // Update employee score if needed
            if ($score_adjustment !== 0) {
                $new_score = intval($current_score) + $score_adjustment;
                if ($new_score < 0)
                    $new_score = 0;

                error_log('Jo_Exit_DB: Updating employee score from ' . $current_score . ' to ' . $new_score);

                $score_result = $wpdb->update(
                    $employees_table,
                    array('score' => $new_score),
                    array('id' => $employee_id)
                );

                if ($score_result === false) {
                    error_log('Jo_Exit_DB: Failed to update employee score: ' . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                    return false;
                }
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            error_log('Jo_Exit_DB: Vote updated successfully');
            return true;
        } catch (Exception $e) {
            error_log('Jo_Exit_DB: Exception in update_user_vote: ' . $e->getMessage());
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Reset all votes for a user
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID
     * @return   bool                 True on success, false on failure
     */
    public static function reset_user_votes($user_id)
    {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'jo_exit_votes';
        $employees_table = $wpdb->prefix . 'jo_exit_employees';

        // Enable error logging
        $wpdb->show_errors();
        error_log('Jo_Exit_DB: Resetting votes for user ID: ' . $user_id);

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Get user votes from meta
            $user_votes = get_user_meta($user_id, 'jo_exit_votes', true);
            if (!is_array($user_votes)) {
                $user_votes = array();
            }

            // Get all exit votes for this user
            $exit_votes = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT employee_id FROM $votes_table WHERE user_identifier = %s AND vote_type = 'exit'",
                    $user_id
                )
            );

            error_log('Jo_Exit_DB: Found ' . count($exit_votes) . ' exit votes for user ID: ' . $user_id);

            // Update employee scores
            foreach ($exit_votes as $vote) {
                $employee_id = $vote->employee_id;
                $points = isset($user_votes[$employee_id]['points']) ? intval($user_votes[$employee_id]['points']) : 1;

                // Get current employee score
                $current_score = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT score FROM $employees_table WHERE id = %d",
                        $employee_id
                    )
                );

                if ($current_score === null) {
                    error_log('Jo_Exit_DB: Employee not found: ' . $employee_id);
                    continue;
                }

                // Calculate new score
                $new_score = intval($current_score) - $points;
                if ($new_score < 0)
                    $new_score = 0;

                error_log('Jo_Exit_DB: Updating employee ID: ' . $employee_id . ' score from ' . $current_score . ' to ' . $new_score);

                // Update employee score
                $wpdb->update(
                    $employees_table,
                    array('score' => $new_score),
                    array('id' => $employee_id)
                );
            }

            // Delete all votes for this user
            $delete_result = $wpdb->delete(
                $votes_table,
                array('user_identifier' => $user_id),
                array('%s')
            );

            if ($delete_result === false) {
                error_log('Jo_Exit_DB: Failed to delete votes: ' . $wpdb->last_error);
                $wpdb->query('ROLLBACK');
                return false;
            }

            // Clear user meta votes
            $meta_result = delete_user_meta($user_id, 'jo_exit_votes');

            if (!$meta_result) {
                error_log('Jo_Exit_DB: Failed to delete user meta');
                $wpdb->query('ROLLBACK');
                return false;
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            error_log('Jo_Exit_DB: User votes reset successfully');
            return true;
        } catch (Exception $e) {
            error_log('Jo_Exit_DB: Exception in reset_user_votes: ' . $e->getMessage());
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
}
