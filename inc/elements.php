<?php
/**
 * Lumea Glow WPBakery elements.
 *
 * Every field defaults to empty: WPBakery does not save empty fields, so a
 * non-empty default would come back whenever someone clears a field.
 * Empty text simply isn't rendered; an empty image falls back to the design photo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- field builders ---------- */

function lg_f( $type, $name, $heading, $extra = array() ) {
	return array_merge(
		array(
			'type'       => $type,
			'param_name' => $name,
			'heading'    => $heading,
			'value'      => '',
		),
		$extra
	);
}
function lg_text( $name, $heading, $extra = array() ) {
	return lg_f( 'textfield', $name, $heading, $extra );
}
function lg_area( $name, $heading, $extra = array() ) {
	return lg_f( 'textarea', $name, $heading, $extra );
}
function lg_html( $heading = 'Text', $extra = array() ) {
	return lg_f( 'textarea_html', 'content', $heading, $extra );
}
function lg_image( $name = 'image', $heading = 'Image', $extra = array() ) {
	return lg_f( 'attach_image', $name, $heading, array_merge( array( 'description' => 'Leave empty to use the design photo.' ), $extra ) );
}
function lg_images( $name = 'images', $heading = 'Images', $extra = array() ) {
	return lg_f( 'attach_images', $name, $heading, $extra );
}
function lg_check( $name, $heading, $extra = array() ) {
	return lg_f( 'checkbox', $name, $heading, array_merge( array( 'value' => array( 'Yes' => 'yes' ) ), $extra ) );
}
function lg_select( $name, $heading, $options, $std, $extra = array() ) {
	return lg_f( 'dropdown', $name, $heading, array_merge( array( 'value' => $options, 'std' => $std ), $extra ) );
}
function lg_items( $name, $heading, $fields, $extra = array() ) {
	return lg_f( 'param_group', $name, $heading, array_merge( array( 'params' => $fields ), $extra ) );
}
function lg_num() {
	return lg_text( 'num', 'Section number', array( 'description' => 'Optional, e.g. 01' ) );
}
function lg_eyebrow_f() {
	return lg_text( 'eyebrow', 'Small label (eyebrow)' );
}
function lg_heading_f( $name = 'heading', $heading = 'Heading' ) {
	return lg_text( $name, $heading, array( 'admin_label' => true ) );
}
function lg_button_f( $prefix = 'btn', $group = 'Button' ) {
	return array(
		lg_text( $prefix . '_label', 'Button text', array( 'group' => $group ) ),
		lg_text( $prefix . '_url', 'Button link', array( 'group' => $group, 'description' => 'Full URL, or a page path like /membership/' ) ),
	);
}
function lg_details_f() {
	$g = 'Contact details';
	return array(
		lg_text( 'address', 'Address', array( 'group' => $g ) ),
		lg_text( 'maps_url', 'Google Maps link', array( 'group' => $g ) ),
		lg_text( 'phone', 'Phone', array( 'group' => $g ) ),
		lg_text( 'email', 'Email', array( 'group' => $g ) ),
		lg_text( 'instagram', 'Instagram handle', array( 'group' => $g, 'description' => 'e.g. @lumeaglow.clinic' ) ),
		lg_text( 'instagram_url', 'Instagram link', array( 'group' => $g ) ),
	);
}

/* ---------- registry ---------- */

$GLOBALS['lg_elements'] = array();

function lg_register( $tag, $name, $desc, $params, $render ) {
	$GLOBALS['lg_elements'][ $tag ] = compact( 'name', 'desc', 'params', 'render' );
	add_shortcode(
		$tag,
		function ( $atts, $content = '' ) use ( $tag ) {
			$el       = $GLOBALS['lg_elements'][ $tag ];
			$defaults = array();
			foreach ( $el['params'] as $p ) {
				if ( 'content' === $p['param_name'] ) {
					continue;
				}
				$defaults[ $p['param_name'] ] = 'dropdown' === $p['type'] ? $p['std'] : '';
			}
			$a = shortcode_atts( $defaults, (array) $atts, $tag );
			return lg_root( call_user_func( $el['render'], $a, (string) $content ) );
		}
	);
}

add_action(
	'vc_before_init',
	function () {
		if ( ! function_exists( 'vc_map' ) ) {
			return;
		}
		foreach ( $GLOBALS['lg_elements'] as $tag => $el ) {
			vc_map(
				array(
					'name'                    => $el['name'],
					'base'                    => $tag,
					'description'             => $el['desc'],
					'category'                => 'Lumea Glow',
					'icon'                    => LG_URL . 'assets/icon.svg',
					'show_settings_on_create' => false,
					'params'                  => $el['params'],
				)
			);
		}
	}
);

/* ---------- shared fragments ---------- */

function lg_details( $a ) {
	$rows = array();
	if ( $a['address'] ) {
		$rows[] = array( 'Visit', $a['maps_url'] ? $a['maps_url'] : 'https://maps.google.com/?q=' . rawurlencode( $a['address'] ), $a['address'] );
	}
	if ( $a['phone'] ) {
		$rows[] = array( 'Call', 'tel:' . preg_replace( '/[^0-9+]/', '', $a['phone'] ), $a['phone'] );
	}
	if ( $a['email'] ) {
		$rows[] = array( 'Email', 'mailto:' . $a['email'], $a['email'] );
	}
	if ( $a['instagram'] ) {
		$rows[] = array( 'Instagram', $a['instagram_url'], $a['instagram'] );
	}
	if ( ! $rows ) {
		return '';
	}
	$o = '<ul class="grid gap-5">';
	foreach ( $rows as $r ) {
		$o .= '<li><p class="eyebrow text-muted-foreground">' . $r[0] . '</p>' . lg_link( $r[1], $r[2], 'font-serif text-2xl hover:opacity-60' ) . '</li>';
	}
	return $o . '</ul>';
}

/* ======================================================================
   HOME
   ====================================================================== */

