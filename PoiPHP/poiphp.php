<?php

/*
 * PoiPHP Framework
 * Version 1.1.1
 */

// --- 1. 環境設定 ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('POI_VERSION', '1.1.1');
if (!defined('POI_DIR')) {
    define('POI_DIR', __DIR__);
}

$html_dir = dirname(POI_DIR) . '/html/';

// --- 2. オートロード設定 ---
spl_autoload_register(function($class){
    $dirs = ['core', 'models'];
    foreach ($dirs as $dir) {
        $file1 = POI_DIR . "/$dir/$class.php";
        $file2 = POI_DIR . "/$dir/" . strtolower($class) . ".php";

        if (is_file($file1)) { require_once $file1; return; }
        if (is_file($file2)) { require_once $file2; return; }
    }
});

// --- 3. コア・ユーティリティの準備 ---
require_once POI_DIR . '/core/boot.php';
require_once POI_DIR . '/core/router.php';

$c = new Controller();

// ヘルパー関数 h() の定義（テンプレートで $s->h() 以外に h() も使えるようにする）
if (!function_exists('h')) {
    function h($data) {
        global $c;
        return (isset($c->s)) ? $c->s->html($data) : htmlspecialchars((string)$data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('html')) {
    function html($data) { return h($data); }
}

// --- 4. 設定とデータベースの初期化 ---
$conf_path = POI_DIR . '/config.php';

if (file_exists($conf_path)) {
    $config = require $conf_path;
    $c->config = $config;

    // Databaseクラスが存在し、DSNが設定されていれば接続
    if (isset($config['database']['dsn']) && class_exists('Database')) {
        $c->db = new Database(
            $config['database']['dsn'],
            $config['database']['user'] ?? null,
            $config['database']['pass'] ?? null
        );
    }
}

// --- 5. ルーティングと機能拡張のセット ---
$router = new Router();
$c = $router->dispatch($c);

// Sanitize のセット
if (class_exists('Sanitize')) {
    $s_obj = new Sanitize(); // 一度変数に入れる
    $c->setSanitize($s_obj); // 変数を渡す
}

// Validate のセット
if (class_exists('Validate')) {
    $v_obj = new Validate(); // 一度変数に入れる
    $c->setValidate($v_obj); // 変数を渡す
}

// --- 6. 描画処理 ---
if (!empty($c->header_file)) {
    include $html_dir . $c->header_file;
}

// action() 内の処理が終わり、最終的な描画を実行
$c->render();

if (!empty($c->footer_file)) {
    include $html_dir . $c->footer_file;
}
