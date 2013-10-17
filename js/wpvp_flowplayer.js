jQuery(window).load(function(){
	var wpvp_swf_location = object_name.swf;
	var player = object_name.player;
	var base = object_name.stylesheet_base;
	if(player=='flowplayer'){
		flowplayer("a.myPlayer", ""+wpvp_swf_location+"", { clip:{ autoPlay:false, autoBuffering:true }, plugins: { controls: { volume: true } }});
	} else if(player=='videojs'){
		videojs.options.flash.swf = base+"/inc/video-js/video-js.swf";
	}
});
