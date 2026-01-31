// session-helper.js - Global session management

// Check if user is logged in via session
async function isUserLoggedIn() {
    try {
        const response = await fetch('/trendify/session_check.php', { 
            credentials: 'include',
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        const data = await response.json();
        return data.logged_in === true;
    } catch (error) {
        console.error('Session check failed:', error);
        return false;
    }
}

// Get current user data
async function getCurrentUser() {
    try {
        const response = await fetch('/trendify/session_check.php', { 
            credentials: 'include',
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        const data = await response.json();
        if (data.logged_in && data.user) {
            return data.user;
        }
        return null;
    } catch (error) {
        console.error('Failed to get user:', error);
        return null;
    }
}

// Ensure user is logged in, redirect if not
async function requireLogin() {
    const loggedIn = await isUserLoggedIn();
    if (!loggedIn) {
        window.location.href = '/trendify/sign-in.html?next=' + encodeURIComponent(window.location.href);
        return false;
    }
    return true;
}
