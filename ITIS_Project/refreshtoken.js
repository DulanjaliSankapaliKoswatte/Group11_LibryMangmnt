function refreshAccessToken() {
    // Get the expired access token from localStorage
    const expiredToken = localStorage.getItem('token');

    return fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/refreshtoken.php', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${expiredToken}`, // Send the expired token in the Authorization header
            'Content-Type': 'application/json' // This header can actually be removed since there is no body being sent
        }
    })
    .then(response => {
        // First check if the response is OK
        if (!response.ok) {
            throw new Error('Failed to refresh token');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.token) {
            // Update the token in localStorage
            localStorage.setItem('token', data.token);
            return data.token; // Return the new access token
        } else {
            // Handle the situation where token is not refreshed
            console.error('Token refresh failed:', data.message);
            return null;
        }
    })
    .catch(error => {
        console.error('Error refreshing access token:', error);
        return null; 
    });
}
