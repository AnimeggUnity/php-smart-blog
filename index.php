<?php
// --- 強化錯誤回報機制 ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// --- 核心設定 ---
define('CONFIG_FILE', 'config.php');
define('DB_DIR_NAME', 'database');
define('UPLOADS_DIR_NAME', 'uploads');
define('IMAGES_DIR_NAME', 'images');
define('ATTACHMENTS_DIR_NAME', 'attachments');
define('ASSETS_DIR_NAME', 'assets');
define('CSS_DIR_NAME', 'css');
define('FONTS_DIR_NAME', 'fonts');
define('JS_DIR_NAME', 'js');
define('INCLUDES_DIR_NAME', 'includes');

// =================================================================
// 首次執行設定精靈 (Setup Wizard)
// =================================================================
if (!file_exists(CONFIG_FILE)) {

    // --- 處理設定表單的提交 ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_submitted'])) {
        $secret_code = $_POST['secret_code'] ?? '';
        if (empty($secret_code)) {
            die("錯誤：驗證碼不能為空。請返回重試。");
        }

        // --- 建立所有必要的資料夾 ---
        $db_dir = __DIR__ . DIRECTORY_SEPARATOR . DB_DIR_NAME;
        $uploads_dir = __DIR__ . DIRECTORY_SEPARATOR . UPLOADS_DIR_NAME;
        $images_dir = $uploads_dir . DIRECTORY_SEPARATOR . IMAGES_DIR_NAME;
        $attachments_dir = $uploads_dir . DIRECTORY_SEPARATOR . ATTACHMENTS_DIR_NAME;
        $assets_dir = __DIR__ . DIRECTORY_SEPARATOR . ASSETS_DIR_NAME;
        $css_dir = $assets_dir . DIRECTORY_SEPARATOR . CSS_DIR_NAME;
        $fonts_dir = $assets_dir . DIRECTORY_SEPARATOR . FONTS_DIR_NAME;
        $js_dir = $assets_dir . DIRECTORY_SEPARATOR . JS_DIR_NAME;
        $includes_dir = __DIR__ . DIRECTORY_SEPARATOR . INCLUDES_DIR_NAME;

        try {
            if (!is_dir($db_dir)) mkdir($db_dir, 0755, true);
            if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);
            if (!is_dir($images_dir)) mkdir($images_dir, 0755, true);
            if (!is_dir($attachments_dir)) mkdir($attachments_dir, 0755, true);
            if (!is_dir($assets_dir)) mkdir($assets_dir, 0755, true);
            if (!is_dir($css_dir)) mkdir($css_dir, 0755, true);
            if (!is_dir($fonts_dir)) mkdir($fonts_dir, 0755, true);
            if (!is_dir($js_dir)) mkdir($js_dir, 0755, true);
            if (!is_dir($includes_dir)) mkdir($includes_dir, 0755, true);
        } catch (Exception $e) {
            die("致命錯誤：無法建立必要的資料夾。請檢查伺服器的寫入權限。詳細資訊: " . $e->getMessage());
        }

        // --- 產生 config.php 的內容 ---
        $config_content = "<?php\n";
        $config_content .= "// --- 由 Gemini 自動產生的設定檔 ---\n\n";
        $config_content .= "// 1. 安全性設定\n";
        $config_content .= "define('SECRET_CODE', '" . htmlspecialchars($secret_code) . "'); // 登入後台的驗證碼\n";
        $config_content .= "define('AUTH_COOKIE_NAME', 'user_auth_token'); // 用於身份驗證的 Cookie 名稱\n";
        $config_content .= "define('AUTH_COOKIE_VALUE', '" . hash('sha256', $secret_code . 'static_salt') . "'); // 用於身份驗證的 Cookie 內容\n";
        $config_content .= "define('AUTH_COOKIE_EXPIRE', time() + (3600 * 24 * 30)); // Cookie 有效期 (30天)\n";
        $config_content .= "define('AUTH_COOKIE_DELETE', time() - 3600); // 用於刪除 Cookie 的時間戳\n\n";
        $config_content .= "// 2. 資料庫設定\n";
        $config_content .= "define('DB_DIR', '" . DB_DIR_NAME . "'); // 資料庫資料夾的相對路徑\n";
        $config_content .= "define('DB_FILE', 'blog.sqlite'); // 資料庫檔案名稱\n\n";
        $config_content .= "// 3. 上傳路徑設定\n";
        $config_content .= "define('UPLOAD_IMAGES_DIR', '" . UPLOADS_DIR_NAME . DIRECTORY_SEPARATOR . IMAGES_DIR_NAME . "'); // 圖片上傳路徑\n";
        $config_content .= "define('UPLOAD_ATTACHMENTS_DIR', '" . UPLOADS_DIR_NAME . DIRECTORY_SEPARATOR . ATTACHMENTS_DIR_NAME . "'); // 附件上傳路徑\n\n";
        $config_content .= "// 4. 分頁設定\n";
        $config_content .= "define('ARTICLES_PER_PAGE', 5); // 每頁顯示的文章數量\n";

        // --- 將設定寫入 config.php ---
        if (file_put_contents(CONFIG_FILE, $config_content) === false) {
            die("致命錯誤：無法寫入 config.php 檔案。請檢查伺服器的寫入權限。");
        }

        // --- 顯示成功訊息 ---
        echo <<<HTML
