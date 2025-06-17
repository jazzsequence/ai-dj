<?php

namespace jazzsequence\AIDJ\Auth;

function bootstrap() {
	add_action('wp_ajax_spotify_callback', __NAMESPACE__ . '\\handle_spotify_callback');
}

function get_spotify_auth_link() {
	$client_id = AIDJ_SPOTIFY_CLIENT_ID ?? '';
	if (empty($client_id)) {
		return new \WP_Error('missing_client_id', 'Spotify Client ID is not set.');
	}
	$redirect_uri = urlencode(admin_url('admin-ajax.php?action=spotify_callback'));
	$scopes = urlencode('user-top-read user-library-read playlist-modify-private');
	$state = wp_create_nonce('spotify-auth');

	return "https://accounts.spotify.com/authorize?response_type=code&client_id={$client_id}&scope={$scopes}&redirect_uri={$redirect_uri}&state={$state}";
}

function handle_spotify_callback() {
	if (!isset($_GET['code'], $_GET['state']) || !wp_verify_nonce($_GET['state'], 'spotify-auth')) {
		wp_die('Invalid Spotify authentication request.');
	}

	$code = sanitize_text_field($_GET['code']);
	$redirect_uri = admin_url('admin-ajax.php?action=spotify_callback');

	$response = wp_remote_post('https://accounts.spotify.com/api/token', [
		'headers' => [
			'Authorization' => 'Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
			'Content-Type' => 'application/x-www-form-urlencoded',
		],
		'body' => http_build_query([
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $redirect_uri,
		]),
	]);

	$body = json_decode(wp_remote_retrieve_body($response), true);

	if (isset($body['access_token'])) {
		$user_id = get_current_user_id();

		update_user_meta($user_id, 'spotify_access_token', $body['access_token']);
		update_user_meta($user_id, 'spotify_refresh_token', $body['refresh_token']);
		update_user_meta($user_id, 'spotify_token_expires', time() + $body['expires_in']);

		wp_redirect(admin_url('admin.php?page=ai-dj-settings&connected=1'));
		exit;
	}

	wp_die('Spotify token exchange failed: ' . print_r($body, true));
}

function get_valid_spotify_access_token($user_id) {
	$access_token = get_user_meta($user_id, 'spotify_access_token', true);
	$expires = get_user_meta($user_id, 'spotify_token_expires', true);
	$refresh_token = get_user_meta($user_id, 'spotify_refresh_token', true);

	if (time() < $expires && $access_token) {
		return $access_token;
	}

	// Refresh the token
	$response = wp_remote_post('https://accounts.spotify.com/api/token', [
		'headers' => [
			'Authorization' => 'Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
			'Content-Type' => 'application/x-www-form-urlencoded',
		],
		'body' => http_build_query([
			'grant_type' => 'refresh_token',
			'refresh_token' => $refresh_token,
		]),
	]);

	$body = json_decode(wp_remote_retrieve_body($response), true);

	if (isset($body['access_token'])) {
		update_user_meta($user_id, 'spotify_access_token', $body['access_token']);
		update_user_meta($user_id, 'spotify_token_expires', time() + $body['expires_in']);
		return $body['access_token'];
	}

	return false;
}
