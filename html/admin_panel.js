document.addEventListener('DOMContentLoaded', () => {
    const usersTableBody = document.querySelector('#usersTable tbody');
    const previewTbody = document.querySelector('#previewTable tbody');

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

    // Add new user (generate password & preview)
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
            body: JSON.stringify({ action: 'generate', callsign })
        });

        const data = await resp.json();
        if (data.success) {
            previewTbody.innerHTML = ''; // clear old preview
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${callsign}</td>
                <td>${data.password}</td>
            `;
            previewTbody.appendChild(row);

            // Store new user temporarily
            window.newUser = { callsign, password: data.password };
        } else {
            alert(`Failed to generate password: ${data.message}`);
        }
    };

    // Commit new user from preview
    document.querySelector('#commitBtn').onclick = async () => {
        if (!window.newUser) {
            alert('No new user to commit.');
            return;
        }

        const resp = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                callsign: window.newUser.callsign,
                password: window.newUser.password
            })
        });

        const data = await resp.json();
        if (data.success) {
            alert(`Callsign ${window.newUser.callsign} committed successfully.`);
            window.newUser = null;
            previewTbody.innerHTML = '';
            loadUsers();
        } else {
            alert(`Failed to commit: ${data.message}`);
        }
    };

    // Cancel reloads table and clears preview
    document.querySelector('#cancelBtn').onclick = () => {
        previewTbody.innerHTML = '';
        window.newUser = null;
        loadUsers();
    };

    // Refresh reloads table
    document.querySelector('#refreshBtn').onclick = loadUsers;

    // Initial load
    loadUsers();
});
