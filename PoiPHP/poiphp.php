<?php

/*
 * PoiPHP Framework
 * Version 1.1.0
 */


// PHP8 でもエラーが見えるように
ini_set('display_errors', 1);
error_reporting(E_ALL);

// フレームワークバージョン
define('POI_VERSION', '1.1.0');

// ライブラリの場所
if (!defined('POI_DIR')) {
    define('POI_DIR', __DIR__);
}


$html_dir = dirname(POI_DIR) . '/html/';



// ------------------------------------------------------------
// autoload
// ------------------------------------------------------------
spl_autoload_register(function($class){
    $dirs = ['core', 'models'];
    foreach ($dirs as $dir) {

        // クラス名そのまま
        $file1 = POI_DIR . "/$dir/$class.php";

        // 小文字ファイル対応
        $file2 = POI_DIR . "/$dir/" . strtolower($class) . ".php";

        if (is_file($file1)) { require_once $file1; return; }
        if (is_file($file2)) { require_once $file2; return; }
    }
});

// ------------------------------------------------------------
// boot
// ------------------------------------------------------------
require_once POI_DIR . '/core/boot.php';
require_once POI_DIR . '/core/router.php';


// ------------------------------------------------------------
// 設定とDBの準備（無限ループ防止策）
// ------------------------------------------------------------
$c = new Controller();

$conf_path = POI_DIR . '/config.php';

if (file_exists($conf_path)) {
    // ここで読み込むファイルは return [...] だけである必要があります
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


// ------------------------------------------------------------
// Router
// ------------------------------------------------------------
$router = new Router();
$c = $router->dispatch($c);

// ヘッダーを表示
if (!empty($c->header_file)) {
    include $html_dir . $c->header_file;
}

// action() の後に render()
$c->render();

// フッターを表示
if (!empty($c->footer_file)) {
    include $html_dir . $c->footer_file;
}
