window.AnalyticsView = {
    render: function() {
        return `
            <div class="glass-panel" style="padding: 24px;">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 style="font-size: 1.25rem; font-family: var(--font-secondary);">Analytics & Reports</h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Ticket volume, resolution times, and agent performance.</p>
                    </div>
                    <select class="input-field" style="max-width: 150px;">
                        <option>Last 7 Days</option>
                        <option>Last 30 Days</option>
                        <option>This Year</option>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px;">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                        <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 8px;">Total Tickets</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--accent-primary);">1,248</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                        <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 8px;">Avg Resolution Time</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--status-success);">4.2 hrs</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                        <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 8px;">SLA Breaches</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--status-danger);">12</div>
                    </div>
                </div>
                
                <div style="height: 300px; width: 100%;">
                    <canvas id="ticketVolumeChart"></canvas>
                </div>
            </div>
        `;
    },
    
    initChart: function() {
        const ctx = document.getElementById('ticketVolumeChart');
        if (!ctx) return;
        
        // Use Chart.js (loaded via CDN in index.html)
        if (typeof Chart !== 'undefined') {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Tickets Created',
                        data: [65, 59, 80, 81, 56, 55, 40],
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Tickets Resolved',
                        data: [45, 60, 70, 90, 60, 40, 35],
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: '#FFFFFF' }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#9CA3AF' }
                        },
                        y: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#9CA3AF' }
                        }
                    }
                }
            });
        }
    }
};
