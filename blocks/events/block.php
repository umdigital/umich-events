<?php

class UmichEvents_Block_Events
{
    static private $_prefix = 'umichevents-events';
    static private $_block  = 'events';

    static public function init()
    {
        $script       = null;
        $styles       = null;
        $editorStyles = null;
        $editorScript = null;

        // FRONT & BACK END JS
        if( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'script.js' ) ) {
            $script = self::$_prefix .'--'. self::$_block .'-js';

            wp_register_script(
                $script,
                plugins_url( '/script.js', __FILE__ ),
                array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-api' ),
                filemtime( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'script.js' )
            );
        }

        // FRONT & BACKEND STYLES
        if( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'styles.css' ) ) {
            $style = self::$_prefix .'--'. self::$_block .'-css';

            wp_register_style(
                $style,
                plugins_url( '/styles.css', __FILE__ ),
                array(),
                filemtime( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'styles.css' )
            );
        }

        // BACKEND STYLES
        if( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'editor.css' ) ) {
            $editorStyles = self::$_prefix .'--'. self::$_block .'-ed-css';

            wp_register_style(
                $editorStyles,
                plugins_url( '/editor.css', __FILE__ ),
                array(),
                filemtime( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'editor.css' )
            );
        }

        $editorScript = self::$_prefix .'--'. self::$_block .'-ed-js';
        wp_register_script(
            $editorScript,
            plugins_url( '/editor.js', __FILE__ ),
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-api' ),
            filemtime( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'editor.js' )
        );

        register_block_type( __DIR__, array(
            'script'          => $script,
            'style'           => $style,
            'editor_style'    => $editorStyles,
            'editor_script'   => $editorScript,
            'render_callback' => function( $instance, $content ){
                $instance = array_merge(array(
                    'showImage'    => false,
                    'imageSize'    => 'thumbnail',
                    'featured'     => false,
                    'ongoing'      => false,
                    'types'        => [],
                    'tags'         => [],
                    'groups'       => [],
                    'locations'    => [],
                    'moreLink'     => false,
                    'moreLinkText' => 'See all events',
                    'limit'        => 25,
                    'className'    => 'is-style-basic'
                ), $instance );

                $classes = array();
                $classes[] = 'wp-block-umichevents-events';
                $classes[] = $instance['className'];

                if( $instance['showImage'] ) {
                    $classes[] = 'umichevents-events--show-image';
                }

                $instance['className'] = implode( ' ', $classes );

                $events = UmichEvents::get(array(
                    'featured'  => (bool) $instance['featured'],
                    'ongoing'   => (bool) $instance['ongoing'],
                    'types'     => $instance['types'],
                    'tags'      => $instance['tags'],
                    'groups'    => $instance['groups'],
                    'locations' => $instance['locations'],
                    'limit'     => $instance['limit']
                ));

                ob_start();
                $template = implode( DIRECTORY_SEPARATOR, array( UmichEvents::$pluginPath, 'templates', 'block-events.tpl' ) );
                if( $tpl = locate_template( array( 'umich-events/block-events.tpl' ), false ) ) {
                    $template = $tpl;
                }

                include $template;

                return ob_get_clean();
            })
        );

        add_action( 'rest_api_init', function(){
            register_rest_route(
                'umich-events/v1', '/metadata/', array(
                    'methods' => 'GET',
                    'callback' => function( WP_REST_Request $request ){
                        ob_start();
                        $metadata = UmichEvents::getMetadata() ?: array(
                            'types'     => [],
                            'tags'      => [],
                            'sponsors'  => [],
                            'locations' => []
                        );
                        ob_clean();

                        $response = new WP_Rest_Response( array( 'metadata' => $metadata ) );
                        $response->set_status( 200 );

                        return $response;
                    }
                )
            );
        });
    }
}
UmichEvents_Block_Events::init();
