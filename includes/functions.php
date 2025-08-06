<?php

/**
 * 處理檔案上傳
 * @param string $file_key $_FILES 陣列中的鍵名
 * @param string $upload_dir 上傳目標目錄
 * @return string|null 成功上傳後的檔案路徑，失敗則為 null
 */
function handle_upload($file_key, $upload_dir) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        // 確保上傳目錄存在且可寫入
        if (!is_dir($upload_dir)) {
            // 嘗試建立目錄，如果失敗則報錯
            if (!mkdir($upload_dir, 0755, true)) {
                error_log("錯誤：上傳目錄 '{$upload_dir}' 無法建立。");
                return null;
            }
        }
        
        if (!is_writable($upload_dir)) {
            error_log("錯誤：上傳目錄 '{$upload_dir}' 不可寫入。");
            return null;
        }

        $original_file_name = $_FILES[$file_key]['name'];
        $tmp_name = $_FILES[$file_key]['tmp_name'];
        $file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
        $new_file_name = microtime(true) . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
        $destination = $upload_dir . '/' . $new_file_name;
        if (move_uploaded_file($tmp_name, $destination)) {
            return $destination;
        }
    }
    return null;
}

/**
 * 檢查使用者是否已登入
 */
function check_auth() {
    if (!isset($_COOKIE[AUTH_COOKIE_NAME]) || $_COOKIE[AUTH_COOKIE_NAME] !== AUTH_COOKIE_VALUE) {
        header("Location: index.php?action=login");
        exit();
    }
}

/**
 * 處理文章的標籤儲存與更新
 * @param PDO $conn 資料庫連接物件
 * @param int $article_id 文章 ID
 * @param string $tags_string 逗號分隔的標籤字串
 */
function process_tags($conn, $article_id, $tags_string) {
    error_log("process_tags: Starting for article ID: " . $article_id . ", tags: " . $tags_string);
    // 1. 清理舊的標籤關聯 (用於更新文章)
    $delete_old_tags_sql = "DELETE FROM article_tags WHERE article_id = :article_id";
    $stmt = $conn->prepare($delete_old_tags_sql);
    $stmt->execute([':article_id' => $article_id]);
    error_log("process_tags: Old tags deleted for article ID: " . $article_id);

    // 2. 解析標籤字串
    $tags = array_filter(array_map('trim', explode(',', $tags_string))); // 分割並清理空白
    error_log("process_tags: Parsed tags: " . implode(', ', $tags));

    if (empty($tags)) {
        error_log("process_tags: No tags to process.");
        return; // 沒有標籤，直接返回
    }

    // 3. 處理每個標籤
    foreach ($tags as $tag_name) {
        error_log("process_tags: Processing tag: " . $tag_name);
        // 檢查標籤是否已存在
        $tag_id = null;
        $check_tag_sql = "SELECT id FROM tags WHERE name = :name";
        $stmt = $conn->prepare($check_tag_sql);
        $stmt->execute([':name' => $tag_name]);
        $existing_tag = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_tag) {
            $tag_id = $existing_tag['id'];
            error_log("process_tags: Tag '" . $tag_name . "' already exists with ID: " . $tag_id);
        } else {
            // 標籤不存在，插入新標籤
            $insert_tag_sql = "INSERT INTO tags (name) VALUES (:name)";
            $stmt = $conn->prepare($insert_tag_sql);
            $stmt->execute([':name' => $tag_name]);
            $tag_id = $conn->lastInsertId(); // 獲取新插入標籤的 ID
            error_log("process_tags: Tag '" . $tag_name . "' inserted with ID: " . $tag_id);
        }

        // 建立文章與標籤的關聯
        if ($tag_id) {
            $insert_article_tag_sql = "INSERT INTO article_tags (article_id, tag_id) VALUES (:article_id, :tag_id)";
            $stmt = $conn->prepare($insert_article_tag_sql);
            // 使用 INSERT OR IGNORE 防止重複插入 (雖然前面刪除了，但多一層保護)
            $stmt->execute([':article_id' => $article_id, ':tag_id' => $tag_id]);
            error_log("process_tags: Article " . $article_id . " linked to tag " . $tag_id);
        }
    }
}

/**
 * 渲染頁面 HTML 頭部和容器開頭
 * @param string $action 當前動作，用於設定頁面標題和條件載入資產
 */
