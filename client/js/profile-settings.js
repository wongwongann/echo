document.addEventListener('DOMContentLoaded', function() {
    const firstNameInput = document.getElementById('firstName');
    const emailInput = document.getElementById('email');
    const saveProfileBtn = document.getElementById('saveProfileBtn');

    async function loadProfileData() {
        try {
            const response = await fetch('api/profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get'
                })
            });

            const data = await response.json();
            if (data.success) {
                firstNameInput.value = data.data.first_name || '';
                emailInput.value = data.data.email || '';
            } else {
                showToast('Failed to load profile data', 'error');
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            showToast('Error loading profile data', 'error');
        }
    }

    // Save pf changes
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', async function() {
            const firstName = firstNameInput.value.trim();
            const email = emailInput.value.trim();

            if (!firstName) {
                showToast('First name is required', 'error');
                return;
            }
            if (email && !validateEmail(email)) {
                showToast('Please enter a valid email address', 'error');
                return;
            }

            const originalText = saveProfileBtn.innerHTML;
            saveProfileBtn.disabled = true;
            saveProfileBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const response = await fetch('api/profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update',
                        first_name: firstName,
                        email: email
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Profile updated successfully', 'success');
                    // Reload 
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Failed to update profile');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast(error.message || 'An error occurred', 'error');
            } finally {
                saveProfileBtn.disabled = false;
                saveProfileBtn.innerHTML = originalText;
            }
        });
    }

    // Email vall helper
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Tost
    function showToast(message, type = 'success') {
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast" role="alert" style="background-color: ${type === 'success' ? '#10b981' : '#ef4444'}; border-radius: 8px; color: white;">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);

        const toastElement = document.getElementById(toastId);
        const bsToast = new bootstrap.Toast(toastElement);
        bsToast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    loadProfileData();
});
