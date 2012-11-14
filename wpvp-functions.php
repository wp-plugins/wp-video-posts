<?php
		
//Used to send debug messages to either the log file or to an email
function wpvp_dump( $data ) {
      // This is my Dumper function...
      	//return error_log( date ( 'r -> ', time() ) . print_r($data,true) . "\n" , 3, "debug_encoder_new.log");
	//return error_log( date ( 'r -> ', time() ) . print_r($data, true) . "\n" , 1, "wpvp@cmstactics.com");
	return $data;
}

function ffmpegCommandExistsCheck($command) {
    $command = escapeshellarg($command);
    $exists = exec($command." -h",$out);
    return sizeof($out);
}

function wpvp_get_options(){
	$wpvp_options = array();
	$video_width = get_option('wpvp_video_width','640');
	$video_height = get_option('wpvp_video_height','360');
	$thumb_width = get_option('wpvp_thumb_width','640');
	$thumb_height = get_option('wpvp_thumb_height','360');
	$capture_image = get_option('wpvp_capture_image','5');	

	//$category_list = get_option('post_category');
	$wpvp_options['video_width']=$video_width;
	$wpvp_options['video_height']=$video_height;
	$wpvp_options['thumb_width']=$thumb_width;
	$wpvp_options['thumb_height']=$thumb_height;
	$wpvp_options['capture_image']=$capture_image;
	//$wpvp_options['category_list']=$category_list;
	return $wpvp_options;
}

