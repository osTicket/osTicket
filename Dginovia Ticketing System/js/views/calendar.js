window.CalendarView = {
    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">Timeline & Deadlines</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Track contract periods and project sprint cycles.</p>
                    </div>
                    <div class="flex gap-4">
                        <button class="btn-primary" style="background: transparent; border: 1px solid var(--border-glass);"><i class="ph ph-caret-left"></i></button>
                        <button class="btn-primary" style="background: transparent; border: 1px solid var(--border-glass);">Today</button>
                        <button class="btn-primary" style="background: transparent; border: 1px solid var(--border-glass);"><i class="ph ph-caret-right"></i></button>
                    </div>
                </div>
                
                <div style="background: rgba(0,0,0,0.2); border-radius: var(--radius-md); padding: 16px; min-height: 400px; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border-glass);">
                    <div style="text-align: center; color: var(--text-muted);">
                        <i class="ph ph-calendar-blank" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.5;"></i>
                        <p>Interactive calendar visualization renders here</p>
                        <p style="font-size: 0.75rem; margin-top: 8px;">Displays global deadlines and sprints</p>
                    </div>
                </div>
            </div>
        `;
    }
};
