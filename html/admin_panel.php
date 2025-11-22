<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SVXReflector Admin Panel</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>SVXReflector Admin Panel</h1>
    <a href="logout.php" class="info-link">Logout</a>
</header>

<div class="admin-container">
    <!-- Add New Callsign -->
    <div class="user-form">
        <h2>Add New Callsign</h2>
        <form id="addUserForm">
            <input type="text" name="callsign" placeholder="CALLSIGN" required>
            <button type="submit">Add</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="user-form">
        <h2>Existing Users</h2>
        <table class="users-list" id="usersTable">
            <thead>
                <tr>
                    <th>Callsign</th>
                    <th>Password</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Filled dynamically -->
            </tbody>
        </table>
    </div>

    <div style="text-align:center; margin-top:20px;">
        <button id="commitBtn">Commit Changes & Backup Config</button>
        <button id="refreshBtn">Refresh Table</button>
    </div>

    <p id="serviceNotice" style="color:#f0a;">After committing, please manually restart svxreflector service.</p>
</div>

<script src="admin_panel.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const usersTableBody = document.querySelector('#usersTable tbody');

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

    document.querySelector('#addUserForm').onsubmit = async e => {
        e.preventDefault();
        const callsign = e.target.callsign.value.toUpperCase();
        await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', callsign })
        });
        e.target.reset();
        loadUsers();
    };

    document.querySelector('#commitBtn').onclick = async () => {
        const resp = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'commit' })
        });
        const data = await resp.json();
        alert(data.success ? 'Config committed and backup created.' : 'Error committing changes.');
    };

    document.querySelector('#refreshBtn').onclick = loadUsers;

    loadUsers();
});
</script>
</body>
</html>

