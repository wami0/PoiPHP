<?php

class Sanitize
{
    // HTMLエスケープ（配列・多次元配列対応）
    public function html($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'html'], $data);
        }
        return htmlspecialchars((string)$data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // html() の短縮形（エイリアス）
    public function h($data)
    {
        return $this->html($data);
    }

    // URL エンコード
    public function url($data)
    {
        return rawurlencode((string)$data);
    }

    // JSON 用（JavaScript への安全な埋め込み）
    public function json($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    // --- POST ---
    public function post($key)
    {
        return isset($_POST[$key]) ? $this->html($_POST[$key]) : null;
    }

    public function postt($key)
    {
        $data = $this->post($key);
        return $data !== null ? trim($data) : null;
    }

    public function posts($key)
    {
        $data = $this->post($key);
        $_SESSION[$key] = $data;
        return $data;
    }

    public function postst($key)
    {
        $data = $this->postt($key);
        $_SESSION[$key] = $data;
        return $data;
    }

    // --- GET ---
    public function get($key)
    {
        return isset($_GET[$key]) ? $this->html($_GET[$key]) : null;
    }

    public function gett($key)
    {
        $data = $this->get($key);
        return $data !== null ? trim($data) : null;
    }

    public function gets($key)
    {
        $data = $this->get($key);
        $_SESSION[$key] = $data;
        return $data;
    }

    public function getst($key)
    {
        $data = $this->gett($key);
        $_SESSION[$key] = $data;
        return $data;
    }
}