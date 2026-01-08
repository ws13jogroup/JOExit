<?php
/**
 * Admin area display for user votes
 *
 * @since      1.0.0
 */
?>

<div class="wrap jo-exit-admin-container">
    <div class="jo-exit-admin-header">
        <h1><?php echo esc_html__('JOb Exit - User Votes', 'job-exit-plugin'); ?></h1>
        <div class="jo-exit-messages"></div>
    </div>

    <div class="jo-exit-admin-content">
        <div class="jo-exit-filters">
            <h2><?php echo esc_html__('Filters', 'job-exit-plugin'); ?></h2>
            <div class="jo-exit-filter-row">
                <div class="jo-exit-filter-item">
                    <label for="jo-exit-user-filter"><?php echo esc_html__('Filter by User:', 'job-exit-plugin'); ?></label>
                    <select id="jo-exit-user-filter">
                        <option value="0"><?php echo esc_html__('All Users', 'job-exit-plugin'); ?></option>
                        <!-- Users will be loaded here via JavaScript -->
                    </select>
                </div>
                <div class="jo-exit-filter-item">
                    <label for="jo-exit-employee-filter"><?php echo esc_html__('Filter by Employee:', 'job-exit-plugin'); ?></label>
                    <select id="jo-exit-employee-filter">
                        <option value="0"><?php echo esc_html__('All Employees', 'job-exit-plugin'); ?></option>
                        <!-- Employees will be loaded here via JavaScript -->
                    </select>
                </div>
                <div class="jo-exit-filter-item">
                    <button id="jo-exit-apply-filters" class="button button-primary"><?php echo esc_html__('Apply Filters', 'job-exit-plugin'); ?></button>
                    <button id="jo-exit-reset-filters" class="button"><?php echo esc_html__('Reset Filters', 'job-exit-plugin'); ?></button>
                </div>
            </div>
            <!-- <?php echo esc_html__('The Fix All User Votes button has been removed because it is no longer needed', 'job-exit-plugin'); ?> -->
        </div>

        <h2><?php echo esc_html__('User Votes', 'job-exit-plugin'); ?></h2>
        <table id="jo-exit-user-votes-table" class="jo-exit-admin-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('User', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Employee', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Photo', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Vote Type', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Points', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Date', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Actions', 'job-exit-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- User votes will be loaded here via JavaScript -->
                <tr>
                    <td colspan="7"><?php echo esc_html__('Loading user votes...', 'job-exit-plugin'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Vote Modal -->
<div id="jo-exit-edit-vote-modal" class="jo-exit-modal">
    <div class="jo-exit-modal-content">
        <span class="jo-exit-modal-close">&times;</span>
        <h2><?php echo esc_html__('Edit Vote', 'job-exit-plugin'); ?></h2>
        <form id="jo-exit-edit-vote-form">
            <input type="hidden" id="jo-exit-edit-vote-user-id" name="user_id" value="">
            <input type="hidden" id="jo-exit-edit-vote-employee-id" name="employee_id" value="">

            <div class="jo-exit-form-row">
                <label for="jo-exit-edit-vote-user"><?php echo esc_html__('User:', 'job-exit-plugin'); ?></label>
                <span id="jo-exit-edit-vote-user"></span>
            </div>

            <div class="jo-exit-form-row">
                <label for="jo-exit-edit-vote-employee"><?php echo esc_html__('Employee:', 'job-exit-plugin'); ?></label>
                <span id="jo-exit-edit-vote-employee"></span>
            </div>

            <div class="jo-exit-form-row">
                <label for="jo-exit-edit-vote-type"><?php echo esc_html__('Vote Type:', 'job-exit-plugin'); ?></label>
                <select id="jo-exit-edit-vote-type" name="vote_type">
                    <option value="exit"><?php echo esc_html__('Exit', 'job-exit-plugin'); ?></option>
                    <option value="nope"><?php echo esc_html__('Nope', 'job-exit-plugin'); ?></option>
                </select>
            </div>

            <div class="jo-exit-form-row">
                <label for="jo-exit-edit-vote-points"><?php echo esc_html__('Points:', 'job-exit-plugin'); ?></label>
                <input type="number" id="jo-exit-edit-vote-points" name="points" min="0" value="1">
            </div>

            <div class="jo-exit-form-actions">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Save Changes', 'job-exit-plugin'); ?></button>
                <button type="button" class="button jo-exit-modal-cancel"><?php echo esc_html__('Cancel', 'job-exit-plugin'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Reset User Votes Modal -->
<div id="jo-exit-reset-user-votes-modal" class="jo-exit-modal">
    <div class="jo-exit-modal-content">
        <span class="jo-exit-modal-close">&times;</span>
        <h2><?php echo esc_html__('Reset User Votes', 'job-exit-plugin'); ?></h2>
        <p><?php echo esc_html__('Are you sure you want to reset all votes for this user? This action cannot be undone.', 'job-exit-plugin'); ?></p>
        <form id="jo-exit-reset-user-votes-form">
            <input type="hidden" id="jo-exit-reset-user-votes-user-id" name="user_id" value="">

            <div class="jo-exit-form-row">
                <label for="jo-exit-reset-user-votes-user"><?php echo esc_html__('User:', 'job-exit-plugin'); ?></label>
                <span id="jo-exit-reset-user-votes-user"></span>
            </div>

            <div class="jo-exit-form-actions">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Reset Votes', 'job-exit-plugin'); ?></button>
                <button type="button" class="button jo-exit-modal-cancel"><?php echo esc_html__('Cancel', 'job-exit-plugin'); ?></button>
            </div>
        </form>
    </div>
</div>