<!DOCTYPE html><html lang="zh-Hant"><head><meta charset="UTF-8"><title>設定完成</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/fonts.css"><link rel="stylesheet" href="assets/css/custom.css">
</head><body class="bg-light"><div class="container"><div class="row justify-content-center">
<div class="col-md-6" style="margin-top: 100px;"><div class="card text-center shadow-sm">
<div class="card-body p-5"><h1 class="card-title text-success" style="font-family: 'Cubic', sans-serif;">🎉 設定完成！</h1>
<p class="card-text mt-3">您的文章系統已成功設定。所有必要的資料夾和設定檔都已建立。</p>
<a href="index.php" class="btn btn-primary mt-4">點此開始使用</a>
</div></div></div></div></div></body></html>
HTML;
        exit(); // 設定完成後，停止執行後續程式碼

    } else {
        // --- 如果 config.php 不存在，顯示設定表單 ---
        echo <<<HTML
<!DOCTYPE html><html lang="zh-Hant"><head><meta charset="UTF-8"><title>首次設定</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/fonts.css"><link rel="stylesheet" href="assets/css/custom.css">
</head><body class="bg-light"><div class="container"><div class="row justify-content-center">
<div class="col-md-6" style="margin-top: 100px;"><div class="card shadow-sm">
<div class="card-body p-5"><h1 class="card-title text-center mb-4" style="font-family: 'Cubic', sans-serif;">首次環境設定</h1>
<p>歡迎使用！系統偵測到這是您第一次執行，請設定一個管理員驗證碼，用於未來登入後台發表文章。</p>
<form action="index.php" method="post" class="mt-4">
    <input type="hidden" name="setup_submitted" value="1">
    <div class="mb-3">
        <label for="secret_code" class="form-label"><strong>管理員驗證碼</strong></label>
        <input type="password" class="form-control" id="secret_code" name="secret_code" required>
        <div class="form-text">這個驗證碼是您未來登入的唯一憑證，請妥善保管。</div>
    </div>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary">完成設定並建立環境</button>
    </div>
</form></div></div></div></div></div></body></html>
HTML;
        exit(); // 顯示表單後，停止執行後續程式碼
    }
}

// =================================================================
// 主要應用程式 (Main Application)
// =================================================================

// --- 引入共用函式 ---
require_once __DIR__ . DIRECTORY_SEPARATOR . INCLUDES_DIR_NAME . DIRECTORY_SEPARATOR . 'functions.php';