function render_header($action) {
    $page_title = "文章系統";
    switch($action) {
        case 'list': $page_title = "文章列表"; break;
        case 'view': $page_title = "檢視文章"; break;
        case 'create': $page_title = "建立新文章"; break;
        case 'edit': $page_title = "編輯文章"; break;
        case 'login': $page_title = "發文認證"; break;
    }
    ?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- 引入 Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 引入 Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- 引入 FullCalendar CSS -->
    <?php if ($action === 'list'): ?>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.css" rel="stylesheet">
    <style>
        .fc-theme-bootstrap5 .article-event {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        .fc-theme-bootstrap5 .article-event:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .fc-event-title {
            font-size: 0.85em;
            font-weight: 500;
        }
        .fc-toolbar {
            margin-bottom: 1rem;
        }
        .fc-button-group .fc-button {
            font-size: 0.875rem;
        }
        .fc-list-event-title a {
            color: #0d6efd;
            text-decoration: none;
        }
        .fc-list-event-title a:hover {
            text-decoration: underline;
        }
    </style>
    <?php endif; ?>
    <!-- 引入自訂字型和樣式 (路徑已更新) -->
    <link rel="stylesheet" href="assets/css/fonts.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <?php if ($action === 'view') render_lightbox_assets(); // 條件載入 Lightbox 資產 ?>
</head>
<body>
    <div class="container mt-5">
    <?php
}

/**
 * 渲染頁面 HTML 尾部和容器結尾
 */
function render_footer() {
    global $action;
    ?>
    </div>
    <!-- 引入 Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- 內嵌主題切換功能 -->
    <script>
        // Bootstrap 5 Dark Mode Toggle - 內嵌版本
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化主題
            function loadTheme() {
                const savedTheme = localStorage.getItem('bs-theme');
                const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                let theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
                setTheme(theme);
            }
            
            // 設定主題
            function setTheme(theme) {
                document.documentElement.setAttribute('data-bs-theme', theme);
                localStorage.setItem('bs-theme', theme);
                updateToggleButton(theme);
                
                // 重新渲染 FullCalendar (如果存在)
                if (typeof window.calendar !== 'undefined' && window.calendar) {
                    setTimeout(() => window.calendar.render(), 100);
                }
            }
            
            // 切換主題
            function toggleTheme() {
                const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
            }
            
            // 更新切換按鈕
            function updateToggleButton(theme) {
                const toggleButtons = document.querySelectorAll('#theme-toggle');
                toggleButtons.forEach(button => {
                    if (!button) return;
                    const icon = button.querySelector('i');
                    const text = button.querySelector('.theme-text');
                    
                    if (theme === 'dark') {
                        if (icon) icon.className = 'bi bi-sun-fill';
                        if (text) text.textContent = '亮色模式';
                        button.title = '切換到亮色模式';
                    } else {
                        if (icon) icon.className = 'bi bi-moon-fill';
                        if (text) text.textContent = '暗色模式';
                        button.title = '切換到暗色模式';
                    }
                });
            }
            
            // 綁定所有切換按鈕
            function bindToggleButtons() {
                const toggleButtons = document.querySelectorAll('#theme-toggle');
                toggleButtons.forEach(button => {
                    button.addEventListener('click', toggleTheme);
                });
            }
            
            // 初始化
            loadTheme();
            bindToggleButtons();
        });
    </script>
    <!-- 條件載入 FullCalendar JS -->
    <?php if ($action === 'list'): ?>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>
    <script src="assets/js/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            // 檢查當前主題
            function getCurrentTheme() {
                return document.documentElement.getAttribute('data-bs-theme') || 'light';
            }
            
            // 根據主題設定日曆樣式
            function getCalendarConfig() {
                const isDark = getCurrentTheme() === 'dark';
                
                return {
                    themeSystem: 'bootstrap5',
                    initialView: 'dayGridMonth',
                    locale: 'zh-tw',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,listMonth'
                    },
                    height: 'auto',
                    events: {
                        url: 'index.php?action=calendar_events',
                        failure: function() {
                            console.error('無法載入日曆事件');
                        }
                    },
                    eventClick: function(info) {
                        if (info.event.url) {
                            window.location.href = info.event.url;
                            info.jsEvent.preventDefault();
                        }
                    },
                    eventDidMount: function(info) {
                        info.el.setAttribute('title', info.event.title);
                        // Dark Mode 下調整事件顏色
                        if (isDark) {
                            info.el.style.backgroundColor = '#0d6efd';
                            info.el.style.borderColor = '#0d6efd';
                        }
                    },
                    dayMaxEvents: 3,
                    moreLinkText: '更多',
                    noEventsText: '本月沒有文章',
                    buttonText: {
                        today: '今天',
                        month: '月',
                        list: '列表'
                    }
                };
            }
            
            const calendar = new FullCalendar.Calendar(calendarEl, getCalendarConfig());
            calendar.render();
            
            // 儲存日曆實例供主題切換使用
            window.calendar = calendar;
        });
    </script>
    <?php endif; ?>
</body>
</html>
    <?php
}

/**
 * 渲染 Lightbox 的 CSS 和 JavaScript
 */
function render_lightbox_assets() {
    ?>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .modal-lightbox { display: none; position: fixed; z-index: 1055; padding-top: 100px; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.9); }
        .modal-lightbox-content { margin: auto; display: block; width: 80%; max-width: 800px; animation-name: zoom; animation-duration: 0.6s; }
        @keyframes zoom { from {transform:scale(0)} to {transform:scale(1)} }
        .close-lightbox { position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; transition: 0.3s; }
        .close-lightbox:hover, .close-lightbox:focus { color: #bbb; text-decoration: none; cursor: pointer; }
        .thumbnail-link img { cursor: pointer; }
    </style>
    <?php
}

