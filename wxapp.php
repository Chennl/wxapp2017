<?php
include_once 'wechatCallbackapiHandler.php';
header('Content-type:text/html; Charset=utf-8');
define("TOKEN", "7242609C8AEF41F88622A80AFCBE4E83");
$wechatObj = new wechatCallbackapiHandler();
if (!isset($_GET['echostr'])) {
    $wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}
 
?>
