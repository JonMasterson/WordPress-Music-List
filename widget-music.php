<?php
/*
 * A widget to display up to the five latest songs you listened to on Last.fm, Spotify, or Apple Music.
 */

class Music_List extends WP_Widget {

  /**
	 * Sets up the widget name, description, etc
	 */
  function __construct() {
    $widget_ops = array( 
      'classname' => 'widget_block music-list',
      'description' => __( 'Displays a list of recenty played music from Last.fm, Spotify, or Apple Music.', 'your_text_domain' ),
      'customize_selective_refresh' => true
    );
    parent::__construct( 
      'Music_List', 
      __( 'Music List', 'your_text_domain' ), 
      $widget_ops
    );

    add_action( 'widgets_init', function() { 
      register_widget( 'Music_List' ); 
    } );
  }

  /**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance The widget options
	 */
  public function widget( $args, $instance ) {
    extract( $args, EXTR_SKIP );
    // Set default widget options in case they are empty
    $title = empty( $instance['widget_title'] ) ? __( 'Listening to This', 'your_text_domain' ) : apply_filters( 'widget_title', $instance['widget_title'] );
    $amount = empty( $instance['display_amount'] ) ? '3' : apply_filters( 'display_amount', $instance['display_amount'] );
    $latest = '';
    $error = __( 'We are not listening to anything at the moment.', 'your_text_domain' );

    if ( isset( $before_widget ) ) { echo $before_widget . "\n"; }
    if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } 

    // Set a nag message if Last.fm credentials are missing
    if ( empty( $instance['lastfm_username'] ) || empty( $instance['lastfm_api_key'] ) ) : ?>

      <p><?php _e( 'Please enter your ', 'your_text_domain' ); ?><a href="https://www.last.fm/" target="_blank">Last.fm</a><?php _e( ' credentials to use this widget.', 'your_text_domain' ); ?></p>

    <?php else :

