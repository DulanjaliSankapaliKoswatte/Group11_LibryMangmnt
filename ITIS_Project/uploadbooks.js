document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.form-container');
    const token = localStorage.getItem('token');
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(form);

        // Append additional data to formData
        formData.append('title', document.getElementById('title').value);
        formData.append('isbn', document.getElementById('isbn').value);
        formData.append('author', document.getElementById('author').value);
        formData.append('year', document.getElementById('year').value);
        formData.append('category', document.getElementById('category').value);
        formData.append('file', document.getElementById('file').files[0]);

        // Define the requests
        const bookDetailsRequest = fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/bookdetails.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': `Bearer ${token}`
            },
        }).then(response => handleResponse(response, "Book Details API"));

        const uploadBooksRequest = fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/uploadbooks.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': `Bearer ${token}`
            },
        }).then(response => handleResponse(response, "Upload Books API"));

        Promise.all([bookDetailsRequest, uploadBooksRequest])
            .then(data => {
                if (data.every(result => result.success)) {
                    alert('Book uploaded successfully!');
                    window.location.href = 'librarymanagment.html';
                } else {
                    let errorMessage = data.map(res => res.message).join("\n");
                    alert('Failed to upload book on one or more services. Please check the details and try again.\n' + errorMessage);
                }
            })
            .catch(error => {
                console.error('Error uploading book:', error);
                alert('Error uploading book. Please check the console for more details.');
            });
    });

    function handleResponse(response, apiName) {
        if (!response.ok) {
            throw new Error(`${apiName} failed with status: ${response.status}`);
        }
        return response.json().then(data => {
            if (!data.success) {
                throw new Error(`${apiName} error: ${data.message}`);
            }
            return data;
        });
    }
});
