<?php
/**
 * One-click updates from GitHub Releases.
 *
 * WordPress asks this file "is there a newer version?" when it checks for
 * plugin updates. We answer from the latest GitHub release of LG_GH_REPO
 * that has a "lumea-glow-blocks.zip" asset. WordPress then shows the normal
 * "Update now" link (and "Enable auto-updates" works too).
 *
 * Private repo: add to wp-config.php
 *   define( 'LG_GH_TOKEN', 'github_pat_...' ); // fine-grained, read-only, this repo only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LG_GH_REPO' ) ) {
	define( 'LG_GH_REPO', 'samson2234/lumea-glow-blocks' );
}
define( 'LG_BASENAME', plugin_basename( LG_DIR . 'lumea-glow-blocks.php' ) );
define( 'LG_ASSET', 'lumea-glow-blocks.zip' );

function lg_gh_headers( $accept = 'application/vnd.github+json' ) {
	$h = array(
		'Accept'     => $accept,
		'User-Agent' => 'lumea-glow-blocks/' . LG_VER,
	);
	if ( defined( 'LG_GH_TOKEN' ) && LG_GH_TOKEN ) {
		$h['Authorization'] = 'Bearer ' . LG_GH_TOKEN;
	}
	return $h;
}

/** Latest release as [version, zip_url, notes, html_url], cached 6 hours. Null on any failure. */
function lg_gh_latest( $force = false ) {
	$cached = get_site_transient( 'lg_gh_latest' );
	if ( ! $force && is_array( $cached ) ) {
		return $cached['v'] ? $cached : null;
	}
	$out = array( 'v' => '' );
	$res = wp_remote_get(
		'https://api.github.com/repos/' . LG_GH_REPO . '/releases/latest',
		array( 'timeout' => 10, 'headers' => lg_gh_headers() )
	);
	if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
		$rel = json_decode( wp_remote_retrieve_body( $res ), true );
		foreach ( (array) ( $rel['assets'] ?? array() ) as $a ) {
			if ( LG_ASSET === ( $a['name'] ?? '' ) ) {
				$private = defined( 'LG_GH_TOKEN' ) && LG_GH_TOKEN;
				$out     = array(
					'v'     => ltrim( (string) $rel['tag_name'], 'vV' ),
					'zip'   => $private ? $a['url'] : $a['browser_download_url'],
					'notes' => (string) ( $rel['body'] ?? '' ),
					'url'   => (string) ( $rel['html_url'] ?? '' ),
				);
				break;
			}
		}
	}
	// Cache failures briefly so a GitHub hiccup never slows the admin down.
	set_site_transient( 'lg_gh_latest', $out, $out['v'] ? 6 * HOUR_IN_SECONDS : 30 * MINUTE_IN_SECONDS );
	return $out['v'] ? $out : null;
}

add_filter(
	'pre_set_site_transient_update_plugins',
	function ( $t ) {
		if ( ! is_object( $t ) ) {
			return $t;
		}
		$rel  = lg_gh_latest( ! empty( $_GET['force-check'] ) ); // "Check again" on Dashboard → Updates.
		$item = (object) array(
			'id'          => LG_BASENAME,
			'slug'        => 'lumea-glow-blocks',
			'plugin'      => LG_BASENAME,
			'new_version' => $rel ? $rel['v'] : LG_VER,
			'url'         => $rel ? $rel['url'] : '',
			'package'     => $rel ? $rel['zip'] : '',
		);
		if ( $rel && version_compare( $rel['v'], LG_VER, '>' ) ) {
			$t->response[ LG_BASENAME ] = $item;
		} else {
			unset( $t->response[ LG_BASENAME ] );
			$t->no_update[ LG_BASENAME ] = $item; // Lets the "Enable auto-updates" toggle appear.
		}
		return $t;
	}
);

/** "View details" popup on the Plugins screen. */
add_filter(
	'plugins_api',
	function ( $res, $action, $args ) {
		if ( 'plugin_information' !== $action || 'lumea-glow-blocks' !== ( $args->slug ?? '' ) ) {
			return $res;
		}
		$rel = lg_gh_latest();
		return (object) array(
			'name'          => 'Lumea Glow Blocks',
			'slug'          => 'lumea-glow-blocks',
			'version'       => $rel ? $rel['v'] : LG_VER,
			'author'        => 'CleverStack',
			'download_link' => $rel ? $rel['zip'] : '',
			'sections'      => array( 'changelog' => $rel ? nl2br( esc_html( $rel['notes'] ) ) : '' ),
		);
	},
	10,
	3
);

/** Private repos: GitHub serves release assets only with the token and an octet-stream Accept header. */
add_filter(
	'http_request_args',
	function ( $args, $url ) {
		if ( defined( 'LG_GH_TOKEN' ) && LG_GH_TOKEN && 0 === strpos( $url, 'https://api.github.com/repos/' . LG_GH_REPO . '/releases/assets/' ) ) {
			$args['headers'] = array_merge( (array) ( $args['headers'] ?? array() ), lg_gh_headers( 'application/octet-stream' ) );
		}
		return $args;
	},
	10,
	2
);

/** "Check for updates" link under the plugin on the Plugins screen. */
add_filter(
	'plugin_row_meta',
	function ( $links, $file ) {
		if ( LG_BASENAME === $file && current_user_can( 'update_plugins' ) ) {
			$links[] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lg_check_update' ), 'lg_check_update' ) ) . '">Check for updates</a>';
		}
		return $links;
	},
	10,
	2
);

add_action(
	'admin_post_lg_check_update',
	function () {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'lg_check_update' );
		delete_site_transient( 'lg_gh_latest' );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();
		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}
);

/** New version installed → drop the cached release info. */
add_action(
	'upgrader_process_complete',
	function () {
		delete_site_transient( 'lg_gh_latest' );
	}
);