lg_register(
	'lg_hero_home',
	'Home Hero',
	'Full-screen photo with headline',
	array_merge(
		array(
			lg_image( 'image', 'Background photo' ),
			lg_eyebrow_f(),
			lg_heading_f( 'title', 'Headline' ),
			lg_area( 'text', 'Intro text' ),
			lg_text( 'cred', 'Registration line' ),
		),
		lg_button_f(),
		array(
			lg_text( 'link_label', 'Second link text', array( 'group' => 'Button' ) ),
			lg_text( 'link_url', 'Second link', array( 'group' => 'Button' ) ),
		)
	),
	function ( $a ) {
		list( $url, $alt ) = lg_img( $a['image'], 'hero' );
		$o  = '<section class="relative h-[100svh] min-h-[620px] overflow-hidden">';
		$o .= '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" fetchpriority="high" data-no-lazy="1" class="skip-lazy no-lazyload absolute inset-0 h-full w-full object-cover object-[70%_center]">';
		$o .= '<div class="absolute inset-0 bg-ivory/30 md:bg-transparent"></div><div class="lg-hero-veil absolute inset-0"></div>';
		$o .= '<div class="wrap relative flex h-full flex-col justify-end pb-16 md:justify-center md:pb-0">' . lg_fade( 'max-w-xl' );
		$o .= lg_eyebrow( $a['eyebrow'] );
		if ( $a['title'] ) {
			$o .= '<h1 class="display mt-6">' . lg_e( $a['title'] ) . '</h1>';
		}
		if ( $a['text'] ) {
			$o .= '<p class="mt-6 max-w-md">' . lg_e( $a['text'] ) . '</p>';
		}
		$btns = lg_link( $a['btn_url'], $a['btn_label'], 'btn btn-primary' ) . lg_link( $a['link_url'], $a['link_label'], 'link-line self-start' );
		if ( $btns ) {
			$o .= '<div class="mt-10 flex flex-col gap-6 md:flex-row md:items-center">' . $btns . '</div>';
		}
		if ( $a['cred'] ) {
			$o .= '<p class="eyebrow mt-6 text-foreground/70">' . lg_e( $a['cred'] ) . '</p>';
		}
		return $o . '</div></div></section>';
	}
);

lg_register(
	'lg_trust',
	'Trust Row',
	'Three short statements between lines',
	array( lg_items( 'items', 'Statements', array( lg_text( 'text', 'Text', array( 'admin_label' => true ) ) ) ) ),
	function ( $a ) {
		$rows = lg_group( $a['items'] );
		if ( ! $rows ) {
			return '';
		}
		$o = '<div class="border-y border-foreground/20"><ul class="wrap grid divide-y divide-foreground/20 md:grid-cols-3 md:divide-x md:divide-y-0">';
		foreach ( $rows as $r ) {
			$o .= '<li class="eyebrow py-5 text-center">' . lg_e( lg_v( $r, 'text' ) ) . '</li>';
		}
		return $o . '</ul></div>';
	}
);

lg_register(
	'lg_about',
	'Heading + Text',
	'Heading on the left, paragraphs and a quote on the right',
	array( lg_num(), lg_eyebrow_f(), lg_heading_f(), lg_html( 'Paragraphs' ), lg_area( 'pull', 'Quote (italic, optional)' ) ),
	function ( $a, $content ) {
		$o  = '<section class="section-pad"><div class="wrap grid gap-12 md:grid-cols-12">';
		$o .= lg_fade( 'md:col-span-5' ) . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$o .= '<h2 class="h-section mt-5">' . lg_e( $a['heading'] ) . '</h2>';
		}
		$o .= '</div>' . lg_fade( 'md:col-span-6 md:col-start-7', 100 ) . '<div class="space-y-5">' . lg_rich( $content ) . '</div>';
		if ( $a['pull'] ) {
			$o .= '<p class="pull mt-10 border-l border-taupe pl-6">' . lg_e( $a['pull'] ) . '</p>';
		}
		return $o . '</div></div></section>';
	}
);

lg_register(
	'lg_image_text',
	'Photo + Text (half and half)',
	'Edge-to-edge photo beside text, stone or espresso background',
	array_merge(
		array(
			lg_select( 'tone', 'Background', array( 'Stone (light)' => 'stone', 'Espresso (dark)' => 'espresso' ), 'stone' ),
			lg_select( 'side', 'Photo side', array( 'Left' => 'left', 'Right' => 'right' ), 'left' ),
			lg_select( 'heading_style', 'Heading style', array( 'Regular' => 'regular', 'Large italic' => 'italic' ), 'regular' ),
			lg_num(),
			lg_eyebrow_f(),
			lg_heading_f(),
			lg_html( 'Paragraphs' ),
			lg_text( 'note', 'Small line under text (optional)' ),
			lg_text( 'cred', 'Registration line with divider (optional)' ),
			lg_image( 'image', 'Photo', array( 'group' => 'Photo' ) ),
		),
		lg_button_f(),
		array( lg_select( 'btn_style', 'Button style', array( 'Solid' => 'primary', 'Outline' => 'outline' ), 'primary', array( 'group' => 'Button' ) ) )
	),
	function ( $a, $content ) {
		$dark  = 'espresso' === $a['tone'];
		$photo = lg_photo( $a['image'], $dark ? 'reception' : 'jess', 'md:h-full' );
		$t     = lg_fade( 'section-pad flex flex-col justify-center px-5 md:px-16 lg:px-24' ) . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$t .= 'italic' === $a['heading_style']
				? '<h2 class="mt-5 font-serif text-5xl italic md:text-7xl">' . lg_e( $a['heading'] ) . '</h2>'
				: '<h2 class="h-section mt-5">' . lg_e( $a['heading'] ) . '</h2>';
		}
		$rich = lg_rich( $content );
		if ( $rich ) {
			$t .= '<div class="' . ( $dark ? 'mt-6 max-w-lg space-y-4 opacity-90' : 'mt-6 space-y-4' ) . '">' . $rich . '</div>';
		}
		if ( $a['note'] ) {
			$t .= '<p class="eyebrow mt-6 opacity-80">' . lg_e( $a['note'] ) . '</p>';
		}
		if ( $a['cred'] ) {
			$t .= '<p class="eyebrow mt-8 border-t border-foreground/25 pt-5 text-foreground/70">' . lg_e( $a['cred'] ) . '</p>';
		}
		$t .= lg_link( $a['btn_url'], $a['btn_label'], 'outline' === $a['btn_style'] ? 'btn btn-outline mt-8 self-start' : 'btn btn-primary mt-8 self-start' ) . '</div>';
		$inner = 'right' === $a['side'] ? $t . $photo : $photo . $t;
		return '<section class="' . ( $dark ? 'bg-espresso text-ivory' : 'bg-stone' ) . '"><div class="grid md:grid-cols-2">' . $inner . '</div></section>';
	}
);

