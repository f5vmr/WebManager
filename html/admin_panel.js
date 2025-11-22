document.addEventListener('DOMContentLoaded', () => {
    const usersTableBody = document.querySelector('#usersTable tbody');

    // Load users from admin_actions.php
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

    // Attach toggle activate/deactivate buttons
    function attachRowHandlers() {
        document.querySelectorAll('.toggle-button').forEach(btn => {
            btn.onclick = async () => {
                const row = btn.closest('tr');
                const callsign = row.dataset.callsign;
                const action = btn.textContent === 'Deactivate' ? 'deactivate' : 'activate';
                await fetch('admin_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, callsign })
                });
                loadUsers();
            };
        });
    }

    // Handle Add New Callsign form
    document.querySelector('#addUserForm').onsubmit = async e => {
    e.preventDefault();
    const callsign = e.target.callsign.value.toUpperCase(); // force uppercase

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
        e.target.reset();
        alert(`Callsign ${data.callsign} added successfully.\nGenerated password: ${data.password}`);
        loadUsers(); // refresh table to show new callsign
    } else {
        alert(`Failed to add callsign ${callsign}. ${data.message || ''}`);
    }
};


    // Commit changes & backup config
    document.querySelector('#commitBtn').onclick = async () => {
        const resp = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'commit' })
        });
        const data = await resp.json();
        alert(data.success ? 'Config committed and backup created.' : 'Error committing changes.');
    };

    // Refresh table
    document.querySelector('#refreshBtn').onclick = loadUsers;

    // Initial load
    loadUsers();
});
