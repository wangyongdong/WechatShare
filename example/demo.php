<?php
require('../init.php');

$appid = 'wxb863ef9e5a9a844b';
$secret = '9c8831d39c6fb23582370de7b6f15fac';
$aShareInfo = WechatShare\WechatOpen::getShare($appid, $secret);
print_r($aShareInfo);
