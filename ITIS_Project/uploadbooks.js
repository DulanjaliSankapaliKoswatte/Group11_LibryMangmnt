
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.form-container');
    const token = localStorage.getItem('token')
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        const fileInput = document.getElementById('file');
        const file = fileInput.files[0]; // Get the file from the file input

        if (!file) {
            alert('Please select a file to upload.');
            return;
        }

        // // Check if the selected file is a PDF
        // if (file.type !== 'application/pdf') {
        //     alert('Only PDF files are allowed.');
        //     return;
        // }

        const formData = new FormData();
        formData.append('file', file); // Append the file to the FormData object

        fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/uploadbooks.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData, // Send the form data to the server
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message); // Show success or error message from server
            window.location.href = 'librarymanagment.html';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error uploading file.');
        });
    });
});

