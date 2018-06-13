<?php
require('../init.php');

$appid = '';
$secret = '';
$aShareInfo = WechatShare\WechatOpen::getShare($appid, $secret);
print_r($aShareInfo);
