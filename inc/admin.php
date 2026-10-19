<?php
/**
 * Pages → Lumea Glow Pages: create the design pages as drafts, or load a
 * template into an existing page (its old content is kept as a revision and
 * in the "_lg_backup_content" field, so it can be restored).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Pages that don't exist on the site yet get their final title and address. */
function lg_new_page_meta( $key ) {
	$final = array(
		'the-glassifier' => array( 'The Glassifier', 'the-glassifier' ),
		'membership'     => array( 'Membership', 'membership' ),
	);
	if ( isset( $final[ $key ] ) && ! get_page_by_path( $final[ $key ][1] ) ) {
		return $final[ $key ];
	}
	$labels = lg_templates();
	return array( $labels[ $key ] . ' (Lumea)', '' );
}

function lg_photo_labels() {
	return array(
		'hero'      => 'Home hero (wide, soft portrait)',
		'jess'      => 'Jess standing (Meet RN Jess)',
		'jess2'     => 'Jess seated',
		'consult2'  => 'Jess with client (Lines & Wrinkles)',
		'consult'   => 'Jess treating (Lines & Wrinkles)',
		'injecting' => 'Skin boosters treatment',
		'needling'  => 'Microneedling 1',
		'needling2' => 'Microneedling 2',
		'facial'    => 'Facial treatment',
		'portrait'  => 'Soft portrait 1',
		'profile'   => 'Soft portrait 2 (Membership)',
		'glow'      => 'Moody portrait (The Glassifier)',
		'serum'     => 'Serum pour',
		'fabric'    => 'Abstract fabric 1',
		'fabric2'   => 'Abstract fabric 2',
		'fabric3'   => 'Abstract fabric 3',
		'client'    => 'Client with mirror 1',
		'client2'   => 'Client with mirror 2',
		'client3'   => 'Client seated with mirror',
		'reception' => 'Waiting area',
		'room'      => 'Treatment room',
		'mirror'    => 'Lumea Glow mirror',
		'front'     => 'Shopfront',
	);
}

function lg_templates() {
	return array(
		'home'               => 'Home',
		'lines-wrinkles'     => 'Lines, Wrinkles & Volume',
		'skin-rejuvenation'  => 'Skin Rejuvenation & Facials',
		'lhala-peel'         => 'The LHALA Peel',
		'microneedling'      => 'Microneedling',
		'the-glassifier'     => 'The Glassifier',
		'skin-boosters'      => 'Skin Boosters & Rejuran',
		'membership'         => 'Membership',
		'find-my-treatment'  => 'Find My Treatment',
		'contact'            => 'Contact',
	);
}

/** Import the bundled design photos into the Media Library once. */
function lg_ensure_images() {
	$ids     = lg_image_ids();
	$changed = false;
	foreach ( explode( ',', LG_IMG_KEYS ) as $key ) {
		if ( ! empty( $ids[ $key ] ) && get_post( (int) $ids[ $key ] ) ) {
			continue;
		}
		$src = LG_DIR . 'assets/img/lg-' . $key . '.jpg';
		if ( ! file_exists( $src ) ) {
			continue;
		}
		$up = wp_upload_bits( 'lumea-' . $key . '.jpg', null, (string) file_get_contents( $src ) );
		if ( ! empty( $up['error'] ) ) {
			continue;
		}
		$id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Lumea Glow ' . $key . ' (design photo)',
				'post_status'    => 'inherit',
			),
			$up['file']
		);
		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
		update_post_meta( $id, '_wp_attachment_image_alt', 'Lumea Glow' );
		$ids[ $key ] = $id;
		$changed     = true;
	}
	if ( $changed ) {
		update_option( 'lg_photo_ids', $ids, false );
	}
}

function lg_template_content( $key ) {
	$file = LG_DIR . 'templates/' . $key . '.txt';
	if ( ! array_key_exists( $key, lg_templates() ) || ! file_exists( $file ) ) {
		return '';
	}
	lg_ensure_images();
	$ids = lg_image_ids();
	return preg_replace_callback(
		'/\{\{img:([a-z0-9]+)\}\}/',
		function ( $m ) use ( $ids ) {
			return ! empty( $ids[ $m[1] ] ) ? (string) (int) $ids[ $m[1] ] : $m[1];
		},
		trim( (string) file_get_contents( $file ) )
	);
}

add_action(
	'admin_menu',
	function () {
		add_pages_page( 'Lumea Glow Pages', 'Lumea Glow Pages', 'edit_pages', 'lumea-glow-pages', 'lg_admin_page' );
	}
);

