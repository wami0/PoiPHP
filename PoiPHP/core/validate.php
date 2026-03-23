<?php

class Validate
{
    // 空チェック（nullや型違いも考慮）
    public function notempty($data, $errmsg = "")
    {
        return $this->_check(trim((string)$data) !== '', $errmsg);
    }

    // 文字数チェック
    public function len($data, $max, $min = 0, $errmsg = "")
    {
        $len = mb_strlen((string)$data);
        $ok = ($min <= $len && $len <= $max);
        return $this->_check($ok, $errmsg);
    }

    // 数値チェック
    public function number($data, $errmsg = "")
    {
        return $this->_check(is_numeric($data), $errmsg);
    }

    // 英数字
    public function eisu($data, $errmsg = "")
    {
        return $this->_check(preg_match("/^[0-9a-zA-Z]+$/", $data), $errmsg);
    }

    // 英数字 + - _
    public function eisu_hu($data, $errmsg = "")
    {
        return $this->_check(preg_match("/^[0-9a-zA-Z\-\_]+$/", $data), $errmsg);
    }

    // メール
    public function email($data, $errmsg = "")
    {
        $ok = filter_var($data, FILTER_VALIDATE_EMAIL) !== false;
        return $this->_check($ok, $errmsg);
    }

    // パスワード一致
    public function password($data1, $data2, $errmsg = "")
    {
        return $this->_check($data1 === $data2, $errmsg);
    }

    // 戻り値
    private function _check($bool, $errmsg)
    {
        if ($bool) {
            // 成功時は空文字（array_filterで消えるため）
            return "";
        } else {
            // 失敗時はメッセージ（なければ false）
            return $errmsg ?: false;
        }
    }
}
