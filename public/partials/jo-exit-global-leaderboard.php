<?php
/**
 * Global Leaderboard screen template for JO Exit Plugin
 *
 * @since      1.0.0
 */
?>

<div class="jo-exit-container" style="--theme-color: <?php echo esc_attr($atts['theme_color']); ?>">
    <div class="jo-exit-header">
        <div class="jo-exit-header-title">JO Exit - Global Leaderboard</div>
        <div class="jo-exit-header-icon" id="jo-exit-info-icon" data-screen="info">
            <i class="fas fa-info-circle"></i>
        </div>
    </div>

    <div class="jo-exit-content">
        <div id="jo-exit-global-leaderboard-screen" class="jo-exit-screen">
            <div class="jo-exit-loading"><div class="jo-exit-spinner"></div>Caricamento classifica globale...</div>
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

        <div class="jo-exit-nav-item active" data-screen="global-leaderboard">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div>Leaderboard</div>
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
                <div>Account</div>
            <?php else : ?>
                <div class="jo-exit-nav-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div>Account</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // console.log('jo-exit-global-leaderboard.php script loaded');
        // console.log('JoExit object:', window.JoExit);

        // Check if the container exists
        const $container = $('#jo-exit-global-leaderboard-screen');
        // console.log('Container exists:', $container.length > 0);
        // console.log('Container HTML:', $container.html());

        // Log all available functions in window.JoExit
        // console.log('Available functions in window.JoExit:');
        for (const key in window.JoExit) {
            // console.log(' - ' + key + ': ' + (typeof window.JoExit[key]));
        }

        // Initialize the global leaderboard screen
        if (window.JoExit && window.JoExit.loadGlobalLeaderboardScreen) {
            // console.log('Calling loadGlobalLeaderboardScreen');
            try {
                window.JoExit.loadGlobalLeaderboardScreen();
                // console.log('loadGlobalLeaderboardScreen called successfully');
            } catch (e) {
                console.error('Error calling loadGlobalLeaderboardScreen:', e);
                $container.html('<div class="jo-exit-empty-message">Errore durante il caricamento: ' + e.message + '</div>');
            }
        } else {
            console.error('loadGlobalLeaderboardScreen function not found');
            $container.html('<div class="jo-exit-empty-message">Errore: funzione di caricamento non trovata.</div>');
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

            // Redirect to the selected screen
            window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=' + screen;
        });
    });
</script>
