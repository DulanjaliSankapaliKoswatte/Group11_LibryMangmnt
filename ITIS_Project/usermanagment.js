document.addEventListener('DOMContentLoaded', function() {
    const userTable = document.getElementById('userTable').getElementsByTagName('tbody')[0];
    const loggedUser = localStorage.getItem('username2');
    let token = localStorage.getItem('token'); 

    function refreshAccessToken() {
        return fetch('http://localhost/ITIS%20Project/ITIS_Project_BE/refresh_token_endpoint', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refreshToken: localStorage.getItem('refreshToken') })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('token', data.newToken);
                token = data.newToken; // Update the local token variable
                return token;
            } else {
                window.location.href = 'login.html';
                throw new Error('Token refresh failed'); // Ensure the flow is interrupted by throwing an error
            }
        })
        .catch(() => {
            window.location.href = 'login.html';
            throw new Error('Token refresh failed');
        });
    }

    function fetchUsers() {
        if (!token) {
            window.location.href = 'login.html'; // Redirect to login page if no token
            return;
        }
        fetch('http://localhost/ITIS%20Project/ITIS_Project_BE/usermanagment.php', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(response => {
            if (!response.ok) {
                return refreshAccessToken().then(newToken => {
                    return fetchUsers(); // Retry the request with the new token
                });
            }
            return response.json();
        })
        .then(data => {
            userTable.innerHTML = ''; // Clear existing rows
            data.forEach(user => {
                const row = userTable.insertRow();
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td>${user.password}</td>
                    <td>${user.active === "0" ? 'Active' : 'Inactive'}</td>
                    <td>${user.userrole === 'admin' ? 'Admin' : 'User'}</td>
                    <td>
                        <div class="dropdown">
                            <button class="dropbtn">Actions</button>
                            <div class="dropdown-content">
                                ${user.userrole === 'user' && (user.active === "0" ? `<a href="#" onclick="toggleStatus(${user.id}, 1)">Make Inactive</a>` : `<a href="#" onclick="toggleStatus(${user.id}, 0)">Make Active</a>`)}
                                ${user.userrole === 'user' ? `<a href="#" onclick="toggleRole(${user.id}, 'admin')">Make Admin</a>` : ''}
                                ${user.userrole === 'admin' && loggedUser !== user.username ? `<a href="#" onclick="toggleRole(${user.id}, 'user')">Make User</a>` : ''}
                                ${user.userrole === 'user' ? `<a href="#" onclick="editUser(${user.id})">Edit</a>` : ''}
                                ${user.userrole === 'user' ? `<a href="#" onclick="deleteUser(${user.id})">Delete</a>` : ''}
                            </div>
                        </div>
                    </td>
                `;
            });
        })
        .catch(error => console.error('Error:', error));
    }

    // Consolidated the error handling and token refresh logic in modify operations
    function performUserModification(url, options, callback) {
        fetch(url, options)
        .then(response => {
            if (!response.ok) {
                return refreshAccessToken().then(newToken => {
                    return callback(); // Retry the request with the new token
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                fetchUsers(); // Refresh the table
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Server error: ' + error.message);
        });
    }

    window.editUser = function(id) {
        const newRole = prompt("Enter new role:");
        if (!newRole) return;

        performUserModification(`http://localhost/ITIS%20Project/ITIS_Project_BE/usermanagment.php?id=${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ userrole: newRole })
        }, () => editUser(id));
    };

    window.deleteUser = function(id) {
        performUserModification(`http://localhost/ITIS%20Project/ITIS_Project_BE/usermanagment.php?id=${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        }, () => deleteUser(id));
    };

    window.toggleStatus = function(id, status) {
        performUserModification(`http://localhost/ITIS%20Project/ITIS_Project_BE/usermanagment.php?id=${id}&toggle=status&status=${status}`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}` }
        }, () => toggleStatus(id, status));
    };

    window.toggleRole = function(id, role) {
        performUserModification(`http://localhost/ITIS%20Project/ITIS_Project_BE/usermanagment.php?id=${id}&toggle=role&role=${role}`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}` }
        }, () => toggleRole(id, role));
    };

    fetchUsers(); // Initial fetch
});
