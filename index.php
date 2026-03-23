<?php

require_once __DIR__ . '/PoiPHP/poiphp.php';

function action(&$c)
{
    $c->set("title", "PoiPHPサンプル");
    $c->set("message", "これは Controller クラスを使わないサンプルです。");

    $c->setTemplateFile("html/index.html");
}
