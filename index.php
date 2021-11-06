<?php

/**
 * Plugin Name: ArtStation
 * Plugin URI: https://github.com/beatreichenbach/artstation-blocks
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
    // page scrapping gets detected by cloudflare, so we manually feed an html site.
    $contents_file = plugin_dir_path( __FILE__ ) . 'portfolio.html';
    if ( file_exists( $contents_file ) ) {
        $url = $contents_file;
    }
    else {
        $username = $block_attributes['username'];
        $url = sprintf( 'https://%s.artstation.com/pages/portfolio', $username );
    }
    // pretend to be a user, otherwise request returns HTTP/1.1 403
    $context = stream_context_create(
        array(
            "http" => array(
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36\n" . "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9\n"
            )
        )
    );

    // load contents from artstation portfolio page
    $contents = @file_get_contents( $url, false, $context);

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
        'editor_style' => 'artstation-gallery',
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
