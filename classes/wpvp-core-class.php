<?php 
class WPVP_Encode_Media{
	protected $options;
	function __construct($options=array()){
		$default = array(
			'api_key'=>'',
			'video_width'=>640,
			'video_height'=>360,
			'thumb_width'=>640,
			'thumb_height'=>360,
			'caption_image'=>5,
			'ffmpeg_path'=>'',
			'wpvp_ffmpeg_ar'=>44100,
			'wpvp_ffmpeg_b_a'=>384,
			'wpvp_ffmpeg_b_v'=>384,
			'wpvp_ffmpeg_ac'=>2,
			'wpvp_ffmpeg_acodec'=>'libfdk_aac',
			'wpvp_ffmpeg_vcodec'=>'libx264',
			'wpvp_ffmpeg_vpre'=>'none',
			'wpvp_ffmpeg_other_flags'=>0
		);
		foreach ($default as $key => $value)
			$this->options[$key] = key_exists($key,$options) ? $options[$key] : $value;
	}
	/**
	*main function to process encoding via ffmpeg for the front end uploader and the backend
	*@access public
	*/
	public function wpvp_encode($ID,$front_end_postID=NULL){
		global $encodeFormat, $shortCode;
		$helper = new WPVP_Helper();
        $options = $helper->wpvp_get_full_options();
		if($helper->wpvp_command_exists_check($this->options['ffmpeg_path']."ffmpeg")>0){
			$ffmpeg_exists = true;
		} else {
			$ffmpeg_exists = false;
		}
		$width = $options['video_width'];
		$height = $options['video_height'];
		$ffmpeg_path = $options['ffmpeg_path'];
		$debug_mode = ($options['debug_mode']=='yes') ? true : false;
		$allowed_ext = array('mp4','flv');

		$encodeFormat = 'mp4'; // Other formats will be available soon...
		// Handle various formats options here...
		if ($encodeFormat=='flash') {
			$extension = '.flv'; 
			$mime_type = 'video/x-flv';
			$thumbfmt  = '.jpg';
			$mime_tmb  = 'image/jpeg';
		}
		else if ($encodeFormat=='mp4') {
            $extension = '.mp4';
            $mime_type = 'video/mp4';
            $thumbfmt  = '.jpg';
            $mime_tmb  = 'image/jpeg';
		}
		//Get the attachment details (we can access the items individually)
        $postDetails = get_post($ID);
		//check if attachment is video
		if($helper->is_video($postDetails->post_mime_type)=='video') {
			//path to upload videos (multisite/BuddyPress compatable)
		        $NewPath = get_option('upload_path');
			//path is empty? Lets assign the default one
			if (!$NewPath)
				$NewPath = 'wp-content/uploads';
			//get_post_meta gets ID and the field
            $attached_file = get_post_meta($ID,'_wp_attached_file',true);
			//get the path to the ORIGINAL file
            $dirnameGet = get_home_path() . $NewPath;
			$originalFileUrl = $dirnameGet . '/' . $attached_file;	
			$fileDetails = pathinfo($attached_file);
			$fileExtension = $fileDetails['extension'];
			//check if ffmpeg exists and if video extension is allowed
			if(!in_array($fileExtension,$allowed_ext)&&!$ffmpeg_exists){
				//do not proceed
				if($debug_mode){
                    $helper->wpvp_dump('No FFMPEG found. Only mp4 and flv extensions are supported. The currently uploaded extension ('.$fileExtension.') is not supported. Please encode the file manually and reupload.');
                }
				return;
			} else {
				//debug_mode is true
				if($debug_mode){
					$helper->wpvp_dump('Initial file details...');
					$helper->wpvp_dump($fileDetails);
				}	
				$guidPath = get_option('fileupload_url');
				//if guidPath is empty, lets lets assign a default
				if (!$guidPath)
					$guidPath = get_option('upload_url_path');
				if (!$guidPath)
					$guidPath = get_option('siteurl') . '/' . $NewPath;
				//normalize the file name and make sure its not a duplicate
				$fileFound = true;
				$i = '';
				while($fileFound){
					if ($fileDetails['dirname'] == '.'){
						$fname = $fileDetails['filename'].$i;
	                } else {
        	            $fname = $fileDetails['dirname'] . '/' . $fileDetails['filename'].$i;
					}
	        		$newFile = $dirnameGet .'/'.$fname.$extension;
		        	$guid = $guidPath . '/' . $fname.$extension;
                	$newFileTB = $dirnameGet .'/'.$fname.$thumbfmt;
	        	    $guidTB = $guidPath . '/' . $fname.$thumbfmt;
			        if ($ffmpeg_exists){
        		      	$file_encoded = 1;
							if(file_exists($newFile))
        	        		    $i = $i=='' ? 1 : $i+1;
	                        else
	                        	$fileFound = false;
		           	} else{
                		$file_encoded = 0;
		        		$fileFound = false;
                	}	
				}//while fileFound ends
				//debug_mode is true
                if($debug_mode){
        	        $helper->wpvp_dump('New files path on the server: video and image ...');
                    $helper->wpvp_dump('video: '.$newFile);
	                $helper->wpvp_dump('image: '.$newFileTB);
					$helper->wpvp_dump('New files url on the server: video and image ...');
        	        $helper->wpvp_dump('video: '.$guid);
                    $helper->wpvp_dump('image: '.$guidTB);
	            }
				if($file_encoded) {
					if($debug_mode){
						$helper->wpvp_dump('FFMPEG found on the server. Encoding initializing...');
					}
					//ffmpeg to get a thumb from the video
					$this->wpvp_convert_thumb($originalFileUrl,$newFileTB);
					//ffmpeg to convert video
					$this->wpvp_convert_video($originalFileUrl, $newFile, $encodeFormat);
					//pathinfo on the FULL path to the NEW file
					$NewfileDetails = pathinfo($newFile);
                	$NewTmbDetails  = pathinfo($newFileTB);
					if($debug_mode){
						if(!file_exists($newFile)){
							$helper->wpvp_dump('Video file was not converted. Possible reasons: missing libraries for ffmpeg, permissions on the directory where the file is being written to...');
						} else {
							$helper->wpvp_dump('Video was converted: '.$newFile);
						}
						if(!file_exists($newFileTB)){
        	                $helper->wpvp_dump('Thumbnail was not created. Possible reasons: missing libraries for ffmpeg, permissions on the directory where the file is being written to...');
                	    } else {
							$helper->wpvp_dump('Thumbnail was created: '.$newFileTB);
						}
					}
					//$guidTB = $newFileTB;
				} else {
					if($debug_mode){
						$helper->wpvp_dump('FFMPEG is not found on the server. Possible reasons: not installed, not properly configured, the path is not provided correctly in the plugin\'s options settings...');
					}
					$guidTB = plugins_url('/images/', dirname(__FILE__)).'default_image.jpg';
					$newFile = $originalFileUrl;
					$NewTmbDetails['basename']='default_image.jpg';
					$NewfileDetails['basename']='default_image.jpg';
				} //no ffmpeg - no encoding
				//shortcode for the flowplayer
				$shortCode  = '[wpvp_flowplayer src='.$guid.' width='.$width.' height='.$height.' splash='.$guidTB.']';	
				//inherit by default from the $_POST
				$VideopostID = 0;
				//update the auto created post with our data
				if(empty($front_end_postID)){
					$postID = intval($_REQUEST['post_id']);
				} else {
					$postID = $front_end_postID;
				}
				$VideopostID = $postID;
				$postObj = get_post($VideopostID);
                $currentContent = $postObj->post_content;
				$newContent = $shortCode.' '.$currentContent;
				$Videopost = array();
				$Videopost['post_content']=$newContent;
				$Videopost['ID'] = $postID;
				//update video post with a shortcode inserted in the content
				$updatedPost = wp_update_post($Videopost);
				//add a video attachment
				$my_NEWpost 			= array();
				$my_NEWpost['post_title']   	= $NewTmbDetails['basename'];
				$my_NEWpost['post_status']  	= 'inherit';
				$my_NEWpost['post_type']    	= 'attachment';
				$my_NEWpost['post_parent']    	= $updatedPost;
  				$my_NEWpost['guid']         	= $guidTB;
	  			$my_NEWpost['post_mime_type']  	= $mime_tmb;
				$newThumbnailPost               = wp_insert_post( $my_NEWpost );
				//if the file is encoded, add meta data
				if($file_encoded){
					if ($VideopostID && $newThumbnailPost)
						add_post_meta($VideopostID, '_thumbnail_id', $newThumbnailPost);
					if ($fileDetails['dirname'] == '.') {
						update_post_meta($ID, '_wp_attached_file', $NewfileDetails['basename']);
						update_post_meta($newThumbnailPost, '_wp_attached_file', $NewTmbDetails['basename']);
					} else {
						update_post_meta($ID, '_wp_attached_file', $fileDetails['dirname'] . '/' . $NewfileDetails['basename']);
                        update_post_meta($newThumbnailPost, '_wp_attached_file', $NewTmbDetails['dirname'] . '/' . $NewTmbDetails['basename']);
					}
					$my_post = array();
					if ($newThumbnailPost) {
                	    $my_post['ID'] = $ID;
                        $my_post['post_title'] = $NewfileDetails['basename'];
	                    $my_post['guid'] = $guid;
                	    $my_post['post_parent'] = $VideopostID;
        	        	$my_post['post_mime_type'] = $mime_type;
	                    wp_update_post( $my_post );
        	        }
					//delete the original file 
					unlink($originalFileUrl);
					//rename($newFile,$originalFileUrl);
				}
				if($newThumbnailPost==0){
					return false;
	            } else {
        	       	return $newThumbnailPost;
				}
			}//ffmpeg and uploaded extension is supported
		}//if uploaded attachment is a video
	}
	/**
	*get a thumbnail from the video file with ffmeg
	*@access protected
	*/
	protected function wpvp_convert_thumb($source,$target){
		$width = $this->options['thumb_width'];
		$height = $this->options['thumb_height'];
		$capture_image = $this->options['capture_image'];
		$ffmpeg_path = $this->options['ffmpeg_path'];
		$dimensions = ($width!=''&&$height!='') ? '-s '.$width.'x'.$height : '';
		$capture_image = $capture_image ? $capture_image : 5;
		$extra = '-vframes 1 '.$dimensions.' -ss '.$capture_image.' -f image2';
		$str = $ffmpeg_path."ffmpeg -y -i ".$source." ". $extra ." ".$target;
		return exec($str);
	}
	/**
	*convert video to a specified format (currently, mp4 only)
	*@access protected
	*/
	protected function wpvp_convert_video($source,$target,$format){
		global $encodeFormat;
		$helper = new WPVP_Helper();
		$helper->wpvp_dump($this->options);
		$width = $this->options['video_width'];
		$height = $this->options['video_height'];
		$ffmpeg_path = $this->options['ffmpeg_path'];
		$dimensions = ($width!=''&&$height!='') ? ' -s '.$width.'x'.$height : '';
		
		$ffmpeg_ar=$this->options['wpvp_ffmpeg_ar'];
		$ffmpeg_b_a=$this->options['wpvp_ffmpeg_b_a'];
		$ffmpeg_b_v=$this->options['wpvp_ffmpeg_b_v'];
		$ffmpeg_ac=$this->options['wpvp_ffmpeg_ac'];
		$ffmpeg_acodec=$this->options['wpvp_ffmpeg_acodec'];
		$ffmpeg_vcodec=$this->options['wpvp_ffmpeg_vcodec'];
		$ffmpeg_vpre=$this->options['wpvp_ffmpeg_vpre'];
		$ffmpeg_other_flags=$this->options['wpvp_ffmpeg_other_flags'];
		
		$extra = $dimentions." ";
		if($ffmpeg_ar!='')
			$extra.=' -ar '.$ffmpeg_ar;
		if($ffmpeg_b_a!='')
			$extra.=' -b:a '.$ffmpeg_b_a.'k';
		if($ffmpeg_b_v!='')
			$extra.=' -b:v '.$ffmpeg_b_v.'k';
		if($ffmpeg_ac!='')
			$extra.=' -ac '.$ffmpeg_ac;
		if($ffmpeg_acodec!='')
			$extra.=' -acodec '.$ffmpeg_acodec;
		if($ffmpeg_vcodec!='')
			$extra.=' -vcodec '.$ffmpeg_vcodec;
		if($ffmpeg_vpre)
			$extra.=' -vpre '.$ffmpeg_vpre;
		if($ffmpeg_other_flags!=0)
			$extra.= " -refs 1 -coder 1 -level 31 -threads 8 -partitions parti4x4+parti8x8+partp4x4+partp8x8+partb8x8 -flags +mv4 -trellis 1 -cmp 256 -me_range 16 -sc_threshold 40 -i_qfactor 0.71 -bf 0 -g 250";
		$str = $ffmpeg_path."ffmpeg -i ".$source." $extra ".$target;
		$helper = new WPVP_Helper();
		$helper->wpvp_dump($str);
		exec($str);
		//check for the file. If not created, attempt to execute a simplier command
		if(!file_exists($target)){
			exec($ffmpeg_path."ffmpeg -i ".$source.$dimensions." ".$ffmpeg_acodec." ".$ffmpeg_vcodec." ".$target);
		}
		//in case of MP4Box is installed, execute command to move the video data to the front
		$prepare = "MP4Box -inter 100  ".$target;
		exec($prepare);
		return 1;
	}
	/**
	*insert short code into the video post
	*@access public
	*/
	public function wpvp_insert_video_into_post($html, $id, $attachment){
		$helper = new WPVP_Helper();
	    $width = $this->options['video_width'];
		$height = $this->options['video_height'];
		if($helper->wpvp_command_exists_check($this->options['ffmpeg_path']."ffmpeg")>0){
            $ffmpeg_exists = true;
        } else {
            $ffmpeg_exists = false;
        }
	    $attachmentID = $id;
        $content = $html;
	    $attachmentObj = get_post($attachmentID);
		$allowed_ext = array('mp4','flv');
      	if($helper->is_video($attachmentObj->post_mime_type)=='video'){
	        $postParentID = $attachmentObj->post_parent;
            $postParentObj = get_post($postParentID);
			$attachmentURI = wp_get_attachment_url($attachmentID);
			$attachmentPathInfo = pathinfo($attachmentURI);
			$attachExt = $attachmentPathInfo['extension'];
			//check for allowed extensions without ffmpeg
			if(!in_array($attachExt,$allowed_ext)&&!$ffmpeg_exists){
				/*if($attachmentID>$postParentID){
	            //Newly Uploaded Video
					$postContent = $postParentObj->post_content;
					$content = $postContent;
				} else { */
				$content = 'WPVP_ERROR: FFMPEG is not found on the server. Allowed extensions for the upload are mp4 and flv. Please convert the video and reupload.';
			} else {
	            //Video with attachment from Media Library
        	    $src = wp_get_attachment_url($attachmentID);
	            $attachments = get_posts(array('post_type'=>'attachment','posts_per_page'=>-1,'post_parent'=>$postParentID,'post_mime_type'=>'image/jpeg'));
                if($attachments){
					$imgAttachmentID = $attachments[0]->ID;
        	        $imgAttachment = wp_get_attachment_url($imgAttachmentID);
	            } else{
					$imgAttachment = plugins_url('/images/', dirname(__FILE__)).'default_image.jpg';
                }
                $content = '[wpvp_flowplayer src='.$src.' width='.$width.' height='.$height.' splash='.$imgAttachment.']';
				//}
			}
	    } //Check post mime type = video
        return $content;
	}
	/**
	*embed video from YouTube or Vimeo
	*@access public
	*/
	public function wpvp_video_embed($video_code,$width,$height,$type){
		if($type){
			if($video_code){
				if($type=='youtube'){
					$embedCode = '<iframe width="'.$width.'" height="'.$height.'" src="http://www.youtube.com/embed/'.$video_code.'" frameborder="0" allowfullscreen></iframe>';
				}
				elseif($type=='vimeo'){
					$embedCode = '<iframe width="'.$width.'" height="'.$height.'" src="http://player.vimeo.com/video/'.$video_code.'" webkitAllowFullScreen mozallowfullscreen allowFullScreen frameborder="0"></iframe>';
				}
				$result = $embedCode;
			}
			else{
				$result = '<span style="color:red;">'._e('No video code is found').'</span>';
			}
		}
		else{
			$result = '<span style="color:red;">'._e('The video source is either not set or is not supported').'.</span>';
		}
		return $result;
	}
	/**
	*display widget for video posts
	*@access public
	*/
	public function wpvp_widget_latest_posts($instance){
		$width = $instance['width'] ? $instance['width'] : 165;
		$height = $instance['height'] ? $instance['height'] : 125;
		$num_posts= $instance['num_posts'] ? $instance['num_posts'] : '-1';
		$display = $instance['display'] ? $instance['display'] : 'v';
		$display_type = $instance['display_type'] ? $instance['display_type'] : 'th';
		$post_title = $instance['post_title'] ? $instance['post_title'] : '';
		$author = $instance['author'] ? $instance['author'] : '';
		$excerpt = $instance['excerpt'] ? $instance['excerpt'] : '';
		$excerpt_length = $instance['excerpt_length'] ? $instance['excerpt_length'] : 10;
		if(!empty($instance['cat_checkbox'])){
			$category__in = $instance['cat_checkbox'];
		}
		$args = array(
        		'post_type' => 'videos',
		        'post_status' => 'publish',
        		'posts_per_page' => $num_posts,
			'category__in'=>$category__in
		);
		$vid_posts = new WP_Query($args);
		while($vid_posts->have_posts()):
			$vid_posts->the_post();
			$postID = get_the_ID();
			$video_meta_array = get_post_meta($postID, 'wpvp_video_code', false);
			$video_meta = array_pop($video_meta_array);
			$video_fp_meta_array = get_post_meta($postID, 'wpvp_fp_code',false);
			if(!empty($video_meta_array)||!empty($video_fp_meta_array)){
				if($display=='v'){
					$class = ' wpvp_widget_vert';
					$style = 'width:'.$width.'px';	
				}
				else if($display=='h'){
					$class = ' wpvp_widget_horiz';
					$style = 'width:'.$width.'px';
				}
				if(($display_type=='th')||($display_type=='')){
					if(is_numeric($video_meta)){
						$vimeo_hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/$video_meta.php"));
						$video_img = $vimeo_hash[0]['thumbnail_medium'];
					}
					else if(preg_match('/[a-zA-Z0-9_-]{11}/',$video_meta)){ 
						$video_img = "http://img.youtube.com/vi/".$video_meta."/1.jpg";
					}
					else if($video_meta==''){
						$video_img_attrs = wp_get_attachment_image_src(get_post_thumbnail_id($postID), array($instance['width'],$instance['height']));
						$video_img = $video_img_attrs[0];
						if($video_img==''){
							$video_img = plugins_url('/images/', dirname(__FILE__)).'default_image.jpg';
						}
					}
					$video_item .= '<div class="wpvp_video_item'.$class.'" style="'.$style.'"><a href="'.get_permalink().'"><img src="'.$video_img.'" width="'.$width.'" height="'.$height.'" /></a>';
				} else if($display_type=='p'){
					if(is_numeric($video_meta)){
						//Vimeo code
						$video_player = '<iframe width="'.$width.'" height="'.$height.'" src="http://player.vimeo.com/video/'.$video_meta.'" webkitAllowFullScreen mozallowfullscreen allowFullScreen frameborder="0"></iframe>';
					}
					else if(preg_match('/[a-zA-Z0-9_-]{11}/',$video_meta)){
						//YouTube code
						$video_player = '<iframe width="'.$width.'" height="'.$height.'" src="http://www.youtube.com/embed/'.$video_meta.'" frameborder="0" allowfullscreen></iframe>';
					}
					else if($video_meta==''){
						//use flowplayer meta code instead
						$video_meta_array = $video_fp_meta_array;
						$video_meta = array_pop($video_meta_array);
						$video_data_array = json_decode($video_meta,true);
						$src = $video_data_array['src'];
						$splash = $video_data_array['splash'];
						$video_player = '<a href="'.$src.'" class="myPlayer" style="display:block;width:'.$width.'px;height:'.$height.'px;"></a>';
					}
					$video_item .= '<div class="wpvp_video_item'.$class.'" style="'.$style.'">'.$video_player;
				}
				if($post_title!=''){
        			        $video_item .= '<div class="wpvp_video_title"><a class="wpvp_title" href="'.get_permalink().'">'.get_the_title().'</a></div>';
	        		}
				if($author!=''){
					$video_item .= '<span class="wpvp_author">'.get_the_author().'</span>';
				}
				if($excerpt!=''){
					$ct = strip_shortcodes(get_the_content());
					$helper = new WPVP_Helper();
					$excerpt_string = $helper->wpvp_string_limit_words($ct,$excerpt_length);
					$video_item .= '<br /><span class="wpvp_excerpt">'.$excerpt_string.'</span>';
				}
				$video_item .= '</div>';
			}//check if video_meta is not empty
		endwhile;
		wp_reset_postdata();
		echo $video_item;
		return;
	}
	/* END OF CODE FOR UPLOAD FROM THE DASHBOARD AND BASIC FUNCTIONALITY */
	/* BEGINNING OF CODE FOR FRONT-END UPLOADER */
	/**
	*process front end uploading
	*@access public
	*/
	public function wpvp_front_video_uploader(){
		$helper = new WPVP_Helper(); 
	        $upload_size_unit = $max_upload_size = $helper->wpvp_max_upload_size();
        	$error_vid_type = false;
	        $video_limit =  $helper->wpvp_return_bytes(ini_get( 'upload_max_filesize' ));
        	if(isset($_POST['wpvp-upload'])){
                	$default_ext = array('video/mp4','video/x-flv');
	                $video_types = get_option('wpvp_allowed_extensions',$default_ext) ? get_option('wpvp_allowed_extensions',$default_ext) : $default_ext;
                	if(in_array($_FILES['async-upload']['type'],$video_types)){
                        	$video_post = $this->wpvp_insert_init_post($_POST,$_FILES);
	                        // send email notification to an admin
        	                $userObj = wp_get_current_user();
	                        $admin = get_bloginfo('admin_email');
        	                $subject = get_bloginfo('name').': New Video Submitted for Review';
                	        $headers = 'MIME-Version: 1.0' . "\r\n";
                        	$headers.= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	                        $message = 'New video uploaded for review on '.get_bloginfo('name').'. Moderate the <a href="'.get_bloginfo('url').'/?post_type=videos&p='.$video_post.'">uploaded video</a>.';
        	                $send_draft_notice = wp_mail($admin, $subject, $message, $headers);
                	} else if($_FILES['async-upload']['size']>$video_limit){
                        	$error_vid_type = true;
	                        $error_mgs = 'The file exceeds the maximum upload size.';
        	        }
                	else {
                        	$error_vid_type = true;
	                        $supported_ext = implode(', ',$video_types);
        	                $error_msg = 'The file is either not a video file or the extension is not supported.<br /> Currently supported extensions are: '.$supported_ext;
                	}
        	} // if wpvp-upload is in $_POST	
		$helper = new WPVP_Helper(); 
		if($helper->wpvp_is_allowed()) { ?>
                <script type="text/javascript">
                jQuery(document).ready(function(){
                        jQuery('form[name=wpvp-upload-video]').submit(function(){
                                if((!jQuery('#wpvp-upload-video input').val())||(!jQuery('textarea[name=wpvp_desc]').val())){
                                        if(!jQuery('input[name=async-upload]').val()){
                                                jQuery('.wpvp_file_error').html('No video is chosen');
                                        } else {
                                                jQuery('.wpvp_file_error').html('');
                                        }
                                        if(!jQuery('input[name=wpvp_title]').val()){
                                                jQuery('.wpvp_title_error').html('Title is missing');
                                        } else{
                                                jQuery('.wpvp_title_error').html('');
                                        }
                                        if(!jQuery('textarea[name=wpvp_desc]').val()){
                                                jQuery('.wpvp_desc_error').html('Description is missing');
                                        } else{
                                                jQuery('.wpvp_desc_error').html('');
                                        }
                                        if(window.fileSize>'<?php echo $video_limit;?>'){
                                                jQuery('.wpvp_file_error').html('Video size exceeds allowed <?php echo ini_get( 'upload_max_filesize' );?>.');
                                                return false;
                                        } else{
                                                jQuery('.wpvp_file_error').html('');
                                        }
                                        return false;
                                } else{
                                        jQuery('.wpvp_file_error').html();
                                        jQuery('.wpvp_title_error').html();
                                        jQuery('.wpvp_desc_error').html();
                                        wpvp_progressBar();
                                }
                        });
                });
		</script>
                <?php if($error_vid_type){ echo '<p style="color:red;font-style:italic;font-size:11px;">'.$error_msg.'</p>';}?>
                <form id="wpvp-upload-video" enctype="multipart/form-data" name="wpvp-upload-video" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <div class="wpvp_block">
                        <label><?php printf( __( 'Choose Video (Max Size of %s):' ), esc_html($upload_size_unit) ); ?><span>*</span></label>
                        <!--<input type="file" name="wpvp_file" value="<?php //echo $_FILE['wpvp_file'];?>" />-->
                        <input type="file" id="async-upload" name="async-upload" />
                        <div class="wpvp_upload_progress" style="display:none;"><img class="wpvp_progress_gif" src="<?php echo plugins_url('images/upload_progress.gif',dirname(__FILE__));?>" /><?php _e('Please, wait while your video is being uploaded.');?></div>
                        <div class="wpvp_file_error wpvp_error"></div>
                </div>
                <div class="wpvp_block">
                        <label><?php _e('Title');?><span>*</span></label>
                        <input type="text" name="wpvp_title" value="<?php echo $_POST['wpvp_title'];?>" />
                        <div class="wpvp_title_error wpvp_error"></div>
                </div>
                <div class="wpvp_block">
                        <label><?php _e('Description');?><span>*</span></label>
                        <textarea name="wpvp_desc"><?php echo $_POST['wpvp_desc'];?></textarea>
                        <div class="wpvp_desc_error wpvp_error"></div>
                </div>
                <div class="wpvp_block">
			<div class="wpvp_cat" style="float:left;width:50%;">
                                <label><?php _e('Choose category');?></label>
                                <select name="wpvp_category">
                        <?php
                                $wpvp_uploader_cats = get_option('wpvp_uploader_cats','');
                                if($wpvp_uploader_cats==''){
                                        $uploader_cats = '';
                                } else {
                                        $uploader_cats = implode(", ",$wpvp_uploader_cats);
                                }
                                $args = array('hide_empty'=>0,'include'=>$uploader_cats);
                                $categories = get_categories($args);
                                foreach($categories as $category){
                                        $options .= '<option ';
                                        $options .= ' value="'.$category->term_id.'">';
                                        $options .= $category->cat_name.'</option>';
                                }
                                echo $options;
                        ?>
                                </select>
                        </div>
                        <?php   $hide_tags = get_option('wpvp_uploader_tags','');
                                if($hide_tags==''){ ?>
                        <div class="wpvp_tag" style="float:right;width:50%;text-align:right;">
                                <label><?php _e('Tags (comma separated)');?></label>
                                <input type="text" name="wpvp_tags" value="<?php echo $_POST['wpvp_tags'];?>" />
                        </div>
                        <?php   } ?>
                        <?php wp_nonce_field('client-file-upload', 'client-file-upload'); ?>
                </div>
                <p class="wpvp_submit_block">
                        <input type="submit" name="wpvp-upload" value="Upload" />
                </p>
                </form>
                <p class="wpvp_info"><span>*</span> = <?php _e('Required fields');?></p>
	<?php 	} else { //Display insufficient priveleges message
                	$denial_message = get_option('wpvp_denial_message');
                	if(!$denial_message || $denial_message == "")
                        	echo '<h2>Sorry, you do not have sufficient privileges to use this feature</h2>';
                	else
	                        echo '<h2>'.$denial_message.'</h2>';

        	}
	}
	/**
	*font end video edit processing
	*@access public
	*/
	public function wpvp_front_video_editor(){
	        if($_REQUEST['video']!=''){
                	//get current user id and check if the video belongs to that user
        	        $curr_user = wp_get_current_user();
	                $user_id = $curr_user->ID;
	                //get post Object based on post id
        	        $post_id = $_GET['video'];
                	$postObj = get_post($post_id);
	                $post_author = $postObj->post_author;
        	        if(!current_user_can('administrator')&&$user_id!=$post_author){
                	        return 'Cheating, huh?!';
	                } else{
				$shortcode_part = explode('[wpvp_flowplayer ',$postObj->post_content);
        	               	$post_content = explode(']',$shortcode_part[1]);
                	       	$video_shortcode = '[wpvp_flowplayer '.$post_content[0].']';
                        	//$video_content = $post_content[1];
				$video_content = strip_shortcodes($postObj->post_content);
	                        if(isset($_POST['wpvp-update'])){
        	                        $post_title = $_POST['wpvp_title'];
                	                $post_desc = $_POST['wpvp_desc'];
                        	        $post_cat = $_POST['wpvp_category'];
                                	$tags_list = $_POST['wpvp_tags'];
	                                $video_post_id = $_GET['video'];
        	                        // check if we still have post id in $_GET
                	                if($video_post_id!=''){
                        	                if($tags_list!=''){
                                	                $tags = explode(',',strtolower($tags_list));
                                        	}
	                                        $post = array(
        	                                        'ID'=>$video_post_id,
                	                                'post_title'=>$post_title,
                        	                        'post_type'=>'videos',
                                	                'post_content'=>$video_shortcode.' '.$post_desc
                                        	);
	                                        $update_post = wp_update_post($post);
        	                                if($update_post){
                	                                wp_set_post_categories($update_post,array($post_cat));
                        	                        if($tags_list!=''){
                                	                        wp_set_object_terms($update_post,$tags,'post_tag');
                                        	        } else{
                                                	        wp_set_object_terms($update_post,'','post_tag');
	                                                }
        	                                        $msg = '<span style="color:green;">Video record is successfully updated.</span>';
                	                        } else{
                        	                        $msg = '<span style="color:red;">Something went wrong.</span>';
                                	        }
                                	} //video post id check
                        	} // check for form submission
				$video_title = $postObj->post_title;
                        	$post_tags = wp_get_post_tags($post_id);
	                        if(!empty($post_tags)){
        	                        $tag_count = count($post_tags);
                	                $tags_list = array();
                        	        foreach($post_tags as $key=>$tag){
                                	        $tags_list[]=$tag->name;
                                	}
	                                $tags_list = implode(', ',$tags_list);
        	                }
                	        $post_category = wp_get_post_categories($post_id);
                        	$post_cat = $post_category[0];
?>
                        <?php if($msg){ echo $msg;}?>
                        <form id="wpvp-update-video" enctype="multipart/form-data" name="wpvp-update-video" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                <div class="wpvp_block">
                                        <?php echo do_shortcode($video_shortcode);?>
                                </div>
                                <div class="wpvp_block">
                                        <label><?php _e('Title');?><span>*</span></label>
                                        <input type="text" name="wpvp_title" value="<?php if($_POST['wpvp_title']) {echo $_POST['wpvp_title'];} else{ echo $video_title;}?>" />
                                        <div class="wpvp_title_error wpvp_error"></div>
                                </div>
                                <div class="wpvp_block">
                                        <label><?php _e('Description');?><span>*</span></label>
                                        <textarea name="wpvp_desc"><?php if($_POST['wpvp_desc']){ echo $_POST['wpvp_desc'];} else{ echo $video_content;};?></textarea>
                                        <div class="wpvp_desc_error wpvp_error"></div>
                                </div>
                                <div class="wpvp_block">
                                <div class="wpvp_cat" style="float:left;width:50%;">
                                 <?php
                                $wpvp_uploader_cats = get_option('wpvp_uploader_cats','');
                                if($wpvp_uploader_cats==''){
                                        $uploader_cats = '';
                                } else {
                                        $uploader_cats = implode(", ",$wpvp_uploader_cats);
                                }
                                ?>
                                        <label><?php _e('Choose category');?></label>
                                        <select name="wpvp_category">
                        <?php
                                $args = array('hide_empty'=>0,'include'=>$uploader_cats);
                                $categories = get_categories($args);
                                foreach($categories as $category){
                                        if($post_cat==$category->term_id){
                                                $selected = ' selected="selected"';
                                        } else { $selected = '';}
                                        $options .= '<option ';
                                        $options .= ' value="'.$category->term_id.'"'.$selected.'>';
                                        $options .= $category->cat_name.'</option>';
                                }
                                echo $options;
                        ?>
                                        </select>
                                </div>
				<?php   $hide_tags = get_option('wpvp_uploader_tags','');
                                if($hide_tags==''){ ?>
                                <div class="wpvp_tag" style="float:right;width:50%;text-align:right;">
                                        <label><?php _e('Tags (comma separated)');?></label>
                                        <input type="text" name="wpvp_tags" value="<?php if($_POST['wpvp_tags']) {echo $_POST['wpvp_tags'];} else { echo $tags_list; }?>" />
                                </div>
                                <?php   } ?>
                        </div>
                        <p class="wpvp_submit_block">
                                <input type="submit" name="wpvp-update" value="Save Changes" />
                        </p>
                </form>
                <p class="wpvp_info"><span>*</span> = <?php _e('Required fields');?></p>
        	<?php   }
	?>
	<?php   } else{
	                return 'Cheating, huh?!';
                	exit;
        	}
	}	
	/**
	*insert a new video post from the front end uploader
	*@access protected
	*/
	protected function wpvp_insert_init_post($data,$file){
		$helper = new WPVP_Helper();
        	if($data['wpvp_category']=='0'){
                	$data['wpvp_category']='1';
        	}
        	$wpvp_post_status = get_option('wpvp_default_post_status','pending');
        	$post = array(
	                'comment_status' => 'open',
        	        'post_author' => $logged_in_user,
                	'post_category' => array($data['wpvp_category']),
	                'post_content' => $data['wpvp_desc'],
        	        'post_title' => $data['wpvp_title'],
                	'post_type' => 'videos',
	                'post_status' => $wpvp_post_status,
        	        'tags_input' => $data['wpvp_tags']
        	);
                //'post_status' => 'pending',
        	$postID = wp_insert_post($post);
        	if ( !empty( $file ) ) {
        	        require_once(ABSPATH . 'wp-admin/includes/admin.php');
	                $upload_overrides = array( 'test_form' => FALSE );
                	$id = media_handle_upload('async-upload', 0,$upload_overrides); //post id of Client Files page
        	        unset($file);
	                if ( is_wp_error($id) ) {
                        	$errors['upload_error'] = $id;
                        	$id = false;
                	}
                	if ($errors) {
                        	return $errors;
                	} else {
                        	$encodedVideoPost = $this->wpvp_encode($id,$postID);
                        	if(!$encodedVideoPost){
                                	$msg = _e('There was an error creating a video post.');
                        	} else{
                	                $msg = _e('Successfully uploaded. You will be redirected in 5 seconds.');
        	                        echo '<script type="text/javascript"> jQuery(window).load(function(){ jQuery("#wpvp-upload-video").css("display","none"); setTimeout(function(){ window.location.href="'.get_permalink($postID).'"},5000);}); </script> '._e('If you are not redirected in 5 seconds, go to ').'<a href="'.get_permalink($postID).'">uploaded video</a>.';
	                        }
                        	return $postID;
                	}
        	}
	}	
}
?>
