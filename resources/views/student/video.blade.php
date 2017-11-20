<!DOCTYPE HTML>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="x-ua-compatible" content="IE=edge" >
	<meta name="viewport"   content="width=device-width, height=device-height, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no"/>
	<title>{{ $title }}</title>
	<link rel="stylesheet" href="//g.alicdn.com/de/prismplayer/2.2.0/skins/default/aliplayer-min.css" />
	<script type="text/javascript" src="//g.alicdn.com/de/prismplayer/2.2.0/aliplayer-min.js"></script>
</head>
<body>
<a href="{!! $playurl !!}" download>点击下载</a>
<div  class="prism-player" id="J_prismPlayer" style="position: absolute;left:0%;"></div>
<script>
    var player = new Aliplayer({
        id: "J_prismPlayer",
        autoplay: true,
        isLive:false,
        playsinline:true,
        width:"100%",
        height:"400px",
        controlBarVisibility:"always",
        useH5Prism:false,
        useFlashPrism:false,
        source:"{!! $playurl !!}",
        cover:""
    });
</script>
</body>