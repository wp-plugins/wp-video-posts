<?php
/*
Plugin Name: WP Video Posts
Plugin URI: http://cmstactics.com
Description: WP Video Posts creates a custom post for uploaded videos. You can upload videos of different formats (FLV, F4V, MP4, AVI, MOV, 3GP and WMV) and the plugin will convert it to MP4 and play it using Flowplayer.  
Version: 1.1
Author: Alex Rayan, cmstactics 
Author URI: http://cmstactics.com
License: GPLv2 or later
*/

//add_action('widget_init','wpvp_register_widgets');
require_once( dirname(__FILE__) . '/wpvp-widgets.php');
add_action('widgets_init', create_function('', 'register_widget("WPVideosForPostsWidget");'));

add_action('init', 'wpvp_init');
function wpvp_init(){
	wpvp_register();
        register_taxonomy_for_object_type('category','videos');
	wpvp_add_menu_options();		
}

function wpvp_add_menu_options(){
	if(is_admin()){
		function wpvp_options_page(){
        		add_options_page('WP Video Posts','WP Video Posts','manage_options','wp-video-posts','wpvp_options');
        	}
        	add_action('admin_menu','wpvp_options_page');

        	function wpvp_options(){
                	include('wpvp-options.php');
        	}
	}
}

function wpvp_head_includes(){
	echo '<script src="'.plugins_url('/js/', __FILE__).'flowplayer-3.2.10.min.js"></script>';
}
add_action('wp_head','wpvp_head_includes');

function wpvp_footer_includes(){
	echo '<script>flowplayer("a.myPlayer", "'.plugins_url('/js/', __FILE__).'flowplayer-3.2.11.swf", { clip:{ autoPlay:false, autoBuffering:true }, plugins: { controls: { volume: true } }});</script>';
}
add_action('wp_footer','wpvp_footer_includes');

//register custom post type
function wpvp_register(){
        $labels = array(
                'name' => _x('Videos', 'post type general name'),
                'singular_name' => _x('Video Item', 'post type singular name'),
                'add_new' => _x('Add New Video', 'video item'),
                'add_new_item' => __('Add New Video Item'),
                'edit_item' => __('Edit Video Item'),
                'new_item' => __('New Video Item'),
                'view_item' => __('View Video Item'),
                'search_items' => __('Search Video'),
                'not_found' =>  __('Nothing found'),
                'not_found_in_trash' => __('Nothing found in Trash'),
                'parent_item_colon' => ''
        );

        $args = array(
                'labels' => $labels,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'query_var' => true,
                'menu_icon' => plugins_url('/images/', __FILE__) . 'videos_menu_icon.png',
                //'rewrite' => true,
                'rewrite' => array('slug'=>'videos'),
                'capability_type' => 'post',
                'hierarchical' => false,
                'menu_position' => null,
                'supports' => array('title','editor','thumbnail')
          );

        register_post_type( 'videos' , $args );
	
}

function wpvp_encode($ID){
	require_once('wpvp-functions.php');
	$postID = intval($_REQUEST['post_id']);
	if($postID){
		$encode_video = encode($ID);
		return $encode_video;
	}
	else{
		return;
	}
}
add_action('add_attachment', 'wpvp_encode');

function wpvp_save_attachment($data){
	require_once( dirname(__FILE__) . '/wpvp-functions.php');
	$save_attachment = attachment_save($data);
	return $save_attachment;
}
add_filter('attachment_fields_to_save', 'wpvp_save_attachment');

function wpvp_edit_attachment($data){
	require_once( dirname(__FILE__) . '/wpvp-functions.php');
	$edit_attachment = attachment_edit($data);
	return $edit_attachment;
}
add_filter('attachment_fields_to_edit',  'wpvp_edit_attachment');

//register shortcode for flowplayer videos
function wpvp_register_shortcode($atts){
	extract(shortcode_atts(array(
		'src'=>'',
		'width'=>'640',
		'height'=>'360',
		'splash'=>''
	),$atts));
	$flowplayer_code = '<a href="'.$src.'" class="myPlayer" style="display:block;width:'.$width.'px;height:'.$height.'px;margin:10px auto"><img width="'.$width.'" height="'.$height.'" src="'.$splash.'" alt="" /></a>';
	return $flowplayer_code;
}
add_shortcode('wpvp_flowplayer','wpvp_register_shortcode');

//register shortcode to embed videos via video codes
function wpvp_register_embed_shortcode($atts){
	require_once( dirname(__FILE__) . '/wpvp-functions.php');
	extract(shortcode_atts(array(
		'video_code'=>'',
		'width'=>'560',
		'height'=>'315',
		'type'=>''
	),$atts));
	$embedCode = wpvp_video_embed($video_code, $width, $height, $type); 
	return $embedCode;
}
add_shortcode('wpvp_embed','wpvp_register_embed_shortcode');

//insert the shortcode into the post content
function wpvp_insert_shortcode_into_post ($html, $id, $attachment) {
	require_once( dirname(__FILE__) . '/wpvp-functions.php');	
	$postContent = wpvp_insert_video_into_post($html, $id, $attachment);
	return $postContent;
}
add_filter('media_send_to_editor','wpvp_insert_shortcode_into_post',20,3);

//add support for videos of defined extensions
function wpvp_add_video_formats_support($existing_mimes){
	require_once( dirname(__FILE__) . '/wpvp-functions.php');
	$formatsSupported = wpvp_video_upload_mime_types($existing_mimes);
	return $formatsSupported;
}
add_filter('upload_mimes','wpvp_add_video_formats_support');

function wpvp_video_code_add_meta($id){
	global $post;
	$post_content = $_POST['post_content'];
	if(!wp_is_post_revision($id)){
        	$post_id = $id;
		$post_content = $_POST['post_content'];
     		if($_POST['post_type']!= 'videos'){
			//do nothing, not our post type
        	}
		else {
        		//if( (preg_match('/youtube/', $post_content)) || (preg_match('/vimeo/', $post_content)) ){
			if(preg_match('/wpvp_embed/',$post_content)){
                		$video_code_start = strpos($post_content, 'video_code=');
                		$video_code = substr($post_content, $video_code_start+11);
                		$video_code_end = strpos($video_code, ' ');
                		$video_code = substr($video_code, 0, $video_code_end);
         	       		update_post_meta($post_id, 'wpvp_video_code',$video_code);
        		} else if(preg_match('/wpvp_flowplayer/',$post_content)){
				//do nothing - no code found
				$video_code_start = strpos($post_content, 'src=');
				$splash_code_start = strpos($post_content, 'splash=');
				$video_code = substr($post_content, $video_code_start+4);
				$splash_code = substr($post_content, $splash_code_start+7);
				$video_code_end = strpos($video_code,' ');
				$video_code = substr($video_code, 0, $video_code_end);
				$splash_code_end = strpos($splash_code,']');
				$splash_code = substr($splash_code, 0, $splash_code_end);
				$fl_codes = array('src'=>$video_code,'splash'=>$splash_code);
				$fl_codes = json_encode($fl_codes);
				update_post_meta($post_id, 'wpvp_fp_code',$fl_codes);
			}
		}
	}
	else {
		//do nothing, this is a revision, not an actual post
	}
}
add_action('publish_videos','wpvp_video_code_add_meta',20);
//add_action('pre_post_update','wpvp_video_code_add_meta');
//add_action('new_to_publish_videos', 'wpvp_video_code_add_meta');
//add_action('draft_to_publish_videos', 'wpvp_video_code_add_meta');
//add_action('pending_to_publish_videos', 'wpvp_video_code_add_meta');
?>
