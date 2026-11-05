<?php

require_once __DIR__ . '/PoiPHP/poiphp.php';

function action(&$c)
{
    $c->set("title", "PoiPHPサンプル");
    $c->set("message", "PoiPHP のサンプルページです。");

    $c->setTemplateFile("html/index.html");
}
