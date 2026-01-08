<?php
/**
 * Admin area display for exited employees
 *
 * @since      1.0.0
 */
?>

<div class="wrap jo-exit-admin-container">
    <div class="jo-exit-admin-header">
        <h1><?php echo esc_html__('JOb Exit - Exited Employees', 'job-exit-plugin'); ?></h1>
        <div class="jo-exit-messages"></div>
    </div>

    <div class="jo-exit-admin-content">
        <h2><?php echo esc_html__('Employees Who Have Exited', 'job-exit-plugin'); ?></h2>
        <table id="jo-exit-exited-table" class="jo-exit-admin-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Exit Date', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Photo', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Name', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Exit Points', 'job-exit-plugin'); ?></th>
                    <th><?php echo esc_html__('Final Exit Score', 'job-exit-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Exited employees will be loaded here via JavaScript -->
            </tbody>
        </table>
    </div>
</div>
