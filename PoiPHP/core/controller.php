<?php

class Controller
{
    public $vars = [];
    public $template = null;
    public $layout = null;

    public $sanitize;
    public $s;
    public $validate;
    public $v;
    public $session;
    public $csrf;
    public $upload;

    public $header_file = null;
    public $footer_file = null;

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

    // レイアウトファイル指定
    public function setLayoutFile($file)
    {
        $this->layout = $file;
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
        $view = new View($this->s, $this);

        $view->setFile(
            $this->template ? SCRIPTDIR . $this->template : null,
            null
        );

        $view->setVars($this->vars);
        $view->layout = $this->layout ? SCRIPTDIR . $this->layout : null;
        $view->header_file = $this->header_file ? SCRIPTDIR . $this->header_file : null;
        $view->footer_file = $this->footer_file ? SCRIPTDIR . $this->footer_file : null;

        $view->display();
    }

    public function loadModel($name)
    {
        global $config;

        $db = new PDO(
            $config['database']['dsn'],
            $config['database']['user'],
            $config['database']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $file = SCRIPTDIR . "models/{$name}.php";
        if (file_exists($file)) {
            require_once $file;
            $class = $name . "Model";
            $this->$name = new $class($db);
        } else {
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
