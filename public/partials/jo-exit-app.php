<?php
/**
 * Main app template for JO Exit Plugin
 *
 * @since      1.0.0
 */
?>

<?php
// Check if dark mode is enabled
$dark_mode_class = '';
if (is_user_logged_in() && get_user_meta(get_current_user_id(), 'jo_exit_dark_mode', true) === 'on') {
    $dark_mode_class = 'jo-exit-dark-mode';
}
?>
<?php
// Convert hex color to RGB for CSS variables
function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return $r . ',' . $g . ',' . $b;
}

$theme_color_rgb = hex2rgb($atts['theme_color']);
?>
<div class="jo-exit-container <?php echo esc_attr($dark_mode_class); ?>" style="--theme-color: <?php echo esc_attr($atts['theme_color']); ?>; --theme-color-rgb: <?php echo esc_attr($theme_color_rgb); ?>;">
    <div class="jo-exit-header">
        <div class="jo-exit-header-title"><img src="<?php echo esc_url(get_option('jo_exit_logo_url', plugin_dir_url(dirname(__FILE__)) . 'img/jo-exit-logo-white.png')); ?>" alt="JOb Exit" class="jo-exit-logo"></div>
        <div class="jo-exit-header-icon" id="jo-exit-notification-icon">
            <div class="jo-exit-notification-bell">
                <i class="fas fa-bell"></i>
                <span class="jo-exit-notification-count" style="display: none;">0</span>
            </div>

            <!-- Notification Popup -->
            <div class="jo-exit-notification-popup">
                <div class="jo-exit-notification-list">
                    <!-- Notifications will be loaded here -->
                </div>
                <div class="jo-exit-notification-footer">
                    <div class="jo-exit-notification-button" id="jo-exit-info-button">
                        <i class="fas fa-info-circle"></i> <?php echo esc_html__('Info', 'job-exit-plugin'); ?>
                    </div>
                    <div class="jo-exit-notification-button" id="jo-exit-changelog-button">
                        <i class="fas fa-history"></i> <?php echo esc_html__('Changelog', 'job-exit-plugin'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="jo-exit-content">
        <!-- Home Screen (Swipe Interface) -->
        <div id="jo-exit-home-screen" class="jo-exit-screen">
            <!-- Content will be loaded via JavaScript -->
        </div>

        <!-- Exit Points Screen -->
        <div id="jo-exit-leaderboard-screen" class="jo-exit-screen" style="display: none;">
            <!-- Content will be loaded via JavaScript -->
        </div>

        <!-- Exit Screen -->
        <div id="jo-exit-exited-screen" class="jo-exit-screen" style="display: none;">
            <!-- Content will be loaded via JavaScript -->
        </div>

        <!-- Info Screen -->
        <div id="jo-exit-info-screen" class="jo-exit-screen" style="display: none;">
            <!-- Content will be loaded via JavaScript -->
        </div>

        <!-- Global Leaderboard Screen -->
        <div id="jo-exit-global-leaderboard-screen" class="jo-exit-screen" style="display: none;">
            <!-- Content will be loaded via JavaScript -->
        </div>

        <!-- Account Screen -->
        <div id="jo-exit-account-screen" class="jo-exit-screen" style="display: none;">
            <!-- Content will be loaded via JavaScript -->
        </div>

        <!-- Notification Content Screen -->
        <div id="jo-exit-notification-screen" class="jo-exit-screen" style="display: none;">
            <div class="jo-exit-notification-content-body">
                <!-- Notification content will be loaded here -->
            </div>
        </div>

        <!-- Changelog Screen -->
        <div id="jo-exit-changelog-screen" class="jo-exit-screen" style="display: none;">
            <div class="jo-exit-changelog-content">
                <?php echo esc_html__('Loading changelog...', 'job-exit-plugin'); ?>
            </div>
        </div>
    </div>

    <div class="jo-exit-bottom-nav">
        <div class="jo-exit-nav-item active" data-screen="home">
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

        <div class="jo-exit-nav-item" data-screen="exited">
            <div class="jo-exit-nav-icon">
                <i class="fas fa-person-walking-arrow-right"></i>
            </div>
            <div><?php echo esc_html__('Exit', 'job-exit-plugin'); ?></div>
        </div>

        <div class="jo-exit-nav-item" data-screen="global-leaderboard">
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

<!-- Notification Content Popup Overlay -->
<div class="jo-exit-notification-content-popup-overlay"></div>

<!-- Notification Content Popup -->
<div class="jo-exit-notification-content-popup-wrapper">
    <!-- Notification content will be loaded here -->
</div>

<script>
    // Global function to open notification popup
    window.openNotificationPopup = function(notificationId) {
        // console.log('Opening notification popup for ID:', notificationId);

        // Close notification list popup
        jQuery('.jo-exit-notification-popup').css('display', 'none');

        // Make AJAX request to get notification content
        jQuery.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_get_notification_content',
                notification_id: notificationId,
                nonce: jo_exit_public.nonce
            },
            success: function(response) {
                // console.log('AJAX response:', response);
                if (response.success) {
                    // console.log('Notification content received');

                    // Set notification content
                    jQuery('.jo-exit-notification-content-popup-wrapper').html(response.data.content);
                    // console.log('Popup content set');

                    // Show notification content popup and overlay
                    // console.log('Showing popup and overlay');
                    jQuery('.jo-exit-notification-content-popup-overlay').show();
                    jQuery('.jo-exit-notification-content-popup-wrapper').css('display', 'flex');
                } else {
                    // console.log('AJAX error:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                // console.log('AJAX error:', status, error);
            }
        });
    };

    jQuery(document).ready(function($) {
        // Check if we need to show a specific screen directly
        const urlParams = new URLSearchParams(window.location.search);
        const directScreen = urlParams.get('direct_screen');

        if (directScreen === 'exited') {
            // Show the exit screen directly
            $('.jo-exit-screen').hide();
            $('#jo-exit-exited-screen').show();
            $('.jo-exit-nav-item').removeClass('active');
            $('.jo-exit-nav-item[data-screen="exited"]').addClass('active');

            // Load the exit screen data
            if (window.JoExit && window.JoExit.loadExitScreen) {
                window.JoExit.loadExitScreen();
            }

            // Update URL to remove the direct_screen parameter
            const newUrl = window.location.pathname + '?page=jo-exit';
            history.replaceState(null, '', newUrl);
        }

        // Load unread notifications count
        function loadUnreadCount() {
            if (!jo_exit_public.is_user_logged_in) {
                return;
            }

            $.ajax({
                url: jo_exit_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'jo_exit_get_unread_count',
                    nonce: jo_exit_public.nonce
                },
                success: function(response) {
                    if (response.success && response.data.count > 0) {
                        $('.jo-exit-notification-count').text(response.data.count).show();
                    } else {
                        $('.jo-exit-notification-count').hide();
                    }
                }
            });
        }

        // Load unread notifications
        function loadUnreadNotifications() {
            if (!jo_exit_public.is_user_logged_in) {
                $('.jo-exit-notification-list').html('<div class="jo-exit-notification-item">' +
                    '<div class="jo-exit-notification-title">' + '<?php echo esc_js(__('Login Required', 'job-exit-plugin')); ?>' + '</div>' +
                    '<div class="jo-exit-notification-preview">' + '<?php echo esc_js(__('Please login to view notifications.', 'job-exit-plugin')); ?>' + '</div>' +
                '</div>');
                return;
            }

            $.ajax({
                url: jo_exit_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'jo_exit_get_unread_notifications',
                    nonce: jo_exit_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const notifications = response.data.notifications;
                        let html = '';

                        if (notifications.length === 0) {
                            html = '<div class="jo-exit-notification-item">' +
                                '<div class="jo-exit-notification-title">' + '<?php echo esc_js(__('No Notifications', 'job-exit-plugin')); ?>' + '</div>' +
                                '<div class="jo-exit-notification-preview">' + '<?php echo esc_js(__('You have no new notifications.', 'job-exit-plugin')); ?>' + '</div>' +
                            '</div>';
                        } else {
                            notifications.forEach(function(notification) {
                                const date = new Date(notification.created_at);
                                const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();

                                // Strip HTML tags for preview
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = notification.content;
                                const preview = tempDiv.textContent || tempDiv.innerText || '';

                                // Decode HTML entities in title and content
                                const decodedTitle = notification.title.replace(/\\'/g, "'").replace(/\\"/g, '"');
                                const decodedPreview = preview.replace(/\\'/g, "'").replace(/\\"/g, '"');

                                html += '<div class="jo-exit-notification-item" data-id="' + notification.id + '" onclick="window.openNotificationPopup(' + notification.id + ')">' +
                                    '<div class="jo-exit-notification-title">' + decodedTitle + '</div>' +
                                    '<div class="jo-exit-notification-preview">' + decodedPreview.substring(0, 100) + '...</div>' +
                                    '<div class="jo-exit-notification-date">' + formattedDate + '</div>' +
                                '</div>';
                            });
                        }

                        $('.jo-exit-notification-list').html(html);
                    }
                }
            });
        }

        // Load changelog content
        function loadChangelog() {
            $.ajax({
                url: jo_exit_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'jo_exit_load_changelog',
                    nonce: jo_exit_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.jo-exit-changelog-content').html(response.data.content);
                    }
                }
            });
        }

        // Handle notification bell click
        $('#jo-exit-notification-icon').on('click', function(e) {
            e.stopPropagation();

            // Toggle popup visibility
            if ($('.jo-exit-notification-popup').css('display') === 'none') {
                $('.jo-exit-notification-popup').css('display', 'flex');
                loadUnreadNotifications();
            } else {
                $('.jo-exit-notification-popup').css('display', 'none');
            }
        });

        // Handle notification close button
        $('.jo-exit-notification-close').on('click', function(e) {
            e.stopPropagation();
            $('.jo-exit-notification-popup').css('display', 'none');
        });

        // Notification item click is now handled by the global openNotificationPopup function

        // Overlay click no longer closes the popup - users must click 'Mark as read'

        // Test button removed

        // Handle mark as read button - use event delegation since the button is loaded dynamically
        $(document).on('click', '.jo-exit-notification-mark-read-button', function() {
            const notificationId = $(this).data('id');
            // console.log('Mark as read clicked for notification ID:', notificationId);

            if (!notificationId) {
                // console.log('No notification ID found');
                return;
            }

            $.ajax({
                url: jo_exit_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'jo_exit_mark_notification_read',
                    notification_id: notificationId,
                    nonce: jo_exit_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Close the popup and overlay
                        $('.jo-exit-notification-content-popup-wrapper').hide();
                        $('.jo-exit-notification-content-popup-overlay').hide();

                        // Refresh unread count and notifications list
                        loadUnreadCount();
                        loadUnreadNotifications();
                    }
                }
            });
        });

        // Handle info button click
        $('#jo-exit-info-button').on('click', function() {
            $('.jo-exit-notification-popup').css('display', 'none');

            if (window.JoExit && window.JoExit.navigateTo) {
                window.JoExit.navigateTo('info');
            }
        });

        // Handle changelog button click
        $('#jo-exit-changelog-button').on('click', function() {
            // Close notification popup
            $('.jo-exit-notification-popup').hide();

            // Load changelog content
            loadChangelog();

            // Show changelog screen
            $('.jo-exit-screen').hide();
            $('#jo-exit-changelog-screen').show();

            // Update active nav item
            $('.jo-exit-nav-item').removeClass('active');
            $('.jo-exit-nav-item[data-screen="home"]').addClass('active');
        });

        // Handle navigation for menu items
        $('.jo-exit-nav-item[data-screen="home"]').on('click', function() {
            // Show the home screen
            $('.jo-exit-screen').hide();
            $('#jo-exit-home-screen').show();
            $('.jo-exit-nav-item').removeClass('active');
            $(this).addClass('active');

            // Also hide any popups that might be open
            $('.jo-exit-notification-popup').hide();
            $('.jo-exit-notification-content-popup-wrapper').hide();
            $('.jo-exit-notification-content-popup-overlay').hide();
        });

        $('.jo-exit-nav-item[data-screen="global-leaderboard"]').on('click', function() {
            // Show the global leaderboard screen
            $('.jo-exit-screen').hide();
            $('#jo-exit-global-leaderboard-screen').show();
            $('.jo-exit-nav-item').removeClass('active');
            $(this).addClass('active');

            // Load the global leaderboard data
            if (window.JoExit && window.JoExit.loadGlobalLeaderboardScreen) {
                window.JoExit.loadGlobalLeaderboardScreen();
            }
        });

        $('.jo-exit-nav-item[data-screen="exited"]').on('click', function() {
            // Show the exit screen directly
            $('.jo-exit-screen').hide();
            $('#jo-exit-exited-screen').show();
            $('.jo-exit-nav-item').removeClass('active');
            $(this).addClass('active');

            // Load the exit screen data
            if (window.JoExit && window.JoExit.loadExitScreen) {
                window.JoExit.loadExitScreen();
            }
        });

        $('.jo-exit-nav-item[data-screen="account"]').on('click', function() {
            if (window.JoExit && window.JoExit.navigateTo) {
                window.JoExit.navigateTo('account');
            }
        });

        // Close notification popup when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.jo-exit-notification-popup').length &&
                !$(e.target).closest('#jo-exit-notification-icon').length) {
                $('.jo-exit-notification-popup').css('display', 'none');
            }
        });

        // Load unread count on page load
        loadUnreadCount();

        // Refresh unread count every 60 seconds
        setInterval(loadUnreadCount, 60000);
    });
</script>
