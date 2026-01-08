<?php
/**
 * Template for the Edit Profile screen content.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/public/partials
 */

// Ensure user is logged in
if (!is_user_logged_in()) {
    echo '<div class="jo-exit-error">' . esc_html__('You must be logged in to edit your profile.', 'job-exit-plugin') . '</div>';
    return;
}

$current_user = wp_get_current_user();
$avatar_url = get_user_meta($current_user->ID, 'jo_exit_avatar', true);
if (empty($avatar_url)) {
    $avatar_url = get_avatar_url($current_user->ID);
}
?>

<div class="jo-exit-edit-profile-container">
    <h2><?php echo esc_html__('Edit Profile', 'job-exit-plugin'); ?></h2>

    <div class="jo-exit-appearance-section">
        <h3><?php echo esc_html__('Appearance', 'job-exit-plugin'); ?></h3>
        <div class="jo-exit-theme-toggle">
            <span class="jo-exit-dark-mode-label"><?php echo esc_html__('Dark Mode', 'job-exit-plugin'); ?></span>
            <label class="jo-exit-switch">
                <input type="checkbox" id="jo-exit-dark-mode-toggle" <?php echo (get_user_meta(get_current_user_id(), 'jo_exit_dark_mode', true) === 'on') ? 'checked' : ''; ?>>
                <span class="jo-exit-slider round"></span>
            </label>
        </div>
    </div>

    <div id="jo-exit-edit-profile-message" class="jo-exit-form-message"></div>

    <div class="jo-exit-form-group">
        <label><?php echo esc_html__('Username', 'job-exit-plugin'); ?></label>
        <input type="text" value="<?php echo esc_attr($current_user->user_login); ?>" disabled>
        <div class="jo-exit-form-help"><?php echo esc_html__('Username cannot be changed.', 'job-exit-plugin'); ?></div>
    </div>

    <div class="jo-exit-form-group">
        <label for="jo-exit-edit-email"><?php echo esc_html__('Email', 'job-exit-plugin'); ?></label>
        <input type="email" id="jo-exit-edit-email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
    </div>

    <div class="jo-exit-form-group">
        <label for="jo-exit-edit-password"><?php echo esc_html__('New Password', 'job-exit-plugin'); ?></label>
        <input type="password" id="jo-exit-edit-password" name="password">
        <div class="jo-exit-form-help"><?php echo esc_html__('Leave empty to keep your current password.', 'job-exit-plugin'); ?></div>
    </div>

    <div class="jo-exit-form-group">
        <label><?php echo esc_html__('Avatar', 'job-exit-plugin'); ?></label>
        <div class="jo-exit-avatar-container">
            <img id="jo-exit-avatar-preview" src="<?php echo strpos($avatar_url, 'data:image/') === 0 ? $avatar_url : esc_url($avatar_url); ?>" alt="<?php echo esc_attr__('Avatar Preview', 'job-exit-plugin'); ?>">
            <input type="hidden" id="jo-exit-edit-avatar" name="avatar" value="<?php echo strpos($avatar_url, 'data:image/') === 0 ? $avatar_url : esc_attr($avatar_url); ?>">

            <div class="jo-exit-avatar-buttons">
                <button type="button" class="jo-exit-button jo-exit-button-secondary" onclick="JoExit.uploadAvatar()"><?php echo esc_html__('Upload Image', 'job-exit-plugin'); ?></button>
                <span class="jo-exit-or"><?php echo esc_html__('or', 'job-exit-plugin'); ?></span>
                <button type="button" class="jo-exit-button jo-exit-button-secondary" onclick="JoExit.generateRandomAvatar()"><?php echo esc_html__('Generate Random', 'job-exit-plugin'); ?></button>
            </div>

            <div class="jo-exit-form-help jo-exit-avatar-help">
                <?php echo esc_html__('If you have problems uploading an image, you can generate a random avatar.', 'job-exit-plugin'); ?>
            </div>
        </div>
    </div>

    <button type="button" id="jo-exit-update-profile-button" class="jo-exit-button"><?php echo esc_html__('Update Profile', 'job-exit-plugin'); ?></button>

    <div class="jo-exit-account-link">
        <a href="#"><?php echo esc_html__('Back to Profile', 'job-exit-plugin'); ?></a>
    </div>

    <div class="jo-exit-delete-account-section">
        <h3><?php echo esc_html__('Delete Account', 'job-exit-plugin'); ?></h3>
        <p><?php echo esc_html__('Warning: this action is irreversible. All your data will be permanently deleted.', 'job-exit-plugin'); ?></p>
        <button type="button" id="jo-exit-delete-account-button" class="jo-exit-button jo-exit-button-danger"><?php echo esc_html__('Delete Account', 'job-exit-plugin'); ?></button>
    </div>
</div>
