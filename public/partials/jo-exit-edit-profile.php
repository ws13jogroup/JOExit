<?php
/**
 * Template for the Edit Profile screen.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/public/partials
 */

// Ensure user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/?page=jo-exit&screen=account'));
    exit;
}

$current_user = wp_get_current_user();
$avatar_url = get_user_meta($current_user->ID, 'jo_exit_avatar', true);
if (empty($avatar_url)) {
    $avatar_url = get_avatar_url($current_user->ID);
}
?>

<div class="jo-exit-container">
    <div class="jo-exit-header">
        <div class="jo-exit-header-title">JOb Exit - <?php echo esc_html__('Edit Profile', 'job-exit-plugin'); ?></div>
        <div class="jo-exit-header-icon" id="jo-exit-info-icon" data-screen="info">
            <i class="fas fa-info-circle"></i>
        </div>
    </div>

    <div class="jo-exit-content">
        <div class="jo-exit-edit-profile-container">
            <h2><?php echo esc_html__('Edit your profile', 'job-exit-plugin'); ?></h2>

            <div class="jo-exit-appearance-section" style="display: block !important; visibility: visible !important; opacity: 1 !important; background-color: #f8f9fa !important; padding: 15px !important; border-radius: 8px !important; margin-bottom: 20px !important; border: 2px solid #e1e4e8 !important;">
                <h3 style="display: block !important; visibility: visible !important; opacity: 1 !important; margin-top: 0 !important; margin-bottom: 15px !important; border-bottom: 1px solid #e1e4e8 !important; padding-bottom: 10px !important; font-size: 18px !important; color: #333 !important;"><?php echo esc_html__('Appearance', 'job-exit-plugin'); ?></h3>
                <div class="jo-exit-theme-toggle" style="display: flex !important; align-items: center !important; justify-content: space-between !important; visibility: visible !important; opacity: 1 !important;">
                    <span style="font-weight: bold !important; display: inline-block !important; visibility: visible !important; opacity: 1 !important;"><?php echo esc_html__('Dark Mode', 'job-exit-plugin'); ?></span>
                    <label class="jo-exit-switch" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;">
                        <input type="checkbox" id="jo-exit-dark-mode-toggle" <?php echo (get_user_meta(get_current_user_id(), 'jo_exit_dark_mode', true) === 'on') ? 'checked' : ''; ?> style="visibility: visible !important; opacity: 1 !important;">
                        <span class="jo-exit-slider round" style="display: block !important; visibility: visible !important; opacity: 1 !important;"></span>
                    </label>
                </div>
            </div>

            <form id="jo-exit-edit-profile-form" method="post">
                <div class="jo-exit-form-group">
                    <label for="jo-exit-edit-username"><?php echo esc_html__('Username', 'job-exit-plugin'); ?></label>
                    <input type="text" id="jo-exit-edit-username" name="username" value="<?php echo esc_attr($current_user->user_login); ?>" disabled>
                    <p class="jo-exit-form-help"><?php echo esc_html__('Username cannot be changed.', 'job-exit-plugin'); ?></p>
                </div>
                <div class="jo-exit-form-group">
                    <label for="jo-exit-edit-email"><?php echo esc_html__('Email', 'job-exit-plugin'); ?></label>
                    <input type="email" id="jo-exit-edit-email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
                </div>
                <div class="jo-exit-form-group">
                    <label for="jo-exit-edit-password"><?php echo esc_html__('New Password (leave empty to keep current)', 'job-exit-plugin'); ?></label>
                    <input type="password" id="jo-exit-edit-password" name="password">
                </div>
                <div class="jo-exit-form-group">
                    <label for="jo-exit-edit-avatar"><?php echo esc_html__('Avatar', 'job-exit-plugin'); ?></label>
                    <div class="jo-exit-avatar-container">
                        <img id="jo-exit-avatar-preview" src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr__('Avatar Preview', 'job-exit-plugin'); ?>">
                        <input type="hidden" id="jo-exit-edit-avatar" name="avatar" value="<?php echo esc_attr($avatar_url); ?>">
                        <div class="jo-exit-avatar-buttons">
                            <button type="button" id="jo-exit-upload-avatar" class="jo-exit-button"><?php echo esc_html__('Upload Image', 'job-exit-plugin'); ?></button>
                            <span class="jo-exit-or"><?php echo esc_html__('or', 'job-exit-plugin'); ?></span>
                            <button type="button" id="jo-exit-generate-avatar" class="jo-exit-button"><?php echo esc_html__('Generate Random Avatar', 'job-exit-plugin'); ?></button>
                        </div>
                    </div>
                </div>
                <div class="jo-exit-form-group">
                    <button type="submit" class="jo-exit-button"><?php echo esc_html__('Save Changes', 'job-exit-plugin'); ?></button>
                    <button type="button" id="jo-exit-cancel-edit" class="jo-exit-button jo-exit-button-secondary"><?php echo esc_html__('Cancel', 'job-exit-plugin'); ?></button>
                </div>
                <div class="jo-exit-form-message" id="jo-exit-edit-profile-message"></div>
            </form>
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
            <div class="jo-exit-nav-icon jo-exit-nav-avatar">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
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

        // Handle edit profile form submission
        $('#jo-exit-edit-profile-form').on('submit', function(e) {
            e.preventDefault();
            if (window.JoExit && window.JoExit.updateUserProfile) {
                const email = $('#jo-exit-edit-email').val();
                const password = $('#jo-exit-edit-password').val();
                const avatar = $('#jo-exit-edit-avatar').val();
                window.JoExit.updateUserProfile(email, password, avatar);
            }
        });

        // Handle cancel button
        $('#jo-exit-cancel-edit').on('click', function() {
            window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=account';
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

        // Handle dark mode toggle
        $('#jo-exit-dark-mode-toggle').on('change', function() {
            if (window.JoExit && window.JoExit.toggleDarkMode) {
                window.JoExit.toggleDarkMode($(this).is(':checked') ? 'on' : 'off');
            }
        });
    });
</script>