/**
 * 生成帶有文章標題的日曆 HTML
 * @param PDO $conn 資料庫連接物件
 * @param int $year 年份
 * @param int $month 月份
 * @return string 日曆 HTML
 */
function generate_calendar_with_articles($conn, $year, $month) {
    // 獲取該月份的文章
    $sql = "SELECT id, title, DATE(created_at) as publish_date FROM articles 
            WHERE strftime('%Y', created_at) = :year AND strftime('%m', created_at) = :month
            ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':year' => $year, ':month' => sprintf('%02d', $month)]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 按日期分組文章
    $articles_by_date = [];
    foreach ($articles as $article) {
        $day = date('j', strtotime($article['publish_date']));
        if (!isset($articles_by_date[$day])) {
            $articles_by_date[$day] = [];
        }
        $articles_by_date[$day][] = $article;
    }
    
    // 生成日曆
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day);
    $start_day = date('w', $first_day); // 0=Sunday, 1=Monday, ...
    $month_name = date('Y年n月', $first_day);
    
    $calendar = '<div class="calendar-widget mb-4">';
    // 保持現有的URL參數
    $existing_params = '';
    if (isset($_GET['tag'])) $existing_params .= '&tag=' . urlencode($_GET['tag']);
    if (isset($_GET['page'])) $existing_params .= '&page=' . intval($_GET['page']);
    
    $calendar .= '<div class="d-flex justify-content-between align-items-center mb-3">';
    $calendar .= '<a href="?action=list&cal_year=' . ($month == 1 ? $year - 1 : $year) . '&cal_month=' . ($month == 1 ? 12 : $month - 1) . $existing_params . '" class="btn btn-outline-secondary btn-sm">&lt;</a>';
    $calendar .= '<h5 class="mb-0">' . $month_name . '</h5>';
    $calendar .= '<a href="?action=list&cal_year=' . ($month == 12 ? $year + 1 : $year) . '&cal_month=' . ($month == 12 ? 1 : $month + 1) . $existing_params . '" class="btn btn-outline-secondary btn-sm">&gt;</a>';
    $calendar .= '</div>';
    
    $calendar .= '<div class="calendar-grid">';
    $calendar .= '<div class="calendar-header">';
    $days = ['日', '一', '二', '三', '四', '五', '六'];
    foreach ($days as $day) {
        $calendar .= '<div class="calendar-day-header">' . $day . '</div>';
    }
    $calendar .= '</div>';
    
    $calendar .= '<div class="calendar-body">';
    
    // 填充第一週前的空白日期
    for ($i = 0; $i < $start_day; $i++) {
        $calendar .= '<div class="calendar-day empty"></div>';
    }
    
    // 填充月份中的日期
    for ($day = 1; $day <= $days_in_month; $day++) {
        $has_articles = isset($articles_by_date[$day]);
        $calendar .= '<div class="calendar-day' . ($has_articles ? ' has-articles' : '') . '">';
        $calendar .= '<span class="day-number">' . $day . '</span>';
        
        if ($has_articles) {
            $calendar .= '<div class="articles-list">';
            foreach ($articles_by_date[$day] as $article) {
                $title = mb_strlen($article['title']) > 8 ? mb_substr($article['title'], 0, 8) . '...' : $article['title'];
                $calendar .= '<a href="?action=view&id=' . $article['id'] . '" class="article-link" title="' . htmlspecialchars($article['title']) . '">' . htmlspecialchars($title) . '</a>';
            }
            $calendar .= '</div>';
        }
        
        $calendar .= '</div>';
    }
    
    $calendar .= '</div></div></div>';
    
    return $calendar;
}

/**
 * 渲染日曆的 CSS 樣式
 */
function render_calendar_styles() {
    ?>
    <style>
        .calendar-widget {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #dee2e6;
        }
        .calendar-grid {
            display: flex;
            flex-direction: column;
        }
        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            margin-bottom: 5px;
        }
        .calendar-day-header {
            background: #6c757d;
            color: white;
            text-align: center;
            padding: 8px 4px;
            font-weight: bold;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .calendar-body {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        .calendar-day {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 4px;
            min-height: 60px;
            position: relative;
        }
        .calendar-day.empty {
            background: #f8f9fa;
            border-color: #e9ecef;
        }
        .calendar-day.has-articles {
            background: #e3f2fd;
            border-color: #2196f3;
        }
        .day-number {
            font-weight: bold;
            font-size: 0.9em;
            color: #495057;
        }
        .articles-list {
            margin-top: 2px;
        }
        .article-link {
            display: block;
            font-size: 0.75em;
            color: #0066cc;
            text-decoration: none;
            margin-bottom: 1px;
            line-height: 1.2;
        }
        .article-link:hover {
            color: #004499;
            text-decoration: underline;
        }
    </style>
    <?php
}
