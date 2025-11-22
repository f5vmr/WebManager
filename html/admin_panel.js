document.addEventListener('DOMContentLoaded', function() {
    const usersTable = document.getElementById('users-table');
    const addUserForm = document.getElementById('add-user-form');

    // Fetch users from server
    function fetchUsers() {
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'fetch'})
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                renderTable(data.users);
            }
        });
    }

    // Render the table
    function renderTable(users) {
        usersTable.innerHTML = '';
        users.forEach(user => {
            const row = document.createElement('tr');
            row.dataset.callsign = user.callsign;

            row.innerHTML = `
                <td>${user.callsign}</td>
                <td class="pass-key">${user.password}</td>
                <td>${user.status}</td>
                <td class="action-buttons">
                    <button class="toggle-button">${user.status === 'ACTIVE' ? 'Deactivate' : 'Activate'}</button>
                </td>
            `;
            usersTable.appendChild(row);

            row.querySelector('.toggle-button').addEventListener('click', () => toggleUser(user.callsign));
        });
    }

    // Toggle ACTIVE/INACTIVE
    function toggleUser(callsign) {
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'toggle', callsign: callsign})
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) fetchUsers();
            else alert('Error: ' + data.error);
        });
    }

    // Add new user
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const callsignInput = document.getElementById('new-callsign');
            const callsign = callsignInput.value.trim().toUpperCase();
            if (!callsign) return alert('Enter a callsign');

            fetch('admin_actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'add', callsign: callsign})
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    alert(`User ${data.callsign} added with password: ${data.password}`);
                    callsignInput.value = '';
                    fetchUsers();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        });
    }

    // Initial fetch
    fetchUsers();
});
