document.addEventListener('DOMContentLoaded', function() {
    // Init modals
    const editModeModal = new bootstrap.Modal(document.getElementById('editModeModal'));
    const deleteModeModal = new bootstrap.Modal(document.getElementById('deleteModeModal'));
    let currentModeId = null;

    // Add 
    document.getElementById('saveNewMode').addEventListener('click', async function() {
        const name = document.getElementById('addModeName').value.trim();
        const description = document.getElementById('addModeDescription').value.trim();
        const status = document.getElementById('addModeStatus').checked ? 'active' : 'inactive';

        if (!name || !description) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        try {
            const response = await fetch('api/mode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create',
                    name: name,
                    description: description,
                    status: status
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Mode added successfully');
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to add mode');
            }
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    // Edit 
    document.querySelectorAll('.edit-mode').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const status = this.dataset.status;

            document.getElementById('editModeId').value = id;
            document.getElementById('editModeName').value = name;
            document.getElementById('editModeDescription').value = description;
            document.getElementById('editModeStatus').checked = status === 'active';

            editModeModal.show();
        });
    });

    // Update
    document.getElementById('updateMode').addEventListener('click', async function() {
        const id = document.getElementById('editModeId').value;
        const name = document.getElementById('editModeName').value.trim();
        const description = document.getElementById('editModeDescription').value.trim();
        const status = document.getElementById('editModeStatus').checked ? 'active' : 'inactive';

        if (!name || !description) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        try {
            const response = await fetch('api/mode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    id: id,
                    name: name,
                    description: description,
                    status: status
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Mode updated successfully');
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to update mode');
            }
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    // Del
    document.querySelectorAll('.delete-mode').forEach(button => {
        button.addEventListener('click', function() {
            currentModeId = this.dataset.id;
            document.getElementById('deleteModeNameSpan').textContent = this.dataset.name;
            deleteModeModal.show();
        });
    });

    // del
    document.getElementById('confirmDeleteMode').addEventListener('click', async function() {
        if (!currentModeId) return;

        try {
            const response = await fetch('api/mode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: currentModeId
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Mode deleted successfully');
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to delete mode');
            }
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    // Tost
    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type} show`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="toast-header">
                <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
});