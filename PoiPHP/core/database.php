<?php

class Database
{
    // public にして、外部（Modelなど）から $this->db->pdo->prepare() と呼べるようにする
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

    // --- Modelクラスが内部で PDO のメソッドを直接使いたい場合のための中継 ---
    
    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    // ------------------------------------------------------------------

    // SELECT
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // INSERT
    public function insert($table, $data)
    {
        $fields = array_keys($data);
        $cols = implode(',', $fields);
        $place = implode(',', array_fill(0, count($fields), '?'));

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
}