<?php 
if (ffmpegCommandExists("ffmpeg")>0) {
	// FFMPEG Exists on server
        //echo "FFMPEG IS installed";
	$ffmpeg_installed = true;
} else {
	// No FFMPEG
        //echo "FFMPEG is NOT installed";
	$ffmpeg_installed = false;
}

function ffmpegCommandExists($command) {
    $command = escapeshellarg($command);
    $exists = exec($command." -h",$out);
    return sizeof($out);
}


if($_POST['wpvp_hidden'] == 'Y') {
        //Form data sent
        $wpvp_width = $_POST['wpvp_video_width'];
	$wpvp_height = $_POST['wpvp_video_height'];
        $wpvp_thumb_width = $_POST['wpvp_thumb_width'];
	$wpvp_thumb_height = $_POST['wpvp_thumb_height'];
	$wpvp_capture_image = $_POST['wpvp_capture_image'];
//	$wpvp_category_list = $_POST['post_category'];
	
	update_option('wpvp_video_width', $wpvp_width);
	update_option('wpvp_video_height', $wpvp_height);
	update_option('wpvp_thumb_width', $wpvp_thumb_width);
	update_option('wpvp_thumb_height', $wpvp_thumb_height);
	update_option('wpvp_capture_image', $wpvp_capture_image);
//	update_option('post_category', $wpvp_category_list);
?>
<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
<?php
} else{
	$wpvp_width = get_option('wpvp_video_width');
	$wpvp_height = get_option('wpvp_video_height');
	$wpvp_thumb_width = get_option('wpvp_thumb_width');
	$wpvp_thumb_height = get_option('wpvp_thumb_height');
	$wpvp_capture_image = get_option('wpvp_capture_image');
//	$wpvp_category_list = get_option('post_category');
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
		echo '<h3 style="color: red;">FFMPEG is not installed on the server, therefore this plugin cannot function properly.<br />Please verify with your administrator or hosting provider to have this installed and configured.</h3><br />';
	} ?>	
	<form name="wpvp_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<input type="hidden" name="wpvp_hidden" value="Y">
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
<?php /*You can uncomment it out to let the user choose categories to assign video posts to by default. It will be used in case the automated functionality is desired. Some changes have to be done to the code in wpvp-functions.php*/
/*
		<p>
                        <style>
                                ul.children{
                                        padding-left:15px;
                                }
                        </style>
                        <strong><?php _e("Choose Category(ies) to assign video posts to: " ); ?></strong><br />
                        <ul style="list-style-type:none;">
                        <?php
                                wp_category_checklist('','',$wpvp_category_list,'','','');
                        ?>
                        </ul>
                </p>
*/?>
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
		<p class="submit">
        	        <input type="submit" name="Submit" value="<?php _e('Update Options' ) ?>" />
	        </p>
        </form>
</div>
