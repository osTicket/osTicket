window.SettingsView = {
    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">System Settings</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Configuration for global variables, email SMTP, and APIs.</p>
                    </div>
                    <button class="btn-primary flex items-center gap-4"><i class="ph ph-floppy-disk"></i> Save Configuration</button>
                </div>
                
                <div style="display: flex; gap: 24px; border-bottom: 1px solid var(--border-glass); margin-bottom: 24px; padding-bottom: 12px;">
                    <div style="font-weight: 500; color: var(--accent-primary); border-bottom: 2px solid var(--accent-primary); padding-bottom: 11px; margin-bottom: -13px;">Email (SMTP)</div>
                    <div style="color: var(--text-secondary); cursor: pointer;">API Integrations</div>
                    <div style="color: var(--text-secondary); cursor: pointer;">Global Variables</div>
                </div>
                
                <div style="max-width: 600px;">
                    <div class="mb-6">
                        <label class="input-label">SMTP Server Host</label>
                        <input type="text" class="input-field" value="smtp.mailgun.org">
                    </div>
                    <div class="flex gap-4 mb-6">
                        <div style="flex: 1;">
                            <label class="input-label">Port</label>
                            <input type="text" class="input-field" value="587">
                        </div>
                        <div style="flex: 1;">
                            <label class="input-label">Encryption</label>
                            <select class="input-field">
                                <option>TLS</option>
                                <option>SSL</option>
                                <option>None</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="input-label">SMTP Username</label>
                        <input type="text" class="input-field" value="postmaster@mg.diginovia.com">
                    </div>
                    <div class="mb-6">
                        <label class="input-label">SMTP Password</label>
                        <input type="password" class="input-field" value="**********">
                    </div>
                    
                    <button class="btn-primary" style="background: transparent; border: 1px solid var(--accent-primary); color: var(--accent-primary);">Test Connection</button>
                </div>
            </div>
        `;
    }
};
