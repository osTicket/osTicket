// js/auth.js
window.AuthView = {
    render: function() {
        return `
            <div style="height: 100vh; width: 100vw; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at top left, var(--bg-surface-hover), var(--bg-main));">
                <div class="glass-panel" style="width: 100%; max-width: 420px; padding: 40px; position: relative; overflow: hidden;">
                    <!-- Decorative blur element -->
                    <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: var(--accent-primary); filter: blur(80px); opacity: 0.3; border-radius: 50%;"></div>
                    
                    <div style="text-align: center; margin-bottom: 32px; position: relative; z-index: 1;">
                        <div style="width: 48px; height: 48px; background: var(--accent-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.5rem; margin: 0 auto 16px;">
                            D
                        </div>
                        <h1 style="font-size: 1.5rem; margin-bottom: 8px; font-family: var(--font-secondary);">Super Admin Portal</h1>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">Enter your credentials to access the system</p>
                        <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 8px; border: 1px dashed var(--border-glass); padding: 6px; border-radius: 4px; display: inline-block;">
                            Hint: <strong>admin@diginovia.com</strong> / <strong>password123</strong>
                        </p>
                    </div>
                    
                    <form id="login-form" style="position: relative; z-index: 1;" onsubmit="event.preventDefault();">
                        <div class="mb-6">
                            <label class="input-label" for="email">Email Address</label>
                            <input type="email" id="email" class="input-field" placeholder="admin@diginovia.com" required>
                        </div>
                        <div class="mb-6">
                            <div class="flex justify-between items-center" style="margin-bottom: 8px;">
                                <label class="input-label" for="password" style="margin: 0;">Password</label>
                                <a href="#" style="font-size: 0.75rem;">Forgot password?</a>
                            </div>
                            <input type="password" id="password" class="input-field" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%;">Sign In</button>
                    </form>
                </div>
            </div>
        `;
    },
    
    attachEvents: function(appInstance) {
        const form = document.getElementById('login-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                // Clear previous error
                const existingError = document.getElementById('error-message');
                if (existingError) existingError.remove();

                const success = appInstance.login(email, password);
                if (!success) {
                    const errorDiv = document.createElement('div');
                    errorDiv.id = 'error-message';
                    errorDiv.style.color = 'var(--status-danger)';
                    errorDiv.style.fontSize = '0.875rem';
                    errorDiv.style.marginTop = '12px';
                    errorDiv.style.textAlign = 'center';
                    errorDiv.innerText = 'Invalid email or password.';
                    form.appendChild(errorDiv);
                }
            });
        }
    }
};
