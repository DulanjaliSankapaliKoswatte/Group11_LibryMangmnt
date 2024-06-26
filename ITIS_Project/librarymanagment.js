document.addEventListener('DOMContentLoaded', function() {
    console.log("Library management JS loaded!");
    // Load PDFs when library page is loaded
    if (window.location.pathname.endsWith('librarymanagment.html') || window.location.pathname.endsWith('library.html')) {
        console.log("Library management script active on this page.");
        console.log("Library management JS loaded2!");
        let bookList = [];
       
        const fetchBooks = () => {
            const token = localStorage.getItem('token');
            if (!token) {
                console.log("Library management JS loaded6!");
                window.location.href = 'login.html'; // Redirect to login page if no token
                return;
            }
            fetch('https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/bookdetails.php', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log("Library management JS loaded3!");
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
                console.log("Library management JS loaded4!");
                if (data.success) {
                    console.log("Library management JS loaded5!");
                    bookList = data.data;
                    console.log("Library management JS loaded6!",bookList);
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
                console.log("Library management JS loaded55!");
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
                    <td>${book.id}</td>
                    <td>${book.ISBN}</td>
                    <td>${book.Author_name}</td>
                    <td>${book.Year_made}</td>
                    <td>${book.Category}</td>
                    <td>${book.book_title}</td>
                    <td><a href="#" class="download-link" data-file="${book.file_location}">Download</a></td>
                `;
                bookListElement.appendChild(row);
            });
        
            document.querySelectorAll('.download-link').forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    const fileLocation = this.getAttribute('data-file');
                    downloadFile(fileLocation);
                });
            });
        };
        

        const downloadFile = (fileName) => {
            const token = localStorage.getItem('token'); // Retrieve the token
            fetch(`https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/library.php?file=${fileName}`, {
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
                            return downloadFile(fileName); // Retry the download with the new token
                        } else {
                            window.location.href = 'login.html'; // Redirect to login page if token refresh fails
                            return Promise.reject(new Error('Failed to refresh token and cannot retry download'));
                        }
                    });
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
                }else if (data.message === 'Token has expired') {
                   // alert(data.message); // Display error message from server
                   return refreshAccessToken().then(newToken => {
                    if (newToken) {
                        localStorage.setItem('token', newToken); // Update the token in localStorage
                        return downloadFile(fileName); // Retry the download with the new token
                    } else {
                        window.location.href = 'login.html'; // Redirect to login page if token refresh fails
                        return Promise.reject(new Error('Failed to refresh token and cannot retry download'));
                    }
                });
                } else {
                    alert(data.message); // Display other error messages from server
                } 
                
                    
              
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to download file: ' + error.message);
            });
        };

        // if (window.location.pathname.endsWith('library.html')) {
        //    // const token = localStorage.getItem('token'); // Retrieve the token
        //     fetchBooks();
        // }
        // Call fetchBooks directly to ensure it's triggered
        const searchForm = document.getElementById('search-form');
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const searchTerm = document.getElementById('book-search').value.toLowerCase();
            const filteredBooks = bookList.filter(book => book.book_title.toLowerCase().includes(searchTerm));
            displayBooks(filteredBooks);
        });
        
        fetchBooks();
       
    }
});
