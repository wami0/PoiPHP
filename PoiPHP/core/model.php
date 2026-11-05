<?php

class Model
{
    protected $db;    // PDO instance
    protected $table;
    protected $pk;

    public function __construct($db, $table, $pk = 'id')
    {
        $this->db    = $db;
        // テーブル名・主キー名は識別子として検証しておく
        $this->ident($table);
        $this->ident($pk);
        $this->table = $table;
        $this->pk    = $pk;
    }

    // テーブル名・カラム名の検証とクォート。
    // 値と違い識別子はプレースホルダにできないので、許可文字以外を含むものは弾く。
    protected function ident($name)
    {
        $parts = explode('.', (string)$name);
        foreach ($parts as $p) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $p)) {
                throw new InvalidArgumentException("Invalid identifier: {$name}");
            }
        }
        $q = ($this->db instanceof PDO && $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') ? '`' : '"';
        return implode('.', array_map(fn($p) => $q . $p . $q, $parts));
    }

    protected function tableIdent()
    {
        return $this->ident($this->table);
    }

    protected function pkIdent()
    {
        return $this->ident($this->pk);
    }

    // ------------------------------------------------------------
    // SQL を発行するたびに即 echo（debug=true のとき）
    // ------------------------------------------------------------
    protected function debugEcho($sql, $params)
    {
        global $config;

        if (!empty($config['debug'])) {
            echo "<pre style='color:#c00'>";
            echo "SQL: {$sql}\n";
            if (!empty($params)) {
                echo "PARAMS: " . print_r($params, true);
            }
            echo "</pre>";
        }
    }

    // ------------------------------------------------------------
    // 生 SQL をそのまま発行（SELECT 用）
    // ------------------------------------------------------------
    public function query($sql, $params = [])
    {
        $this->debugEcho($sql, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------
    // 生 SQL をそのまま発行（INSERT/UPDATE/DELETE 用）
    // ------------------------------------------------------------
    public function exec($sql, $params = [])
    {
        $this->debugEcho($sql, $params);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // 全件取得
    public function findAll()
    {
        $sql = "SELECT * FROM {$this->tableIdent()}";
        return $this->query($sql); // 自分の query() を呼ぶことで、デバッグ表示 + fetchAll(PDO::FETCH_ASSOC)
    }

    // 主キーで1件取得
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->tableIdent()} WHERE {$this->pkIdent()} = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null; // 1件だけ返す。なければnull
    }

    // 条件付き取得（$column は識別子として検証される）
    public function findBy($column, $value)
    {
        $sql = "SELECT * FROM {$this->tableIdent()} WHERE {$this->ident($column)} = ?";
        return $this->query($sql, [$value]);
    }

    // INSERT
    public function insert($data)
    {
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException("insert(): data is empty.");
        }
        $cols = array_keys($data);
        $vals = array_values($data);

        $colStr = implode(",", array_map(fn($c) => $this->ident($c), $cols));
        $place  = implode(",", array_fill(0, count($cols), "?"));

        $sql = "INSERT INTO {$this->tableIdent()} ({$colStr}) VALUES ({$place})";

        $this->debugEcho($sql, $vals);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($vals);

        return $this->db->lastInsertId(); // IDを返す仕様
    }

    // UPDATE 本体（update / updateCount で共用）。実行した PDOStatement を返す。
    protected function doUpdate($id, $data)
    {
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException("update(): data is empty.");
        }
        $set = [];
        foreach ($data as $k => $v) {
            $set[] = "{$this->ident($k)} = ?";
        }
        $setStr = implode(",", $set);

        $sql = "UPDATE {$this->tableIdent()} SET {$setStr} WHERE {$this->pkIdent()} = ?";

        $vals = array_values($data);
        $vals[] = $id;

        $this->debugEcho($sql, $vals);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($vals);

        return $stmt;
    }

    // UPDATE（成功したら true）
    public function update($id, $data)
    {
        $this->doUpdate($id, $data);
        return true;
    }

    // UPDATE（更新件数を返す）
    public function updateCount($id, $data)
    {
        $stmt = $this->doUpdate($id, $data);
        return $stmt->rowCount();
    }

    // DELETE
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->tableIdent()} WHERE {$this->pkIdent()} = ?";
        return $this->exec($sql, [$id]);
    }
}
