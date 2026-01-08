<?php
/**
 * Admin area display for JOb Exit Plugin
 *
 * @since      1.0.0
 */
?>

<div class="wrap jo-exit-admin-container">
    <div class="jo-exit-admin-header">
        <h1><?php echo esc_html__('JOb Exit - Manage Employees', 'job-exit-plugin'); ?></h1>
        <div class="jo-exit-messages"></div>
    </div>

    <div class="jo-exit-admin-content">
        <h2><?php echo esc_html__('Add/Edit Employee', 'job-exit-plugin'); ?></h2>
        <form id="jo-exit-employee-form" class="jo-exit-admin-form">
            <input type="hidden" id="jo-exit-employee-id" name="employee_id" value="">

            <div class="form-row">
                <label for="jo-exit-first-name"><?php echo esc_html__('First Name:', 'job-exit-plugin'); ?></label>
                <input type="text" id="jo-exit-first-name" name="first_name" required>
            </div>

            <div class="form-row">
                <label for="jo-exit-last-name"><?php echo esc_html__('Last Name:', 'job-exit-plugin'); ?></label>
                <input type="text" id="jo-exit-last-name" name="last_name" required>
            </div>

            <!-- Role field removed -->

            <div class="form-row">
                <label for="jo-exit-company-code"><?php echo esc_html__('Company Code:', 'job-exit-plugin'); ?></label>
                <input type="text" id="jo-exit-company-code" name="company_code" required>
            </div>

            <div class="form-row">
                <label for="jo-exit-photo-url"><?php echo esc_html__('Photo:', 'job-exit-plugin'); ?></label>
                <input type="text" id="jo-exit-photo-url" name="photo_url" readonly required>
                <div class="jo-exit-photo-buttons">
                    <button type="button" id="jo-exit-upload-photo" class="button"><?php echo esc_html__('Upload Photo', 'job-exit-plugin'); ?></button>
                    <span class="jo-exit-or"><?php echo esc_html__('or', 'job-exit-plugin'); ?></span>
                    <button type="button" id="jo-exit-generate-avatar" class="button"><?php echo esc_html__('Generate Random Avatar', 'job-exit-plugin'); ?></button>
                </div>
                <img id="jo-exit-photo-preview" class="preview-image" src="" alt="<?php echo esc_attr__('Employee Photo Preview', 'job-exit-plugin'); ?>">
            </div>

            <div class="form-row">
                <label for="jo-exit-hire-year"><?php echo esc_html__('Hire Year:', 'job-exit-plugin'); ?></label>
                <input type="number" id="jo-exit-hire-year" name="hire_year" min="1900" max="2100" value="<?php echo date('Y'); ?>">
                <p class="description"><?php echo esc_html__('Year when the employee was hired. Will be used to calculate seniority.', 'job-exit-plugin'); ?></p>
            </div>

            <div class="form-row">
                <label for="jo-exit-score"><?php echo esc_html__('Exit Points:', 'job-exit-plugin'); ?></label>
                <input type="number" id="jo-exit-score" name="score" min="0" value="0">
                <p class="description"><?php echo esc_html__('Current score of the employee. You can modify it manually.', 'job-exit-plugin'); ?></p>
            </div>

            <div class="button-row">
                <input type="submit" id="jo-exit-submit-button" class="button button-primary" value="<?php echo esc_attr__('Add Employee', 'job-exit-plugin'); ?>">
                <input type="button" id="jo-exit-reset-button" class="button" value="<?php echo esc_attr__('Reset', 'job-exit-plugin'); ?>">
            </div>
        </form>

        <h2><?php echo esc_html__('Employees', 'job-exit-plugin'); ?></h2>
        <table id="jo-exit-employees-table" class="jo-exit-admin-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Photo', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Name', 'job-exit-plugin'); ?></th>
                    <!-- Role column removed -->
                    <th><?php echo esc_html__('Company Code', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Hire Year', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Exit Points', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Actions', 'job-exit-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Employees will be loaded here via JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Exit Modal -->
<div id="jo-exit-exit-modal" class="jo-exit-modal">

    <div class="jo-exit-modal-content">
        <div class="jo-exit-modal-header">
            <span class="jo-exit-modal-close">&times;</span>
            <h2><?php echo esc_html__('Mark Employee as Exited', 'job-exit-plugin'); ?></h2>
        </div>

        <form id="jo-exit-exit-form">
            <input type="hidden" id="jo-exit-exit-employee-id" name="employee_id" value="">

            <p><?php echo esc_html__('Are you sure you want to mark', 'job-exit-plugin'); ?> <strong><span id="jo-exit-exit-employee-name"></span></strong> <?php echo esc_html__('as exited?', 'job-exit-plugin'); ?></p>

            <div class="form-row">
                <label for="jo-exit-exit-date"><?php echo esc_html__('Exit Date:', 'job-exit-plugin'); ?></label>
                <input type="date" id="jo-exit-exit-date" name="exit_date" required>
            </div>

            <div class="jo-exit-modal-footer">
                <button type="button" class="button jo-exit-cancel-exit"><?php echo esc_html__('Cancel', 'job-exit-plugin'); ?></button>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Confirm Exit', 'job-exit-plugin'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Votes Modal - Hidden because there are no voting limits -->
<div id="jo-exit-reset-votes-modal" class="jo-exit-modal" style="display: none;">
    <div class="jo-exit-modal-content">
        <div class="jo-exit-modal-header">
            <span class="jo-exit-modal-close">&times;</span>
            <h2><?php echo esc_html__('Reset Votes', 'job-exit-plugin'); ?></h2>
        </div>

        <form id="jo-exit-reset-votes-form">
            <input type="hidden" id="jo-exit-reset-votes-employee-id" name="employee_id" value="">

            <p><?php echo esc_html__('Are you sure you want to reset votes for', 'job-exit-plugin'); ?> <strong><span id="jo-exit-reset-votes-employee-name"></span></strong>?</p>
            <p><?php echo esc_html__('This will delete all votes for this employee and reset their score to 0.', 'job-exit-plugin'); ?></p>

            <div class="jo-exit-modal-footer">
                <button type="button" class="button jo-exit-cancel-reset-votes"><?php echo esc_html__('Cancel', 'job-exit-plugin'); ?></button>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Reset Votes', 'job-exit-plugin'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Employee Modal removed -->
