// js/app.js
class App {
    constructor() {
        this.appElement = document.getElementById('app');
        this.isAuthenticated = false;
        this.currentView = 'titles'; // default view
        
        // Listen to hash changes for routing
        window.addEventListener('hashchange', () => this.handleRoute());
        
        this.init();
    }
    
    init() {
        // Check if user is logged in (simulated with localStorage)
        const user = localStorage.getItem('diginovia_user');
        if (user) {
            this.isAuthenticated = true;
        }
        this.handleRoute();
    }
    
    handleRoute() {
        if (!this.isAuthenticated) {
            this.renderAuth();
            return;
        }
        
        const hash = window.location.hash.substring(1);
        this.currentView = hash || 'titles';
        this.renderLayout();
    }
    
    renderAuth() {
        this.appElement.innerHTML = '';
        if (window.AuthView) {
            const authHTML = window.AuthView.render();
            this.appElement.innerHTML = authHTML;
            window.AuthView.attachEvents(this);
        } else {
            console.error('AuthView is not loaded');
        }
    }
    
    renderLayout() {
        this.appElement.innerHTML = `
            <div class="app-container">
                <aside class="sidebar">
                    <div style="padding: 24px; border-bottom: 1px solid var(--border-glass); display: flex; align-items: center; gap: 12px;">
                        <div style="width: 32px; height: 32px; background: var(--accent-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem;">D</div>
                        <h2 style="font-size: 1.25rem; margin: 0; font-family: var(--font-secondary);">Diginovia</h2>
                    </div>
                    <nav style="padding: 16px; flex: 1; overflow-y: auto;">
                        ${this.renderNavLinks()}
                    </nav>
                </aside>
                <main class="main-content">
                    <header class="header">
                        <div class="header-title" style="font-size: 1.25rem; font-weight: 600; text-transform: capitalize;">
                            ${this.currentView.replace('-', ' ')}
                        </div>
                        <div class="header-actions flex items-center gap-4">
                            <button class="icon-btn" style="background: transparent; color: var(--text-primary); font-size: 1.5rem; border:none; cursor:pointer;"><i class="ph ph-bell"></i></button>
                            <div class="user-profile flex items-center gap-4" style="cursor: pointer;" onclick="app.logout()">
                                <div style="text-align: right;">
                                    <div style="font-size: 0.875rem; font-weight: 600;">Super Admin</div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Logout</div>
                                </div>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--bg-surface-hover); display: flex; align-items: center; justify-content: center;">
                                    <i class="ph ph-user" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                        </div>
                    </header>
                    <section class="content-area animate-fade-in" id="main-content-area">
                        <!-- Content injected here -->
                    </section>
                </main>
            </div>
        `;
        
        this.renderCurrentView();
    }
    
    renderNavLinks() {
        const links = [
            { id: 'titles', icon: 'tag', label: 'Titles' },
            { id: 'scope', icon: 'crosshair', label: 'Scope' },
            { id: 'calendar', icon: 'calendar-blank', label: 'Start & End Date' },
            { id: 'users', icon: 'users', label: 'User & Client Mgt' },
            { id: 'contracts', icon: 'handshake', label: 'Contracts' },
            { id: 'tickets', icon: 'ticket', label: 'Tickets' },
            { id: 'departments', icon: 'git-merge', label: 'Departments & Routing' },
            { id: 'sla', icon: 'shield-warning', label: 'SLA & Escalations' },
            { id: 'analytics', icon: 'chart-bar', label: 'Analytics & Reports' },
            { id: 'settings', icon: 'gear', label: 'System Settings' }
        ];
        
        return links.map(link => `
            <a href="#${link.id}" class="nav-link" style="
                display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin-bottom: 8px; border-radius: var(--radius-md); 
                color: ${this.currentView === link.id ? 'white' : 'var(--text-secondary)'};
                background: ${this.currentView === link.id ? 'var(--accent-primary)' : 'transparent'};
                transition: var(--transition);
                text-decoration: none;
            ">
                <i class="ph ph-${link.icon}" style="font-size: 1.25rem;"></i>
                <span style="font-weight: 500;">${link.label}</span>
            </a>
        `).join('');
    }
    
    renderCurrentView() {
        const contentArea = document.getElementById('main-content-area');
        if (!contentArea) return;
        
        let viewHTML = '';
        switch(this.currentView) {
            case 'titles': viewHTML = window.TitlesView ? window.TitlesView.render() : '<div style="color:var(--text-secondary);">Titles view not loaded</div>'; break;
            case 'contracts': viewHTML = window.ContractsView ? window.ContractsView.render() : '<div style="color:var(--text-secondary);">Contracts view not loaded</div>'; break;
            case 'tickets': viewHTML = window.TicketsView ? window.TicketsView.renderHistory() : '<div style="color:var(--text-secondary);">Tickets view not loaded</div>'; break;
            case 'create-ticket': viewHTML = window.TicketsView ? window.TicketsView.renderCreateForm() : '<div style="color:var(--text-secondary);">Tickets view not loaded</div>'; break;
            case 'scope': viewHTML = window.ScopeView ? window.ScopeView.render() : '<div style="color:var(--text-secondary);">Scope view not loaded</div>'; break;
            case 'calendar': viewHTML = window.CalendarView ? window.CalendarView.render() : '<div style="color:var(--text-secondary);">Calendar view not loaded</div>'; break;
            case 'users': viewHTML = window.UsersView ? window.UsersView.render() : '<div style="color:var(--text-secondary);">Users view not loaded</div>'; break;
            case 'invite-user': viewHTML = window.InviteView ? window.InviteView.render() : '<div style="color:var(--text-secondary);">Invite view not loaded</div>'; break;
            case 'departments': viewHTML = window.DepartmentsView ? window.DepartmentsView.render() : '<div style="color:var(--text-secondary);">Departments view not loaded</div>'; break;
            case 'sla': viewHTML = window.SLAView ? window.SLAView.render() : '<div style="color:var(--text-secondary);">SLA view not loaded</div>'; break;
            case 'analytics': 
                viewHTML = window.AnalyticsView ? window.AnalyticsView.render() : '<div style="color:var(--text-secondary);">Analytics view not loaded</div>'; 
                break;
            case 'settings': viewHTML = window.SettingsView ? window.SettingsView.render() : '<div style="color:var(--text-secondary);">Settings view not loaded</div>'; break;
            default: viewHTML = window.TitlesView ? window.TitlesView.render() : '<div style="color:var(--text-secondary);">View not found</div>';
        }
        
        contentArea.innerHTML = viewHTML;
        
        // Setup scripts for specific views if needed
        if (this.currentView === 'analytics' && window.AnalyticsView) {
            setTimeout(() => window.AnalyticsView.initChart(), 100);
        }
    }
    
    login(email, password) {
        // Validate specific credentials
        if (email.toLowerCase() === 'admin@diginovia.com' && password === 'password123') {
            localStorage.setItem('diginovia_user', email);
            this.isAuthenticated = true;
            window.location.hash = 'titles';
            this.handleRoute();
            return true;
        }
        return false;
    }
    
    logout() {
        localStorage.removeItem('diginovia_user');
        this.isAuthenticated = false;
        window.location.hash = '';
        this.handleRoute();
    }
}

// Initialize App
const app = new App();
