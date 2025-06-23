<?php
/**
 * Plugin Name: University of Michigan: Events
 * Plugin URI: https://github.com/umdigital/umich-events/
 * Description: Pull events from events.umich.edu
 * Version: 1.3.3
 * Author: U-M: Digital
 * Author URI: https://vpcomm.umich.edu
 * Update URI: https://github.com/umdigital/umich-events/releases/latest
 */

/** PLUGIN & DATA MANAGEMENT CODE **/
class UmichEvents
{
    static public $pluginPath;

    static private $_baseRemoteUrl   = 'https://events.umich.edu/list/json?filter={FILTERS}&range={DATE}';
    static private $_cacheTimeout    = 5; // in minutes (should be at least 1 minute)
    static private $_imgCacheTimeout = 7; // in days (should be at least 1 day)

    static private $_baseMetaUrl = 'https://events.umich.edu/list/metadata/json';
    static private $_metaTimeout = 7; // in days (should be at least 1 day)

    static private $_eventsURL  = null;
    static private $_eventsData = array();

    static private $_metaURL  = null;
    static private $_metaData = array();

    static public function init()
    {
        self::$pluginPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

        // convert cache timeouts into seconds
        self::$_cacheTimeout    = 60 * (self::$_cacheTimeout >= 1 ? self::$_cacheTimeout : 1);
        self::$_imgCacheTimeout = 60 * 60 * 24 * (self::$_imgCacheTimeout >= 1 ? self::$_imgCacheTimeout : 1);
        self::$_metaTimeout     = 60 * 60 * 24 * (self::$_metaTimeout >= 1 ? self::$_metaTimeout : 1);

        // load updater library
        if( file_exists( UMCOOKIECONSENT_PATH . implode( DIRECTORY_SEPARATOR, [ 'vendor', 'umdigital', 'wordpress-github-updater', 'github-updater.php' ] ) ) ) {
            include UMCOOKIECONSENT_PATH . implode( DIRECTORY_SEPARATOR, [ 'vendor', 'umdigital', 'wordpress-github-updater', 'github-updater.php' ] );
        }
        else if( file_exists( UMCOOKIECONSENT_PATH .'includes'. DIRECTORY_SEPARATOR .'github-updater.php' ) ) {
            include UMCOOKIECONSENT_PATH .'includes'. DIRECTORY_SEPARATOR .'github-updater.php';
        }

        // Initialize Github Updater
        if( class_exists( '\Umich\GithubUpdater\Init' ) ) {
            new \Umich\GithubUpdater\Init([
                'repo' => 'umdigital/umich-events',
                'slug' => plugin_basename( __FILE__ ),
            ]);
        }
        // Show error upon failure
        else {
            add_action( 'admin_notices', function(){
                echo '<div class="error notice"><h3>WARNING</h3><p>U-M: Events plugin is currently unable to check for updates due to a missing dependency.  Please <a href="https://github.com/umdigital/umich-events">reinstall the plugin</a>.</p></div>';
            });
        }

        // ADD EDITOR BLOCKS
        add_action( 'init', function(){
            if( function_exists( 'register_block_type' ) ) {
                foreach( glob( __DIR__ .'/blocks/*',  GLOB_ONLYDIR ) as $block ) {
                    if( is_file( "{$block}/block.php" ) ) {
                        include_once "{$block}/block.php";
                    }
                }
            }
        });

        add_action( 'widgets_init', array( __CLASS__, 'initWidget' ) );

        add_shortcode( 'umichevents', array( __CLASS__, 'displayEvents' ) );
    }

    /* CLEANUP CACHE DIRECTORIES */
    static public function cleanup()
    {
        // 10% chance of cleanup
        if( mt_rand( 1, 10 ) == 3 ) {
            $wpUpload = wp_upload_dir();
            $tmp = array(
                $wpUpload['basedir'],
                'umich-events-cache'
            );
            $cachePath = implode( DIRECTORY_SEPARATOR, $tmp );

            self::_cleanupDir( $cachePath, self::$_cacheTimeout );
            self::_cleanupDir( $cachePath . DIRECTORY_SEPARATOR .'thumbnails', self::$_imgCacheTimeout, true );
        }
    }

