document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form from submitting the default way
            console.log('Login form submitted'); // Debug log

            const formData = new FormData(this);
            const jsonData = {};

            const captchaResponse = grecaptcha.getResponse();
 
            if (captchaResponse.length===0){
                alert("Captcha not complete")
                return;
            }

            formData.forEach((value, key) => {
                jsonData[key] = value;
            });

            // Encrypt the password using base64 encoding
            
            const encryptedPassword = btoa(unescape(encodeURIComponent(jsonData.password)));
            jsonData.password = encryptedPassword;
            const encryptedUsername = btoa(unescape(encodeURIComponent(jsonData.username)));
            const username2 = jsonData.username;
            jsonData.username = encryptedUsername;
            jsonData['g-recaptcha-response']=captchaResponse

            console.log('Encrypted JSON data:', jsonData); // Debug log

            fetch('http://localhost/ITIS%20Project/ITIS_Project_BE/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text) });
                }
                return response.json();
            })
            .then(data => {
                console.log(data); // Handle the response from the server
                alert(data.message); // Display the response message
                if (data.success) {
                    localStorage.setItem('token', data.token); // Store the token
                    localStorage.setItem('username',data.encryptedUsername);
                    localStorage.setItem('username2',username2);
                    // Redirect based on user role
                    const userRole = data.userrole;
                    if (userRole === 'user') {
                        window.location.href = 'library.html'; // Redirect to user library page
                    } else if (userRole === 'admin') {
                        window.location.href = 'adminlibrary.html'; // Redirect to admin library page
                    } else {
                        // Handle other roles or unexpected scenarios
                        console.error('Unknown user role:', userRole);
                        window.location.href = 'login.html'; // Redirect to admin library page
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message); // Show the error message
            });
        });
    }
});
