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

    // 設定とDB（poiphp.php / boot.php がセットする。宣言しておくことで
    // PHP 8.2+ の「動的プロパティ非推奨」警告を防ぐ）
    public $config = null;
    public $db = null;

    public $header_file = null;
    public $footer_file = null;

    // loadModel() で読み込んだモデルと、アプリが自由に付けた値の置き場。
    // 未宣言プロパティへの代入（動的プロパティ）も、ここで受けて警告を出さない。
    private $models = [];
    private $extra = [];

    public function __set($name, $value)
    {
        $this->extra[$name] = $value;
    }

    public function __get($name)
    {
        if (isset($this->models[$name])) {
            return $this->models[$name];
        }
        return $this->extra[$name] ?? null;
    }

    public function __isset($name)
    {
        return isset($this->models[$name]) || isset($this->extra[$name]);
    }

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
        // パストラバーサル防止：モデル名は英字始まりの識別子だけ許可
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', (string)$name)) {
            throw new InvalidArgumentException("Invalid model name: {$name}");
        }

        // 同じモデルは使い回す（呼び出しごとに DB 接続を作らない）
        if (isset($this->models[$name])) {
            return $this->models[$name];
        }

        // PDO は $c->db（Database）が持っている接続を共有する
        if ($this->db instanceof Database) {
            $pdo = $this->db->pdo();
        } else {
            global $config;
            $pdo = new PDO(
                $config['database']['dsn'],
                $config['database']['user'] ?? null,
                $config['database']['pass'] ?? null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }

        $file = SCRIPTDIR . "models/{$name}.php";
        if (file_exists($file)) {
            require_once $file;
            $class = $name . "Model";
            $model = new $class($pdo);
        } else {
            $model = new Model($pdo, strtolower($name));
        }

        $this->models[$name] = $model;
        return $model;
    }

    public function json($data)
    {
        header("Content-Type: application/json; charset=utf-8");
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            http_response_code(500);
            $json = '{"error":"json_encode failed"}';
        }
        echo $json;
        exit;
    }
}
