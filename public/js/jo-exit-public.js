/**
 * Public JavaScript for JOb Exit Plugin
 */

// Create a global namespace for JOb Exit functions
window.JoExit = window.JoExit || {};
window.JoExit.userVotes = {};

(function ($) {
    'use strict';

    // Current screen
    let currentScreen = 'home';

    // Store employees data
    let employeesData = [];
    let currentEmployeeIndex = 0;
    let votedEmployeesCount = 0; // Track how many employees have been voted for
    let allEmployeesVoted = false; // Flag to indicate if all employees have been voted for

    // Arrays of ironic comments from localized data
    const exitComments = jo_exit_public.exit_comments || [];
    const nopeComments = jo_exit_public.nope_comments || [];

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

    $(document).ready(function () {
        // Initialize the app if we're on the main app page
        if ($('.jo-exit-container').length > 0) {
            initApp();

            // Handle navigation clicks
            $('.jo-exit-nav-item').on('click', function () {
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

        // Apply dark mode if enabled
        applyDarkMode();
    }

    /**
     * Apply dark mode based on user preference
     */
    function applyDarkMode() {
        // Check if dark mode is enabled in user meta
        if (jo_exit_public.is_user_logged_in && jo_exit_public.dark_mode === 'on') {
            $('body').addClass('jo-exit-dark-mode');
        } else {
            $('body').removeClass('jo-exit-dark-mode');
        }
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
                setTimeout(function () {
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
            success: function (response) {
                if (jo_exit_public.debug) {
                    // console.log('AJAX Response:', response);
                }
                if (response.success) {
                    // Make sure we have a valid response
                    if (response.data && Array.isArray(response.data.employees)) {
                        // Use all employees, no filtering
                        employeesData = response.data.employees;

                        // Store user votes in a global variable
                        window.JoExit.userVotes = response.data.user_votes || {};

                        if (jo_exit_public.debug) {
                            // console.log('All Employees:', employeesData.length);
                            // console.log('First employee data:', employeesData[0]);
                            // console.log('User votes:', window.JoExit.userVotes);
                        }
                    } else {
                        employeesData = [];
                        window.JoExit.userVotes = {};
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
            error: function (xhr, status, error) {
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
        setTimeout(function () {
            card.removeClass('jo-exit-card-animate-in');
        }, 500); // Same duration as the animation

        // Get the current exit votes count if user is logged in
        let exitVotesCount = 0;
        let maxExitVotes = 5; // Maximum allowed exit votes

        if (jo_exit_public.is_user_logged_in) {
            // Make AJAX request to get current exit votes count asynchronously
            $.ajax({
                url: jo_exit_public.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_get_exit_votes_count',
                    'nonce': jo_exit_public.nonce
                },
                success: function (response) {
                    if (response.success && response.data) {
                        exitVotesCount = response.data.count || 0;
                        maxExitVotes = response.data.max || 5;
                        // Update the counter display if it exists
                        $('.jo-exit-votes-counter span').text(`${exitVotesCount}/${maxExitVotes}`);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error getting exit votes count:', status, error);
                }
            });
        }

        // Add the swipe buttons with vote counter
        const swipeButtons = $(`
            <div class="jo-exit-swipe-buttons">
                <div class="jo-exit-swipe-button jo-exit-exit-button" data-vote="nope">
                    <i class="fas fa-times"></i>
                </div>
                <div class="jo-exit-votes-counter">
                    <span>${exitVotesCount}/${maxExitVotes}</span>
                </div>
                <div class="jo-exit-swipe-button jo-exit-like-button" data-vote="exit">
                    <i class="fas fa-person-walking-arrow-right"></i>
                </div>
            </div>
        `);

        // Create card counter
        const cardCounter = $(`
            <div class="jo-exit-card-counter">
                ${jo_exit_public.card_counter_label} ${currentEmployeeIndex + 1} ${jo_exit_public.cards_counter_of} ${employeesData.length}
            </div>
        `);

        // Create a container for the counter to ensure proper centering
        const counterContainer = $('<div style="text-align: center;"></div>');
        counterContainer.append(cardCounter);

        // Add the card container, counter and buttons to the home screen
        $('#jo-exit-home-screen').append(cardContainer);
        $('#jo-exit-home-screen').append(counterContainer);
        $('#jo-exit-home-screen').append(swipeButtons);

        // Initialize swipe after animation completes
        setTimeout(function () {
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

        // Check if this employee is in the user's team (has an EXIT vote)
        let inTeamLabel = '';
        if (jo_exit_public.is_user_logged_in && window.JoExit.userVotes) {
            const userVotes = window.JoExit.userVotes;
            if (userVotes[employee.id] && userVotes[employee.id].vote === 'exit') {
                inTeamLabel = `<div class="jo-exit-in-team-label">${jo_exit_public.in_team_label || 'In Team'}</div>`;
            }
        }

        return $(`
            <div class="jo-exit-card" data-id="${employee.id}">
                <div class="jo-exit-exit-indicator jo-exit-swipe-indicator">EXIT</div>
                <div class="jo-exit-like-indicator jo-exit-swipe-indicator">NOPE</div>
                ${inTeamLabel}
                <img src="${employee.photo_url}" class="jo-exit-card-photo" alt="${employee.first_name} ${employee.last_name}">
                <div class="jo-exit-card-info">
                    <div class="jo-exit-card-name">${employee.first_name} ${employee.last_name}</div>
                    <div class="jo-exit-card-code">${employee.company_code}</div>
                    ${yearsOfService}
                </div>
            </div>
        `);
    }

    /**
     * Initialize swipe functionality
     */
    function initSwipe() {
        // Handle swipe button clicks
        $('.jo-exit-swipe-button').off('click').on('click', function () {
            const voteType = $(this).data('vote');
            const employeeId = $('.jo-exit-card').data('id');

            // Animate the card
            if (voteType === 'nope') {
                // Nope button (verde) -> card scorre a sinistra
                animateCardSwipe('left', employeeId, 'nope');
            } else {
                // Exit button (rosso) -> card scorre a destra
                animateCardSwipe('right', employeeId, 'exit');
            }
        });

        // Make sure we have jQuery and Hammer.js available
        if (typeof $ === 'undefined') {
            console.error('jQuery not loaded!');
            return;
        }

        if (typeof Hammer === 'undefined') {
            console.error('Hammer.js not loaded!');
            return;
        }

        // Find the card element
        const $card = $('.jo-exit-card');

        // Debug logging
        if (jo_exit_public.debug) {
            // console.log('initSwipe called, card found:', $card.length > 0);
        }

        // If no card is found, retry after a short delay
        if ($card.length === 0) {
            if (jo_exit_public.debug) {
                // console.log('No card found, retrying in 100ms');
            }
            setTimeout(initSwipe, 100);
            return;
        }

        // Get the DOM element
        const card = $card[0];

        // Reset the card position and style
        resetCardPosition($card);

        // Clean up any existing Hammer instances
        if (card.hammer) {
            card.hammer.destroy();
            card.hammer = null;
        }

        try {
            // Create a simple Hammer instance directly on the card
            const hammer = new Hammer(card, {
                inputClass: Hammer.TouchInput,
                touchAction: 'none'
            });

            // Configure for horizontal panning only
            hammer.get('pan').set({
                direction: Hammer.DIRECTION_HORIZONTAL,
                threshold: 5
            });

            // Store hammer instance on the element
            card.hammer = hammer;

            // Variables to track swipe state
            let isDragging = false;
            let startX = 0;
            let startY = 0;
            let initialOffset = { left: 0, top: 0 };

            // Handle pan start
            hammer.on('panstart', function (e) {
                isDragging = true;

                // Store initial position
                initialOffset = $card.offset();
                startX = e.center.x;
                startY = e.center.y;

                if (jo_exit_public.debug) {
                    // console.log('Pan start detected', e);
                    // console.log('Initial offset:', initialOffset);
                }

                // Remove transition for immediate response
                $card.css({
                    'transition': 'none',
                    'transform': 'translate(-50%, -50%)'
                });
                $card.addClass('jo-exit-dragging');
            });

            // Handle pan move
            hammer.on('pan', function (e) {
                if (!isDragging) return;

                // Calculate the new position
                const deltaX = e.deltaX;
                const rotate = deltaX * 0.05;

                if (jo_exit_public.debug) {
                    // console.log('Pan move detected, deltaX:', deltaX);
                }

                // Apply transform to the card - ONLY translate and rotate
                $card.css({
                    'transform': `translate(calc(-50% + ${deltaX}px), -50%) rotate(${rotate}deg)`
                });

                // Show appropriate indicator based on drag direction
                if (deltaX < -50) {
                    // Swiping left - NOPE (no points)
                    $card.addClass('swiping-left').removeClass('swiping-right');
                } else if (deltaX > 50) {
                    // Swiping right - EXIT (add points)
                    $card.addClass('swiping-right').removeClass('swiping-left');
                } else {
                    $card.removeClass('swiping-left swiping-right');
                }
            });

            // Handle pan end
            hammer.on('panend', function (e) {
                if (!isDragging) return;
                isDragging = false;

                if (jo_exit_public.debug) {
                    // console.log('Pan end detected', e);
                }

                // Remove dragging class
                $card.removeClass('jo-exit-dragging');

                // Add transition for smooth animation
                $card.css('transition', 'transform 0.3s ease');

                const deltaX = e.deltaX;
                const threshold = 80;

                if (jo_exit_public.debug) {
                    // console.log('Final deltaX:', deltaX, 'threshold:', threshold);
                }

                if (deltaX < -threshold) {
                    // Swiped left - NOPE (no points)
                    animateCardSwipe('left', $card.data('id'), 'nope');
                } else if (deltaX > threshold) {
                    // Swiped right - EXIT (add points)
                    // Per la sincronicità, l'animazione chiamerà recordVote che ora è asincrono
                    animateCardSwipe('right', $card.data('id'), 'exit');
                } else {
                    // Reset position if not swiped far enough
                    if (jo_exit_public.debug) {
                        // console.log('Not swiped far enough, resetting position');
                    }
                    resetCardPosition($card);
                }
            });

            if (jo_exit_public.debug) {
                // console.log('Hammer initialized successfully on card ID:', $card.data('id'));
            }

        } catch (error) {
            console.error('Error initializing Hammer:', error);
        }
    }

    /**
     * Reset card position to center
     */
    function resetCardPosition($card) {
        $card.css({
            'cursor': 'grab',
            'touch-action': 'none',
            'user-select': 'none',
            '-webkit-user-select': 'none',
            '-moz-user-select': 'none',
            '-ms-user-select': 'none',
            'transform': 'translate(-50%, -50%)',
            'transition': 'transform 0.3s ease'
        });
        $card.removeClass('swiping-left swiping-right jo-exit-dragging');
    }

    /**
     * Animate card swipe in a specific direction
     */
    async function animateCardSwipe(direction, employeeId, voteType) {
        const $card = $('.jo-exit-card');

        // Add transition for smooth animation
        $card.css('transition', 'transform 0.5s ease');

        if (direction === 'left') {
            // Swipe left animation
            $card.css('transform', 'translate(-200%, -50%) rotate(-30deg)');
        } else {
            // Swipe right animation
            $card.css('transform', 'translate(100%, -50%) rotate(30deg)');
        }

        // console.log('animateCardSwipe: Recording vote for employee ID:', employeeId, 'vote type:', voteType);

        // Store the timeout ID in a global variable so we can clear it if needed
        window.nextCardTimeout = null;

        try {
            // Show ironic comment based on vote type immediately
            showIronicComment(voteType);

            // Record the vote asynchronously
            const shouldContinue = await recordVote(employeeId, voteType);

            // console.log('animateCardSwipe: shouldContinue =', shouldContinue);

            // If we shouldn't continue (cooldown was set), show the animation and comment, then show cooldown
            if (!shouldContinue) {
                // console.log('Cooldown needed, but showing animation first');

                // Wait for animation to complete, then show cooldown
                window.nextCardTimeout = setTimeout(function () {
                    // Fade out the comment by removing the visible class
                    $('.jo-exit-comment').removeClass('jo-exit-comment-visible');

                    // Remove the comment after animation completes
                    setTimeout(function () {
                        $('.jo-exit-comment-container').remove();

                        // Now show the cooldown screen
                        // console.log('Animation complete, now showing cooldown');
                        checkCooldownPeriod(true);
                    }, 300);
                }, 2000); // 2 seconds to allow reading the comment

                return;
            }

            // If we should continue, wait for animation to complete, then show next card
            window.nextCardTimeout = setTimeout(function () {
                // Fade out the comment by removing the visible class
                $('.jo-exit-comment').removeClass('jo-exit-comment-visible');

                // Remove the comment after animation completes
                setTimeout(function () {
                    $('.jo-exit-comment-container').remove();
                }, 300);

                showNextCard();
            }, 2000); // 2 seconds to allow reading the comment
        } catch (error) {
            // If we caught the COOLDOWN_ACTIVATED error, it means the cooldown screen is already shown
            if (error.message === 'COOLDOWN_ACTIVATED') {
                // console.log('Caught COOLDOWN_ACTIVATED exception, cooldown screen is already shown');
                // No need to do anything else, the cooldown screen is already shown
            } else {
                // For any other error, log it and continue with normal flow
                console.error('Error in animateCardSwipe:', error);
                showMessage('An error occurred while processing your vote.');
            }
        }
    }

    /**
     * Show an ironic comment based on vote type
     *
     * @param {string} voteType - The vote type ('exit' or 'nope')
     */
    function showIronicComment(voteType) {
        // Get a random comment based on vote type - INVERTED logic
        let comments = voteType === 'exit' ? exitComments : nopeComments;
        let randomIndex = Math.floor(Math.random() * comments.length);
        let comment = comments[randomIndex];

        // Create comment element with inline style to ensure correct background color and text color
        const themeColor = jo_exit_public.theme_color || '#ff4757';
        // console.log('Creating ironic comment with theme color:', themeColor);
        const $comment = $('<div class="jo-exit-comment" style="background-color: ' + themeColor + ' !important; color: white !important;">' + comment + '</div>');

        // COMPLETELY NEW APPROACH: Create the comment with a specific class for animation
        // Remove any existing comments first
        $('.jo-exit-comment, .jo-exit-comment-container').remove();

        // Create a container div for better positioning
        const $container = $('<div class="jo-exit-comment-container"></div>');
        $('body').append($container);

        // Add the comment with a specific animation class
        $comment.addClass('jo-exit-comment-animate-from-center');
        $container.append($comment);

        // Calculate the viewport dimensions
        const viewportWidth = $(window).width();
        const viewportHeight = $(window).height();

        // Adjust size if needed for small screens
        if (viewportWidth < 500 || viewportHeight < 600) {
            $comment.css({
                'font-size': '16px',
                'padding': '15px 20px',
                'line-height': '1.3'
            });
        }

        // Force browser to recognize the element before animation
        setTimeout(function () {
            $comment.addClass('jo-exit-comment-visible');
        }, 10);
    }

    /**
     * Record a vote
     *
     * @param {number} employeeId - The employee ID
     * @param {string} voteType - The vote type ('exit' or 'nope')
     */
    function recordVote(employeeId, voteType) {
        return new Promise((resolve) => {
            // Increment the counter for voted employees
            votedEmployeesCount++;

            // Check if we've voted for all employees
            if (votedEmployeesCount >= employeesData.length) {
                allEmployeesVoted = true;
            }

            // Default flag to indicate if we should continue to the next card
            let continueToNextCard = true;

            // Final function to resolve the promise
            const finishVote = (shouldContinue) => {
                resolve(shouldContinue);
            };

            // If it's an 'exit' vote, we check the counter
            const checkCounterBeforeVote = () => {
                if (voteType === 'exit' && jo_exit_public.is_user_logged_in) {
                    $.ajax({
                        url: jo_exit_public.ajax_url,
                        type: 'POST',
                        data: {
                            'action': 'jo_exit_get_exit_votes_count',
                            'nonce': jo_exit_public.nonce
                        },
                        success: function (response) {
                            if (response.success && response.data) {
                                let currentExitVotes = response.data.count || 0;
                                let maxExitVotes = response.data.max || 5;
                                if (currentExitVotes >= maxExitVotes) {
                                    window.showLimitMessageAfterVote = true;
                                }
                            }
                            proceedToVote();
                        },
                        error: function () {
                            proceedToVote();
                        }
                    });
                } else {
                    proceedToVote();
                }
            };

            const proceedToVote = () => {
                $.ajax({
                    url: jo_exit_public.use_custom_ajax ? jo_exit_public.custom_ajax_url : jo_exit_public.ajax_url,
                    type: 'POST',
                    data: {
                        'action': jo_exit_public.use_custom_ajax ? 'vote' : 'jo_exit_vote',
                        'nonce': jo_exit_public.nonce,
                        'employee_id': employeeId,
                        'vote_type': voteType,
                        'all_voted': allEmployeesVoted
                    },
                    success: function (response) {
                        if (response.success) {
                            if (jo_exit_public.is_user_logged_in) {
                                loadUserVotingData();
                                updateExitVotesCounter();

                                if (!window.JoExit.userVotes) {
                                    window.JoExit.userVotes = {};
                                }

                                if (voteType === 'exit') {
                                    const existingVote = window.JoExit.userVotes[employeeId];
                                    const points = existingVote && existingVote.points ? existingVote.points + 1 : 1;
                                    window.JoExit.userVotes[employeeId] = {
                                        vote: 'exit',
                                        points: points,
                                        timestamp: Math.floor(Date.now() / 1000)
                                    };
                                } else if (voteType === 'nope') {
                                    delete window.JoExit.userVotes[employeeId];
                                }

                                if (window.showLimitMessageAfterVote) {
                                    window.showLimitMessageAfterVote = false;
                                    showLimitMessage();
                                }

                                if (response.data && response.data.all_employees_voted) {
                                    $.ajax({
                                        url: jo_exit_public.ajax_url,
                                        type: 'POST',
                                        data: {
                                            'action': 'jo_exit_set_cooldown',
                                            'nonce': jo_exit_public.nonce
                                        },
                                        success: function () {
                                            checkCooldownPeriod(true);
                                            finishVote(false);
                                        },
                                        error: function () {
                                            checkCooldownPeriod(true);
                                            finishVote(false);
                                        }
                                    });
                                    return; // Wait for cooldown AJAX
                                }
                            }
                        } else {
                            showMessage(response.data);
                        }
                        finishVote(continueToNextCard);
                    },
                    error: function () {
                        showMessage('An error occurred while recording your vote.');
                        finishVote(continueToNextCard);
                    }
                });
            };

            checkCounterBeforeVote();
        });
    }

    // Track if we've gone through all employees in this session
    // Variable allEmployeesVoted is already declared at the top of the file

    /**
     * Delete a vote for an employee
     *
     * @param {number} employeeId - The ID of the employee to delete the vote for
     */
    function deleteVote(employeeId) {
        // Show loading indicator
        const loadingHtml = `<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.deleting_vote}</div>`;
        $('#jo-exit-voted-employees').html(loadingHtml);

        // Make AJAX request to delete the vote
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                'action': 'jo_exit_delete_vote',
                'nonce': jo_exit_public.nonce,
                'employee_id': employeeId
            },
            success: function (response) {
                if (response.success) {
                    // Reload the user voting data
                    loadUserVotingData();

                    // Update the exit votes counter
                    // Note: We don't know if the deleted vote was 'exit' or 'nope',
                    // but we update the counter anyway to ensure it's accurate
                    updateExitVotesCounter();

                    // console.log('Vote deleted successfully for employee ID:', employeeId);
                } else {
                    // Show error message
                    $('#jo-exit-voted-employees').html(`<p class="jo-exit-error">${jo_exit_public.error_deleting_vote}: ${response.data.message}</p>`);
                    console.error('Error deleting vote:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
                // Show error message
                $('#jo-exit-voted-employees').html(`<p class="jo-exit-error">${jo_exit_public.error_deleting_vote_try_again}</p>`);
                console.error('AJAX error deleting vote:', status, error);
            }
        });
    }

    /**
     * Update the exit votes counter
     */
    function updateExitVotesCounter() {
        // Make AJAX request to get current exit votes count
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                'action': 'jo_exit_get_exit_votes_count',
                'nonce': jo_exit_public.nonce
            },
            success: function (response) {
                if (response.success && response.data) {
                    // Use total exit votes count instead of session votes
                    const exitVotesCount = response.data.count || 0;
                    const maxExitVotes = response.data.max || 5;

                    // Update the counter display
                    $('.jo-exit-votes-counter span').text(`${exitVotesCount}/${maxExitVotes}`);

                    // console.log('Updated exit votes counter - total exit votes:', exitVotesCount, 'max:', maxExitVotes);

                    // Show or hide the limit message based on the vote count
                    if (exitVotesCount >= maxExitVotes) {
                        showLimitMessage();
                    } else {
                        hideLimitMessage();
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('Error updating exit votes count:', status, error);
            }
        });
    }

    /**
     * Show limit message when user has reached the maximum number of exit votes
     */
    function showLimitMessage() {
        // Check if the message already exists
        if ($('.jo-exit-limit-message').length === 0) {
            // Create the message element
            const message = $(`
                <div class="jo-exit-limit-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${jo_exit_public.limit_message || 'You have reached the maximum number of EXIT votes. New votes will replace the oldest ones.'}
                    <br>
                    ${jo_exit_public.vote_all_message || 'To confirm your score, you need to vote for all employees. Keep voting!'}
                </div>
            `);

            // Add the message after the swipe buttons
            $('.jo-exit-swipe-buttons').after(message);

            // Animate the message in
            setTimeout(function () {
                message.addClass('jo-exit-limit-message-visible');
            }, 10);
        }
    }

    /**
     * Hide limit message
     */
    function hideLimitMessage() {
        // Remove the message if it exists
        $('.jo-exit-limit-message').remove();
    }

    /**
     * Show the next card
     */
    function showNextCard() {
        // If we've already gone through all employees, show cooldown
        if (allEmployeesVoted) {
            // Set cooldown on the server
            $.ajax({
                url: jo_exit_public.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_set_cooldown',
                    'nonce': jo_exit_public.nonce
                },
                success: function (cooldownResponse) {
                    checkCooldownPeriod(true);
                },
                error: function (xhr, status, error) {
                    console.error('Error setting cooldown:', status, error);
                    checkCooldownPeriod(true);
                }
            });

            return;
        }

        // Increment the current employee index
        currentEmployeeIndex++;

        // If we've gone through all employees, show completion message
        if (!employeesData || !Array.isArray(employeesData) || currentEmployeeIndex >= employeesData.length) {
            // Mark that we've gone through all employees
            allEmployeesVoted = true;

            // If there are no employees at all, show a different message
            if (!employeesData || employeesData.length === 0) {
                $('#jo-exit-home-screen').empty().html(`
                    <div class="jo-exit-no-cards">
                        <p>Nessun dipendente disponibile per la votazione.</p>
                        <p>Ricontrolla più tardi!</p>
                    </div>
                `);
                return;
            }

            // Set cooldown on the server
            $.ajax({
                url: jo_exit_public.ajax_url,
                type: 'POST',
                data: {
                    'action': 'jo_exit_set_cooldown',
                    'nonce': jo_exit_public.nonce
                },
                success: function (cooldownResponse) {
                    checkCooldownPeriod(true);
                },
                error: function (xhr, status, error) {
                    console.error('Error setting cooldown:', status, error);
                    checkCooldownPeriod(true);
                }
            });

            return;
        }

        // Show the next employee
        const employee = employeesData[currentEmployeeIndex];

        // Check if we have a valid employee
        if (!employee || !employee.id) {
            // Show message that no valid employees are available
            $('#jo-exit-home-screen').empty().html(`
                <div class="jo-exit-no-cards">
                    <p>No valid employees available for voting.</p>
                    <p>Check back later!</p>
                </div>
            `);
            return;
        }

        // Create the new card
        const card = createEmployeeCard(employee);

        // Check if the card container exists
        if ($('.jo-exit-card-container').length === 0) {
            // If not, recreate the entire home screen
            loadHomeScreen();
            return;
        }

        // Debug log for the next employee
        if (jo_exit_public.debug) {
            // console.log('Showing next employee:', employee);
            // console.log('Current userVotes:', window.JoExit.userVotes);
            if (window.JoExit.userVotes && window.JoExit.userVotes[employee.id]) {
                // console.log('This employee has a vote:', window.JoExit.userVotes[employee.id]);
            }
        }

        // Replace the current card
        $('.jo-exit-card-container').empty().append(card);

        // Update card counter
        $('.jo-exit-card-counter').text(`${jo_exit_public.card_counter_label} ${currentEmployeeIndex + 1} ${jo_exit_public.cards_counter_of} ${employeesData.length}`);

        // Get the new card and make sure it's properly positioned first
        const $newCard = $('.jo-exit-card');
        resetCardPosition($newCard);

        // Force a reflow to ensure the position is applied
        $newCard[0].offsetHeight;

        // Then add animation class
        $newCard.addClass('jo-exit-card-animate-in');

        // Remove animation class after animation completes to enable drag
        setTimeout(function () {
            $newCard.removeClass('jo-exit-card-animate-in');
        }, 500); // Same duration as the animation

        // Re-initialize swipe for the new card after a longer delay to ensure animation is complete
        setTimeout(function () {
            initSwipe();
            if (jo_exit_public.debug) {
                // console.log('Swipe re-initialized for employee ID:', employee.id);
            }
        }, 550); // Slightly longer than animation duration
    }

    /**
     * Load the Exit Points screen
     */
    function loadLeaderboardScreen() {
        // Show loading indicator
        $('#jo-exit-leaderboard-screen').html(`<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.loading_scores}</div>`);

        // Get leaderboard data
        $.ajax({
            url: jo_exit_public.custom_ajax_url || jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                'action': 'get_leaderboard'
            },
            success: function (response) {
                // console.log('Leaderboard screen AJAX response:', response);
                try {
                    // Assicuriamoci che la risposta sia un oggetto JSON
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }

                    if (response && response.success && response.data && response.data.employees) {
                        renderLeaderboardScreen(response.data.employees);
                    } else {
                        console.error('Error loading leaderboard:', response);
                        let errorMessage = jo_exit_public.error_loading_scores;
                        if (response && response.data) {
                            errorMessage = response.data;
                        }
                        $('#jo-exit-leaderboard-screen').html('<div class="jo-exit-error">' + errorMessage + '</div>');
                    }
                } catch (e) {
                    console.error('Error parsing JSON response:', e);
                    $('#jo-exit-leaderboard-screen').html(`<div class="jo-exit-error">${jo_exit_public.error_response_format}</div>`);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error loading leaderboard:', status, error);
                let errorMessage = jo_exit_public.error_loading_scores;
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                $('#jo-exit-leaderboard-screen').html('<div class="jo-exit-error">' + errorMessage + '</div>');
            }
        });
    }

    /**
     * Render the Exit Points screen
     *
     * @param {Array} employees - The employees data
     */
    function renderLeaderboardScreen(employees) {
        // Clear the leaderboard screen
        $('#jo-exit-leaderboard-screen').empty();

        // Create the leaderboard container
        const leaderboardContainer = $('<div class="jo-exit-leaderboard"></div>');

        // If no employees, show message
        if (employees.length === 0) {
            leaderboardContainer.html(`<div class="jo-exit-no-data">${jo_exit_public.no_employees_in_leaderboard}</div>`);
            $('#jo-exit-leaderboard-screen').append(leaderboardContainer);
            return;
        }

        // Add each employee to the leaderboard
        employees.forEach(function (employee, index) {
            const rank = index + 1;
            const item = $(`
                <div class="jo-exit-leaderboard-item">
                    <div class="jo-exit-leaderboard-rank">${rank}</div>
                    <img src="${employee.photo_url}" class="jo-exit-leaderboard-photo" alt="${employee.first_name} ${employee.last_name}">
                    <div class="jo-exit-leaderboard-info">
                        <div class="jo-exit-leaderboard-name">${employee.first_name} ${employee.last_name}</div>
                        <div class="jo-exit-leaderboard-code">${employee.company_code}</div>
                        ${(() => {
                    if (employee.hire_year) {
                        const years = new Date().getFullYear() - parseInt(employee.hire_year);
                        const yearLabel = years === 1 ? jo_exit_public.year_label : jo_exit_public.years_label;
                        return `<div class="jo-exit-leaderboard-years">${jo_exit_public.seniority} ${years} ${yearLabel}</div>`;
                    } else {
                        return `<div class="jo-exit-leaderboard-years">${jo_exit_public.seniority} ${jo_exit_public.not_available}</div>`;
                    }
                })()}
                    </div>
                    <div class="jo-exit-leaderboard-score">${employee.score}</div>
                </div>
            `);

            leaderboardContainer.append(item);
        });

        // Add the leaderboard to the screen
        $('#jo-exit-leaderboard-screen').append(leaderboardContainer);
    }

    /**
     * Load the exit screen
     */
    function loadExitScreen() {
        // Show loading indicator
        $('#jo-exit-exited-screen').html(`<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.loading_exited_employees}</div>`);

        // console.log('Loading exit screen...');

        // Carica i dipendenti usciti tramite AJAX
        // Utilizziamo il custom_ajax_url se disponibile, altrimenti utilizziamo ajax_url
        $.ajax({
            url: jo_exit_public.custom_ajax_url || jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                'action': 'get_exited'
            },
            success: function (response) {
                // console.log('Exit screen AJAX response:', response);
                try {
                    // Assicuriamoci che la risposta sia un oggetto JSON
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }

                    if (response && response.success && response.data && response.data.employees) {
                        renderExitScreen(response.data.employees);
                    } else {
                        console.error('Error loading exited employees:', response);
                        // Mostra un messaggio di errore più dettagliato
                        let errorMessage = jo_exit_public.error_loading_exited;
                        if (response && response.data) {
                            errorMessage = response.data;
                        }
                        $('#jo-exit-exited-screen').html('<div class="jo-exit-error">' + errorMessage + '</div>');
                    }
                } catch (e) {
                    console.error('Error parsing JSON response:', e);
                    $('#jo-exit-exited-screen').html(`<div class="jo-exit-error">${jo_exit_public.error_response_format}</div>`);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error loading exited employees:', status, error);
                // Mostra un messaggio di errore più dettagliato
                let errorMessage = jo_exit_public.error_loading_exited;
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                $('#jo-exit-exited-screen').html('<div class="jo-exit-error">' + errorMessage + '</div>');
            }
        });
    }

    /**
     * Render the exit screen
     *
     * @param {Array} employees - The exited employees data
     */
    function renderExitScreen(employees) {
        // console.log('Rendering exit screen with employees:', employees);

        // Clear the exit screen
        $('#jo-exit-exited-screen').empty();

        // Create the exit container
        const exitContainer = $('<div class="jo-exit-exited"></div>');

        // If no exited employees or employees is not an array, show message
        if (!employees || !Array.isArray(employees) || employees.length === 0) {
            // console.log('No exited employees to display');
            exitContainer.html(`<div class="jo-exit-no-data">${jo_exit_public.no_exited_employees}</div>`);
            $('#jo-exit-exited-screen').append(exitContainer);
            return;
        }

        // Add each exited employee to the container
        employees.forEach(function (employee) {
            // console.log('Processing employee:', employee);

            try {
                // Handle both name formats (first_name/last_name or name/surname)
                const firstName = employee.first_name || employee.name || '';
                const lastName = employee.last_name || employee.surname || '';
                const exitDate = formatDate(employee.exit_date);
                const companyCode = employee.company_code || '';
                const photoUrl = employee.photo_url || 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMDAgMjAwIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2NjY2NjYyIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjM2IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSIgZmlsbD0iIzY2NjY2NiI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';
                const score = employee.score || 0;

                // Calcola il punteggio finale se non è già presente
                let finalScore = employee.final_score || 0;
                if (finalScore <= 0) {
                    // Calcola gli anni di anzianità
                    let years = 0;
                    if (employee.hire_year) {
                        years = new Date().getFullYear() - parseInt(employee.hire_year);
                    }
                    // Calcola il punteggio finale (exit points + anzianità)
                    finalScore = score + years;
                }

                // Create a cleaner display name
                const displayName = `${firstName} ${lastName}`.trim();
                const nameToShow = displayName || 'Dipendente';

                // Calcola gli anni di anzianità per la visualizzazione
                let yearsHtml = `<div class="jo-exit-exited-years">${jo_exit_public.seniority} ${jo_exit_public.not_available}</div>`;
                let years = 0;
                if (employee.hire_year) {
                    years = new Date().getFullYear() - parseInt(employee.hire_year);
                    const yearLabel = years === 1 ? jo_exit_public.year_label : jo_exit_public.years_label;
                    yearsHtml = `<div class="jo-exit-exited-years">${jo_exit_public.seniority} ${years} ${yearLabel}</div>`;
                }

                // Prepara la sezione dei punteggi player
                let playerScoreHtml = '';

                // Se l'utente è loggato e ha un punteggio player per questo dipendente
                if (employee.player_username && employee.player_points) {
                    playerScoreHtml = `
                        <div class="jo-exit-exited-player-score">
                            <div class="jo-exit-exited-player-score-label">${jo_exit_public.your_score}</div>
                            <div class="jo-exit-exited-player-score-value">
                                ${employee.player_points} ${jo_exit_public.points}
                            </div>
                        </div>
                    `;
                }

                // Aggiungi i top 3 punteggi player se disponibili
                if (employee.top_scores && employee.top_scores.length > 0) {
                    playerScoreHtml += '<div class="jo-exit-exited-top-players"><div class="jo-exit-exited-top-players-label">Top 3 Players:</div>';

                    employee.top_scores.forEach((score, index) => {
                        const playerName = score.display_name || score.user_login || 'Utente sconosciuto';
                        playerScoreHtml += `
                            <div class="jo-exit-exited-top-player">
                                <div class="jo-exit-exited-top-player-rank">${index + 1}.</div>
                                <div class="jo-exit-exited-top-player-name">${playerName}</div>
                                <div class="jo-exit-exited-top-player-score">${score.player_points} ${jo_exit_public.points}</div>
                            </div>
                        `;
                    });

                    playerScoreHtml += '</div>';
                }

                const item = $(`
                    <div class="jo-exit-exited-item">
                        <div class="jo-exit-exited-top-info">
                            <div class="jo-exit-exited-date">${jo_exit_public.exited_on} ${exitDate}</div>
                            <div class="jo-exit-exited-score">${finalScore}</div>
                        </div>
                        <div class="jo-exit-exited-bottom-info">
                            <img src="${photoUrl}" class="jo-exit-exited-photo" alt="${nameToShow}">
                            <div class="jo-exit-exited-info">
                                <div class="jo-exit-exited-name">${nameToShow}</div>
                                <div class="jo-exit-exited-code">${companyCode}</div>
                                ${yearsHtml}
                            </div>
                        </div>
                        <div class="jo-exit-exited-final-score-info">
                            ${playerScoreHtml}
                        </div>
                    </div>
                `);

                exitContainer.append(item);
            } catch (error) {
                console.error('Error rendering employee:', error, employee);
            }
        });

        // Add the container to the screen
        $('#jo-exit-exited-screen').append(exitContainer);
    }

    /**
     * Format a date string
     *
     * @param {string} dateString - The date string (YYYY-MM-DD)
     * @returns {string} The formatted date (DD/MM/YYYY)
     */
    function formatDate(dateString) {
        if (!dateString) return 'N/D';

        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'N/D';

            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        } catch (e) {
            console.error('Error formatting date:', e);
            return 'N/D';
        }
    }

    // No voting limits - users can vote multiple times for the same employee

    /**
     * Load the info screen
     */
    function loadInfoScreen() {
        // Load the info content from the server
        $.ajax({
            url: jo_exit_public.use_custom_ajax ? jo_exit_public.custom_ajax_url : jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                'action': 'jo_exit_load_info',
                'nonce': jo_exit_public.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#jo-exit-info-screen').html(response.data.content);
                } else {
                    $('#jo-exit-info-screen').html('<div class="jo-exit-error">Error loading info content.</div>');
                }
            },
            error: function () {
                // If AJAX fails, load the static content
                loadStaticInfoContent();
            }
        });
    }

    /**
     * Load static info content as fallback
     */
    function loadStaticInfoContent() {
        // Load the static info content
        $.get(jo_exit_public.plugin_url + 'partials/jo-exit-info.php', function (data) {
            $('#jo-exit-info-screen').html(data);
        }).fail(function () {
            // If that fails too, show a simple message
            const infoContent = `
                <div class="jo-exit-info-container">
                    <h2>Benvenuto in JO Exit!</h2>

                    <div class="jo-exit-info-section">
                        <h3>Cos'è JO Exit?</h3>
                        <p>JO Exit è un gioco divertente pensato per fare quattro risate tra colleghi. È ispirato alle app di dating, ma con un tocco di umorismo da ufficio!</p>
                        <p>Questo gioco è stato creato con spirito goliardico e non vuole in alcun modo essere offensivo verso nessuno. È solo un modo per alleggerire la giornata lavorativa e creare un momento di svago.</p>
                    </div>

                    <div class="jo-exit-info-section">
                        <h3>Come si gioca?</h3>
                        <ol>
                            <li><strong>Swipe a destra</strong> - Se pensi che il collega meriti un "like" (aggiunge un punto al suo punteggio)</li>
                            <li><strong>Swipe a sinistra</strong> - Se pensi che il collega meriti un "exit" (non influisce sul punteggio)</li>
                            <li>Puoi anche usare i pulsanti in basso per esprimere la tua preferenza</li>
                            <li>Controlla la <strong>Leaderboard</strong> per vedere chi ha più punti!</li>
                        </ol>
                    </div>

                    <div class="jo-exit-info-section">
                        <h3>Buon divertimento!</h3>
                        <p>Ora torna alla home e inizia a votare!</p>
                    </div>
                </div>
            `;
            $('#jo-exit-info-screen').html(infoContent);
        });
    }

    /**
     * Show a message
     *
     * @param {string} message - The message to show
     */
    function showMessage(message) {
        // Create the message element
        const messageElement = $(`<div class="jo-exit-message">${message}</div>`);

        // Add it to the body
        $('body').append(messageElement);

        // Remove it after 3 seconds
        setTimeout(function () {
            messageElement.remove();
        }, 3000);
    }

    /**
     * Show login message for non-authenticated users
     */
    function showLoginMessage() {
        const message = `
            <div class="jo-exit-login-required">
                <div class="jo-exit-login-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h3>${jo_exit_public.access_required}</h3>
                <p>${jo_exit_public.login_required_message}</p>
                <button class="jo-exit-login-button" id="jo-exit-goto-login">${jo_exit_public.login_register_button}</button>
            </div>
        `;

        $('#jo-exit-home-screen').html(message);

        // Add click handler for the login button
        $('#jo-exit-goto-login').on('click', function () {
            navigateTo('account');
        });
    }

    /**
     * Check if the user needs to wait before voting again
     * @param {boolean} forceShow - Force show cooldown message without checking server
     * @returns {boolean} True if the user needs to wait, false otherwise
     */
    function checkCooldownPeriod(forceShow) {
        // console.log('Checking cooldown period...');

        // Show loading indicator
        $('#jo-exit-home-screen').html(`<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.checking_status}</div>`);

        // If forceShow is true, it means all employees have been voted
        // So we can show the cooldown message immediately with 8 hours (28800 seconds)
        if (forceShow) {
            // console.log('FORCE SHOW COOLDOWN: Forcing cooldown message display with 8 hours remaining');
            const cooldownTime = 28800; // 8 hours in seconds

            // Clear any existing content in the home screen
            $('#jo-exit-home-screen').empty();

            // Show cooldown message immediately
            showCooldownMessage(cooldownTime * 1000); // Convert to milliseconds

            // Start the countdown timer
            startCooldownTimer(cooldownTime * 1000); // Convert to milliseconds

            // Stop any further processing
            return true;
        }

        // Make AJAX request to check cooldown status
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                'action': 'jo_exit_check_cooldown',
                'nonce': jo_exit_public.nonce
            },
            success: function (response) {
                // console.log('Cooldown check response:', response);

                if (response.success) {
                    if (response.data.in_cooldown) {
                        // console.log('User is in cooldown period, remaining time:', response.data.remaining_time, 'seconds');

                        // User is in cooldown period
                        showCooldownMessage(response.data.remaining_time * 1000); // Convert to milliseconds

                        // Start the countdown timer
                        startCooldownTimer(response.data.remaining_time * 1000); // Convert to milliseconds
                    } else {
                        // console.log('User is not in cooldown period, session votes reset to:', response.data.session_votes || 0);

                        // Update the counter display
                        const sessionVotes = response.data.session_votes || 0;
                        const maxExitVotes = 5; // Default max
                        $('.jo-exit-votes-counter span').text(`${sessionVotes}/${maxExitVotes}`);

                        // User is not in cooldown, load employees
                        loadEmployees();
                    }
                } else {
                    // Error checking cooldown, load employees anyway
                    console.error('Error checking cooldown:', response);
                    loadEmployees();
                }
            },
            error: function (xhr, status, error) {
                // Error making AJAX request, load employees anyway
                console.error('AJAX error checking cooldown:', status, error);
                loadEmployees();
            }
        });

        // console.log('Returning true to prevent immediate loadEmployees() call');

        // Return true to prevent loadEmployees() from being called immediately
        return true;
    }

    /**
     * Show cooldown message with remaining time
     * @param {number} remainingTime - Remaining time in milliseconds
     */
    function showCooldownMessage(remainingTime) {
        const remainingSeconds = Math.ceil(remainingTime / 1000);
        // console.log('Showing cooldown message with remaining time:', remainingSeconds, 'seconds');

        // Clear any existing content and timers
        clearTimeout(window.nextCardTimeout);
        $('.jo-exit-comment-container').remove();
        $('#jo-exit-home-screen').empty();

        // Create the cooldown message
        const message = `
            <div class="jo-exit-cooldown-required">
                <div class="jo-exit-cooldown-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <h3>${jo_exit_public.cooldown_title}</h3>
                <p>${jo_exit_public.cooldown_message}</p>
                <div class="jo-exit-cooldown-timer" id="jo-exit-cooldown-timer">${formatTime(remainingSeconds)}</div>
            </div>
        `;

        // Insert the message into the home screen
        $('#jo-exit-home-screen').html(message);

        // console.log('Cooldown message displayed');
    }

    /**
     * Start the cooldown timer
     * @param {number} remainingTime - Remaining time in milliseconds
     */
    function startCooldownTimer(remainingTime) {
        let remainingSeconds = Math.ceil(remainingTime / 1000);
        // console.log('Starting cooldown timer with remaining time:', remainingSeconds, 'seconds');

        const timerElement = $('#jo-exit-cooldown-timer');

        if (!timerElement.length) {
            console.error('Timer element not found!');
            return;
        }

        // Clear any existing interval
        if (window.cooldownInterval) {
            clearInterval(window.cooldownInterval);
            // console.log('Cleared existing cooldown interval');
        }

        // Update the timer every second
        window.cooldownInterval = setInterval(function () {
            remainingSeconds--;
            // console.log('Cooldown timer:', remainingSeconds, 'seconds remaining');

            if (remainingSeconds <= 0) {
                // Time's up, clear the interval and reload the home screen
                clearInterval(window.cooldownInterval);
                // console.log('Cooldown timer finished, reloading home screen');

                // Reset session votes counter on the server
                $.ajax({
                    url: jo_exit_public.ajax_url,
                    type: 'POST',
                    data: {
                        'action': 'jo_exit_check_cooldown',
                        'nonce': jo_exit_public.nonce
                    },
                    success: function (response) {
                        // console.log('Session votes reset after cooldown:', response);
                        loadHomeScreen();
                    },
                    error: function () {
                        console.error('Error resetting session votes after cooldown');
                        loadHomeScreen();
                    }
                });
                return;
            }

            // Update the timer display
            timerElement.text(formatTime(remainingSeconds));
        }, 1000);

        // console.log('Cooldown timer started');
    }

    /**
     * Format time in seconds to HH:MM:SS format
     * @param {number} seconds - Time in seconds
     * @returns {string} Formatted time string
     */
    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;

        if (hours > 0) {
            // Format with hours (HH:MM:SS)
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        } else {
            // Format without hours (MM:SS)
            return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
    }

    // User authentication functions
    function loginUser(username, password) {
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_login_user',
                nonce: jo_exit_public.nonce,
                username: username,
                password: password
            },
            success: function (response) {
                if (response.success) {
                    // Show success message
                    $('#jo-exit-login-message').removeClass('error').addClass('success').text(response.data.message).show();

                    // Redirect to account page after a short delay
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    $('#jo-exit-login-message').removeClass('success').addClass('error').text(response.data.message).show();
                }
            },
            error: function () {
                $('#jo-exit-login-message').removeClass('success').addClass('error').text('Si è verificato un errore durante l\'accesso. Riprova più tardi.').show();
            }
        });
    }

    function registerUser(username, email, password, avatar) {
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_register_user',
                nonce: jo_exit_public.nonce,
                username: username,
                email: email,
                password: password,
                avatar: avatar
            },
            success: function (response) {
                if (response.success) {
                    // Show success message
                    $('#jo-exit-register-message').removeClass('error').addClass('success').text(response.data.message).show();

                    // Redirect to login page after a short delay
                    setTimeout(function () {
                        window.location.href = jo_exit_public.home_url + '?page=jo-exit&screen=account';
                    }, 2000);
                } else {
                    // Show error message
                    $('#jo-exit-register-message').removeClass('success').addClass('error').text(response.data.message).show();
                }
            },
            error: function () {
                $('#jo-exit-register-message').removeClass('success').addClass('error').text('Si è verificato un errore durante la registrazione. Riprova più tardi.').show();
            }
        });
    }

    function logoutUser() {
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_logout_user',
                nonce: jo_exit_public.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Redirect to home page
                    window.location.href = jo_exit_public.home_url + '?page=jo-exit';
                }
            }
        });
    }

    function updateUserProfile(email, password, avatar) {
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_update_profile',
                nonce: jo_exit_public.nonce,
                email: email,
                password: password,
                avatar: avatar
            },
            success: function (response) {
                if (response.success) {
                    // Show success message
                    $('#jo-exit-edit-profile-message').removeClass('error').addClass('success').text(response.data.message).show();

                    // Redirect to account page after a short delay
                    setTimeout(function () {
                        window.location.href = jo_exit_public.home_url + '?page=jo-exit&screen=account';
                    }, 2000);
                } else {
                    // Show error message
                    $('#jo-exit-edit-profile-message').removeClass('success').addClass('error').text(response.data.message).show();
                }
            },
            error: function () {
                $('#jo-exit-edit-profile-message').removeClass('success').addClass('error').text('Si è verificato un errore durante l\'aggiornamento del profilo. Riprova più tardi.').show();
            }
        });
    }

    function loadUserVotingData() {
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_get_user_voting_data',
                nonce: jo_exit_public.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Non aggiorniamo più i total points perché abbiamo rimosso quella sezione

                    // Update voted employees list
                    const votedEmployees = response.data.voted_employees;
                    const votedEmployeesContainer = $('#jo-exit-voted-employees');

                    if (votedEmployees.length > 0) {
                        let html = '';
                        // I dipendenti sono già filtrati dal server, non serve filtrarli qui
                        // Mostriamo direttamente tutti i dipendenti che arrivano dal server

                        votedEmployees.forEach(function (employee) {
                            // Log per debug
                            // console.log('Employee:', employee.name, 'has', employee.points, 'exit points (from server)');

                            // Costruisci il testo dei punti
                            // Usa i punti esattamente come arrivano dal server
                            const pointsText = `${employee.points} Exit Point${employee.points !== 1 ? 's' : ''}`;

                            // console.log('Showing employee:', employee.name, 'vote_type:', employee.vote_type, 'points:', employee.points);

                            html += `
                                <div class="jo-exit-voted-employee">
                                    <div class="jo-exit-voted-employee-photo">
                                        <img src="${employee.photo_url}" alt="${employee.name}">
                                    </div>
                                    <div class="jo-exit-voted-employee-info">
                                        <div class="jo-exit-voted-employee-name">${employee.name}</div>
                                        <div class="jo-exit-voted-employee-points">${pointsText}</div>
                                    </div>
                                    <div class="jo-exit-voted-employee-actions">
                                        <button class="jo-exit-delete-vote" data-employee-id="${employee.id}" title="Cancella voto">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        // Mostriamo i dipendenti e aggiungiamo gli event listener
                        votedEmployeesContainer.html(html);

                        // Aggiungi event listener per i pulsanti di cancellazione
                        $('.jo-exit-delete-vote').on('click', function () {
                            const employeeId = $(this).data('employee-id');
                            if (confirm(jo_exit_public.confirm_delete_vote)) {
                                deleteVote(employeeId);
                            }
                        });
                    } else {
                        // Mostra il messaggio di nessun voto
                        votedEmployeesContainer.html(`<p>${jo_exit_public.no_voted_employees}</p>`);
                        // console.log('No voted employees found, showing message:', jo_exit_public.no_voted_employees);
                    }

                    // La schermata account è stata caricata con successo
                    // console.log('Account screen loaded successfully');

                    // La pagina è stata caricata con successo
                    // console.log('Page loaded successfully with correct points');
                } else {
                    $('#jo-exit-voted-employees').html('<p>Errore nel caricamento dei dati.</p>');
                }
            },
            error: function () {
                $('#jo-exit-voted-employees').html('<p>Errore nel caricamento dei dati.</p>');
            }
        });
    }

    function uploadAvatar() {
        // Create a file input element
        const fileInput = $('<input type="file" accept="image/*" style="display:none">');
        $('body').append(fileInput);

        // Trigger click on the file input
        fileInput.trigger('click');

        // Handle file selection
        fileInput.on('change', function () {
            const file = this.files[0];
            if (!file) {
                fileInput.remove();
                return;
            }

            // Check file size (max 1MB for base64 encoding)
            const maxSize = 1 * 1024 * 1024; // 1MB in bytes
            if (file.size > maxSize) {
                alert('Il file è troppo grande. La dimensione massima consentita è 1MB.');
                fileInput.remove();
                return;
            }

            // Check file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Tipo di file non supportato. Sono consentiti solo JPG, PNG e GIF.');
                fileInput.remove();
                return;
            }

            // Show loading message
            const loadingMessage = $('<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>Caricamento avatar in corso...</div>');
            $('#jo-exit-avatar-preview').after(loadingMessage);

            // Use FileReader to convert the image to base64
            const reader = new FileReader();

            reader.onload = function (e) {
                // Get the base64 data
                const base64Data = e.target.result;

                // Use the base64 data directly
                $('#jo-exit-avatar-preview').attr('src', base64Data).show();
                $('#jo-exit-reg-avatar, #jo-exit-edit-avatar').val(base64Data);

                // Remove loading message
                loadingMessage.remove();

                // Remove file input
                fileInput.remove();
            };

            reader.onerror = function () {
                // Show error message
                loadingMessage.remove();
                const errorElement = $('<div class="jo-exit-form-message error">Errore durante la lettura del file. Riprova con un altro file o usa un URL.</div>');
                $('#jo-exit-avatar-preview').after(errorElement);

                // Remove error message after 5 seconds
                setTimeout(function () {
                    errorElement.fadeOut(function () {
                        $(this).remove();
                    });
                }, 5000);

                // Remove file input
                fileInput.remove();
            };

            // Read the file as a data URL (base64)
            reader.readAsDataURL(file);
        });
    }

    function generateRandomAvatar() {
        // Generate a random seed
        const seed = Math.random().toString(36).substring(2, 15);

        // Generate avatar URL using DiceBear API
        const avatarUrl = `https://api.dicebear.com/7.x/micah/svg?seed=${seed}&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf`;

        // Update avatar preview
        $('#jo-exit-avatar-preview').attr('src', avatarUrl).show();

        // Update hidden input value
        $('#jo-exit-reg-avatar, #jo-exit-edit-avatar').val(avatarUrl);
    }

    /**
     * Load the account screen
     */
    function loadAccountScreen() {
        // Show loading indicator
        $('#jo-exit-account-screen').html(`<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.loading}</div>`);

        // Utilizziamo l'endpoint AJAX dedicato
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_load_account',
                nonce: jo_exit_public.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Inseriamo il contenuto nella schermata account
                    $('#jo-exit-account-screen').html(response.data.content);

                    // Initialize account screen events
                    initAccountEvents();

                    // Load user voting data if user is logged in
                    if (jo_exit_public.is_user_logged_in) {
                        loadUserVotingData();
                    }

                    if (jo_exit_public.debug) {
                        // console.log('Account screen loaded successfully');
                    }
                } else {
                    console.error('Error loading account screen:', response);
                    $('#jo-exit-account-screen').html(`<div class="jo-exit-error">${jo_exit_public.error_loading_account}</div>`);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error loading account screen:', status, error);
                $('#jo-exit-account-screen').html(`<div class="jo-exit-error">${jo_exit_public.error_loading_account}</div>`);
            }
        });
    }

    /**
     * Initialize account screen events
     */
    function initAccountEvents() {
        // Handle login form submission
        $('#jo-exit-login-form').off('submit').on('submit', function (e) {
            e.preventDefault();
            const username = $('#jo-exit-username').val();
            const password = $('#jo-exit-password').val();
            loginUser(username, password);
        });

        // Handle register link
        $('#jo-exit-register-link').off('click').on('click', function (e) {
            e.preventDefault();
            navigateTo('register');
        });

        // Handle edit profile button
        $('#jo-exit-edit-profile').off('click').on('click', function () {
            navigateTo('edit-profile');
        });

        // Handle logout button
        $('#jo-exit-logout').off('click').on('click', function () {
            logoutUser();
        });

        // Dark mode toggle is now in the edit profile screen

        if (jo_exit_public.debug) {
            // console.log('Account screen events initialized');
        }
    }

    /**
     * Toggle dark mode
     *
     * @param {string|boolean} enable - Whether to enable dark mode ('on'/'off' or true/false)
     */
    function toggleDarkMode(enable) {
        // Convert string 'on'/'off' to boolean
        const isDarkMode = enable === true || enable === 'on';

        // Update UI
        if (isDarkMode) {
            $('body').addClass('jo-exit-dark-mode');
        } else {
            $('body').removeClass('jo-exit-dark-mode');
        }

        // Save preference to user meta
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                'action': 'jo_exit_toggle_dark_mode',
                'nonce': jo_exit_public.nonce,
                'dark_mode': isDarkMode ? 'on' : 'off'
            },
            success: function (response) {
                if (jo_exit_public.debug) {
                    // console.log('Dark mode preference saved:', response);
                }

                // Update the dark mode setting in the public object
                jo_exit_public.dark_mode = isDarkMode ? 'on' : 'off';
            },
            error: function (xhr, status, error) {
                console.error('Error saving dark mode preference:', status, error);
            }
        });
    }

    /**
     * Load the register screen
     */
    function loadRegisterScreen() {
        // Show loading indicator
        $('#jo-exit-account-screen').html(`<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.loading}</div>`);

        // Utilizziamo l'endpoint AJAX dedicato
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_load_register',
                nonce: jo_exit_public.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Inseriamo il contenuto nella schermata account
                    $('#jo-exit-account-screen').html(response.data.content);

                    // Initialize register screen events
                    initRegisterEvents();

                    if (jo_exit_public.debug) {
                        // console.log('Register screen loaded successfully');
                    }
                } else {
                    console.error('Error loading register screen:', response);
                    $('#jo-exit-account-screen').html(`<div class="jo-exit-error">${jo_exit_public.error_loading_register}</div>`);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error loading register screen:', status, error);
                $('#jo-exit-account-screen').html(`<div class="jo-exit-error">${jo_exit_public.error_loading_register}</div>`);
            }
        });
    }

    /**
     * Initialize register screen events
     */
    function initRegisterEvents() {
        // Handle register form submission
        $('#jo-exit-register-button').off('click').on('click', function () {
            const username = $('#jo-exit-reg-username').val();
            const email = $('#jo-exit-reg-email').val();
            const password = $('#jo-exit-reg-password').val();
            const avatar = $('#jo-exit-reg-avatar').val();

            if (!username || !email || !password) {
                $('#jo-exit-register-message').removeClass('success').addClass('error').text(jo_exit_public.all_fields_required).show();
                return;
            }

            if (password.length < 8) {
                $('#jo-exit-register-message').removeClass('success').addClass('error').text('La password deve essere di almeno 8 caratteri.').show();
                return;
            }

            // Call the register function
            registerUser(username, email, password, avatar);
        });

        // Handle back to login link
        $('.jo-exit-login-link a').off('click').on('click', function (e) {
            e.preventDefault();
            navigateTo('account');
        });
    }

    /**
     * Load the edit profile screen
     */
    function loadEditProfileScreen() {
        // Show loading indicator
        $('#jo-exit-account-screen').html(`<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.loading}</div>`);

        // Utilizziamo l'endpoint AJAX dedicato
        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_load_edit_profile',
                nonce: jo_exit_public.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Inseriamo il contenuto nella schermata account
                    $('#jo-exit-account-screen').html(response.data.content);

                    // Initialize edit profile screen events
                    initEditProfileEvents();

                    if (jo_exit_public.debug) {
                        // console.log('Edit profile screen loaded successfully');
                    }
                } else {
                    console.error('Error loading edit profile screen:', response);
                    $('#jo-exit-account-screen').html(`<div class="jo-exit-error">${jo_exit_public.error_loading_profile}</div>`);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error loading edit profile screen:', status, error);
                $('#jo-exit-account-screen').html(`<div class="jo-exit-error">${jo_exit_public.error_loading_profile}</div>`);
            }
        });
    }

    /**
     * Delete user account
     */
    function deleteUserAccount() {
        if (!confirm(jo_exit_public.confirm_delete_account)) {
            return;
        }

        // Show loading message
        $('#jo-exit-edit-profile-message').removeClass('success error').text(jo_exit_public.deleting_account).show();

        $.ajax({
            url: jo_exit_public.ajax_url,
            type: 'POST',
            data: {
                action: 'jo_exit_delete_account',
                nonce: jo_exit_public.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Show success message
                    $('#jo-exit-edit-profile-message').removeClass('error').addClass('success').text(response.data.message).show();

                    // Redirect to home page after a short delay
                    setTimeout(function () {
                        window.location.href = jo_exit_public.home_url + '?page=jo-exit';
                    }, 2000);
                } else {
                    // Show error message
                    $('#jo-exit-edit-profile-message').removeClass('success').addClass('error').text(response.data.message).show();
                }
            },
            error: function () {
                $('#jo-exit-edit-profile-message').removeClass('success').addClass('error').text(jo_exit_public.error_deleting_account).show();
            }
        });
    }

    /**
     * Initialize edit profile screen events
     */
    function initEditProfileEvents() {
        // Handle edit profile form submission
        $('#jo-exit-update-profile-button').off('click').on('click', function () {
            const email = $('#jo-exit-edit-email').val();
            const password = $('#jo-exit-edit-password').val();
            const avatar = $('#jo-exit-edit-avatar').val();

            if (!email) {
                $('#jo-exit-edit-profile-message').removeClass('success').addClass('error').text(jo_exit_public.email_required).show();
                return;
            }

            // Call the update profile function
            updateUserProfile(email, password, avatar);
        });

        // Handle back to account link
        $('.jo-exit-account-link a').off('click').on('click', function (e) {
            e.preventDefault();
            navigateTo('account');
        });

        // Handle delete account button
        $('#jo-exit-delete-account-button').off('click').on('click', function () {
            deleteUserAccount();
        });

        // Handle dark mode toggle
        $('#jo-exit-dark-mode-toggle').off('change').on('change', function () {
            const isDarkMode = $(this).is(':checked');
            toggleDarkMode(isDarkMode ? 'on' : 'off');
        });
    }

    /**
     * Use an external URL as avatar
     */
    function useAvatarUrl() {
        const url = $('#jo-exit-avatar-url').val().trim();

        if (!url) {
            alert(jo_exit_public.invalid_url);
            return;
        }

        // Simple URL validation
        if (!url.match(/^https?:\/\/.+\..+/)) {
            alert(jo_exit_public.invalid_url_format);
            return;
        }

        // Show loading message
        const loadingMessage = $(`<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.verifying_url}</div>`);
        $('#jo-exit-avatar-preview').after(loadingMessage);

        // Check if the URL is an image by loading it
        const img = new Image();

        img.onload = function () {
            // Image loaded successfully
            loadingMessage.remove();

            // Update avatar preview
            $('#jo-exit-avatar-preview').attr('src', url).show();

            // Update hidden input value
            $('#jo-exit-reg-avatar, #jo-exit-edit-avatar').val(url);

            // Clear URL input
            $('#jo-exit-avatar-url').val('');
        };

        img.onerror = function () {
            // Image failed to load
            loadingMessage.remove();

            // Show error message
            const errorElement = $(`<div class="jo-exit-form-message error">${jo_exit_public.invalid_image_url}</div>`);
            $('#jo-exit-avatar-preview').after(errorElement);

            // Remove error message after 5 seconds
            setTimeout(function () {
                errorElement.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        };

        // Set the source to start loading
        img.src = url;
    }

    /**
     * Load the global leaderboard screen
     */
    function loadGlobalLeaderboardScreen() {
        // console.log('loadGlobalLeaderboardScreen called');
        // console.log('JoExit object:', window.JoExit);
        // console.log('jo_exit_public object:', jo_exit_public);

        // Log the current URL for debugging
        // console.log('Current URL:', window.location.href);

        // Log the current page and screen parameters
        const urlParams = new URLSearchParams(window.location.search);
        // console.log('Page parameter:', urlParams.get('page'));
        // console.log('Screen parameter:', urlParams.get('screen'));

        // Check if the container exists
        const $container = $('#jo-exit-global-leaderboard-screen');
        if ($container.length === 0) {
            console.error('Container #jo-exit-global-leaderboard-screen not found');
            return;
        }

        // console.log('Container found:', $container);
        // console.log('Container HTML:', $container.html());

        // Show loading indicator
        $container.html(`<div class="jo-exit-loading"><div class="jo-exit-spinner"></div>${jo_exit_public.loading_global_leaderboard}</div>`);

        try {
            // Always use the WordPress AJAX handler for user leaderboard
            const ajaxUrl = jo_exit_public.ajax_url;
            const action = 'jo_exit_get_user_leaderboard';

            // Validate AJAX URL
            if (!ajaxUrl) {
                console.error('AJAX URL is missing');
                $container.html(`<div class="jo-exit-empty-message">${jo_exit_public.ajax_config_invalid}</div>`);
                return;
            }

            // console.log('AJAX URL:', ajaxUrl);
            // console.log('Action:', action);
            // console.log('Nonce:', jo_exit_public.nonce);

            // Set a timeout to handle cases where the AJAX request hangs
            const timeoutId = setTimeout(function () {
                console.error('AJAX request timed out');
                $container.html(`<div class="jo-exit-empty-message">${jo_exit_public.request_timeout}</div>`);
            }, 15000); // 15 seconds timeout

            // console.log('Making AJAX request to:', ajaxUrl);
            // console.log('Action:', action);
            // console.log('Nonce:', jo_exit_public.nonce);

            // Make the user leaderboard request
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    'action': action,
                    'nonce': jo_exit_public.nonce,
                    'limit': 20 // Limit to top 20 users
                },
                success: function (response) {
                    clearTimeout(timeoutId); // Clear the timeout
                    // console.log('Global leaderboard AJAX response:', response);

                    // Handle various response formats
                    if (response && response.success) {
                        // Standard format
                        if (response.data && response.data.users) {
                            // console.log('Users data:', response.data.users);
                            renderGlobalLeaderboard(response.data.users);
                        } else {
                            console.error('Response missing users data');
                            $container.html(`<div class="jo-exit-empty-message">${jo_exit_public.data_unavailable}</div>`);
                        }
                    } else if (response && response.users) {
                        // Alternative format
                        // console.log('Alternative format - Users data:', response.users);
                        renderGlobalLeaderboard(response.users);
                    } else {
                        // Error or unknown format
                        console.error('Error in response or unknown format:', response);
                        $container.html(`<div class="jo-exit-empty-message">${jo_exit_public.invalid_response_format}</div>`);
                    }
                },
                error: function (xhr, status, error) {
                    clearTimeout(timeoutId); // Clear the timeout
                    console.error('Global leaderboard AJAX error:', status, error);
                    console.error('Response text:', xhr.responseText);

                    // Try to parse the response if it's JSON
                    try {
                        if (xhr.responseText) {
                            const errorResponse = JSON.parse(xhr.responseText);
                            // console.log('Parsed error response:', errorResponse);

                            // Check if it's actually a success response in a different format
                            if (errorResponse && (errorResponse.users || (errorResponse.data && errorResponse.data.users))) {
                                const users = errorResponse.users || errorResponse.data.users;
                                // console.log('Found users data in error response:', users);
                                renderGlobalLeaderboard(users);
                                return;
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }

                    $container.html(`<div class="jo-exit-empty-message">${jo_exit_public.error_loading_scores}</div>`);
                }
            });
        } catch (e) {
            console.error('Exception in loadGlobalLeaderboardScreen:', e);
            $container.html(`<div class="jo-exit-empty-message">${jo_exit_public.unexpected_error}</div>`);
        }
    }

    /**
     * Render the player leaderboard
     *
     * @param {Array} employees - Array of exited employees with their top player scores
     */
    function renderPlayerLeaderboard(employees) {
        // console.log('renderPlayerLeaderboard called with employees:', employees);

        // Check if employees is valid
        if (!employees || !Array.isArray(employees) || employees.length === 0) {
            // console.log('No employees to display or invalid data');
            $('#jo-exit-leaderboard-trophy-screen').html(`<div class="jo-exit-empty-message">${jo_exit_public.no_exited_employees}</div>`);
            return;
        }

        let html = '<div class="jo-exit-leaderboard-trophy-container">';

        try {
            // Sort employees by exit date (newest first)
            employees.sort(function (a, b) {
                // Handle missing or invalid dates
                if (!a.exit_date) return 1;  // Move items without dates to the end
                if (!b.exit_date) return -1;

                return new Date(b.exit_date) - new Date(a.exit_date);
            });
            // console.log('Sorted employees:', employees);

            // Loop through each employee
            employees.forEach(function (employee) {
                // console.log('Processing employee:', employee);

                // Skip invalid employees
                if (!employee || !employee.id) {
                    // console.log('Invalid employee data, skipping');
                    return;
                }

                // Format exit date
                let formattedDate = 'Data sconosciuta';
                if (employee.exit_date) {
                    try {
                        const exitDate = new Date(employee.exit_date);
                        formattedDate = exitDate.toLocaleDateString('it-IT');
                    } catch (e) {
                        console.error('Error formatting date:', e);
                    }
                }
                // console.log('Exit date:', employee.exit_date, 'formatted as:', formattedDate);

                // Get employee name
                const firstName = employee.first_name || '';
                const lastName = employee.last_name || '';
                const employeeName = (firstName + ' ' + lastName).trim() || jo_exit_public.unknown_employee;

                html += '<div class="jo-exit-leaderboard-trophy-section">';
                html += '<div class="jo-exit-leaderboard-trophy-header">';
                html += '<div class="jo-exit-leaderboard-trophy-date">' + formattedDate + '</div>';
                html += '<div class="jo-exit-leaderboard-trophy-employee">' + jo_exit_public.exited + ' ' + employeeName + '</div>';
                html += '</div>';

                // Check if there are any player scores
                if (employee.top_scores && Array.isArray(employee.top_scores) && employee.top_scores.length > 0) {
                    // console.log('Employee has top scores:', employee.top_scores);
                    html += '<div class="jo-exit-leaderboard-trophy-podium">';

                    // Loop through top 3 scores (or less if not enough)
                    for (let i = 0; i < Math.min(employee.top_scores.length, 3); i++) {
                        const score = employee.top_scores[i];
                        // console.log('Processing score:', score);

                        // Skip invalid scores
                        if (!score) {
                            // console.log('Invalid score data, skipping');
                            continue;
                        }

                        const position = i + 1;
                        const medal = position === 1 ? 'gold' : (position === 2 ? 'silver' : 'bronze');
                        const displayName = score.display_name || score.user_login || 'Utente sconosciuto';
                        const avatarUrl = score.avatar_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                        const playerPoints = score.player_points !== undefined ? score.player_points : 0;

                        html += '<div class="jo-exit-leaderboard-trophy-position ' + medal + '">';
                        html += '<div class="jo-exit-leaderboard-trophy-rank">' + position + ' posizione</div>';
                        html += '<div class="jo-exit-leaderboard-trophy-player">';
                        html += '<div class="jo-exit-leaderboard-trophy-avatar"><img src="' + avatarUrl + '" alt="' + displayName + '"></div>';
                        html += '<div class="jo-exit-leaderboard-trophy-name">' + displayName + '</div>';
                        html += '</div>';
                        html += '<div class="jo-exit-leaderboard-trophy-points">' + playerPoints + ' Player Points</div>';
                        html += '</div>';
                    }

                    html += '</div>';
                } else {
                    // console.log('Employee has no top scores');
                    html += '<div class="jo-exit-leaderboard-trophy-empty">Nessun giocatore ha indovinato questo exit.</div>';
                }

                html += '</div>';
            });

            html += '</div>';

            // console.log('Final HTML:', html);
            $('#jo-exit-leaderboard-trophy-screen').html(html);
            // console.log('HTML inserted into DOM');
        } catch (e) {
            console.error('Error rendering player leaderboard:', e);
            $('#jo-exit-leaderboard-trophy-screen').html('<div class="jo-exit-empty-message">Si è verificato un errore durante il caricamento della leaderboard.</div>');
        }
    }

    /**
     * Render the global leaderboard
     *
     * @param {Array} users - Array of users with their experience points
     */
    function renderGlobalLeaderboard(users) {
        // console.log('renderGlobalLeaderboard called with users:', users);
        // console.log('Container exists:', $('#jo-exit-global-leaderboard-screen').length > 0);
        // console.log('Container HTML before rendering:', $('#jo-exit-global-leaderboard-screen').html());

        // Check if users is valid
        if (!users || !Array.isArray(users) || users.length === 0) {
            // console.log('No users to display or invalid data');
            $('#jo-exit-global-leaderboard-screen').html('<div class="jo-exit-empty-message">Nessun utente ha ancora accumulato punti esperienza.</div>');
            return;
        }

        let html = '<div class="jo-exit-leaderboard-container">';

        try {
            // Loop through each user
            users.forEach(function (user, index) {
                // console.log('Processing user:', user);

                // Skip invalid users
                if (!user) {
                    // console.log('Invalid user data, skipping');
                    return;
                }

                // console.log('User data:', user);

                // Ensure user_id exists
                if (!user.user_id) {
                    // console.log('User missing user_id, trying to use index as fallback');
                    // Try to use other properties as fallback
                    if (user.ID) {
                        user.user_id = user.ID;
                    } else {
                        // console.log('Cannot determine user_id, skipping');
                        return;
                    }
                }

                // Get user name
                const displayName = user.display_name || user.user_login || 'Utente sconosciuto';
                const position = index + 1;
                const expPoints = user.exp_points !== undefined ? user.exp_points : 0;
                const avatarUrl = user.avatar || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';

                // Add special class for top 3 positions
                let positionClass = '';
                if (position === 1) {
                    positionClass = 'gold';
                } else if (position === 2) {
                    positionClass = 'silver';
                } else if (position === 3) {
                    positionClass = 'bronze';
                }

                html += `
                    <div class="jo-exit-leaderboard-item ${positionClass}">
                        <div class="jo-exit-leaderboard-rank">${position}</div>
                        <div class="jo-exit-leaderboard-user">
                            <img src="${avatarUrl}" class="jo-exit-leaderboard-avatar" alt="${displayName}">
                            <div class="jo-exit-leaderboard-name">${displayName}</div>
                        </div>
                        <div class="jo-exit-leaderboard-exp">${expPoints} EXP</div>
                    </div>
                `;
            });

            html += '</div>';

            // console.log('Final HTML:', html);
            $('#jo-exit-global-leaderboard-screen').html(html);
            // console.log('HTML inserted into DOM');
        } catch (e) {
            console.error('Error rendering global leaderboard:', e);
            $('#jo-exit-global-leaderboard-screen').html('<div class="jo-exit-empty-message">Si è verificato un errore durante il caricamento della classifica globale.</div>');
        }
    }

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
    window.JoExit.toggleDarkMode = toggleDarkMode;

})(jQuery);
