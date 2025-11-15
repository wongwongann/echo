let skuInput, resultsContainer;
let ws; 

function initWebSocket() {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const hostname = window.location.hostname;
    const wsPort = '3000';
    const wsUrl = `${protocol}//${hostname}:${wsPort}`;
    
    ws = new WebSocket(wsUrl);
    
    ws.onopen = () => {
        console.log('WebSocket connected to:', wsUrl);
    };
    
    ws.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data);
            if (data.type === 'broadcasts') {
                setupPublishButtons();
            }
        } catch (error) {
            console.error('WebSocket message error:', error);
        }
    };
    
    ws.onclose = () => {
        console.log('WebSocket disconnected, attempting to reconnect...');
        setTimeout(initWebSocket, 3000);
    };
    
    ws.onerror = (error) => {
        console.error('WebSocket error:', error);
    };
}

document.addEventListener('DOMContentLoaded', function() {
    skuInput = document.getElementById('object1Input');
    resultsContainer = document.createElement('div');
    resultsContainer.className = 'autocomplete-results';
    skuInput.parentNode.appendChild(resultsContainer);
    
    initWebSocket();

    // Reset & setup form fieldsz
    ['modeInput', 'object1Input', 'object2Input', 'memoInput'].forEach(id => {
        document.getElementById(id).value = '';
        document.getElementById(id).addEventListener('input', updatePreview);
    });

    // Setup event listeners
    setupAutoComplete();
    setupPreviewTabs();
    setupPublishButtons();
    document.getElementById('submitForm').addEventListener('click', submitBroadcast);
    updatePreview();
});

function updatePreview() {
    const mode = document.getElementById('modeInput').value || '---';
    const skuFull = skuInput.value || '---';
    const parts = skuFull.split(' - ');
    const description = parts[0] || '---';
    const sku = skuInput.getAttribute('data-sku') || parts[1] || '---';
    const param = document.getElementById('object2Input').value || '---';
    const memo = document.getElementById('memoInput').value || '---';

    // Update detailed preview
    document.getElementById('modePreview').textContent = mode;
    document.getElementById('object1Preview').textContent = skuFull;
    document.getElementById('object2Preview').textContent = param;
    document.getElementById('memoPreview').textContent = memo;

    // Update text preview
    const mainText = `${mode} ${description} - ${sku} ${param}`.trim();
    document.getElementById('textPreviewMain').textContent = mainText || '---';
    document.getElementById('textPreviewMemo').textContent = memo;
}

function setupAutoComplete() {
    let debounceTimer;

    // SKU Input handler
    skuInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const searchValue = this.value.trim();
            if (searchValue.length >= 2) {
                fetchSuggestions(searchValue);
            } else {
                hideResults();
            }
        }, 300);
    });

    // Close rslt
    document.addEventListener('click', function(e) {
        if (!skuInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            hideResults();
        }
    });
}

function fetchSuggestions(query) {
    fetch('api/echo.item.php?function=get')
        .then(response => response.json())
        .then(data => {
            if (data.status === 1 && Array.isArray(data.data)) {
                const filteredItems = data.data.filter(item => {
                    const itemDesc = (item.description || '').toString().toLowerCase();
                    const itemSku = (item.sku || '').toString().toLowerCase();
                    const searchQuery = query.toLowerCase();
                    return itemSku.includes(searchQuery) || itemDesc.includes(searchQuery);
                });
                displayResults(filteredItems.slice(0, 10));
            }
        })
        .catch(error => {
            console.error('Error fetching suggestions:', error);
            showToast('Error fetching suggestions. Please try again.', 'error');
        });
}

function displayResults(items) {
    if (items.length === 0) {
        hideResults();
        return;
    }

    resultsContainer.innerHTML = items.map(item => `
        <div class="autocomplete-item" data-sku="${item.sku}" data-description="${item.description || ''}">
            <div class="p-2">
                <div class="autocomplete-item-title">${item.sku}</div>
                ${item.description ? `<div class="autocomplete-item-subtitle">${item.description}</div>` : ''}
            </div>
        </div>
    `).join('');

    resultsContainer.classList.add('show');

    // click handlers for results
    resultsContainer.querySelectorAll('.autocomplete-item').forEach(item => {
        item.addEventListener('click', function() {
            const sku = this.dataset.sku;
            const description = this.dataset.description;
            skuInput.value = description ? `${description} - ${sku}` : sku;
            skuInput.setAttribute('data-sku', sku);
            hideResults();
            updatePreview();
        });
    });
}