function lg_admin_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$msg = isset( $_GET['lg_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['lg_msg'] ) ) : '';
	$pid = isset( $_GET['lg_page'] ) ? absint( $_GET['lg_page'] ) : 0;
	echo '<div class="wrap"><h1>Lumea Glow Pages</h1>';
	if ( 'all' === $msg ) {
		echo '<div class="notice notice-success"><p>' . esc_html( absint( $_GET['lg_n'] ?? 0 ) ) . ' draft pages created. Find them under Pages → All Pages (drafts).</p></div>';
	}
	if ( 'photos' === $msg ) {
		echo '<div class="notice notice-success"><p>Design photos saved. Every page using them is updated. If you use a cache plugin, clear its cache.</p></div>';
	}
	if ( $msg && $pid ) {
		$text = 'created' === $msg ? 'Draft page created.' : 'Page content replaced. The previous version is saved in Revisions.';
		echo '<div class="notice notice-success"><p>' . esc_html( $text ) . ' <a href="' . esc_url( get_edit_post_link( $pid ) ) . '">Edit</a> · <a href="' . esc_url( get_preview_post_link( $pid ) ) . '" target="_blank">Preview</a></p></div>';
	}
	echo '<p><strong>Create draft</strong> makes a new, unpublished page you can safely preview. <strong>Load into page</strong> replaces the content of an existing page (use this at go-live so the page keeps its address); the old content is kept in Revisions.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:12px 0 18px" onsubmit="return confirm(\'Create all 10 pages as drafts?\');">';
	wp_nonce_field( 'lg_install_all' );
	echo '<input type="hidden" name="action" value="lg_install_all">';
	submit_button( 'Create all 10 as drafts', 'primary', 'submit', false );
	echo ' <span class="description">The Glassifier and Membership are created with their final titles and addresses (/the-glassifier/, /membership/), ready to publish.</span></form>';
	echo '<table class="widefat striped" style="max-width:980px"><thead><tr><th>Template</th><th>New draft</th><th>Load into existing page</th></tr></thead><tbody>';
	foreach ( lg_templates() as $key => $label ) {
		$action = esc_url( admin_url( 'admin-post.php' ) );
		echo '<tr><td><strong>' . esc_html( $label ) . '</strong></td><td>';
		echo '<form method="post" action="' . $action . '">';
		wp_nonce_field( 'lg_install_' . $key );
		echo '<input type="hidden" name="action" value="lg_install"><input type="hidden" name="tpl" value="' . esc_attr( $key ) . '"><input type="hidden" name="mode" value="new">';
		submit_button( 'Create draft', 'primary small', 'submit', false );
		echo '</form></td><td>';
		echo '<form method="post" action="' . $action . '" onsubmit="return confirm(\'Replace the content of the selected page with the ' . esc_js( $label ) . ' template? The old content stays in Revisions.\');">';
		wp_nonce_field( 'lg_install_' . $key );
		echo '<input type="hidden" name="action" value="lg_install"><input type="hidden" name="tpl" value="' . esc_attr( $key ) . '"><input type="hidden" name="mode" value="replace">';
		wp_dropdown_pages(
			array(
				'name'              => 'target',
				'show_option_none'  => '— choose page —',
				'option_none_value' => '0',
				'post_status'       => array( 'publish', 'draft', 'private', 'pending' ),
			)
		);
		echo ' ';
		submit_button( 'Load into page', 'secondary small', 'submit', false );
		echo '</form></td></tr>';
	}
	echo '</tbody></table>';
	lg_admin_photos();
	echo '</div>';
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( false !== strpos( (string) $hook, 'lumea-glow-pages' ) ) {
			wp_enqueue_media();
		}
	}
);

