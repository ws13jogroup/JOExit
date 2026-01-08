<?php
/**
 * Admin area display for settings
 *
 * @since      1.0.0
 */
?>

<div class="wrap jo-exit-admin-container">
    <div class="jo-exit-admin-header">
        <h1><?php echo esc_html__('JOb Exit - Settings', 'job-exit-plugin'); ?></h1>
        <div class="jo-exit-messages"></div>
    </div>

    <div class="jo-exit-admin-content">
        <div class="jo-exit-settings-section">
            <h2><?php echo esc_html__('Shortcodes', 'job-exit-plugin'); ?></h2>

            <p><?php echo esc_html__('Use the following shortcodes to display the JOb Exit app on your site:', 'job-exit-plugin'); ?></p>

            <ul>
                <li><code>[jo_exit_app]</code> - <?php echo esc_html__('Full app with all screens', 'job-exit-plugin'); ?></li>
                <li><code>[jo_exit_home]</code> - <?php echo esc_html__('Only the home screen (swipe interface)', 'job-exit-plugin'); ?></li>
                <li><code>[jo_exit_leaderboard]</code> - <?php echo esc_html__('Only the Exit Points screen (formerly Leaderboard)', 'job-exit-plugin'); ?></li>
                <li><code>[jo_exit_exited]</code> - <?php echo esc_html__('Only the exited employees screen', 'job-exit-plugin'); ?></li>
            </ul>
        </div>

        <div class="jo-exit-settings-section">
            <h2><?php echo esc_html__('Theme Settings', 'job-exit-plugin'); ?></h2>

            <p><?php echo esc_html__('Customize the appearance of the JOb Exit app.', 'job-exit-plugin'); ?></p>

            <form id="jo-exit-theme-settings-form" class="jo-exit-settings-form">
                <div class="form-row">
                    <label for="jo-exit-theme-color"><?php echo esc_html__('Theme Color', 'job-exit-plugin'); ?></label>
                    <input type="color" id="jo-exit-theme-color" name="theme_color" value="<?php echo esc_attr(get_option('jo_exit_theme_color', '#ff4757')); ?>">
                    <p class="description"><?php echo esc_html__('Select a color for the theme. This will change the main color used throughout the app.', 'job-exit-plugin'); ?></p>
                </div>

                <div class="form-row">
                    <label for="jo-exit-logo-upload"><?php echo esc_html__('Header Logo', 'job-exit-plugin'); ?></label>
                    <div class="jo-exit-logo-upload-container">
                        <div class="jo-exit-logo-preview">
                            <?php
                            $logo_url = get_option('jo_exit_logo_url', plugin_dir_url(dirname(__FILE__)) . 'public/img/jo-exit-logo-white.png');
                            ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Logo Preview" id="jo-exit-logo-preview-img">
                        </div>
                        <input type="hidden" id="jo-exit-logo-url" name="logo_url" value="<?php echo esc_attr($logo_url); ?>">
                        <div class="jo-exit-logo-buttons">
                            <button type="button" id="jo-exit-upload-logo" class="button"><?php echo esc_html__('Upload Logo', 'job-exit-plugin'); ?></button>
                            <button type="button" id="jo-exit-reset-logo" class="button"><?php echo esc_html__('Reset to Default Logo', 'job-exit-plugin'); ?></button>
                        </div>
                        <p class="description"><?php echo esc_html__('Upload a custom logo for the header. The logo will be resized to maintain a height of 42px.', 'job-exit-plugin'); ?></p>
                    </div>
                </div>
                <div class="form-row">
                    <button type="submit" id="jo-exit-save-theme-settings" class="button button-primary"><?php echo esc_html__('Save Theme Settings', 'job-exit-plugin'); ?></button>
                    <button type="button" id="jo-exit-reset-theme-color" class="button"><?php echo esc_html__('Reset to Default Color', 'job-exit-plugin'); ?></button>
                    <span class="spinner" style="float: none; margin-left: 10px;"></span>
                </div>
            </form>
        </div>

        <div class="jo-exit-settings-section">
            <h2><?php echo esc_html__('Cooldown Management', 'job-exit-plugin'); ?></h2>

            <p><?php echo esc_html__('Here you can view and manage user cooldowns. Users in cooldown must wait before they can vote again.', 'job-exit-plugin'); ?></p>

            <div class="jo-exit-cooldown-actions">
                <button id="jo-exit-reset-all-cooldowns" class="button button-primary"><?php echo esc_html__('Reset Cooldown for All', 'job-exit-plugin'); ?></button>
                <span class="spinner" style="float: none; margin-left: 10px;"></span>
            </div>

            <div class="jo-exit-cooldown-table-container">
                <table class="wp-list-table widefat fixed striped" id="jo-exit-cooldown-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Username', 'job-exit-plugin'); ?></th>
                            <th><?php echo esc_html__('Display Name', 'job-exit-plugin'); ?></th>
                            <th><?php echo esc_html__('Cooldown Status', 'job-exit-plugin'); ?></th>
                            <th><?php echo esc_html__('Remaining Time', 'job-exit-plugin'); ?></th>
                            <th><?php echo esc_html__('Actions', 'job-exit-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5"><?php echo esc_html__('Loading users...', 'job-exit-plugin'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>



        <div class="jo-exit-settings-section">
            <h2><?php echo esc_html__('Global Leaderboard Management', 'job-exit-plugin'); ?></h2>

            <p><?php echo esc_html__('Here you can view and manage user scores in the global leaderboard.', 'job-exit-plugin'); ?></p>

            <div class="jo-exit-leaderboard-actions">
                <button id="jo-exit-reset-all-exp-points" class="button button-warning"><?php echo esc_html__('Reset All Scores', 'job-exit-plugin'); ?></button>
                <button id="jo-exit-delete-all-exp-points" class="button button-danger"><?php echo esc_html__('Delete All Scores', 'job-exit-plugin'); ?></button>
                <span class="spinner" style="float: none; margin-left: 10px;"></span>
            </div>

            <div class="jo-exit-leaderboard-table-container">
                <table class="wp-list-table widefat fixed striped" id="jo-exit-leaderboard-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Username', 'job-exit-plugin'); ?></th>
                            <th><?php echo esc_html__('Display Name', 'job-exit-plugin'); ?></th>
                            <th><?php echo esc_html__('Experience Points', 'job-exit-plugin'); ?></th>
                            <th><?php echo esc_html__('Actions', 'job-exit-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4"><?php echo esc_html__('Loading users...', 'job-exit-plugin'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/template" id="jo-exit-cooldown-row-template">
    <tr data-user-id="{{id}}">
        <td>{{username}}</td>
        <td>{{display_name}}</td>
        <td>{{cooldown_status}}</td>
        <td>{{remaining_time}}</td>
        <td>
            <button class="button jo-exit-reset-cooldown" {{button_disabled}}><?php echo esc_html__('Reset Cooldown', 'job-exit-plugin'); ?></button>
            <span class="spinner" style="float: none; margin-left: 10px;"></span>
        </td>
    </tr>
</script>

<script type="text/template" id="jo-exit-leaderboard-row-template">
    <tr data-user-id="{{id}}">
        <td>{{username}}</td>
        <td>{{display_name}}</td>
        <td>
            <span class="jo-exit-exp-points-display">{{exp_points}} EXP</span>
            <div class="jo-exit-exp-points-edit" style="display: none;">
                <input type="number" class="jo-exit-exp-points-input" value="{{exp_points}}" min="0" style="width: 80px;">
                <button class="button button-small jo-exit-save-exp-points"><?php echo esc_html__('Save', 'job-exit-plugin'); ?></button>
                <button class="button button-small jo-exit-cancel-edit"><?php echo esc_html__('Cancel', 'job-exit-plugin'); ?></button>
            </div>
        </td>
        <td>
            <button class="button button-small jo-exit-edit-exp-points"><?php echo esc_html__('Edit', 'job-exit-plugin'); ?></button>
            <button class="button button-small jo-exit-delete-from-leaderboard"><?php echo esc_html__('Delete', 'job-exit-plugin'); ?></button>
            <span class="spinner" style="float: none; margin-left: 10px;"></span>
        </td>
    </tr>
</script>
