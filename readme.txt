=== WP Video Posts ===
Contributors: AlexRayan, cmstactics
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J535UTFPCXFQC
Tags: video converter, video plugin, ffmpeg, video post
Requires at least: 3.2.1
Tested up to: 3.4.2
Stable tag: 1.5

Upload videos to create custom video posts. With FFMPEG installed, it encodes
and creates splash image. Supports FLV, F4V, MP4, AVI, MOV, 3GP and WMV formats.

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

In order for all WP Video Posts features to work properly, then FFMPEG is required and need to be installed on your server.

- Server should support **ffmpeg**

If FFMPEG is not installed, then the plugin still allows you to upload videos to create custom posts, but the video is not encoded nor is a splash image created.  The default image will show as the splash image.

= Instructions =
1. After install, go to the Dashboard.

2. Hover over the Videos menu item and click on Add New Video in the submen for Videos.

3. Add a title to the Video Post.

4. Click the Upload/Insert icon above the post content editor.

5. In the media uploader pop up, add the video you want to attach to this video post.

6. After the video uploads, it will automatically be encoded.  After this process, the video attachment details open where you can modify the title, caption and description.

7. Once the details have been modified/added if you chose to add those details, click on the Insert into Post button.  It will then add a shortcode that will appear as the following: [wpvp_flowplayer src=http://yoursite.com/wp-content/uploads/2012/06/MyCar.mp4 width=640 height=360 splash=http://yoursite.com/wp-content/uploads/2012/06/MyCar.jpg] 

8. Add any other details in the post content and click the Publish button.

9. That is all.

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

Q: I'm using the plugin but I do not have FFMPEG installed on my server.  How can I create my own default splash image and have it display rather than the default image supplied with the plugin?
A: You can create an image with the dimensions you want and upload it to your server to override our default_image.png located in the /wp-content/plugins/wp-video-posts/images/ directory.

Q: How do I install FFMPEG if I have root access on my server?
A: If you have either CentOS, RedHat or Fedora you can follow these steps:
    1. First you would need to install the DAG RPM repositories which contains
    huge amount of rpm packages:

    For Linux 5 / x86_64 (please, Google commands for other versions of
    Linux):

    Type the following on the command line:
    rpm -Uhv http://apt.sw.be/redhat/el5/en/x86_64/rpmforge/RPMS//rpmforge-release-0.3.6-1.el5.rf.x86_64.rpm

    2. Type the following on the command line:
    yum install ffmpeg ffmpeg-devel

    3. Restart Apache.  An example for CentOS would be:
    Type the following on the command line:
    sudo /etc/init.d/httpd restart

Q: I have ffmpeg on the server but encoding of the video doesn't work for me
A: Check what version of ffmpeg is installed. We usually recommend FFmpeg version 0.6.5 (currently, the latest stable) with the following configuration:

configuration: --prefix=/usr --enable-gpl --enable-libopencore-amrnb
--enable-libopencore-amrwb --enable-libx264 --enable-version3 --enable-libfaac
--enable-nonfree

To check the codecs supported, type the following on the command line:

ffmpeg -codecs | less

Make sure libfaac and libx264 are among the codecs available for you.

= What pre-requirements do I need to install this plugin? =

You must Install ffmpeg on your server and recompile PHP with ffmpeg-PHP.  

= What happen if I dont have ffmpeg in my server? =

If you do not have ffmpeg support on your server, this plugin will simply ignore the conversion and proceed with the rest of the process.  The supported file formats without ffmpeg installed would only be FLV and MP4.  In addition, the splash image will not be generated either and the default image will be displayed.

== Screenshots ==

1. Video Posts page displaying your video posts.

2. WP Video Posts, edit post page.

3. WP Video Posts Options page.

4. WP Video Posts Widet Options.

5. WP Video Posts Widget in action.

6. WP Video Posts Front End Uploader.

== Changelog ==
= 1.5 =
- Added front end video uploader and front end editor functionality to allow users and/or other bloggers of your site to upload videos without having to have access to the dashboard.
	- Control who is allowed to upload videos from the front end.
	- Choose the default post status for videos uploaded from the front end.
	- Choose whether users who upload videos receive a notification email once the video has been published.  Only works for video posts who are defaulted to Draft or Pending Review statuses.
	- Choose which categories uploaded videos are allowed to be saved into.
	- Filter extension types which are allowed to be uploaded.
- Added more features in the WP Video Posts Options for the administrator.
- Added support for post tags.
- Cleaned up CSS and Javascript.

= 1.4 =
- Removed hardcoded path for FFMPEG where we thought was standard but it varies among various hosting providers.
- Attempted to mitigate javascript conflict with other plugins and themes.

= 1.3 =
- Fixed conflict with media uploader for other plugins and post/page media uploader.
- Fixed bug with creating video posts with previously uploaded videos being inserted from Media Library.
- Changed default splash image to be a jpg instead of a png file since the splash image generated when FFMPEG is installed is output as a jpg file.  This allowed us to only have to check for the jpg mime type.
- Fixed bug for headers being sent message due to echoing flowplayer script include instead of enqueueing the script.

= 1.2 =
- Added a widget to the plugin to allow you to display your videos that were uploaded or from YouTube or Vimeo.
- The added widget can display your videos in a vertical or horizontal layout.  It also gives you the options to show the player or thumbnails that link to the video post.  See screenshot-4 for more options available in the widget.

= 1.1 =
- Added support for plugin for those without FFMPEG installed on their server.  Without FFMPEG installed, this plugin doesn't take advantage of all the features it offers.
- Added default splash image in case this plugin is used without FFMPEG.

= 1.0 =
Initial release

== Arbitrary section ==

== A brief Markdown Example ==

