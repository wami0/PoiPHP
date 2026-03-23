<?php

/*
 * PoiPHP Framework
 * Version 1.0.0
 */


// PHP8 でもエラーが見えるように
ini_set('display_errors', 1);
error_reporting(E_ALL);

// フレームワークバージョン
define('POI_VERSION', '1.0.0');

// ライブラリの場所
if (!defined('POI_DIR')) {
    define('POI_DIR', __DIR__);
}

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
// Router
// ------------------------------------------------------------
$router = new Router();
$c = $router->dispatch($c);

// action() の後に render()
$c->render();

