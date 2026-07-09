window.TicketsView = {
    data: [
        { id: 'TKT-1001', title: 'Header alignment issue on mobile', client: 'Khalifa Computer Group', contractId: 1, type: 'Web Issue', priority: 'Medium', status: 'Open', date: '2026-07-08' },
        { id: 'TKT-1002', title: 'Database crash during report generation', client: 'Acme Corp', contractId: 2, type: 'Report Bug', priority: 'High', status: 'In Progress', date: '2026-07-07' }
    ],
    
    renderHistory: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">Support Tickets</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">View and manage support requests linked to active contracts.</p>
                    </div>
                    <a href="#create-ticket" class="btn-primary flex items-center gap-4" style="text-decoration: none;">
                        <i class="ph ph-plus-circle"></i> Create Ticket
                    </a>
                </div>
                
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass); color: var(--text-secondary);">
                            <th style="padding: 16px 8px; font-weight: 500;">Ticket ID</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Title</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Client</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Type</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Priority</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.data.map(t => {
                            let priorityColor = 'var(--text-secondary)';
                            if (t.priority === 'High' || t.priority === 'Urgent') priorityColor = 'var(--status-danger)';
                            if (t.priority === 'Medium') priorityColor = 'var(--status-warning)';
                            
                            let statusColor = 'var(--text-secondary)';
                            if (t.status === 'Open') statusColor = 'var(--accent-primary)';
                            if (t.status === 'In Progress') statusColor = 'var(--status-warning)';
                            if (t.status === 'Resolved') statusColor = 'var(--status-success)';
                            
                            return `
                                <tr style="border-bottom: 1px solid var(--border-glass); transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 16px 8px; font-weight: 600; color: var(--accent-primary);">${t.id}</td>
                                    <td style="padding: 16px 8px; font-weight: 500;">${t.title}</td>
                                    <td style="padding: 16px 8px; color: var(--text-secondary);">${t.client}</td>
                                    <td style="padding: 16px 8px;">
                                        <span style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;">${t.type}</span>
                                    </td>
                                    <td style="padding: 16px 8px; color: ${priorityColor}; font-weight: 600; font-size: 0.875rem;">${t.priority}</td>
                                    <td style="padding: 16px 8px;">
                                        <span style="color: ${statusColor}; display: flex; align-items: center; gap: 6px; font-size: 0.875rem;">
                                            <div style="width: 6px; height: 6px; border-radius: 50%; background: ${statusColor};"></div>
                                            ${t.status}
                                        </span>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    },
    
    renderCreateForm: function() {
        const currentUser = localStorage.getItem('diginovia_user') || '';
        const isAgent = currentUser.includes('admin');
        
        // Fetch active contracts
        const allContracts = window.ContractsView ? window.ContractsView.data : [];
        const activeContracts = allContracts.filter(c => c.status === 'Active');
        
        // List of unique clients from active contracts (for admin view)
        const clients = [...new Set(activeContracts.map(c => c.clientName))];

        return `
            <div class="glass-panel" style="padding: 32px; max-width: 800px; margin: 0 auto;">
                <h2 style="font-size: 1.25rem; font-family: var(--font-secondary); margin-bottom: 8px;">Create Support Ticket</h2>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 24px;">Please fill out the form below. Tickets must be linked to an active support contract.</p>
                
                <form id="create-ticket-form" onsubmit="window.TicketsView.handleSubmit(event)">
                    ${isAgent ? `
                        <div class="mb-6">
                            <label class="input-label" for="t-client">Select Client</label>
                            <select id="t-client" class="input-field" onchange="window.TicketsView.handleClientChange(this.value)" required>
                                <option value="" disabled selected>Choose a client...</option>
                                ${clients.map(c => `<option value="${c}">${c}</option>`).join('')}
                            </select>
                        </div>
                        <div class="mb-6" id="contract-selection-container" style="display: none;">
                            <label class="input-label" for="t-contract">Linked Contract</label>
                            <select id="t-contract" class="input-field" onchange="window.TicketsView.handleContractChange(this.value)" required>
                                <!-- Dynamically loaded -->
                            </select>
                        </div>
                    ` : `
                        <div class="mb-6">
                            <label class="input-label" for="t-contract">Linked Contract</label>
                            <select id="t-contract" class="input-field" onchange="window.TicketsView.handleContractChange(this.value)" required>
                                <option value="" disabled selected>Choose one of your active contracts...</option>
                                ${activeContracts.filter(c => c.clientName === currentUser).map(c => `
                                    <option value="${c.id}">Contract #${c.id} - ${c.type}</option>
                                `).join('')}
                            </select>
                        </div>
                    `}
                    
                    <div class="mb-6">
                        <label class="input-label" for="t-title">Ticket Title</label>
                        <input type="text" id="t-title" class="input-field" placeholder="e.g. Broken styling on landing page header" required>
                    </div>
                    
                    <div class="mb-6">
                        <label class="input-label" for="t-desc">Detailed Description</label>
                        <textarea id="t-desc" class="input-field" rows="6" placeholder="Describe the steps to reproduce, errors encountered, or changes requested..." required></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label class="input-label">Issue Type</label>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                            ${['Web Issue', 'Report Bug', 'Fix Request', 'General Support'].map((type, idx) => `
                                <button type="button" class="issue-type-btn ${idx === 0 ? 'active' : ''}" 
                                        onclick="window.TicketsView.selectIssueType(this, '${type}')"
                                        style="padding: 10px; border-radius: var(--radius-md); background: ${idx === 0 ? 'var(--accent-primary)' : 'rgba(255,255,255,0.05)'}; color: ${idx === 0 ? '#fff' : 'var(--text-secondary)'}; font-weight: 500; font-size: 0.875rem; border: 1px solid var(--border-glass); cursor: pointer; text-align: center; transition: var(--transition);">
                                    ${type}
                                </button>
                            `).join('')}
                        </div>
                        <input type="hidden" id="t-type" value="Web Issue">
                    </div>
                    
                    <div class="mb-6">
                        <label class="input-label">Priority Level</label>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; background: var(--bg-main); padding: 4px; border-radius: var(--radius-md); border: 1px solid var(--border-glass);">
                            ${['Low', 'Medium', 'High', 'Urgent'].map((priority, idx) => `
                                <button type="button" class="priority-btn ${idx === 1 ? 'active' : ''}" 
                                        onclick="window.TicketsView.selectPriority(this, '${priority}')"
                                        style="padding: 8px; border-radius: 4px; background: ${idx === 1 ? 'var(--accent-primary)' : 'transparent'}; border: none; color: ${idx === 1 ? '#fff' : 'var(--text-secondary)'}; font-weight: 500; font-size: 0.875rem; cursor: pointer; text-align: center; transition: var(--transition);">
                                    ${priority}
                                </button>
                            `).join('')}
                        </div>
                        <input type="hidden" id="t-priority" value="Medium">
                    </div>
                    
                    <div class="mb-6">
                        <label class="input-label">Attachments</label>
                        <div id="drop-zone" style="border: 2px dashed var(--border-glass); border-radius: var(--radius-lg); padding: 32px; text-align: center; cursor: pointer; transition: var(--transition);" ondragover="event.preventDefault(); this.style.borderColor='var(--accent-primary)';" ondragleave="this.style.borderColor='var(--border-glass)';" onclick="document.getElementById('t-file').click()">
                            <i class="ph ph-cloud-arrow-up" style="font-size: 2.5rem; color: var(--accent-primary); margin-bottom: 8px;"></i>
                            <div style="font-weight: 500; font-size: 0.875rem; margin-bottom: 4px;">Drag & drop files here, or click to upload</div>
                            <div style="color: var(--text-muted); font-size: 0.75rem;">Supports screenshots, logs, and docs (Max 10MB)</div>
                            <input type="file" id="t-file" style="display: none;" onchange="window.TicketsView.handleFileSelect(this)">
                            <div id="selected-file-name" style="margin-top: 8px; font-size: 0.875rem; color: var(--status-success); font-weight: 500;"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Submit Ticket</button>
                </form>
            </div>
        `;
    },
    
    selectIssueType: function(button, type) {
        const btns = document.querySelectorAll('.issue-type-btn');
        btns.forEach(btn => {
            btn.style.background = 'rgba(255,255,255,0.05)';
            btn.style.color = 'var(--text-secondary)';
        });
        button.style.background = 'var(--accent-primary)';
        button.style.color = '#fff';
        document.getElementById('t-type').value = type;
    },
    
    selectPriority: function(button, priority) {
        const btns = document.querySelectorAll('.priority-btn');
        btns.forEach(btn => {
            btn.style.background = 'transparent';
            btn.style.color = 'var(--text-secondary)';
        });
        button.style.background = 'var(--accent-primary)';
        button.style.color = '#fff';
        document.getElementById('t-priority').value = priority;
    },
    
    handleFileSelect: function(input) {
        const fileNameDiv = document.getElementById('selected-file-name');
        if (input.files && input.files.length > 0) {
            fileNameDiv.innerText = `Selected file: ${input.files[0].name}`;
        }
    },
    
    handleClientChange: function(clientName) {
        const allContracts = window.ContractsView ? window.ContractsView.data : [];
        const activeContracts = allContracts.filter(c => c.clientName === clientName && c.status === 'Active');
        
        const contractSelect = document.getElementById('t-contract');
        const container = document.getElementById('contract-selection-container');
        
        if (contractSelect) {
            contractSelect.innerHTML = `<option value="" disabled selected>Choose a contract...</option>` +
                activeContracts.map(c => `
                    <option value="${c.id}">Contract #${c.id} - ${c.type}</option>
                `).join('');
            container.style.display = 'block';
        }
    },
    
    handleContractChange: function(contractId) {
        const allContracts = window.ContractsView ? window.ContractsView.data : [];
        const contract = allContracts.find(c => c.id == contractId);
        
        if (contract) {
            let issueType = 'General Support';
            if (contract.type === 'Web') issueType = 'Web Issue';
            if (contract.type === 'Fix') issueType = 'Fix Request';
            if (contract.type === 'Report') issueType = 'Report Bug';
            if (contract.type === 'Mobile App') issueType = 'Web Issue';
            
            const btns = document.querySelectorAll('.issue-type-btn');
            btns.forEach(btn => {
                if (btn.innerText.trim() === issueType) {
                    this.selectIssueType(btn, issueType);
                }
            });
        }
    },
    
    handleSubmit: function(e) {
        e.preventDefault();
        
        const clientNameSelect = document.getElementById('t-client');
        const clientName = clientNameSelect ? clientNameSelect.value : localStorage.getItem('diginovia_user');
        const contractId = document.getElementById('t-contract').value;
        const title = document.getElementById('t-title').value;
        const type = document.getElementById('t-type').value;
        const priority = document.getElementById('t-priority').value;
        
        if (!contractId) {
            alert('An active contract must be selected.');
            return;
        }
        
        const ticketId = `TKT-${Math.floor(1000 + Math.random() * 9000)}`;
        
        const newTicket = {
            id: ticketId,
            title,
            client: clientName,
            contractId: parseInt(contractId),
            type,
            priority,
            status: 'Open',
            date: new Date().toISOString().split('T')[0]
        };
        
        this.data.unshift(newTicket);
        
        const alertOverlay = document.createElement('div');
        alertOverlay.style.position = 'fixed';
        alertOverlay.style.top = '0';
        alertOverlay.style.left = '0';
        alertOverlay.style.width = '100vw';
        alertOverlay.style.height = '100vh';
        alertOverlay.style.background = 'rgba(0,0,0,0.7)';
        alertOverlay.style.display = 'flex';
        alertOverlay.style.alignItems = 'center';
        alertOverlay.style.justifyContent = 'center';
        alertOverlay.style.zIndex = '2000';
        alertOverlay.style.backdropFilter = 'blur(8px)';
        
        alertOverlay.innerHTML = `
            <div class="glass-panel animate-fade-in" style="max-width: 400px; width: 90%; padding: 32px; text-align: center; border: 1px solid var(--border-glass);">
                <i class="ph ph-check-circle" style="font-size: 4rem; color: var(--status-success); margin-bottom: 16px;"></i>
                <h3 style="font-size: 1.5rem; margin-bottom: 8px; font-family: var(--font-secondary);">Ticket Created!</h3>
                <p style="color: var(--text-secondary); margin-bottom: 24px;">Ticket <strong style="color: var(--accent-primary);">${ticketId}</strong> has been generated and assigned successfully.</p>
                <button class="btn-primary" style="width: 100%;" onclick="window.TicketsView.closeAlertRedirect(this)">View Ticket History</button>
            </div>
        `;
        
        document.body.appendChild(alertOverlay);
    },
    
    closeAlertRedirect: function(button) {
        button.closest('div').parentElement.remove();
        window.location.hash = 'tickets';
    }
};
