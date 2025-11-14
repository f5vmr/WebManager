document.addEventListener('DOMContentLoaded', function() {
    // Edit user handler
    function editUser(callsign) {
        const row = document.querySelector(`tr[data-callsign="${callsign}"]`);
        const passKeyCell = row.querySelector('.pass-key');
        const currentPassKey = passKeyCell.textContent;
        
        const select = document.createElement('select');
        select.innerHTML = `
            <option value="${currentPassKey}">${currentPassKey}</option>
            <option value="MyNodes">MyNodes</option>
            <option value="linkup1pass">linkup1pass</option>
            <option value="new">Generate New Key</option>
        `;
        
        passKeyCell.innerHTML = '';
        passKeyCell.appendChild(select);
        
        // Add save button
        const saveBtn = document.createElement('button');
        saveBtn.textContent = 'Save';
        saveBtn.onclick = () => saveUserChanges(callsign, select.value);
        passKeyCell.appendChild(saveBtn);
    }
    
    // Delete user handler
    function deleteUser(callsign) {
        if (confirm(`Are you sure you want to delete ${callsign}?`)) {
            fetch('manage_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    callsign: callsign
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`tr[data-callsign="${callsign}"]`).remove();
                }
            });
        }
    }
    
    // Save changes handler
    function saveUserChanges(callsign, newPassKey) {
        fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update',
                callsign: callsign,
                passKey: newPassKey
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
    
    // Attach handlers to buttons
    document.querySelectorAll('.edit-button').forEach(btn => {
        btn.onclick = () => editUser(btn.dataset.callsign);
    });
    
    document.querySelectorAll('.delete-button').forEach(btn => {
        btn.onclick = () => deleteUser(btn.dataset.callsign);
    });
});

function fetchNewCopy() {
    fetch('admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'fetch'
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) location.reload();
    });
}

function confirmPushLive() {
    if(confirm('Are you sure you want to push these changes to the live system?')) {
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'push_live'
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'Changes pushed successfully' : 'Error pushing changes');
        });
    }
}
