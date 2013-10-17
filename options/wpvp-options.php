<?php 
$helper = new WPVP_Helper();
if($helper->wpvp_command_exists_check("ffmpeg")>0) {
	//FFMPEG is installed and found on the serverr
	$ffmpeg_installed = true;
} else {
	// No FFMPEG installed or found
	$ffmpeg_installed = false;
}
if($_POST['wpvp_hidden'] == 'Y') {
        //Form data sent
        $wpvp_width = $_POST['wpvp_video_width'];
	$wpvp_height = $_POST['wpvp_video_height'];
        $wpvp_thumb_width = $_POST['wpvp_thumb_width'];
	$wpvp_thumb_height = $_POST['wpvp_thumb_height'];
	$wpvp_capture_image = $_POST['wpvp_capture_image'];
	$wpvp_ffmpeg_path = $_POST['wpvp_ffmpeg_path'];
	$wpvp_main_loop_alter = $_POST['wpvp_main_loop_alter'];
	$wpvp_debug_mode = $_POST['wpvp_debug_mode'];
	
	update_option('wpvp_video_width', $wpvp_width);
	update_option('wpvp_video_height', $wpvp_height);
	update_option('wpvp_thumb_width', $wpvp_thumb_width);
	update_option('wpvp_thumb_height', $wpvp_thumb_height);
	update_option('wpvp_capture_image', $wpvp_capture_image);
	update_option('wpvp_ffmpeg_path', $wpvp_ffmpeg_path);
	update_option('wpvp_main_loop_alter', $wpvp_main_loop_alter);
	update_option('wpvp_debug_mode', $wpvp_debug_mode);
?>
<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
<?php
} else{
	$wpvp_width = get_option('wpvp_video_width',640) ? get_option('wpvp_video_width',640) : 640;
	$wpvp_height = get_option('wpvp_video_height',360) ? get_option('wpvp_video_height',360) : 360;
	$wpvp_thumb_width = get_option('wpvp_thumb_width',640) ? get_option('wpvp_thumb_width',640) : 640;
	$wpvp_thumb_height = get_option('wpvp_thumb_height',360) ? get_option('wpvp_thumb_height','360') : 360;
	$wpvp_capture_image = get_option('wpvp_capture_image',5)? get_option('wpvp_capture_image','5') : 5;
	$wpvp_ffmpeg_path = get_option('wpvp_ffmpeg_path');
	$wpvp_main_loop_alter = get_option('wpvp_main_loop_alter','yes') ? get_option('wpvp_main_loop_alter','yes') : 'yes';
	$wpvp_debug_mode = get_option('wpvp_debug_mode');
}
?>
<div class="wrap">
	<?php	echo "<h2>" . __( 'WP Video Posts - General Options' ) . "</h2>";?>

	<!-- PayPal Donate -->
	<?php echo "<h3>Please donate if you enjoy this plugin (WPVP):</h3>"; ?>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="J535UTFPCXFQC">
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>
	<hr>

	<?php 	if(!$ffmpeg_installed){
		echo '<h3 style="color: red;">FFMPEG is not installed on the server, therefore this plugin cannot function properly. The only extensions available for the upload will be mp4 and flv.<br />Please verify with your administrator or hosting provider to have this installed and configured. If ffmpeg is installed but you still see this message, specify the path to ffmpeg installation below:</h3><br />';
	} ?>	
	<form name="wpvp_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<input type="hidden" name="wpvp_hidden" value="Y">
		<p>
                        <strong><?php _e("Path to ffmpeg installation (optional): " ); ?></strong>
                        <input type="text" name="wpvp_ffmpeg_path" value="<?php echo $wpvp_ffmpeg_path; ?>" size="25" /> <?php _e("(example: /usr/local/bin/)"); ?>
                </p>
		<p>
                        <strong><?php _e("Converted video width: " ); ?></strong>
			<input type="text" name="wpvp_video_width" value="<?php echo $wpvp_width; ?>" size="5" /> <?php _e("(in pixels) Default 640px"); ?>
		</p>
		<p>
                        <strong><?php _e("Converted video height: " ); ?></strong>
                        <input type="text" name="wpvp_video_height" value="<?php echo $wpvp_height; ?>" size="5" /> <?php _e("(in pixels) Default 360px"); ?> 
                </p>
		<p>
                        <strong><?php _e("Converted video thumbnail width: " ); ?></strong>
                        <input type="text" name="wpvp_thumb_width" value="<?php echo $wpvp_thumb_width; ?>" size="5" /> <?php _e("(in pixels) Default 640px"); ?> 
                </p>
		<p>
                        <strong><?php _e("Converted video thumbnail height: " ); ?></strong>
                        <input type="text" name="wpvp_thumb_height" value="<?php echo $wpvp_thumb_height; ?>" size="5" /> <?php _e("(in pixels) Default 360px"); ?>
                </p>
		<p>
                        <strong><?php _e("Capture Splash Image: " ); ?></strong>
                        <input type="text" name="wpvp_capture_image" value="<?php echo $wpvp_capture_image; ?>" size="5" /> <?php _e("(in seconds) Default 5 seconds"); ?>
                </p>
		<p>
			<strong><?php _e('Video posts within the main loop (e.g. latest posts, tags, categories, etc.)');?></strong>
			<p><input type="checkbox" name="wpvp_main_loop_alter" value="yes"<?php if($wpvp_main_loop_alter=='yes'){ echo ' checked="checked"';}?>/> <?php _e('display the video posts');?></p>
		</p>
		<p>
			<strong><?php _e("Allowed video extensions for uploading");?></strong>
			<ul>
			<?php 
				$allowed = get_allowed_mime_types();
				foreach($allowed as $key=>$value){
					$t = explode('/',$value);
					if($t[0]=='video'){
						$types .= '<li>-'.$key.' ('.$value.')';
					}
				}	
				echo $types;
			?>
			</ul>
		</p>
		<p>
                        <strong><span style="color:red;"><?php _e('Debug Mode:');?></span></strong>
                        <p><input type="checkbox" name="wpvp_debug_mode" value="yes"<?php if($wpvp_debug_mode=='yes'){ echo ' checked="checked"';}?>/> <?php _e('enable (the debugging results will be written to /tmp/debug.log and when the file exists the contents will be displayed below)');?></p>
                </p>
		<p class="submit">
        	        <input type="submit" name="Submit" value="<?php _e('Update Options' ) ?>" />
	        </p>
        </form>
	<?php if($wpvp_debug_mode=='yes'&&file_exists('/tmp/debug.log')){
	?>
	<h3><?php _e('Contents of debug.log:');?></h3>
	<div style="height:400px;overflow:scroll;width:700px;" class="updated">
		<pre><?php echo file_get_contents('/tmp/debug.log');?></pre>
	</div>
	<?php	
	}?>
</div>
