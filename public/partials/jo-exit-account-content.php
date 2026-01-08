<?php
/**
 * Template for the Account screen content.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    Jo_Exit
 * @subpackage Jo_Exit/public/partials
 */
?>

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
                <img src="<?php echo strpos($avatar_url, 'data:image/') === 0 ? $avatar_url : esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
            </div>
            <div class="jo-exit-account-info">
                <h2><?php echo esc_html($current_user->display_name); ?></h2>
                <p><?php echo esc_html($current_user->user_email); ?></p>
                <?php
                // Calcola i punti esperienza dell'utente
                if (class_exists('Jo_Exit_User')) {
                    $exp_points = Jo_Exit_User::calculate_user_exp($current_user->ID);
                    echo '<p class="jo-exit-account-exp">' . esc_html__('Exp:', 'job-exit-plugin') . ' ' . esc_html($exp_points) . '</p>';
                }
                ?>
            </div>
            <div class="jo-exit-account-buttons">
                <button class="jo-exit-button jo-exit-button-edit" id="jo-exit-edit-profile"><?php echo esc_html__('Edit Profile', 'job-exit-plugin'); ?></button>
                <button class="jo-exit-button jo-exit-button-logout" id="jo-exit-logout"><?php echo esc_html__('Logout', 'job-exit-plugin'); ?></button>
            </div>
        </div>

        <div class="jo-exit-account-section">
            <h3><?php echo esc_html__('Voted Employees', 'job-exit-plugin'); ?></h3>
            <div class="jo-exit-account-voted-employees" id="jo-exit-voted-employees">
                <p><?php echo esc_html__('Loading...', 'job-exit-plugin'); ?></p>
            </div>
        </div>
    <?php else : ?>
        <div class="jo-exit-login-form">
            <h2><?php echo esc_html__('Login', 'job-exit-plugin'); ?></h2>
            <form id="jo-exit-login-form" method="post">
                <div class="jo-exit-form-group">
                    <label for="jo-exit-username"><?php echo esc_html__('Username or Email', 'job-exit-plugin'); ?></label>
                    <input type="text" id="jo-exit-username" name="username" required>
                </div>
                <div class="jo-exit-form-group">
                    <label for="jo-exit-password"><?php echo esc_html__('Password', 'job-exit-plugin'); ?></label>
                    <input type="password" id="jo-exit-password" name="password" required>
                </div>
                <div class="jo-exit-form-group">
                    <button type="submit" class="jo-exit-button"><?php echo esc_html__('Login', 'job-exit-plugin'); ?></button>
                </div>
                <div class="jo-exit-form-message" id="jo-exit-login-message"></div>
            </form>
            <div class="jo-exit-register-link">
                <p><?php echo esc_html__('Don\'t have an account?', 'job-exit-plugin'); ?> <a href="#" id="jo-exit-register-link"><?php echo esc_html__('Register', 'job-exit-plugin'); ?></a></p>
            </div>
            <div class="jo-exit-register-link">
                        <p>Non ricordi le tue credenziali? <a href="/wp-login.php?action=lostpassword" id="jo-exit-register-link">Recupera Password</a></p>
                    </div>
        </div>
    <?php endif; ?>
</div>