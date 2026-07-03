// users.js – CRUD operations for user management page

const APP_URL = document.querySelector('meta[name="app-url"]')?.content || '/uaskte';
let deleteTargetId = null;

function openModal(mode, userId = null) {
    const modal = document.getElementById('user-modal');
    const title = document.getElementById('modal-title');
    const form  = document.getElementById('user-form');
    form.reset();

    if (mode === 'create') {
        title.textContent = 'Tambah User Baru';
        document.getElementById('form-action').value = 'create';
        document.getElementById('form-user-id').value = '';
        modal.style.display = 'flex';
    } else if (mode === 'edit' && userId) {
        title.textContent = 'Edit User';
        document.getElementById('form-action').value = 'update';

        // AJAX fetch user data
        fetch('users.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get&id=${userId}`
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const d = res.data;
                document.getElementById('form-user-id').value = d.id;
                document.getElementById('form-nama').value  = d.nama;
                document.getElementById('form-email').value = d.email;
                document.getElementById('form-wa').value    = d.no_wa;
                document.getElementById('form-role').value  = d.role;
                document.getElementById('form-status').value = d.is_active;
                modal.style.display = 'flex';
            } else {
                showToast(res.message, 'error');
            }
        });
    }
}

function closeModal() {
    document.getElementById('user-modal').style.display = 'none';
}

function saveUser() {
    const form = document.getElementById('user-form');
    const btn  = document.getElementById('btn-save-user');
    const fd   = new FormData(form);

    btn.disabled = true;
    btn.querySelector('span').textContent = 'Menyimpan...';

    fetch('users.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams([...fd])
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.querySelector('span').textContent = 'Simpan';
        if (res.success) {
            showToast(res.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.querySelector('span').textContent = 'Simpan';
        showToast('Terjadi kesalahan jaringan', 'error');
    });
}

function confirmDelete(userId, userName) {
    deleteTargetId = userId;
    document.getElementById('delete-user-name').textContent = userName;
    document.getElementById('delete-modal').style.display = 'flex';
}

function deleteUser() {
    if (!deleteTargetId) return;
    const btn = document.getElementById('btn-confirm-delete');
    btn.disabled = true;

    fetch('users.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&id=${deleteTargetId}`
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        document.getElementById('delete-modal').style.display = 'none';
        if (res.success) {
            showToast(res.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(res.message, 'error');
        }
    });
}

function showAudit(userId, userName) {
    document.getElementById('audit-title').textContent = `Riwayat – ${userName}`;
    document.getElementById('audit-body').innerHTML = '<div class="loading-spinner"></div>';
    document.getElementById('audit-modal').style.display = 'flex';

    fetch('users.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=audit&id=${userId}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data.length > 0) {
            let html = '<div class="table-responsive"><table class="data-table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Field</th><th>Lama</th><th>Baru</th><th>Oleh</th></tr></thead><tbody>';
            res.data.forEach(log => {
                const dt = new Date(log.changed_at).toLocaleString('id-ID');
                html += `<tr>
                    <td><small class="text-mono">${dt}</small></td>
                    <td><span class="badge badge-${log.action.toLowerCase()}">${log.action}</span></td>
                    <td><code>${log.field_changed || '-'}</code></td>
                    <td><span class="text-old">${log.old_value || '-'}</span></td>
                    <td><span class="text-new">${log.new_value || '-'}</span></td>
                    <td>${log.changed_by_name || 'System'}</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            document.getElementById('audit-body').innerHTML = html;
        } else {
            document.getElementById('audit-body').innerHTML = '<div class="empty-state"><p>Belum ada riwayat perubahan</p></div>';
        }
    });
}

// Search filter
document.getElementById('search-user')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#user-table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Close modals on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});

function showToast(msg, type = 'info') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast toast-${type} show`;
    setTimeout(() => t.className = 'toast', 3000);
}
