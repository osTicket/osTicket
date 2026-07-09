window.TitlesView = {
    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">System Titles & Labels</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Manage all system-wide categories, roles, and status labels.</p>
                    </div>
                    <button class="btn-primary flex items-center gap-4"><i class="ph ph-plus"></i> Add New Label</button>
                </div>
                
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass); color: var(--text-secondary);">
                            <th style="padding: 16px 8px; font-weight: 500;">Title Name</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Category</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Associated Roles</th>
                            <th style="padding: 16px 8px; font-weight: 500; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border-glass); transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 8px; font-weight: 500;">L1 Support Agent</td>
                            <td style="padding: 16px 8px;"><span style="background: rgba(59,130,246,0.1); color: var(--accent-primary); padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">Support</span></td>
                            <td style="padding: 16px 8px; color: var(--text-secondary);">Agent, Junior</td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <button style="background: transparent; color: var(--text-secondary); margin-right: 12px; font-size: 1.1rem;"><i class="ph ph-pencil-simple"></i></button>
                                <button style="background: transparent; color: var(--status-danger); font-size: 1.1rem;"><i class="ph ph-trash"></i></button>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-glass); transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 8px; font-weight: 500;">System Administrator</td>
                            <td style="padding: 16px 8px;"><span style="background: rgba(16,185,129,0.1); color: var(--status-success); padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">Admin</span></td>
                            <td style="padding: 16px 8px; color: var(--text-secondary);">Super Admin, Configurator</td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <button style="background: transparent; color: var(--text-secondary); margin-right: 12px; font-size: 1.1rem;"><i class="ph ph-pencil-simple"></i></button>
                                <button style="background: transparent; color: var(--status-danger); font-size: 1.1rem;"><i class="ph ph-trash"></i></button>
                            </td>
                        </tr>
                        <tr style="transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 8px; font-weight: 500;">Client VIP</td>
                            <td style="padding: 16px 8px;"><span style="background: rgba(245,158,11,0.1); color: var(--status-warning); padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">Client</span></td>
                            <td style="padding: 16px 8px; color: var(--text-secondary);">External User, Priority</td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <button style="background: transparent; color: var(--text-secondary); margin-right: 12px; font-size: 1.1rem;"><i class="ph ph-pencil-simple"></i></button>
                                <button style="background: transparent; color: var(--status-danger); font-size: 1.1rem;"><i class="ph ph-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
    }
};