function encode($ID,$front_end_postID=NULL) {
	//	wpvp_dump('Running encode function...');
		global $encodeFormat, $shortCode;
		$options = wpvp_get_options();

		$width = $options['video_width'];
		$height = $options['video_height'];
		//$category = $options['category_list'];
		
		// What format should we convert to?
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

                // Will get the attachment details (we can access the members individually)
                $postDetails = get_post($ID); 
		//wpvp_dump('ID that is passed '.$ID);


                // We only will do something if the uploaded attachment is a video...
		if(is_video($postDetails->post_mime_type)=='video') {

	                global $wpdb;

                        // Get the PATH where videos should be uploaded (Works with BuddyPress/Multisite too)
		        $NewPath = get_option('upload_path');

			// Is NewPath empty? then lets assign a default...
			if (!$NewPath)
				$NewPath = 'wp-content/uploads';

                        // get_post_meta will access the post metadata, we need the actual ID and the field we want.
                        $attached_file = get_post_meta($ID,'_wp_attached_file',true);

			// Get the FULL path to the ORIGINAL file
                        $dirnameGet = get_home_path() . $NewPath;
			$originalFileUrl = $dirnameGet . '/' . $attached_file;

			// Hack to get the directory portion...
			$fileDetails = pathinfo($attached_file);	

			// What is the path used for all file downloads?
	        		$GuidPath = get_option('fileupload_url');

			// Is GuidPath empty? then lets assign a default...
			if (!$GuidPath)
				$GuidPath = get_option('upload_url_path');

			// Still empty?
			if (!$GuidPath)
				$GuidPath = get_option('siteurl') . '/' . $NewPath;

			// We are about to enter a loop to normalize the file name and make sure its not a duplicate
			$fileFound = true;
			$i='';

			// This is the actual loop
			while($fileFound) {
				if ($fileDetails['dirname'] == '.')
					$fname = $fileDetails['filename'].$i;						
				else
					$fname = $fileDetails['dirname'] . '/' . $fileDetails['filename'].$i;	

				$newFile = $dirnameGet .'/'.$fname.$extension;
				$guid = $GuidPath . '/' . $fname.$extension;
				$newFileTB = $dirnameGet .'/'.$fname.$thumbfmt;
				$guidTB = $GuidPath . '/' . $fname.$thumbfmt;
				
				if (ffmpegCommandExistsCheck("ffmpeg")>0){
					$file_encoded = 1;
					if(file_exists($newFile))
						$i = $i=='' ? 1 : $i+1;			
					else
						$fileFound = false;
				}	
				else{
					$file_encoded = 0;
					$fileFound = false;
				}
			}

			//wpvp_dump('Converting from ' . $originalFileUrl);
			//wpvp_dump('Converting to ' . $newFile);

			// Call our ffmpeg function to thumb the video...
			thumb ( $originalFileUrl, $newFileTB );

			// Call our ffmpeg encoder function
			convert ( $originalFileUrl, $newFile, $encodeFormat );
	
			if($file_encoded) {	
				// We call the pathinfo function on the FULL path to the NEW file so we can access elements.
				$NewfileDetails = pathinfo($newFile);
                                $NewTmbDetails  = pathinfo($newFileTB);
			}
			else{
				$guidTB = plugins_url('/images/', __FILE__).'default_image.jpg';
				$newFile = $originalFileUrl;
				$NewTmbDetails['basename']='default_image.jpg';
				$NewfileDetails['basename']='default_image.jpg';	
			}

				// To display the player automatically...
				$shortCode  = '[wpvp_flowplayer src='.$guid.' width='.$width.' height='.$height.' splash='.$guidTB.']';
				
				// We inherit by default from
				$VideopostID = 0;

				// Get the categories list in case the plugin is made to execute in an automated way
				//$categories = $options['category_list'];

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

  				$Videopost 			= array();
				$Videopost['post_content']    	= $newContent;
				//$Videopost['post_category']	= $categories;
				$Videopost['ID']		= $postID;
				$updatedPost = wp_update_post($Videopost);	
				//wpvp_dump('updatedpost id or error '.$updatedPost);
								
				// We add a video attachment post 
  				$my_NEWpost 			= array();
				$my_NEWpost['post_title']   	= $NewTmbDetails['basename'];
				$my_NEWpost['post_status']  	= 'inherit';
				$my_NEWpost['post_type']    	= 'attachment';
				$my_NEWpost['post_parent']    	= $updatedPost;
  				$my_NEWpost['guid']         	= $guidTB;
  				$my_NEWpost['post_mime_type']  	= $mime_tmb;
				$newVideoPost               	= wp_insert_post( $my_NEWpost );
				
				if($file_encoded){
					if ($VideopostID and $newVideoPost)
						add_post_meta($VideopostID, '_thumbnail_id', $newVideoPost);
					// We update the meta_data (postmeta table)
					if ($fileDetails['dirname'] == '.') {
					   	update_post_meta($ID, '_wp_attached_file', $NewfileDetails['basename']);
				   		update_post_meta($newVideoPost, '_wp_attached_file', $NewTmbDetails['basename']);
					}
					else { 
                               	   		update_post_meta($ID, '_wp_attached_file', $fileDetails['dirname'] . '/' . $NewfileDetails['basename']);
                               	   		update_post_meta($newVideoPost, '_wp_attached_file', $NewTmbDetails['dirname'] . '/' . $NewTmbDetails['basename']);
					}
					// We update the actual post main data (posts table)
        	                        $my_post = array();
                                	if ($newVideoPost) {
                                        	$my_post['ID'] = $ID;
                                        	$my_post['post_title'] = $NewfileDetails['basename'];
                                	        $my_post['guid'] = $guid;
                        	                $my_post['post_parent'] = $VideopostID;
                	                        $my_post['post_mime_type'] = $mime_type;
	                                        wp_update_post( $my_post );
        	                        }

					unlink($originalFileUrl);
				}
				if($newVideoPost==0){
                                        return false;
                                } else {
                                        return $newVideoPost;
                                }
		}
}

function thumb ( $source, $target ) {
	// Here we thumbnail our video
	//wpvp_dump('Running thumb function...');
	$options = wpvp_get_options();

        $width = $options['thumb_width'];
        $height = $options['thumb_height'];
        $capture_image = $options['capture_image'];

        $extra = '-vframes 1 -s '.$width.'x'.$height.' -ss '.$capture_image.' -f image2';
	$str = "ffmpeg -y -i ".$source." ". $extra ." ".$target;
	//wpvp_dump($str);
	return exec($str);
}