// --- 1. 載入設定檔 ---
if (file_exists(CONFIG_FILE)) {
    require_once CONFIG_FILE;
} else {
    // 如果設定檔不存在，重新導向到設定精靈
    header("Location: index.php");
    exit();
}

        // --- 2. 資料庫設定和連接 ---
        $db_dir = __DIR__ . DIRECTORY_SEPARATOR . DB_DIR;
        $db_file = $db_dir . DIRECTORY_SEPARATOR . DB_FILE;
try {
    if (!is_dir($db_dir)) {
        mkdir($db_dir, 0755, true);
    }
    $conn = new PDO('sqlite:' . $db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS articles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        image_path TEXT,
        file_path TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    );
    CREATE TABLE IF NOT EXISTS article_tags (
        article_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        PRIMARY KEY (article_id, tag_id),
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    );";
    $conn->exec($create_table_sql);
} catch (PDOException $e) {
    die("資料庫錯誤: " . $e->getMessage());
}

// --- 4. 路由與請求處理 ---
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// --- API endpoint for calendar events ---
if ($action === 'calendar_events') {
    header('Content-Type: application/json');
    
    $start_date = $_GET['start'] ?? null;
    $end_date = $_GET['end'] ?? null;
    
    $sql = "SELECT id, title, created_at FROM articles";
    $params = [];
    
    if ($start_date && $end_date) {
        $sql .= " WHERE created_at >= :start_date AND created_at <= :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $events = [];
        foreach ($articles as $article) {
            $events[] = [
                'id' => $article['id'],
                'title' => $article['title'],
                'start' => date('Y-m-d', strtotime($article['created_at'])),
                'url' => 'index.php?action=view&id=' . $article['id'],
                'classNames' => ['article-event'],
                'extendedProps' => [
                    'description' => mb_strlen($article['title']) > 30 ? mb_substr($article['title'], 0, 30) . '...' : $article['title']
                ]
            ];
        }
        
        echo json_encode($events);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit();
}

// --- API endpoint for tag suggestions ---
if ($action === 'get_tags') {
    header('Content-Type: application/json');
    
    $keyword = $_GET['q'] ?? '';
    $keyword = trim($keyword);
    
    try {
        if (empty($keyword)) {
            // 如果沒有關鍵字，回傳最常用的標籤
            $sql = "SELECT t.name, COUNT(at.tag_id) as usage_count 
                    FROM tags t 
                    LEFT JOIN article_tags at ON t.id = at.tag_id 
                    GROUP BY t.id, t.name 
                    ORDER BY usage_count DESC, t.name ASC 
                    LIMIT 10";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        } else {
            // 有關鍵字則進行模糊搜尋
            $sql = "SELECT t.name, COUNT(at.tag_id) as usage_count 
                    FROM tags t 
                    LEFT JOIN article_tags at ON t.id = at.tag_id 
                    WHERE t.name LIKE :keyword 
                    GROUP BY t.id, t.name 
                    ORDER BY usage_count DESC, t.name ASC 
                    LIMIT 10";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':keyword' => '%' . $keyword . '%']);
        }
        
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = array_map(function($tag) {
            return $tag['name'];
        }, $tags);
        
        echo json_encode($result);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['form_type'])) die("無效的請求");
    
    switch ($_POST['form_type']) {
        case 'create_article':
            check_auth();
            $title = $_POST['title'];
            $content = $_POST['content'];
            $tags_string = $_POST['tags'] ?? ''; // 獲取標籤字串
            $image_path = handle_upload('image', UPLOADS_DIR_NAME . DIRECTORY_SEPARATOR . IMAGES_DIR_NAME);
            $file_path = handle_upload('file', UPLOADS_DIR_NAME . DIRECTORY_SEPARATOR . ATTACHMENTS_DIR_NAME);
            $sql = "INSERT INTO articles (title, content, image_path, file_path) VALUES (:title, :content, :image_path, :file_path)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':title' => $title, ':content' => $content, ':image_path' => $image_path, ':file_path' => $file_path]);
            $article_id = $conn->lastInsertId(); // 獲取新文章的 ID
            process_tags($conn, $article_id, $tags_string); // 處理標籤
            header("Location: index.php");
            exit();

        case 'update_article':
            check_auth();
            $article_id = $_POST['id'];
            $title = $_POST['title'];
            $content = $_POST['content'];
            $tags_string = $_POST['tags'] ?? ''; // 獲取標籤字串

            $stmt = $conn->prepare("SELECT image_path, file_path FROM articles WHERE id = :id");
            $stmt->execute([':id' => $article_id]);
            $old_paths = $stmt->fetch(PDO::FETCH_ASSOC);

            $new_image_path = handle_upload('image', UPLOADS_DIR_NAME . DIRECTORY_SEPARATOR . IMAGES_DIR_NAME);
            $new_file_path = handle_upload('file', UPLOADS_DIR_NAME . DIRECTORY_SEPARATOR . ATTACHMENTS_DIR_NAME);

            if ($new_image_path && $old_paths['image_path'] && file_exists($old_paths['image_path'])) {
                unlink($old_paths['image_path']);
            }
            $final_image_path = $new_image_path ?: $old_paths['image_path'];

            if ($new_file_path && $old_paths['file_path'] && file_exists($old_paths['file_path'])) {
                unlink($old_paths['file_path']);
            }
            $final_file_path = $new_file_path ?: $old_paths['file_path'];

            $sql = "UPDATE articles SET title = :title, content = :content, image_path = :image_path, file_path = :file_path WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':title' => $title, ':content' => $content, ':image_path' => $final_image_path, ':file_path' => $final_file_path, ':id' => $article_id]);
            process_tags($conn, $article_id, $tags_string); // 處理標籤
            header("Location: index.php?action=view&id=" . $article_id);
            exit();

        case 'login':
            if ($_POST['secret_code'] === SECRET_CODE) {
                setcookie(AUTH_COOKIE_NAME, AUTH_COOKIE_VALUE, AUTH_COOKIE_EXPIRE, "/");
                header("Location: index.php");
            } else {
                header("Location: index.php?action=login&error=1");
            }
            exit();
    }
}

