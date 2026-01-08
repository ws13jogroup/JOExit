<?php
/**
 * Template for the Registration screen.
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
        <div class="jo-exit-header-title">JO Exit - Registrazione</div>
        <div class="jo-exit-header-icon" id="jo-exit-info-icon" data-screen="info">
            <i class="fas fa-info-circle"></i>
        </div>
    </div>

    <div class="jo-exit-content">
        <div class="jo-exit-register-container">
            <h2>Crea un nuovo account</h2>
            <form id="jo-exit-register-form" method="post">
                <div class="jo-exit-form-group">
                    <label for="jo-exit-reg-username">Username</label>
                    <input type="text" id="jo-exit-reg-username" name="username" required>
                </div>
                <div class="jo-exit-form-group">
                    <label for="jo-exit-reg-email">Email</label>
                    <input type="email" id="jo-exit-reg-email" name="email" required>
                </div>
                <div class="jo-exit-form-group">
                    <label for="jo-exit-reg-password">Password</label>
                    <input type="password" id="jo-exit-reg-password" name="password" required>
                </div>
                <div class="jo-exit-form-group">
                    <label for="jo-exit-reg-avatar">Avatar</label>
                    <div class="jo-exit-avatar-container">
                        <img id="jo-exit-avatar-preview" src="" alt="Avatar Preview" style="display: none;">
                        <input type="hidden" id="jo-exit-reg-avatar" name="avatar">
                        <div class="jo-exit-avatar-buttons">
                            <button type="button" id="jo-exit-upload-avatar" class="jo-exit-button">Carica Immagine</button>
                            <span class="jo-exit-or">o</span>
                            <button type="button" id="jo-exit-generate-avatar" class="jo-exit-button">Genera Avatar Casuale</button>
                        </div>
                    </div>
                </div>
                <div class="jo-exit-form-group">
                    <button type="submit" class="jo-exit-button">Registrati</button>
                </div>
                <div class="jo-exit-form-message" id="jo-exit-register-message"></div>
            </form>
            <div class="jo-exit-login-link">
                <p>Hai già un account? <a href="#" id="jo-exit-login-link">Accedi</a></p>
            </div>
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
            <div class="jo-exit-nav-icon">
                <i class="fas fa-user"></i>
            </div>
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
            } else {
                window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=account';
            }
        });

        // Handle login link
        $('#jo-exit-login-link').on('click', function(e) {
            e.preventDefault();
            window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=account';
        });

        // Handle register form submission
        $('#jo-exit-register-form').on('submit', function(e) {
            e.preventDefault();
            if (window.JoExit && window.JoExit.registerUser) {
                const username = $('#jo-exit-reg-username').val();
                const email = $('#jo-exit-reg-email').val();
                const password = $('#jo-exit-reg-password').val();
                const avatar = $('#jo-exit-reg-avatar').val();
                window.JoExit.registerUser(username, email, password, avatar);
            }
        });

        // Handle avatar upload
        $('#jo-exit-upload-avatar').on('click', function() {
            if (window.JoExit && window.JoExit.uploadAvatar) {
                window.JoExit.uploadAvatar();
            }
        });

        // Handle avatar generation
        $('#jo-exit-generate-avatar').on('click', function() {
            if (window.JoExit && window.JoExit.generateRandomAvatar) {
                window.JoExit.generateRandomAvatar();
            }
        });
    });
</script>
