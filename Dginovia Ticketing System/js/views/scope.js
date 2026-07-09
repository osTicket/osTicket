window.ScopeView = {
    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">Project Scope & Boundaries</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Define parameters, module access, and SLA limitations.</p>
                    </div>
                    <button class="btn-primary flex items-center gap-4"><i class="ph ph-floppy-disk"></i> Save Changes</button>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div>
                        <label class="input-label">Project Name</label>
                        <input type="text" class="input-field" value="Diginovia Core Transformation">
                    </div>
                    <div>
                        <label class="input-label">Client Organization</label>
                        <select class="input-field">
                            <option>Acme Corp</option>
                            <option selected>Khalifa Computer Group</option>
                            <option>Global Tech</option>
                        </select>
                    </div>
                    
                    <div style="grid-column: span 2;">
                        <label class="input-label">Module Access Permissions</label>
                        <div style="display: flex; gap: 16px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" checked> <span>Ticketing Engine</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" checked> <span>Knowledge Base</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" checked> <span>Live Chat (AI)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox"> <span>Asset Management</span>
                            </label>
                        </div>
                    </div>
                    
                    <div style="grid-column: span 2; margin-top: 16px;">
                        <label class="input-label">SLA Boundaries Notes</label>
                        <textarea class="input-field" rows="4">Standard 9-5 support window applied. VIP clients receive 24/7 coverage. Maximum 4 hour response time for critical issues.</textarea>
                    </div>
                </div>
            </div>
        `;
    }
};