lg_register(
	'lg_section_head',
	'Section Heading',
	'Numbered heading with a line under it (use above Treatment Rows)',
	array( lg_num(), lg_eyebrow_f(), lg_heading_f() ),
	function ( $a ) {
		$o = '<section class="lg-pt"><div class="wrap">' . lg_fade( 'flex flex-col justify-between gap-6 border-b border-foreground/25 pb-10 md:flex-row md:items-end' ) . '<div>' . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$o .= '<h2 class="h-section mt-5">' . lg_e( $a['heading'] ) . '</h2>';
		}
		return $o . '</div></div></div></section>';
	}
);

lg_register(
	'lg_index_row',
	'Treatment Row',
	'Big number, title, text and photo (alternate sides with "Photo on right")',
	array(
		lg_text( 'n', 'Number', array( 'description' => 'e.g. 01' ) ),
		lg_heading_f( 'title', 'Title' ),
		lg_area( 'text', 'Text' ),
		lg_text( 'url', 'Link to page' ),
		lg_text( 'link_label', 'Link text', array( 'description' => 'Defaults to "Find out more"' ) ),
		lg_check( 'flip', 'Photo on right' ),
		lg_check( 'last', 'Last row in the list', array( 'description' => 'Removes the bottom line and adds section spacing' ) ),
		lg_image( 'image', 'Photo', array( 'group' => 'Photo' ) ),
	),
	function ( $a ) {
		$flip  = 'yes' === $a['flip'];
		$last  = 'yes' === $a['last'];
		$label = $a['link_label'] ? $a['link_label'] : 'Find out more';
		$url   = $a['url'] ? $a['url'] : '#';
		$o     = '<div class="' . ( $last ? 'wrap lg-pb' : 'wrap' ) . '"><div class="' . ( $last ? 'grid items-center gap-10 py-14 md:grid-cols-12' : 'grid items-center gap-10 py-14 md:grid-cols-12 border-b border-foreground/25' ) . '">';
		$o    .= '<div class="' . ( $flip ? 'md:col-span-5 md:order-2 md:col-start-8' : 'md:col-span-5' ) . '">' . lg_a_open( $url ) . lg_photo( $a['image'], 'profile' ) . '</a></div>';
		$o    .= lg_fade( $flip ? 'md:col-span-6 md:order-1 md:col-start-1' : 'md:col-span-6 md:col-start-7' );
		if ( $a['n'] ) {
			$o .= '<p class="font-serif text-6xl text-taupe">' . lg_e( $a['n'] ) . '</p>';
		}
		if ( $a['title'] ) {
			$o .= '<h3 class="mt-4 font-serif text-4xl md:text-5xl">' . lg_e( $a['title'] ) . '</h3>';
		}
		if ( $a['text'] ) {
			$o .= '<p class="mt-5 max-w-lg text-muted-foreground">' . lg_e( $a['text'] ) . '</p>';
		}
		$o .= lg_link( $url, $label, 'link-line mt-8 inline-block' );
		return $o . '</div></div></div>';
	}
);

lg_register(
	'lg_index_pair',
	'Treatment Row (two cards)',
	'Number and intro on the left, two treatment cards on the right',
	array(
		lg_text( 'n', 'Number' ),
		lg_heading_f( 'title', 'Title' ),
		lg_area( 'text', 'Text' ),
		lg_check( 'last', 'Last row in the list' ),
		lg_items(
			'cards',
			'Cards',
			array(
				lg_text( 'title', 'Card title', array( 'admin_label' => true ) ),
				lg_text( 'price', 'Price', array( 'description' => 'e.g. from $250' ) ),
				lg_area( 'text', 'Text' ),
				lg_text( 'url', 'Link to page' ),
				lg_image( 'image', 'Photo' ),
			),
			array( 'group' => 'Cards' )
		),
	),
	function ( $a ) {
		$last = 'yes' === $a['last'];
		$o    = '<div class="' . ( $last ? 'wrap lg-pb' : 'wrap' ) . '"><div class="' . ( $last ? 'grid gap-10 py-14 md:grid-cols-12' : 'grid gap-10 border-b border-foreground/25 py-14 md:grid-cols-12' ) . '">' . lg_fade( 'md:col-span-4' );
		if ( $a['n'] ) {
			$o .= '<p class="font-serif text-6xl text-taupe">' . lg_e( $a['n'] ) . '</p>';
		}
		if ( $a['title'] ) {
			$o .= '<h3 class="mt-4 font-serif text-4xl">' . lg_e( $a['title'] ) . '</h3>';
		}
		if ( $a['text'] ) {
			$o .= '<p class="mt-4 text-muted-foreground">' . lg_e( $a['text'] ) . '</p>';
		}
		$o .= '</div><div class="grid gap-8 sm:grid-cols-2 md:col-span-8">';
		$fb = array( 'needling', 'glow' );
		foreach ( lg_group( $a['cards'] ) as $i => $c ) {
			$url = lg_v( $c, 'url' ) ? lg_v( $c, 'url' ) : '#';
			$o  .= lg_fade( '', $i * 100 ) . lg_a_open( $url, 'block' ) . lg_photo( lg_v( $c, 'image' ), $fb[ $i % 2 ] );
			$o  .= '<div class="mt-5 flex items-baseline justify-between border-b border-foreground/25 pb-3"><h4 class="eyebrow text-sm">' . lg_e( lg_v( $c, 'title' ) ) . '</h4><span class="eyebrow">' . lg_e( lg_v( $c, 'price' ) ) . '</span></div>';
			if ( lg_v( $c, 'text' ) ) {
				$o .= '<p class="mt-4 text-muted-foreground">' . lg_e( lg_v( $c, 'text' ) ) . '</p>';
			}
			$o .= '<span class="link-line mt-5 inline-block">Find out more</span></a></div>';
		}
		return $o . '</div></div></div>';
	}
);

