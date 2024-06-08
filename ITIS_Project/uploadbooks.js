document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.form-container');
    const token = localStorage.getItem('token');
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        // Collect all form data
        const formData = new FormData(form);
        const bookData = {
            title: document.getElementById('title').value,
            isbn: document.getElementById('isbn').value,
            author: document.getElementById('author').value,
            year: document.getElementById('year').value,
            category: document.getElementById('category').value,
            file: document.getElementById('file').files[0],
        };

        // Append data to formData
        for (const key in bookData) {
            formData.append(key, bookData[key]);
        }

        // Requests to both PHP files
        const requests = [
            fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/bookdetails.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json' // This might need to be removed since you're sending FormData
                },
            }),
            fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/uploadbooks.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
            })
        ];

        // Handling both promises
        Promise.all(requests)
            .then(responses => Promise.all(responses.map(response => response.json())))
            .then(data => {
                // Check if both uploads were successful
                if (data.every(result => result.success)) {
                    alert('Book uploaded successfully!');
                    window.location.href = 'librarymanagment.html'; // Redirect or update the UI as needed
                } else {
                    alert('Failed to upload book on one or more services. Please check the details and try again.');
                }
            })
            .catch(error => {
                console.error('Error uploading book:', error);
                alert('Error uploading book. Please check the console for more details.');
            });
    });
});
