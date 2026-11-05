<?php

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Cookie の安全フラグ（HttpOnly / SameSite=Lax、HTTPS 時は Secure）
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function clear()
    {
        $_SESSION = [];
    }

    // セッションを完全に破棄する（データ＋Cookie＋セッション本体）
    public function destroy()
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // ログイン時など、権限が変わるタイミングで ID を振り直す（セッション固定攻撃対策）
    public function regenerate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function csrfToken()
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    // CSRF トークンの検証用メソッド
    public function checkToken($token)
    {
        $stored = $_SESSION['_csrf_token'] ?? '';
        // 保存側が空のときに「空 == 空」で通ってしまう事故を防ぐ
        if ($stored === '' || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public function flash($key, $value)
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash($key)
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return null;
        }
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]); // 1回で消える
        return $value;
    }
}
