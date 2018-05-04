# 微信分享sdk封装

> 基于微信 Jssdk 封装

example:

```
require('../init.php');

$appid = 'xxxxxx';  //appid
$secret = 'xxxxxx'; //密钥
$aShareInfo = WechatOpen\WechatOpen::getShare($appid, $secret);
$aShare = json_decode($aShareInfo, true);
print_r($aShare);
```

html

```angular2html

<script src="https://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
	wx.config({
    	debug: false,
        appId: '<?php echo $aShare['appid'];?>',
        timestamp: <?php echo $aShare['timestamp'];?>,
        nonceStr: '<?php echo $aShare['nonceStr'];?>',
        signature: '<?php echo $aShare['signature'];?>',
        jsApiList: [
        	'onMenuShareTimeline','onMenuShareAppMessage','onMenuShareQQ','onMenuShareWeibo','hideMenuItems'
        ]
	});
	
	wx.ready(function(){
    	shareData={
			'title':"",     //分享标题
			'link':"",      //分享地址
			'imgUrl':'',    //分享图标地址
			'desc':""       //分享内容描述
   	    }
		wx.onMenuShareTimeline({
	    	 title: shareData.title,    // 分享标题
	    	 link: shareData.link,      // 分享链接
	        imgUrl: shareData.imgUrl,  // 分享图标
	        success: function () { 
	        	// 用户确认分享后执行的回调函数
	    	 },
	        cancel: function () { 
	        	// 用户取消分享后执行的回调函数
	    	 }
		});
	
		wx.onMenuShareAppMessage({
	    	title: shareData.title,     // 分享标题
	    	desc: shareData.desc,       // 分享描述
	     	link: shareData.link,       // 分享链接
	     	imgUrl: shareData.imgUrl,   // 分享图标
	     	type: 'link',               // 分享类型,music、video或link，不填默认为link
	     	dataUrl: '',                // 如果type是music或video，则要提供数据链接，默认为空
	    	success: function () { 
	        	 // 用户确认分享后执行的回调函数
	    	},
	      	cancel: function () { 
	          	 // 用户取消分享后执行的回调函数
	      	}
		});
	
		wx.hideMenuItems({
	    	menuList: [
	      		"menuItem:originPage",
	           "menuItem:copyUrl", // 复制链接
	          	"menuItem:openWithQQBrowser",
	           "menuItem:openWithSafari"
	       	]
		}); 
	});
 </script>

```