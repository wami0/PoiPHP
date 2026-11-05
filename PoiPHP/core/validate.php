<?php

class Validate
{
    // 文字列として扱える値だけ文字列化する（配列・オブジェクト・null の型事故防止）。
    // 扱えない型なら null を返す＝検証失敗として扱う。
    private function toStr($data)
    {
        if (is_string($data) || is_numeric($data) || is_bool($data)) {
            return (string)$data;
        }
        return null;
    }

    // 空チェック（nullや型違いも考慮）
    public function notempty($data, $errmsg = "")
    {
        $str = $this->toStr($data);
        return $this->_check($str !== null && trim($str) !== '', $errmsg);
    }

    // 文字数チェック
    public function len($data, $max, $min = 0, $errmsg = "")
    {
        $str = $this->toStr($data);
        if ($str === null) {
            return $this->_check(false, $errmsg);
        }
        $len = mb_strlen($str);
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
        $str = $this->toStr($data);
        return $this->_check($str !== null && preg_match("/^[0-9a-zA-Z]+$/", $str) === 1, $errmsg);
    }

    // 英数字 + - _
    public function eisu_hu($data, $errmsg = "")
    {
        $str = $this->toStr($data);
        return $this->_check($str !== null && preg_match("/^[0-9a-zA-Z\\-\\_]+$/", $str) === 1, $errmsg);
    }

    // メール
    public function email($data, $errmsg = "")
    {
        $str = $this->toStr($data);
        $ok = $str !== null && filter_var($str, FILTER_VALIDATE_EMAIL) !== false;
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
