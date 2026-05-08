<?php

class Database
{
    public $pdo;

    public function __construct($dsn = null, $user = null, $pass = null, $options = [])
    {
        if ($dsn) {
            $this->connect($dsn, $user, $pass, $options);
        }
    }

    public function connect($dsn, $user = null, $pass = null, $options = [])
    {
        $default = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $options = $options + $default;

        $this->pdo = new PDO($dsn, $user, $pass, $options);
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    // SELECT / 汎用実行
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
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
        $fields = array_keys($data);
        $cols   = implode(',', $fields);
        $place  = implode(',', array_fill(0, count($fields), '?'));

        $sql = "INSERT INTO {$table} ({$cols}) VALUES ({$place})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->pdo->lastInsertId();
    }

    // UPDATE
    public function update($table, $data, $condition, $params = [])
    {
        $set = implode(',', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$condition}";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(array_merge(array_values($data), $params));
    }

    // DELETE
    public function delete($table, $condition, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$condition}";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Transaction 開始
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    // Transaction 確定
    public function commit()
    {
        return $this->pdo->commit();
    }

    // Transaction 取り消し
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    // Transaction 中かどうか
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }
}
?>
