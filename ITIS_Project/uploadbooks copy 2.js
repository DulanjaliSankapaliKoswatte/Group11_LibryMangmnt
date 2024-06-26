document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.form-container');
    const token = localStorage.getItem('token');

    form.addEventListener('submit', function(event) {

        if (!token) {
            console.log("No Token!");
            window.location.href = 'login.html'; // Redirect to login page if no token
            return;
        }
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(form);
        formData.append('file', document.getElementById('file').files[0]); 
        const fileInput = document.getElementById('file');
        const file = fileInput.files[0];
        const filename = file.name;
        // First, upload book details to the database
        const bookDetailsData = new FormData();
        bookDetailsData.append('title', document.getElementById('title').value);
        bookDetailsData.append('isbn', document.getElementById('isbn').value);
        bookDetailsData.append('author', document.getElementById('author').value);
        bookDetailsData.append('year', document.getElementById('year').value);
        bookDetailsData.append('category', document.getElementById('category').value);
        bookDetailsData.append('filename', filename);
        
        
        fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/bookdetails.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: bookDetailsData
        })
        .then(response => {
            const clone = response.clone();  // Clone the response
            response.text().then(text => console.log(text));  // Log as text
            return clone.json();  // Parse the clone as JSON
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message); // If saving details fails, throw an error
            }

            // If book details are successfully saved, proceed to upload the file
            return fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/uploadbooks.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData // Send the form data for file upload to S3
            });
        })
        .then(response => {
            const clone = response.clone();  // Clone the response
            response.text().then(text => console.log(text));  // Log as text
            return clone.json();  // Parse the clone as JSON
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message); // If file upload fails, throw an error
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
