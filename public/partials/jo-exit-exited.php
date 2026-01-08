<?php
/**
 * Exited employees screen template for JOb Exit Plugin
 *
 * @since      1.0.0
 */

// Ottieni direttamente i dipendenti licenziati dal database
global $wpdb;
$table_name = $wpdb->prefix . 'jo_exit_employees';
$query = "SELECT * FROM $table_name WHERE status = 'exit' ORDER BY exit_date DESC";
$exited_employees = $wpdb->get_results($query);

// Verifica se la colonna final_score esiste nella tabella
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'final_score'");
if (empty($columns)) {
    // La colonna non esiste, aggiungiamola
    error_log('JOb Exit: La colonna final_score non esiste, la aggiungo');
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN final_score int(11) DEFAULT 0 AFTER score");

    // Aggiorna il punteggio finale per tutti i dipendenti in exit
    if (!empty($exited_employees)) {
        foreach ($exited_employees as $employee) {
            // Calcola il punteggio finale (exit points + anzianità)
            $final_score = intval($employee->score); // Exit points attuali
            $years = 0;

            // Calcola gli anni di anzianità se disponibili
            if (!empty($employee->hire_year) && is_numeric($employee->hire_year)) {
                $current_year = intval(date('Y'));
                $hire_year = intval($employee->hire_year);

                // Validate hire year is reasonable
                if ($hire_year > 1900 && $hire_year <= $current_year) {
                    $years = $current_year - $hire_year;
                }
            }

            // Aggiungi gli anni di anzianità al punteggio finale
            $final_score += $years;

            // Aggiorna il punteggio finale nel database
            $wpdb->update(
                $table_name,
                array('final_score' => $final_score),
                array('id' => $employee->id)
            );

            // Aggiorna anche l'oggetto employee per l'uso in questa pagina
            $employee->final_score = $final_score;
        }
    }
}

// Debug - Registra i risultati della query
error_log('JOb Exit: Query per dipendenti usciti: ' . $query);
error_log('JOb Exit: Numero di dipendenti usciti trovati: ' . count($exited_employees));
if (!empty($exited_employees)) {
    error_log('JOb Exit: Primo dipendente uscito: ' . print_r($exited_employees[0], true));

    // Verifica se il punteggio finale è impostato
    if (isset($exited_employees[0]->final_score)) {
        error_log('JOb Exit: Punteggio finale del primo dipendente: ' . $exited_employees[0]->final_score);
    } else {
        error_log('JOb Exit: Punteggio finale non impostato per il primo dipendente');
    }

    // Verifica se l'anno di assunzione è impostato
    if (isset($exited_employees[0]->hire_year)) {
        error_log('JOb Exit: Anno di assunzione del primo dipendente: ' . $exited_employees[0]->hire_year);
        $years = date('Y') - intval($exited_employees[0]->hire_year);
        error_log('JOb Exit: Anni di anzianità calcolati: ' . $years);
    } else {
        error_log('JOb Exit: Anno di assunzione non impostato per il primo dipendente');
    }
}

// Ottieni i top player per ogni dipendente uscito
$player_scores_table = $wpdb->prefix . 'jo_exit_player_scores';
$top_players = array();

// Verifica se la tabella player_scores esiste
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$player_scores_table'");
error_log('JOb Exit: Tabella player_scores esiste: ' . ($table_exists ? 'Sì' : 'No'));

if ($table_exists && !empty($exited_employees)) {
    foreach ($exited_employees as $employee) {
        // Query per ottenere il top player per questo dipendente
        $player_query = $wpdb->prepare(
            "SELECT ps.*, u.display_name, u.user_login
            FROM $player_scores_table ps
            JOIN {$wpdb->users} u ON ps.user_id = u.ID
            WHERE ps.employee_id = %d
            ORDER BY ps.player_points DESC
            LIMIT 1",
            $employee->id
        );

        error_log('JOb Exit: Query per top player del dipendente ' . $employee->id . ': ' . $player_query);

        $top_player = $wpdb->get_row($player_query);

        if ($top_player) {
            error_log('JOb Exit: Top player trovato per dipendente ' . $employee->id . ': ' . print_r($top_player, true));
            $top_players[$employee->id] = $top_player;
        } else {
            error_log('JOb Exit: Nessun top player trovato per dipendente ' . $employee->id);
        }
    }
}

error_log('JOb Exit: Numero di top players trovati: ' . count($top_players));

