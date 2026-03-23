<?php

<?php

class Upload
{
    public function save($field, $dir)
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // 保存先ディレクトリ（なければ作成）
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $tmp  = $_FILES[$field]['tmp_name'];
        $orig = $_FILES[$field]['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

        // 禁止拡張子のチェック（最低限の防御）
        $deny = ['php', 'phtml', 'php5', 'php7', 'cgi', 'exe'];
        if (in_array($ext, $deny)) {
            return false;
        }

        // 衝突・推測回避：ランダムなファイル名に変更
        $new = bin2hex(random_bytes(8)) . "_" . time() . "." . $ext;
        $path = rtrim($dir, "/") . "/" . $new;

        if (move_uploaded_file($tmp, $path)) {
            return $path; // 保存されたパスを返す
        }

        return null;
    }
}