lg_register(
	'lg_chips',
	'Concern Buttons',
	'Heading with a set of outlined concern links',
	array(
		lg_num(),
		lg_eyebrow_f(),
		lg_heading_f(),
		lg_text( 'pull', 'Italic line under heading' ),
		lg_items(
			'chips',
			'Buttons',
			array(
				lg_text( 'label', 'Text', array( 'admin_label' => true ) ),
				lg_text( 'url', 'Link' ),
			),
			array( 'group' => 'Buttons' )
		),
	),
	function ( $a ) {
		$o = '<section class="section-pad"><div class="wrap grid gap-10 md:grid-cols-12">' . lg_fade( 'md:col-span-4' ) . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$o .= '<h2 class="h-section mt-5">' . lg_e( $a['heading'] ) . '</h2>';
		}
		if ( $a['pull'] ) {
			$o .= '<p class="pull mt-3 text-muted-foreground">' . lg_e( $a['pull'] ) . '</p>';
		}
		$o .= '</div>' . lg_fade( 'flex flex-wrap content-start gap-3 md:col-span-8', 100 );
		foreach ( lg_group( $a['chips'] ) as $c ) {
			$o .= lg_link( lg_v( $c, 'url' ), lg_v( $c, 'label' ), 'border border-foreground/30 px-5 py-3 font-serif text-xl transition-colors hover:bg-espresso hover:text-ivory' );
		}
		return $o . '</div></div></section>';
	}
);

/* ======================================================================
   SHARED: pricing + get in touch
   ====================================================================== */

lg_register(
	'lg_pricing',
	'Pricing Menu',
	'Treatment price list on stone background',
	array_merge(
		array(
			lg_num(),
			lg_eyebrow_f(),
			lg_heading_f(),
			lg_text( 'pull', 'Italic line under heading' ),
			lg_items(
				'items',
				'Prices',
				array(
					lg_text( 'name', 'Treatment', array( 'admin_label' => true ) ),
					lg_text( 'price', 'Price' ),
				),
				array( 'group' => 'Prices' )
			),
			lg_area( 'note_1', 'Note 1 (small text)' ),
			lg_area( 'note_2', 'Note 2 (small text)' ),
		),
		lg_button_f()
	),
	function ( $a ) {
		$o = '<section class="bg-stone section-pad"><div class="wrap grid gap-12 md:grid-cols-12">' . lg_fade( 'md:col-span-4' ) . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$o .= '<h2 class="h-section mt-4">' . lg_e( $a['heading'] ) . '</h2>';
		}
		if ( $a['pull'] ) {
			$o .= '<p class="pull mt-6 text-muted-foreground">' . lg_e( $a['pull'] ) . '</p>';
		}
		$o .= '</div>' . lg_fade( 'md:col-span-8', 100 ) . '<ul class="border-t border-foreground/25">';
		foreach ( lg_group( $a['items'] ) as $r ) {
			$o .= '<li class="flex items-baseline justify-between gap-6 border-b border-foreground/25 py-5"><span class="font-serif text-2xl md:text-3xl">' . lg_e( lg_v( $r, 'name' ) ) . '</span><span class="eyebrow shrink-0">' . lg_e( lg_v( $r, 'price' ) ) . '</span></li>';
		}
		$o .= '</ul>';
		if ( $a['note_1'] ) {
			$o .= '<p class="mt-8 max-w-2xl text-sm text-muted-foreground">' . lg_e( $a['note_1'] ) . '</p>';
		}
		if ( $a['note_2'] ) {
			$o .= '<p class="mt-3 max-w-2xl text-sm text-muted-foreground">' . lg_e( $a['note_2'] ) . '</p>';
		}
		$o .= lg_link( $a['btn_url'], $a['btn_label'], 'btn btn-primary mt-8' );
		return $o . '</div></div></section>';
	}
);

lg_register(
	'lg_get_in_touch',
	'Get in Touch + Form',
	'Contact details, booking button and the enquiry form',
	array_merge(
		array(
			lg_num(),
			lg_eyebrow_f(),
			lg_heading_f(),
			lg_text( 'form_id', 'Contact Form 7 ID', array( 'description' => 'Found in Contact → Contact Forms (currently 497).' ) ),
		),
		lg_details_f(),
		lg_button_f()
	),
	function ( $a ) {
		$o = '<section class="section-pad"><div class="wrap grid gap-12 md:grid-cols-2 md:gap-20">' . lg_fade() . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$o .= '<h2 class="h-section mt-4">' . lg_e( $a['heading'] ) . '</h2>';
		}
		$d = lg_details( $a );
		if ( $d ) {
			$o .= '<div class="mt-10">' . $d . '</div>';
		}
		$o .= lg_link( $a['btn_url'], $a['btn_label'], 'btn btn-primary mt-10' ) . '</div>';
		return $o . lg_fade( '', 100 ) . lg_form( $a['form_id'] ) . '</div></div></section>';
	}
);

/* ======================================================================
   TREATMENT PAGES (also used by Membership / Find My Treatment)
   ====================================================================== */

