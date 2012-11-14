<?php 
//extend widget class
class WPVideosForPostsWidget extends WP_Widget
{
        function WPVideosForPostsWidget(){
                $widget_ops = array(    'classname' => 'wp-videos-posts',
                                        'description' => 'Displays the embedded videos from YouTube or Vimeo');
                $this->WP_Widget('WPVideosForPostsWidget','WP Videos Posts',$widget_ops);
        }
        function form($instance){
                $instance = wp_parse_args((array) $instance, array('title'=> '','width'=>'','cat_checkbox'=>'','num_posts'=>'','display'=>'','display_type'=>'','post_title'=>'','author'=>'','excerpt'=>''));
                $title = $instance['title'];
		$width = $instance['width'];
		$height = $instance['height'];
		$categories = $instance['cat_checkbox'];
		$num_posts = $instance['num_posts'];
		$display = $instance['display'];
		$display_type = $instance['display_type'];
		$post_title = $instance['post_title'];
		$author = $instance['author'];
		$excerpt = $instance['excerpt'];
		?>
                <p>
                        <label for="<?php echo $this->get_field_id('title');?>">
                        <?php _e('Title:');?>
                        </label>
			<?php 
		//	print_r($instance);
			?>
                        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" />
			<p>
			<label for="<?php echo $this->get_field_id('width');?>">
			<?php _e('Width of a video item (px):');?>
			</label>
                        <input style="width:60px;" width="40" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo attribute_escape($width); ?>" />
			</p>
			<p>
			<label for="<?php echo $this->get_field_id('height');?>">
                        <?php _e('Height of a video item (px):');?>
                        </label>
                        <input style="width:60px;" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo attribute_escape($height); ?>" />
			</p>
			<p>
			<label for="<?php echo $this->get_field_id('num_posts');?>">
                        <?php _e('Number of posts:');?>
                        </label>
			<input style="width:60px;" id="<?php echo $this->get_field_id('num_posts'); ?>" name="<?php echo $this->get_field_name('num_posts'); ?>" type="text" value="<?php echo attribute_escape($num_posts); ?>" />
			</p>	
			<p>
			<label for="<?php echo $this->get_field_id('display_type');?>">
                        <b><?php _e('Display Options:')?></b>
                        </label>
			</p>
			<p>
			<label for="<?php echo $this->get_field_id('display');?>">
                        <?php _e('Layout:');?>
			</label>
			<input type="radio" name="<?php echo $this->get_field_name('display');?>" value="v" <?php if($instance['display']=='v'){ echo 'checked="checked"'; }?>/><?php _e('Vertical');?>
			<input type="radio" name="<?php echo $this->get_field_name('display');?>" value="h" <?php if($instance['display']=='h'){ echo 'checked="checked"'; }?>/><?php _e('Horizontal');?>
			</p>
			<p>
			<label><?php _e('Display type:');?></label>
                        <input type="radio" name="<?php echo $this->get_field_name('display_type');?>" value="p" <?php if($instance['display_type']=='p'){ echo 'checked="checked"'; }?>/><?php _e('Player');?>
                        <input type="radio" name="<?php echo $this->get_field_name('display_type');?>" value="th" <?php if($instance['display_type']=='th'){ echo 'checked="checked"'; }?>/><?php _e('Thumbnails');?>
			</p>
			<p>
                        <input type="checkbox" name="<?php echo $this->get_field_name('post_title');?>" value="post_title" <?php if($instance['post_title']=='post_title'){ echo 'checked="checked"';};?>/> <?php _e('Display Post Title');?>
                        </p>
			<p>
			<input type="checkbox" name="<?php echo $this->get_field_name('author');?>" value="author" <?php if($instance['author']=='author'){ echo 'checked="checked"';};?>/> <?php _e('Display Author');?>
			</p>
			<p>
                        <input type="checkbox" name="<?php echo $this->get_field_name('excerpt');?>" value="excerpt" <?php if($instance['excerpt']=='excerpt'){ echo 'checked="checked"';};?>/> <?php _e('Display Excerpt');?>
                        </p>
			<p>
                        <strong><?php _e("Categories to display from: " ); ?></strong><br />
			<div style="height:145px;overflow:auto;">
                        <ul style="list-style-type:none;">
                        <?php
				$args = array('hide_empty'=>0);
				$categories = get_categories($args);
				foreach($categories as $category){
					$options .= '<li><input type="checkbox" id="'.$this->get_field_id('cat_checkbox').'[]" name="'.$this->get_field_name('cat_checkbox').'[]"';
					if(is_array($instance['cat_checkbox'])){
						foreach($instance['cat_checkbox'] as $cats){
							if($cats == $category->term_id){
								$options .= ' checked = "checked"';
							}
						}
					}
					$options .= ' value="'.$category->term_id.'" /> ';
					$options .= $category->cat_name.'</li>';
				}
				echo $options;
                        ?>
                        </ul>
			</div>
                	</p>
                </p>
<?php
        }
        function update($new_instance, $old_instace){
                $instance = $old_instance;
                $instance['title'] = $new_instance['title'];
		$instance['width'] = $new_instance['width'];
		$instance['height'] = $new_instance['height'];
		$instance['cat_checkbox'] = $new_instance['cat_checkbox'];
		$instance['num_posts'] = $new_instance['num_posts'];
		$instance['display'] = $new_instance['display'];
		$instance['display_type'] = $new_instance['display_type'];
		$instance['post_title']= $new_instance['post_title'];
		$instance['author']=$new_instance['author'];
		$instance['excerpt']=$new_instance['excerpt'];
                return $instance;
        }
        function widget($args, $instance){
                extract($args, EXTR_SKIP);
                echo $before_widget;
                $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
		$width = empty($instance['width']) ? ' ' : apply_filters('widget_title', $instance['width']);
		$height = empty($instance['height']) ? ' ' : apply_filters('widget_title', $instance['height']);
		$categories = empty($instance['post_category']) ? ' ' : apply_filters('widget_title', $instance['post_category']);
		$num_posts = empty($instance['num_posts']) ? ' ' : apply_filters('widget_title', $instance['num_posts']);
		$display = empty($instance['display']) ? ' ' : apply_filters('widget_title', $instance['display']);
		$display_type = empty($instance['display_type']) ? ' ' : apply_filters('widget_title', $instance['display_type']);
		$display_type = empty($instance['post_title']) ? ' ' : apply_filters('widget_title', $instance['post_title']);
		$display_type = empty($instance['author']) ?  ' ' : apply_filters('widget_title', $instance['author']);
		$display_type = empty($instance['excerpt']) ?  ' ' : apply_filters('widget_title', $instance['excerpt']);
                if(!empty($title))
                        echo $before_title .$title . $after_title;

        //widget code
                echo wpvp_widget_function($instance);
                echo $after_widget;
        }
}
//add_action('widgets_init', create_function('','return register_widget("WPVideosForPostsWidget");'));

function wpvp_widget_function($instance){
        require_once('wpvp-functions.php');
        $video_posts = wpvp_widget_latest_posts($instance);
        return $video_posts;
}
?>
