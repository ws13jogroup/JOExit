<?php
/**
 * Exit Points screen template for JO Exit Plugin
 *
 * @since      1.0.0
 */
?>

<div class="jo-exit-container" style="--theme-color: <?php echo esc_attr($atts['theme_color']); ?>">
    <div class="jo-exit-header">
        <div class="jo-exit-header-title">JOb Exit - <?php echo esc_html__('Exit Points', 'job-exit-plugin'); ?></div>
        <div class="jo-exit-header-icon" id="jo-exit-info-icon" data-screen="info">
            <i class="fas fa-info-circle"></i>
        </div>
    </div>

    <div class="jo-exit-content">
        <div id="jo-exit-leaderboard-screen" class="jo-exit-screen">
            <!-- Content will be loaded via JavaScript -->
        </div>
    </div>

    <div class="jo-exit-bottom-nav">
        <div class="jo-exit-nav-item" data-screen="home">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-home"></i>
            </div>
            <div><?php echo esc_html__('Home', 'job-exit-plugin'); ?></div>
        </div>

        <div class="jo-exit-nav-item active" data-screen="leaderboard">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div><?php echo esc_html__('Points', 'job-exit-plugin'); ?></div>
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
        // Initialize the leaderboard screen
        if (window.JoExit && window.JoExit.loadLeaderboardScreen) {
            window.JoExit.loadLeaderboardScreen();
        }

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

            // If not leaderboard, redirect to the full app with the selected screen
            if (screen !== 'leaderboard') {
                window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=' + screen;
            }
        });
    });
</script>
