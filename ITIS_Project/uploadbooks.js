document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.form-container');
    const token = localStorage.getItem('token');

    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        if (!token) {
            console.log("No Token!");
            window.location.href = 'login.html'; // Redirect to login page if no token
            return;
        }

        const formData = new FormData(form);
        formData.append('file', document.getElementById('file').files[0]);

        // Attempt to upload the file first
        fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/uploadbooks.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData // Send the form data for file upload to S3
        })
        .then(response => {
            const clone = response.clone();  // Clone the response
            response.text().then(text => console.log(text));  // Log as text
            return clone.json();  // Parse the clone as JSON
        })
        .then(data => {
            if (!data.success) {
                // If file upload fails, attempt to refresh the token
                return refreshAccessToken().then(newToken => {
                    if (newToken) {
                        localStorage.setItem('token', newToken); // Update the token in localStorage
                        return fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/uploadbooks.php', {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${newToken}`
                            },
                            body: formData // Retry the file upload with the new token
                        });
                    } else {
                        throw new Error('Failed to refresh token');
                    }
                });
            }
            return data;  // If file upload succeeds, return the data to proceed
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message); // If retry also fails, throw an error
            }

            // If file upload is successful, upload book details
            const bookDetailsData = new FormData();
            bookDetailsData.append('title', document.getElementById('title').value);
            bookDetailsData.append('isbn', document.getElementById('isbn').value);
            bookDetailsData.append('author', document.getElementById('author').value);
            bookDetailsData.append('year', document.getElementById('year').value);
            bookDetailsData.append('category', document.getElementById('category').value);
            bookDetailsData.append('filename', document.getElementById('file').files[0].name);

            return fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/bookdetails.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: bookDetailsData
            });
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message); // If saving details fails, throw an error
            }
            alert('Book uploaded successfully!');
            window.location.href = 'librarymanagment.html'; // Redirect on success
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error uploading book details or file. ' + error.message);
        });
    });
});

function refreshAccessToken() {
    const expiredToken = localStorage.getItem('token');
    if (!expiredToken) {
        console.log("No Token!");
        window.location.href = 'login.html'; // Redirect to login page if no token
        return Promise.reject('No token available for refresh');
    }
    return fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/refreshtoken.php', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${expiredToken}`,
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to refresh token');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.token) {
            localStorage.setItem('token', data.token);
            return data.token; // Return the new access token
        } else {
            console.error('Token refresh failed:', data.message);
            return null;
        }
    })
    .catch(error => {
        console.error('Error refreshing access token:', error);
        return null;
    });
}
