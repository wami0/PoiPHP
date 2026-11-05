<?php

class Upload
{
    // 実行・解釈される恐れのある拡張子（許可リスト方式が使えない場合の最低限の防御）
    private $denyExts = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash',
        'exe', 'com', 'bat', 'cmd', 'msi',
        'jsp', 'jspx', 'asp', 'aspx', 'shtml', 'shtm',
        'htaccess', 'svg',
    ];

    /**
     * アップロードファイルを保存する。
     *
     * @param string      $field       フォームのファイル欄の name
     * @param string      $dir         保存先ディレクトリ
     * @param int|null    $maxBytes    上限サイズ（バイト）。null なら無制限
     * @param array|null  $allowedExts 許可する拡張子の配列（例: ['jpg','png']）。
     *                                 公開アップロードではこちら（許可リスト）を推奨。
     * @return string|false|null 成功時は保存パス、拒否時は false、ファイル未送信時は null
     */
    public function save($field, $dir, $maxBytes = null, $allowedExts = null)
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($maxBytes !== null && ($_FILES[$field]['size'] ?? 0) > $maxBytes) {
            return false;
        }

        $tmp  = $_FILES[$field]['tmp_name'];
        $orig = (string)($_FILES[$field]['name'] ?? '');

        // ドットファイル（.htaccess 等）と拡張子なしは受け付けない
        if ($orig === '' || $orig[0] === '.') {
            return false;
        }
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if ($ext === '') {
            return false;
        }

        // 拡張子チェック
        if ($allowedExts !== null) {
            $allowed = array_map(fn($e) => strtolower((string)$e), $allowedExts);
            if (!in_array($ext, $allowed, true)) {
                return false;
            }
        } elseif (in_array($ext, $this->denyExts, true)) {
            return false;
        }

        if (!is_uploaded_file($tmp)) {
            return null;
        }

        // 保存先ディレクトリ（なければ作成）
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // 衝突・推測回避：ランダムなファイル名に変更
        $new = bin2hex(random_bytes(16)) . "_" . time() . "." . $ext;
        $path = rtrim($dir, "/") . "/" . $new;

        if (move_uploaded_file($tmp, $path)) {
            return $path; // 保存されたパスを返す
        }

        return null;
    }
}
