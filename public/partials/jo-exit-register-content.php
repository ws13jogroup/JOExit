<?php
/**
 * Template for the Registration screen content.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/public/partials
 */
?>

<div class="jo-exit-register-container">
    <div class="jo-exit-register-form">
        <h2><?php echo esc_html__('Registration', 'job-exit-plugin'); ?></h2>

        <div id="jo-exit-register-message" class="jo-exit-form-message"></div>

        <div class="jo-exit-form-group">
            <label for="jo-exit-reg-username"><?php echo esc_html__('Username', 'job-exit-plugin'); ?></label>
            <input type="text" id="jo-exit-reg-username" name="username" required>
        </div>

        <div class="jo-exit-form-group">
            <label for="jo-exit-reg-email"><?php echo esc_html__('Email', 'job-exit-plugin'); ?></label>
            <input type="email" id="jo-exit-reg-email" name="email" required>
        </div>

        <div class="jo-exit-form-group">
            <label for="jo-exit-reg-password"><?php echo esc_html__('Password', 'job-exit-plugin'); ?></label>
            <input type="password" id="jo-exit-reg-password" name="password" required>
            <div class="jo-exit-form-help"><?php echo esc_html__('Password must be at least 8 characters long.', 'job-exit-plugin'); ?></div>
        </div>

        <div class="jo-exit-form-group">
            <label><?php echo esc_html__('Avatar', 'job-exit-plugin'); ?></label>
            <div class="jo-exit-avatar-container">
                <img id="jo-exit-avatar-preview" src="" alt="<?php echo esc_attr__('Avatar Preview', 'job-exit-plugin'); ?>" style="display:none;">
                <input type="hidden" id="jo-exit-reg-avatar" name="avatar">

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

        <button type="button" id="jo-exit-register-button" class="jo-exit-button"><?php echo esc_html__('Register', 'job-exit-plugin'); ?></button>

        <div class="jo-exit-login-link">
            <?php echo esc_html__('Already have an account?', 'job-exit-plugin'); ?> <a href="#"><?php echo esc_html__('Login', 'job-exit-plugin'); ?></a>
        </div>
    </div>
</div>
