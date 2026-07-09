window.UsersView = {
    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">User & Client Management</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">View, invite, edit, and suspend all system users.</p>
                    </div>
                    <a href="#invite-user" class="btn-primary flex items-center gap-4" style="text-decoration: none;"><i class="ph ph-user-plus"></i> Invite User</a>
                </div>
                
                <div class="flex gap-4 mb-6">
                    <input type="text" class="input-field" placeholder="Search users by name, email..." style="max-width: 300px;">
                    <select class="input-field" style="max-width: 150px;">
                        <option>All Roles</option>
                        <option>Admin</option>
                        <option>Agent</option>
                        <option>Client</option>
                    </select>
                </div>
                
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass); color: var(--text-secondary);">
                            <th style="padding: 16px 8px; font-weight: 500;">User</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Role</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Status</th>
                            <th style="padding: 16px 8px; font-weight: 500; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border-glass); transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 8px;">
                                <div class="flex items-center gap-4">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--accent-primary); display: flex; align-items: center; justify-content: center; font-weight: 600;">JD</div>
                                    <div>
                                        <div style="font-weight: 500;">John Doe</div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary);">john@diginovia.com</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 16px 8px;">Super Admin</td>
                            <td style="padding: 16px 8px;"><span style="color: var(--status-success); display: flex; align-items: center; gap: 6px;"><div style="width: 6px; height: 6px; border-radius: 50%; background: var(--status-success);"></div> Active</span></td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <button style="background: transparent; color: var(--text-secondary); margin-right: 12px; font-size: 1.1rem;"><i class="ph ph-pencil-simple"></i></button>
                                <button style="background: transparent; color: var(--status-warning); font-size: 1.1rem;" title="Suspend User"><i class="ph ph-prohibit"></i></button>
                            </td>
                        </tr>
                        <tr style="transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 8px;">
                                <div class="flex items-center gap-4">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-main); border: 1px solid var(--border-glass); display: flex; align-items: center; justify-content: center; font-weight: 600;">SA</div>
                                    <div>
                                        <div style="font-weight: 500;">Sarah Ahmed</div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary);">sarah@client.com</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 16px 8px;">Client VIP</td>
                            <td style="padding: 16px 8px;"><span style="color: var(--text-secondary); display: flex; align-items: center; gap: 6px;"><div style="width: 6px; height: 6px; border-radius: 50%; background: var(--text-secondary);"></div> Suspended</span></td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <button style="background: transparent; color: var(--text-secondary); margin-right: 12px; font-size: 1.1rem;"><i class="ph ph-pencil-simple"></i></button>
                                <button style="background: transparent; color: var(--status-success); font-size: 1.1rem;" title="Activate User"><i class="ph ph-check-circle"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
    }
};
