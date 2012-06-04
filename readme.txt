=== WP Video Posts ===
Contributors: AlexRayan, cmstactics
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J535UTFPCXFQC
Tags: video converter, video plugin, ffmpeg, video post
Requires at least: 3.2.1
Tested up to: 3.3.1
Stable tag: 1.0

Upload videos into your custom video posts. FFMPEG must be installed to encode
and create splash image. Supports FLV, F4V, MP4, AVI, MOV, 3GP and WMV
formats.

== Description ==

WP Video Posts will enable you to create custom Video posts, upload and insert
videos into these posts. All uploaded video files will be converted into MP4
format to enhance the performance so that it loads and plays fast.  It creates
snapshot of a specified frame at a specific time to create the splash image
for the video. This plugin enables WordPress the ability to allow the
following formats to be uploaded as well as convert them to the finalized MP4
format for playing; FLV, F4V, MP4, AVI, MOV, 3GP and WMV formats.

In addition, WP Video Posts allows the embed of Youtube and Vimeo videos with
the use of the following shortcodes:

Youtube:
[wpvp_embed type=youtube video_code=vAFQIciWsF4 width=560 height=315]

Vimeo:
[wpvp_embed type=vimeo video_code=23117398 width=500 height=281]

In order for WP Video Posts to work properly following requirements need to be present on your server.

- Server should support **ffmpeg**
- PHP needs to be compiled with **ffmpeg-PHP extension**

== Installation ==

- Upload the wp_video_posts.zip file into `/wp-content/plugins/` directory
- Activate the plugin through the 'Plugins' menu in WordPress
- Under 'Settings'->'WP Video Posts' customize options for width and height
  of a video and a thumb, as well as the frame (in seconds) the thumb should be generated from
- Under 'Settings'->'Permalinks' click Save to refresh your permalink
  structure.

== Frequently Asked Questions ==

Q: I get a "Page not found" error when I view my new video post. Why is this
happening? 
A: With any new custom post type being registered with WordPress, the permalinks need to be updated.  The solution is go to the 'Settings'->'Permalinks' and save your current links structure again. 

Q: I'm running WordPress multisite and I get the message that says something about the file type not being supported.  How do I fix that?
A: If you are using WordPress multisite, then you need to manually list the type of video formats to allow for upload.  This is done by logging in to the wp-admin, and going to 'My Site' => 'Network Admin', then click on 'Settings' => 'Network Settings'.
Scroll down to the Upload Settings section of the network settings page and add the format in the Upload file types list.

= What pre-requirements do I need to install this plugin? =

You must Install ffmpeg on your server and recompile PHP with ffmpeg-PHP.  

= What happen if I dont have ffmpeg in my server? =

If you do not have ffmpeg support on your server, this plugin will simply ignore the conversion and proceed with the rest of the process.  

== Screenshots ==

1. Video Posts page displaying your video posts.

2. WP Video Posts, edit post page.

3. WP Video Posts Options page.

== Changelog ==

= 1.0 =
Initial release

== Arbitrary section ==

== A brief Markdown Example ==