function hideResults() {
    resultsContainer.innerHTML = '';
    resultsContainer.classList.remove('show');
}

function setupPreviewTabs() {
    document.querySelectorAll('.preview-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.preview-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.preview-pane').forEach(p => p.classList.remove('active'));
            
            tab.classList.add('active');
            const paneId = tab.dataset.tab === 'detailed' ? 'detailedPreview' : 'textPreview';
            document.getElementById(paneId).classList.add('active');
        });
    });
}

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

function resetForm() {
    ['modeInput', 'object1Input', 'object2Input', 'memoInput'].forEach(id => {
        document.getElementById(id).value = '';
    });
    skuInput.removeAttribute('data-sku');
    updatePreview();
}

// Submit handler
async function submitBroadcast() {
    try {
        const mode = document.getElementById('modeInput').value.trim();
        const sku = skuInput.value.trim();
        const param = document.getElementById('object2Input').value.trim();

        if (!mode) {
            throw new Error('Mode is required');
        }
        if (!sku) {
            throw new Error('SKU/Description is required');
        }
        if (!param) {
            throw new Error('Parameter is required');
        }

        await submitData();
        resetForm();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

// Separating data submission logic
async function submitData() {
    const submitBtn = document.getElementById('submitForm');
    const spinner = submitBtn.querySelector('.spinner-border');
    const buttonText = submitBtn.querySelector('.button-text');

    submitBtn.disabled = true;
    buttonText.textContent = 'Submitting...';
    spinner.classList.remove('d-none');

    try {
        const response = await fetch('include/submit_broadcast.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(getFormData())
        });

        const data = await response.json();

        if (data.success) {
            showToast('Data saved successfully. Code: ' + data.code);

            const event = new CustomEvent('broadcastSubmitted', {
                detail: {
                    broadcast: data.broadcast
                }
            });
            document.dispatchEvent(event);
        } else {
            throw new Error(data.message || 'Failed to save data');
        }
    } finally {
        submitBtn.disabled = false;
        buttonText.textContent = 'Submit';
        spinner.classList.add('d-none');
    }
}

function getFormData() {
    return {
        mode: document.getElementById('modeInput').value,
        object1: skuInput.getAttribute('data-sku') || skuInput.value.split(' - ')[0],
        object2: document.getElementById('object2Input').value,
        memo: document.getElementById('memoInput').value
    };
}

// Setup publish btns
function setupPublishButtons() {
    let currentPublishBtn = null;
    const republishModal = new bootstrap.Modal(document.getElementById('republishModal'));
    const confirmRepublishBtn = document.getElementById('confirmRepublish');

    // Remove existing event listeners
    document.querySelectorAll('.btn-publish').forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
    });

    // Add fresh event listeners
    document.querySelectorAll('.btn-publish').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.stopPropagation(); // Prevent history expansion
            const code = this.getAttribute('data-code');
            if (!code) return;

            // if published
            try {
                const checkResponse = await fetch(`api/webhook.publish.php?check=1&code=${code}`);
                const checkData = await checkResponse.json();

                if (checkData.isPublished) {
                    // Show conf modal
                    currentPublishBtn = this;
                    document.getElementById('lastPublishDate').textContent = checkData.lastPublishDate;
                    republishModal.show();
                } else {
                    publishBroadcast(code);
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        });
    });

    // Handle confirmation
    confirmRepublishBtn.addEventListener('click', async function() {
        if (currentPublishBtn) {
            const code = currentPublishBtn.getAttribute('data-code');
            republishModal.hide();
            await publishBroadcast(code);
            currentPublishBtn = null;
        }
    });
}

async function publishBroadcast(code) {
    try {
        const button = document.querySelector(`[data-code="${code}"]`);
        if (button) button.disabled = true;

        const response = await fetch('api/webhook.publish.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ code })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast(data.message);
            // Update UI immediately
            const statusSpan = button?.closest('.broadcast-item')?.querySelector('.publish-status');
            if (statusSpan) {
                statusSpan.classList.remove('unpublished');
                statusSpan.classList.add('published');
                statusSpan.textContent = 'Published';
            }
            // If there are any warning details, show them
            if (data.details) {
                data.details.forEach(detail => {
                    showToast(detail, 'warning');
                });
            }
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        showToast(error.message, 'error');
        // Re-enable button on error
        const button = document.querySelector(`[data-code="${code}"]`);
        if (button) button.disabled = false;
    }
}