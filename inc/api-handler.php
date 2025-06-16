<?php

namespace jazzsequence\AIDJ\ApiHandler;

function get_spotify_user_top_tracks($access_token) {
    $url = "https://api.spotify.com/v1/me/top/tracks?limit=10";
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token
        ]
    ]);
    
    if (is_wp_error($response)) {
        return new \WP_Error('api_error', 'Error communicating with Spotify API: ' . $response->get_error_message());
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['items'] ?? [];
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
