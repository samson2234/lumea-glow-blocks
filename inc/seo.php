<?php
/**
 * Local SEO: LocalBusiness schema (JSON-LD) and descriptive image alt text.
 * Clinic details live in lg_clinic(); change them there and release an update.
 * Wording is TGA-safe: no prescription-only product names or references.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lg_clinic() {
	return apply_filters(
		'lg_clinic',
		array(
			'name'      => 'Lumea Glow',
			'phone'     => '+61408463126',
			'street'    => '208 Victoria Road',
			'locality'  => 'Gladesville',
			'region'    => 'NSW',
			'postcode'  => '2111',
			'country'   => 'AU',
			'booking'   => 'https://lumeaglow.as.me/',
			'priceband' => '$$',
			// day => [open, close], 24h.
			'hours'     => array(
				'Monday'    => array( '09:00', '20:30' ),
				'Tuesday'   => array( '09:00', '20:30' ),
				'Wednesday' => array( '18:30', '20:30' ),
				'Thursday'  => array( '18:30', '20:30' ),
				'Friday'    => array( '09:00', '20:30' ),
				'Saturday'  => array( '09:00', '13:30' ),
				'Sunday'    => array( '10:00', '13:30' ),
			),
			'areas'     => array( 'Gladesville', 'Hunters Hill', 'Ryde', 'Putney', 'Drummoyne', 'Abbotsford', 'Five Dock', 'Lane Cove', 'Balmain' ),
			'services'  => array(
				array( 'Microneedling', '/microneedling/' ),
				array( 'Skin Rejuvenation & Targeted Facials', '/skin-rejuvenation-targeted-facials/' ),
				array( 'LHALA Peel', '/lhala-peel-korean-skincare-glass-facial-gladesville/' ),
				array( 'The Glassifier', '/the-glassifier/' ),
				array( 'Skin Boosters & Rejuran', '/rejuran/' ),
				array( 'Lines, Wrinkles & Volume Consultations', '/wrinkle-concerns-restoring-facial-volume/' ),
			),
		)
	);
}

function lg_schema() {
	$c     = lg_clinic();
	$home  = home_url( '/' );
	$hours = array();
	foreach ( $c['hours'] as $day => $h ) {
		$hours[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => 'https://schema.org/' . $day,
			'opens'     => $h[0],
			'closes'    => $h[1],
		);
	}
	$offers = array();
	foreach ( $c['services'] as $s ) {
		$offers[] = array(
			'@type'       => 'Offer',
			'itemOffered' => array(
				'@type' => 'Service',
				'name'  => $s[0],
				'url'   => home_url( $s[1] ),
			),
		);
	}
	list( $img ) = lg_img( 'front', 'front' );
	return array(
		'@context'                  => 'https://schema.org',
		'@type'                     => array( 'HealthAndBeautyBusiness', 'MedicalBusiness' ),
		'@id'                       => $home . '#clinic',
		'name'                      => $c['name'],
		'description'               => 'Registered Nurse-led skin clinic in Gladesville offering personalised consultations, facials, peels, microneedling and skin treatments.',
		'url'                       => $home,
		'logo'                      => LG_URL . 'assets/lg-logo-dark.png',
		'image'                     => $img,
		'telephone'                 => $c['phone'],
		'priceRange'                => $c['priceband'],
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $c['street'],
			'addressLocality' => $c['locality'],
			'addressRegion'   => $c['region'],
			'postalCode'      => $c['postcode'],
			'addressCountry'  => $c['country'],
		),
		'openingHoursSpecification' => $hours,
		'areaServed'                => array_map(
			function ( $a ) {
				return array( '@type' => 'Place', 'name' => $a . ' NSW' );
			},
			$c['areas']
		),
		'potentialAction'           => array(
			'@type'  => 'ReserveAction',
			'target' => $c['booking'],
		),
		'hasOfferCatalog'           => array(
			'@type'           => 'OfferCatalog',
			'name'            => 'Skin treatments',
			'itemListElement' => $offers,
		),
	);
}

add_action(
	'wp_head',
	function () {
		if ( ! lg_page_uses_blocks() ) {
			return;
		}
		echo "\n<script type=\"application/ld+json\">" . wp_json_encode( lg_schema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	},
	20
);

/** Descriptive alt text for the design photos (TGA-safe, no product names). */
function lg_photo_alts() {
	return array(
		'hero'      => 'Woman with healthy, glowing skin at Lumea Glow skin clinic, Gladesville',
		'jess'      => 'Registered Nurse Jess, founder of Lumea Glow',
		'jess2'     => 'RN Jess seated in the Lumea Glow clinic',
		'consult2'  => 'RN Jess in a skin consultation with a client',
		'consult'   => 'RN Jess performing a skin treatment',
		'injecting' => 'Skin booster treatment at Lumea Glow Gladesville',
		'needling'  => 'Microneedling treatment at Lumea Glow Gladesville',
		'needling2' => 'Close-up of a microneedling skin treatment',
		'facial'    => 'Clinical facial treatment at Lumea Glow',
		'portrait'  => 'Portrait of a woman with radiant, even-toned skin',
		'profile'   => 'Side profile of a woman with smooth, hydrated skin',
		'glow'      => 'Portrait showing a glass-skin glow',
		'serum'     => 'Skincare serum being poured',
		'fabric'    => 'Soft neutral fabric texture',
		'fabric2'   => 'Soft neutral fabric texture',
		'fabric3'   => 'Soft neutral fabric texture',
		'client'    => 'Client checking her skin in a mirror after treatment',
		'client2'   => 'Client smiling at her skin in a hand mirror',
		'client3'   => 'Client seated with a mirror after her skin treatment',
		'reception' => 'Lumea Glow waiting area, Gladesville',
		'room'      => 'Treatment room at Lumea Glow skin clinic',
		'mirror'    => 'Lumea Glow branded mirror',
		'front'     => 'Lumea Glow shopfront at 208 Victoria Road, Gladesville',
	);
}

/** One-time: replace the generic "Lumea Glow" alt on imported design photos. Edited alts are left alone. */
add_action(
	'admin_init',
	function () {
		if ( (int) get_option( 'lg_alt_ver', 0 ) >= 1 ) {
			return;
		}
		$alts = lg_photo_alts();
		foreach ( lg_image_ids() as $key => $id ) {
			$cur = (string) get_post_meta( (int) $id, '_wp_attachment_image_alt', true );
			if ( isset( $alts[ $key ] ) && ( '' === $cur || 'Lumea Glow' === $cur ) ) {
				update_post_meta( (int) $id, '_wp_attachment_image_alt', $alts[ $key ] );
			}
		}
		update_option( 'lg_alt_ver', 1, false );
	}
);