function wpvp_insert_video_into_post($html, $id, $attachment){
        $options = wpvp_get_options();
        $width = $options['video_width'];
        $height = $options['video_height'];
        $attachmentID = $id;
        $content = $html;
        $attachmentObj = get_post($attachmentID);
        if(is_video($attachmentObj->post_mime_type)=='video'){
        	$postParentID = $attachmentObj->post_parent;
                $postParentObj = get_post($postParentID);
                if($attachmentID>$postID){
                //Newly Uploaded Video
                	$postContent = $postParentObj->post_content;
                        $content = $postContent;
                } else {
                //Video From Media Library
                        $src = wp_get_attachment_url($attachmentID);
                        $attachments = get_posts(array('post_type'=>'attachment','posts_per_page'=>-1,'post_parent'=>$postParentID,'post_mime_type'=>'image/jpeg'));
                        if($attachments){
                		$imgAttachmentID = $attachments[0]->ID;
                                $imgAttachment = wp_get_attachment_url($imgAttachmentID);
                       	} else{
                        	$imgAttachment = plugins_url('/images/', __FILE__).'default_image.jpg';
                       	}
                        $content  = '[wpvp_flowplayer src='.$src.' width='.$width.' height='.$height.' splash='.$imgAttachment.']';
               	}
        } //Check post mime type = video
        return $content;
}

function convert ( $source, $target, $format ) {
	// Here we call ffmpeg

	//wpvp_dump('Running convert function...');
        global $encodeFormat;
	$options = wpvp_get_options();

        $width = $options['video_width'];
        $height = $options['video_height'];

	//wpvp_dump('convert width and height '.$width.' '.$height.'...');
	
        $extra = "-s ${width}x${height} -ar 44100 -b 384k -ac 2 ";

	if ($encodeFormat=='mp4') {
        	$extra .= "-acodec libfaac -vcodec libx264 -f mp4 -vtag avc1 -vpre normal -refs 1 -coder 1 -level 31 -threads 8 -partitions parti4x4+parti8x8+partp4x4+partp8x8+partb8x8 -flags +mv4 -trellis 1 -cmp 256 -me_range 16 -sc_threshold 40 -i_qfactor 0.71 -bf 0 -g 250";				
	}

	$str = "ffmpeg -y -i ".$source." $extra ".$target;
	//wpvp_dump($str);
	exec($str);

	$prepare = "MP4Box -inter 100  ".$target;
	exec($prepare);	

	return 1;
}

function is_video ($mime_type) {
	$type = explode("/", $mime_type);
	return strtolower($type[0]);
}

function guess_file_type ($filename) {
	return strtolower(array_pop(explode('.',$filename)));
}

function attachment_save ($data) {
	if( is_video($data['post_mime_type'])=='video' ) {
	  //      wpvp_dump($data);
		$parent_post = $data['post_parent'];
		$newdata = array (
			ID	     	=> $parent_post,
			post_excerpt 	=> $data['post_content'],
			post_title 	=> $data['post_title'],
			tags_input 	=> $data['post_excerpt']
		);
		wp_update_post( $newdata );	
		return $data;
	}
	else {
		return $data;
	}
}

function attachment_edit ($data) {
	$ext = guess_file_type($data['post_title']['value']);
	if ($ext=='flv') {
	//	wpvp_dump($data);
		$data['post_excerpt']['label'] = 'Tags';
		$data['post_excerpt']['helps'] = 'Separate tags with commas';
		return $data;
	}
	else {
		return $data;
	}
}

function wpvp_video_upload_mime_types($existing_mimes){
	$existing_mimes['mov'] = 'video/quicktime';
	$existing_mimes['avi'] = 'video/avi';
	$existing_mimes['wmv|wvx|wm|wmx'] = 'video/x-ms-wmv';
	$existing_mimes['3gp|3gpp|3gpp2|3g2'] = 'video/3gpp';
	return $existing_mimes;
}

