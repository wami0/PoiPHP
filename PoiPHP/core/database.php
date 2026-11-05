<?php

class Database
{
    public $pdo;

    // 遅延接続用の保持値。コンストラクタでは接続せず、初回利用時に接続する。
    private $dsn;
    private $user;
    private $pass;
    private $options = [];

    public function __construct($dsn = null, $user = null, $pass = null, $options = [])
    {
        if ($dsn) {
            // ここでは接続しない（DB を使わないページで接続エラーにしないため）
            $this->dsn     = $dsn;
            $this->user    = $user;
            $this->pass    = $pass;
            $this->options = $options;
        }
    }

    // 明示的に今すぐ接続したい場合
    public function connect($dsn, $user = null, $pass = null, $options = [])
    {
        $this->dsn     = $dsn;
        $this->user    = $user;
        $this->pass    = $pass;
        $this->options = $options;
        $this->pdo     = null;
        $this->pdo();
        return $this;
    }

    // PDO を返す（未接続ならここで接続する）
    public function pdo()
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }
        if ($this->dsn === null) {
            throw new Exception("Database is not configured (no DSN).");
        }

        $default = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // 本物のプリペアドステートメントを使う
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $options = $this->options + $default;

        $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $options);
        return $this->pdo;
    }

    // テーブル名・カラム名の検証とクォート。
    // 値と違い識別子はプレースホルダにできないので、許可文字以外を含むものは弾く。
    // "users" や "users.id" の形だけを許可する。
    public function ident($name)
    {
        $parts = explode('.', (string)$name);
        foreach ($parts as $p) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $p)) {
                throw new InvalidArgumentException("Invalid identifier: {$name}");
            }
        }
        $q = $this->quoteChar();
        return implode('.', array_map(fn($p) => $q . $p . $q, $parts));
    }

    private function quoteChar()
    {
        $driver = '';
        if ($this->pdo instanceof PDO) {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        } elseif ($this->dsn !== null) {
            $driver = strtolower(strstr($this->dsn, ':', true) ?: '');
        }
        return $driver === 'mysql' ? '`' : '"';
    }

    public function prepare($sql)
    {
        return $this->pdo()->prepare($sql);
    }

    public function lastInsertId()
    {
        return $this->pdo()->lastInsertId();
    }

    // SELECT / 汎用実行
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // 1行だけ取得（見つからなければ null）
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    // 1つの値だけ取得（先頭行の先頭カラム）
    public function fetchValue($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        $val = $stmt->fetchColumn(0);
        return $val === false ? null : $val;
    }

    // INSERT
    public function insert($table, $data)
    {
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException("insert(): data is empty.");
        }
        $fields = array_keys($data);
        $cols   = implode(',', array_map(fn($f) => $this->ident($f), $fields));
        $place  = implode(',', array_fill(0, count($fields), '?'));

        $sql = "INSERT INTO {$this->ident($table)} ({$cols}) VALUES ({$place})";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->pdo()->lastInsertId();
    }

    // UPDATE
    // 注意: $condition は開発者が書く WHERE 句そのもの。ユーザー入力を直接入れないこと（値は $params で渡す）。
    public function update($table, $data, $condition, $params = [])
    {
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException("update(): data is empty.");
        }
        $set = implode(',', array_map(fn($k) => "{$this->ident($k)} = ?", array_keys($data)));
        $sql = "UPDATE {$this->ident($table)} SET {$set} WHERE {$condition}";

        $stmt = $this->pdo()->prepare($sql);
        return $stmt->execute(array_merge(array_values($data), $params));
    }

    // DELETE
    // 注意: $condition は開発者が書く WHERE 句そのもの。ユーザー入力を直接入れないこと（値は $params で渡す）。
    public function delete($table, $condition, $params = [])
    {
        $sql = "DELETE FROM {$this->ident($table)} WHERE {$condition}";
        $stmt = $this->pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    // Transaction 開始
    public function beginTransaction()
    {
        return $this->pdo()->beginTransaction();
    }

    // Transaction 確定
    public function commit()
    {
        return $this->pdo()->commit();
    }

    // Transaction 取り消し
    public function rollBack()
    {
        return $this->pdo()->rollBack();
    }

    // Transaction 中かどうか
    public function inTransaction()
    {
        return $this->pdo()->inTransaction();
    }
}
