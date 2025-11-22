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
    const input = e.target.callsign;
    const callsign = input.value.toUpperCase().trim();
    input.value = callsign; // make sure the field reflects uppercase

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
        // show password immediately
        alert(`Callsign ${data.callsign} added.\nGenerated password: ${data.password}\n\nNote: Press COMMIT to save to config file.`);
        // refresh the table (calls fetch); even if in-memory not persisted to disk,
        // we call fetch to get latest persisted state after commit — but this will
        // show updates once you press Commit.
        await loadUsers();
        e.target.reset();
    } else {
        alert(`Failed to add callsign: ${data.message || 'Unknown error'}`);
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
