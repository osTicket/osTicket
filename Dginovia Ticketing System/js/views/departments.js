window.DepartmentsView = {
    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">Departments & Routing</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Create teams and define rules for how new tickets are routed.</p>
                    </div>
                    <button class="btn-primary flex items-center gap-4"><i class="ph ph-git-branch"></i> New Routing Rule</button>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px;">
                        <div class="flex justify-between items-center mb-4">
                            <h3 style="font-size: 1rem; font-weight: 500;"><i class="ph ph-users-three" style="margin-right: 8px;"></i>Development Team</h3>
                            <button style="background: transparent; color: var(--text-secondary);"><i class="ph ph-dots-three"></i></button>
                        </div>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 12px;">Handles core system bugs and feature requests.</p>
                        <div style="font-size: 0.875rem;">
                            <strong>Routing Rule:</strong> IF category = "Bug" AND severity = "High" THEN route to Development.
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px;">
                        <div class="flex justify-between items-center mb-4">
                            <h3 style="font-size: 1rem; font-weight: 500;"><i class="ph ph-headset" style="margin-right: 8px;"></i>Support Team (L1)</h3>
                            <button style="background: transparent; color: var(--text-secondary);"><i class="ph ph-dots-three"></i></button>
                        </div>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 12px;">First point of contact for all incoming queries.</p>
                        <div style="font-size: 0.875rem;">
                            <strong>Routing Rule:</strong> Default routing for all uncategorized tickets.
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
};
