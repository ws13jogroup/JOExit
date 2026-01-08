<?php
/**
 * Custom AJAX handler for JO Exit Plugin
 */

// Define WordPress path
define('WP_USE_THEMES', false);

// Find the wp-load.php file
$wp_load_paths = array(
    '../../../../wp-load.php',
    '../../../../../wp-load.php',
    '../../../../../../wp-load.php',
);

foreach ($wp_load_paths as $path) {
    if (file_exists(dirname(__FILE__) . '/' . $path)) {
        require_once(dirname(__FILE__) . '/' . $path);
        break;
    }
}

// Make sure we have WordPress loaded
if (!function_exists('wp_die')) {
    die('WordPress not loaded');
}

// Set headers
header('Content-Type: application/json');

// Get the action
$action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';

// Disabilitiamo completamente la verifica del nonce per tutte le richieste AJAX
// Questo è necessario per far funzionare la schermata exit

// Handle the action
switch ($action) {
    case 'get_employees':
        // Get active employees
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';

        $query = "SELECT * FROM $table_name WHERE status = 'active' ORDER BY score DESC";
        $employees = $wpdb->get_results($query);

        // Get user votes if user is logged in
        $user_votes = array();
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $user_votes = get_user_meta($user_id, 'jo_exit_votes', true);
            if (!is_array($user_votes)) {
                $user_votes = array();
            }
        }

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'employees' => $employees,
                'user_votes' => $user_votes,
            ),
        ));
        break;

    case 'get_leaderboard':
        // Get leaderboard
        global $wpdb;
        $table_name = $wpdb->prefix . 'jo_exit_employees';

        $query = "SELECT * FROM $table_name WHERE status = 'active' ORDER BY score DESC";
        $leaderboard = $wpdb->get_results($query);

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'employees' => $leaderboard,
            ),
        ));
        break;

    case 'get_exited':
        try {
            // Include the DB class if not already included
            if (!class_exists('Jo_Exit_DB')) {
                error_log('Jo_Exit_DB class not found, including it now');
                require_once dirname(__FILE__) . '/../includes/class-jo-exit-db.php';
            }

            // Get exited employees
            global $wpdb;
            $table_name = $wpdb->prefix . 'jo_exit_employees';
            $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';

            // Log per debug
            error_log('Jo_Exit: Custom AJAX handler - get_exited action called');

            $query = "SELECT * FROM $table_name WHERE status = 'exit' ORDER BY exit_date DESC";
            $exited = $wpdb->get_results($query);

            // Log per debug
            error_log('Jo_Exit: Custom AJAX handler - Found ' . count($exited) . ' exited employees');
            if (count($exited) > 0) {
                error_log('Jo_Exit: Custom AJAX handler - First exited employee: ' . print_r($exited[0], true));
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
                    $wpdb->update(
                        $table_name,
                        array('final_score' => $final_score),
                        array('id' => $employee->id)
                    );

                    // Aggiorna il dipendente nell'array
                    $exited[$key]->final_score = $final_score;
                }

                // Aggiungi i punteggi player per questo dipendente
                // Ottieni l'utente corrente
                $current_user_id = is_user_logged_in() ? get_current_user_id() : 0;

                if ($current_user_id > 0) {
                    // Verifica se l'utente ha votato questo dipendente
                    $vote_query = $wpdb->prepare(
                        "SELECT vote_type FROM {$wpdb->prefix}jo_exit_votes
                         WHERE employee_id = %d AND user_identifier = %s",
                        $employee->id,
                        $current_user_id
                    );

                    $vote = $wpdb->get_var($vote_query);

                    // Se l'utente ha votato 'exit', calcola il punteggio player
                    if ($vote === 'exit') {
                        // Calcola il punteggio player: punteggio finale * exit points dell'utente
                        $final_score = intval($employee->final_score);

                        // Ottieni i punti exit che l'utente ha dato a questo dipendente
                        $user_votes = get_user_meta($current_user_id, 'jo_exit_votes', true);
                        $user_exit_points = 0;

                        if (is_array($user_votes) && isset($user_votes[$employee->id]) &&
                            isset($user_votes[$employee->id]['points'])) {
                            $user_exit_points = intval($user_votes[$employee->id]['points']);
                        }

                        // Calcola il punteggio player
                        $player_points = $final_score * $user_exit_points;

                        // Aggiungi il punteggio player all'oggetto dipendente
                        $exited[$key]->player_points = $player_points;

                        // Ottieni il nome utente
                        $user_info = get_userdata($current_user_id);
                        $exited[$key]->player_username = $user_info ? $user_info->user_login : 'Utente ' . $current_user_id;

                        // Salva il punteggio player nel database
                        $existing_score = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM $player_scores_table WHERE user_id = %d AND employee_id = %d",
                            $current_user_id,
                            $employee->id
                        ));

                        if ($existing_score) {
                            // Aggiorna il punteggio esistente
                            $wpdb->update(
                                $player_scores_table,
                                array(
                                    'player_points' => $player_points,
                                    'updated_at' => current_time('mysql')
                                ),
                                array('id' => $existing_score->id)
                            );
                        } else {
                            // Inserisci un nuovo punteggio
                            $wpdb->insert(
                                $player_scores_table,
                                array(
                                    'user_id' => $current_user_id,
                                    'employee_id' => $employee->id,
                                    'player_points' => $player_points,
                                    'created_at' => current_time('mysql'),
                                    'updated_at' => current_time('mysql')
                                )
                            );
                        }
                    }
                }

                // Ottieni i top 3 punteggi player per questo dipendente
                $top_scores_query = $wpdb->prepare(
                    "SELECT ps.*, u.display_name, u.user_login
                     FROM $player_scores_table ps
                     JOIN {$wpdb->users} u ON ps.user_id = u.ID
                     WHERE ps.employee_id = %d
                     ORDER BY ps.player_points DESC
                     LIMIT 3",
                    $employee->id
                );

                $top_scores = $wpdb->get_results($top_scores_query);

                // Aggiungi i top scores all'oggetto dipendente
                $exited[$key]->top_scores = $top_scores ?: array();
            }

            // Prepara la risposta
            $response = array(
                'success' => true,
                'data' => array(
                    'employees' => $exited,
                ),
            );

            // Log per debug
            error_log('Jo_Exit: Custom AJAX handler - Sending response: ' . json_encode($response));

            // Invia la risposta
            echo json_encode($response);
        } catch (Exception $e) {
            error_log('Jo_Exit: Exception in get_exited: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'data' => 'Si è verificato un errore durante il caricamento dei dipendenti usciti: ' . $e->getMessage(),
            ));
        }
        break;

    case 'vote':
        // Validate input
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        $vote_type = isset($_POST['vote_type']) ? sanitize_text_field($_POST['vote_type']) : '';

        if ($employee_id <= 0 || !in_array($vote_type, array('exit', 'nope'))) {
            echo json_encode(array(
                'success' => false,
                'data' => 'Invalid input',
            ));
            exit;
        }

        // Get user identifier (IP address or user ID if logged in)
        $user_identifier = is_user_logged_in() ? get_current_user_id() : $_SERVER['REMOTE_ADDR'];

        // Use the DB class to record the vote
        if (!class_exists('Jo_Exit_DB')) {
            // Include the DB class
            require_once dirname(__FILE__) . '/../includes/class-jo-exit-db.php';
        }

        // Record vote using the DB class
        $result = Jo_Exit_DB::record_vote($employee_id, $user_identifier, $vote_type);

        if (!$result) {
            echo json_encode(array(
                'success' => false,
                'data' => 'Failed to record vote',
            ));
            exit;
        }

        // Non chiamiamo più store_user_vote qui perché viene già chiamato in Jo_Exit_DB::record_vote
        // Questo evita il doppio conteggio dei punti
        // Non aggiorniamo nemmeno il timestamp dell'ultimo voto qui, perché questo causerebbe
        // l'attivazione del cooldown dopo un solo voto
        // Il timestamp dell'ultimo voto deve essere aggiornato solo quando l'utente ha votato tutti i dipendenti
        // o quando ha raggiunto il limite massimo di voti exit

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'message' => 'Vote recorded successfully',
            ),
        ));
        break;

    case 'jo_exit_load_info':
        // Load info content
        ob_start();
        include_once dirname(__FILE__) . '/partials/jo-exit-info.php';
        $content = ob_get_clean();

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'content' => $content,
            ),
        ));
        break;

    case 'get_player_leaderboard':
        // Get player leaderboard
        error_log('Custom AJAX handler: get_player_leaderboard action called');

        try {
            // Include the DB class if not already included
            if (!class_exists('Jo_Exit_DB')) {
                error_log('Jo_Exit_DB class not found, including it now');
                require_once dirname(__FILE__) . '/../includes/class-jo-exit-db.php';
            } else {
                error_log('Jo_Exit_DB class already loaded');
            }

            // Include the activator class to ensure the database structure is up to date
            if (!class_exists('Jo_Exit_Activator')) {
                error_log('Jo_Exit_Activator class not found, including it now');
                require_once dirname(__FILE__) . '/../includes/class-jo-exit-activator.php';
                Jo_Exit_Activator::update_database_structure();
            } else {
                error_log('Jo_Exit_Activator class already loaded');
                Jo_Exit_Activator::update_database_structure();
            }

            // Check if the player_scores table exists
            global $wpdb;
            $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
            error_log('Player scores table exists: ' . ($table_exists ? 'Yes' : 'No'));

            // Get all exited employees first
            $exited_employees = Jo_Exit_DB::get_exited_employees();
            error_log('Found ' . count($exited_employees) . ' exited employees');

            // Debug the first exited employee
            if (count($exited_employees) > 0) {
                error_log('First exited employee: ' . print_r($exited_employees[0], true));
            }

            // Now get exited employees with their top player scores
            $exited_employees_with_scores = Jo_Exit_DB::get_exited_employees_with_scores();
            error_log('Custom AJAX handler: Found ' . count($exited_employees_with_scores) . ' exited employees with scores');

            // Debug the first exited employee with scores
            if (count($exited_employees_with_scores) > 0) {
                error_log('First exited employee with scores: ' . print_r($exited_employees_with_scores[0], true));

                // Check if top_scores property exists
                if (isset($exited_employees_with_scores[0]->top_scores)) {
                    error_log('top_scores property exists with ' . count($exited_employees_with_scores[0]->top_scores) . ' scores');

                    // Debug the first top score if available
                    if (count($exited_employees_with_scores[0]->top_scores) > 0) {
                        error_log('First top score: ' . print_r($exited_employees_with_scores[0]->top_scores[0], true));
                    }
                } else {
                    error_log('top_scores property does not exist');
                }
            }

            // Prepare response
            $response = array(
                'success' => true,
                'data' => array(
                    'employees' => $exited_employees_with_scores,
                ),
            );

            // Debug the response
            error_log('Response: ' . json_encode($response));

            // Send the response
            echo json_encode($response);
        } catch (Exception $e) {
            error_log('Exception in get_player_leaderboard: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'data' => 'Si è verificato un errore durante il caricamento della leaderboard: ' . $e->getMessage(),
            ));
        }
        break;

    case 'get_user_leaderboard':
        // Get user leaderboard
        error_log('Custom AJAX handler: get_user_leaderboard action called');

        try {
            // Include the DB class if not already included
            if (!class_exists('Jo_Exit_DB')) {
                error_log('Jo_Exit_DB class not found, including it now');
                require_once dirname(__FILE__) . '/../includes/class-jo-exit-db.php';
            } else {
                error_log('Jo_Exit_DB class already loaded');
            }

            // Include the activator class to ensure the database structure is up to date
            if (!class_exists('Jo_Exit_Activator')) {
                error_log('Jo_Exit_Activator class not found, including it now');
                require_once dirname(__FILE__) . '/../includes/class-jo-exit-activator.php';
                Jo_Exit_Activator::update_database_structure();
            } else {
                error_log('Jo_Exit_Activator class already loaded');
                Jo_Exit_Activator::update_database_structure();
            }

            // Get the limit parameter (default: 20)
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;

            // Check if player_scores table exists and has data
            global $wpdb;
            $player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");

            if (!$table_exists) {
                error_log('Custom AJAX handler: Player scores table does not exist');

                // Create the table
                require_once dirname(__FILE__) . '/../includes/class-jo-exit-activator.php';
                Jo_Exit_Activator::update_database_structure();

                // Non aggiungiamo più record di test automaticamente
                error_log('Custom AJAX handler: Player scores table created');
            }

            // Get user leaderboard
            $user_leaderboard = Jo_Exit_DB::get_user_leaderboard($limit);
            error_log('Custom AJAX handler: Found ' . count($user_leaderboard) . ' users for leaderboard');

            // Debug the first user if available
            if (count($user_leaderboard) > 0) {
                error_log('First user in leaderboard: ' . print_r($user_leaderboard[0], true));
            }

            // Prepare response
            $response = array(
                'success' => true,
                'data' => array(
                    'users' => $user_leaderboard,
                ),
            );

            // Debug the response
            error_log('Response: ' . json_encode($response));

            // Send the response
            echo json_encode($response);
        } catch (Exception $e) {
            error_log('Exception in get_user_leaderboard: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'data' => 'Si è verificato un errore durante il caricamento della classifica utenti: ' . $e->getMessage(),
            ));
        }
        break;

    default:
        echo json_encode(array(
            'success' => false,
            'data' => 'Invalid action',
        ));
        break;
}

exit;
