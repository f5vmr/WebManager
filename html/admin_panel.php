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
            <button type="submit">Generate Password</button>
        </form>
    </div>

    <!-- Password Step -->
    <div id="passwordStep" class="user-form" style="display:none;">
        <h3>Password Generated</h3>
        <p>Callsign: <span id="pwCallsign"></span></p>
        <p>Generated Password: <span id="generatedPassword"></span></p>
        <button id="proceedBtn">Proceed</button>
        <button id="regenerateBtn">Generate New Password</button>
        <button id="cancelBtn">Cancel</button>
    </div>
    <!-- Preview Step -->
    <div id="previewStep" class="user-form" style="display:none;">
        <h3>Preview Lines</h3>
        <pre id="previewText"></pre>
        <button id="confirmAddBtn">Confirm & Add</button>
        <button id="cancelPreviewBtn">Cancel</button>
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
    const passwordStep = document.querySelector('#passwordStep');
    const pwCallsign = document.querySelector('#pwCallsign');
    const generatedPassword = document.querySelector('#generatedPassword');
    let currentCallsign = '';
    let currentPassword = '';

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

    // --- Step 1: Generate Password ---
    document.querySelector('#addUserForm').onsubmit = async e => {
        e.preventDefault();
        const callsign = e.target.callsign.value.toUpperCase();
        if (!callsign.match(/^[A-Z0-9]+$/)) {
            alert('Callsign must be alphanumeric and uppercase.');
            return;
        }

        const resp = await fetch('generate_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ callsign })
        });
        const data = await resp.json();
        if (!data.success) { alert('Failed to generate password'); return; }

        currentCallsign = callsign;
        currentPassword = data.password;

        pwCallsign.textContent = currentCallsign;
        generatedPassword.textContent = currentPassword;
        passwordStep.style.display = 'block';
    };

    // --- Step 2: Password Step Buttons ---
    document.querySelector('#proceedBtn').onclick = () => {
        // Prepare preview
        const preview = `[USERS]\n${currentCallsign} = ${currentCallsign.toLowerCase()}\n\n[PASSWORDS]\n${currentCallsign.toLowerCase()} = ${currentPassword}`;
    document.querySelector('#previewText').textContent = preview;
        passwordStep.style.display = 'none';
    document.querySelector('#previewStep').style.display = 'block';
};

        const data = await resp.json();
        if (data.success) {
            passwordStep.style.display = 'none';
            document.querySelector('#addUserForm').reset();
            loadUsers();
            alert(`Callsign ${currentCallsign} added successfully.`);
        } else {
            alert('Failed to add callsign. It may already exist.');
        }
    };

    document.querySelector('#regenerateBtn').onclick = async () => {
        const resp = await fetch('generate_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ callsign: currentCallsign })
        });
        const data = await resp.json();
        if (data.success) {
            currentPassword = data.password;
            generatedPassword.textContent = currentPassword;
        }
    };

    document.querySelector('#cancelBtn').onclick = () => {
        passwordStep.style.display = 'none';
        document.querySelector('#addUserForm').reset();
    };

    // --- Commit & Refresh ---
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