function wpvp_video_embed($video_code,$width,$height,$type){
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

function wpvp_widget_latest_posts($instance){
	if($instance['width']==''){
		$instance['width'] = '165';
	}
	if($instance['height']==''){
		$instance['height']='125';
	}
	if($instance['num_posts']==''){
        	$instance['num_posts']='-1';
	}
	if($instance['display']==''){
		$instance['display']='v';
	}
	if(!empty($instance['cat_checkbox'])){
		$num = count($instance['cat_checkbox']);
		$x = 0;
		foreach($instance['cat_checkbox'] as $categories){
			$cat_list .= $categories;
			if($x<$num){
				$cat_list .= ',';
			}	
			$x++;
		}
	}
	$args = array(
        	'post_type' => 'videos',
	        'post_status' => 'publish',
        	'numberposts' => $instance['num_posts'],
		'category'=>$cat_list
	);
	global $post;
	$posts = get_posts($args);
	foreach($posts as $post): setup_postdata($post); 
		$video_meta_array = get_post_meta($post->ID, 'wpvp_video_code', false);	
		//print_r($video_meta_array);
		$video_meta = array_pop($video_meta_array);
		if($instance['display']=='v'){
			$class = ' wpvp_widget_vert';
			$style = 'width:'.$instance['width'].'px';	
		}
		else if($instance['display']=='h'){
			$class = ' wpvp_widget_horiz';
			$style = 'width:'.$instance['width'].'px';
		}
		if(($instance['display_type']=='th')||($instance['display_type']=='')){
			if(is_numeric($video_meta)){
				$vimeo_hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/$video_meta.php"));
				$video_img = $vimeo_hash[0]['thumbnail_medium'];
			}
			else if(preg_match('/[a-zA-Z0-9_-]{11}/',$video_meta)){ 
				$video_img = "http://img.youtube.com/vi/".$video_meta."/1.jpg";
			}
			else if($video_meta==''){
				$video_img_attrs = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), array($instance['width'],$instance['height']));
				$video_img = $video_img_attrs[0];
				if($video_img==''){
					$video_img = plugins_url('/images/', __FILE__).'default_image.jpg';
				}
			}
			$video_item .= '<div class="wpvp_video_item'.$class.'" style="'.$style.'"><a href="'.get_permalink().'"><img src="'.$video_img.'" width="'.$instance['width'].'" height="'.$instance['height'].'" /></a>';
		} else if($instance['display_type']=='p'){
			if(is_numeric($video_meta)){
				$video_player = '<iframe width="'.$instance['width'].'" height="'.$instance['height'].'" src="http://player.vimeo.com/video/'.$video_meta.'" webkitAllowFullScreen mozallowfullscreen allowFullScreen frameborder="0"></iframe>';
			}
			else if(preg_match('/[a-zA-Z0-9_-]{11}/',$video_meta)){
				$video_player = '<iframe width="'.$instance['width'].'" height="'.$instance['height'].'" src="http://www.youtube.com/embed/'.$video_meta.'" frameborder="0" allowfullscreen></iframe>';
			}
			else if($video_meta==''){
				$video_meta_array = get_post_meta($post->ID, 'wpvp_fp_code',false);
				$video_meta = array_pop($video_meta_array);
				$video_data_array = json_decode($video_meta,true);
				$src = $video_data_array['src'];
				$splash = $video_data_array['splash'];
				//print_r(' src: '.$src.'splash:'.$splash);
				$video_player = '<a href="'.$src.'" class="myPlayer" style="display:block;width:'.$instance['width'].'px;height:'.$instance['height'].'px;"></a>';
				//<img width="'.$instance['width'].'" height="'.$instance['height'].'" src="'.$splash.'" alt="" /></a>';
			}
			$video_item .= '<div class="wpvp_video_item'.$class.'" style="'.$style.'">'.$video_player;
		}
		if($instance['post_title']!=''){
        	        $video_item .= '<div class="wpvp_video_title"><a class="wpvp_title" href="'.get_permalink().'">'.get_the_title().'</a></div>';
        	}
		if($instance['author']!=''){
			$video_item .= '<span class="wpvp_author">'.get_the_author().'</span>';
		}
		if($instance['excerpt']!=''){
			$video_item .= '<br /><span class="wpvp_excerpt">'.get_the_excerpt().'</span>';
		}
		$video_item .= '</div>';
	endforeach;
	echo $video_item;
