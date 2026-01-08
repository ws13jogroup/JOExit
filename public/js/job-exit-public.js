/**
 * Public JavaScript for JOb Exit Plugin
 */

// Create a global namespace for JOb Exit functions
window.JoExit = {};

(function($) {
    'use strict';

    // Current screen
    let currentScreen = 'home';

    // Store employees data
    let employeesData = [];
    let currentEmployeeIndex = 0;
    let votedEmployeesCount = 0; // Track how many employees have been voted for
    let allEmployeesVoted = false; // Flag to indicate if all employees have been voted for

    // Arrays of ironic comments - these will be loaded from localized strings
    const exitComments = jo_exit_public.exit_comments || [
        "It will be a great loss... or maybe not 😅",
        "Another talent leaving... towards the exit! 😏",
        "Promotion pending... to the door! 🙄",
        "Someone prepare the reference letter... very short! 😉",
        "Their JOby is already deactivated! 😜",
        "Their desk is already free! 🙋",
        "Goodbye, we won't miss you... much! 😊",
        "This is why you should never get too attached to colleagues! 😂",
        "Let's see who will be next... 😈",
        "One less problem for the HR office! 😎"
    ];

    const nopeComments = jo_exit_public.nope_comments || [
        "Saved by a hair... this time! 🙌",
        "Survived for another day! 😍",
        "Their luck won't last forever... 😝",
        "Stays... for now! 😎",
        "Today is their lucky day! 😉",
        "Escaped firing... for this time! 😁",
        "Someone up there is protecting them! 😊",
        "Will continue to occupy their desk... for now! 😬",
        "Still safe... but we'll keep an eye on them! 🙂",
        "Avoided the worst... at least for today! 😏"
    ];

    // Global Hammer.js configuration
    if (typeof Hammer !== 'undefined') {
        // Configure Hammer.js for better touch detection
        Hammer.defaults.inputClass = Hammer.TouchInput;

        if (jo_exit_public.debug) {
            // console.log('Hammer.js global configuration applied');
        }
    } else if (jo_exit_public.debug) {
        console.error('Hammer.js not loaded!');
    }

    // No voting limits - users can vote multiple times for the same employee

    $(document).ready(function() {
        // Initialize the app if we're on the main app page
        if ($('.jo-exit-container').length > 0) {
            initApp();

            // Handle navigation clicks
            $('.jo-exit-nav-item').on('click', function() {
                const screen = $(this).data('screen');

                // Skip leaderboard-trophy as it's handled separately in jo-exit-app.php
                if (screen !== 'leaderboard-trophy') {
                    navigateTo(screen);
                }
            });
        }
    });

    /**
     * Initialize the app
     */
    function initApp() {
        // Load the initial screen (home)
        loadHomeScreen();

        // Set the active navigation item
        setActiveNavItem('home');
    }

    /**
     * Navigate to a screen
     *
     * @param {string} screen - The screen to navigate to
     */
    function navigateTo(screen) {
        // Hide all screens
        $('.jo-exit-screen').hide();

        // Special case for account-related screens
        let displayScreen = screen;
        if (screen === 'register' || screen === 'edit-profile') {
            displayScreen = 'account';
        }

        // Show the selected screen
        $(`#jo-exit-${displayScreen}-screen`).show();

        // Set the active navigation item
        setActiveNavItem(displayScreen);

        // Update current screen
        currentScreen = screen;

        // Load screen data if needed
        switch (screen) {
            case 'home':
                loadHomeScreen();
                // Make sure to initialize swipe when returning to home screen
                setTimeout(function() {
                    initSwipe();
                    if (jo_exit_public.debug) {
                        // console.log('Swipe initialized after navigation to home screen');
                    }
                }, 100);
                break;
            case 'leaderboard':
                loadLeaderboardScreen();
                break;
            case 'exited':
                loadExitScreen();
                break;
            case 'info':
                loadInfoScreen();
                break;
            case 'account':
                loadAccountScreen();
                break;
            case 'register':
                loadRegisterScreen();
                break;
            case 'edit-profile':
                loadEditProfileScreen();
                break;
            case 'global-leaderboard':
                loadGlobalLeaderboardScreen();
                break;
        }
    }

    /**
     * Set the active navigation item
     *
     * @param {string} screen - The active screen
     */
    function setActiveNavItem(screen) {
        // Remove active class from all navigation items
        $('.jo-exit-nav-item').removeClass('active');

        // Add active class to the selected navigation item
        $(`.jo-exit-nav-item[data-screen="${screen}"]`).addClass('active');
    }

    /**
     * Load the home screen (swipe interface)
     */
    function loadHomeScreen() {
        // console.log('Loading home screen...');

        // Reset employee data before loading
        employeesData = [];
        currentEmployeeIndex = 0;
        votedEmployeesCount = 0; // Reset the counter
        allEmployeesVoted = false; // Reset the flag

        // console.log('Reset employee data, votedEmployeesCount:', votedEmployeesCount, 'allEmployeesVoted:', allEmployeesVoted);

        // Check if user is logged in
        if (!jo_exit_public.is_user_logged_in) {
            // console.log('User not logged in, showing login message');
            showLoginMessage();
            return;
        }

        // console.log('User is logged in, checking cooldown period');

        // Check if user needs to wait before voting again
        if (checkCooldownPeriod()) {
            // console.log('User is in cooldown period, not loading employees');
            return;
        }

        // Show loading indicator
        $('#jo-exit-home-screen').html('<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>' + jo_exit_public.loading_text + '</div>');

        // console.log('Loading employees...');

        // Load employees
        loadEmployees();
    }

    /**
     * Load employees data
     */
    function loadEmployees() {

        // Debug output
        if (jo_exit_public.debug) {
            // console.log('AJAX URL:', jo_exit_public.ajax_url);
            // console.log('Nonce:', jo_exit_public.nonce);
        }

        // Get employees data
        $.ajax({
            url: jo_exit_public.use_custom_ajax ? jo_exit_public.custom_ajax_url : jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                'action': jo_exit_public.use_custom_ajax ? 'get_employees' : 'jo_exit_get_employees',
                'nonce': jo_exit_public.nonce
            },
            success: function(response) {
                if (jo_exit_public.debug) {
                    // console.log('AJAX Response:', response);
                }
                if (response.success) {
                    // Make sure we have a valid response
                    if (response.data && Array.isArray(response.data.employees)) {
                        // Use all employees, no filtering
                        employeesData = response.data.employees;

                        if (jo_exit_public.debug) {
                            // console.log('All Employees:', employeesData.length);
                            // console.log('First employee data:', employeesData[0]);
                        }
                    } else {
                        employeesData = [];
                    }

                    currentEmployeeIndex = 0;

                    // Check if there are any employees available
                    if (!employeesData || employeesData.length === 0) {
                        // Show message that no employees are available
                        $('#jo-exit-home-screen').html('<div class="jo-exit-no-cards">' + jo_exit_public.no_employees_text + '</div>');
                    } else {
                        // Render the home screen with available employees
                        renderHomeScreen();
                    }
                } else {
                    showMessage(response.data);
                }
            },
            error: function(xhr, status, error) {
                if (jo_exit_public.debug) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                }
                showMessage(jo_exit_public.error_loading_employees);
            }
        });
    }

    /**
     * Render the home screen
     */
    function renderHomeScreen() {
        // Clear the home screen
        $('#jo-exit-home-screen').empty();

        // Double check if there are any employees available
        if (!employeesData || !Array.isArray(employeesData) || employeesData.length === 0) {
            // Show message that no employees are available
            $('#jo-exit-home-screen').html('<div class="jo-exit-no-cards">' + jo_exit_public.no_employees_text + '</div>');
            return;
        }

        // Make sure currentEmployeeIndex is valid
        if (currentEmployeeIndex >= employeesData.length) {
            currentEmployeeIndex = 0;
        }

        // Get the current employee
        const employee = employeesData[currentEmployeeIndex];

        // Final check to make sure we have a valid employee
        if (!employee || !employee.id) {
            // Show message that no valid employees are available
            $('#jo-exit-home-screen').html('<div class="jo-exit-no-cards">' + jo_exit_public.no_valid_employees_text + '</div>');
            return;
        }

        // Clear the home screen first
        $('#jo-exit-home-screen').empty();

        // Create the card container
        const cardContainer = $('<div class="jo-exit-card-container"></div>');

        // Add the current employee card
        const card = createEmployeeCard(employee);

        // Add the card to the container
        cardContainer.append(card);

        // Force a reflow to ensure the card is rendered with correct position
        card[0].offsetHeight;

        // Add animation class to the card
        card.addClass('jo-exit-card-animate-in');

        // Remove animation class after animation completes to enable drag
        setTimeout(function() {
            card.removeClass('jo-exit-card-animate-in');
        }, 500); // Same duration as the animation

        // Add the swipe buttons
        const swipeButtons = $(`
            <div class="jo-exit-swipe-buttons">
                <div class="jo-exit-swipe-button jo-exit-exit-button" data-vote="nope">
                    <i class="fas fa-times"></i>
                </div>
                <div class="jo-exit-swipe-button jo-exit-like-button" data-vote="exit">
                    <i class="fas fa-person-walking-arrow-right"></i>
                </div>
            </div>
        `);

        // Add the card container and buttons to the home screen
        $('#jo-exit-home-screen').append(cardContainer);
        $('#jo-exit-home-screen').append(swipeButtons);

        // Initialize swipe after animation completes
        setTimeout(function() {
            initSwipe();
            if (jo_exit_public.debug) {
                // console.log('Swipe initialized for first card');
            }
        }, 550); // Slightly longer than animation duration
    }

    /**
     * Create an employee card
     *
     * @param {Object} employee - The employee data
     * @returns {jQuery} The employee card
     */
    function createEmployeeCard(employee) {
        // Debug employee data
        if (jo_exit_public.debug) {
            // console.log('Creating card for employee:', employee);
            // console.log('Employee hire_year:', employee.hire_year);
        }

        // Calculate years of service
        let yearsOfService = '';
        if (employee.hire_year) {
            const currentYear = new Date().getFullYear();
            const years = currentYear - parseInt(employee.hire_year);
            const yearLabel = years === 1 ? jo_exit_public.year_singular : jo_exit_public.year_plural;
            yearsOfService = `<div class="jo-exit-card-years">${jo_exit_public.seniority_label}: ${years} ${yearLabel}</div>`;
        } else {
            // If hire_year is not available, show a default message
            yearsOfService = `<div class="jo-exit-card-years">${jo_exit_public.seniority_label}: ${jo_exit_public.not_available}</div>`;

            // Log in debug mode
            if (jo_exit_public.debug) {
                // console.log('hire_year not available for employee:', employee.id);
            }
        }

        return $(`
            <div class="jo-exit-card" data-id="${employee.id}">
                <div class="jo-exit-exit-indicator jo-exit-swipe-indicator">EXIT</div>
                <div class="jo-exit-like-indicator jo-exit-swipe-indicator">NOPE</div>
                <img src="${employee.photo_url}" class="jo-exit-card-photo" alt="${employee.first_name} ${employee.last_name}">
                <div class="jo-exit-card-info">
                    <div class="jo-exit-card-name">${employee.first_name} ${employee.last_name}</div>
                    <div class="jo-exit-card-code">${employee.company_code}</div>
                    ${yearsOfService}
                </div>
            </div>
        `);
    }

    // ... rest of the JavaScript file remains the same, but with localized strings
    
    // Expose functions to global namespace
    window.JoExit.loadHomeScreen = loadHomeScreen;
    window.JoExit.loadLeaderboardScreen = loadLeaderboardScreen;
    window.JoExit.loadExitScreen = loadExitScreen;
    window.JoExit.loadGlobalLeaderboardScreen = loadGlobalLeaderboardScreen;
    window.JoExit.loadEmployees = loadEmployees;
    window.JoExit.navigateTo = navigateTo;
    window.JoExit.loginUser = loginUser;
    window.JoExit.registerUser = registerUser;
    window.JoExit.logoutUser = logoutUser;
    window.JoExit.updateUserProfile = updateUserProfile;
    window.JoExit.loadUserVotingData = loadUserVotingData;
    window.JoExit.uploadAvatar = uploadAvatar;
    window.JoExit.generateRandomAvatar = generateRandomAvatar;
    window.JoExit.useAvatarUrl = useAvatarUrl;

})(jQuery);
