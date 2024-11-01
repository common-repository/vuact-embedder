<?php
/*
	Plugin Name: Vuact Embedder
	Plugin URI: http://www.vuact.com/wordpress
	Description: Easily add Vuact videos to your posts.
	Version: 0.2.0
	Author: Vuact Inc.
	Author URI: http://www.vuact.com

	Copyright 2013, Vuact Inc. + weefselkweekje, sebaxtian, Roy Tanck Roy Tanck

	This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
// Modified from Youtuber plugin v. 1.8.2 by Roy Tanck, http://www.roytanck.com/2007/08/27/wordpress-plugin-youtuber/

define( 'VUACT_PLUGIN_URL', WP_PLUGIN_URL . '/vuact-embedder' );

define('VUACT_HEADER_V', '0.2.0');

add_action('wp_head', 'vuact_embed_header');
add_action('admin_init', 'vuact_embed_add_buttons');
add_action('admin_menu', 'vuact_embed_add_pages');
add_filter('the_content', 'vuact_embed');
add_filter('the_excerpt', 'vuact_embed');
add_action('init', 'vuact_embed_text_domain', 1);
add_action('wp_ajax_vuact_embed_tinymce', 'vuact_embed_tinymce');
register_activation_hook(__FILE__, 'vuact_embed_install');

add_shortcode('vuact_embed', 'vuact_embed_shortcode' );

function vuact_embed_install() {
	$newoptions = get_option('vuact_embed_options');
	$newoptions['width'] = '640';
	$newoptions['height'] = '420';
	$newoptions['quality'] = false;
	add_option('vuact_embed_options', $newoptions);
}

function vuact_embed_text_domain() {
	load_plugin_textdomain('vuact_embed', false, 'vuact_embed/lang');
}

function vuact_embed_add_pages() {
	add_options_page('Vuact Embedder', 'Vuact Embedder', 'manage_options', 'vuact_embed', 'vuact_embed_options');
}

function vuact_embed_tinymce() {
	// check for rights
    if ( !current_user_can('edit_pages') && !current_user_can('edit_posts') )
    	die(__("You are not allowed to be here"));

   	require_once('tinymce/mce_vuact_embed.php');

    die();
}

function vuact_embed_shortcode( $atts ) {
	$options = get_option('vuact_embed_options');

	if(array_key_exists('vuact', $atts)) {
		//Vuact URL?
		$search = "@\s*vuact.com\/(watch\/|video\/|v\/)([^\[]+)\s*@i";
		//$search = "@\s*localhost:8080\/(watch\/|video\/|v\/)([^\[]+)\s*@i";
		if(preg_match_all($search, $atts['vuact'], $matches)) {
			if(is_array($matches)) {
				foreach($matches[1] as $key =>$id) {
					// Get the data from the tag

					$id = $matches[2][$key];

					$answer = vuact_embed_video($id);
				}
			}
		} else {
			// Get the data from the tag
			$id = $atts['vuact'];
			$id = str_replace('×', 'x', vuact_embed_numeric_entities($id));

			$answer = vuact_embed_video($id);
		}
	}

	if(array_key_exists('youtube', $atts)) {
		//Youtube URL?
		$search = "@\s*(youtube.com\/watch\?v=([^\[]+))\s*@i";
		if(preg_match_all($search, $atts['youtube'], $matches)) {
			if(is_array($matches)) {
				foreach($matches[1] as $key =>$id) {
					// Get the data from the tag
					$url = $matches[1][$key];
					$search = $matches[0][$key];
					$url = parse_url(vuact_embed_numeric_entities($url));
					parse_str($url['query']);
					$v = str_replace('×', 'x', $v);
					$answer = vuact_embed_youtube($v);
				}
			}
		} else {
			// Get the data from the tag
			$id = $atts['youtube'];
			$id = str_replace('×', 'x', vuact_embed_numeric_entities($id));

			$answer = vuact_embed_youtube($id);
		}

	}

	if(array_key_exists('vimeo', $atts)) {
		//Vimeo URL?
		$search = "@\s*vimeo.com\/([^\/^\[]+)(.*)\s*@i";
		if(preg_match_all($search, $atts['vimeo'], $matches)) {
			if(is_array($matches)) {
				foreach($matches[1] as $key =>$id) {
					// Get the data from the tag
					$id = $matches[1][$key];

					$answer = vuact_embed_vimeo($id);
				}
			}
		} else {
			$id = $atts['vimeo'];

			$answer = vuact_embed_vimeo($id);
		}
	}

	if(array_key_exists('googlevideo', $atts)) {
		//Google Video URL?
		$search = "@\s*\video.google.com/videoplay\?docid=([0-9]+|-[0-9]+)([^\[]*)\s*@i";
		if(preg_match_all($search, $atts['googlevideo'], $matches)) {
			if(is_array($matches)) {
				foreach($matches[1] as $key =>$id) {
					// Get the data from the tag
					$id = $matches[1][$key];

					$answer = vuact_embed_googlevideo($id);
				}
			}
		} else {
			$id = $atts['googlevideo'];

			$answer = vuact_embed_googlevideo($id);
		}
	}
	return $answer;
}

function vuact_embed_video($id) {
	$id = trim($id);
	$options = get_option('vuact_embed_options');
	//__('This video was embedded using the Vuact plugin. Adobe Flash Player is required to view the video.', 'vuact_embed');
	// $vars = array( "rel" => 0, "fs" => 1 );
	$vars = array();
	if (is_user_logged_in() ) {
		$urlparts = parse_url(site_url());
		$domain = $urlparts ['host'];
		// We should make the token configurable through the options page, use domain for now
		$token = $domain;
// 		$vars = array( "domain" => $domain, "token" => $token, "username" => $current_user->user_login, "email" => $current_user->user_email );
		$vars = array( "domain" => $domain);
	}

	//	if($options['quality']) $vars['ap'] = "%2526fmt%3D18";
	$url = "http://www.vuact.com/v/".$id."?".http_build_query($vars, '', '&amp;');
	//$url = "http://localhost:8080/v/".$id."?".http_build_query($vars, '', '&amp;');
	$answer = sprintf("<iframe class='youtube-player vuact-embed' type='text/html' width='%s' height='%s' src='%s' webkitAllowFullScreen mozallowfullscreen allowFullScreen frameborder='0'></iframe>", $options['width'], $options['height'], $url);

	if (is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		// use the validation key as part of the token hash once the admin ui is implemented for it
		$script= sprintf('window.addEventListener("message", function(event){if(event.data !== "vuact:affiliate_user_info_request") return; event.source.postMessage("vuact:affiliate_user_info_response:%s|%s|%s","*");}, false);', urlencode($current_user->user_login), md5($domain.$current_user->user_login), urlencode($current_user->user_email) );
		$answer=$answer.'<script language="javascript" type="text/javascript">'.$script.'</script>';
	}


	if( $options['width'] == 0 || $options['width'] == '100%' || strlen($options['width']) == 0 )
		$answer = "<div class='vuact_embed-container'>".$answer."</div>";
	return $answer;
}



function vuact_embed_youtube($id) {
	$id = trim($id);
	$options = get_option('vuact_embed_options');
	//__('This video was embedded using the Vuact_embed plugin by <a href="http://www.vuact.com">Vuact Inc.</a>. Adobe Flash Player is required to view the video.', 'vuact_embed');
	$vars = array( "rel" => 0, "fs" => 1 );
	if($options['quality']) $vars['ap'] = "%2526fmt%3D18";
	$url = "http://www.youtube.com/embed/".$id."?".http_build_query($vars, '', '&amp;');
	$answer = sprintf("<iframe class='youtube-player vuact_embed' type='text/html' width='%s' height='%s' src='%s' webkitAllowFullScreen mozallowfullscreen allowFullScreen frameborder='0'></iframe>", $options['width'], $options['height'], $url);
	if( $options['width'] == 0 || $options['width'] == '100%' || strlen($options['width']) == 0 )
		$answer = "<div class='vuact_embed-container'>".$answer."</div>";
	return $answer;
}

function vuact_embed_vimeo($id) {
	$id = trim($id);
	$options = get_option('vuact_embed_options');
	//'.__('This video was embedded using the Vuact_embed plugin by <a href="http://www.vuact.com">Vuact Inc.</a>. Adobe Flash Player is required to view the video.', 'vuact_embed').'
	$vars = array( "title" => 0, "byline" => 0, "portrait" => 0 );
	if($options['quality']) $vars['ap'] = "%2526fmt%3D18";
	$url = "http://player.vimeo.com/video/".$id."?".http_build_query($vars, '', '&amp;');
	$answer = sprintf("<iframe class='youtube-player vuact_embed' type='text/html' width='%s' height='%s' src='%s' frameborder='0'></iframe>", $options['width'], $options['height'], $url);
	if( $options['width'] == 0 || $options['width'] == '100%' || strlen($options['width']) == 0 )
		$answer = "<div class='vuact_embed-container'>".$answer."</div>";
	return $answer;
}

function vuact_embed_googlevideo($id) {
	$id = trim($id);
	$options = get_option('vuact_embed_options');
	//'.__('This video was embedded using the Vuact_embed plugin by <a href="http://www.vuact.com">Vuact Inc.</a>. Adobe Flash Player is required to view the video.', 'vuact_embed').'
	return '<object class="vuact_embed" id="VideoPlayback" style="width: '.$options['width'].'px; height: '.$options['height'].'px;" width="'.$options['width'].'" height="'.$options['height'].'" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0"><param name="src" value="http://video.google.com/googleplayer.swf?docid='.$id.'" /><embed id="VideoPlayback" style="width: '.$options['width'].'px; height: '.$options['height'].'px;" type="application/x-shockwave-flash" width="'.$options['width'].'" height="'.$options['height'].'" src="http://video.google.com/googleplayer.swf?docid='.$id.'"></embed></object>';

}

function vuact_embed_numeric_entities($string){
	$mapping_hex = array();
	$mapping_dec = array();

	foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $char => $entity){
		$mapping_hex[html_entity_decode($entity,ENT_QUOTES,"UTF-8")] = '&#x' . strtoupper(dechex(ord(html_entity_decode($entity,ENT_QUOTES)))) . ';';
		$mapping_dec[html_entity_decode($entity,ENT_QUOTES,"UTF-8")] = '&#' . ord(html_entity_decode($entity,ENT_QUOTES)) . ';';
	}
	$string = str_replace(array_values($mapping_hex),array_keys($mapping_hex) , $string);
	$string = str_replace(array_values($mapping_dec),array_keys($mapping_dec) , $string);
	return $string;
}

function vuact_embed($content){
	$options = get_option('vuact_embed_options');

	//Vuact URL?
	$search = "@\s*\[vuact\]\s*(http:\/\/www.vuact.com\/watch\/([^\[]+))\s*\[/vuact\]\s*@i";
	// $search = "@\s*\[vuact\]\s*(http:\/\/localhost:8080\/watch\/([^\[]+))\s*\[/vuact\]\s*@i";
	if(preg_match_all($search, $content, $matches)) {
		if(is_array($matches)) {
			foreach($matches[1] as $key =>$id) {
				// Get the data from the tag
				$url = $matches[1][$key];
				$search = $matches[0][$key];
				$url = parse_url(vuact_embed_numeric_entities($url));
				parse_str($url['query']);
				$v = str_replace('×', 'x', $v);
				$replace = vuact_embed_video($v);
				$content = str_replace ($search, $replace, $content);
			}
		}
	}


	//Youtube URL?
	$search = "@\s*\[youtube\]\s*(http:\/\/www.youtube.com\/watch\?v=([^\[]+))\s*\[/youtube\]\s*@i";
	if(preg_match_all($search, $content, $matches)) {
		if(is_array($matches)) {
			foreach($matches[1] as $key =>$id) {
				// Get the data from the tag
				$url = $matches[1][$key];
				$search = $matches[0][$key];
				$url = parse_url(vuact_embed_numeric_entities($url));
				parse_str($url['query']);
				$v = str_replace('×', 'x', $v);
				$replace = vuact_embed_youtube($v);
				$content = str_replace ($search, $replace, $content);
			}
		}
	}

	//Vimeo URL?
	$search = "@\s*\[vimeo\]\s*http:\/\/(|www.)vimeo.com\/([^\/^\[]+)(.*)\s*\[/vimeo\]\s*@i";
	if(preg_match_all($search, $content, $matches)) {
		if(is_array($matches)) {
			foreach($matches[1] as $key =>$id) {
				// Get the data from the tag
				$id = $matches[2][$key];
				$search = $matches[0][$key];

				$replace = vuact_embed_vimeo($id);
				$content = str_replace ($search, $replace, $content);
			}
		}
	}

	//Google Video URL?
	$search = "@\s*\[googlevideo\]\s*http:\/\/video.google.com/videoplay\?docid=([0-9]+|-[0-9]+)([^\[]*)\s*\[/googlevideo\]\s*@i";
	if(preg_match_all($search, $content, $matches)) {
		if(is_array($matches)) {
			foreach($matches[1] as $key =>$id) {
				// Get the data from the tag
				$id = $matches[1][$key];
				$search = $matches[0][$key];

				$replace = vuact_embed_googlevideo($id);
				$content = str_replace ($search, $replace, $content);
			}
		}
	}


	//Just the Youtube ID?
	$search = "@\s*\[youtube\]\s*([^\[]+)\s*\[/youtube\]\s*@i";
	if(preg_match_all($search, $content, $matches)) {
		if(is_array($matches)) {
			foreach($matches[1] as $key =>$id) {
				// Get the data from the tag
				$id = $matches[1][$key];
				$id = str_replace('×', 'x', vuact_embed_numeric_entities($id));
				$search = $matches[0][$key];

				$replace = vuact_embed_youtube($id);
				$content = str_replace ($search, $replace, $content);
			}
		}
	}

	//Just the Vimeo ID?
	$search = "@\s*\[vimeo\]\s*([0-9]+)\s*\[/vimeo\]\s*@i";
	if(preg_match_all($search, $content, $matches)) {
		if(is_array($matches)) {
			foreach($matches[1] as $key =>$id) {
				// Get the data from the tag
				$id = $matches[1][$key];
				$search = $matches[0][$key];

				$replace = vuact_embed_vimeo($id);
				$content = str_replace ($search, $replace, $content);
			}
		}
	}

	//Just Google Video ID?
	$search = "@\s*\[googlevideo\]\s*([0-9]+|-[0-9]+)\s*\[/googlevideo\]\s*@i";
	if(preg_match_all($search, $content, $matches)) {
		if(is_array($matches)) {
			foreach($matches[1] as $key =>$id) {
				// Get the data from the tag
				$id = $matches[1][$key];
				$search = $matches[0][$key];

				$replace = vuact_embed_googlevideo($id);
				$content = str_replace ($search, $replace, $content);
			}
		}
	}

	return $content;
}

function vuact_embed_options() {
	global $table_prefix, $wpdb;
	// get options
	$options = $newoptions = get_option('vuact_embed_options');
	// if submitted, process results
	if ( isset($_POST["vuact_embed_submit"]) && $_POST["vuact_embed_submit"]) {
		$newoptions['width'] = strip_tags(stripslashes($_POST["width"]));
		$newoptions['height'] = strip_tags(stripslashes($_POST["height"]));
		if ($_POST["quality"]=="on") {
			$newoptions['quality'] = true;
		} else {
			$newoptions['quality'] = false;
		}
	}
	// any changes? save!
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('vuact_embed_options', $options);
	}
	// check if installed (hook is not called if used as mu-plugin)
	$wtemp = get_option('vuact_embed_width');
	if( empty($wtemp) ){ vuact_embed_install(); }
	// if options form was sent, process those...
	if( isset($_GET['action']) && $_GET['action'] == "updateoptions" ){
		update_option('vuact_embed_width', $_POST['vuact_embed_width']);
		update_option('vuact_embed_height', $_POST['vuact_embed_height']);
		update_option('vuact_embed_quality', $_POST['vuact_embed_quality']);
	}
	// options form
	echo'<form method="post" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=vuact_embed/vuact_embed.php">';
	echo '<div class="wrap"><p><h2>'.__('Vuact Embedder options', 'vuact_embed').'</h2><p>';
	// settings
	echo '<table class="form-table">';
	// width
	echo '<tr valign="top"><th scope="row">'.__('Movie width', 'vuact_embed').'</th>';
	echo '<td><input type="text" name="width" value="'.$options['width'].'" size="5"></input><br />'.__('Width in pixels', 'vuact_embed').'<br />'.__('Set value to 0 to use the max possible width and auto scale the height.', 'vuact_embed').'<br />('.sprintf(__('Vuact\'s default is %d', 'vuact_embed'), 640).')</td></tr>';
	// height
	echo '<tr valign="top"><th scope="row">'.__('Movie height', 'vuact_embed').'</th>';
	echo '<td><input type="text" name="height" value="'.$options['height'].'" size="5"></input><br />'.__('Height in pixels (should ideally be 3/4 of the width plus 25 pixels)', 'vuact_embed').'<br />'.sprintf(__('Example: %d * 3/4 + 25 = %d (rounded to the nearest pixel).', 'vuact_embed'), $options['width'], round( $options['width'] * .75 + 25)).'<br />('.sprintf(__('Vuact\'s default is %d', 'vuact_embed'), 420).')</td></tr>';
	// quality
	echo '<tr valign="top"><th scope="row">'.__('Video quality', 'vuact_embed').'</th>';
	if (isset($options['quality']) && !empty($options['quality'])) {
		$checked = " checked=\"checked\"";
	} else {
		$checked = "";
	}
	echo '<td><input type="checkbox" name="quality" value="on"'.$checked.' />'.__('Attempt to show all videos in high quality.', 'vuact_embed').'</td></tr>';
	echo '</table>';
	echo '<input type="hidden" name="vuact_embed_submit" value="true"></input>';
	echo '<p class="submit"><input type="submit" value="'.__('Update Options &raquo;', 'vuact_embed').'"></input></p>';
	echo '</form>';
	echo "</div>";
	echo '<div class="wrap"><p><h2>'.__('Using Vuact Embedder', 'vuact_embed').'</h2><p>';
	_e('To embed a video, follow these steps:<ul><li>Find the video on Vuact.</li><li>Extract the ID from the video page url.<br>An example URL would look like:<ul><li>http://www.vuact.com/watch/<strong>vuact-official/vuact-product-introduction</strong></li>The bolded text is the ID of the video.</li><li>Type <strong>[vuact]vuact-official/vuact-product-introduction[/vuact]</strong> anywhere in your post.</li><li>Save and publish the post.</li></ul>', 'vuact_embed');
	echo '</p><p>';

	$iconURL = VUACT_PLUGIN_URL . '/tinymce/vuact_embed.png';

	printf( __('You can also use the button %s in the Post/Page Editor. Just add the URL to the video.', 'vuact_embed'), "<img src='".$iconURL."'>");
	echo '</p></div>';
}

function vuact_embed_add_buttons() {
	// Don't bother doing this stuff if the current user lacks permissions
	if( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;

	// Add only in Rich Editor mode
	if( get_user_option('rich_editing') == 'true') {

		// add the button for wp21 in a new way
		add_filter('mce_external_plugins', 'add_vuact_embed_script');
		add_filter('mce_buttons', 'add_vuact_embed_button');
	}
}

function add_vuact_embed_button($buttons) {
	array_push($buttons, 'Vuact_embed');
	return $buttons;
}

function add_vuact_embed_script($plugins) {
	$pluginURL = VUACT_PLUGIN_URL . '/tinymce/editor_plugin.js?ver='.VUACT_HEADER_V;
	$plugins['Vuact_embed'] = $pluginURL;
	return $plugins;
}

function vuact_embed_header() {
	$options = get_option('vuact_embed_options');
	if( $options['width'] == 0 || $options['width'] == '100%' || strlen($options['width']) == 0 ) {
		wp_register_style('vuact_embed', VUACT_PLUGIN_URL . '/css/vuact_embed.css', array(), VUACT_HEADER_V);
		wp_enqueue_style('vuact_embed');

		wp_print_styles( array( 'vuact_embed' ));
	}
}

?>
