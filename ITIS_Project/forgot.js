document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgot-password-form');

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const email = document.getElementById('email').value;
       
        fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/forgot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password reset instructions have been sent to your email.');
                window.location.href = 'reset.html';
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
