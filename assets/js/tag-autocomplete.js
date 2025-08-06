/**
 * 標籤自動完成功能
 * 為標籤輸入框提供智能提示和自動完成功能
 */
class TagAutocomplete {
    constructor(inputSelector, options = {}) {
        this.input = document.querySelector(inputSelector);
        if (!this.input) {
            console.error('TagAutocomplete: 找不到指定的輸入框');
            return;
        }
        
        this.options = {
            apiUrl: 'index.php?action=get_tags',
            debounceDelay: 300,
            maxSuggestions: 10,
            ...options
        };
        
        this.currentSuggestions = [];
        this.selectedIndex = -1;
        this.isLoading = false;
        this.debounceTimer = null;
        
        this.init();
    }
    
    init() {
        this.createDropdown();
        this.bindEvents();
    }
    
    createDropdown() {
        // 包裝輸入框
        const wrapper = document.createElement('div');
        wrapper.className = 'tag-autocomplete-container';
        this.input.parentNode.insertBefore(wrapper, this.input);
        wrapper.appendChild(this.input);
        
        // 建立下拉選單
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'tag-autocomplete-dropdown';
        wrapper.appendChild(this.dropdown);
    }
    
    bindEvents() {
        // 輸入事件
        this.input.addEventListener('input', (e) => {
            this.handleInput(e);
        });
        
        // 鍵盤事件
        this.input.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });
        
        // 失焦事件
        this.input.addEventListener('blur', (e) => {
            // 延遲隱藏，讓點擊事件能夠執行
            setTimeout(() => {
                this.hideDropdown();
            }, 150);
        });
        
        // 聚焦事件
        this.input.addEventListener('focus', (e) => {
            const currentValue = this.getCurrentTag();
            if (currentValue.length > 0) {
                this.fetchSuggestions(currentValue);
            } else {
                // 顯示熱門標籤
                this.fetchSuggestions('');
            }
        });
        
        // 全域點擊事件
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.tag-autocomplete-container')) {
                this.hideDropdown();
            }
        });
    }
    
    handleInput(e) {
        clearTimeout(this.debounceTimer);
        
        const currentTag = this.getCurrentTag();
        
        if (currentTag.length === 0) {
            this.hideDropdown();
            return;
        }
        
        this.debounceTimer = setTimeout(() => {
            this.fetchSuggestions(currentTag);
        }, this.options.debounceDelay);
    }
    
    handleKeydown(e) {
        if (!this.isDropdownVisible()) return;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.moveSelection(1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.moveSelection(-1);
                break;
            case 'Enter':
            case 'Tab':
                e.preventDefault();
                this.selectCurrentItem();
                break;
            case 'Escape':
                this.hideDropdown();
                break;
        }
    }
    
    getCurrentTag() {
        const value = this.input.value;
        const cursorPos = this.input.selectionStart;
        
        // 找到游標位置的標籤
        let start = value.lastIndexOf(',', cursorPos - 1) + 1;
        let end = value.indexOf(',', cursorPos);
        if (end === -1) end = value.length;
        
        return value.substring(start, end).trim();
    }
    
    getCurrentTagRange() {
        const value = this.input.value;
        const cursorPos = this.input.selectionStart;
        
        let start = value.lastIndexOf(',', cursorPos - 1) + 1;
        let end = value.indexOf(',', cursorPos);
        if (end === -1) end = value.length;
        
        return { start, end };
    }
    
    async fetchSuggestions(keyword) {
        this.isLoading = true;
        this.showLoading();
        
        try {
            const url = `${this.options.apiUrl}&q=${encodeURIComponent(keyword)}`;
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const suggestions = await response.json();
            this.currentSuggestions = suggestions;
            this.selectedIndex = -1;
            this.renderSuggestions();
            
        } catch (error) {
            console.error('獲取標籤建議失敗:', error);
            this.showError();
        } finally {
            this.isLoading = false;
        }
    }
    
    renderSuggestions() {
        if (this.currentSuggestions.length === 0) {
            this.showNoResults();
            return;
        }
        
        // 過濾已經選擇的標籤
        const existingTags = this.getExistingTags();
        const filteredSuggestions = this.currentSuggestions.filter(tag => 
            !existingTags.includes(tag.toLowerCase())
        );
        
        if (filteredSuggestions.length === 0) {
            this.showNoResults('所有建議的標籤都已選擇');
            return;
        }
        
        this.dropdown.innerHTML = '';
        
        filteredSuggestions.forEach((tag, index) => {
            const item = document.createElement('div');
            item.className = 'tag-autocomplete-item';
            item.textContent = tag;
            item.dataset.index = index;
            
            item.addEventListener('click', () => {
                this.selectTag(tag);
            });
            
            this.dropdown.appendChild(item);
        });
        
        this.showDropdown();
    }
    
    getExistingTags() {
        const value = this.input.value;
        return value.split(',')
                   .map(tag => tag.trim().toLowerCase())
                   .filter(tag => tag.length > 0);
    }
    
    moveSelection(direction) {
        const items = this.dropdown.querySelectorAll('.tag-autocomplete-item');
        if (items.length === 0) return;
        
        // 移除目前選擇
        if (this.selectedIndex >= 0) {
            items[this.selectedIndex].classList.remove('selected');
        }
        
        // 計算新位置
        this.selectedIndex += direction;
        if (this.selectedIndex < 0) {
            this.selectedIndex = items.length - 1;
        } else if (this.selectedIndex >= items.length) {
            this.selectedIndex = 0;
        }
        
        // 添加新選擇
        items[this.selectedIndex].classList.add('selected');
        items[this.selectedIndex].scrollIntoView({ block: 'nearest' });
    }
    
    selectCurrentItem() {
        const items = this.dropdown.querySelectorAll('.tag-autocomplete-item');
        if (this.selectedIndex >= 0 && this.selectedIndex < items.length) {
            const selectedTag = items[this.selectedIndex].textContent;
            this.selectTag(selectedTag);
        }
    }
    
    selectTag(tag) {
        const range = this.getCurrentTagRange();
        const value = this.input.value;
        
        // 替換當前標籤
        let newValue = value.substring(0, range.start) + tag + value.substring(range.end);
        
        // 確保標籤後有逗號和空格（如果不是最後一個標籤）
        if (range.end < value.length) {
            if (!newValue.substring(range.start + tag.length).startsWith(',')) {
                newValue = value.substring(0, range.start) + tag + ', ' + value.substring(range.end);
            }
        } else {
            // 是最後一個標籤，添加逗號和空格為下一個標籤做準備
            if (!newValue.endsWith(', ')) {
                newValue += ', ';
            }
        }
        
        this.input.value = newValue;
        
        // 設定游標位置
        const newCursorPos = range.start + tag.length + (range.end < value.length ? 2 : 2);
        this.input.setSelectionRange(newCursorPos, newCursorPos);
        
        this.hideDropdown();
        this.input.focus();
    }
    
    showLoading() {
        this.dropdown.innerHTML = '<div class="tag-autocomplete-loading">載入中...</div>';
        this.showDropdown();
    }
    
    showError() {
        this.dropdown.innerHTML = '<div class="tag-autocomplete-no-results">載入失敗，請稍後再試</div>';
        this.showDropdown();
    }
    
    showNoResults(message = '沒有找到相符的標籤') {
        this.dropdown.innerHTML = `<div class="tag-autocomplete-no-results">${message}</div>`;
        this.showDropdown();
    }
    
    showDropdown() {
        this.dropdown.style.display = 'block';
    }
    
    hideDropdown() {
        this.dropdown.style.display = 'none';
        this.selectedIndex = -1;
    }
    
    isDropdownVisible() {
        return this.dropdown.style.display === 'block';
    }
}

// 全域初始化函式
window.initTagAutocomplete = function(selector, options) {
    return new TagAutocomplete(selector, options);
};