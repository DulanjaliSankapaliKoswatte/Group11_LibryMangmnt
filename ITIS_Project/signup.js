document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signup-form');
    const passwordInput = signupForm.querySelector('input[name="password"]');
    const passwordStrength = document.getElementById('password-strength');

    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) {
            strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[\W_]/)) strength += 1;
        }

        return strength;
    }

    passwordInput.addEventListener('input', function() {
        const strength = checkPasswordStrength(passwordInput.value);
        let strengthMessage;
        switch(strength) {
            case 0:
            case 1:
                strengthMessage = 'Weak';
                passwordStrength.style.color = 'red';
                break;
            case 2:
            case 3:
                strengthMessage = 'Moderate';
                passwordStrength.style.color = 'orange';
                break;
            case 4:
            case 5:
                strengthMessage = 'Strong';
                passwordStrength.style.color = 'green';
                break;
        }
        passwordStrength.textContent = `Password Strength: ${strengthMessage}`;
    });

    if (signupForm) {
        signupForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form from submitting the default way

            const formData = new FormData(this);
            const jsonData = {};

            formData.forEach((value, key) => {
                jsonData[key] = value;
            });

            const passwordStrength = checkPasswordStrength(jsonData.password);
            if (passwordStrength < 4) {
                alert('Password is too weak. Please make it stronger.');
                return;
            }

            // Encrypt the password using PHP encryption code
            const encryptedPassword = btoa(unescape(encodeURIComponent(jsonData.password)));
            jsonData.password = encryptedPassword;

            const encryptedUsername = btoa(unescape(encodeURIComponent(jsonData.username)));
            jsonData.username = encryptedUsername;

            console.log('Encrypted JSON data:', jsonData); // Debug log

            fetch('http://localhost/ITIS%20Project/ITIS_Project_BE/signup.php', {
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
                console.log('Response data:', data); // Handle the response from the server
                alert(data.message); // Display the response message
                if (data.success) {
                    window.location.href = 'login.html'; // Redirect to login page
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message); // Show the error message
            });
        });
    }
});
