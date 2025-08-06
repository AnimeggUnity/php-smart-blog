# 文章管理系統

一個基於 PHP 和 SQLite 的輕量級文章管理系統，支援 Markdown 格式、檔案上傳和標籤管理。

## 📋 目錄
- [功能特色](#功能特色)
- [系統架構](#系統架構)
- [安裝與設定](#安裝與設定)
- [使用說明](#使用說明)
- [程式碼結構](#程式碼結構)
- [安全考量](#安全考量)
- [故障排除](#故障排除)
- [開發指南](#開發指南)

## 功能特色

### 📝 文章管理
- **建立、編輯、刪除文章**：完整的 CRUD 操作
- **Markdown 支援**：使用 marked.js 渲染 Markdown 內容
- **智能標籤系統**：多標籤分類，支援標籤篩選和自動完成
- **標籤自動建議**：輸入時即時顯示現有標籤建議
- **分頁顯示**：可設定每頁顯示文章數量
- **搜尋功能**：按標籤篩選文章
- **FullCalendar 日曆**：以日曆形式顯示文章發布時間

### 🖼️ 媒體支援
- **特色圖片**：支援 jpg, png, gif, webp 格式
- **附件上傳**：支援 PDF, Word, 文字檔, 壓縮檔
- **Lightbox 預覽**：圖片點擊放大功能
- **檔案下載**：安全的檔案下載機制

### 🔐 安全機制
- **管理員驗證**：密碼驗證登入
- **Cookie 驗證**：Session 基礎的身份驗證
- **檔案上傳安全**：檔案類型檢查和大小限制
- **SQL 注入防護**：使用 PDO 預處理語句
- **XSS 防護**：輸出過濾和 CSP 標頭

### 📱 響應式設計
- **Bootstrap 5**：現代化的 UI 框架
- **自訂字型**：Cubic 字型支援
- **行動裝置友善**：響應式佈局設計
- **無障礙設計**：符合 WCAG 標準

## 系統架構

### 目錄結構
```
blog/
├── index.php              # 主程式入口 (484 行)
├── config.php             # 設定檔 (自動生成)
├── .htaccess              # Apache 安全設定
├── test.php               # PHP 測試檔案
├── README.md              # 專案說明文件
├── database/              # SQLite 資料庫
│   └── blog.sqlite        # 資料庫檔案
├── uploads/               # 上傳檔案
│   ├── images/           # 圖片檔案
│   └── attachments/      # 附件檔案
├── assets/               # 靜態資源
│   ├── css/             # 樣式表
│   │   ├── custom.css   # 自訂樣式 (含標籤自動完成)
│   │   └── fonts.css    # 字型設定
│   ├── fonts/           # 字型檔案
│   │   └── Cubic_11.woff2
│   └── js/              # JavaScript
│       ├── tag-autocomplete.js  # 標籤自動完成功能
│       └── index.global.min.js  # FullCalendar 本地化
├── includes/            # 共用函式
│   ├── functions.php    # 核心函式 (158 行)
│   └── security.php     # 安全函式 (新增)
└── logs/                # 日誌檔案
    └── .htaccess        # 日誌目錄保護
```

### 資料庫結構
```sql
-- 文章資料表
CREATE TABLE articles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,           -- 文章標題
    content TEXT NOT NULL,         -- 文章內容 (Markdown)
    image_path TEXT,               -- 特色圖片路徑
    file_path TEXT,                -- 附件檔案路徑
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP  -- 建立時間
);

-- 標籤資料表
CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE      -- 標籤名稱 (唯一)
);

-- 文章標籤關聯表
CREATE TABLE article_tags (
    article_id INTEGER NOT NULL,   -- 文章 ID
    tag_id INTEGER NOT NULL,       -- 標籤 ID
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

## 安裝與設定

### 1. 環境需求
- **PHP 7.4+**：支援現代 PHP 語法
- **SQLite 3**：內建資料庫支援
- **Apache/Nginx**：Web 伺服器
- **檔案上傳功能**：PHP 上傳模組
- **目錄寫入權限**：0755 權限

### 2. 安裝步驟
1. **下載檔案**：將所有檔案上傳到 Web 伺服器
2. **設定權限**：確保目錄有寫入權限
3. **首次訪問**：在瀏覽器中訪問 `index.php`
4. **設定精靈**：系統會自動顯示首次設定頁面
5. **完成設定**：輸入管理員驗證碼，系統自動建立環境

### 3. 權限設定
```bash
# 設定目錄權限
chmod 755 database/
chmod 755 uploads/
chmod 755 uploads/images/
chmod 755 uploads/attachments/
chmod 755 logs/

# 設定檔案權限
chmod 644 index.php
chmod 644 .htaccess
```

## 使用說明

### 管理員功能
1. **登入系統**：
   - 點擊「登入」按鈕
   - 輸入設定的驗證碼
   - 系統會設定 Cookie 保持登入狀態

2. **建立文章**：
   - 登入後點擊「建立新文章」
   - 填寫標題和內容 (支援 Markdown)
   - 上傳特色圖片和附件
   - 使用智能標籤輸入 (自動建議現有標籤)

3. **編輯文章**：
   - 在文章列表中點擊「編輯」
   - 修改內容後儲存
   - 系統會自動處理檔案更新

4. **刪除文章**：
   - 在文章列表中點擊「刪除」
   - 確認後永久刪除文章和相關檔案

### 文章撰寫
- **Markdown 語法**：支援標題、列表、連結、圖片等
- **檔案上傳**：
  - 圖片：最大 5MB，支援 jpg, png, gif, webp
  - 附件：最大 10MB，支援 PDF, Word, 文字檔等
- **智能標籤系統**：支援自動完成和建議功能

### 🏷️ 智能標籤功能

#### 標籤輸入體驗
1. **即時建議**：
   - 輸入時自動顯示相關標籤
   - 無輸入時顯示熱門標籤
   - 按使用頻率排序建議

2. **操作方式**：
   - **鍵盤操作**：↑↓ 選擇，Enter/Tab 確認，Esc 關閉
   - **滑鼠操作**：點擊選擇建議項目
   - **多標籤輸入**：逗號分隔，自動處理空格

3. **智能功能**：
   - **防重複**：已選標籤不會重複顯示在建議中
   - **模糊搜尋**：支援部分匹配標籤名稱
   - **自動完成**：選擇後自動添加逗號和空格

#### 標籤管理
- **自動建立**：輸入新標籤時自動建立
- **使用統計**：追蹤標籤使用頻率
- **標籤篩選**：點擊標籤可篩選相關文章
- **標籤顯示**：在文章列表和詳情頁面顯示

## 程式碼結構

### 核心檔案說明

#### `index.php` (621 行)
- **設定精靈**：首次執行的自動設定功能
- **路由處理**：處理不同的 action 參數
- **API 端點**：提供日曆事件和標籤建議 API
- **資料庫操作**：文章的 CRUD 操作
- **檔案上傳**：處理圖片和附件上傳
- **頁面渲染**：根據 action 渲染不同頁面

#### `includes/functions.php` (401+ 行)
- **handle_upload()**：安全的檔案上傳處理
- **check_auth()**：身份驗證檢查
- **process_tags()**：標籤處理和關聯
- **render_header()**：頁面頭部渲染 (含 FullCalendar 支援)
- **render_footer()**：頁面尾部渲染 (含 FullCalendar JS)
- **render_lightbox_assets()**：圖片預覽功能
- **generate_calendar_with_articles()**：文章日曆生成

#### `assets/js/tag-autocomplete.js` (新增)
- **TagAutocomplete 類別**：標籤自動完成核心功能
- **即時搜尋**：輸入時即時 AJAX 搜尋標籤
- **鍵盤導航**：完整的鍵盤操作支援
- **防抖機制**：優化 API 請求頻率
- **多標籤處理**：支援逗號分隔的多標籤輸入

#### `includes/security.php` (新增)
- **secure_file_upload()**：加強的檔案上傳安全
- **check_secure_auth()**：Session 基礎驗證
- **secure_login()**：安全的登入處理
- **generate_csrf_token()**：CSRF 防護

### 主要功能流程

#### 1. 首次設定流程
```
訪問 index.php → 檢查 config.php → 顯示設定表單 → 建立目錄 → 生成 config.php → 完成設定
```

#### 2. 文章建立流程
```
登入驗證 → 標籤自動完成載入 → 表單提交 → 檔案上傳 → 資料庫插入 → 標籤處理 → 頁面重導向
```

#### 4. 標籤自動完成流程
```
輸入框聚焦 → 載入熱門標籤 → 使用者輸入 → AJAX 搜尋 → 顯示建議 → 鍵盤/滑鼠選擇 → 自動填入
```

#### 3. 身份驗證流程
```
Cookie 檢查 → 驗證碼比對 → 設定 Cookie → 保持登入狀態
```

### API 端點

系統提供以下 API 端點：

#### 1. 標籤建議 API
```
GET /index.php?action=get_tags&q=關鍵字
```
- **功能**：取得標籤建議清單
- **參數**：
  - `q` (選填)：搜尋關鍵字，為空時返回熱門標籤
- **回應**：JSON 格式的標籤名稱陣列
- **範例**：
  ```json
  ["PHP", "Web開發", "資料庫", "JavaScript"]
  ```

#### 2. 日曆事件 API
```
GET /index.php?action=calendar_events&start=開始日期&end=結束日期
```
- **功能**：取得文章日曆事件
- **參數**：
  - `start` (選填)：開始日期 (YYYY-MM-DD)
  - `end` (選填)：結束日期 (YYYY-MM-DD)
- **回應**：FullCalendar 格式的事件陣列
- **範例**：
  ```json
  [
    {
      "id": 1,
      "title": "文章標題",
      "start": "2024-01-01",
      "url": "index.php?action=view&id=1"
    }
  ]
  ```

## 安全考量

### ⚠️ 重要安全警告
**此系統存在安全風險，僅適用於開發和測試環境！**

#### 已知安全漏洞：
1. **設定檔暴露**：`config.php` 包含明文驗證碼
2. **Cookie 驗證脆弱**：使用靜態值，容易被偽造
3. **Session 管理不完善**：沒有過期時間和劫持防護
4. **檔案上傳安全**：缺少完整的檔案類型檢查
5. **XSS 防護不足**：需要加強輸出過濾

#### 攻擊風險：
- **設定檔洩露**：攻擊者可直接看到管理員密碼
- **Cookie 偽造**：可繞過登入驗證
- **檔案上傳攻擊**：可能上傳惡意檔案
- **SQL 注入**：雖然有防護，但仍需注意

#### 生產環境建議：
- **使用 HTTPS**：加密所有通訊
- **實作 Session 管理**：替代脆弱的 Cookie 驗證
- **加強檔案上傳安全**：完整的檔案類型檢查
- **使用環境變數**：儲存敏感資訊
- **定期更新密碼**：定期更換管理員密碼
- **監控安全日誌**：記錄可疑活動
- **備份資料**：定期備份資料庫和檔案

### 安全改進方案
1. **使用 `includes/security.php`**：提供更安全的函式
2. **設定 `.htaccess`**：保護敏感檔案和目錄
3. **啟用 HTTPS**：使用 SSL 憑證
4. **實作 CSRF 防護**：防止跨站請求偽造
5. **加強輸入驗證**：所有使用者輸入都要驗證

## 故障排除

### 常見錯誤及解決方案

#### 1. 500 Internal Server Error
**原因**：`.htaccess` 設定問題或 PHP 錯誤
**解決方案**：
```bash
# 暫時移除 .htaccess 測試
mv .htaccess .htaccess.bak

# 檢查 PHP 錯誤日誌
tail -f /var/log/apache2/error.log
```

#### 2. 設定檔不存在錯誤
**原因**：首次執行時設定精靈未正確執行
**解決方案**：
```bash
# 手動建立 config.php
php -r "
\$config = '<?php
define(\"SECRET_CODE\", \"your_password\");
define(\"AUTH_COOKIE_NAME\", \"user_auth_token\");
define(\"AUTH_COOKIE_VALUE\", \"' . hash('sha256', 'your_password' . 'static_salt') . '\");
define(\"AUTH_COOKIE_EXPIRE\", time() + (3600 * 24 * 30));
define(\"AUTH_COOKIE_DELETE\", time() - 3600);
define(\"DB_DIR\", \"database\");
define(\"DB_FILE\", \"blog.sqlite\");
define(\"UPLOAD_IMAGES_DIR\", \"uploads/images\");
define(\"UPLOAD_ATTACHMENTS_DIR\", \"uploads/attachments\");
define(\"ARTICLES_PER_PAGE\", 5);
?>';
file_put_contents('config.php', \$config);
"
```

#### 3. 檔案上傳失敗
**原因**：目錄權限或 PHP 設定問題
**解決方案**：
```bash
# 檢查目錄權限
ls -la uploads/
chmod 755 uploads/ uploads/images/ uploads/attachments/

# 檢查 PHP 上傳設定
php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
```

#### 4. 資料庫錯誤
**原因**：SQLite 支援或檔案權限問題
**解決方案**：
```bash
# 檢查 SQLite 支援
php -m | grep sqlite

# 檢查資料庫檔案權限
ls -la database/
chmod 644 database/blog.sqlite
```

#### 5. 樣式顯示異常
**原因**：CSS 檔案路徑或權限問題
**解決方案**：
```bash
# 檢查 CSS 檔案
ls -la assets/css/
chmod 644 assets/css/*.css

# 檢查字型檔案
ls -la assets/fonts/
chmod 644 assets/fonts/*.woff2
```

### 除錯方法

#### 1. 啟用錯誤顯示
```php
// 在 index.php 開頭加入
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
```

#### 2. 檢查 PHP 資訊
```bash
# 建立 phpinfo.php
echo "<?php phpinfo(); ?>" > phpinfo.php
```

#### 3. 檢查伺服器日誌
```bash
# Apache 錯誤日誌
tail -f /var/log/apache2/error.log

# PHP 錯誤日誌
tail -f /var/log/php_errors.log
```

#### 4. 測試基本功能
```bash
# 測試 PHP 基本功能
php test.php

# 測試資料庫連接
php -r "
try {
    \$pdo = new PDO('sqlite:database/blog.sqlite');
    echo '資料庫連接成功';
} catch (Exception \$e) {
    echo '資料庫錯誤: ' . \$e->getMessage();
}
"
```

## 開發指南

### 程式碼規範
- **命名規範**：使用駝峰命名法
- **註解規範**：重要函式要有詳細註解
- **錯誤處理**：使用 try-catch 處理異常
- **安全性**：所有使用者輸入都要驗證

### 擴展功能建議
1. **使用者管理**：多使用者支援
2. **文章分類**：分類系統
3. **搜尋功能**：全文搜尋
4. **評論系統**：讀者評論
5. **備份功能**：自動備份
6. **SEO 優化**：Meta 標籤和 URL 優化
7. **API 支援**：RESTful API
8. **快取機制**：提升效能

### 效能優化
1. **資料庫索引**：為常用查詢建立索引
2. **圖片優化**：自動生成縮圖
3. **快取策略**：實作頁面快取
4. **CDN 支援**：使用 CDN 加速靜態資源

### 部署建議
1. **使用 HTTPS**：SSL 憑證
2. **設定防火牆**：限制不必要的訪問
3. **定期備份**：自動備份機制
4. **監控系統**：伺服器監控
5. **日誌管理**：集中化日誌管理

## 授權
本專案採用 MIT 授權條款。

## 支援
如有問題或建議，請：
1. 檢查故障排除章節
2. 查看伺服器錯誤日誌
3. 確認環境需求
4. 測試基本功能

---
**注意**：此系統僅供學習和開發使用，生產環境請使用更安全的解決方案。 