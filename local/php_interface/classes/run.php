<?php
$_SERVER['DOCUMENT_ROOT'] = "/home/c/ca51409/bitrix_vhuzw/public_html";

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/classes/autoload.php');
$comments = new \Ameton\Generators\CommentGenerator();
$comments->createAll();
?>
