<?php

/*
Plugin Name: Slick Audio
Plugin URI: http://richardsweeney.com/slick-audio/
Description: A nice way to add an mp3 player to your site, with HTML5 support & flash fallback
Version 0.1
Author: Theorboman
Author URI: http://richardsweeney.com/
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


/* @richardsweeney */


class Slick_Audio {

	/* Constructor function - all action hooks must be added here */
	public function __construct() {

		global $wpdb;
		// Define constants
		define('SA_VERSION', '0.1');
		define('URL', get_bloginfo('url'));
		define('PLUGIN_URL',  plugin_dir_url(__FILE__));
		define('SA_DB_TABLE',  $wpdb->prefix . "slick_audio");

		register_activation_hook(__FILE__, array(&$this, 'on_plugin_activation'));

		add_action('admin_menu', array(&$this, 'create_menu_item'));
		add_action('wp_enqueue_scripts', array(&$this, 'add_frontend_js_css'));
    add_action('plugins_loaded', array(&$this, 'plugin_textdomain'));
		
		add_shortcode('slick-audio', array(&$this, 'create_widget_shortcode'));
	}


	/* Create the extra table on plugin activation */
	public function on_plugin_activation() {
		global $wpdb;
		// Create events table
		$sql = "CREATE TABLE IF NOT EXISTS " .  $wpdb->prefix . "slick_audio (
			id mediumint(9) NOT NULL,
			data mediumtext NOT NULL,
			PRIMARY KEY (id)
		);";	
		$wpdb->query($sql);
		register_uninstall_hook(__FILE__, array(&$this, 'on_plugin_uninstall'));
	}


	/* Delete the extra table on plugin uninstallation */
	public function on_plugin_uninstall() {		
		global $wpdb;
		$sql = 'DROP TABLE ' . SA_DB_TABLE . ';';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}

	public function plugin_textdomain() {
		load_plugin_textdomain('slick-audio', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}


	/* Add JS + CSS to the plugin page */
	public function add_js_css() {
		wp_enqueue_style('thickbox');
		wp_enqueue_style('sa-css', PLUGIN_URL . 'css/sa.css');
		wp_enqueue_script('jquery');
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jplayer', PLUGIN_URL . 'js/jquery.jplayer.min.js', array('jquery'));
		wp_enqueue_script('jplayer-playlist', PLUGIN_URL . 'js/jplayer.playlist.min.js', array('jquery', 'jplayer'));
		wp_enqueue_script('sa-js', PLUGIN_URL . 'js/sa.js', array('jquery', 'media-upload', 'thickbox', 'jquery-ui-core', 'jquery-ui-sortable'));	
		$data = array(
			'nonce' => wp_create_nonce('sa-check'),
			'pluginURL' => PLUGIN_URL
		);
		wp_localize_script('sa-js', 'SaGlobals', $data);
	}

	/* Add JS + CSS to the frontend */
	public function add_frontend_js_css() {
		wp_enqueue_style('sa-css', PLUGIN_URL . 'css/sa.css');
		wp_enqueue_script('jquery');
		wp_enqueue_script('jplayer', PLUGIN_URL . 'js/jquery.jplayer.min.js', array('jquery'));
		wp_enqueue_script('jplayer-playlist', PLUGIN_URL . 'js/jplayer.playlist.min.js', array('jquery', 'jplayer'));
		$data = array('pluginURL' => PLUGIN_URL);
		wp_localize_script('jplayer', 'SaGlobals', $data);
	}


	/* Create the menu item */
	public function create_menu_item() {
		$settings = add_menu_page('Slick Audio', 'Slick Audio', 'manage_options', 'slick-audio', array(&$this, 'create_page'));
		// Add JS + CSS to plugin page only
		add_action('load-' . $settings, array(&$this, 'add_js_css'));
	}

	/* Build the player */
	private function create_player($playlist) {
		$tracks = '';

		foreach($playlist as $track) {
			$tracks .= '<li><a href="' . $track['url'] . '">' . $track['title'] . '</a></li>' . "\n";
		}

		$player = '<div class="jplayer-container">
			<div id="jquery_jplayer_1" class="jp-jplayer"></div>
			<div class="jp_ui" id="jp_interface_1">
        <ul id="jp-controls">
          <li><a href="#" class="jp-play" tabindex="1"><img src="' . PLUGIN_URL . 'img/play.png" alt="play"></a></li>
          <li><a href="#" class="jp-pause" tabindex="1"><img src="' . PLUGIN_URL . 'img/pause.png" alt="play"></a></li>
					<li><a href="#" class="jp-previous" tabindex="1"><img src="' . PLUGIN_URL . 'img/previous.png" alt="play"></a></li>
					<li><a href="#" class="jp-next" tabindex="1"><img src="' . PLUGIN_URL . 'img/next.png" alt="play"></a></li>
        </ul>
	      <div class="jp-progress">
	      	<div class="jp-seek-bar">
	        	<div class="jp-play-bar"></div>
  	      </div>
    	  </div>
      </div>
      <div id="jp_playlist_1" class="jp-playlist">
        <ul>' . $tracks . '</ul>
      </div>
    </div>';

	  return $player;
	}


	/* Create the markup for the page */
	public function create_page() {

		global $wpdb;

		$sql = 'SELECT id, data FROM ' . SA_DB_TABLE . ';';
		$results = $wpdb->get_results($sql, OBJECT);

		if (isset($_POST['mp3'])) {

			check_admin_referer('sa-check');

			$mp3Array = array();

			foreach ($_POST['mp3'] as $mp3) {
				if(!empty($mp3['title'])) {
					$data = array(
						'title' => sanitize_text_field($mp3['title']),
						'url' => esc_url_raw($mp3['url']),
					);
					$mp3Array[] = $data;
				}
			}

			$values = array('data' => serialize($mp3Array));
			if (empty($results)) {
				$values['id'] = 1;
				$formats = array('%d', '%s');
				$wpdb->insert(SA_DB_TABLE, $values, $formats);
			} else {
				$where = array('id' => 1);
				$formats = array('%s');
				$formatsWhere = array('%d');
				$wpdb->update(SA_DB_TABLE, $values, $where, $formats, $formatsWhere);
			}

			$results = $wpdb->get_results($sql, OBJECT);
		}

		$html = '<ol class="mp3-list">';

		if (!empty($results)) {
			$data = unserialize($results[0]->data);
			$i = 0;
			foreach($data as $track) {
				$extraClass = ($i == 0) ? ' first-mp3' : '';
				$html .= '<li id="sa-' . $i . '" class="mp3-li' . $extraClass . '">';
				$html .= '<span class="number">' . ($i + 1) . '</span>';
				$html .= sprintf('<input class="sa-input-title" value="%s" type="text" name="mp3[%d][title]" placeholder="%s" />', $track['title'], $i, __('Title of the mp3', 'slick-audio'));
				$html .= sprintf('<input class="sa-input-url" value="%s" type="text" name="mp3[%d][url]" placeholder="%s" />', $track['url'], $i, __('URL of the mp3', 'slick-audio'));
				$html .= sprintf('<a class="sa-add-mp3 button-secondary">%s</a>', __('upload / select'));
				$html .= sprintf('<a href="#" class="sa-delete" data-id="%d">%s</a>', $i, __('delete'));
				$html .= '</li>' . "\n";
				$i++;
			}
		} else {
			$html .= '<li id="sa-0" class="mp3-li first-mp3">';
			$html .= '<span class="number">1</span>';
			$html .= sprintf('<input class="sa-input-title" value="%s" type="text" name="mp3[0][title]" placeholder="%s" />', $track['title'], __('Title of the mp3', 'slick-audio'));
			$html .= sprintf('<input class="sa-input-url" value="%s" type="text" name="mp3[0][url]" placeholder="%s" />', $track['url'],  __('URL of the mp3', 'slick-audio'));
			$html .= sprintf('<a class="sa-add-mp3 button-secondary">%s</a>', __('upload / select'));
			$html .= sprintf('<a href="#" class="sa-delete" data-id="0">%s</a>', __('delete'));
			$html .= '</li>' . "\n";
		}
		$html .= '</ol>'

	?>

		<div class="wrap sa-container">

			<?php screen_icon('plugins'); ?>
			<h2>Slick Audio</h2>

			<form class="sa-form" method="POST" action="">

				<?php echo $html; ?>
				<p class="sa-add-new-field-container"><a class="sa-add-new-field button-secondary"><?php _e('Add another mp3', 'slick-audio'); ?></a></p>
				<p class="submit-container"><input id="sa-submit-button" type="submit" value="<?php _e('Save', 'slick-audio'); ?>" class="button-primary" /></p>
				<?php wp_nonce_field('sa-check'); ?>

			</form>

			<h3><?php _e('Player preview', 'slick-audio'); ?></h3>

			<?php echo $this->create_player($data); ?>

		</div>
	<?php }


	public function create_widget_shortcode() {

		global $wpdb;

		$sql = 'SELECT id, data FROM ' . SA_DB_TABLE . ';';
		$results = $wpdb->get_results($sql, OBJECT);

		if (!empty($results)) {
			$data = unserialize($results[0]->data);
			return $this->create_player($data);
		} else {
			return false;
		}

	}

}

// Instantiate the class
$slick_audio = new Slick_Audio();



class SlickAudioWidget extends WP_Widget {

	function SlickAudioWidget() {
		// Instantiate the parent object
		parent::__construct( false, 'Slick Audio' );
	}

	function widget( $args, $instance ) {
		global $slick_audio;
		// Grab the code from our plugin class.
		echo $slick_audio->create_widget_shortcode();
	}

	// function update( $new_instance, $old_instance ) {}

	function form( $instance ) {
	?>
		<p><?php _e('You can add tracks to your mp3 player on the', 'slick-audio'); ?> <a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=slick-audio"><?php _e('slick audio admin page.', 'slick-audio'); ?></a></p>
	<?php
	}

}

function slick_audio_register_widgets() {
	register_widget('SlickAudioWidget');
}

add_action('widgets_init', 'slick_audio_register_widgets');





