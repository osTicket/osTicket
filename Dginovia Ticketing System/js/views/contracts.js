window.ContractsView = {
    data: [
        { id: 1, clientName: 'Khalifa Computer Group', type: 'Web', value: 25000, currency: 'USD', startDate: '2026-01-01', endDate: '2026-12-31', status: 'Active' },
        { id: 2, clientName: 'Acme Corp', type: 'Fix', value: 5000, currency: 'EUR', startDate: '2026-03-15', endDate: '2026-04-15', status: 'Completed' },
        { id: 3, clientName: 'Global Tech', type: 'Mobile App', value: 45000, currency: 'SAR', startDate: '2026-06-01', endDate: '2026-11-30', status: 'Draft' }
    ],
    
    clients: ['Khalifa Computer Group', 'Acme Corp', 'Global Tech', 'Vance Refrigeration', 'Initech'],

    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px; position: relative;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">Client Agreements & Contracts</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Manage, track, and create contracts for all active clients.</p>
                    </div>
                    <button class="btn-primary flex items-center gap-4" onclick="window.ContractsView.openModal()">
                        <i class="ph ph-plus-circle"></i> Create a new Contract
                    </button>
                </div>
                
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass); color: var(--text-secondary);">
                            <th style="padding: 16px 8px; font-weight: 500;">Client Name</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Type</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Contract Value</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Duration</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="contracts-table-body">
                        ${this.renderTableRows()}
                    </tbody>
                </table>
            </div>

            <!-- Create Contract Modal -->
            <div id="contract-modal" class="flex items-center justify-center" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); z-index: 1000; backdrop-filter: blur(8px);">
                <div class="glass-panel animate-fade-in" style="width: 100%; max-width: 500px; padding: 32px; border: 1px solid var(--border-glass); position: relative;">
                    <button onclick="window.ContractsView.closeModal()" style="position: absolute; top: 16px; right: 16px; background: transparent; color: var(--text-secondary); font-size: 1.5rem; border: none; cursor: pointer;">
                        <i class="ph ph-x"></i>
                    </button>
                    
                    <h3 style="font-size: 1.25rem; margin-bottom: 24px; font-family: var(--font-secondary);">Create New Contract</h3>
                    
                    <form id="create-contract-form" onsubmit="window.ContractsView.handleSubmit(event)">
                        <div class="mb-6">
                            <label class="input-label" for="c-client">Client Name</label>
                            <input type="text" id="c-client" class="input-field" placeholder="Search or select client..." list="clients-list" required>
                            <datalist id="clients-list">
                                ${this.clients.map(c => `<option value="${c}"></option>`).join('')}
                            </datalist>
                        </div>
                        
                        <div class="mb-6">
                            <label class="input-label">Contract Type</label>
                            <!-- Segmented control bar -->
                            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; background: var(--bg-main); padding: 4px; border-radius: var(--radius-md); border: 1px solid var(--border-glass);">
                                ${['Web', 'Report', 'Fix', 'Mobile App', 'Other'].map((type, idx) => `
                                    <button type="button" class="type-segment-btn ${idx === 0 ? 'active' : ''}" 
                                            onclick="window.ContractsView.selectType(this, '${type}')" 
                                            style="padding: 8px 4px; font-size: 0.75rem; border-radius: 4px; background: ${idx === 0 ? 'var(--accent-primary)' : 'transparent'}; border: none; color: ${idx === 0 ? '#fff' : 'var(--text-secondary)'}; font-weight: 500; text-align: center; cursor: pointer; outline: none; transition: var(--transition);">
                                        ${type}
                                    </button>
                                `).join('')}
                            </div>
                            <input type="hidden" id="c-type" value="Web">
                            
                            <!-- Conditional input for "Other" -->
                            <div id="c-custom-type-container" style="display: none; margin-top: 12px; transition: var(--transition);" class="animate-fade-in">
                                <label class="input-label" for="c-custom-type">Custom Contract Name</label>
                                <input type="text" id="c-custom-type" class="input-field" placeholder="e.g. Graphic Design Support">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="input-label" for="c-value">Contract Value</label>
                            <div style="display: flex; gap: 8px;">
                                <select id="c-currency" class="input-field" style="width: 100px; flex-shrink: 0; padding-right: 8px; font-weight: 600;">
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="EGP">EGP (E£)</option>
                                    <option value="SAR">SAR (SR)</option>
                                    <option value="AED">AED (د.إ)</option>
                                    <option value="KWD">KWD (د.ك)</option>
                                    <option value="QAR">QAR (ر.ق)</option>
                                    <option value="OMR">OMR (ر.ع.)</option>
                                    <option value="BHD">BHD (د.ب)</option>
                                </select>
                                <input type="number" id="c-value" class="input-field" placeholder="0.00" required min="0" step="0.01" style="flex: 1;">
                            </div>
                        </div>
                        
                        <div class="flex gap-4 mb-6">
                            <div style="flex: 1;">
                                <label class="input-label" for="c-start">Start Date</label>
                                <input type="date" id="c-start" class="input-field" required>
                            </div>
                            <div style="flex: 1;">
                                <label class="input-label" for="c-end">End Date</label>
                                <input type="date" id="c-end" class="input-field" required>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="input-label" for="c-status">Status</label>
                            <select id="c-status" class="input-field">
                                <option value="Draft">Draft</option>
                                <option value="Active" selected>Active</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-primary" style="width: 100%;">Create Contract</button>
                    </form>
                </div>
            </div>
        `;
    },
    
    renderTableRows: function() {
        return this.data.map(contract => {
            let statusColor = 'var(--text-secondary)';
            if (contract.status === 'Active') statusColor = 'var(--status-success)';
            if (contract.status === 'Draft') statusColor = 'var(--status-warning)';
            if (contract.status === 'Completed') statusColor = 'var(--accent-primary)';
            
            return `
                <tr style="border-bottom: 1px solid var(--border-glass); transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 16px 8px; font-weight: 500;">${contract.clientName}</td>
                    <td style="padding: 16px 8px;">
                        <span class="type-badge type-${contract.type.toLowerCase().replace(' ', '-')}" style="
                            padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;
                            ${this.getTypeBadgeStyle(contract.type)}
                        ">${contract.type}</span>
                    </td>
                    <td style="padding: 16px 8px; font-weight: 600;">${this.formatCurrency(contract.value, contract.currency)}</td>
                    <td style="padding: 16px 8px; color: var(--text-secondary); font-size: 0.875rem;">${contract.startDate} to ${contract.endDate}</td>
                    <td style="padding: 16px 8px;">
                        <span style="color: ${statusColor}; display: flex; align-items: center; gap: 6px; font-size: 0.875rem;">
                            <div style="width: 6px; height: 6px; border-radius: 50%; background: ${statusColor};"></div>
                            ${contract.status}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    },
    
    formatCurrency: function(val, cur) {
        let prefix = '';
        if (cur === 'USD') prefix = '$';
        else if (cur === 'EUR') prefix = '€';
        else if (cur === 'EGP') prefix = 'E£ ';
        else if (cur === 'SAR') prefix = 'SR ';
        
        const formatted = val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        return `${prefix}${formatted} ${cur}`;
    },
    
    getTypeBadgeStyle: function(type) {
        switch(type) {
            case 'Web': return 'background: rgba(59, 130, 246, 0.1); color: #3B82F6;';
            case 'Report': return 'background: rgba(139, 92, 246, 0.1); color: #8B5CF6;';
            case 'Fix': return 'background: rgba(239, 68, 68, 0.1); color: #EF4444;';
            case 'Mobile App': return 'background: rgba(16, 185, 129, 0.1); color: #10B981;';
            default: return 'background: rgba(245, 158, 11, 0.1); color: #F59E0B;';
        }
    },
    
    openModal: function() {
        const modal = document.getElementById('contract-modal');
        if (modal) modal.style.display = 'flex';
    },
    
    closeModal: function() {
        const modal = document.getElementById('contract-modal');
        if (modal) modal.style.display = 'none';
        document.getElementById('create-contract-form').reset();
        
        // Reset segment control style
        const segmentBtns = document.querySelectorAll('.type-segment-btn');
        segmentBtns.forEach((btn, idx) => {
            btn.style.background = idx === 0 ? 'var(--accent-primary)' : 'transparent';
            btn.style.color = idx === 0 ? '#fff' : 'var(--text-secondary)';
        });
        document.getElementById('c-type').value = 'Web';
        
        // Reset other-custom field
        const customContainer = document.getElementById('c-custom-type-container');
        const customInput = document.getElementById('c-custom-type');
        if (customContainer) {
            customContainer.style.display = 'none';
            customInput.removeAttribute('required');
            customInput.value = '';
        }
    },
    
    selectType: function(button, type) {
        const segmentBtns = document.querySelectorAll('.type-segment-btn');
        segmentBtns.forEach(btn => {
            btn.style.background = 'transparent';
            btn.style.color = 'var(--text-secondary)';
        });
        button.style.background = 'var(--accent-primary)';
        button.style.color = '#fff';
        document.getElementById('c-type').value = type;
        
        const customContainer = document.getElementById('c-custom-type-container');
        const customInput = document.getElementById('c-custom-type');
        if (type === 'Other') {
            customContainer.style.display = 'block';
            customInput.setAttribute('required', 'required');
            customInput.focus();
        } else {
            customContainer.style.display = 'none';
            customInput.removeAttribute('required');
            customInput.value = '';
        }
    },
    
    handleSubmit: function(e) {
        e.preventDefault();
        
        const clientName = document.getElementById('c-client').value;
        let type = document.getElementById('c-type').value;
        if (type === 'Other') {
            type = document.getElementById('c-custom-type').value || 'Other';
        }
        
        const value = parseFloat(document.getElementById('c-value').value);
        const currency = document.getElementById('c-currency').value;
        const startDate = document.getElementById('c-start').value;
        const endDate = document.getElementById('c-end').value;
        const status = document.getElementById('c-status').value;
        
        const newContract = {
            id: this.data.length + 1,
            clientName,
            type,
            value,
            currency,
            startDate,
            endDate,
            status
        };
        
        this.data.push(newContract);
        
        // Refresh table rows
        const tableBody = document.getElementById('contracts-table-body');
        if (tableBody) {
            tableBody.innerHTML = this.renderTableRows();
        }
        
        // Also dynamically refresh Create Ticket forms client contracts dropdowns if loaded
        if (window.TicketsView && window.TicketsView.handleClientChange) {
            const clientSelect = document.getElementById('t-client');
            if (clientSelect && clientSelect.value) {
                window.TicketsView.handleClientChange(clientSelect.value);
            }
        }
        
        this.closeModal();
    }
};
