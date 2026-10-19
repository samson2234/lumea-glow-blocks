<?php
/**
 * Small rendering helpers shared by every Lumea Glow element.
 * Markup and class names mirror the approved Lovable design exactly,
 * so do not "tidy" class strings: the stylesheet is compiled from them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lg_e( $s ) {
	return esc_html( (string) $s );
}

/** Eyebrow label, optionally numbered: "01 —— About Lumea Glow". */
function lg_eyebrow( $text, $num = '', $extra = '' ) {
	if ( '' === trim( (string) $text ) && '' === trim( (string) $num ) ) {
		return '';
	}
	$cls = trim( 'eyebrow flex items-center gap-3 ' . $extra );
	$out = '<p class="' . esc_attr( $cls ) . '">';
	if ( '' !== trim( (string) $num ) ) {
		$out .= '<span>' . lg_e( $num ) . '</span><span class="h-px w-8 bg-current opacity-50"></span>';
	}
	return $out . '<span>' . lg_e( $text ) . '</span></p>';
}

/** Opening tag of a fade-up block. Close with '</div>'. */
function lg_fade( $cls = '', $delay = 0 ) {
	$style = $delay ? ' style="transition-delay:' . (int) $delay . 'ms"' : '';
	return '<div class="' . esc_attr( trim( 'fade-up ' . $cls ) ) . '"' . $style . '>';
}

function lg_is_external( $url ) {
	if ( ! preg_match( '#^https?://#i', (string) $url ) ) {
		return false;
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) !== strtolower( (string) $host );
}

/** Opening <a> tag; external links open in a new tab. */
function lg_a_open( $url, $cls = '' ) {
	$attrs = ' href="' . esc_url( $url ) . '"';
	if ( $cls ) {
		$attrs .= ' class="' . esc_attr( $cls ) . '"';
	}
	if ( lg_is_external( $url ) ) {
		$attrs .= ' target="_blank" rel="noopener noreferrer"';
	}
	return '<a' . $attrs . '>';
}

function lg_link( $url, $label, $cls ) {
	if ( '' === trim( (string) $label ) ) {
		return '';
	}
	if ( '' === trim( (string) $url ) ) {
		$url = '#';
	}
	return lg_a_open( $url, $cls ) . lg_e( $label ) . '</a>';
}

/** Attachment IDs of the bundled design images, imported on activation. */
function lg_image_ids() {
	$ids = get_option( 'lg_photo_ids', array() );
	return is_array( $ids ) ? $ids : array();
}

/** Replacement photos chosen under Pages → Lumea Glow Pages → Design photos. */
function lg_image_overrides() {
	$o = get_option( 'lg_photo_override', array() );
	return is_array( $o ) ? $o : array();
}

function lg_attachment( $id, $size ) {
	$url = $id ? wp_get_attachment_image_url( (int) $id, $size ) : false;
	if ( ! $url ) {
		return null;
	}
	$alt = get_post_meta( (int) $id, '_wp_attachment_image_alt', true );
	return array( $url, $alt ? $alt : 'Lumea Glow' );
}

/**
 * Resolve an image value to [url, alt].
 * Value may be an attachment ID (picked in WPBakery) or a design-photo key such as "hero".
 * A design photo that has been replaced on the Design photos screen is swapped everywhere.
 */
function lg_img( $val, $fallback = 'hero', $size = 'full' ) {
	$val  = trim( (string) $val );
	$keys = explode( ',', LG_IMG_KEYS );
	$ids  = lg_image_ids();
	$over = lg_image_overrides();
	$key  = null;
	if ( ctype_digit( $val ) && (int) $val > 0 ) {
		$key = array_search( (int) $val, array_map( 'intval', $ids ), true );
		if ( false === $key || empty( $over[ $key ] ) ) {
			$hit = lg_attachment( (int) $val, $size );
			if ( $hit ) {
				return $hit;
			}
		}
	}
	if ( ! is_string( $key ) || ! in_array( $key, $keys, true ) ) {
		$key = in_array( $val, $keys, true ) ? $val : $fallback;
	}
	foreach ( array( $over[ $key ] ?? 0, $ids[ $key ] ?? 0 ) as $id ) {
		$hit = lg_attachment( $id, $size );
		if ( $hit ) {
			return $hit;
		}
	}
	return array( LG_URL . 'assets/img/lg-' . $key . '.jpg', 'Lumea Glow' );
}

/** 4:5 photo with gentle zoom on hover. */
function lg_photo( $val, $fallback, $cls = '', $eager = false, $ratio = '4/5' ) {
	list( $url, $alt ) = lg_img( $val, $fallback );
	return '<figure class="' . esc_attr( trim( 'zoom relative bg-stone ' . $cls ) ) . '" style="aspect-ratio:' . esc_attr( $ratio ) . '">'
		. '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="' . ( $eager ? 'eager' : 'lazy' ) . '" decoding="async" data-no-lazy="1" class="skip-lazy no-lazyload absolute inset-0 h-full w-full object-cover">'
		. '</figure>';
}

/** Parse a WPBakery param_group value into an array of rows. */
function lg_group( $val ) {
	if ( is_array( $val ) ) {
		return $val;
	}
	$val = (string) $val;
	if ( '' === $val ) {
		return array();
	}
	$rows = function_exists( 'vc_param_group_parse_atts' ) ? vc_param_group_parse_atts( $val ) : json_decode( rawurldecode( $val ), true );
	if ( ! is_array( $rows ) ) {
		return array();
	}
	return array_values(
		array_filter(
			$rows,
			function ( $r ) {
				return is_array( $r ) && implode( '', array_map( 'strval', $r ) ) !== '';
			}
		)
	);
}

function lg_v( $row, $key ) {
	return isset( $row[ $key ] ) ? (string) $row[ $key ] : '';
}

/** Split a textarea into non-empty trimmed lines. */
function lg_lines( $s ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $s ) ), 'strlen' ) );
}

/** Rich text from a WPBakery text editor field. */
function lg_rich( $content ) {
	$content = trim( (string) $content );
	if ( '' === $content ) {
		return '';
	}
	if ( function_exists( 'wpb_js_remove_wpautop' ) ) {
		return wpb_js_remove_wpautop( $content, true );
	}
	return wpautop( do_shortcode( shortcode_unautop( $content ) ) );
}

/** Contact Form 7 by ID (numeric or the newer hash IDs). */
function lg_form( $id ) {
	$id = preg_replace( '/[^A-Za-z0-9]/', '', (string) $id );
	if ( '' === $id ) {
		return '';
	}
	return '<div class="lg-form">' . do_shortcode( '[contact-form-7 id="' . $id . '"]' ) . '</div>';
}

function lg_root( $html ) {
	return '' === $html ? '' : '<div class="lg-root">' . $html . '</div>';
}