    static private function _cleanupDir( $dir, $expires, $recursive = false )
    {
        foreach( glob( $dir . DIRECTORY_SEPARATOR .'*' ) as $file ) {
            if( is_dir( $file ) ) {
                if( $recursive ) {
                    self::_cleanupDir( $file, $expires, $recursive );
                }
            }
            else if( (filemtime( $file ) + $expires) < time() ) {
                unlink( $file );
            }
        }
    }

    static public function initWidget()
    {
        register_widget( 'UmichEventsWidget' );
    }

    static public function getMetadata()
    {
        if( self::$_metaData ) {
            return self::$_metaData;
        }

        $fileKey = 'metadata';
        self::$_metaURL = self::$_baseMetaUrl;

        // wp upload settings
        $wpUpload = wp_upload_dir();

        $tmp = array(
            $wpUpload['basedir'],
            'umich-events-cache',
            $fileKey .'_'. date( 'Ymd' ) .'.cache'
        );
        $cachePath = implode( DIRECTORY_SEPARATOR, $tmp );

        // check cache, get results if stale/DNE
        if( !file_exists( $cachePath ) || ((@filemtime( $cachePath ) + self::$_metaTimeout) < time()) ) {
            // update timestamp so other requests don't make a pull request at the same time
            @touch( $cachePath );

            // get live results
            $feed = file_get_contents( self::$_metaURL );

            if( $feed && (json_last_error() === JSON_ERROR_NONE) ) {
                $feed = json_decode( $feed );

                // cleanup and organize data
                $meta = array( 'raw' => $feed );
                foreach( $feed as $type => $data ) {
                    $meta[ $type ] = array();

                    foreach( $data as $record ) {
                        $meta[ $type ][ $record->name ] = $record->id;
                    }

                    ksort( $meta[ $type ] );
                }


                // CACHE RESULTS
                // make sure store dir is there
                wp_mkdir_p( dirname( $cachePath ) );

                @file_put_contents( $cachePath, json_encode( $meta ) );
            }
        }

        // display some debug html comments
        /*
        if ( current_user_can( 'manage_options' ) ) {
            echo '<!-- Events Meta Source: '. self::$_metaURL ." -->\n";
            echo '<!-- Disk Cachefile: uploads'. str_replace( $wpUpload['basedir'], '', $cachePath ) ." -->\n";
            echo '<!-- Disk Cachfile Date: '. date( 'Y-m-d H:i:s', @filemtime( $cachePath ) ) ."UTC -->\n";
        }
        */

        return self::$_metaData = @json_decode( file_get_contents( $cachePath ) );
    }

