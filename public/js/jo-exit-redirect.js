/**
 * Handle redirects for JOb Exit Plugin
 */
jQuery(document).ready(function($) {
    // Get the current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const screen = urlParams.get('screen');

    // If we're on the exited screen, redirect to the main app with a special parameter
    if (screen === 'exited') {
        window.location.href = window.location.origin + window.location.pathname + '?page=jo-exit&direct_screen=exited';
    }
});
