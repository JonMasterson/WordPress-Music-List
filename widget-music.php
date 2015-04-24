<?php
/*
 * A widget to display up to the five latest songs you listened to on Last.fm or Spotify.
 */

class SH_Music extends WP_Widget {
 
  public function __construct() {
      $widget_ops = array( 'classname' => '', 'description' => 'Displays a list of recenty played music from Last.fm or Spotify.' );
      $this->WP_Widget( 'SH_Music', 'Music List', $widget_ops );
  }
  
  function widget( $args, $instance ) {
    extract( $args, EXTR_SKIP );
    $title = empty( $instance['widget_title'] ) ? 'Listening to This' : apply_filters( 'widget_title', $instance['widget_title'] );
	$amount = empty( $instance['display_amount'] ) ? '3' : apply_filters( 'display_amount', $instance['display_amount'] );
	$latest = '';
	$error = 'I\'m not listening to anything at the moment, unfortunately.';
	echo ( isset( $before_widget ) ? $before_widget: '' );
	
	if ( !empty( $title ) )
	  echo $before_title . $title . $after_title; ?>

	<ul class="media-list">
    
	<?php if ( empty( $instance['lastfm_username'] ) || empty( $instance['lastfm_api_key'] ) ) : ?>
      <li class="media">
        <p>Please enter your <a href="http://www.last.fm/" target="_blank">Last.fm</a> credentials to use this widget.</p>
      </li>
	<?php else :
	  $username = apply_filters( 'lastfm_username', $instance['lastfm_username'] );
	  $api_key = apply_filters( 'lastfm_api_key', $instance['lastfm_api_key'] );
	  $latest = get_transient( 'mylastfmtracks_' . $username );
	  if ( false == ( $latest ) ) {
		$request_url = 'http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=' . $username . '&limit=' . $amount . '&api_key=' . $api_key . '&format=json';
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $request_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 20 );
		$tracks = curl_exec( $ch );
		$latest = ( curl_error( $ch ) ) ? 'broken' : $tracks;
		curl_close( $ch );
		set_transient( 'mylastfmtracks' . $username, $latest, 60 ); 
	  }
	  if ( $latest == 'broken' ) : ?>
		<li class="media">
		  <p class="tight"><?php echo $error; ?></p>
		</li>
	  <?php
	  else :
		$latest = json_decode($latest, true);
		if ( $latest['recenttracks']['track'] ) :
		  $i = 0;
		  foreach ( $latest['recenttracks']['track'] as $track ) :
			// setup variables
			$artist = ( isset( $track['artist']['#text'] ) ) ? $track['artist']['#text'] : ''; // artist name
			$title = ( isset( $track['name'] ) ) ? $track['name'] : ''; // track title
			$track_url = ( isset( $track['url'] ) ) ? $track['url'] : ''; // track url on Last.fm
			$albumArt = ( isset( $track['image'][1]['#text'] ) ) ? $track['image'][1]['#text'] : ''; // medium-sized album art
			$nowPlaying = ( isset( $track['@attr']['nowplaying'] ) ) ? true : ''; // if true, track is currently playing
			$datePlayed = ( isset( $track['date']['uts'] ) ) ? $track['date']['uts'] : ''; // time played
			date_default_timezone_set("America/New_York"); // set your time zone here
	  ?>
            <li class="media">
              <div class="pull-left">
              	<?php if ( $albumArt != '' ) : // If no album art, use default ?>
                <img class="media-object" src="<?php echo esc_attr( $albumArt ); ?>"  width="64px" height="64px" alt="Album Art for <?php echo esc_attr( $title ); ?>" />
              <?php else : ?>
                <div class="media-object no-album-art" title="No Album Art Available">
                  <span class="now-playing"><i class="icon-type-music"></i></span>
                </div>
              <?php endif; ?>
              </div>
              <div class="media-body">
                <h5 class="media-heading"><?php echo $artist; ?></h5>
                <small>
                  <p class="tight"><a href="<?php echo $track_url; ?>"><?php echo $title; ?></a><br />
                  <?php if ( $nowPlaying == true ) : // Test if song is playing right now. ?>
                    <span class="text-muted">Listening now</span>
                  <?php else : ?>
                    <span class="text-muted"><?php echo human_time_diff( $datePlayed ); ?> ago</span>
                  <?php endif; ?>
                  </p>
                </small>
              </div>
              <?php $i++; ?>
            </li>
			<?php
			if ( $i == $amount ) { break; }
		  endforeach;
		else : ?>
          <li class="media">
            <p class="tight"><?php echo $error; ?></p>
          </li>	
    	<?php endif; ?>
	  <?php endif; ?>
    <?php endif; ?>
    </ul>
	<?php echo ( isset( $after_widget ) ? $after_widget: '' );
  }
 
  public function form( $instance ) {
     $instance = wp_parse_args( ( array ) $instance, array( 'widget_title' => '', 'lastfm_username' => '', 'lastfm_api_key' => '', 'display_amount' => '' ) );
     $title = $instance['widget_title'];
	 $username = $instance['lastfm_username'];
	 $api_key = $instance['lastfm_api_key'];
	 $amount = $instance['display_amount'];
     ?>
     <p>
      <label for="<?php echo $this->get_field_id( 'widget_title' ); ?>">Title: 
        <input class="widefat" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" 
               name="<?php echo $this->get_field_name( 'widget_title' ); ?>" type="text" 
               value="<?php echo esc_attr( $title ); ?>" />
      </label>
     </p>
     <p>
      <label for="<?php echo $this->get_field_id( 'lastfm_username' ); ?>">Last.fm Username: 
        <input class="widefat" id="<?php echo $this->get_field_id( 'lastfm_username' ); ?>" 
               name="<?php echo $this->get_field_name( 'lastfm_username' ); ?>" type="text" 
               value="<?php echo esc_attr( $username ); ?>" />
      </label>
     </p>
     <p>
      <label for="<?php echo $this->get_field_id( 'lastfm_api_key' ); ?>"><a href="http://www.last.fm/api/webauth" target="_blank">API Key:</a> 
        <input class="widefat" id="<?php echo $this->get_field_id( 'lastfm_api_key' ); ?>" 
               name="<?php echo $this->get_field_name( 'lastfm_api_key' ); ?>" type="text" 
               value="<?php echo esc_attr( $api_key ); ?>" />
      </label>
     </p>
     <p>
        <label for="<?php echo $this->get_field_id( 'display_amount' ); ?>">Songs to Display:
        <select id="<?php echo $this->get_field_id( 'display_amount' ); ?>" name="<?php echo $this->get_field_name( 'display_amount' ); ?>">
            <option value="1" <?php if ( $amount == 1) { echo "selected"; } ?>>1</option>
            <option value="2" <?php if ( $amount == 2) { echo "selected"; } ?>>2</option>
            <option value="3" <?php if ( $amount == 3) { echo "selected"; } ?>>3</option>
            <option value="4" <?php if ( $amount == 4) { echo "selected"; } ?>>4</option>
            <option value="5" <?php if ( $amount == 5) { echo "selected"; } ?>>5</option>
        </select>
        </label>
     </p>
     <?php
  }
 
  function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['widget_title'] = $new_instance['widget_title'];
	$instance['lastfm_username'] = $new_instance['lastfm_username'];
	$instance['lastfm_api_key'] = $new_instance['lastfm_api_key'];
	$instance['display_amount'] = $new_instance['display_amount'];
    return $instance;
  }
  
}
add_action( 'widgets_init', create_function( '', 'return register_widget( "SH_Music" );' ) );
