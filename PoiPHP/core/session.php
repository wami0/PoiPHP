<?php

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
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

    public function csrfToken()
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }


    // 検証用メソッドの追加案
    public function checkToken($token)
    {
        $stored = $_SESSION['_csrf_token'] ?? '';
        return hash_equals($stored, (string)$token);
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
