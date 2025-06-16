<div class="wrap">
	<h2>Generate AI Playlist</h2>
	<form id="ai-playlist-form">
		<label for="user-prompt">Enter a theme:</label>
		<input type="text" id="user-prompt" name="user_prompt" class="regular-text">
		<button type="submit" class="button button-primary">Generate Playlist</button>
	</form>
	<div id="playlist-results"></div>
</div>

<script>
	jQuery(document).ready(function($) {
		$('#ai-playlist-form').submit(function(e) {
			e.preventDefault();
			let userPrompt = $('#user-prompt').val();

			$.post(ajaxurl, {
				action: 'generate_playlist',
				user_prompt: userPrompt
			}, function(response) {
				$('#playlist-results').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
			});
		});
	});
</script>