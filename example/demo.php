<?php
require('../init.php');

$appid = 'wx908dc2ba213d9ffc';
$secret = '2b514578e7eab123ca5e80d26cd20851';
$aShareInfo = WechatShare\WechatOpen::getShare($appid, $secret);
print_r($aShareInfo);