// --- 5. 頁面內容渲染 ---
error_log("index.php: Rendering page for action: " . $action);
render_header($action);

switch ($action) {
    case 'list':
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * ARTICLES_PER_PAGE;
        $filter_tag = $_GET['tag'] ?? null;

        $sql_count = "SELECT COUNT(DISTINCT a.id) FROM articles a";
        $sql_select = "SELECT a.id, a.title, a.created_at, GROUP_CONCAT(t.name) AS tags FROM articles a LEFT JOIN article_tags at ON a.id = at.article_id LEFT JOIN tags t ON at.tag_id = t.id";
        $params = [];

        if ($filter_tag) {
            $sql_count .= " JOIN article_tags at_filter ON a.id = at_filter.article_id JOIN tags t_filter ON at_filter.tag_id = t_filter.id WHERE t_filter.name = :filter_tag";
            $sql_select .= " JOIN article_tags at_filter ON a.id = at_filter.article_id JOIN tags t_filter ON at_filter.tag_id = t_filter.id WHERE t_filter.name = :filter_tag";
            $params[':filter_tag'] = $filter_tag;
        }

        $total_articles_stmt = $conn->prepare($sql_count);
        $total_articles_stmt->execute($params);
        $total_articles = $total_articles_stmt->fetchColumn();
        $total_pages = ceil($total_articles / ARTICLES_PER_PAGE);

        if ($page > $total_pages && $total_pages > 0) {
            $page = $total_pages;
            $offset = ($page - 1) * ARTICLES_PER_PAGE;
        } else if ($page < 1) {
            $page = 1;
            $offset = 0;
        }

        $sql_select .= " GROUP BY a.id ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql_select);
        $stmt->bindValue(':limit', ARTICLES_PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        if ($filter_tag) {
            $stmt->bindValue(':filter_tag', $filter_tag, PDO::PARAM_STR);
        }
        $stmt->execute();
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
        <!-- FullCalendar 日曆 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">文章日曆</h5>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>文章列表<?php echo $filter_tag ? ' - 標籤: ' . htmlspecialchars($filter_tag) : ''; ?></h1>
            <div>
                <?php if (isset($_COOKIE[AUTH_COOKIE_NAME]) && $_COOKIE[AUTH_COOKIE_NAME] === AUTH_COOKIE_VALUE): ?>
                    <a href="index.php?action=create" class="btn btn-primary">建立新文章</a>
                    <a href="index.php?action=logout" class="btn btn-danger">登出</a>
                <?php else: ?>
                    <a href="index.php?action=login" class="btn btn-success">登入</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="list-group">
            <?php if (empty($articles)): ?>
                <div class="list-group-item text-center">
                    <p class="mb-0 text-muted">目前沒有任何文章</p>
                </div>
            <?php else: foreach ($articles as $article): ?>
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">
                                <a href="index.php?action=view&id=<?php echo htmlspecialchars($article['id']); ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </h5>
                            <?php if (!empty($article['tags'])): ?>
                                <div class="mt-2">
                                    <?php foreach (explode(',', $article['tags']) as $tag): ?>
                                        <a href="index.php?action=list&tag=<?php echo urlencode(trim($tag)); ?>" class="badge bg-info text-dark me-1 text-decoration-none"><?php echo htmlspecialchars(trim($tag)); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <small class="text-muted me-3"><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></small>
                            <?php if (isset($_COOKIE[AUTH_COOKIE_NAME]) && $_COOKIE[AUTH_COOKIE_NAME] === AUTH_COOKIE_VALUE): ?>
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?action=edit&id=<?php echo $article['id']; ?>" class="btn btn-outline-secondary btn-sm">編輯</a>
                                    <a href="index.php?action=delete&id=<?php echo $article['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('您確定要永久刪除這篇文章嗎？');">刪除</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4"><ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="index.php?action=list&page=<?php echo $page - 1; ?><?php echo $filter_tag ? '&tag=' . urlencode($filter_tag) : ''; ?>">&laquo;</a></li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>"><a class="page-link" href="index.php?action=list&page=<?php echo $i; ?><?php echo $filter_tag ? '&tag=' . urlencode($filter_tag) : ''; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="index.php?action=list&page=<?php echo $page + 1; ?><?php echo $filter_tag ? '&tag=' . urlencode($filter_tag) : ''; ?>">&raquo;</a></li>
        </ul></nav>
        <?php endif; ?>
<?php
        break;

    case 'view':
        if (!$id) die("無效的 ID");
        $stmt = $conn->prepare("SELECT a.*, GROUP_CONCAT(t.name) AS tags FROM articles a LEFT JOIN article_tags at ON a.id = at.article_id LEFT JOIN tags t ON at.tag_id = t.id WHERE a.id = :id GROUP BY a.id");
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$article) die("找不到文章");
?>
        <h1><?php echo htmlspecialchars($article['title']); ?></h1>
        <p class="text-muted">發布於: <?php echo date('Y年m月d日', strtotime($article['created_at'])); ?></p>
        <?php if (!empty($article['tags'])): ?>
            <div class="mb-3">
                <?php foreach (explode(',', $article['tags']) as $tag): ?>
                    <a href="index.php?action=list&tag=<?php echo urlencode(trim($tag)); ?>" class="badge bg-info text-dark me-1"><?php echo htmlspecialchars(trim($tag)); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <hr>
        <?php if (!empty($article['image_path'])): ?>
            <div style="float: left; max-width: 300px; margin-right: 20px; margin-bottom: 10px;">
                <a href="<?php echo htmlspecialchars($article['image_path']); ?>" class="thumbnail-link">
                    <img src="<?php echo htmlspecialchars($article['image_path']); ?>" class="img-fluid rounded" alt="特色圖片">
                </a>
            </div>
        <?php endif; ?>
        <div id="article-content" style="overflow-wrap: break-word;"></div>
        <hr style="clear: both;">
        <?php if (!empty($article['file_path'])): ?>
            <div class="card"><div class="card-body">
                <h5 class="card-title">附加檔案</h5>
                <p>檔案名稱: <?php echo basename($article['file_path']); ?></p>
                <a href="<?php echo htmlspecialchars($article['file_path']); ?>" class="btn btn-success" download>下載檔案</a>
            </div></div>
        <?php endif; ?>
        <div class="mt-4"><a href="index.php" class="btn btn-secondary">返回列表</a></div>
        <div id="myModal" class="modal-lightbox"><span class="close-lightbox">&times;</span><img class="modal-lightbox-content" id="img01"></div>
        <script>
            document.getElementById('article-content').innerHTML = marked.parse(<?php echo json_encode($article['content']); ?>);
            const modal = document.getElementById("myModal"), imgLink = document.querySelector(".thumbnail-link"), modalImg = document.getElementById("img01"), span = document.getElementsByClassName("close-lightbox")[0];
            if(imgLink) imgLink.onclick = e => { e.preventDefault(); modal.style.display = "block"; modalImg.src = imgLink.href; }
            if(span) span.onclick = () => modal.style.display = "none";
            window.onclick = e => { if (e.target == modal) modal.style.display = "none"; }
        </script>
<?php
        break;

    case 'create':
        check_auth();
?>
        <h2>建立新文章</h2>
        <form action="index.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="form_type" value="create_article">
            <div class="mb-3"><label for="title" class="form-label">文章標題</label><input type="text" class="form-control" id="title" name="title" required></div>
            <div class="mb-3"><label for="content" class="form-label">文章內容 (支援 Markdown)</label><textarea class="form-control" id="content" name="content" rows="10" required></textarea></div>
            <div class="mb-3"><label for="image" class="form-label">特色圖片</label><input class="form-control" type="file" id="image" name="image" accept="image/*"></div>
            <div class="mb-3"><label for="file" class="form-label">附加檔案</label><input class="form-control" type="file" id="file" name="file"></div>
            <div class="mb-3">
                <label for="tags" class="form-label">標籤 (多個標籤請用逗號分隔)</label>
                <div class="tag-input-wrapper">
                    <input type="text" class="form-control" id="tags" name="tags" placeholder="例如: PHP, Web開發, 資料庫" autocomplete="off">
                    <i class="bi bi-tags tag-input-icon"></i>
                </div>
                <small class="form-text text-muted">輸入時會顯示建議標籤，使用方向鍵選擇，Enter 或 Tab 確認</small>
            </div>
            <button type="submit" class="btn btn-primary">發布文章</button>
            <a href="index.php" class="btn btn-secondary">返回首頁</a>
        </form>
        
        <script src="assets/js/tag-autocomplete.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 初始化標籤自動完成功能
                window.initTagAutocomplete('#tags');
            });
        </script>
