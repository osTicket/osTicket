window.InviteView = {
    pendingInvites: [
        { email: 'pending.client@acme.com', type: 'Client', sentDate: '2026-07-07', token: 'tok_89af32' },
        { email: 'developer.john@diginovia.com', type: 'Member', sentDate: '2026-07-06', token: 'tok_12de45' }
    ],
    
    render: function() {
        const activeContracts = window.ContractsView ? window.ContractsView.data.filter(c => c.status === 'Active') : [];
        const uniqueCompanies = window.ContractsView ? [...new Set(window.ContractsView.data.map(c => c.clientName))] : ['Acme Corp', 'Khalifa Computer Group', 'Global Tech'];

        return `
            <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 32px;">
                <!-- Back Link -->
                <div>
                    <a href="#users" style="display: inline-flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; font-size: 0.875rem;">
                        <i class="ph ph-arrow-left"></i> Back to User Management
                    </a>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">
                    <!-- Invitation Form Card -->
                    <div class="glass-panel" style="padding: 32px;">
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary); margin-bottom: 8px;">Invite New User</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 24px;">Send a secure invitation link to register a new client or team member.</p>
                        
                        <form id="invite-user-form" onsubmit="window.InviteView.handleSubmit(event)">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                                <div>
                                    <label class="input-label" for="i-email">Invitee Email Address</label>
                                    <input type="email" id="i-email" class="input-field" placeholder="name@company.com" required>
                                </div>
                                <div>
                                    <label class="input-label" for="i-name">Full Name</label>
                                    <input type="text" id="i-name" class="input-field" placeholder="First and Last name" required>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="input-label">User Type</label>
                                <!-- Segmented Toggle -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; background: var(--bg-main); padding: 4px; border-radius: var(--radius-md); border: 1px solid var(--border-glass); max-width: 300px;">
                                    <button type="button" class="user-type-btn active" id="btn-type-client" onclick="window.InviteView.selectUserType('Client')" style="padding: 10px; border-radius: 4px; background: var(--accent-primary); border: none; color: #fff; font-weight: 500; font-size: 0.875rem; cursor: pointer; text-align: center;">
                                        Client
                                    </button>
                                    <button type="button" class="user-type-btn" id="btn-type-member" onclick="window.InviteView.selectUserType('Member')" style="padding: 10px; border-radius: 4px; background: transparent; border: none; color: var(--text-secondary); font-weight: 500; font-size: 0.875rem; cursor: pointer; text-align: center;">
                                        Member (Staff)
                                    </button>
                                </div>
                                <input type="hidden" id="i-usertype" value="Client">
                            </div>
                            
                            <!-- Dynamic Section: Client Option Fields -->
                            <div id="client-fields" class="animate-fade-in" style="margin-bottom: 24px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                                    <div>
                                        <label class="input-label" for="i-company">Company / Organization</label>
                                        <input type="text" id="i-company" class="input-field" placeholder="Select or type..." list="companies-list" required>
                                        <datalist id="companies-list">
                                            ${uniqueCompanies.map(co => `<option value="${co}"></option>`).join('')}
                                        </datalist>
                                    </div>
                                    <div>
                                        <label class="input-label" for="i-role-client">Client Permissions Role</label>
                                        <select id="i-role-client" class="input-field">
                                            <option value="Primary Account Owner">Primary Account Owner (Billing & Contracts)</option>
                                            <option value="Standard User" selected>Standard User (Submit & View Tickets)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="input-label">Linked Contract(s)</label>
                                    <div style="max-height: 120px; overflow-y: auto; background: rgba(0,0,0,0.2); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 12px;">
                                        ${activeContracts.length === 0 ? '<div style="color: var(--text-muted); font-size: 0.875rem;">No active contracts found.</div>' : activeContracts.map(c => `
                                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; cursor: pointer; font-size: 0.875rem;">
                                                <input type="checkbox" class="contract-checkbox" value="${c.id}">
                                                <span>Contract #${c.id} - ${c.clientName} (${c.type})</span>
                                            </label>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dynamic Section: Member Option Fields -->
                            <div id="member-fields" class="animate-fade-in" style="display: none; margin-bottom: 24px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                                    <div>
                                        <label class="input-label" for="i-dept">Department Assignment</label>
                                        <select id="i-dept" class="input-field">
                                            <option value="Development">Development</option>
                                            <option value="QA">QA (Quality Assurance)</option>
                                            <option value="Technical Support">Technical Support</option>
                                            <option value="Sales">Sales</option>
                                            <option value="Management">Management</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="input-label" for="i-role-member">System Role (Permissions)</label>
                                        <select id="i-role-member" class="input-field">
                                            <option value="Super Admin">Super Admin</option>
                                            <option value="Team Lead">Team Lead</option>
                                            <option value="Support Agent" selected>Support Agent</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="input-label" for="i-capacity">Ticket Capacity Limit</label>
                                    <input type="number" id="i-capacity" class="input-field" placeholder="Unlimited" min="1">
                                    <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 4px;">Max active assignments allowed at once.</span>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; border-top: 1px solid var(--border-glass); padding-top: 24px;">
                                <div>
                                    <label class="input-label" for="i-expiry">Invitation Expiry</label>
                                    <select id="i-expiry" class="input-field">
                                        <option value="24 Hours">24 Hours</option>
                                        <option value="48 Hours" selected>48 Hours</option>
                                        <option value="7 Days">7 Days</option>
                                        <option value="Never">Never</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="input-label" for="i-welcome">Custom Welcome Message (Optional)</label>
                                <textarea id="i-welcome" class="input-field" rows="4" placeholder="Add a personal greeting that will be included in the invitation email..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn-primary" style="padding: 12px 24px;">Send Invitation</button>
                        </form>
                    </div>

                    <!-- Pending Invitations Table -->
                    <div class="glass-panel" style="padding: 24px;">
                        <h3 style="font-size: 1.1rem; font-family: var(--font-secondary); margin-bottom: 16px;">Pending Invitations</h3>
                        
                        <table style="width: 100%; text-align: left; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-glass); color: var(--text-secondary); font-size: 0.875rem;">
                                    <th style="padding: 12px 8px; font-weight: 500;">Email</th>
                                    <th style="padding: 12px 8px; font-weight: 500;">Type</th>
                                    <th style="padding: 12px 8px; font-weight: 500;">Sent Date</th>
                                    <th style="padding: 12px 8px; font-weight: 500; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="pending-invites-body">
                                ${this.renderTableRows()}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    },

    renderTableRows: function() {
        if (this.pendingInvites.length === 0) {
            return `<tr><td colspan="4" style="padding: 16px; text-align: center; color: var(--text-muted); font-size: 0.875rem;">No pending invitations</td></tr>`;
        }
        return this.pendingInvites.map(invite => `
            <tr style="border-bottom: 1px solid var(--border-glass); transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                <td style="padding: 12px 8px; font-size: 0.875rem; font-weight: 500;">${invite.email}</td>
                <td style="padding: 12px 8px; font-size: 0.875rem;">
                    <span style="background: ${invite.type === 'Client' ? 'rgba(59,130,246,0.1)' : 'rgba(16,185,129,0.1)'}; color: ${invite.type === 'Client' ? 'var(--accent-primary)' : 'var(--status-success)'}; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                        ${invite.type}
                    </span>
                </td>
                <td style="padding: 12px 8px; font-size: 0.875rem; color: var(--text-secondary);">${invite.sentDate}</td>
                <td style="padding: 12px 8px; text-align: right;">
                    <button onclick="window.InviteView.reinvite('${invite.email}')" style="background: transparent; color: var(--accent-primary); border: none; font-size: 0.875rem; font-weight: 500; margin-right: 12px; cursor: pointer;">Reinvite</button>
                    <button onclick="window.InviteView.revoke('${invite.email}')" style="background: transparent; color: var(--status-danger); border: none; font-size: 0.875rem; font-weight: 500; cursor: pointer;">Revoke</button>
                </td>
            </tr>
        `).join('');
    },

    selectUserType: function(type) {
        const clientBtns = document.getElementById('btn-type-client');
        const memberBtns = document.getElementById('btn-type-member');
        const clientFields = document.getElementById('client-fields');
        const memberFields = document.getElementById('member-fields');
        
        const clientInputs = clientFields.querySelectorAll('input, select');
        const memberInputs = memberFields.querySelectorAll('input, select');

        document.getElementById('i-usertype').value = type;

        if (type === 'Client') {
            clientBtns.classList.add('active');
            clientBtns.style.background = 'var(--accent-primary)';
            clientBtns.style.color = '#fff';
            
            memberBtns.classList.remove('active');
            memberBtns.style.background = 'transparent';
            memberBtns.style.color = 'var(--text-secondary)';
            
            clientFields.style.display = 'block';
            memberFields.style.display = 'none';

            // Manage required attributes
            clientInputs.forEach(i => { if (i.id === 'i-company') i.setAttribute('required', 'required') });
            memberInputs.forEach(i => i.removeAttribute('required'));
        } else {
            memberBtns.classList.add('active');
            memberBtns.style.background = 'var(--accent-primary)';
            memberBtns.style.color = '#fff';
            
            clientBtns.classList.remove('active');
            clientBtns.style.background = 'transparent';
            clientBtns.style.color = 'var(--text-secondary)';
            
            memberFields.style.display = 'block';
            clientFields.style.display = 'none';

            // Manage required attributes
            memberInputs.forEach(i => i.removeAttribute('required'));
            clientInputs.forEach(i => i.removeAttribute('required'));
        }
    },

    handleSubmit: function(e) {
        e.preventDefault();
        
        const email = document.getElementById('i-email').value;
        const name = document.getElementById('i-name').value;
        const userType = document.getElementById('i-usertype').value;
        const token = 'tok_' + Math.random().toString(36).substr(2, 6);
        const today = new Date().toISOString().split('T')[0];

        // Store new invite in local data array
        const newInvite = {
            email,
            type: userType,
            sentDate: today,
            token
        };

        this.pendingInvites.unshift(newInvite);
        
        // Refresh table rows
        const tableBody = document.getElementById('pending-invites-body');
        if (tableBody) {
            tableBody.innerHTML = this.renderTableRows();
        }

        // Trigger visual validation & notification alert
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
            <div class="glass-panel animate-fade-in" style="max-width: 450px; width: 90%; padding: 32px; text-align: center; border: 1px solid var(--border-glass);">
                <i class="ph ph-envelope-simple-open" style="font-size: 4rem; color: var(--accent-primary); margin-bottom: 16px;"></i>
                <h3 style="font-size: 1.25rem; margin-bottom: 8px; font-family: var(--font-secondary);">Invitation Sent!</h3>
                <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 0.875rem;">
                    A secure email invitation containing a unique registration token (<code style="color:var(--status-success);">${token}</code>) has been dispatched to <strong>${email}</strong>.
                </p>
                <div style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 4px; font-size: 0.75rem; text-align: left; color: var(--text-muted); border: 1px solid var(--border-glass); margin-bottom: 24px; max-height: 120px; overflow-y: auto;">
                    <strong>Subject:</strong> Invitation to Join Diginovia Ticketing System<br/>
                    <strong>Message:</strong> Hello ${name}, you have been invited to join the Diginovia Ticketing System. Click link below to activate...
                </div>
                <button class="btn-primary" style="width: 100%;" onclick="this.closest('div').parentElement.remove()">Close Dialog</button>
            </div>
        `;
        
        document.body.appendChild(alertOverlay);
        document.getElementById('invite-user-form').reset();
        this.selectUserType('Client'); // Reset fields display to Client
    },

    reinvite: function(email) {
        const invite = this.pendingInvites.find(i => i.email === email);
        if (invite) {
            invite.sentDate = new Date().toISOString().split('T')[0];
            const tableBody = document.getElementById('pending-invites-body');
            if (tableBody) {
                tableBody.innerHTML = this.renderTableRows();
            }
            alert(`Re-invited ${email}! Security token has been refreshed.`);
        }
    },

    revoke: function(email) {
        if (confirm(`Are you sure you want to revoke the invitation for ${email}?`)) {
            this.pendingInvites = this.pendingInvites.filter(i => i.email !== email);
            const tableBody = document.getElementById('pending-invites-body');
            if (tableBody) {
                tableBody.innerHTML = this.renderTableRows();
            }
        }
    }
};