      // Setup the Last.fm request
      $username = apply_filters( 'lastfm_username', $instance['lastfm_username'] );
      $api_key = apply_filters( 'lastfm_api_key', $instance['lastfm_api_key'] );
      $request_url = 'https://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=' . $username . '&limit=' . $amount . '&api_key=' . $api_key . '&format=json';
      $ch = curl_init();
      curl_setopt( $ch, CURLOPT_URL, $request_url );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 4 );
      $tracks = curl_exec( $ch );
      $latest = ( curl_error( $ch ) ) ? 'broken' : $tracks;
      curl_close( $ch );

      // If there's an error, output a message
      if ( $latest == 'broken' ) : ?>

        <p><?php echo $error; ?></p>

      <?php else :

        // Setup the data
        $latest = json_decode( $latest, true );
        // If we have tracks, let's go!
        if ( $latest['recenttracks'] && $latest['recenttracks']['track'] ) :
          $i = 0;
          // Begin to loop over the tracks, and cherrypick data
          foreach ( $latest['recenttracks']['track'] as $track ) :
            // Setup variables
            date_default_timezone_set("America/New_York"); // Set this to your time zone
            $artist = ( isset( $track['artist']['#text'] ) ) ? $track['artist']['#text'] : ''; // Name of the artist
            $title = ( isset( $track['name'] ) ) ? $track['name'] : ''; // Name of the song
            $url = ( isset( $track['url'] ) ) ? $track['url'] : ''; // Link to the track on Last.fm
            $albumArt = ( isset( $track['image'][2]['#text'] ) ) ? $track['image'][2]['#text'] : ''; // Large-sized album art
            $nowPlaying = ( isset( $track['@attr']['nowplaying'] ) ) ? true : ''; // The song playing right now
            $datePlayed = ( isset( $track['date']['uts'] ) ) ? human_time_diff( $track['date']['uts'] ) : ''; // Date/Time played
            // Output the track data... ?>

          <div class="wp-block-media-text alignwide" style="grid-template-columns:25% auto; margin-top: 0; margin-bottom: 3rem;">
            <figure class="wp-block-media-text__media">
              <a style="text-decoration: none;" href="<?php esc_attr_e( $url ); ?>" target="_blank">
                <?php if ( $albumArt !== '' ) : ?>
                  <img class="size-thumbnail" style="border-radius: 50%;" src="<?php esc_attr_e( $albumArt ); ?>" alt="<?php printf( __( 'Album Art for %s', 'your_text_domain' ), $title ); ?>">
                <?php else : ?>
                  <div class="size-thumbnail" style="width: 175px; height: 175px; border-radius: 50%; font-size: 70px; background-color: black; color: white; display: flex; align-items: center; justify-content: center;">&#9834;</div>
                <?php endif; ?>
              </a>
            </figure>
            <div class="wp-block-media-text__content" style="padding-top: 0; padding-bottom: 0;">
              <p class="has-normal-font-size" style="margin-bottom: 0;">
                <strong><?php echo $artist; ?></strong>
              </p>
              <p class="has-small-font-size" style="margin-top: 0;">
                <a href="<?php esc_attr_e( $url ); ?>" target="_blank"><?php echo $title; ?></a>
                <br>
                <small style="opacity: 0.7;">
                <?php ( $nowPlaying == true ) ? _e( 'Listening now', 'your_text_domain' ) : printf( __( '%s ago', 'your_text_domain' ), $datePlayed ); ?>
                </small>
              </p>
            </div>
          </div>
        
          <?php
          // Increment for each track
          $i++;
          // Stop when we hit the limit set in widget options (default = 3)
          if ( $i == $amount ) { 
            break;
          }
          // End track loop
          endforeach;

        else : 
        // There are no tracks, so output the error message... ?>

        <p><?php echo $error; ?></p>

        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  
    <?php if ( isset( $after_widget ) ) { echo "\n\t\t" . $after_widget . "\n"; }
  }

  /**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
  public function form( $instance ) {
    // Setup options
    $instance = wp_parse_args( ( array ) $instance, array( 'widget_title' => '', 'lastfm_username' => '', 'lastfm_api_key' => '', 'display_amount' => '' ) );
    $title = $instance['widget_title'];
    $username = $instance['lastfm_username'];
    $api_key = $instance['lastfm_api_key'];
    $amount = $instance['display_amount'];
    ?>

    <p>
      <label for="<?php esc_attr_e( $this->get_field_id( 'widget_title' ) ); ?>"><?php _e( 'Title:', 'your_text_domain' ); ?>
        <input class="widefat" id="<?php esc_attr_e( $this->get_field_id( 'widget_title' ) ); ?>" name="<?php esc_attr_e( $this->get_field_name( 'widget_title' ) ); ?>" type="text" value="<?php esc_attr_e( $title ); ?>">
      </label>
    </p>
    <p>
      <label for="<?php esc_attr_e( $this->get_field_id( 'lastfm_username' ) ); ?>"><?php _e( 'Last.fm Username:', 'your_text_domain' ); ?> 
        <input class="widefat" id="<?php _e( $this->get_field_id( 'lastfm_username' ) ); ?>" name="<?php esc_attr_e( $this->get_field_name( 'lastfm_username' ) ); ?>" type="text" value="<?php esc_attr_e( $username ); ?>">
      </label>
    </p>
    <p>
      <label for="<?php esc_attr_e( $this->get_field_id( 'lastfm_api_key' ) ); ?>"><a href="https://www.last.fm/api/webauth" target="_blank"><?php _e( 'API Key:', 'your_text_domain' ); ?></a> 
        <input class="widefat" id="<?php esc_attr_e( $this->get_field_id( 'lastfm_api_key' ) ); ?>" name="<?php esc_attr_e( $this->get_field_name( 'lastfm_api_key' ) ); ?>" type="text" value="<?php esc_attr_e( $api_key ); ?>">
      </label>
    </p>
    <p>
      <label for="<?php esc_attr_e( $this->get_field_id( 'display_amount' ) ); ?>"><?php _e( 'Songs to Display:', 'your_text_domain' ); ?>
        <select id="<?php esc_attr_e( $this->get_field_id( 'display_amount' ) ); ?>" name="<?php esc_attr_e( $this->get_field_name( 'display_amount' ) ); ?>">
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
 
  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['widget_title'] = $new_instance['widget_title'];
    $instance['lastfm_username'] = $new_instance['lastfm_username'];
    $instance['lastfm_api_key'] = $new_instance['lastfm_api_key'];
    $instance['display_amount'] = $new_instance['display_amount'];

    return $instance;
  }
  
}
$music_list = new Music_List();