function lg_admin_photos() {
	$ids  = lg_image_ids();
	$over = lg_image_overrides();
	echo '<h2 id="lg-photos" style="margin-top:36px">Design photos</h2>';
	echo '<p>These are the clinic photos used across the site. Choose another photo from the Media Library to replace one <strong>everywhere at once</strong>. To change just one spot, change the image in that element instead.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'lg_photos' );
	echo '<input type="hidden" name="action" value="lg_photos"><table class="widefat striped" style="max-width:980px"><tbody>';
	foreach ( lg_photo_labels() as $k => $label ) {
		$cur = ! empty( $over[ $k ] ) ? (int) $over[ $k ] : 0;
		list( $url ) = lg_img( $k, $k, 'thumbnail' );
		echo '<tr><td style="width:96px"><img class="lg-ph-img" src="' . esc_url( $url ) . '" style="width:80px;height:80px;object-fit:cover;display:block"></td>';
		echo '<td><strong>' . esc_html( $label ) . '</strong><br><span class="description">' . ( $cur ? 'Replaced with your photo' : 'Original clinic photo' ) . '</span></td>';
		echo '<td style="text-align:right"><input type="hidden" class="lg-ph-id" name="lg_photo[' . esc_attr( $k ) . ']" value="' . esc_attr( $cur ? $cur : '' ) . '">';
		echo '<button type="button" class="button lg-ph-pick">Choose photo</button> <button type="button" class="button-link lg-ph-reset" data-src="' . esc_url( LG_URL . 'assets/img/lg-' . $k . '.jpg' ) . '">Reset</button></td></tr>';
	}
	echo '</tbody></table>';
	submit_button( 'Save design photos' );
	echo '</form>';
	?>
	<script>
	(function ($) {
		$(document).on('click', '.lg-ph-pick', function (e) {
			e.preventDefault();
			var row = $(this).closest('tr');
			var frame = wp.media({ title: 'Choose photo', library: { type: 'image' }, multiple: false, button: { text: 'Use this photo' } });
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				row.find('.lg-ph-id').val(a.id);
				row.find('.lg-ph-img').attr('src', (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url));
			});
			frame.open();
		});
		$(document).on('click', '.lg-ph-reset', function (e) {
			e.preventDefault();
			var row = $(this).closest('tr');
			row.find('.lg-ph-id').val('');
			row.find('.lg-ph-img').attr('src', $(this).data('src'));
		});
	})(jQuery);
	</script>
	<?php
}

function lg_create_draft( $key, $content ) {
	list( $title, $slug ) = lg_new_page_meta( $key );
	$res = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			)
		),
		true
	);
	if ( ! is_wp_error( $res ) ) {
		update_post_meta( $res, '_wpb_vc_js_status', 'true' );
	}
	return $res;
}

add_action(
	'admin_post_lg_install_all',
	function () {
		check_admin_referer( 'lg_install_all' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( 'Not allowed.' );
		}
		$n = 0;
		foreach ( array_keys( lg_templates() ) as $key ) {
			$res = lg_create_draft( $key, lg_template_content( $key ) );
			$n  += is_wp_error( $res ) ? 0 : 1;
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=page&page=lumea-glow-pages&lg_msg=all&lg_n=' . $n ) );
		exit;
	}
);

add_action(
	'admin_post_lg_photos',
	function () {
		check_admin_referer( 'lg_photos' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( 'Not allowed.' );
		}
		$in  = isset( $_POST['lg_photo'] ) && is_array( $_POST['lg_photo'] ) ? wp_unslash( $_POST['lg_photo'] ) : array();
		$out = array();
		foreach ( array_keys( lg_photo_labels() ) as $k ) {
			$id = isset( $in[ $k ] ) ? absint( $in[ $k ] ) : 0;
			if ( $id && wp_attachment_is_image( $id ) ) {
				$out[ $k ] = $id;
			}
		}
		update_option( 'lg_photo_override', $out, false );
		if ( function_exists( 'speedycache_delete_cache' ) ) {
			speedycache_delete_cache();
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=page&page=lumea-glow-pages&lg_msg=photos#lg-photos' ) );
		exit;
	}
);

add_action(
	'admin_post_lg_install',
	function () {
		$key = isset( $_POST['tpl'] ) ? sanitize_key( wp_unslash( $_POST['tpl'] ) ) : '';
		check_admin_referer( 'lg_install_' . $key );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( 'Not allowed.' );
		}
		$content = lg_template_content( $key );
		if ( '' === $content ) {
			wp_die( 'Template not found.' );
		}
		$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'new';
		$labels = lg_templates();

		if ( 'replace' === $mode ) {
			$target = isset( $_POST['target'] ) ? absint( $_POST['target'] ) : 0;
			$page   = $target ? get_post( $target ) : null;
			if ( ! $page || 'page' !== $page->post_type || ! current_user_can( 'edit_post', $target ) ) {
				wp_die( 'Please choose a page first. <a href="javascript:history.back()">Back</a>' );
			}
			update_post_meta( $target, '_lg_backup_content', wp_slash( $page->post_content ) );
			$res = wp_update_post( wp_slash( array( 'ID' => $target, 'post_content' => $content ) ), true );
			$id  = is_wp_error( $res ) ? 0 : $target;
			$msg = 'replaced';
		} else {
			$res = lg_create_draft( $key, $content );
			$id  = is_wp_error( $res ) ? 0 : (int) $res;
			$msg = 'created';
		}
		if ( ! $id ) {
			wp_die( esc_html( is_wp_error( $res ) ? $res->get_error_message() : 'Could not save the page.' ) );
		}
		update_post_meta( $id, '_wpb_vc_js_status', 'true' );
		wp_safe_redirect( admin_url( 'edit.php?post_type=page&page=lumea-glow-pages&lg_msg=' . $msg . '&lg_page=' . $id ) );
		exit;
	}
);
