<?php
/**
 * Plugin Name: Blockify Breadcrumbs
 * Plugin URI:  https://blockifywp.com/blocks/breadcrumbs
 * Description: Lightweight, customizable breadcrumbs block for WordPress.
 * Author:      Blockify
 * Author URI:  https://blockifywp.com/
 * Version:     0.0.1
 * License:     GPLv2-or-Later
 * Text Domain: blockify
 */

declare( strict_types=1 );

namespace Blockify\Breadcrumbs;

use DOMElement;
use function apply_filters;
use function function_exists;
use function get_option;
use function get_permalink;
use function get_post;
use function get_the_title;
use function home_url;
use function is_front_page;
use function is_home;
use function str_contains;
use function str_replace;
use function trailingslashit;
use function wp_get_post_parent_id;

use function register_block_type;
use function add_action;

const NS = __NAMESPACE__ . '\\';
const DS = DIRECTORY_SEPARATOR;

add_action( 'after_setup_theme', NS . 'register' );
/**
 * Registers the block.
 *
 * @since 0.0.1
 *
 * @since 1.0.0
 *
 * @return void
 */
function register() {
	register_block_type( __DIR__ . '/build' );
}

/**
 * Renders the breadcrumbs block.
 *
 * @since 0.0.2
 *
 * @param array  $attributes
 * @param string $content
 *
 * @return string
 */
function breadcrumbs_block_render_callback( array $attributes, string $content ): string {
	if ( ! str_contains( $content, 'wp-block-blockify-breadcrumbs' ) ) {
		return $content;
	}

	if ( function_exists( 'yoast_breadcrumb' ) ) {
		return yoast_breadcrumb( '<p id=breadcrumbs', '</p>', false );
	}

	$html = null;
	$post = get_post();

	if ( $post ) {
		$html = '<span>' . $post->post_title . '</span>';
	}

	$id        = wp_get_post_parent_id();
	$separator = ' / ';

	while ( $id ) {
		$url   = get_permalink( $id );
		$title = get_the_title( $id );
		$html  = "<a href=\"$url\" >$title</a>" . $separator . $html;
		$id    = wp_get_post_parent_id( $id );
	}

	if ( 'page' === get_option( 'show_on_front' ) ) {
		$url = get_permalink( get_option( 'page_on_front' ) );
	} else {
		$url = trailingslashit( home_url() );
	}

	$default = apply_filters( 'blockify_breadcrumbs_home', __( 'Home', 'blockify' ) );
	$home    = ( is_home() && is_front_page() ) ? $default : "<a href=\"$url\" >$default</a>";

	$dom = dom( str_replace(
		'</div>',
		$home . $separator . $html,
		$content
	) );

	/**
	 * @var $div DOMElement
	 */
	$div = $dom->firstChild;

	$style = ';';

	if ( isset( $attributes['layout']['type'] ) ) {
		$style .= 'display:' . $attributes['layout']['type'] . ';';
	}

	if ( isset( $attributes['layout']['justifyContent'] ) ) {
		$style .= 'justify-content:' . $attributes['layout']['justifyContent'] . ';';
	}

	if ( isset( $attributes['style']['spacing']['blockGap'] ) ) {
		$style .= 'gap:' . $attributes['style']['spacing']['blockGap'] . ';';
	}

	$div->setAttribute( 'style', $div->getAttribute( 'style' ) . $style );


	return $dom->saveHTML();
}

use function defined;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function mb_convert_encoding;
use DOMDocument;

/**
 * Returns a formatted DOMDocument object from a given string.
 *
 * @since 0.0.2
 *
 * @param string $html
 *
 * @return string
 */
function dom( string $html ): DOMDocument {
	$dom = new DOMDocument();

	if ( ! $html ) {
		return $dom;
	}

	$libxml_previous_state   = libxml_use_internal_errors( true );
	$dom->preserveWhiteSpace = true;

	if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
		$options = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD;
	} else if ( defined( 'LIBXML_HTML_NOIMPLIED' ) ) {
		$options = LIBXML_HTML_NOIMPLIED;
	} else if ( defined( 'LIBXML_HTML_NODEFDTD' ) ) {
		$options = LIBXML_HTML_NODEFDTD;
	} else {
		$options = 0;
	}

	$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ), $options );

	$dom->formatOutput = true;

	libxml_clear_errors();
	libxml_use_internal_errors( $libxml_previous_state );

	return $dom;
}