    /* GET DATA FROM EVENTS API */
    static public function get( $options = array() )
    {
        $options = array_merge(
            array(
                'featured'  => false,
                'ongoing'   => false,
                'tags'      => array(),
                'types'     => array(),
                'groups'    => array(),
                'locations' => array(),
                'limit'     => 25 
            ),
            $options
        );

        if( is_null( $options['limit'] ) ) {
            $options['limit'] = 25;
        }

        $show = array();
        if( $options['featured'] ) {
            $show[] = 'feature';
        }
        if( !$options['ongoing'] ) {
            $show[] = 'new';
        }

        $filters = array(
            'show'        => $show,
            'tags'        => $options['tags'],
            'types'       => $options['types'],
            'sponsors'    => $options['groups'],
            'locations'   => $options['locations']
        );

        $filtersString = array();
        foreach( $filters as $key => $val ) {
            if( is_array( $val ) ) {
                foreach( $val as &$sval ) {
                    $sval = urlencode( $sval );
                }

                $val = implode( ',', $val );
            }
            else {
                $val = urlencode( $val );
            }

            if( $val ) {
                $filtersString[] = "{$key}:{$val}";
            }
        }
        $filtersString = implode( ',', $filtersString );

        // cache file
        $fileKey = md5( $filtersString . date( 'Y-m-d' ) . $options['limit'] );

        // already in memory return it
        if( @self::$_eventsData[ $fileKey ] ) {
            return self::$_eventsData[ $fileKey ];
        }

        // wp upload settings
        $wpUpload = wp_upload_dir();

        $tmp = array(
            $wpUpload['basedir'],
            'umich-events-cache',
            $fileKey .'_'. date( 'Ymd' ) .'.cache'
        );
        $cachePath = implode( DIRECTORY_SEPARATOR, $tmp );

        // create remote url
        self::$_eventsURL = self::$_baseRemoteUrl;
        self::$_eventsURL = str_replace(
            array( '{FILTERS}', '{DATE}' ),
            array( $filtersString, date( 'Y-m-d' ) ),
            self::$_eventsURL
        );

        if( preg_match( '/^[0-9]+$/', $options['limit'] ) ) {
            self::$_eventsURL .= '&max-results='. $options['limit'];
        }

        // check cache, get results if stale/DNE
        if( !file_exists( $cachePath ) || ((@filemtime( $cachePath ) + self::$_cacheTimeout) < time()) ) {
            // update timestamp so other requests don't make a pull request at the same time
            @touch( $cachePath );

            // get live results
            $feed = file_get_contents( self::$_eventsURL );
            json_decode( $feed );

            if( $feed && (json_last_error() === JSON_ERROR_NONE) ) {
                // CACHE RESULTS
                // make sure store dir is there
                wp_mkdir_p( dirname( $cachePath ) );

                @file_put_contents( $cachePath, $feed );
            }
        }

        // display some debug html comments
        /*
        if ( current_user_can( 'manage_options' ) ) {
            echo '<!-- Events Source: '. self::$_eventsURL ." -->\n";
            echo '<!-- Disk Cachefile: uploads'. str_replace( $wpUpload['basedir'], '', $cachePath ) ." -->\n";
            echo '<!-- Disk Cachfile Date: '. date( 'Y-m-d H:i:s', @filemtime( $cachePath ) ) ."UTC -->\n";
        }
        */

        return self::$_eventsData[ $fileKey ] = @json_decode(file_get_contents( $cachePath ));
    }

    static public function displayEvents( $atts )
    {
        $instance = shortcode_atts(array(
            'showimage'    => false,
            'imagesize'   => 'full',
            'featured'     => false,
            'ongoing'      => false,
            'tags'         => '',
            'types'        => '',
            'groups'       => '',
            'locations'    => '',
            'wraptpl'      => 'shortcode',
            'eventtpl'     => 'event-shortcode',
            'morelink'     => false,
            'morelinktext' => 'See all events',
            'limit'        => 25
        ), $atts );

        $instance['featured']   = (bool) $instance['featured'];
        $instance['ongoing']    = (bool) $instance['ongoing'];
        $instance['morelink']   = (bool) $instance['morelink'];
        $instance['image-size'] = $instance['imagesize'];
        $instance['wraptpl']    = $instance['wraptpl']  ? $instance['wraptpl']  : 'shortcode';
        $instance['eventtpl']   = $instance['eventtpl'] ? $instance['eventtpl'] : 'event-shortcode';

        $events = UmichEvents::get(array(
            'featured'  => $instance['featured'],
            'ongoing'   => $instance['ongoing'],
            'tags'      => explode( ',', $instance['tags'] ),
            'types'     => explode( ',', $instance['types'] ),
            'groups'    => explode( ',', $instance['groups'] ),
            'locations' => explode( ',', $instance['locations'] ),
            'limit'     => $instance['limit']
        ));

        // locate theme template version
        $tmp = array( dirname( __FILE__ ), 'templates', 'event.tpl' );
        $eventTemplate = implode( DIRECTORY_SEPARATOR, $tmp );
        if( $template = locate_template( array( 'umich-events/'. $instance['eventtpl'] .'.tpl' ), false ) ) {
            $eventTemplate = $template;
        }

        ob_start();
        // locate theme template version
        if( $template = locate_template( array( 'umich-events/'. $instance['wraptpl'] .'.tpl' ), false ) ) {
            include $template;
        }
        else {
            $tmp = array( dirname( __FILE__ ), 'templates', 'shortcode.tpl' );
            include( implode( DIRECTORY_SEPARATOR, $tmp ) );
        }

        return ob_get_clean();
    }

