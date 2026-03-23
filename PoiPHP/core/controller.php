<?php

class Controller
{
    public $vars = [];
    public $template = null;
    public $layout;

    public $sanitize;
    public $s;
    public $validate;
    public $v;
    public $session;
    public $csrf;
    public $upload;


    // 変数セット
    public function set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    public function setArray($array)
    {
        foreach ($array as $k => $v) {
            $this->set($k, $v);
        }
    }

    // テンプレートファイル指定
    public function setTemplateFile($file)
    {
        $this->template = $file;
    }

    // Sanitize
    public function setSanitize(&$sanitize)
    {
        $this->sanitize = $sanitize;
        $this->s = &$this->sanitize;
    }

    // Validate
    public function setValidate(&$validate)
    {
        $this->validate = $validate;
        $this->v = &$this->validate;
    }

    // 描画
    public function render()
    {
        extract($this->vars);
    
        $s = $this->s;
        $v = $this->v;
    
        // テンプレートをバッファリング
        ob_start();
        include SCRIPTDIR . $this->template;
        $_poi_content = ob_get_clean();
    
        // layout が設定されていて、ファイルが存在する場合のみ使う
        if ($this->layout) {
            $layoutFile = SCRIPTDIR . $this->layout;
            if (file_exists($layoutFile)) {
                include $layoutFile;
                return;
            }
        }
    
        // layout が無い or ファイルが無い → テンプレートだけ出す
        echo $_poi_content;
    }




    public function loadModel($name)
    {
        global $config;
    
        // DB 接続（必要な時だけ）
        $db = new PDO(
            $config['database']['dsn'],
            $config['database']['user'],
            $config['database']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    
        // モデルファイル（任意で作る）
        $file = SCRIPTDIR . "models/{$name}.php";
        if (file_exists($file)) {
            require_once $file;
            $class = $name . "Model";
            $this->$name = new $class($db);
        } else {
            // モデルファイルが無い場合は汎用 Model を使う
            $this->$name = new Model($db, strtolower($name));
        }
    
        return $this->$name;
    }

    public function json($data)
    {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }


}
