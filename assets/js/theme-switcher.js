/**
 * AmazonGest Pro - Theme Switcher
 * Sistema de troca de temas com persistência
 */

class ThemeSwitcher {
    constructor() {
        this.themes = ['dark', 'light', 'purple'];
        this.currentTheme = this.loadTheme();
        this.init();
    }

    init() {
        // Aplicar tema carregado
        this.applyTheme(this.currentTheme);

        // Criar seletor de temas se não existir
        this.createThemeSelector();

        // Adicionar listener de teclado
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                this.showThemeMenu();
            }
        });

        // Sincronizar seletor com tema atual
        this.updateSelectorState();
    }

    loadTheme() {
        // Tentar pegar do localStorage
        const saved = localStorage.getItem('amazongest-theme');
        if (saved && this.themes.includes(saved)) {
            return saved;
        }

        // Verificar preferência do sistema
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
            return 'light';
        }

        // Padrão é dark
        return 'dark';
    }

    saveTheme(theme) {
        localStorage.setItem('amazongest-theme', theme);
    }

    applyTheme(theme, animate = true) {
        if (!this.themes.includes(theme)) return;

        const body = document.body;
        const oldTheme = this.currentTheme;

        // Remover classes antigas
        body.classList.remove(`theme-${oldTheme}`);

        // Adicionar animação
        if (animate && oldTheme !== theme) {
            body.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            setTimeout(() => {
                body.style.transition = '';
            }, 300);
        }

        // Aplicar nova classe
        body.classList.add(`theme-${theme}`);
        this.currentTheme = theme;

        // Salvar preferência
        this.saveTheme(theme);

        // Disparar evento
        this.dispatchThemeChange(theme);
    }

    createThemeSelector() {
        // Verificar se já existe
        if (document.querySelector('.theme-selector')) return;

        // Criar HTML do seletor
        const selectorHTML = `
            <div class="theme-selector" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
                <button class="theme-btn" id="themeToggle" title="Alterar Tema">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                </button>

                <div class="theme-menu" id="themeMenu" style="display: none;">
                    <div class="theme-menu-header">
                        <span>Escolha o Tema</span>
                    </div>
                    <div class="theme-options">
                        <button class="theme-option" data-theme="dark">
                            <span class="theme-icon">🌙</span>
                            <span class="theme-label">Dark Professional</span>
                        </button>
                        <button class="theme-option" data-theme="light">
                            <span class="theme-icon">☀️</span>
                            <span class="theme-label">Light Mode</span>
                        </button>
                        <button class="theme-option" data-theme="purple">
                            <span class="theme-icon">💜</span>
                            <span class="theme-label">Purple Midnight</span>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Adicionar ao body
        document.body.insertAdjacentHTML('beforeend', selectorHTML);

        // Adicionar listeners
        this.setupSelectorListeners();

        // Adicionar estilos
        this.addSelectorStyles();
    }

    setupSelectorListeners() {
        const toggle = document.getElementById('themeToggle');
        const menu = document.getElementById('themeMenu');
        const options = menu.querySelectorAll('.theme-option');

        // Toggle menu
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        });

        // Fechar menu ao clicar fora
        document.addEventListener('click', () => {
            menu.style.display = 'none';
        });

        menu.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Trocar tema
        options.forEach(option => {
            option.addEventListener('click', () => {
                const theme = option.dataset.theme;
                this.applyTheme(theme);
                menu.style.display = 'none';

                // Feedback visual
                this.showThemeToast(theme);
            });
        });
    }

    addSelectorStyles() {
        const styles = `
            <style>
                .theme-btn {
                    width: 48px;
                    height: 48px;
                    background: var(--bg-tertiary);
                    border: 2px solid var(--border-color);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    color: var(--text-primary);
                }

                .theme-btn:hover {
                    transform: scale(1.1);
                    border-color: var(--accent-primary);
                    box-shadow: 0 0 20px var(--neon-glow);
                }

                .theme-menu {
                    position: absolute;
                    top: 60px;
                    right: 0;
                    background: var(--bg-card);
                    border: 1px solid var(--border-color);
                    border-radius: 1rem;
                    box-shadow: var(--shadow-lg);
                    min-width: 250px;
                    overflow: hidden;
                    animation: menuSlideIn 0.3s ease;
                }

                @keyframes menuSlideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .theme-menu-header {
                    padding: 1rem;
                    background: var(--glass-bg);
                    border-bottom: 1px solid var(--border-light);
                    font-weight: 600;
                    color: var(--text-primary);
                }

                .theme-option {
                    width: 100%;
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    padding: 1rem;
                    background: transparent;
                    border: none;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    gap: 1rem;
                }

                .theme-option:hover {
                    background: var(--glass-bg);
                }

                .theme-option.active {
                    background: rgba(0, 212, 255, 0.1);
                    color: var(--accent-primary);
                }

                .theme-icon {
                    font-size: 1.5rem;
                }

                .theme-label {
                    color: var(--text-primary);
                    font-weight: 500;
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    showThemeMenu() {
        const menu = document.getElementById('themeMenu');
        if (menu) {
            menu.style.display = 'block';
        }
    }

    showThemeToast(theme) {
        const themeNames = {
            dark: 'Dark Professional',
            light: 'Light Mode',
            purple: 'Purple Midnight'
        };

        const toast = document.createElement('div');
        toast.className = 'toast success';
        toast.innerHTML = `
            <span>Tema alterado para</span>
            <strong style="display: inline-block; margin-left: 0.5rem;">${themeNames[theme]}</strong>
        `;

        // Adicionar ao container ou criar
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        container.appendChild(toast);

        // Auto remover em 3 segundos
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    updateSelectorState() {
        const options = document.querySelectorAll('.theme-option');
        options.forEach(option => {
            if (option.dataset.theme === this.currentTheme) {
                option.classList.add('active');
            } else {
                option.classList.remove('active');
            }
        });
    }

    dispatchThemeChange(theme) {
        const event = new CustomEvent('themeChange', {
            detail: { theme }
        });
        window.dispatchEvent(event);

        // Atualizar seletor
        this.updateSelectorState();
    }

    // Método público para trocar tema via código
    setTheme(theme) {
        this.applyTheme(theme);
    }

    // Método para obter tema atual
    getTheme() {
        return this.currentTheme;
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.themeSwitcher = new ThemeSwitcher();
});

// Expose globalmente
window.ThemeSwitcher = ThemeSwitcher;