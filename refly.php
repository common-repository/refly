<?php
/*
Plugin Name: Refly
Plugin URI: http://www.refly.it
Description: Publish and Refly your posts
Author: Refly
Version: 1.3
*/
global $plugin_url_path;

function pts_change_publish_button( $translation, $text ) {
	if ( $text == 'Publish' )
		return __('Publish & Refly','pts');
	return $translation;
}

function pts_change_update_button( $translation, $text ) {
	if ( $text == 'Update' )
		return __('Update & Refly','pts');
	return $translation;
}

$plugin_url_path = untrailingslashit( plugin_dir_url( __FILE__ ) );
$plugin_url_path = is_ssl() ? str_replace( 'http:', 'https:', $plugin_url_path ) : $plugin_url_path;

$refly_version = '1.3';

// Setup defaults if options do not exist
add_option('refly_data', '<img style="float:left;margin-right:6px;" src="'.$plugin_url_path.'/images/refly_logo.png" width="25" height="25" alt="Share with Refly" /><p>Share</p>');	// iconnature data
add_option('refly_sindex', FALSE);	// Show on index
add_option('refly_sposts', TRUE);	// Show on posts

function refly_add_option_pages() {
	if (function_exists('add_options_page')) {
		add_options_page("Refly", 'Refly', 8, __FILE__, 'refly_options_page');
	}		
}

function refly_trim_icon($icon) {
	return trim($icon, "*");
}

function refly_options_page() {

	global $plugin_url_path;
	global $refly_version;

	if (isset($_POST['set_defaults'])) {
		echo '<div id="message" class="updated fade"><p><strong>';
		update_option('refly_data', '<a href="http://refly.it"></a>');
		update_option('refly_sindex', FALSE);	// Show on index
		update_option('refly_sposts', TRUE);	// Show on posts
		echo 'Refly is now hidden';
		echo '</strong></p></div>';

	} else if (isset($_POST['info_update'])) {
		echo '<div id="message" class="updated fade"><p><strong>';
		update_option('refly_data', '<img style="float:left;margin-right:6px;" src="'.$plugin_url_path.'/images/refly_logo.png" width="25" height="25" alt="Share with Refly" /><p>Share</p>');
		update_option('refly_sindex', FALSE);
		update_option('refly_sposts', TRUE);

		echo 'Refly link will be displayed along with your posts!';
		echo '</strong></p></div>';
	} ?>

	<body style="background-color:#74828F;">
	<div class=wrap style="text-align:center;" >
	<br><br><br>
	<a href="http://refly.it"><img src="<?php echo $plugin_url_path;?>/images/refly.png" width="70" height="70"/></a>
	<h2 style="font-weight:bold;color:#fff;font-size:20px;">Refly v<?php echo $refly_version; ?></h2>
	<p style="color:#ddd;margin-top:-10px;">Souped Up Way To Share Your Contents<br />

	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<input type="hidden" name="info_update" id="info_update" value="true" />

	<br>
	
	<p style="color:#ddd;margin-bottom:-10px;">You can hide/show refly link here.</p>

	<div class="submit">
		<input type="submit" style="background:#eee;padding:10px;border:2px solid #ccc;-webkit-border-radius: 5px;border-radius: 5px;" 
		name="set_defaults" value="<?php _e('Hide Refly');?> " />

		<input type="submit" style="background:#eee;padding:10px;border:2px solid #ccc;-webkit-border-radius: 5px;border-radius: 5px;" 
		name="info_update" value="<?php _e('Show Refly'); ?> " />
	</div>

	</form>
	</div>
	</body>
	<?php
}

function refly_generate($content) {

	global $plugin_url_path;
	// strip p tags around html comments
	$content = preg_replace('/<p>\s*<!--(.*)-->\s*<\/p>/i', "<!--$1-->", $content);
	// Load options
	$refly_data = get_option('refly_data');
	$refly_sindex = get_option('refly_sindex');
	$refly_sposts = get_option('refly_sposts');

	// Check page type
	$show_icon = FALSE;

	if (is_home() && $refly_sindex) {
		$show_icon = TRUE;
	}
	if (is_single() && $refly_sposts) {
		$show_icon = TRUE;
	}
	$found = strpos ($content, '<!-- refly -->');
	if ($found) {
		$show_icon = TRUE;
	}
	if ($show_icon) {
		$the_icon = stripslashes(nl2br(refly_trim_icon($refly_data)));
		$postid = get_the_ID();
		$title = get_the_title();
		$url = get_permalink ($post = $postid);
		$content1 = get_post_field('post_content', $postid);
		$content1 = strip_shortcodes( $content1 );
		$result = substr($content1, 0, strlen($content1)-1);
		$result = strip_tags($result);
		if (strlen($content1) >= 180) {
			$result = substr($content1, 0, 180);
			$result = strip_tags($result);
		}

		$files = get_children('post_parent='.get_the_ID().'&post_type=attachment&post_mime_type=image');
		$thumb = "";
	    if($files) :
	        $keys = array_reverse(array_keys($files));
	        $j=0; $num = $keys[$j];
	        $image=wp_get_attachment_image($num, 'large', false);
	        $imagepieces = explode('"', $image);
	        $imagepath = $imagepieces[1];
	        $thumb=wp_get_attachment_thumb_url($num);
	    endif;
	    if ($thumb == "") {
	        	$thumb = "$plugin_url_path/images/wordpress.png";
	    }
		$parse = "https://alpha.refly.it/add/#/?url=$url&title=$title&description=$result....&image=$thumb";
		$the_icon = '<div class="refly_wrap" style="width:80px;" title="Share with Refly"><a href="'.$parse.'" target="_blank">' . $the_icon . '</a></div>';
		$content .= $the_icon;
	}
	return $content;
}

function redirect_to_post_on_publish_or_save()
{
	global $plugin_url_path;
	$postid = get_the_ID();
		$title = get_the_title( $post = $postid );
		$content_post = get_post($postid);
		$content = $content_post->post_content;
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		$result = substr($content, 0, strlen($content)-1);
		$result = strip_tags($result);
		$url = get_permalink ($post = $postid);
		//wp_redirect($url);
		$files = get_children('post_parent='.get_the_ID().'&post_type=attachment&post_mime_type=image');
		$thumb = "";
	    if($files) :
	        $keys = array_reverse(array_keys($files));
	        $j=0; $num = $keys[$j];
	        $image=wp_get_attachment_image($num, 'large', false);
	        $imagepieces = explode('"', $image);
	        $imagepath = $imagepieces[1];
	        $thumb=wp_get_attachment_thumb_url($num);
	    endif;
	    if ($thumb == "") {
	        	$thumb = "$plugin_url_path/images/wordpress.png";
	    }
		if (strlen($content) >= 100) {
			$result = substr($content, 0, 100);
			$result = strip_tags($result);
		}
	echo "<script>
	window.location.assign('$url');
	window.open('https://alpha.refly.it/add/#/?url=$url&title=$title&description=$result....&image=$thumb','_blank');</script>";
	
}

add_filter('redirect_post_location', 'redirect_to_post_on_publish_or_save');
add_filter('the_content', 'refly_generate');
add_action('admin_menu', 'refly_add_option_pages');
add_filter( 'gettext', 'pts_change_publish_button', 10, 2 );
add_filter( 'gettext', 'pts_change_update_button', 10, 2 );
?>