<?php
        break;

    case 'edit':
        check_auth();
        $stmt = $conn->prepare("SELECT a.*, GROUP_CONCAT(t.name) AS tags FROM articles a LEFT JOIN article_tags at ON a.id = at.article_id LEFT JOIN tags t ON at.tag_id = t.id WHERE a.id = :id GROUP BY a.id");
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$article) die("找不到文章");
?>
        <h2>編輯文章</h2>
        <form action="index.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="form_type" value="update_article">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($article['id']); ?>">
            <div class="mb-3"><label for="title" class="form-label">文章標題</label><input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($article['title']); ?>" required></div>
            <div class="mb-3"><label for="content" class="form-label">文章內容 (支援 Markdown)</label><textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($article['content']); ?></textarea></div>
            <div class="mb-3">
                <label for="tags" class="form-label">標籤 (多個標籤請用逗號分隔)</label>
                <div class="tag-input-wrapper">
                    <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($article['tags']); ?>" placeholder="例如: PHP, Web開發, 資料庫" autocomplete="off">
                    <i class="bi bi-tags tag-input-icon"></i>
                </div>
                <small class="form-text text-muted">輸入時會顯示建議標籤，使用方向鍵選擇，Enter 或 Tab 確認</small>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">更新特色圖片</label>
                <input class="form-control" type="file" id="image" name="image" accept="image/*">
                <?php if(!empty($article['image_path'])): ?><small class="form-text">目前圖片: <a href="<?php echo htmlspecialchars($article['image_path']); ?>" target="_blank">點擊查看</a></small><?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="file" class="form-label">更新附加檔案</label>
                <input class="form-control" type="file" id="file" name="file">
                 <?php if(!empty($article['file_path'])): ?><small class="form-text">目前檔案: <a href="<?php echo htmlspecialchars($article['file_path']); ?>" download>點擊下載</a></small><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">更新文章</button>
            <a href="index.php" class="btn btn-secondary">取消</a>
        </form>
        
        <script src="assets/js/tag-autocomplete.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 初始化標籤自動完成功能
                window.initTagAutocomplete('#tags');
            });
        </script>