lg_register(
	'lg_page_hero',
	'Page Hero (text + photo)',
	'Title, intro, button and price beside a photo',
	array_merge(
		array(
			lg_select( 'tone', 'Background', array( 'Ivory' => 'ivory', 'Taupe' => 'taupe' ), 'ivory' ),
			lg_select( 'title_style', 'Title size', array( 'Large' => 'serif', 'Extra large' => 'display' ), 'serif' ),
			lg_eyebrow_f(),
			lg_heading_f( 'title', 'Title' ),
			lg_text( 'subline', 'Italic line under title (optional)' ),
			lg_area( 'intro', 'Intro text (optional)' ),
			lg_select( 'intro_size', 'Intro size', array( 'Large' => 'large', 'Normal' => 'normal' ), 'large' ),
			lg_text( 'price', 'Price tag (optional)', array( 'description' => 'e.g. from $250' ) ),
			lg_image( 'image', 'Photo', array( 'group' => 'Photo' ) ),
		),
		lg_button_f()
	),
	function ( $a ) {
		$o = '<section class="' . ( 'taupe' === $a['tone'] ? 'bg-taupe pt-20' : 'pt-20' ) . '"><div class="grid md:grid-cols-2">';
		$o .= lg_fade( 'section-pad flex flex-col justify-center px-5 md:px-12 lg:px-20' ) . lg_eyebrow( $a['eyebrow'] );
		if ( $a['title'] ) {
			$o .= 'display' === $a['title_style']
				? '<h1 class="display mt-6">' . lg_e( $a['title'] ) . '</h1>'
				: '<h1 class="lg-ph-title mt-6 font-serif text-5xl leading-[1.02] md:text-7xl">' . lg_e( $a['title'] ) . '</h1>';
		}
		if ( $a['subline'] ) {
			$o .= '<p class="pull mt-5 text-muted-foreground">' . lg_e( $a['subline'] ) . '</p>';
		}
		if ( $a['intro'] ) {
			$o .= '<p class="' . ( 'large' === $a['intro_size'] ? 'mt-6 max-w-md text-lg' : 'mt-6 max-w-lg' ) . '">' . lg_e( $a['intro'] ) . '</p>';
		}
		$row = lg_link( $a['btn_url'], $a['btn_label'], 'btn btn-primary' ) . ( $a['price'] ? '<span class="eyebrow">' . lg_e( $a['price'] ) . '</span>' : '' );
		if ( $row ) {
			$o .= '<div class="mt-10 flex flex-col gap-5 md:flex-row md:items-center">' . $row . '</div>';
		}
		return $o . '</div>' . lg_photo( $a['image'], 'serum', '', true ) . '</div></section>';
	}
);

lg_register(
	'lg_overview',
	'Overview',
	'Numbered text with photo, optional numbered list, two lists and feature box',
	array(
		lg_num(),
		lg_eyebrow_f(),
		lg_html( 'Paragraphs', array( 'admin_label' => false ) ),
		lg_area( 'pull', 'Quote under photo (optional)' ),
		lg_image( 'image', 'Photo', array( 'group' => 'Photo' ) ),
		lg_text( 'list_lead', 'Text above numbered list', array( 'group' => 'Numbered list' ) ),
		lg_items( 'list', 'Numbered list', array( lg_text( 'text', 'Item', array( 'admin_label' => true ) ) ), array( 'group' => 'Numbered list' ) ),
		lg_items(
			'columns',
			'Two lists side by side',
			array(
				lg_text( 'title', 'List title', array( 'admin_label' => true ) ),
				lg_area( 'items', 'Items (one per line)' ),
			),
			array( 'group' => 'Two lists' )
		),
		lg_area( 'feature_text', 'Feature box text', array( 'group' => 'Feature box' ) ),
		lg_text( 'feature_label', 'Feature box link text', array( 'group' => 'Feature box' ) ),
		lg_text( 'feature_url', 'Feature box link', array( 'group' => 'Feature box' ) ),
	),
	function ( $a, $content ) {
		$o  = '<section class="section-pad"><div class="wrap grid gap-12 md:grid-cols-12">' . lg_fade( 'md:col-span-7' ) . lg_eyebrow( $a['eyebrow'], $a['num'] );
		$o .= '<div class="mt-6 max-w-[640px] space-y-5">' . lg_rich( $content );
		$list = lg_group( $a['list'] );
		if ( $a['list_lead'] && $list ) {
			$o .= '<p>' . lg_e( $a['list_lead'] ) . '</p>';
		}
		if ( $list ) {
			$o .= '<ol class="border-t border-foreground/25">';
			foreach ( $list as $i => $r ) {
				$o .= '<li class="flex gap-6 border-b border-foreground/25 py-4"><span class="font-serif text-2xl text-taupe">' . sprintf( '%02d', $i + 1 ) . '</span><span>' . lg_e( lg_v( $r, 'text' ) ) . '</span></li>';
			}
			$o .= '</ol>';
		}
		$o .= '</div></div>' . lg_fade( 'md:col-span-4 md:col-start-9', 100 ) . lg_photo( $a['image'], 'needling' );
		if ( $a['pull'] ) {
			$o .= '<p class="pull mt-8 border-l border-taupe pl-6">' . lg_e( $a['pull'] ) . '</p>';
		}
		$o .= '</div></div>';
		$cols = lg_group( $a['columns'] );
		if ( $cols ) {
			$o .= '<div class="wrap mt-16 grid gap-10 border-t border-foreground/25 pt-12 md:grid-cols-2">';
			foreach ( $cols as $i => $c ) {
				$o .= lg_fade( '', $i * 100 ) . '<h3 class="font-serif text-3xl">' . lg_e( lg_v( $c, 'title' ) ) . '</h3><ul class="mt-6 border-t border-foreground/25">';
				foreach ( lg_lines( lg_v( $c, 'items' ) ) as $x ) {
					$o .= '<li class="border-b border-foreground/25 py-3">' . lg_e( $x ) . '</li>';
				}
				$o .= '</ul></div>';
			}
			$o .= '</div>';
		}
		if ( $a['feature_text'] ) {
			$o .= lg_fade( 'wrap mt-16' ) . lg_a_open( $a['feature_url'] ? $a['feature_url'] : '#', 'grid items-center gap-8 bg-taupe p-8 md:grid-cols-12 md:p-12' );
			$o .= '<p class="pull md:col-span-9">' . lg_e( $a['feature_text'] ) . '</p>';
			if ( $a['feature_label'] ) {
				$o .= '<span class="link-line justify-self-start md:col-span-3 md:justify-self-end">' . lg_e( $a['feature_label'] ) . '</span>';
			}
			$o .= '</a></div>';
		}
		return $o . '</section>';
	}
);

lg_register(
	'lg_facts',
	'Quick Facts',
	'Three facts in a stone band (treatment time, downtime…)',
	array(
		lg_items(
			'items',
			'Facts',
			array(
				lg_text( 'label', 'Label', array( 'admin_label' => true ) ),
				lg_text( 'value', 'Value' ),
			)
		),
	),
	function ( $a ) {
		$rows = lg_group( $a['items'] );
		if ( ! $rows ) {
			return '';
		}
		$o = '<section class="border-y border-foreground/20 bg-stone"><ul class="wrap grid divide-y divide-foreground/20 md:grid-cols-3 md:divide-x md:divide-y-0">';
		foreach ( $rows as $r ) {
			$o .= '<li class="py-8 md:px-8 md:first:pl-0"><p class="eyebrow text-muted-foreground">' . lg_e( lg_v( $r, 'label' ) ) . '</p><p class="mt-2 font-serif text-3xl">' . lg_e( lg_v( $r, 'value' ) ) . '</p></li>';
		}
		return $o . '</ul></section>';
	}
);

