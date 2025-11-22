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

    <!-- Step 0: Add New Callsign -->
    <div class="user-form" id="addUserFormStep">
        <h2>Add New Callsign</h2>
        <form id="addUserForm">
            <input type="text" name="callsign" placeholder="CALLSIGN" required>
            <button type="submit">Generate Password</button>
        </form>
    </div>

    <!-- Step 1: Password Generated -->
    <div class="user-form" id="passwordStep" style="display:none;">
        <h2>Password Generated</h2>
        <p>Callsign: <span id="displayCallsign"></span></p>
        <p>Generated Password: <span id="displayPassword"></span></p>
        <button id="proceedBtn">Proceed to Preview</button>
        <button id="regenerateBtn">Generate New Password</button>
        <button id="cancelPasswordBtn">Cancel</button>
    </div>

    <!-- Step 2: Preview -->
    <div class="user-form" id="previewStep" style="display:none;">
        <h2>Preview Lines to be Added</h2>
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

    <p id="serviceNotice" style="color:#f0a;">
        After committing, please manually restart svxreflector service.
    </p>
</div>

<script src="admin_panel.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const usersTableBody = document.querySelector('#usersTable tbody');

    let currentCallsign = '';
    let currentPassword = '';

    // --- Load Users Table ---
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

    // --- Step 0: Generate Password ---
    const addUserForm = document.querySelector('#addUserForm');
    const addUserFormStep = document.querySelector('#addUserFormStep');
    const passwordStep = document.querySelector('#passwordStep');

    addUserForm.onsubmit = async e => {
        e.preventDefault();
        currentCallsign = e.target.callsign.value.toUpperCase().trim();
        if (!currentCallsign.match(/^[A-Z0-9]+$/)) {
            alert('Callsign must be alphanumeric and uppercase.');
            return;
        }

        // Request generated password from server
        const resp = await fetch('generate_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ callsign: currentCallsign })
        });
        const data = await resp.json();
        if (!data.success) {
            alert('Failed to generate password.');
            return;
        }

        currentPassword = data.password;
        document.querySelector('#displayCallsign').textContent = currentCallsign;
        document.querySelector('#displayPassword').textContent = currentPassword;

        addUserFormStep.style.display = 'none';
        passwordStep.style.display = 'block';
    };

    document.querySelector('#regenerateBtn').onclick = async () => {
        const resp = await fetch('generate_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ callsign: currentCallsign })
        });
        const data = await resp.json();
        if (!data.success) { alert('Failed to generate password.'); return; }
        currentPassword = data.password;
        document.querySelector('#displayPassword').textContent = currentPassword;
    };

    document.querySelector('#cancelPasswordBtn').onclick = () => {
        passwordStep.style.display = 'none';
        addUserFormStep.style.display = 'block';
        addUserForm.reset();
    };

    // --- Step 1: Preview ---
    document.querySelector('#proceedBtn').onclick = () => {
        const preview = `[USERS]\n${currentCallsign} = ${currentCallsign.toLowerCase()}\n\n[PASSWORDS]\n${currentCallsign.toLowerCase()} = ${currentPassword}`;
        document.querySelector('#previewText').textContent = preview;
        passwordStep.style.display = 'none';
        document.querySelector('#previewStep').style.display = 'block';
    };

    document.querySelector('#cancelPreviewBtn').onclick = () => {
        document.querySelector('#previewStep').style.display = 'none';
        passwordStep.style.display = 'block';
    };

    document.querySelector('#confirmAddBtn').onclick = async () => {
        const resp = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_generated',
                callsign: currentCallsign,
                password: currentPassword
            })
        });
        const data = await resp.json();
        if (data.success) {
            document.querySelector('#previewStep').style.display = 'none';
            addUserForm.reset();
            addUserFormStep.style.display = 'block';
            loadUsers();
            alert(`Callsign ${currentCallsign} added successfully.`);
        } else {
            alert('Failed to add callsign. It may already exist.');
        }
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
