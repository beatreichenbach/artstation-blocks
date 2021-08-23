<?php

/**
 * Plugin Name: ArtStation
 * Plugin URI: https://github.com/beatreichenbach
 * Description: A collection of blocks to integrate ArtStation into WordPress.
 * Version: 1.0.0
 * Author: Beat Reichenbach
 *
 * @package artstation
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'load_block_attributes' ) ) {
    function load_block_attributes( $block_attributes ) {
        // adding block attributes as it is not supported yet.
        $attributes = array();

        if( $block_attributes['anchor'] )
            $attributes['id'] = $block_attributes['anchor'];

        if( $block_attributes['align'] )
            $attributes['class'] = sprintf( 'align%s', $block_attributes['align'] );

        return get_block_wrapper_attributes($attributes);
    }
}

function artstation_gallery_render_block( $block_attributes, $content ) {

	$username = $block_attributes['username'];
	$url = sprintf( 'https://%s.artstation.com/pages/portfolio', $username );

	// load contents from artstation portfolio page
	$contents = file_get_contents( $url );

    if ( false === $contents ) {
    	return null;
    }

    libxml_use_internal_errors( true );
    $artstation_doc = new DOMDocument();
    $artstation_doc->loadHTML( $contents );
    libxml_use_internal_errors( false );

    // create new doc with album items and import from artstation doc
    $doc = new DOMDocument();
    $album = $doc->createElement( 'div' );
    $album->setAttribute( 'class', 'album-grid' );
    $doc->appendChild( $album );

    $divs = $artstation_doc->getElementsByTagName( 'div' );
    foreach( $divs as $div ) {
        if ( $div->getAttribute( 'class') == 'album-grid-item' ) {
            $item = $doc->importNode( $div, true );
            $album->appendChild( $item );
        }
    }

    // change links to point to community page
    $links = $doc->getElementsByTagName( 'a' );
    foreach($links as $link) {
        $href = $link->getAttribute( 'href' );
        $href = str_replace( '/projects/', 'https://www.artstation.com/artwork/', $href );
        $link->setAttribute( 'href', $href );
    }

    // mark cloudflare script for editor js
    $scripts = $doc->getElementsByTagName( 'script' );
    foreach($scripts as $script) {
        $src = $script->getAttribute( 'src' );
        if(strpos($src, 'cloudflare') !== false){
        	$script->setAttribute( 'id', 'cloudflare' );
        }
    }

    $blockProps = load_block_attributes( $block_attributes );

   	$html = sprintf( '<div %s>%s</div>', $blockProps, $doc->saveHTML( $album ) );
    return $html;

}

function artstation_gallery_register_block() {

	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

    // automatically load dependencies and version
    $asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php');

	wp_register_script(
		'artstation-gallery',
		plugins_url( '/build/index.js', __FILE__ ),
        $asset_file['dependencies'],
        $asset_file['version']
	);

	wp_register_style(
		'artstation-gallery',
		plugins_url( 'style.css', __FILE__ ),
		array( ),
		filemtime( plugin_dir_path( __FILE__ ) . 'style.css' )
	);

	register_block_type( 'artstation/gallery', array(
		'api_version' => 2,
		'style' => 'artstation-gallery',
		'editor_script' => 'artstation-gallery',
		'render_callback' => 'artstation_gallery_render_block',
		'attributes' => array(
			'username' => array(
				'type' => 'string',
				'default' => ''
				),
            'anchor' => array(
                'type' => 'string',
                'default' => ''
                ),
            'align' => array(
                'type' => 'string',
                'default' => ''
                ),
			)
		)
	);

}
add_action( 'init', 'artstation_gallery_register_block' );
