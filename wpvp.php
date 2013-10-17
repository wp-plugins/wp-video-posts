<?php
/*
Plugin Name: WP Video Posts
Plugin URI: http://cmstactics.com
Description: WP Video Posts creates a custom post for uploaded videos. You can upload videos of different formats (FLV, F4V, MP4, AVI, MOV, 3GP and WMV) and the plugin will convert it to MP4 and play it using Flowplayer.  
Version: 3.0
Author: Alex Rayan, cmstactics 
Author URI: http://cmstactics.com
License: GPLv2 or later
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

require( dirname(__FILE__) . '/classes/wpvp-widgets-class.php');
require( dirname(__FILE__) . '/classes/wpvp-core-class.php');
require( dirname(__FILE__) . '/classes/wpvp-helper-class.php');
if(!class_exists('WPVPMediaEncoder')):
/*
Main WPVPMediaEncoder Class
*/
class WPVPMediaEncoder{
	/**
	* @var string WPVPMediaEncoder version
	*/
	public $version = '3.0';
	public static function init(){
		$class = __CLASS__;
		new $class;
	}
	/**
	* The main loader
	* $uses WPVPMediaEncoder::includes() Include required files
	*/
	public function __construct(){
		$this->setup_actions();
	}
	/**
	*Setup teh default hooks and actions
	*@access private
	*/
	private function setup_actions(){
		add_action('widgets_init', create_function('', 'register_widget("WPVideosForPostsWidget");'));
		if(is_admin()){
			add_action('admin_menu',array(&$this,'wpvp_options_page'));
		}
		add_action('init',array(&$this,'wpvp_register_videos_post_type'));
		add_action('wp_enqueue_scripts',array(&$this,'wpvp_enqueue_scripts'));
		add_action('add_attachment', array(&$this,'wpvp_encode'),1);
		add_action('publish_videos',array(&$this,'wpvp_add_code_meta'),20,1);
		add_action('the_content',array(&$this,'wpvp_insert_edit_link_into_video_post'),10,1);
		add_filter('media_send_to_editor',array(&$this,'wpvp_insert_shortcode_into_post'),10,3);
		add_filter('upload_mimes',array(&$this,'wpvp_add_video_formats_support'),10,1);
		add_filter('attachment_fields_to_save',array(&$this,'wpvp_attachment_save'),10,1);
		add_filter('attachment_fields_to_edit',array(&$this,'wpvp_attachment_edit'),10,1);
		add_shortcode('wpvp_flowplayer',array(&$this,'wpvp_register_shortcode'));
		add_shortcode('wpvp_embed',array(&$this,'wpvp_register_embed_shortcode'));
		add_shortcode('wpvp_upload_video',array(&$this,'wpvp_register_front_uploader_shortcode'));
		add_shortcode('wpvp_edit_video',array(&$this,'wpvp_register_front_editor_shortcode'));
		//Only call the published notification email function if the option is set to yes
		$wpvp_published_notification = get_option('wpvp_published_notification','no');
		if($wpvp_published_notification == 'yes') {
	        	add_action('draft_to_publish',array(&$this,'wpvp_draft_to_publish_notification'),20,1);
	        	add_action('pending_to_publish',array(&$this,'wpvp_draft_to_publish_notification'),20,1);
		}
		$wpvp_main_loop_alter = get_option('wpvp_main_loop_alter','yes');
		if($wpvp_main_loop_alter == 'yes'){
			add_action('pre_get_posts',array(&$this,'wpvp_get_custom_video_posts'),1);
		}
		$wpvp_clean_url = get_option('wpvp_clean_url',false);
		if($wpvp_clean_url){
			add_action('pre_get_posts',array(&$this,'wpvp_parse_request_query'),10,1);
			/* Customized part - 9/30/13 */
			add_filter( 'post_type_link', array(&$this,'wpvp_remove_slug'), 10, 3 );
		}
		add_action( 'admin_enqueue_scripts', array(&$this,'wpvp_admin_scripts_enqueue'));
	}
	/**
	*Register menu options on admin_menu action hook
	*access public
	*/
	public function wpvp_options_page(){
        add_options_page(
			'WP Video Posts',
			'WP Video Posts',
			'manage_options',
			'wp-video-posts',
			array(&$this,'wpvp_options')
		);
    }
	/**
	*Register tabs
	*access public
	*/
    public function wpvp_options(){
	        global $pagenow;
            if ( isset ( $_GET['tab'] ) )
                $this->wpvp_define_tabs($_GET['tab']);
        	else
	            $this->wpvp_define_tabs('general');
        	switch($_GET['tab']){
	            case 'front-end-uploader':
                    include('options/wpvp-uploader-options.php');
                    break;
                case 'front-end-editor':
        	        include('options/wpvp-editor-options.php');
	                break;
                case 'shortcodes':
                    include('options/wpvp-shortcodes-options.php');
                	break;
        	    case 'general':
	            default:
                    include('options/wpvp-options.php');
                    break;
            }
    }
	/**
	*Define tabs pages
	*access private
	*/
	private function wpvp_define_tabs($current = 'general'){
        $tabs = array('general'=>'General','front-end-uploader'=>'Front End Uploader','front-end-editor'=>'Front End Editor','shortcodes'=>'Shortcodes Reminder');
        echo '<div id="icon-options-general" class="icon32"><br></div>';
        echo '<h2 class="nav-tab-wrapper">';
        foreach( $tabs as $tab => $name ){
            $class = ( $tab == $current ) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=wp-video-posts&tab=$tab'>$name</a>";
        }
        echo '</h2>';
    }
	/**
	*Enqueue admin scripts on the plugin's page
	*@access public
	*/
	public function wpvp_admin_scripts_enqueue($hook){
		if( 'options-general.php' != $hook && $_GET['page']!='wp-video-posts')
			return;
		wp_enqueue_style( 'wpvp_admin_style', plugin_dir_url( __FILE__ ) . 'css/admin.css' );
	}
	/*
    **register custom post type: videos on init action hook
    *@access static
    */
    static function wpvp_register_videos_post_type(){
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
                'rewrite' => array('slug'=>'videos'),
                'capability_type' => 'post',
                'hierarchical' => false,
                'menu_position' => null,
                'supports' => array('title','editor','thumbnail','comments','author','custom-fields'),
                'taxonomies'=>array('post_tag')
            );
            register_post_type( 'videos' , $args );
            register_taxonomy_for_object_type('category','videos');
    }
    /**
    *Flush rewrite rules on plugin reactivation
    *@access static
    **/
    static function wpvp_create_video_post_flush_rewrite_rules(){
            self::wpvp_register_videos_post_type();
            self::wpvp_flush_rewrite_rules();
    }
	/**
    *Flush rewrite rules
    *@access static
    **/
    static function wpvp_flush_rewrite_rules(){
			flush_rewrite_rules();
    }
	/*
	*Enqueue scripts on wp_enqueue_scripts action hook
	*@action public
	*/
	public function wpvp_enqueue_scripts(){
		$wpvp_player = get_option('wpvp_player','flowplayer') ? get_option('wpvp_player','flowplayer') : 'flowplayer';
		if($wpvp_player=='flowplayer'){
	        wp_enqueue_script('wpvp_flowplayer',plugins_url('/js/', __FILE__).'flowplayer-3.2.10.min.js',array('jquery'),NULL);
		} else if($wpvp_player=='videojs'){
			wp_enqueue_script('wpvp_videojs',plugins_url('/inc/video-js/', __FILE__).'video.js',array('jquery'),NULL);
			wp_enqueue_script('wpvp_videojs_yt',plugins_url('/inc/video-js/', __FILE__).'vjs.youtube.js',array('wpvp_videojs'),NULL);
			wp_enqueue_style('wpvp_videojs_css',plugins_url('/inc/video-js/', __FILE__).'video-js.css');
		}
        wp_enqueue_script( 'wpvp_front_end_js',plugins_url('/js/', __FILE__).'wpvp-front-end.js',array('jquery'),NULL );
		wp_enqueue_script( 'wpvp_flowplayer_js',plugins_url('/js/',__FILE__).'wpvp_flowplayer.js',array('jquery','wpvp_flowplayer'),NULL);
		
		$swf_loc = plugins_url('/js/', __FILE__).'flowplayer-3.2.11.swf';
		$vars_to_pass = array('swf'=>$swf_loc,'player'=>$wpvp_player,'stylesheet_base'=>get_bloginfo('stylesheet_directory'));
		wp_localize_script('wpvp_flowplayer_js','object_name',$vars_to_pass);
        wp_enqueue_style('wpvp_widget',plugins_url('/css/', __FILE__).'style.css');
	}
	/**
	*add support for videos of defined extensions on upload_mimes filter hook
	*@access public
	*/
	public function wpvp_add_video_formats_support($existing_mimes){
        	$existing_mimes['mov'] = 'video/quicktime';
	        $existing_mimes['avi'] = 'video/avi';
        	$existing_mimes['wmv|wvx|wm|wmx'] = 'video/x-ms-wmv';
	        $existing_mimes['3gp|3gpp|3gpp2|3g2'] = 'video/3gpp';
        	return $existing_mimes;
	}
	/**
	*Process encoding on add_attachment action hook
	*@access public
	*/
	public function wpvp_encode($ID){
        	$postID = intval($_REQUEST['post_id']);
	        if($postID){
			$helper = new WPVP_Helper();
                	$options = $helper->wpvp_get_full_options();
        	        $newEncode = new WPVP_Encode_Media($options);
	                $encode_video = $newEncode->wpvp_encode($ID);
                	//return $encode_video;
        	}
	        return;
	}
	/**
	*send email on post status change to publish to the post author on draft_to_publish and pending_to_publish action hook
	*@access public
	*/
	public function wpvp_draft_to_publish_notification($postObj) {
        	global $post;
	        if($postObj->post_type== 'videos'){
                	$post_content = $postObj->post_content;
        	        $post_author_id = $postObj->post_author;
	                $userObj = get_userdata($post_author_id);
                	$post_author_email = $userObj->user_email;
        	        $post_author_login = $userObj->user_login;
	                $post_thumb = explode('splash=',$post_content);
                	$post_thumb = explode(']',$post_thumb[1]);
        	        $post_thumb = $post_thumb[0];
	                $post_permalink = get_permalink($postObj->ID);
        	        $admin = array($post_author_email);
	                if(strlen($postObj->post_title) > 15) {
                        	$postObj->post_title = substr($postObj->post_title, 0, 15) . '...';
                	}
        	        $subject = get_bloginfo('name').': "'.$postObj->post_title.'" has been published';
	                $headers = 'MIME-Version: 1.0' . "\r\n";
                	$headers.= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        	        $message = 'Your video has been reviewed by '.get_bloginfo('name').' and it has been published. You can view your video by accessing this link, "<a href="'.$post_permalink.'">'.$postObj->post_title.'</a>".<br /><br /><a href="'.$post_permalink.'"><img src="'.$post_thumb.'" width="250px" height="142px" /><br />'.$postObj->post_title.'</a>';
	                $message .= '<br /><br />Regards,<br />'.get_bloginfo('name');
                	$send_publish_notice = wp_mail($admin, $subject, $message, $headers);
        	}
	}
	/*
	**Update post data on attachment_fields_to_save filter hook
	*@access public
	*/
	public function wpvp_attachment_save($data) {
		$helper = new WPVP_Helper();
	        if( $helper->is_video($data['post_mime_type'])=='video' ) {
	                $parent_post = $data['post_parent'];
                	$newdata = array (
        	                'ID'            => $parent_post,
	                        'post_excerpt'  => $data['post_content'],
                        	'post_title'    => $data['post_title'],
                	        'tags_input'    => $data['post_excerpt']
        	        );
	                wp_update_post( $newdata );
                	return $data;
        	}
	        else {
                	return $data;
        	}
	}
	/*
	**Edit attachment data on attachment_fields_to_edit filter hook
	*@access public
	*/
	public function wpvp_attachment_edit($data){
		$helper = new WPVP_Helper();
	        $ext = $helper->guess_file_type($data['post_title']['value']);
        	if ($ext=='flv') {
                	$data['post_excerpt']['label'] = 'Tags';
        	        $data['post_excerpt']['helps'] = 'Separate tags with commas';
	                return $data;
        	}
	        else {
                	return $data;
        	}
	}
	/**
	*insert short code into post on media_send_to_editor filter hook
	*@access public
	*/
	public function wpvp_insert_shortcode_into_post ($html, $id, $attachment) {
        	$postID = intval($_REQUEST['post_id']);
	        $postObj = get_post($postID);
        	$postContent = $html;
	        if($postObj->post_type=='videos'){
			$helper = new WPVP_Helper();
        	        $options = $helper->wpvp_get_full_options();
	                $newVideo = new WPVP_Encode_Media($options);
                	$postContent = $newVideo->wpvp_insert_video_into_post($postContent, $id, $attachment);
        	}
        	return $postContent;
	}
	/**
	*function to add code to the post meta and update on post update if needed on publish_videos custom post type action hook
	*@access public
	*/
	public function wpvp_add_code_meta($id){
		$helper = new WPVP_Helper();
		$helper->wpvp_video_code_add_meta($id); 
	}
	/**
	*insert video edit link into video post (only if admin or post author) on the_content action hook
	*@access public
	*/
	public function wpvp_insert_edit_link_into_video_post($content){
        	global $post;
	        $postID = $post->ID;
        	$post_type = get_post_type($postID);
	        $post_status = get_post_status($postID);
        	$editPageID = get_option('wpvp_editor_page');
	        $curr_user = wp_get_current_user();
        	$user_id = $curr_user->ID;
	        //get post Object based on post id
        	$post_author = $post->post_author;
	        if(current_user_can('administrator')||$user_id==$post_author){
        	        $permalink = get_permalink($editPageID);
	                if(is_single() && $post_type=='videos' && $post_status=='publish' && $editPageID && $permalink != ""){
                	        $content='<div class="wpvp_edit_video_link"><a href="'.$permalink.'?video='.$postID.'">Edit Video</a></div>'.$content;
        	        }
	        }
        	return $content;
	}
	/**
	*Alter main Wordpress query for latest posts, categories, feed, tags to display videos custom post type
	*on pre_get_posts filter
	*@access public
	*/
	public function wpvp_get_custom_video_posts($query){
		if((is_home()&&$query->is_main_query())||is_feed()||(is_category()&&$query->is_main_query())||(is_tag()&&$query->is_main_query())||(is_front_page()&&$query->is_main_query())||(is_author()&&$query->is_main_query())){
			$query->set( 'post_type', array( 'post','videos'));
		}
		return $query;
	}
	/*** REGISTER SHORT CODES ***/
	/**
	* Register shortcode for flowplayer videos
	* @access public
	*/
	public function wpvp_register_shortcode($atts){
	        extract(shortcode_atts(array(
                	'src'=>'',
        	        'width'=>'640',
	                'height'=>'360',
                	'splash'=>''
        	),$atts));
			$wpvp_player = get_option('wpvp_player','flowplayer') ? get_option('wpvp_player','flowplayer') : 'flowplayer';
			if($wpvp_player=='flowplayer'){
				$flowplayer_code = '<a href="'.$src.'" class="myPlayer" style="display:block;width:'.$width.'px;height:'.$height.'px;margin:10px auto"><img width="'.$width.'" height="'.$height.'" src="'.$splash.'" alt="" /></a>';
			} else if($wpvp_player=='videojs'){
				$autoplay = get_option('wpvp_autoplay',false) ? get_option('wpvp_autoplay',false) : false;
				$splash_check = get_option('wpvp_splash',false) ? get_option('wpvp_splash',false) : false;
				if($autoplay)
					$ap = 'autoplay ';
				else
					$ap = '';
				if($splash_check)
					$sp = 'poster="'.$splash.'"';
				else
					$sp = '';
				$flowplayer_code = '<video id="wpvp_videojs" '.$ap.'class="video-js vjs-default-skin" controls preload="none" width="'.$width.'" height="'.$height.'"'.$sp.' data-setup="{}">
					<source src="'.$src.'" type="video/mp4" />
				</video>';
				//$flowplayer_code = '<video id="vid1" src="" class="video-js vjs-default-skin" controls autoplay preload="auto" width="640" height="360" data-setup='{ "techOrder": ["youtube"], "src": "http://www.youtube.com/watch?v=iivaXP6W1e4" }'></video>';
			}
	        return $flowplayer_code;
	}
	/**
	* register shortcode to embed videos via video codes
	*@access public 
	*/
	public function wpvp_register_embed_shortcode($atts){
        	//require_once( dirname(__FILE__) . '/wpvp-core-class.php');
	        extract(shortcode_atts(array(
        	        'video_code'=>'',
	                'width'=>'560',
                	'height'=>'315',
        	        'type'=>''
	        ),$atts));
        	$newDisplay = new WPVP_Encode_Media();
	        $embedCode = $newDisplay->wpvp_video_embed($video_code, $width, $height, $type);
        	return $embedCode;
	}
	/**
	*register shortcode for the front end uploader
	*@access public
	*/
	public function wpvp_register_front_uploader_shortcode($atts){
        	extract(shortcode_atts(array(
	        ),$atts));
        	$newMedia = new WPVP_Encode_Media();
	        $uploader = $newMedia->wpvp_front_video_uploader();
        	return $uploader;
	}
	/**
	*register shortcode for the front end editor
	*@access public
	*/
	public function wpvp_register_front_editor_shortcode($atts){
        	extract(shortcode_atts(array(
	        ),$atts));
        	$newMedia = new WPVP_Encode_Media();
	        $editor = $newMedia->wpvp_front_video_editor();
        	return $editor;
	}
	/**
	*Remove slug "videos" from our custom post type
	*@access public
	**/
	public function wpvp_remove_slug($post_link, $post, $leavename){
		if ( ! in_array( $post->post_type, array( 'videos' ) ) || 'publish' != $post->post_status )
       			return $post_link;
    		$post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link ); 
    		return $post_link;
	}
	/**
	*
	*
	**/
	public function wpvp_parse_request_query($query){
		// Check for the main query
    		if ( ! $query->is_main_query() )
        		return; 
    		if ( 2 != count( $query->query ) || ! isset( $query->query['page'] ) )
        		return;
 
    		if ( ! empty( $query->query['name'] ) )
        		$query->set( 'post_type', array( 'post', 'videos', 'page' ) );
	}
}
endif;

add_action('plugins_loaded',array('WPVPMediaEncoder','init'));
register_activation_hook( __FILE__, array('WPVPMediaEncoder', 'wpvp_create_video_post_flush_rewrite_rules' ) );
register_deactivation_hook( __FILE__, array('WPVPMediaEncoder', 'wpvp_flush_rewrite_rules' ) );
?>