// Funzione per formattare la data
function format_exit_date($date_string) {
    if (!$date_string) return 'N/D';

    try {
        $date = new DateTime($date_string);
        return $date->format('d/m/Y');
    } catch (Exception $e) {
        return 'N/D';
    }
}

// Forza la visualizzazione degli errori per il debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<div class="jo-exit-container" style="--theme-color: <?php echo esc_attr($atts['theme_color']); ?>">
    <div class="jo-exit-header">
        <div class="jo-exit-header-title">JOb Exit - <?php echo esc_html__('Exit', 'job-exit-plugin'); ?></div>
        <div class="jo-exit-header-icon" id="jo-exit-info-icon" data-screen="info">
            <i class="fas fa-info-circle"></i>
        </div>
    </div>

    <div class="jo-exit-content">
        <div class="jo-exit-exit-table-container">
            <h2><?php echo esc_html__('Exited Employees', 'job-exit-plugin'); ?></h2>



            <?php if (empty($exited_employees)) : ?>
                <div class="jo-exit-no-data"><?php echo esc_html__('No employees have exited yet.', 'job-exit-plugin'); ?></div>
            <?php else : ?>
                <div class="jo-exit-exited">
                    <?php foreach ($exited_employees as $employee) :
                        $first_name = isset($employee->first_name) ? $employee->first_name : '';
                        $last_name = isset($employee->last_name) ? $employee->last_name : '';
                        $name = trim("$first_name $last_name");
                        $name = empty($name) ? 'Dipendente ' . $employee->id : $name;
                        $score = isset($employee->score) ? intval($employee->score) : 0;
                        $exit_date = isset($employee->exit_date) ? format_exit_date($employee->exit_date) : 'N/D';
                        $photo_url = isset($employee->photo_url) ? $employee->photo_url : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMDAgMjAwIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2NjY2NjYyIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjM2IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSIgZmlsbD0iIzY2NjY2NiI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';
                        $company_code = isset($employee->company_code) ? $employee->company_code : '';
                    ?>
                        <div class="jo-exit-exited-item">
                            <div class="jo-exit-exited-top-info">
                                <div class="jo-exit-exited-date"><?php echo esc_html__('Exited on:', 'job-exit-plugin'); ?> <?php echo esc_html($exit_date); ?></div>
                                <?php
                                // Mostra il punteggio finale se disponibile, altrimenti mostra il punteggio normale
                                $display_score = isset($employee->final_score) && $employee->final_score > 0 ? $employee->final_score : $score;
                                error_log('JOb Exit: Dipendente ID ' . $employee->id . ' - Score: ' . $score . ', Final Score: ' . (isset($employee->final_score) ? $employee->final_score : 'non impostato') . ', Display Score: ' . $display_score);
                                ?>
                                <div class="jo-exit-exited-score"><?php echo esc_html($display_score); ?></div>
                            </div>
                            <div class="jo-exit-exited-bottom-info">
                                <img src="<?php echo esc_url($photo_url); ?>" class="jo-exit-exited-photo" alt="<?php echo esc_attr($name); ?>">
                                <div class="jo-exit-exited-info">
                                    <div class="jo-exit-exited-name"><?php echo esc_html($name); ?></div>
                                    <div class="jo-exit-exited-code"><?php echo esc_html($company_code); ?></div>
                                    <?php if (isset($employee->hire_year)) :
                                        $years = date('Y') - intval($employee->hire_year);
                                        $year_label = ($years === 1) ? esc_html__('year', 'job-exit-plugin') : esc_html__('years', 'job-exit-plugin');
                                    ?>
                                        <div class="jo-exit-exited-years"><?php echo esc_html__('Seniority:', 'job-exit-plugin'); ?> <?php echo esc_html($years); ?> <?php echo esc_html($year_label); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php
                            // Forza il calcolo del punteggio finale se non è impostato
                            if (!isset($employee->final_score) || $employee->final_score <= 0) {
                                $final_score = intval($employee->score); // Exit points attuali
                                $years = 0;

                                // Calcola gli anni di anzianità se disponibili
                                if (!empty($employee->hire_year) && is_numeric($employee->hire_year)) {
                                    $current_year = intval(date('Y'));
                                    $hire_year = intval($employee->hire_year);

                                    // Validate hire year is reasonable
                                    if ($hire_year > 1900 && $hire_year <= $current_year) {
                                        $years = $current_year - $hire_year;
                                    }
                                }

                                // Aggiungi gli anni di anzianità al punteggio finale
                                $final_score += $years;
                                $employee->final_score = $final_score;

                                error_log('JOb Exit: Punteggio finale calcolato manualmente per dipendente ID ' . $employee->id . ': ' . $final_score);
                            }

                            // Mostra sempre la sezione del punteggio finale
                            ?>
                            <div class="jo-exit-exited-final-score-info">
                                <div class="jo-exit-exited-final-score-label"><?php echo esc_html__('Final Score:', 'job-exit-plugin'); ?> <?php echo esc_html($employee->final_score); ?> <?php echo esc_html__('points', 'job-exit-plugin'); ?></div>
                                <div class="jo-exit-exited-final-score-desc">
                                    <?php
                                    // Calcola i componenti del punteggio finale
                                    $exit_points = isset($employee->score) ? intval($employee->score) : 0;
                                    $years = isset($employee->hire_year) ? (date('Y') - intval($employee->hire_year)) : 0;
                                    ?>
                                    (<?php echo esc_html__('Exit Points:', 'job-exit-plugin'); ?> <?php echo esc_html($exit_points); ?> + <?php echo esc_html__('Seniority:', 'job-exit-plugin'); ?> <?php echo esc_html($years); ?>)
                                </div>
                            </div>

                            <?php if (isset($top_players[$employee->id])) :
                                $top_player = $top_players[$employee->id];
                                $player_name = isset($top_player->display_name) ? $top_player->display_name : $top_player->user_login;
                                $player_points = isset($top_player->player_points) ? intval($top_player->player_points) : 0;
                            ?>
                            <div class="jo-exit-exited-winner">
                                <div class="jo-exit-exited-winner-label"><?php echo esc_html__('Top Player:', 'job-exit-plugin'); ?></div>
                                <div class="jo-exit-exited-winner-info">
                                    <div class="jo-exit-exited-winner-name"><?php echo esc_html($player_name); ?></div>
                                    <div class="jo-exit-exited-winner-points"><?php echo esc_html($player_points); ?> <?php echo esc_html__('Player Points', 'job-exit-plugin'); ?></div>
                                </div>
                            </div>
                            <?php else : ?>
                            <div class="jo-exit-exited-winner jo-exit-exited-no-winner">
                                <div class="jo-exit-exited-winner-label"><?php echo esc_html__('Top Player:', 'job-exit-plugin'); ?></div>
                                <div class="jo-exit-exited-winner-info">
                                    <div class="jo-exit-exited-winner-name"><?php echo esc_html__('No winner', 'job-exit-plugin'); ?></div>
                                    <div class="jo-exit-exited-winner-points">0 <?php echo esc_html__('Player Points', 'job-exit-plugin'); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="jo-exit-bottom-nav">
        <div class="jo-exit-nav-item" data-screen="home">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-home"></i>
            </div>
            <div><?php echo esc_html__('Home', 'job-exit-plugin'); ?></div>
        </div>

        <div class="jo-exit-nav-item" data-screen="leaderboard">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div><?php echo esc_html__('Points', 'job-exit-plugin'); ?></div>
        </div>

        <div class="jo-exit-nav-item active" data-screen="exited">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-person-walking-arrow-right"></i>
            </div>
            <div><?php echo esc_html__('Exit', 'job-exit-plugin'); ?></div>
        </div>

        <div class="jo-exit-nav-item" data-screen="leaderboard-trophy">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div><?php echo esc_html__('Leaderboard', 'job-exit-plugin'); ?></div>
        </div>

        <div class="jo-exit-nav-item" data-screen="account">
            <?php if (is_user_logged_in()) :
                $current_user = wp_get_current_user();
                $avatar_url = get_user_meta($current_user->ID, 'jo_exit_avatar', true);
                if (empty($avatar_url)) {
                    $avatar_url = get_avatar_url($current_user->ID);
                }
            ?>
                <div class="jo-exit-nav-avatar">
                    <img src="<?php echo strpos($avatar_url, 'data:image/') === 0 ? $avatar_url : esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                </div>
                <div><?php echo esc_html__('Account', 'job-exit-plugin'); ?></div>
            <?php else : ?>
                <div class="jo-exit-nav-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div><?php echo esc_html__('Account', 'job-exit-plugin'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Handle info icon click
        $('#jo-exit-info-icon').on('click', function() {
            window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=info';
        });

        // Handle navigation clicks
        $('.jo-exit-nav-item').on('click', function() {
            const screen = $(this).data('screen');

            // Skip if clicking on the current active screen
            if ($(this).hasClass('active')) {
                return;
            }

            // Redirect to the selected screen
            window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=' + screen;
        });
    });
</script>
