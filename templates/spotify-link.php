<?php
use jazzsequence\AIDJ\Auth;
?>

<a href="<?php echo esc_url(Auth\get_spotify_auth_link()); ?>" class="button button-primary"><?php _e( 'Connect to Spotify', 'ai-dj' ); ?></a>
