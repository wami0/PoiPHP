<?php

class Router
{
    public function dispatch($c)
    {
        global $config;

        $url = trim((string)($_GET['url'] ?? ''), '/');
        $segments = ($url === '') ? [] : explode('/', $url);

        // 実行中のエントリファイル名（拡張子なし）。index.php / app.php など。
        $mainName = basename($_SERVER['SCRIPT_FILENAME'] ?? 'index.php', '.php');

        if (!empty($segments)) {
            $controller = $segments[0];
        } elseif (function_exists('action')) {
            // URL 指定なし＆エントリ自身が action() を持つ → そのエントリ自身を実行
            $controller = $mainName;
        } else {
            // フロントコントローラ（app.php）経由で URL 指定なし → 既定コントローラ
            $controller = $config['app']['default_controller'] ?? 'index';
        }

        // 1. ファイル名として安全な文字（英数字とハイフン、アンダースコア）だけに制限
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $controller)) {
            throw new Exception("Page not found.", 404);
        }

        $controllerFile = SCRIPTDIR . $controller . '.php';

        if (!is_file($controllerFile)) {
            // セキュリティ上、詳細なパスは出さず 404 を返す
            throw new Exception("Page not found.", 404);
        }

        $isMainFile = (realpath($controllerFile) === realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));

        if (!$isMainFile) {
            // エントリ側で既に action() が定義されていると、同名関数の再宣言で
            // Fatal error になる。その場合はフロントコントローラ経由を案内する。
            if (function_exists('action')) {
                throw new Exception(
                    "Controller '{$controller}' cannot be loaded from this entry file " .
                    "because action() is already defined there. " .
                    "Use the front controller instead: app.php?url={$controller}"
                );
            }
            require_once $controllerFile;
        }

        if (!function_exists('action')) {
            throw new Exception("Action 'action' not defined in {$controller}.php");
        }

        // 実行
        action($c);

        return $c;
    }
}
