/**
 * Admin JavaScript for JO Exit Plugin
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize media uploader
        var mediaUploader;

        $('#jo-exit-upload-photo').on('click', function(e) {
            e.preventDefault();

            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            // Create the media uploader
            mediaUploader = wp.media({
                title: jo_exit_admin.select_photo,
                button: {
                    text: jo_exit_admin.use_photo
                },
                multiple: false
            });

            // When a photo is selected, run a callback
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#jo-exit-photo-url').val(attachment.url);
                $('#jo-exit-photo-preview').attr('src', attachment.url).show();
            });

            // Open the uploader dialog
            mediaUploader.open();
        });

        // Handle avatar generation
        $('#jo-exit-generate-avatar').on('click', function(e) {
            e.preventDefault();

            // Generate a simple avatar URL that is guaranteed to work
            var seed = Math.floor(Math.random() * 1000000);

            // Choose a random background color
            var colors = ['b6e3f4', 'ffd5dc', 'c0aede', 'ffdfbf', 'bde7a9'];
            var randomColor = colors[Math.floor(Math.random() * colors.length)];

            // Create the URL with the latest DiceBear API endpoint
            var avatarUrl = 'https://api.dicebear.com/7.x/micah/svg?seed=' + seed + '&backgroundColor=' + randomColor;

            // Randomly choose gender for more variety
            var genders = ['male', 'female'];
            var randomGender = genders[Math.floor(Math.random() * genders.length)];
            avatarUrl += '&gender=' + randomGender;

            // Log the URL for debugging
            // // console.log('Avatar URL:', avatarUrl);

            // Set the avatar URL and show the preview
            $('#jo-exit-photo-url').val(avatarUrl);
            $('#jo-exit-photo-preview').attr('src', avatarUrl).show();

            // Debug: log the URL to console only
            // // console.log('Avatar URL:', avatarUrl);
        });

        // Handle form submission
        $('#jo-exit-employee-form').on('submit', function(e) {
            e.preventDefault();

            var formData = {
                'action': 'jo_exit_save_employee',
                'nonce': jo_exit_admin.nonce,
                'id': $('#jo-exit-employee-id').val(),
                'first_name': $('#jo-exit-first-name').val(),
                'last_name': $('#jo-exit-last-name').val(),
                'role': 'employee', // Default role
                'company_code': $('#jo-exit-company-code').val(),
                'photo_url': $('#jo-exit-photo-url').val(),
                'hire_year': $('#jo-exit-hire-year').val(),
                'score': $('#jo-exit-score').val()
            };

            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        // Reset form and reload employee list
                        resetForm();
                        loadEmployees();
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', 'Si è verificato un errore durante il salvataggio del dipendente.');
                }
            });
        });

        // Handle edit button click
        $(document).on('click', '.jo-exit-edit-employee', function() {
            var employeeId = $(this).data('id');

            // Get employee data
            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_get_employees',
                    'nonce': jo_exit_admin.nonce,
                    'id': employeeId
                },
                success: function(response) {
                    if (response.success && response.data.employees.length > 0) {
                        var employee = response.data.employees[0];

                        // Fill form with employee data
                        $('#jo-exit-employee-id').val(employee.id);
                        $('#jo-exit-first-name').val(employee.first_name);
                        $('#jo-exit-last-name').val(employee.last_name);
                        // Role field removed
                        $('#jo-exit-company-code').val(employee.company_code);
                        $('#jo-exit-photo-url').val(employee.photo_url);
                        $('#jo-exit-photo-preview').attr('src', employee.photo_url).show();
                        $('#jo-exit-hire-year').val(employee.hire_year || new Date().getFullYear());
                        $('#jo-exit-score').val(employee.score);

                        // Change button text
                        $('#jo-exit-submit-button').val(jo_exit_admin.update_employee);

                        // Scroll to form
                        $('html, body').animate({
                            scrollTop: $('#jo-exit-employee-form').offset().top - 50
                        }, 500);
                    } else {
                        showMessage('error', jo_exit_admin.error_load);
                    }
                },
                error: function() {
                    showMessage('error', jo_exit_admin.error_load_data);
                }
            });
        });

        // Handle delete button click
        $(document).on('click', '.jo-exit-delete-employee', function() {
            if (confirm(jo_exit_admin.confirm_delete)) {
                var employeeId = $(this).data('id');

                $.ajax({
                    url: jo_exit_admin.ajax_url,
                    type: 'POST',
                    data: {
                        'action': 'jo_exit_delete_employee',
                        'nonce': jo_exit_admin.nonce,
                        'id': employeeId
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', response.data.message);
                            loadEmployees();
                        } else {
                            showMessage('error', response.data);
                        }
                    },
                    error: function() {
                        showMessage('error', jo_exit_admin.error_delete);
                    }
                });
            }
        });

        // Handle exit button click
        $(document).on('click', '.jo-exit-mark-exit', function() {
            var employeeId = $(this).data('id');
            var employeeName = $(this).data('name');

            // Show exit modal
            $('#jo-exit-exit-employee-id').val(employeeId);
            $('#jo-exit-exit-employee-name').text(employeeName);
            $('#jo-exit-exit-date').val(getCurrentDate());
            $('#jo-exit-exit-modal').show();
        });

        // Handle exit modal close
        $('.jo-exit-modal-close, .jo-exit-cancel-exit').on('click', function() {
            $('#jo-exit-exit-modal').hide();
        });

        // Handle reset votes modal close
        $('.jo-exit-modal-close, .jo-exit-cancel-reset-votes').on('click', function() {
            $('#jo-exit-reset-votes-modal').hide();
        });

        // Handle exit form submission
        $('#jo-exit-exit-form').on('submit', function(e) {
            e.preventDefault();

            var formData = {
                'action': 'jo_exit_mark_exit',
                'nonce': jo_exit_admin.nonce,
                'id': $('#jo-exit-exit-employee-id').val(),
                'exit_date': $('#jo-exit-exit-date').val()
            };

            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        // Close modal and reload employee list
                        $('#jo-exit-exit-modal').hide();
                        loadEmployees();
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', jo_exit_admin.error_mark_exit);
                }
            });
        });

        // Handle reset button click
        $('#jo-exit-reset-button').on('click', function() {
            resetForm();
        });

        // Handle reset votes button click
        $(document).on('click', '.jo-exit-reset-votes', function() {
            var employeeId = $(this).data('id');
            var employeeName = $(this).data('name');

            // Show reset votes modal
            $('#jo-exit-reset-votes-employee-id').val(employeeId);
            $('#jo-exit-reset-votes-employee-name').text(employeeName);
            $('#jo-exit-reset-votes-modal').show();
        });

        // Handle reset votes form submission
        $('#jo-exit-reset-votes-form').on('submit', function(e) {
            e.preventDefault();

            var formData = {
                'action': 'jo_exit_reset_votes',
                'nonce': jo_exit_admin.nonce,
                'id': $('#jo-exit-reset-votes-employee-id').val()
            };

            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        // Close modal and reload employee list
                        $('#jo-exit-reset-votes-modal').hide();
                        loadEmployees();
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', jo_exit_admin.error_reset_votes);
                }
            });
        });

        // Load employees on page load
        if ($('#jo-exit-employees-table').length) {
            loadEmployees();
        }

        // Load exited employees on page load
        if ($('#jo-exit-exited-table').length) {
            loadExitedEmployees();
        }

        // Load users cooldown on settings page
        if ($('#jo-exit-cooldown-table').length) {
            loadUsersCooldown();

            // Refresh cooldown data every 10 seconds
            setInterval(function() {
                loadUsersCooldown();
            }, 10000);
        }

        // Load users experience points on settings page
        if ($('#jo-exit-leaderboard-table').length) {
            loadUsersExpPoints();
        }

        // Update logo preview background color when theme color changes
        $('#jo-exit-theme-color').on('input', function() {
            var themeColor = $(this).val();
            $('.jo-exit-logo-preview').css('--theme-color', themeColor);
        });

        // Set initial theme color for logo preview
        $('.jo-exit-logo-preview').css('--theme-color', $('#jo-exit-theme-color').val());

        // Handle theme settings form submission
        $('#jo-exit-theme-settings-form').on('submit', function(e) {
            e.preventDefault();

            var button = $('#jo-exit-save-theme-settings');
            var spinner = button.siblings('.spinner');
            var themeColor = $('#jo-exit-theme-color').val();
            var logoUrl = $('#jo-exit-logo-url').val();

            // Show spinner
            spinner.addClass('is-active');

            // Disable button
            button.prop('disabled', true);

            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_save_theme_settings',
                    'nonce': jo_exit_admin.nonce,
                    'theme_color': themeColor,
                    'logo_url': logoUrl
                },
                success: function(response) {
                    // Hide spinner
                    spinner.removeClass('is-active');

                    // Enable button
                    button.prop('disabled', false);

                    if (response.success) {
                        showMessage('success', response.data.message);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    // Hide spinner
                    spinner.removeClass('is-active');

                    // Enable button
                    button.prop('disabled', false);

                    showMessage('error', jo_exit_admin.error_save_theme_settings);
                }
            });
        });

        // Handle reset theme color button
        $('#jo-exit-reset-theme-color').on('click', function() {
            var defaultColor = '#ff4757';
            var colorInput = $('#jo-exit-theme-color');
            var button = $(this);
            var spinner = button.siblings('.spinner');

            // Set the color input to default
            colorInput.val(defaultColor);

            // Update logo preview background color
            $('.jo-exit-logo-preview').css('--theme-color', defaultColor);

            // Show spinner
            spinner.addClass('is-active');

            // Disable button
            button.prop('disabled', true);

            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_save_theme_settings',
                    'nonce': jo_exit_admin.nonce,
                    'theme_color': defaultColor
                },
                success: function(response) {
                    // Hide spinner
                    spinner.removeClass('is-active');

                    // Enable button
                    button.prop('disabled', false);

                    if (response.success) {
                        showMessage('success', jo_exit_admin.theme_color_reset);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    // Hide spinner
                    spinner.removeClass('is-active');

                    // Enable button
                    button.prop('disabled', false);

                    showMessage('error', jo_exit_admin.error_save_theme_settings);
                }
            });
        });

        // Handle logo upload button
        $('#jo-exit-upload-logo').on('click', function() {
            var button = $(this);
            var logoUrlInput = $('#jo-exit-logo-url');
            var logoPreviewImg = $('#jo-exit-logo-preview-img');

            // Create media uploader
            var mediaUploader = wp.media({
                title: jo_exit_admin.upload_logo_title,
                button: {
                    text: jo_exit_admin.upload_logo_button
                },
                multiple: false
            });

            // When a file is selected
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();

                // Check if it's an image
                if (attachment.type !== 'image') {
                    showMessage('error', jo_exit_admin.error_not_image);
                    return;
                }

                // Update hidden input and preview image
                logoUrlInput.val(attachment.url);
                logoPreviewImg.attr('src', attachment.url);
            });

            // Open media uploader
            mediaUploader.open();
        });

        // Handle reset logo button
        $('#jo-exit-reset-logo').on('click', function() {
            var button = $(this);
            var logoUrlInput = $('#jo-exit-logo-url');
            var logoPreviewImg = $('#jo-exit-logo-preview-img');
            var spinner = button.closest('.form-row').find('.spinner');
            var defaultLogoUrl = jo_exit_admin.default_logo_url;

            // Show spinner
            spinner.addClass('is-active');

            // Disable button
            button.prop('disabled', true);

            // Update hidden input and preview image
            logoUrlInput.val(defaultLogoUrl);
            logoPreviewImg.attr('src', defaultLogoUrl);

            // Save the default logo
            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_save_theme_settings',
                    'nonce': jo_exit_admin.nonce,
                    'logo_url': defaultLogoUrl
                },
                success: function(response) {
                    // Hide spinner
                    spinner.removeClass('is-active');

                    // Enable button
                    button.prop('disabled', false);

                    if (response.success) {
                        showMessage('success', jo_exit_admin.logo_reset);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    // Hide spinner
                    spinner.removeClass('is-active');

                    // Enable button
                    button.prop('disabled', false);

                    showMessage('error', jo_exit_admin.error_save_theme_settings);
                }
            });
        });

        // Handle reset cooldown button click
        $(document).on('click', '.jo-exit-reset-cooldown', function() {
            var button = $(this);
            var userId = button.closest('tr').data('user-id');
            var spinner = button.next('.spinner');

            // Show spinner
            spinner.addClass('is-active');

            // Disable button
            button.prop('disabled', true);

            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_reset_user_cooldown',
                    'nonce': jo_exit_admin.nonce,
                    'user_id': userId
                },
                success: function(response) {
                    // Hide spinner
                    spinner.removeClass('is-active');

                    if (response.success) {
                        showMessage('success', response.data.message);
                        // Reload cooldown data
                        loadUsersCooldown();
                    } else {
                        // Enable button
                        button.prop('disabled', false);
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    // Hide spinner
                    spinner.removeClass('is-active');
                    // Enable button
                    button.prop('disabled', false);
                    showMessage('error', jo_exit_admin.error_reset_cooldown);
                }
            });
        });

        // Handle reset all cooldowns button click
        $('#jo-exit-reset-all-cooldowns').on('click', function() {
            var button = $(this);
            var spinner = button.next('.spinner');

            // Show confirmation dialog
            if (confirm(jo_exit_admin.confirm_reset_all_cooldowns)) {
                // Show spinner
                spinner.addClass('is-active');

                // Disable button
                button.prop('disabled', true);

                $.ajax({
                    url: jo_exit_admin.ajax_url,
                    type: 'POST',
                    data: {
                        'action': 'jo_exit_reset_all_cooldowns',
                        'nonce': jo_exit_admin.nonce
                    },
                    success: function(response) {
                        // Hide spinner
                        spinner.removeClass('is-active');
                        // Enable button
                        button.prop('disabled', false);

                        if (response.success) {
                            showMessage('success', response.data.message);
                            // Reload cooldown data
                            loadUsersCooldown();
                        } else {
                            showMessage('error', response.data);
                        }
                    },
                    error: function() {
                        // Hide spinner
                        spinner.removeClass('is-active');
                        // Enable button
                        button.prop('disabled', false);
                        showMessage('error', jo_exit_admin.error_reset_all_cooldowns);
                    }
                });
            }
        });

        // Handle edit experience points button click
        $(document).on('click', '.jo-exit-edit-exp-points', function() {
            var row = $(this).closest('tr');
            row.find('.jo-exit-exp-points-display').hide();
            row.find('.jo-exit-exp-points-edit').show();
            $(this).hide();
            row.find('.jo-exit-delete-from-leaderboard').hide();
        });

        // Handle cancel edit button click
        $(document).on('click', '.jo-exit-cancel-edit', function() {
            var row = $(this).closest('tr');
            row.find('.jo-exit-exp-points-display').show();
            row.find('.jo-exit-exp-points-edit').hide();
            row.find('.jo-exit-edit-exp-points').show();
            row.find('.jo-exit-delete-from-leaderboard').show();
        });

        // Handle save experience points button click
        $(document).on('click', '.jo-exit-save-exp-points', function() {
            var button = $(this);
            var row = button.closest('tr');
            var userId = row.data('user-id');
            var expPoints = row.find('.jo-exit-exp-points-input').val();
            var spinner = row.find('.spinner');

            // Show spinner
            spinner.addClass('is-active');

            // Disable buttons
            button.prop('disabled', true);
            row.find('.jo-exit-cancel-edit').prop('disabled', true);

            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_update_user_exp_points',
                    'nonce': jo_exit_admin.nonce,
                    'user_id': userId,
                    'exp_points': expPoints
                },
                success: function(response) {
                    // Hide spinner
                    spinner.removeClass('is-active');

                    if (response.success) {
                        showMessage('success', response.data.message);
                        // Reload leaderboard data
                        loadUsersExpPoints();
                    } else {
                        // Enable buttons
                        button.prop('disabled', false);
                        row.find('.jo-exit-cancel-edit').prop('disabled', false);
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    // Hide spinner
                    spinner.removeClass('is-active');
                    // Enable buttons
                    button.prop('disabled', false);
                    row.find('.jo-exit-cancel-edit').prop('disabled', false);
                    showMessage('error', jo_exit_admin.error_update_exp);
                }
            });
        });

        // Handle delete from leaderboard button click
        $(document).on('click', '.jo-exit-delete-from-leaderboard', function() {
            var button = $(this);
            var row = button.closest('tr');
            var userId = row.data('user-id');
            var userName = row.find('td:first').text();
            var spinner = row.find('.spinner');

            // Show confirmation dialog
            if (confirm(jo_exit_admin.confirm_delete_from_leaderboard.replace('{name}', userName))) {
                // Show spinner
                spinner.addClass('is-active');

                // Disable button
                button.prop('disabled', true);
                row.find('.jo-exit-edit-exp-points').prop('disabled', true);

                $.ajax({
                    url: jo_exit_admin.ajax_url,
                    type: 'POST',
                    data: {
                        'action': 'jo_exit_delete_user_from_leaderboard',
                        'nonce': jo_exit_admin.nonce,
                        'user_id': userId
                    },
                    success: function(response) {
                        // Hide spinner
                        spinner.removeClass('is-active');

                        if (response.success) {
                            showMessage('success', response.data.message);
                            // Reload leaderboard data
                            loadUsersExpPoints();
                        } else {
                            // Enable buttons
                            button.prop('disabled', false);
                            row.find('.jo-exit-edit-exp-points').prop('disabled', false);
                            showMessage('error', response.data);
                        }
                    },
                    error: function() {
                        // Hide spinner
                        spinner.removeClass('is-active');
                        // Enable buttons
                        button.prop('disabled', false);
                        row.find('.jo-exit-edit-exp-points').prop('disabled', false);
                        showMessage('error', jo_exit_admin.error_delete_from_leaderboard);
                    }
                });
            }
        });

        // Handle reset all experience points button click
        $('#jo-exit-reset-all-exp-points').on('click', function() {
            var button = $(this);
            var spinner = button.next('.spinner');

            // Show confirmation dialog
            if (confirm(jo_exit_admin.confirm_reset_all_exp_points)) {
                // Show spinner
                spinner.addClass('is-active');

                // Disable button
                button.prop('disabled', true);

                $.ajax({
                    url: jo_exit_admin.ajax_url,
                    type: 'POST',
                    data: {
                        'action': 'jo_exit_reset_all_exp_points',
                        'nonce': jo_exit_admin.nonce
                    },
                    success: function(response) {
                        // Hide spinner
                        spinner.removeClass('is-active');
                        // Enable button
                        button.prop('disabled', false);

                        if (response.success) {
                            showMessage('success', response.data.message);
                            // Reload leaderboard data
                            loadUsersExpPoints();
                        } else {
                            showMessage('error', response.data);
                        }
                    },
                    error: function() {
                        // Hide spinner
                        spinner.removeClass('is-active');
                        // Enable button
                        button.prop('disabled', false);
                        showMessage('error', jo_exit_admin.error_reset_exp_points);
                    }
                });
            }
        });

        // Handle delete all experience points button click
        $('#jo-exit-delete-all-exp-points').on('click', function() {
            var button = $(this);
            var spinner = button.next('.spinner');

            // Show confirmation dialog
            if (confirm(jo_exit_admin.confirm_delete_all_exp_points)) {
                // Show spinner
                spinner.addClass('is-active');

                // Disable button
                button.prop('disabled', true);

                $.ajax({
                    url: jo_exit_admin.ajax_url,
                    type: 'POST',
                    data: {
                        'action': 'jo_exit_delete_all_exp_points',
                        'nonce': jo_exit_admin.nonce
                    },
                    success: function(response) {
                        // Hide spinner
                        spinner.removeClass('is-active');
                        // Enable button
                        button.prop('disabled', false);

                        if (response.success) {
                            showMessage('success', response.data.message);
                            // Reload leaderboard data
                            loadUsersExpPoints();
                        } else {
                            showMessage('error', response.data);
                        }
                    },
                    error: function() {
                        // Hide spinner
                        spinner.removeClass('is-active');
                        // Enable button
                        button.prop('disabled', false);
                        showMessage('error', jo_exit_admin.error_delete_exp_points);
                    }
                });
            }
        });

        // Helper functions
        function loadEmployees() {
            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_get_employees',
                    'nonce': jo_exit_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderEmployeesTable(response.data.employees);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', jo_exit_admin.error_load_employees);
                }
            });
        }

        function loadExitedEmployees() {
            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_get_exited',
                    'nonce': jo_exit_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderExitedEmployeesTable(response.data.employees);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', jo_exit_admin.error_load_exited_employees);
                }
            });
        }

        function renderEmployeesTable(employees) {
            var tableBody = $('#jo-exit-employees-table tbody');
            tableBody.empty();

            if (employees.length === 0) {
                tableBody.append('<tr><td colspan="7">' + jo_exit_admin.no_employees_found + '</td></tr>');
                return;
            }

            employees.forEach(function(employee) {
                var row = $('<tr></tr>');

                row.append('<td><img src="' + employee.photo_url + '" class="jo-exit-employee-photo" alt="' + employee.first_name + ' ' + employee.last_name + '"></td>');
                row.append('<td>' + employee.first_name + ' ' + employee.last_name + '</td>');
                // Role column removed
                row.append('<td>' + employee.company_code + '</td>');
                row.append('<td>' + (employee.hire_year || '-') + '</td>');
                row.append('<td>' + employee.score + '</td>');

                var actions = $('<td class="jo-exit-actions"></td>');
                // Add edit button with all necessary data attributes
                actions.append('<button type="button" class="button jo-exit-edit-employee" data-id="' + employee.id + '" data-first-name="' + employee.first_name + '" data-last-name="' + employee.last_name + '" data-company-code="' + employee.company_code + '" data-photo-url="' + employee.photo_url + '" data-score="' + employee.score + '" title="' + jo_exit_admin.edit_employee + '"><span class="dashicons dashicons-edit jo-exit-edit-icon"></span></button>');
                actions.append('<button type="button" class="button jo-exit-mark-exit" data-id="' + employee.id + '" data-name="' + employee.first_name + ' ' + employee.last_name + '" title="' + jo_exit_admin.mark_as_exited + '"><span class="fas fa-person-walking-arrow-right jo-exit-exit-icon"></span></button>');
                // Reset votes button removed - no voting limits
                actions.append('<button type="button" class="button jo-exit-delete-employee" data-id="' + employee.id + '" title="' + jo_exit_admin.delete_employee + '"><span class="dashicons dashicons-trash jo-exit-delete-icon"></span></button>');

                row.append(actions);
                tableBody.append(row);
            });
        }

        function renderExitedEmployeesTable(employees) {
            var tableBody = $('#jo-exit-exited-table tbody');
            tableBody.empty();

            if (employees.length === 0) {
                tableBody.append('<tr><td colspan="6">' + jo_exit_admin.no_exited_employees_found + '</td></tr>');
                return;
            }

            employees.forEach(function(employee) {
                var row = $('<tr></tr>');

                // Calcola il punteggio finale (exit points + anzianità)
                var exitPoints = parseInt(employee.score) || 0;
                var years = 0;

                // Calcola gli anni di anzianità se disponibili
                if (employee.hire_year) {
                    var currentYear = new Date().getFullYear();
                    var hireYear = parseInt(employee.hire_year);

                    if (hireYear > 1900 && hireYear <= currentYear) {
                        years = currentYear - hireYear;
                    }
                }

                // Calcola il punteggio finale
                var finalScore = exitPoints + years;

                // Se il punteggio finale è già presente nell'oggetto employee, usalo
                if (employee.final_score && parseInt(employee.final_score) > 0) {
                    finalScore = parseInt(employee.final_score);
                }

                row.append('<td>' + formatDate(employee.exit_date) + '</td>');
                row.append('<td><img src="' + employee.photo_url + '" class="jo-exit-exited-photo" alt="' + employee.first_name + ' ' + employee.last_name + '"></td>');
                row.append('<td>' + employee.first_name + ' ' + employee.last_name + '</td>');
                row.append('<td>' + exitPoints + '</td>'); // Exit Points
                row.append('<td>' + finalScore + '</td>'); // Final Exit Score

                // Add actions column
                var actions = $('<td class="jo-exit-actions"></td>');
                // Removed edit button from exited employees
                actions.append('<button type="button" class="button jo-exit-reactivate-employee" data-id="' + employee.id + '" data-name="' + employee.first_name + ' ' + employee.last_name + '" title="' + jo_exit_admin.reactivate_employee + '"><span class="dashicons dashicons-undo jo-exit-reactivate-icon"></span></button>');
                actions.append('<button type="button" class="button jo-exit-delete-employee" data-id="' + employee.id + '" title="' + jo_exit_admin.delete_employee + '"><span class="dashicons dashicons-trash jo-exit-delete-icon"></span></button>');

                row.append(actions);
                tableBody.append(row);
            });
        }

        function resetForm() {
            $('#jo-exit-employee-id').val('');
            $('#jo-exit-first-name').val('');
            $('#jo-exit-last-name').val('');
            // Role field removed
            $('#jo-exit-company-code').val('');
            $('#jo-exit-photo-url').val('');
            $('#jo-exit-photo-preview').attr('src', '').hide();
            $('#jo-exit-score').val('0');
            // Gender selector removed
            $('#jo-exit-submit-button').val(jo_exit_admin.add_employee);
        }

        function showMessage(type, message) {
            var messageClass = type === 'success' ? 'jo-exit-success' : 'jo-exit-error';
            var messageHtml = '<div class="jo-exit-message ' + messageClass + '">' + message + '</div>';

            $('.jo-exit-messages').html(messageHtml);

            // Auto-hide message after 5 seconds
            setTimeout(function() {
                $('.jo-exit-message').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        function getCurrentDate() {
            var now = new Date();
            var year = now.getFullYear();
            var month = (now.getMonth() + 1).toString().padStart(2, '0');
            var day = now.getDate().toString().padStart(2, '0');
            return year + '-' + month + '-' + day;
        }

        function formatDate(dateString) {
            var date = new Date(dateString);
            var year = date.getFullYear();
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var day = date.getDate().toString().padStart(2, '0');
            return year + '-' + month + '-' + day;
        }

        function loadUsersCooldown() {
            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_get_users_cooldown',
                    'nonce': jo_exit_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderUsersCooldownTable(response.data.users);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', jo_exit_admin.error_load_users);
                }
            });
        }

        function loadUsersExpPoints() {
            $.ajax({
                url: jo_exit_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_get_users_exp_points',
                    'nonce': jo_exit_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderUsersLeaderboardTable(response.data.users);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', jo_exit_admin.error_load_users);
                }
            });
        }

        function renderUsersCooldownTable(users) {
            var tableBody = $('#jo-exit-cooldown-table tbody');
            tableBody.empty();

            if (users.length === 0) {
                tableBody.append('<tr><td colspan="5">' + jo_exit_admin.no_users_found + '</td></tr>');
                return;
            }

            users.forEach(function(user) {
                // Get template and replace placeholders
                var template = $('#jo-exit-cooldown-row-template').html();

                // Replace placeholders
                template = template.replace('{{id}}', user.id);
                template = template.replace('{{username}}', user.username);
                template = template.replace('{{display_name}}', user.display_name || user.username);

                // Set cooldown status
                var cooldownStatus = user.in_cooldown ?
                    '<span class="jo-exit-cooldown-active">' + jo_exit_admin.in_cooldown + '</span>' :
                    '<span class="jo-exit-cooldown-inactive">' + jo_exit_admin.no_cooldown + '</span>';
                template = template.replace('{{cooldown_status}}', cooldownStatus);

                // Set remaining time
                var remainingTime = user.in_cooldown ?
                    user.formatted_time :
                    '-';
                template = template.replace('{{remaining_time}}', remainingTime);

                // Disable button if not in cooldown
                var buttonDisabled = !user.in_cooldown ? 'disabled' : '';
                template = template.replace('{{button_disabled}}', buttonDisabled);

                tableBody.append(template);
            });
        }

        function renderUsersLeaderboardTable(users) {
            var tableBody = $('#jo-exit-leaderboard-table tbody');
            tableBody.empty();

            if (users.length === 0) {
                tableBody.append('<tr><td colspan="4">' + jo_exit_admin.no_users_in_leaderboard + '</td></tr>');
                return;
            }

            users.forEach(function(user) {
                // Get template and replace placeholders
                var template = $('#jo-exit-leaderboard-row-template').html();

                // Replace placeholders
                template = template.replace(/{{id}}/g, user.user_id);
                template = template.replace('{{username}}', user.user_login);
                template = template.replace('{{display_name}}', user.display_name || user.user_login);
                template = template.replace(/{{exp_points}}/g, user.exp_points);

                // Add the row to the table
                tableBody.append(template);
            });
        }

        // Handle reactivate employee button click
        $(document).on('click', '.jo-exit-reactivate-employee', function() {
            var employeeId = $(this).data('id');
            var employeeName = $(this).data('name');

            // Show confirmation dialog
            if (confirm(jo_exit_admin.confirm_reactivate.replace('{name}', employeeName))) {
                $.ajax({
                    url: jo_exit_admin.ajax_url,
                    type: 'POST',
                    data: {
                        'action': 'jo_exit_reactivate_employee',
                        'nonce': jo_exit_admin.nonce,
                        'id': employeeId
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', response.data.message);
                            // Reload employee lists
                            loadEmployees();
                            loadExitedEmployees();
                        } else {
                            showMessage('error', response.data);
                        }
                    },
                    error: function() {
                        showMessage('error', jo_exit_admin.error_reactivate);
                    }
                });
            }
        });

        // Handle edit employee button click (only in Employees screen)
        $(document).on('click', '.jo-exit-edit-employee', function() {
            var employeeId = $(this).data('id');
            var firstName = $(this).data('first-name');
            var lastName = $(this).data('last-name');
            // Role field removed
            var companyCode = $(this).data('company-code');
            var photoUrl = $(this).data('photo-url');
            var score = $(this).data('score');

            // Populate the main form instead of showing a modal
            $('#jo-exit-employee-id').val(employeeId);
            $('#jo-exit-first-name').val(firstName);
            $('#jo-exit-last-name').val(lastName);
            // Role field removed
            $('#jo-exit-company-code').val(companyCode);
            $('#jo-exit-photo-url').val(photoUrl);
            $('#jo-exit-score').val(score);
            $('#jo-exit-photo-preview').attr('src', photoUrl).show();
            $('#jo-exit-submit-button').val(jo_exit_admin.update_employee);

            // Scroll to the form
            $('html, body').animate({
                scrollTop: $('#jo-exit-employee-form').offset().top - 50
            }, 500);
        });

        // Edit employee form submission is now handled by the main form

        // User Votes Page Functionality
        if ($('#jo-exit-user-votes-table').length) {
            // Load user votes on page load
            loadUserVotes();

            // Load users and employees for filters
            loadUsersForFilter();
            loadEmployeesForFilter();

            // Handle apply filters button
            $('#jo-exit-apply-filters').on('click', function() {
                loadUserVotes();
            });

            // Handle reset filters button
            $('#jo-exit-reset-filters').on('click', function() {
                $('#jo-exit-user-filter').val('0');
                $('#jo-exit-employee-filter').val('0');
                loadUserVotes();
            });

            // Handle edit vote button click
            $(document).on('click', '.jo-exit-edit-vote', function() {
                var userId = $(this).data('user-id');
                var employeeId = $(this).data('employee-id');
                var userName = $(this).data('user-name');
                var employeeName = $(this).data('employee-name');
                var voteType = $(this).data('vote-type');
                var points = $(this).data('points');

                // Fill the edit vote modal
                $('#jo-exit-edit-vote-user-id').val(userId);
                $('#jo-exit-edit-vote-employee-id').val(employeeId);
                $('#jo-exit-edit-vote-user').text(userName);
                $('#jo-exit-edit-vote-employee').text(employeeName);
                $('#jo-exit-edit-vote-type').val(voteType);
                $('#jo-exit-edit-vote-points').val(points);

                // Show the modal
                $('#jo-exit-edit-vote-modal').show();
            });

            // Handle edit vote modal close
            $('.jo-exit-modal-close, .jo-exit-modal-cancel').on('click', function() {
                $('#jo-exit-edit-vote-modal').hide();
                $('#jo-exit-reset-user-votes-modal').hide();
            });

            // Handle edit vote form submission
            $('#jo-exit-edit-vote-form').on('submit', function(e) {
                e.preventDefault();

                var formData = {
                    'action': 'jo_exit_update_user_vote',
                    'nonce': jo_exit_admin.nonce,
                    'user_id': $('#jo-exit-edit-vote-user-id').val(),
                    'employee_id': $('#jo-exit-edit-vote-employee-id').val(),
                    'vote_type': $('#jo-exit-edit-vote-type').val(),
                    'points': $('#jo-exit-edit-vote-points').val()
                };

                $.ajax({
                    url: jo_exit_admin.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', jo_exit_admin.vote_updated);
                            // Close modal and reload votes
                            $('#jo-exit-edit-vote-modal').hide();
                            loadUserVotes();
                        } else {
                            showMessage('error', response.data);
                        }
                    },
                    error: function() {
                        showMessage('error', jo_exit_admin.error_update_vote);
                    }
                });
            });

            // Handle reset user votes button click
            $(document).on('click', '.jo-exit-reset-user-votes', function() {
                var userId = $(this).data('user-id');
                var userName = $(this).data('user-name');

                // Fill the reset user votes modal
                $('#jo-exit-reset-user-votes-user-id').val(userId);
                $('#jo-exit-reset-user-votes-user').text(userName);

                // Show the modal
                $('#jo-exit-reset-user-votes-modal').show();
            });

            // Handle reset user votes form submission
            $('#jo-exit-reset-user-votes-form').on('submit', function(e) {
                e.preventDefault();

                var formData = {
                    'action': 'jo_exit_reset_user_votes',
                    'nonce': jo_exit_admin.nonce,
                    'user_id': $('#jo-exit-reset-user-votes-user-id').val()
                };

                $.ajax({
                    url: jo_exit_admin.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', jo_exit_admin.user_votes_reset);
                            // Close modal and reload votes
                            $('#jo-exit-reset-user-votes-modal').hide();
                            loadUserVotes();
                        } else {
                            showMessage('error', response.data);
                        }
                    },
                    error: function() {
                        showMessage('error', jo_exit_admin.error_reset_user_votes);
                    }
                });
            });

            // The Fix All User Votes button has been removed because it is no longer needed
        }
    });

    // Function to load user votes
    function loadUserVotes() {
        var userId = $('#jo-exit-user-filter').val() || 0;
        var employeeId = $('#jo-exit-employee-filter').val() || 0;

        $.ajax({
            url: jo_exit_admin.ajax_url,
            type: 'POST',
            data: {
                'action': 'jo_exit_get_user_votes',
                'nonce': jo_exit_admin.nonce,
                'user_id': userId,
                'employee_id': employeeId
            },
            success: function(response) {
                if (response.success) {
                    renderUserVotesTable(response.data.votes);
                } else {
                    showMessage('error', response.data);
                }
            },
            error: function() {
                showMessage('error', jo_exit_admin.error_load_user_votes);
            }
        });
    }

    // Function to render user votes table
    function renderUserVotesTable(votes) {
        var tableBody = $('#jo-exit-user-votes-table tbody');
        tableBody.empty();

        if (votes.length === 0) {
            tableBody.append('<tr><td colspan="7">' + jo_exit_admin.no_user_votes_found + '</td></tr>');
            return;
        }

        votes.forEach(function(vote) {
            var row = $('<tr></tr>');

            // User column
            row.append('<td>' + vote.display_name + ' (' + vote.user_login + ')</td>');

            // Employee column
            row.append('<td>' + vote.first_name + ' ' + vote.last_name + '</td>');

            // Photo column
            row.append('<td><img src="' + vote.photo_url + '" class="jo-exit-employee-photo" alt="' + vote.first_name + ' ' + vote.last_name + '"></td>');

            // Vote type column
            var voteTypeDisplay = vote.vote_type === 'exit' ?
                '<span class="jo-exit-vote-exit">' + jo_exit_admin.exit_text + '</span>' :
                '<span class="jo-exit-vote-nope">' + jo_exit_admin.nope_text + '</span>';
            row.append('<td>' + voteTypeDisplay + '</td>');

            // Points column
            row.append('<td>' + vote.points + '</td>');

            // Date column
            var date = new Date(vote.created_at);
            row.append('<td>' + date.toLocaleDateString() + ' ' + date.toLocaleTimeString() + '</td>');

            // Actions column
            var actions = $('<td class="jo-exit-actions"></td>');

            // Add edit button
            actions.append('<button type="button" class="button jo-exit-edit-vote" ' +
                'data-user-id="' + vote.user_identifier + '" ' +
                'data-employee-id="' + vote.employee_id + '" ' +
                'data-user-name="' + vote.display_name + '" ' +
                'data-employee-name="' + vote.first_name + ' ' + vote.last_name + '" ' +
                'data-vote-type="' + vote.vote_type + '" ' +
                'data-points="' + vote.points + '" ' +
                'title="' + jo_exit_admin.edit_vote + '"><span class="dashicons dashicons-edit"></span></button>');

            // Add reset votes button (only show once per user)
            if (!row.hasClass('has-reset-button-' + vote.user_identifier)) {
                actions.append('<button type="button" class="button jo-exit-reset-user-votes" ' +
                    'data-user-id="' + vote.user_identifier + '" ' +
                    'data-user-name="' + vote.display_name + '" ' +
                    'title="' + jo_exit_admin.reset_all_votes + '"><span class="dashicons dashicons-trash"></span></button>');
                row.addClass('has-reset-button-' + vote.user_identifier);
            }

            row.append(actions);
            tableBody.append(row);
        });
    }

    // Function to load users for filter
    function loadUsersForFilter() {
        $.ajax({
            url: jo_exit_admin.ajax_url,
            type: 'POST',
            data: {
                'action': 'jo_exit_get_users_exp_points',
                'nonce': jo_exit_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var users = response.data.users;
                    var userFilter = $('#jo-exit-user-filter');

                    users.forEach(function(user) {
                        userFilter.append('<option value="' + user.user_id + '">' + user.display_name + ' (' + user.user_login + ')</option>');
                    });
                }
            }
        });
    }

    // Function to load employees for filter
    function loadEmployeesForFilter() {
        $.ajax({
            url: jo_exit_admin.ajax_url,
            type: 'POST',
            data: {
                'action': 'jo_exit_get_employees',
                'nonce': jo_exit_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var employees = response.data.employees;
                    var employeeFilter = $('#jo-exit-employee-filter');

                    employees.forEach(function(employee) {
                        employeeFilter.append('<option value="' + employee.id + '">' + employee.first_name + ' ' + employee.last_name + '</option>');
                    });
                }
            }
        });
    }

    // Helper function to show messages
    function showMessage(type, message) {
        var messageContainer = $('.jo-exit-messages');
        messageContainer.removeClass('success error').addClass(type).html(message).show();

        // Hide message after 5 seconds
        setTimeout(function() {
            messageContainer.fadeOut();
        }, 5000);
    }

    // Helper function to reset form
    function resetForm() {
        $('#jo-exit-employee-form')[0].reset();
        $('#jo-exit-employee-id').val('');
        $('#jo-exit-photo-preview').attr('src', '').hide();
        $('#jo-exit-submit-button').val(jo_exit_admin.add_employee);
    }

    // Helper function to get current date in YYYY-MM-DD format
    function getCurrentDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = (now.getMonth() + 1).toString().padStart(2, '0');
        var day = now.getDate().toString().padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

})(jQuery);
