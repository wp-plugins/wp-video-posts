<?php
		
//Used to send debug messages to either the log file or to an email
function dump_encoder( $data ) {
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

function encode($ID) {
	//	dump_encoder('Running encode function...');
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
		//dump_encoder('ID that is passed '.$ID);


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

			//dump_encoder('Converting from ' . $originalFileUrl);
			//dump_encoder('Converting to ' . $newFile);

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
				$guidTB = plugins_url('/images/', __FILE__).'default_image.png';
				$newFile = $originalFileUrl;
				$NewTmbDetails['basename']='default_image.png';
				$NewfileDetails['basename']='default_image.png';	
			}

				// To display the player automatically...
				$shortCode  = '[wpvp_flowplayer src='.$guid.' width='.$width.' height='.$height.' splash='.$guidTB.']';
				
				// We inherit by default from
				$VideopostID = 0;

				// Get the categories list in case the plugin is made to execute in an automated way
				//$categories = $options['category_list'];

				//update the auto created post with our data
				$postID = intval($_REQUEST['post_id']);
				$VideopostID = $postID;

  				$Videopost 			= array();
				$Videopost['post_content']    	= $shortCode;
				//$Videopost['post_category']	= $categories;
				$Videopost['ID']		= $postID;
				$updatedPost = wp_update_post($Videopost);	
				//dump_encoder('updatedpost id or error '.$updatedPost);
								
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
		}
}

function thumb ( $source, $target ) {
	// Here we thumbnail our video
	//dump_encoder('Running thumb function...');
	$options = wpvp_get_options();

        $width = $options['thumb_width'];
        $height = $options['thumb_height'];
        $capture_image = $options['capture_image'];

        $extra = '-vframes 1 -s '.$width.'x'.$height.' -ss '.$capture_image.' -f image2';
	$str = "ffmpeg -y -i ".$source." ". $extra ." ".$target;
	//dump_encoder($str);
	return exec($str);
}

function wpvp_insert_video_into_post($html, $id, $attachment){
	$attachment = get_post($id);
	$postParentID = $attachment->post_parent;
	$postParent = get_post($postParentID);
	$postContent = $postParent->post_content;
	if(is_video($attachment->post_mime_type)=='video'){
		$src = wp_get_attachment_url($id);
		//dump_encoder('Got into insert function with postContent: '.$postContent);
	}
	return $postContent;
}

function convert ( $source, $target, $format ) {
	// Here we call ffmpeg

	//dump_encoder('Running convert function...');
        global $encodeFormat;
	$options = wpvp_get_options();

        $width = $options['video_width'];
        $height = $options['video_height'];

	//dump_encoder('convert width and height '.$width.' '.$height.'...');
	
        $extra = "-s ${width}x${height} -ar 44100 -b 384k -ac 2 ";

	if ($encodeFormat=='mp4') {
        	$extra .= "-acodec libfaac -vcodec libx264 -f mp4 -vtag avc1 -vpre normal -refs 1 -coder 1 -level 31 -threads 8 -partitions parti4x4+parti8x8+partp4x4+partp8x8+partb8x8 -flags +4mv -trellis 1 -cmp 256 -me_range 16 -sc_threshold 40 -i_qfactor 0.71 -bf 0 -g 250";				
	}

	$str = "/usr/bin/ffmpeg -y -i ".$source." $extra ".$target;
	//dump_encoder($str);
	exec($str);

	$prepare = "/usr/local/bin/MP4Box -inter 100  ".$target;
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
	  //      dump_encoder($data);
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
	//	dump_encoder($data);
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
	$existing_mimes['f4v|f4p'] = 'video/mp4';
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
			return $embedCode;
		}
		else{
			return '<span style="color:red;">No video code is found.</span>';
		}
	}
	else{
		return '<span style="color:red;">The type is either not set or is not supported.</span>';
	}
}
?>
