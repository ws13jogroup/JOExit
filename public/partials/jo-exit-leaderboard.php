<?php
/**
 * Exit Points screen template for JO Exit Plugin
 *
 * @since      1.0.0
 */
?>

<div class="jo-exit-container" style="--theme-color: <?php echo esc_attr($atts['theme_color']); ?>">
    <div class="jo-exit-header">
        JO Exit - Exit Points
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
            <div>Home</div>
        </div>

        <div class="jo-exit-nav-item active" data-screen="leaderboard">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>Points</div>
        </div>

        <div class="jo-exit-nav-item" data-screen="exit">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-person-walking-arrow-right"></i>
            </div>
            <div>Exit</div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Initialize the leaderboard screen
        if (window.JoExit && window.JoExit.loadLeaderboardScreen) {
            window.JoExit.loadLeaderboardScreen();
        }

        // Handle navigation clicks
        $('.jo-exit-nav-item').on('click', function() {
            const screen = $(this).data('screen');

            // If not leaderboard, redirect to the full app with the selected screen
            if (screen !== 'leaderboard') {
                window.location.href = '<?php echo esc_url(home_url('/')); ?>?page=jo-exit&screen=' + screen;
            }
        });
    });
</script>
