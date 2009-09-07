<?php

/*
Plugin Name: MyAnimeList for Wordpress
Version: 1.0
Description: Displays your recently watched anime. Basically a rewrite of the last.fm-plugin written by Ricardo González.
Author: André Lersveen
Author URI: http://sofacore.net
*/

/*  Copyright 2009  André Lersveen

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//define('MAGPIE_CACHE_AGE', 120);
define('MAGPIE_CACHE_ON', 0); //2.7 Cache Bug
define('MAGPIE_INPUT_ENCODING', 'UTF-8');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

$mal_options['widget_fields']['title'] = array('label'=>'Title:', 'type'=>'text', 'default'=>'');
$mal_options['widget_fields']['username'] = array('label'=>'Username:', 'type'=>'text', 'default'=>'');
$mal_options['widget_fields']['num'] = array('label'=>'Number of links:', 'type'=>'text', 'default'=>'');
$mal_options['widget_fields']['update'] = array('label'=>'Show timestamps:', 'type'=>'checkbox', 'default'=>false);
$mal_options['widget_fields']['linked'] = array('label'=>'Linked tracks:', 'type'=>'checkbox', 'default'=>false);
$mal_options['widget_fields']['encode_utf8'] = array('label'=>'UTF8 Encode:', 'type'=>'checkbox', 'default'=>false);

$lastfm_options['prefix'] = 'mal';


// Display MyAnimeList recently watched anime.

function mal_episodes($username = '', $num = 5, $list = true, $update = true, $linked  = true, $encode_utf8 = false) {
	global $mal_options;
	include_once(ABSPATH . WPINC . '/rss.php');

	$episodes = fetch_rss('http://myanimelist.net/rss.php?type=rw&u='.$username);

	if ($num <=0) $num = 1;
	if ($num >10) $num = 10;
	
	if ($list) echo '<ul class="mal">';
	
	if ($username == '') {
		if ($list) echo '<li>';
		echo 'Username not configured';
		if ($list) echo '</li>';
	} else {
			if ( empty($episodes->items) ) {
				if ($list) echo '<li>';
				echo 'No recently watched anime.';
				if ($list) echo '</li>';
			} else {
				foreach ( $episodes->items as $episode ) {
					$msg = htmlspecialchars($episode['title']);
					$progress = htmlspecialchars($episode['description']);
					if($encode_utf8) {$msg = utf8_encode($msg); $progress = utf8_encode($progress);}
					$link = $episode['link'];
				
					if ($list) echo '<li class="mal-item">'; elseif ($num != 1) echo '<p class="mal-episode">';
					if ($linked) { 
            echo '<a href="'.$link.'" class="mal-link">'.$msg.'</a>'; // Puts a link to the anime.
          } else {
            echo $msg; // Only the anime, no link.
          }
          
          echo '<p class="mal-progress">'.$progress.'</p>';
          
          if($update) {				
            $time = strtotime($episode['pubdate']);
            
            if ( ( abs( time() - $time) ) < 86400 )
              $h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
            else
              $h_time = date(__('Y/m/d'), $time);

            echo sprintf( '%s' ,' <p class="mal-timestamp"><abbr title="' . date(__('Y/m/d H:i:s'), $time) . '">' . $h_time . '</abbr></p>' );
           }   
           
           if ($list) echo '</li>'; elseif ($num != 1) echo '</p>';
				
					$i++;
					if ( $i >= $num ) break;
				}
			}	
		}
    if ($list) echo '</ul>';
	}

// MyAnimeList widget stuff
function widget_mal_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;
	
	$check_options = get_option('widget_mal');
  if ($check_options['number']=='') {
    $check_options['number'] = 1;
    update_option('widget_mal', $check_options);
  }
	function widget_mal($args, $number = 1) {
	
	global $mal_options;
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		include_once(ABSPATH . WPINC . '/rss.php');
		$options = get_option('widget_mal');
		
		// fill options with default values if value is not set
		$item = $options[$number];
		foreach($mal_options['widget_fields'] as $key => $field) {
			if (! isset($item[$key])) {
				$item[$key] = $field['default'];
			}
		}
		
		$episodes = fetch_rss('http://myanimelist.net/rss.php?type=rw&u='.$username);

		// These lines generate our output.
echo $before_widget . $before_title . '<a href="http://myanimelist.net/profile/' .
$item['username'] . '" class="mal_title_link">'. $item['title'] . '</a>' . $after_title;
		mal_episodes($item['username'], $item['num'], true, $item['update'], $item['linked'], $item['encode_utf8']);
		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_mal_control($number) {

		global $mal_options;

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_mal');
		
		if ( isset($_POST['mal-submit']) ) {

			foreach($mal_options['widget_fields'] as $key => $field) {
				$options[$number][$key] = $field['default'];
				$field_name = sprintf('%s_%s_%s', $mal_options['prefix'], $key, $number);

				if ($field['type'] == 'text') {
					$options[$number][$key] = strip_tags(stripslashes($_POST[$field_name]));
				} elseif ($field['type'] == 'checkbox') {
					$options[$number][$key] = isset($_POST[$field_name]);
				}
			}

			update_option('widget_mal', $options);
		}

		foreach($mal_options['widget_fields'] as $key => $field) {
			
			$field_name = sprintf('%s_%s_%s', $mal_options['prefix'], $key, $number);
			$field_checked = '';
			if ($field['type'] == 'text') {
				$field_value = htmlspecialchars($options[$number][$key], ENT_QUOTES);
			} elseif ($field['type'] == 'checkbox') {
				$field_value = 1;
				if (! empty($options[$number][$key])) {
					$field_checked = 'checked="checked"';
				}
			}
			
			printf('<p style="text-align:right;" class="mal_field"><label for="%s">%s <input id="%s" name="%s" type="%s" value="%s" class="%s" %s /></label></p>',
				$field_name, __($field['label']), $field_name, $field_name, $field['type'], $field_value, $field['type'], $field_checked);
		}
		echo '<input type="hidden" id="mal-submit" name="mal-submit" value="1" />';
	}
	
	function widget_mal_setup() {
		$options = $newoptions = get_option('widget_mal');
		
		if ( isset($_POST['mal-number-submit']) ) {
			$number = (int) $_POST['mal-number'];
			$newoptions['number'] = $number;
		}
		
		if ( $options != $newoptions ) {
			update_option('widget_mal', $newoptions);
			widget_mal_register();
		}
	}
	
	
	function widget_mal_page() {
		$options = $newoptions = get_option('widget_mal');
	?>
		<div class="wrap">
			<form method="POST">
				<h2><?php _e('MAL Widgets'); ?></h2>
				<p style="line-height: 30px;"><?php _e('How many MAL widgets would you like?'); ?>
				<select id="mal-number" name="mal-number" value="<?php echo $options['number']; ?>">
	<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
				</select>
				<span class="submit"><input type="submit" name="mal-number-submit" id="mal-number-submit" value="<?php echo attribute_escape(__('Save')); ?>" /></span></p>
			</form>
		</div>
	<?php
	}
	
	
	function widget_mal_register() {
		
		$options = get_option('widget_mal');
		$dims = array('width' => 300, 'height' => 300);
		$class = array('classname' => 'widget_mal');

		for ($i = 1; $i <= 9; $i++) {
			$name = sprintf(__('myanimelist #%d'), $i);
			$id = "mal-$i"; // Never never never translate an id
			wp_register_sidebar_widget($id, $name, $i <= $options['number'] ? 'widget_mal' : /* unregister */ '', $class, $i);
			wp_register_widget_control($id, $name, $i <= $options['number'] ? 'widget_mal_control' : /* unregister */ '', $dims, $i);
		}
		
		add_action('sidebar_admin_setup', 'widget_mal_setup');
		add_action('sidebar_admin_page', 'widget_mal_page');
	}

	widget_mal_register();
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_mal_init');



?>