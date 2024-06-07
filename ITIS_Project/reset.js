document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reset-password-form');
    const passwordInput = document.getElementById('new-password');
    const confirmPasswordInput = document.getElementById('confirm-password');
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
            default:
                strengthMessage = '';
                passwordStrength.style.color = '';
        }
        passwordStrength.textContent = `Password Strength: ${strengthMessage}`;
    });

    confirmPasswordInput.addEventListener('input', function() {
        const newPassword = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (newPassword !== confirmPassword) {
            confirmPasswordInput.setCustomValidity("Passwords do not match");
        } else {
            confirmPasswordInput.setCustomValidity("");
        }
    });

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const resetCode = document.getElementById('reset-code').value;
        const newPassword = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (newPassword !== confirmPassword) {
            alert('Passwords do not match. Please try again.');
            return;
        }
        const encryptedPassword = btoa(unescape(encodeURIComponent(newPassword)));
        console.error('newPassword:', newPassword);
        console.error('encryptedPassword:', encryptedPassword);
        fetch('http://localhost/ITIS%20Project/ITIS_Project_BE/reset.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reset_code: resetCode, new_password: encryptedPassword })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Your password has been reset successfully.');
                window.location.href = 'login.html';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
});