lg_register(
	'lg_faq',
	'FAQ',
	'Questions that open and close',
	array(
		lg_num(),
		lg_eyebrow_f(),
		lg_heading_f(),
		lg_items(
			'items',
			'Questions',
			array(
				lg_text( 'q', 'Question', array( 'admin_label' => true ) ),
				lg_area( 'a', 'Answer' ),
				lg_area( 'list', 'Answer as a list (optional, one item per line)' ),
				lg_check( 'two_col', 'Show list in two columns' ),
			),
			array( 'group' => 'Questions' )
		),
	),
	function ( $a ) {
		$o = '<section class="section-pad"><div class="wrap grid gap-10 md:grid-cols-12">' . lg_fade( 'md:col-span-4' ) . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$o .= '<h2 class="h-section mt-5">' . lg_e( $a['heading'] ) . '</h2>';
		}
		$o .= '</div>' . lg_fade( 'md:col-span-8', 100 ) . '<div class="border-t border-foreground/25">';
		foreach ( lg_group( $a['items'] ) as $i => $f ) {
			$o .= '<details' . ( 0 === $i ? ' open' : '' ) . ' class="border-b border-foreground/25"><summary class="flex items-start justify-between gap-6 py-6"><span class="font-serif text-2xl md:text-[1.75rem] leading-snug">' . lg_e( lg_v( $f, 'q' ) ) . '</span><span class="faq-plus mt-2 text-2xl font-light transition-transform duration-300">+</span></summary><div class="pb-8 pr-10 text-muted-foreground">';
			if ( lg_v( $f, 'a' ) ) {
				$o .= '<p class="max-w-2xl">' . nl2br( lg_e( lg_v( $f, 'a' ) ) ) . '</p>';
			}
			$items = lg_lines( lg_v( $f, 'list' ) );
			if ( $items ) {
				$o .= '<ul class="' . ( 'yes' === lg_v( $f, 'two_col' ) ? 'grid gap-x-10 sm:grid-cols-2' : 'grid gap-x-10' ) . '">';
				foreach ( $items as $x ) {
					$o .= '<li class="border-b border-foreground/15 py-2">' . lg_e( $x ) . '</li>';
				}
				$o .= '</ul>';
			}
			$o .= '</div></details>';
		}
		return $o . '</div></div></div></section>';
	}
);

lg_register(
	'lg_strip',
	'Photo Strip (4 photos)',
	'Four photos edge to edge; swipeable on phones',
	array( lg_images( 'images', 'Photos (pick 4)' ) ),
	function ( $a ) {
		$imgs = array_filter( array_map( 'trim', explode( ',', $a['images'] ) ), 'strlen' );
		if ( ! $imgs ) {
			return '';
		}
		$o = '<section><div class="no-scrollbar flex snap-x snap-mandatory gap-3 overflow-x-auto px-5 md:grid md:grid-cols-4 md:overflow-visible md:px-0">';
		foreach ( $imgs as $im ) {
			$o .= lg_photo( $im, 'needling', 'w-[75vw] shrink-0 snap-start md:w-auto' );
		}
		return $o . '</div></section>';
	}
);

lg_register(
	'lg_disclaimer',
	'Registration + Disclaimer',
	'Centred registration number and consultation disclaimer',
	array( lg_text( 'cred', 'Registration line', array( 'admin_label' => true ) ), lg_area( 'text', 'Disclaimer' ) ),
	function ( $a ) {
		$o = '';
		if ( $a['cred'] ) {
			$o .= '<p class="eyebrow wrap pt-8 text-center text-foreground/70">' . lg_e( $a['cred'] ) . '</p>';
		}
		if ( $a['text'] ) {
			$o .= '<p class="wrap py-8 text-center text-sm italic text-muted-foreground">' . lg_e( $a['text'] ) . '</p>';
		}
		return $o;
	}
);

lg_register(
	'lg_related',
	'Related Treatment Link',
	'Full-width stone band linking to another page',
	array( lg_eyebrow_f(), lg_heading_f( 'title', 'Title' ), lg_text( 'link_label', 'Link text' ), lg_text( 'url', 'Link' ) ),
	function ( $a ) {
		$o = lg_a_open( $a['url'] ? $a['url'] : '#', 'group block border-y border-foreground/20 bg-stone' ) . '<div class="wrap flex flex-col justify-between gap-4 py-12 md:flex-row md:items-center"><div>' . lg_eyebrow( $a['eyebrow'] );
		if ( $a['title'] ) {
			$o .= '<p class="mt-3 font-serif text-4xl md:text-5xl">' . lg_e( $a['title'] ) . '</p>';
		}
		$o .= '</div>';
		if ( $a['link_label'] ) {
			$o .= '<span class="link-line self-start transition-transform group-hover:translate-x-2 md:self-auto">' . lg_e( $a['link_label'] ) . '</span>';
		}
		return $o . '</div></a>';
	}
);

/* ======================================================================
   MEMBERSHIP / FIND MY TREATMENT / CONTACT
   ====================================================================== */

lg_register(
	'lg_steps',
	'Numbered Steps',
	'Three large numbered steps',
	array(
		lg_select( 'size', 'Size', array( 'Large' => 'large', 'Medium' => 'medium' ), 'large' ),
		lg_num(),
		lg_eyebrow_f(),
		lg_heading_f(),
		lg_items( 'items', 'Steps', array( lg_area( 'text', 'Step', array( 'admin_label' => true ) ) ), array( 'group' => 'Steps' ) ),
	),
	function ( $a ) {
		$big = 'large' === $a['size'];
		$o   = '<section class="section-pad"><div class="wrap">' . lg_fade() . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$o .= '<h2 class="' . ( $big ? 'h-section mt-5 max-w-2xl' : 'h-section mt-5' ) . '">' . lg_e( $a['heading'] ) . '</h2>';
		}
		$o .= '</div><ol class="mt-12 grid border-t border-foreground/25 md:grid-cols-3">';
		foreach ( lg_group( $a['items'] ) as $i => $r ) {
			$o .= '<li class="fade-up border-b border-foreground/25 py-10 md:border-b-0 md:border-r md:px-8 md:first:pl-0 md:last:border-r-0"' . ( $i ? ' style="transition-delay:' . ( $i * 100 ) . 'ms"' : '' ) . '>';
			$o .= '<p class="' . ( $big ? 'font-serif text-7xl text-taupe' : 'font-serif text-6xl text-taupe' ) . '">' . sprintf( '%02d', $i + 1 ) . '</p>';
			$o .= '<p class="' . ( $big ? 'mt-6 font-serif text-3xl leading-snug' : 'mt-5 font-serif text-3xl leading-snug' ) . '">' . lg_e( lg_v( $r, 'text' ) ) . '</p></li>';
		}
		return $o . '</ol></div></section>';
	}
);

