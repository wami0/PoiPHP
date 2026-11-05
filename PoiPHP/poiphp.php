<?php

/*
 * PoiPHP Framework
 * Version 1.2.0
 */

// --- 1. バージョン・パス ---
define('POI_VERSION', '1.2.0');
if (!defined('POI_DIR')) {
    define('POI_DIR', __DIR__);
}

// --- 2. 設定の先読み ---
// エラー表示を debug フラグで切り替えるため、boot より前に読む。
// $config は boot.php と各コアクラスから global 参照される。
$config = [];
if (is_file(POI_DIR . '/config.php')) {
    $config = require POI_DIR . '/config.php';
}
$poiDebug = !empty($config['debug']);

// 本番（debug=false）ではエラー詳細を画面に出さず、ログに残す。
ini_set('display_errors', $poiDebug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// --- 3. オートロード設定 ---
spl_autoload_register(function ($class) {
    $dirs = ['core', 'models'];
    foreach ($dirs as $dir) {
        $file1 = POI_DIR . "/$dir/$class.php";
        $file2 = POI_DIR . "/$dir/" . strtolower($class) . ".php";

        if (is_file($file1)) { require_once $file1; return; }
        if (is_file($file2)) { require_once $file2; return; }
    }
});

// --- 4. 初期化（$c は boot.php で作られる1つだけを使う） ---
require_once POI_DIR . '/core/boot.php';
require_once POI_DIR . '/core/router.php';

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

// --- 5. ルーティングと描画 ---
try {
    // DB は「登録だけ」する。実際の接続は Database が初回利用時に行う（遅延接続）ので、
    // DB を使わないページで接続エラーが起きることはない。
    if (!empty($config['database']['dsn']) && class_exists('Database')) {
        $c->db = new Database(
            $config['database']['dsn'],
            $config['database']['user'] ?? null,
            $config['database']['pass'] ?? null
        );
    }

    $router = new Router();
    $router->dispatch($c);

    $c->render();

} catch (Throwable $e) {
    // 描画途中の出力バッファは捨てて、エラーメッセージと混ざらないようにする
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $isNotFound = ($e->getCode() === 404);
    http_response_code($isNotFound ? 404 : 500);
    error_log('[PoiPHP] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if ($poiDebug) {
        echo '<pre style="color:#c00">';
        echo htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
        echo htmlspecialchars($e->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ':' . $e->getLine() . "\n";
        echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo '</pre>';
    } else {
        echo $isNotFound ? '404 Not Found' : '500 Internal Server Error';
    }
}
