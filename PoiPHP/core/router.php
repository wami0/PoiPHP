<?php

class Router
{
    public function dispatch($c)
    {
        $url = $_GET['url'] ?? '';
        $segments = explode('/', trim($url, '/'));
    
        // 1. ファイル名として安全な文字（英数字とハイフン、アンダースコア）だけに制限
        $controller = $segments[0] ?: 'index';
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $controller)) {
            throw new Exception("Invalid controller name.");
        }

        $controllerFile = SCRIPTDIR . $controller . '.php';
    
        if (!is_file($controllerFile)) {
            // セキュリティ上、詳細なパスを表示せず 404 等を投げるのが一般的
            throw new Exception("Page not found.");
        }
    
        require_once $controllerFile;
    
        if (!function_exists('action')) {
            throw new Exception("Action 'action' not defined in $controller.php");
        }
    
        // 実行
        action($c);

        return $c;
    }
}