lg_register(
	'lg_benefits',
	'Benefits Grid',
	'Numbered benefit tiles on stone background',
	array(
		lg_num(),
		lg_eyebrow_f(),
		lg_heading_f(),
		lg_items( 'items', 'Benefits', array( lg_area( 'text', 'Benefit', array( 'admin_label' => true ) ) ), array( 'group' => 'Benefits' ) ),
	),
	function ( $a ) {
		$o = '<section class="bg-stone section-pad"><div class="wrap">' . lg_fade() . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['heading'] ) {
			$o .= '<h2 class="h-section mt-5">' . lg_e( $a['heading'] ) . '</h2>';
		}
		$o .= '</div><div class="mt-12 grid border-l border-t border-foreground/25 sm:grid-cols-2 lg:grid-cols-3">';
		foreach ( lg_group( $a['items'] ) as $i => $r ) {
			$o .= lg_fade( 'border-b border-r border-foreground/25 p-8 md:p-10', ( $i % 3 ) * 80 ) . '<p class="eyebrow text-muted-foreground">' . sprintf( '%02d', $i + 1 ) . '</p><p class="mt-4 font-serif text-2xl leading-snug md:text-3xl">' . lg_e( lg_v( $r, 'text' ) ) . '</p></div>';
		}
		return $o . '</div></div></section>';
	}
);

lg_register(
	'lg_quote',
	'Large Quote',
	'Centred italic statement',
	array( lg_area( 'text', 'Quote', array( 'admin_label' => true ) ) ),
	function ( $a ) {
		if ( ! $a['text'] ) {
			return '';
		}
		return '<section class="section-pad">' . lg_fade( 'wrap' ) . '<p class="mx-auto max-w-4xl text-center font-serif text-3xl italic leading-snug md:text-5xl">' . lg_e( $a['text'] ) . '</p></div></section>';
	}
);

lg_register(
	'lg_cta',
	'Call to Action Band',
	'Coloured band with a heading and button',
	array_merge(
		array(
			lg_select( 'tone', 'Background', array( 'Taupe' => 'taupe', 'Stone' => 'stone', 'Espresso (dark)' => 'espresso' ), 'taupe' ),
			lg_eyebrow_f(),
			lg_heading_f( 'title', 'Heading' ),
			lg_text( 'note', 'Small note (optional)' ),
		),
		lg_button_f()
	),
	function ( $a ) {
		if ( 'espresso' === $a['tone'] ) {
			$o = '<section class="bg-espresso text-ivory section-pad">' . lg_fade( 'wrap flex flex-col justify-between gap-8 md:flex-row md:items-end' ) . '<div>' . lg_eyebrow( $a['eyebrow'] );
			if ( $a['title'] ) {
				$o .= '<p class="mt-5 max-w-2xl font-serif text-3xl leading-snug md:text-4xl">' . lg_e( $a['title'] ) . '</p>';
			}
			if ( $a['note'] ) {
				$o .= '<p class="mt-4 text-sm opacity-80">' . lg_e( $a['note'] ) . '</p>';
			}
			return $o . '</div>' . lg_link( $a['btn_url'], $a['btn_label'], 'btn btn-outline shrink-0' ) . '</div></section>';
		}
		$o = '<section class="' . ( 'stone' === $a['tone'] ? 'bg-stone section-pad' : 'bg-taupe section-pad' ) . '">' . lg_fade( 'wrap flex flex-col items-start justify-between gap-8 md:flex-row md:items-end' ) . '<div>' . lg_eyebrow( $a['eyebrow'] );
		if ( $a['title'] ) {
			$o .= '<h2 class="h-section mt-4 max-w-3xl">' . lg_e( $a['title'] ) . '</h2>';
		}
		if ( $a['note'] ) {
			$o .= '<p class="mt-4 text-sm opacity-80">' . lg_e( $a['note'] ) . '</p>';
		}
		return $o . '</div>' . lg_link( $a['btn_url'], $a['btn_label'], 'btn btn-primary shrink-0' ) . '</div></section>';
	}
);