<?php
        break;

    case 'delete':
        check_auth();
        if (!$id) die("無效的 ID");
        $stmt = $conn->prepare("SELECT image_path, file_path FROM articles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($paths = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($paths['image_path']) && file_exists($paths['image_path'])) unlink($paths['image_path']);
            if (!empty($paths['file_path']) && file_exists($paths['file_path'])) unlink($paths['file_path']);
        }
        // 刪除文章的標籤關聯
        $delete_article_tags_stmt = $conn->prepare("DELETE FROM article_tags WHERE article_id = :id");
        $delete_article_tags_stmt->execute([':id' => $id]);

        $delete_stmt = $conn->prepare("DELETE FROM articles WHERE id = :id");
        $delete_stmt->execute([':id' => $id]);
        header("Location: index.php");
        exit();

    case 'login':
?>
    <div class="row justify-content-center"><div class="col-md-6 col-lg-4" style="margin-top: 100px;"><div class="card">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">請輸入驗證碼</h3>
            <?php if(isset($_GET['error'])): ?><div class="alert alert-danger">驗證碼錯誤</div><?php endif; ?>
            <form action="index.php" method="post">
                <input type="hidden" name="form_type" value="login">
                <div class="mb-3"><label for="secret_code" class="form-label">驗證碼</label><input type="password" class="form-control" id="secret_code" name="secret_code" required></div>
                <div class="d-grid"><button type="submit" class="btn btn-primary">驗證</button></div>
            </form>
        </div>
    </div></div></div>
<?php
        break;

    case 'logout':
        setcookie(AUTH_COOKIE_NAME, '', AUTH_COOKIE_DELETE, '/');
        header("Location: index.php");
        exit();

    case 'error':
        $error_code = $_GET['code'] ?? '500';
        $error_messages = [
            '403' => '禁止訪問',
            '404' => '頁面不存在',
            '500' => '伺服器內部錯誤'
        ];
        $error_message = $error_messages[$error_code] ?? '未知錯誤';
        http_response_code((int)$error_code);
        ?>
        <div class="text-center mt-5">
            <h1 class="display-1 text-danger"><?php echo $error_code; ?></h1>
            <h2 class="mb-4"><?php echo htmlspecialchars($error_message); ?></h2>
            <p class="mb-4">抱歉，您請求的頁面無法訪問。</p>
            <a href="index.php" class="btn btn-primary">返回首頁</a>
        </div>
        <?php
        break;

    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>頁面不存在。</p>";
        break;
}

render_footer();
?>
