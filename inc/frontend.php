<?php
/**
 * Front end: assets, body class, and the Lumea header/footer on pages built
 * with Lumea Glow elements. Salient's own header/footer are hidden on those
 * pages only; every other page keeps the theme as it is.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lg_page_uses_blocks() {
	static $uses = null;
	if ( null !== $uses ) {
		return $uses;
	}
	$post = get_queried_object();
	$uses = is_singular() && $post instanceof WP_Post && false !== strpos( $post->post_content, '[lg_' );
	return $uses;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! lg_page_uses_blocks() ) {
			return;
		}
		wp_enqueue_style( 'lg-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,400&family=DM+Sans:wght@400;500&display=swap', array(), null );
		wp_enqueue_style( 'lg-design', LG_URL . 'assets/lumea.css', array(), LG_VER );
		wp_enqueue_script( 'lg-design', LG_URL . 'assets/lumea.js', array(), LG_VER, true );
	},
	100
);

/** Open the Google Fonts connections early (fonts are render-critical on these pages). */
add_filter(
	'wp_resource_hints',
	function ( $urls, $type ) {
		if ( 'preconnect' === $type && lg_page_uses_blocks() ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
		}
		return $urls;
	},
	10,
	2
);

add_filter(
	'body_class',
	function ( $classes ) {
		if ( lg_page_uses_blocks() ) {
			$classes[] = 'lg-page';
		}
		return $classes;
	}
);

function lg_part( $name, $vars = array() ) {
	$html = (string) file_get_contents( LG_DIR . 'parts/' . $name . '.html' );
	foreach ( $vars as $k => $v ) {
		$html = str_replace( '{{' . $k . '}}', $v, $html );
	}
	return $html;
}

add_filter(
	'the_content',
	function ( $content ) {
		static $done = false;
		if ( $done || ! lg_page_uses_blocks() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || get_the_ID() !== $post->ID ) {
			return $content;
		}
		$done    = true;
		$is_home = false !== strpos( $post->post_content, '[lg_hero_home' );
		$logo_d  = '<img class="lg-logo-img" src="' . esc_url( LG_URL . 'assets/lg-logo-dark.png' ) . '" alt="Lumea Glow" width="1400" height="168">';
		$logo_l  = '<img class="lg-logo-img lg-logo-lg" src="' . esc_url( LG_URL . 'assets/lg-logo-light.png' ) . '" alt="Lumea Glow" width="1400" height="168">';
		$header  = lg_part( 'header', array( 'HDRCLS' => $is_home ? ' bg-transparent' : ' border-b border-foreground/10 bg-ivory', 'LOGO_DARK' => $logo_d ) );
		if ( $is_home ) {
			$header = str_replace( '<header class=', '<header data-lg-home="1" class=', $header );
		}
		$footer = lg_part( 'footer', array( 'YEAR' => gmdate( 'Y' ), 'LOGO_LIGHT' => $logo_l ) );
		return '<div class="lg-root lg-chrome">' . $header . '</div>' . $content . '<div class="lg-root lg-chrome">' . $footer . '</div>';
	},
	99
);