lg_register(
	'lg_concern_group',
	'Concern Group',
	'Photo with a list of concerns and links (Find My Treatment)',
	array(
		lg_text( 'anchor', 'Anchor ID', array( 'description' => 'Used by links like #lines-volume. Letters, numbers and dashes.' ) ),
		lg_check( 'alt', 'Stone background, photo on right' ),
		lg_num(),
		lg_eyebrow_f(),
		lg_heading_f( 'title', 'Title' ),
		lg_image( 'image', 'Photo', array( 'group' => 'Photo' ) ),
		lg_items(
			'items',
			'Concerns',
			array(
				lg_text( 'name', 'Concern', array( 'admin_label' => true ) ),
				lg_text( 'line', 'Short description' ),
			),
			array( 'group' => 'Concerns' )
		),
		lg_items(
			'links',
			'Links under each concern',
			array(
				lg_text( 'label', 'Text', array( 'admin_label' => true ) ),
				lg_text( 'url', 'Link' ),
			),
			array( 'group' => 'Links' )
		),
	),
	function ( $a ) {
		$alt   = 'yes' === $a['alt'];
		$id    = preg_replace( '/[^A-Za-z0-9_-]/', '', $a['anchor'] );
		$links = '';
		foreach ( lg_group( $a['links'] ) as $l ) {
			$links .= lg_link( lg_v( $l, 'url' ), lg_v( $l, 'label' ), 'link-line' );
		}
		$o  = '<section' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . ' class="' . ( $alt ? 'scroll-mt-20 bg-stone section-pad' : 'scroll-mt-20 section-pad' ) . '"><div class="wrap grid gap-10 md:grid-cols-12">';
		$o .= lg_fade( $alt ? 'md:col-span-4 md:order-2 md:col-start-9' : 'md:col-span-4' ) . lg_photo( $a['image'], 'profile' ) . '</div>';
		$o .= '<div class="' . ( $alt ? 'md:col-span-7 md:order-1 md:col-start-1' : 'md:col-span-7 md:col-start-6' ) . '">' . lg_fade() . lg_eyebrow( $a['eyebrow'], $a['num'] );
		if ( $a['title'] ) {
			$o .= '<h2 class="h-section mt-5">' . lg_e( $a['title'] ) . '</h2>';
		}
		$o .= '</div><div class="mt-8 border-t border-foreground/25">';
		foreach ( lg_group( $a['items'] ) as $i => $it ) {
			$o .= lg_fade( 'border-b border-foreground/25 py-7', $i * 80 ) . '<h3 class="font-serif text-3xl">' . lg_e( lg_v( $it, 'name' ) ) . '</h3>';
			if ( lg_v( $it, 'line' ) ) {
				$o .= '<p class="mt-2 text-muted-foreground">' . lg_e( lg_v( $it, 'line' ) ) . '</p>';
			}
			if ( $links ) {
				$o .= '<div class="mt-5 flex flex-wrap gap-x-8 gap-y-3">' . $links . '</div>';
			}
			$o .= '</div>';
		}
		return $o . '</div></div></div></section>';
	}
);

lg_register(
	'lg_link_band',
	'Link Band',
	'Italic line with a link, between thin lines',
	array( lg_heading_f( 'text', 'Text' ), lg_text( 'link_label', 'Link text' ), lg_text( 'url', 'Link' ) ),
	function ( $a ) {
		$o = lg_a_open( $a['url'] ? $a['url'] : '#', 'group block border-y border-foreground/20' ) . '<div class="wrap flex flex-col justify-between gap-4 py-10 md:flex-row md:items-center">';
		$o .= '<p class="font-serif text-3xl italic md:text-4xl">' . lg_e( $a['text'] ) . '</p>';
		if ( $a['link_label'] ) {
			$o .= '<span class="link-line self-start md:self-auto">' . lg_e( $a['link_label'] ) . '</span>';
		}
		return $o . '</div></a>';
	}
);

lg_register(
	'lg_simple_hero',
	'Simple Page Title',
	'Large title without a photo (Contact)',
	array( lg_eyebrow_f(), lg_heading_f( 'title', 'Title' ), lg_text( 'pull', 'Italic line' ) ),
	function ( $a ) {
		$o = '<section class="pt-32 md:pt-40">' . lg_fade( 'wrap pb-14 md:pb-20' ) . lg_eyebrow( $a['eyebrow'] );
		if ( $a['title'] ) {
			$o .= '<h1 class="display mt-6">' . lg_e( $a['title'] ) . '</h1>';
		}
		if ( $a['pull'] ) {
			$o .= '<p class="pull mt-5 text-muted-foreground">' . lg_e( $a['pull'] ) . '</p>';
		}
		return $o . '</div></section>';
	}
);

lg_register(
	'lg_contact_main',
	'Contact Details + Hours + Form',
	'Contact page main section',
	array_merge(
		array(
			lg_text( 'hours_title', 'Hours label' ),
			lg_items(
				'hours',
				'Opening hours',
				array(
					lg_text( 'day', 'Day', array( 'admin_label' => true ) ),
					lg_text( 'time', 'Hours' ),
				),
				array( 'group' => 'Opening hours' )
			),
			lg_text( 'form_eyebrow', 'Label above form' ),
			lg_text( 'form_id', 'Contact Form 7 ID', array( 'description' => 'Currently 497.' ) ),
		),
		lg_details_f()
	),
	function ( $a ) {
		$o  = '<section class="border-t border-foreground/20 section-pad"><div class="wrap grid gap-14 md:grid-cols-2 md:gap-20">' . lg_fade() . lg_details( $a );
		$hr = lg_group( $a['hours'] );
		if ( $hr ) {
			$o .= '<div class="mt-12"><p class="eyebrow text-muted-foreground">' . lg_e( $a['hours_title'] ) . '</p><ul class="mt-4 border-t border-foreground/25">';
			foreach ( $hr as $h ) {
				$o .= '<li class="flex justify-between border-b border-foreground/25 py-3 text-sm"><span>' . lg_e( lg_v( $h, 'day' ) ) . '</span><span class="text-muted-foreground">' . lg_e( lg_v( $h, 'time' ) ) . '</span></li>';
			}
			$o .= '</ul></div>';
		}
		$o .= '</div>' . lg_fade( '', 100 ) . lg_eyebrow( $a['form_eyebrow'] ) . '<div class="mt-8">' . lg_form( $a['form_id'] ) . '</div></div>';
		return $o . '</div></section>';
	}
);

lg_register(
	'lg_gallery',
	'Photo Row (3 photos)',
	'Three photos side by side',
	array( lg_images( 'images', 'Photos (pick 3)' ) ),
	function ( $a ) {
		$imgs = array_filter( array_map( 'trim', explode( ',', $a['images'] ) ), 'strlen' );
		if ( ! $imgs ) {
			return '';
		}
		$o = '<section class="grid gap-3 md:grid-cols-3">';
		foreach ( $imgs as $im ) {
			$o .= lg_photo( $im, 'room' );
		}
		return $o . '</section>';
	}
);

lg_register(
	'lg_map',
	'Google Map',
	'Wide map of the clinic',
	array( lg_text( 'address', 'Address shown on the map', array( 'admin_label' => true ) ) ),
	function ( $a ) {
		$q = rawurlencode( $a['address'] ? $a['address'] : '208 Victoria Road Gladesville NSW 2111' );
		return '<section class="relative bg-stone" style="aspect-ratio:21/9;min-height:320px"><iframe title="Lumea Glow location" src="https://www.google.com/maps?q=' . $q . '&amp;output=embed" style="position:absolute;inset:0;width:100%;height:100%;border:0;filter:grayscale(.35)" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></section>';
	}
);
