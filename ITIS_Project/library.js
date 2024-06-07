document.addEventListener('DOMContentLoaded', function() {
    // Load PDFs when library page is loaded
    if (window.location.pathname.endsWith('library.html')) {
        
        let bookList = [];

        const fetchBooks = (token) => {
            token = localStorage.getItem('token')
            if (!token) {
                window.location.href = 'login.html'; // Redirect to login page if no token
                return;
            }
            fetch('http://localhost/ITIS%20Project/ITIS_Project_BE/library.php', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })
            .then(response => {
                if (!response.ok) {
                    // return response.text().then(text => { throw new Error(text) });
                    return refreshAccessToken().then(newToken => {
                        if (newToken) {
                            localStorage.setItem('token', newToken); // Update the token in localStorage
                            return fetchBooks(); // Retry the request with the new token
                        } else {
                            // throw new Error('Failed to refresh token');
                            window.location.href = 'login.html'; // Redirect to login page if token refresh fails
                            return Promise.reject(new Error('Failed to refresh token'));
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    bookList = data.data;
                    displayBooks(bookList);
                } else if (data.message === 'Token has expired') {
                    // Token expired, attempt to refresh it
                    return refreshAccessToken().then(newToken => {
                        if (newToken) {
                            localStorage.setItem('token', newToken); // Update the token in localStorage
                            return fetchBooks(); // Retry the request with the new token
                        } else {
                            //throw new Error('Failed to refresh token');
                            window.location.href = 'login.html'; // Redirect to login page if token refresh fails
                            return Promise.reject(new Error('Failed to refresh token'));
                        }
                    });
                } else {
                    alert(data.message); // Display other error messages from server
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load books: ' + error.message);
            });
        };

        const displayBooks = (books) => {
            const bookListElement = document.getElementById('book-list');
            bookListElement.innerHTML = ''; // Clear existing content
            books.forEach((book, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${book.name}</td>
                    <td><a href="#" class="download-link" data-file="${book.name}">Download</a></td>
                `;
                bookListElement.appendChild(row);
            });

            document.querySelectorAll('.download-link').forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    const fileName = this.getAttribute('data-file');
                    downloadFile(fileName);
                });
            });
        };

        const downloadFile = (fileName) => {
            const token = localStorage.getItem('token'); // Retrieve the token
            fetch(`http://localhost/ITIS%20Project/ITIS_Project_BE/library.php?file=${fileName}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text) });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const link = document.createElement('a');
                    link.href = `data:application/octet-stream;base64,${data.data}`;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(data.message); // Display error message from server
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to download file: ' + error.message);
            });
        };

        if (window.location.pathname.endsWith('library.html')) {
            const token = localStorage.getItem('token'); // Retrieve the token
            fetchBooks(token);
        }

        const searchForm = document.getElementById('search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent form from submitting the default way
                const searchTerm = document.getElementById('book-search').value.toLowerCase();
                const filteredBooks = bookList.filter(book => book.name.toLowerCase().includes(searchTerm));
                displayBooks(filteredBooks);
            });
        }

        
    }

    
    
});