return;
}


/* Front End for Uploading Videos */

function wpvp_insert_init_post($data,$file){
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
        $postID = wp_insert_post($post,$wp_error);
        if($wp_error){
                print_r($wp_error);
        } else{
                //echo $postID;
        }
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
                        $encodedVideoPost = encode($id,$postID);
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


function wpvp_front_video_uploader(){

	$upload_size_unit = $max_upload_size = wpvp_max_upload_size();
        $error_vid_type = false;
	$video_limit =  wpvp_return_bytes(ini_get( 'upload_max_filesize' ));
        if(isset($_POST['wpvp-upload'])){
                //print_r($_FILES['async-upload']);
                //$video_types = array('video/quicktime','video/mpeg','video/mp4','video/ogg','video/webm','video/x-matroska','video/x-ms-wmv','video/x-flv','video/3gpp','video/3gp','video/3gpp2','video/3gp2','video/avi','application/octet-stream', 'video/webm');
		$default_ext = array('video/mpeg','video/mp4');
		$video_types = get_option('wpvp_allowed_extensions',$default_ext);
		if($video_types==''){
			$video_types = $default_ext;
		}

                if(in_array($_FILES['async-upload']['type'],$video_types)){
                        $video_post = wpvp_insert_init_post($_POST,$_FILES);
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
        } 
	
	if(wpvp_is_allowed()) { ?>

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
                        <div class="wpvp_upload_progress" style="display:none;"><img class="wpvp_progress_gif" src="<?php echo plugins_url('images/upload_progress.gif',__FILE__);?>" /><?php _e('Please, wait while your video is being uploaded.');?></div>
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
			<?php 	$hide_tags = get_option('wpvp_uploader_tags','');
				if($hide_tags==''){ ?>
                        <div class="wpvp_tag" style="float:right;width:50%;text-align:right;">
                                <label><?php _e('Tags (comma separated)');?></label>
                                <input type="text" name="wpvp_tags" value="<?php echo $_POST['wpvp_tags'];?>" />
                        </div>
			<?php 	} ?>
                        <?php wp_nonce_field('client-file-upload', 'client-file-upload'); ?>
                </div>
                <p class="wpvp_submit_block">
                        <input type="submit" name="wpvp-upload" value="Upload" />
                </p>
                </form>
                <p class="wpvp_info"><span>*</span> = <?php _e('Required fields');?></p>
<?php
        } else { //Display insufficient priveleges message
                $denial_message = get_option('wpvp_denial_message');
		if(!$denial_message || $denial_message == "")
			echo '<h2>Sorry, you do not have sufficient privileges to use this feature</h2>';
		else
                        echo '<h2>'.$denial_message.'</h2>';
                        
        }
}

function wpvp_front_video_editor(){
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
                        $post_content = explode(']',$postObj->post_content);
                        $video_shortcode = $post_content[0].']';
                        $video_content = $post_content[1];
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
                                }
                        }
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
				<?php 	} ?>
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

function wpvp_is_allowed() {
        //Bet User Privileges Options
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
		                $current_user_role = wpvp_get_current_user_role();
                		if(in_array($current_user_role, $allowed_user_roles))
		                        return true;
                		else 
		                        return false;
			}
		}
        }
}

function wpvp_get_current_user_role() {
        global $wp_roles;
        $current_user = wp_get_current_user();
        $roles = $current_user->roles;
        $role = array_shift($roles);
        return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role]) : false;
}

function wpvp_max_upload_size() {
         $max_upload = (int)(ini_get('upload_max_filesize'));
         $max_post = (int)(ini_get('post_max_size'));
         $memory_limit = (int)(ini_get('memory_limit'));
         $upload_mb = min($max_upload, $max_post, $memory_limit);
         return $upload_mb."MB";
}

function wpvp_return_bytes($val) {
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
?>
