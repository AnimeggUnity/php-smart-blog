/**
 * Bootstrap 5 Dark Mode Toggle
 * 提供一鍵切換亮色/暗色主題功能
 */
class ThemeToggle {
    constructor() {
        this.init();
    }
    
    init() {
        // 檢查是否有儲存的主題偏好
        this.loadTheme();
        
        // 綁定切換按鈕事件
        this.bindToggleButton();
        
        // 更新 FullCalendar 主題（如果存在）
        this.updateFullCalendarTheme();
    }
    
    /**
     * 載入使用者的主題偏好
     */
    loadTheme() {
        const savedTheme = localStorage.getItem('bs-theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // 優先順序：使用者設定 > 系統偏好 > 預設亮色
        let theme;
        if (savedTheme) {
            theme = savedTheme;
        } else if (systemPrefersDark) {
            theme = 'dark';
        } else {
            theme = 'light';
        }
        
        this.setTheme(theme);
    }
    
    /**
     * 設定主題
     * @param {string} theme - 'light' 或 'dark'
     */
    setTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('bs-theme', theme);
        this.updateToggleButton(theme);
        this.updateFullCalendarTheme(theme);
    }
    
    /**
     * 切換主題
     */
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }
    
    /**
     * 綁定切換按鈕事件
     */
    bindToggleButton() {
        const toggleButton = document.getElementById('theme-toggle');
        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                this.toggleTheme();
            });
        }
    }
    
    /**
     * 更新切換按鈕的圖示和文字
     * @param {string} theme - 當前主題
     */
    updateToggleButton(theme) {
        const toggleButton = document.getElementById('theme-toggle');
        if (!toggleButton) return;
        
        const icon = toggleButton.querySelector('i');
        const text = toggleButton.querySelector('.theme-text');
        
        if (theme === 'dark') {
            if (icon) {
                icon.className = 'bi bi-sun-fill';
            }
            if (text) {
                text.textContent = '亮色模式';
            }
            toggleButton.title = '切換到亮色模式';
        } else {
            if (icon) {
                icon.className = 'bi bi-moon-fill';
            }
            if (text) {
                text.textContent = '暗色模式';
            }
            toggleButton.title = '切換到暗色模式';
        }
    }
    
    /**
     * 更新 FullCalendar 主題
     * @param {string} theme - 當前主題
     */
    updateFullCalendarTheme(theme) {
        // 如果頁面有 FullCalendar，更新其主題
        if (typeof FullCalendar !== 'undefined' && window.calendar) {
            // 重新渲染日曆以應用新主題
            window.calendar.render();
        }
    }
    
    /**
     * 監聽系統主題變化
     */
    watchSystemTheme() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addListener((e) => {
            // 只有當使用者沒有手動設定主題時才跟隨系統
            const savedTheme = localStorage.getItem('bs-theme');
            if (!savedTheme) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }
}

// 頁面載入完成後初始化主題切換功能
document.addEventListener('DOMContentLoaded', function() {
    window.themeToggle = new ThemeToggle();
    window.themeToggle.watchSystemTheme();
});

// 為其他腳本提供主題狀態檢查函式
window.getCurrentTheme = function() {
    return document.documentElement.getAttribute('data-bs-theme') || 'light';
};

window.isDarkMode = function() {
    return window.getCurrentTheme() === 'dark';
};