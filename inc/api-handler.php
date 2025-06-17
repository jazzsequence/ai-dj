<?php

namespace jazzsequence\AIDJ\ApiHandler;
use WP_Error;
use jazzsequence\AIDJ\Auth;

function get_spotify_user_top_tracks($access_token) {
	$user_id = get_current_user_id();
	return spotify_api_get($user_id, 'me/top/tracks');
}

function generate_playlist_from_openai($user_top_tracks, $user_prompt) {
	if ( ! defined('AIDJ_OPENAI_API_KEY ' ) ) {
		return new \WP_Error('missing_api_key', 'OpenAI API key is not set.');
	}
	
	$prompt = "Create a Spotify playlist based on the user's favorite tracks: " . json_encode($user_top_tracks) . " and this theme: '$user_prompt'. Return 10 song titles and artists.";

	$response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
		'headers' => [
			'Authorization' => 'Bearer ' . AIDJ_OPENAI_API_KEY,
			'Content-Type' => 'application/json'
		],
		'body' => json_encode([
			'model' => 'gpt-4',
			'messages' => [['role' => 'user', 'content' => $prompt]],
		])
	]);

	if (is_wp_error($response)) {
		return new \WP_Error('api_error', 'Error communicating with OpenAI API: ' . $response->get_error_message());
	}

	$body = json_decode(wp_remote_retrieve_body($response), true);
	return $body['choices'][0]['message']['content'] ?? [];
}

function spotify_api_get($user_id, $endpoint) {
	$token = Auth\get_valid_spotify_access_token($user_id);
	if (!$token) return new WP_Error('spotify_auth', 'Invalid or expired token.');

	$response = wp_remote_get("https://api.spotify.com/v1/{$endpoint}", [
		'headers' => ['Authorization' => 'Bearer ' . $token],
	]);

	return $response;
}
