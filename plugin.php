<?php
/**
 * Plugin Name: AI DJ
 * Plugin URI: https://github.com/jazzsequence/ai-dj
 * Description: AI DJ is a WordPress plugin that uses AI to generate music playlists based on user preferences and moods.
 * Version: 0.0.1
 * Author: Chris Reynolds
 * Author URI: https://jazzsequence.com
 * License: MIT
 * License URI: https://opensource.org/license/mit-license/
 * Text Domain: ai-dj
 * Domain Path: /languages
 */

namespace jazzsequence\AIDJ;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function bootstrap() {
	include_once plugin_dir_path( __FILE__ ) . 'inc/api-handler.php';
	include_once plugin_dir_path( __FILE__ ) . 'inc/auth.php';

	Auth\bootstrap();
	add_action('wp_ajax_generate_playlist', 'generate_playlist_ajax');
}

function generate_playlist_ajax() {
	if (!isset($_POST['user_prompt'])) {
		wp_send_json_error('Missing prompt');
	}

	if ( ! defined( 'AIDJ_OPENAI_API_KEY' ) ) {
		wp_send_json_error('OpenAI API key not set');
	}
	if ( ! defined( 'AIDJ_SPOTIFY_CLIENT_ID' ) || ! defined( 'AIDJ_SPOTIFY_CLIENT_SECRET' ) ) {
		wp_send_json_error('Spotify Client ID or Secret not set');
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error('Unauthorized');
	}

	$user_top_tracks = ApiHandler\get_spotify_user_top_tracks(AIDJ_SPOTIFY_CLIENT_ID);
	$playlist = ApiHandler\generate_playlist_from_openai($user_top_tracks, sanitize_text_field($_POST['user_prompt']));

	wp_send_json_success($playlist);
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\bootstrap' );
