<?php
/**
 * Template for the Account screen.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/public/partials
 */
?>

<div class="jo-exit-container">
    <div class="jo-exit-header">
        <div class="jo-exit-header-title">JO Exit - Account</div>
        <div class="jo-exit-header-icon" id="jo-exit-info-icon" data-screen="info">
            <i class="fas fa-info-circle"></i>
        </div>
    </div>

    <div class="jo-exit-content">
        <div class="jo-exit-account-container">
            <?php if (is_user_logged_in()) : ?>
                <?php
                $current_user = wp_get_current_user();
                $avatar_url = get_user_meta($current_user->ID, 'jo_exit_avatar', true);
                if (empty($avatar_url)) {
                    $avatar_url = get_avatar_url($current_user->ID);
                }
                ?>
                <div class="jo-exit-account-profile">
                    <div class="jo-exit-account-avatar">
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                    </div>
                    <div class="jo-exit-account-info">
                        <h2><?php echo esc_html($current_user->display_name); ?></h2>
                        <p><?php echo esc_html($current_user->user_email); ?></p>
                    </div>
                    <div class="jo-exit-account-actions">
                        <button class="jo-exit-button" id="jo-exit-edit-profile">Modifica Profilo</button>
                    </div>
                </div>

                <div class="jo-exit-account-section">
                    <button class="jo-exit-button jo-exit-button-logout" id="jo-exit-logout">Esci</button>
                </div>

                <div class="jo-exit-account-section">
                    <h3>Exit Points Assegnati</h3>
                    <div class="jo-exit-account-points-total">
                        <span id="jo-exit-total-points">Caricamento...</span>
                    </div>
                </div>

                <div class="jo-exit-account-section">
                    <h3>Dipendenti Votati</h3>
                    <div class="jo-exit-account-voted-employees" id="jo-exit-voted-employees">
                        <p>Caricamento...</p>
                    </div>
                </div>
            <?php else : ?>
                <div class="jo-exit-login-form">
                    <h2>Accedi</h2>
                    <form id="jo-exit-login-form" method="post">
                        <div class="jo-exit-form-group">
                            <label for="jo-exit-username">Username o Email</label>
                            <input type="text" id="jo-exit-username" name="username" required>
                        </div>
                        <div class="jo-exit-form-group">
                            <label for="jo-exit-password">Password</label>
                            <input type="password" id="jo-exit-password" name="password" required>
                        </div>
                        <div class="jo-exit-form-group">
                            <button type="submit" class="jo-exit-button">Accedi</button>
                        </div>
                        <div class="jo-exit-form-message" id="jo-exit-login-message"></div>
                    </form>
                    <div class="jo-exit-register-link">
                        <p>Non hai un account? <a href="#" id="jo-exit-register-link">Registrati</a></p>
                    </div>
                    <div class="jo-exit-register-link">
                        <p>Non ricordi le tue credenziali? <a href="/wp-login.php?action=lostpassword" id="jo-exit-register-link">Recupera Password</a></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="jo-exit-bottom-nav">
        <div class="jo-exit-nav-item" data-screen="home">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-home"></i>
            </div>
            <div>Home</div>
        </div>

        <div class="jo-exit-nav-item" data-screen="leaderboard">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>Points</div>
        </div>

        <div class="jo-exit-nav-item" data-screen="exited">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-person-walking-arrow-right"></i>
            </div>
            <div>Exit</div>
        </div>

        <div class="jo-exit-nav-item" data-screen="leaderboard-trophy">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div>Leaderboard</div>
        </div>

        <div class="jo-exit-nav-item active" data-screen="account">
            <?php if (is_user_logged_in()) :
                $current_user = wp_get_current_user();
                $avatar_url = get_user_meta($current_user->ID, 'jo_exit_avatar', true);
                if (empty($avatar_url)) {
                    $avatar_url = get_avatar_url($current_user->ID);
                }
            ?>
                <div class="jo-exit-nav-icon jo-exit-nav-avatar">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                </div>
            <?php else : ?>
                <div class="jo-exit-nav-icon">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <div>Account</div>
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

            // Handle new menu items
            if (screen === 'leaderboard-trophy') {
                alert('Leaderboard feature coming soon!');
                return;
            }

            // If not account, redirect to the full app with the selected screen
            if (screen !== 'account') {
                window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=' + screen;
            }
        });

        <?php if (is_user_logged_in()) : ?>
        // Load user voting data
        if (window.JoExit && window.JoExit.loadUserVotingData) {
            window.JoExit.loadUserVotingData();
        }

        // Handle edit profile button
        $('#jo-exit-edit-profile').on('click', function() {
            window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=edit-profile';
        });

        // Handle logout button
        $('#jo-exit-logout').on('click', function() {
            if (window.JoExit && window.JoExit.logoutUser) {
                window.JoExit.logoutUser();
            }
        });
        <?php else : ?>
        // Handle login form submission
        $('#jo-exit-login-form').on('submit', function(e) {
            e.preventDefault();
            if (window.JoExit && window.JoExit.loginUser) {
                const username = $('#jo-exit-username').val();
                const password = $('#jo-exit-password').val();
                window.JoExit.loginUser(username, password);
            }
        });

        // Handle register link
        $('#jo-exit-register-link').on('click', function(e) {
            e.preventDefault();
            window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=register';
        });
        <?php endif; ?>
    });
</script>