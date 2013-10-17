<?php
/*Helper class*/ 
class WPVP_Helper{
	/**
	*check if ffmpeg extension is installed on the server
	*@access public
	*/
        public function wpvp_command_exists_check($command){
                $command = escapeshellarg($command);
                $exists = exec($command." -h",$out);
                return sizeof($out);
        }
	/**
	*get plugin\'s options and return them in array
	*@access public
	*/		
	public function wpvp_get_full_options(){
        	$wpvp_options = array();
		$default_ext = array('video/mpeg','video/mp4');	
	        $video_width = get_option('wpvp_video_width','640');
        	$video_height = get_option('wpvp_video_height','360');
	        $thumb_width = get_option('wpvp_thumb_width','640');
        	$thumb_height = get_option('wpvp_thumb_height','360');
	        $capture_image = get_option('wpvp_capture_image','5');
        	$ffmpeg_path = get_option('wpvp_ffmpeg_path','');
		$debug_mode = get_option('wpvp_debug_mode');
		$allowed_extensions = get_option('wpvp_allowed_extensions',$default_ext);
	        $wpvp_options['video_width']=$video_width;
        	$wpvp_options['video_height']=$video_height;
	        $wpvp_options['thumb_width']=$thumb_width;
        	$wpvp_options['thumb_height']=$thumb_height;
	        $wpvp_options['capture_image']=$capture_image;
        	$wpvp_options['ffmpeg_path']=$ffmpeg_path;
		$wpvp_options['debug_mode']=$debug_mode;
	        return $wpvp_options;
	}
	/**
        *call wpvp_dump($data) function for debugging
        *@access public
        */
        public function wpvp_dump($data){
                return error_log( date ( 'r -> ', time() ) . print_r($data,true) . "\n" , 3, "/tmp/debug.log");
        }
	/**
	*returns extension of the mime_type
	*@access public
	*/
        public function is_video($mime_type){
                $type = explode("/", $mime_type);
                return strtolower($type[0]);
        }
	/**
        *return file extension
        *@access public
        */
        public function guess_file_type ($filename) {
                return strtolower(array_pop(explode('.',$filename)));
        }
	/**
	*limit words in string
	*@access public
	*/
        public function wpvp_string_limit_words($string, $word_limit){
                $words = explode(' ', $string, ($word_limit + 1));
                if(count($words) > $word_limit)
                      array_pop($words);
                return implode(' ', $words);
        }
	/**
	*check if upload is allowed for this user role
	*@access public
	*/
        public function wpvp_is_allowed() {
                //get User Privileges Options
                $allow_guest = get_option('wpvp_allow_guest', 'yes');
                $allowed_user_roles = get_option('wpvp_uploader_roles');
                if(empty($allowed_user_roles) || !isset($allowed_user_roles))
                        $allowed_user_roles[0] = "Administrator";
                if($allow_guest == 'yes') {
                        return true;
                } else {
                        global $user_login;
                        if(!$user_login) {
                                return false;
                        } else {
                                if(is_array($allowed_user_roles)) {
                                        $current_user_role = $this->wpvp_get_current_user_role();
                                        if(in_array($current_user_role, $allowed_user_roles))
                                                return true;
                                        else
                                                return false;
                                }
                        } //user login check
                } //guess check
        }
	/**
	*check current user role
	*@access public
	*/
        protected function wpvp_get_current_user_role() {
                global $wp_roles;
                $current_user = wp_get_current_user();
                $roles = $current_user->roles;
                $role = array_shift($roles);
                return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role]) : false;
        }
	/**
	*check for max upload size based on php.ini settings
	*@access public
	*/
        public function wpvp_max_upload_size() {
                $max_upload = (int)(ini_get('upload_max_filesize'));
                $max_post = (int)(ini_get('post_max_size'));
                $memory_limit = (int)(ini_get('memory_limit'));
                $upload_mb = min($max_upload, $max_post, $memory_limit);
                return $upload_mb."MB";
        }
	/**
	*convert to bytes
	*@access public
	*/
        public function wpvp_return_bytes($val) {
                $val = trim($val);
                switch (strtolower(substr($val, -1))){
                        case 'm': $val = (int)substr($val, 0, -1) * 1048576; break;
                        case 'k': $val = (int)substr($val, 0, -1) * 1024; break;
                        case 'g': $val = (int)substr($val, 0, -1) * 1073741824; break;
                        case 'b':
                        switch (strtolower(substr($val, -2, 1))){
                                case 'm': $val = (int)substr($val, 0, -2) * 1048576; break;
                                case 'k': $val = (int)substr($val, 0, -2) * 1024; break;
                                case 'g': $val = (int)substr($val, 0, -2) * 1073741824; break;
                                default : break;
                        } break;
                        default: break;
                }
                return $val;
        }
	/**
        *function to add code to the post meta and update on post update if needed on publish_videos custom post type action hook
        *@access public
        */
        public function wpvp_video_code_add_meta($id){
		if($_POST['post_content']==''){
			$postObj = get_post($id);
			$post_content = $postObj->post_content;
			$post_type = $postObj->post_type;
		} else {
                	global $post;
	                $post_content = $_POST['post_content'];
			$post_type = $_POST['post_type'];
		}
                if(!wp_is_post_revision($id)){
                        $post_id = $id;
                        if($post_type== 'videos'){
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
}
?>
