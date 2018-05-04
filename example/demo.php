<?php
require('../init.php');

$appid = '';
$secret = '';
$aShareInfo = WechatOpen\WechatOpen::getShare($appid, $secret);
$aShare = json_decode($aShareInfo, true);
print_r($aShare);