    /* GET EVENTS URL FOR MORE EVENTS */
    static public function getMoreURL()
    {
        return str_replace( '/json', '', self::$_eventsURL );
    }

    // @NOTE: https://codex.wordpress.org/Class_Reference/WP_Image_Editor
    //        cache for 1 week
    /* RESIZE EXTERNAL EVENTS IMAGE/CACHE LOCALLY */
    static public function getResizedEventImage( $imageUrl, $size = 'full', $crop = null )
    {
        global $_wp_additional_image_sizes;

        // prepare thumbnail destination
        $wpUpload = wp_upload_dir();
        $tmp = array(
            $wpUpload['basedir'],
            'umich-events-cache',
            'thumbnails'
        );
        $cachePath = implode( DIRECTORY_SEPARATOR, $tmp );

        // get width/height by thumbnail size
        if( !is_array( $size ) ) {
            if( in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) ) {
                $width  = get_option( $size .'_size_w' );
                $height = get_option( $size .'_size_h' );
                $crop   = is_null( $crop ) ? (get_option( $size .'_crop', null )) : $crop;
                $crop   = is_null( $crop ) ? true : (bool) $crop;
            }
            else if( isset( $_wp_additional_image_sizes[ $size ] ) ) {
                $width  = $_wp_additional_image_sizes[ $size ]['width'];
                $height = $_wp_additional_image_sizes[ $size ]['height'];
                $crop   = is_null( $crop ) ? $_wp_additional_image_sizes[ $size ]['crop'] : $crop;
            }
            // we don't know what the width/height of this is
            else {
                return $imageUrl;
            }
        }
        else {
            list( $width, $height ) = $size;
            $crop = is_null( $crop ) ? true : $crop;
        }


        // check if we already have the image cached and cache is still good
        $info = pathinfo( $imageUrl );
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR ."{$info['filename']}-{$width}x{$height}.{$info['extension']}";

        if( file_exists( $cacheFile ) && ((filemtime( $cacheFile ) + self::$_imgCacheTimeout) > time()) ) {
            return $wpUpload['baseurl'] .'/umich-events-cache/thumbnails/'. basename( $cacheFile );
        }


        // CACHE DNE OR IS STALE SO LETS REDO IT

        // prevent race condition on updating image
        if( file_exists( $cacheFile ) ) {
            @touch( $cacheFile );
        }

        // prepare editor/load remote image
        $img = wp_get_image_editor( $imageUrl );
        if( is_wp_error( $img ) || ($size == 'full') ) {
            return $imageUrl;
        }

        // resize image
        $img->resize( $width, $height, $crop );

        // make storage directory
        wp_mkdir_p( $cachePath );

        // save image
        $thumb = $img->save( $cacheFile );

        if( is_wp_error( $thumb ) || !isset( $thumb['path'] ) ) {
            return $imageUrl;
        }

        return $wpUpload['baseurl'] .'/umich-events-cache/thumbnails/'. $thumb['file'] .'?time='. time();
    }
}
UmichEvents::init();

/** WIDGET CODE **/
class UmichEventsWidget extends WP_Widget
{
    private $_cachePath          = null;
    private $_multiselectVersion = '1.0';

    function __construct()
    {
        parent::__construct( false, 'U-M: Events', array(
            'classname'   => 'umich-events clearfix',
            'description' => 'Display U-M Events from events.umich.edu'
        ));

        add_action( 'sidebar_admin_setup', array( $this, 'admin_setup' ) );
    }

