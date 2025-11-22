document.addEventListener('DOMContentLoaded', () => {
    const usersTableBody = document.querySelector('#usersTable tbody');

    // Load and display users
    async function loadUsers() {
        const resp = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'fetch' })
        });
        const data = await resp.json();
        if (!data.success) return;

        usersTableBody.innerHTML = '';
        data.users.forEach(user => {
            const row = document.createElement('tr');
            row.dataset.callsign = user.callsign;
            row.innerHTML = `
                <td>${user.callsign}</td>
                <td>${user.password}</td>
                <td>${user.active ? 'ACTIVE' : 'INACTIVE'}</td>
                <td>
                    <button class="toggle-button">${user.active ? 'Deactivate' : 'Activate'}</button>
                </td>
            `;
            usersTableBody.appendChild(row);
        });
        attachRowHandlers();
    }

    // Attach activate/deactivate buttons
    function attachRowHandlers() {
        document.querySelectorAll('.toggle-button').forEach(btn => {
            btn.onclick = async () => {
                const row = btn.closest('tr');
                const callsign = row.dataset.callsign;
                const action = btn.textContent === 'Deactivate' ? 'deactivate' : 'activate';

                const resp = await fetch('admin_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, callsign })
                });
                const data = await resp.json();
                if (data.success) loadUsers();
            };
        });
    }

    // Add new user
    document.querySelector('#addUserForm').onsubmit = async e => {
        e.preventDefault();
        const callsign = e.target.callsign.value.toUpperCase();
        if (!callsign.match(/^[A-Z0-9]+$/)) {
            alert('Callsign must be alphanumeric and uppercase.');
            return;
        }

        const resp = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', callsign })
        });
        const data = await resp.json();

        if (data.success) {
            // Show generated password inline above table temporarily
            const notice = document.getElementById('serviceNotice');
            notice.textContent = `Callsign ${callsign} added. Generated password: ${data.password}`;
            notice.style.color = '#0f0'; // green for success
            e.target.reset();
            loadUsers();
        } else {
            alert(data.message || 'Failed to add callsign.');
        }
    };

    // Commit changes
    document.querySelector('#commitBtn').onclick = async () => {
        const resp = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'commit' })
        });
        const data = await resp.json();
        alert(data.success ? 'Config committed and backup created.' : 'Error committing changes.');
    };

    // Cancel reloads table
    document.querySelector('#cancelBtn').onclick = loadUsers;

    // Refresh reloads table
    document.querySelector('#refreshBtn').onclick = loadUsers;

    // Initial load
    loadUsers();
});
