window.SLAView = {
    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">SLA & Escalations</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Set time-to-resolution targets and trigger alerts when tickets are overdue.</p>
                    </div>
                    <button class="btn-primary flex items-center gap-4"><i class="ph ph-plus"></i> New SLA Policy</button>
                </div>
                
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass); color: var(--text-secondary);">
                            <th style="padding: 16px 8px; font-weight: 500;">Policy Name</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Target Resolution</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Escalation Action</th>
                            <th style="padding: 16px 8px; font-weight: 500;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border-glass); transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 8px; font-weight: 500;">Critical Incident</td>
                            <td style="padding: 16px 8px; color: var(--status-danger);">4 Hours</td>
                            <td style="padding: 16px 8px; font-size: 0.875rem;">Email Manager, SMS to On-call</td>
                            <td style="padding: 16px 8px;"><span style="color: var(--status-success);">Active</span></td>
                        </tr>
                        <tr style="transition: var(--transition);" onmouseover="this.style.background='var(--bg-surface-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 8px; font-weight: 500;">Standard Request</td>
                            <td style="padding: 16px 8px;">48 Hours</td>
                            <td style="padding: 16px 8px; font-size: 0.875rem;">Re-assign to L2 Support</td>
                            <td style="padding: 16px 8px;"><span style="color: var(--status-success);">Active</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
    }
};