    function admin_setup()
    {
        wp_enqueue_style( 'jquery-multiselect', plugins_url('vendor/jquery.multiselect.css', __FILE__), null, $this->_multiselectVersion );
        wp_enqueue_style( 'umevents-admin', plugins_url('umevents_admin.css', __FILE__), null, '1.0' );

        wp_enqueue_script('jquery-actual-js', plugins_url('vendor/jquery.actual.js', __FILE__), array( 'jquery' ) );
        wp_enqueue_script('jquery-multiselect-js', plugins_url('vendor/jquery.multiselect.js', __FILE__), array( 'jquery' ) );
        wp_enqueue_script('umevents-admin-js', plugins_url('umevents_admin.js', __FILE__), array( 'jquery' ) );
    }

    function widget( $args, $instance )
    {
        UmichEvents::cleanup();

        $instance = array_merge(array(
            'featured'  => null,
            'ongoing'   => null,
            'tags'      => null,
            'types'     => null,
            'groups'    => null,
            'locations' => null,
            'limit'     => null
        ), $instance );

        $events = UmichEvents::get(array(
            'featured'  => $instance['featured'],
            'ongoing'   => $instance['ongoing'],
            'tags'      => explode( ',', $instance['tags'] ),
            'types'     => explode( ',', $instance['types'] ),
            'groups'    => explode( ',', $instance['groups'] ),
            'locations' => explode( ',', $instance['locations'] ),
            'limit'     => $instance['limit']
        ));

        // locate theme template version
        $tmp = array( dirname( __FILE__ ), 'templates', 'event.tpl' );
        $eventTemplate = implode( DIRECTORY_SEPARATOR, $tmp );
        if( $template = locate_template( array( 'umich-events/event-widget.tpl' ), false ) ) {
            $eventTemplate = $template;
        }
        // support for < 1.1 installs
        else if( $template = locate_template( array( 'umich-events/event.tpl' ), false ) ) {
            $eventTemplate = $template;
        }

        // locate theme template version
        if( $template = locate_template( array( 'umich-events/widget.tpl' ), false ) ) {
            include $template;
        }
        else {
            $tmp = array( dirname( __FILE__ ), 'templates', 'widget.tpl' );
            include( implode( DIRECTORY_SEPARATOR, $tmp ) );
        }
    }

    function update( $new, $old )
    {
        foreach( array( 'featured', 'ongoing', 'showimage' ) as $key ) {
            $new[ $key ] = isset( $new[ $key ] ) ? true : false;
        }

        foreach( array( 'tags', 'types', 'groups', 'locations' ) as $key ) {
            if( !is_array( $new[ $key ] ) ) {
                $new[ $key ] = explode( ',', $new[ $key ] );
            }

            foreach( $new[ $key ] as &$val ) {
                $val = trim( $val );
            }
            $new[ $key ] = implode( ',', $new[ $key ] );
        }

        $new['limit'] = trim( $new['limit'] );
        if( !$new['limit'] || !preg_match( '/^[0-9]+$/', $new['limit'] ) ) {
            $new['limit'] = 4;
        }

        return $new;
    }

    function form( $instance )
    {
        $instance = wp_parse_args(
            (array) $instance,
            array(
                'title'      => '',
                'featured'   => false,
                'ongoing'    => false,
                'showimage'  => false,
                'image-size' => 'full',
                'tags'       => '',
                'types'      => '',
                'groups'     => '',
                'locations'  => '',
                'viewalltxt' => '',
                'limit'      => 4
            )
        );
        $instance['imagesize'] = $instance['image-size'];

        foreach( array( 'tags', 'types', 'groups', 'locations' ) as $key ) {
            $instance[ $key ] = explode( ',', $instance[ $key ] );
            foreach( $instance[ $key ] as &$val ) {
                $val = trim( $val );
            }
        }

        $tmp = array( dirname( __FILE__ ), 'templates', 'form.tpl' );
        include( implode( DIRECTORY_SEPARATOR, $tmp ) );
    }
}
