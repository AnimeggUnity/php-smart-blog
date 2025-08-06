<?php

/**
 * 安全相關函式庫
 * 提供更安全的身份驗證和檔案處理機制
 */

/**
 * 生成安全的 Session ID
 */
function generate_secure_session_id() {
    return bin2hex(random_bytes(32));
}

/**
 * 生成安全的 Cookie 值
 */
function generate_secure_cookie_value($user_id, $timestamp) {
    $secret = defined('SECRET_CODE') ? SECRET_CODE : 'fallback_secret';
    return hash('sha256', $user_id . $timestamp . $secret . $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
}

/**
 * 檢查 Session 是否有效
 */
function check_secure_auth() {
    // 檢查 Session 是否存在
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time']) || !isset($_SESSION['session_id'])) {
        return false;
    }
    
    // 檢查 Session 是否過期 (2小時)
    if (time() - $_SESSION['login_time'] > 7200) {
        session_destroy();
        return false;
    }
    
    // 檢查 Session ID 是否有效
    if ($_SESSION['session_id'] !== session_id()) {
        session_destroy();
        return false;
    }
    
    // 檢查 User Agent 是否一致 (防止 Session 劫持)
    if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')) {
        session_destroy();
        return false;
    }
    
    return true;
}

/**
 * 安全的檔案上傳處理
 */
function secure_file_upload($file_key, $upload_dir, $allowed_types = [], $max_size = 5242880) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $file = $_FILES[$file_key];
    
    // 檢查檔案大小
    if ($file['size'] > $max_size) {
        error_log("檔案上傳失敗：檔案過大 - " . $file['name']);
        return null;
    }
    
    // 檢查檔案類型
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowed_types) && !in_array($file_extension, $allowed_types)) {
        error_log("檔案上傳失敗：不允許的檔案類型 - " . $file['name']);
        return null;
    }
    
    // 檢查 MIME 類型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // 圖片檔案 MIME 類型檢查
    $allowed_mimes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain', 'application/zip', 'application/x-rar-compressed'
    ];
    
    if (!in_array($mime_type, $allowed_mimes)) {
        error_log("檔案上傳失敗：不允許的 MIME 類型 - " . $mime_type);
        return null;
    }
    
    // 確保上傳目錄存在且安全
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("無法建立上傳目錄：" . $upload_dir);
            return null;
        }
    }
    
    // 生成安全的檔案名稱
    $new_file_name = date('Ymd_His') . '_' . bin2hex(random_bytes(16)) . '.' . $file_extension;
    $destination = $upload_dir . DIRECTORY_SEPARATOR . $new_file_name;
    
    // 移動檔案
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // 設定安全的檔案權限
        chmod($destination, 0644);
        return $destination;
    }
    
    return null;
}

/**
 * 安全的登入處理
 */
function secure_login($secret_code) {
    if (!defined('SECRET_CODE') || $secret_code !== SECRET_CODE) {
        return false;
    }
    
    // 啟動 Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 設定 Session 資料
    $_SESSION['user_id'] = 1; // 管理員 ID
    $_SESSION['login_time'] = time();
    $_SESSION['session_id'] = session_id();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // 設定安全的 Cookie
    $cookie_value = generate_secure_cookie_value(1, time());
    setcookie('auth_token', $cookie_value, time() + 7200, '/', '', true, true); // 2小時過期，HTTPS only
    
    return true;
}

/**
 * 安全的登出處理
 */
function secure_logout() {
    // 清除 Session
    session_start();
    session_destroy();
    
    // 清除 Cookie
    setcookie('auth_token', '', time() - 3600, '/');
    
    // 清除所有 Session Cookie
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time() - 3600, '/');
        }
    }
}

/**
 * 防止 XSS 攻擊的輸出過濾
 */
function safe_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 防止 CSRF 攻擊的 Token 生成
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 驗證 CSRF Token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 安全的資料庫查詢
 */
function secure_query($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("資料庫查詢錯誤：" . $e->getMessage());
        return false;
    }
}

/**
 * 記錄安全事件
 */
function log_security_event($event, $details = '') {
    $log_entry = date('Y-m-d H:i:s') . " - " . $event . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    error_log($log_entry, 3, __DIR__ . '/../logs/security.log');
} 