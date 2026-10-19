<?php
/**
 * Plugin Name: Lumea Glow Blocks
 * Description: The Lumea Glow website design as editable WPBakery elements (category "Lumea Glow"), plus one-click page templates under Pages → Lumea Glow Pages.
 * Version: 1.5.0
 * Author: CleverStack
 * Author URI: https://cleverfullstack.vercel.app/
 * Requires PHP: 7.4
 * Text Domain: lumea-glow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// A second copy of this plugin (e.g. an extra upload) must never fatal the site.
if ( defined( 'LG_VER' ) ) {
	return;
}

define( 'LG_VER', '1.5.0' );
define( 'LG_DIR', plugin_dir_path( __FILE__ ) );
define( 'LG_URL', plugin_dir_url( __FILE__ ) );
define( 'LG_IMG_KEYS', 'client,client2,client3,consult,consult2,fabric,fabric2,fabric3,facial,front,glow,hero,injecting,jess,jess2,mirror,needling,needling2,portrait,profile,reception,room,serum' );

require_once LG_DIR . 'inc/helpers.php';
require_once LG_DIR . 'inc/elements.php';
require_once LG_DIR . 'inc/frontend.php';
require_once LG_DIR . 'inc/seo.php';
require_once LG_DIR . 'inc/admin.php';
require_once LG_DIR . 'inc/updater.php';

register_activation_hook( __FILE__, 'lg_ensure_images' );
