<?php

class Model
{
    protected $db;    // PDO instance
    protected $table;
    protected $pk;

    public function __construct($db, $table, $pk = 'id')
    {
        $this->db    = $db;
        $this->table = $table;
        $this->pk    = $pk;
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
        $sql = "SELECT * FROM {$this->table}";
        return $this->query($sql); // 自分の query() を呼ぶことで、デバッグ表示 + fetchAll(PDO::FETCH_ASSOC)
    }

    // 主キーで1件取得
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->pk} = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null; // 1件だけ返す。なければnull
    }

    // 条件付き取得
    public function findBy($column, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        return $this->query($sql, [$value]);
    }

    // INSERT
    public function insert($data)
    {
        $cols = array_keys($data);
        $vals = array_values($data);

        $colStr = implode(",", $cols);
        $place  = implode(",", array_fill(0, count($cols), "?"));

        $sql = "INSERT INTO {$this->table} ({$colStr}) VALUES ({$place})";
        
        $this->debugEcho($sql, $vals);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($vals);

        return $this->db->lastInsertId(); // IDを返す仕様に変更
    }

    // UPDATE
    public function update($id, $data)
    {
        $set = [];
        foreach ($data as $k => $v) {
            $set[] = "{$k} = ?";
        }
        $setStr = implode(",", $set);

        $sql = "UPDATE {$this->table} SET {$setStr} WHERE {$this->pk} = ?";
        
        $vals = array_values($data);
        $vals[] = $id;

        $this->debugEcho($sql, $vals);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($vals);
    }


    // 更新件数を返す
    public function updateCount($id, $data)
    {
        $set = [];
        foreach ($data as $k => $v) {
            $set[] = "{$k} = ?";
        }
        $setStr = implode(",", $set);
    
        $sql = "UPDATE {$this->table} SET {$setStr} WHERE {$this->pk} = ?";
    
        $vals = array_values($data);
        $vals[] = $id;
    
        $this->debugEcho($sql, $vals);
    
        $stmt = $this->db->prepare($sql);
        $stmt->execute($vals);
    
        return $stmt->rowCount(); // ← 更新件数を返す
    }
    


    // DELETE
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->pk} = ?";
        return $this->exec($sql, [$id]);
    }
}
