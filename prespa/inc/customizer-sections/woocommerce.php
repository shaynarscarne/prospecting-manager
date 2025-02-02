<?php
// Woocommerce colors

function prespa_customize_woocommerce_colors( $wp_customize ) {

	$wp_customize->add_section(
		'prespa_woo_colors',
		array(
			'title'       => __( 'Colors', 'prespa' ),
			'description' => __( 'Customize WooCommerce colors. By default, the theme uses theme accent colors to handle these but you can specify specific styles here for more flexibility.', 'prespa' ),
			'panel'       => 'woocommerce',
		)
	);

	$wp_customize->add_setting(
		'woo_btn_bgr_color',
		array(
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'woo_btn_bgr_color',
			array(
				'label'   => esc_html__( 'Add to Cart Background Color', 'prespa' ),
				'section' => 'prespa_woo_colors',
			)
		)
	);

	$wp_customize->add_setting(
		'woo_btn_text_color',
		array(
			'default'           => '#404040',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'woo_btn_text_color',
			array(
				'label'   => esc_html__( 'Add to Cart Text Color', 'prespa' ),
				'section' => 'prespa_woo_colors',
			)
		)
	);

	$wp_customize->add_setting(
		'woo_info_bgr_color',
		array(
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'woo_info_bgr_color',
			array(
				'label'   => esc_html__( 'Woocommerce Notifications Background Color', 'prespa' ),
				'section' => 'prespa_woo_colors',
			)
		)
	);

}

add_action( 'customize_register', 'prespa_customize_woocommerce_colors', 10 );

function prespa_customize_woocommerce_colors_css() {

	$primary_accent_color   = get_theme_mod( 'primary_accent_color', '#3a72d3' );
	$secondary_accent_color = get_theme_mod( 'secondary_accent_color', '#ebeefc' );
	$woo_btn_text_color = get_theme_mod( 'woo_btn_text_color', '#404040' );
	$woo_btn_bgr_color  = get_theme_mod( 'woo_btn_bgr_color' );
	$woo_info_bgr_color = get_theme_mod( 'woo_info_bgr_color' );

	?>
	
	<style>
	.woocommerce-info, .woocommerce-noreviews, .woocommerce-message, p.no-comments {
		background-color: <?php echo esc_attr( $primary_accent_color ); ?>;
	}

	.wc-block-components-product-sale-badge, 
	.woocommerce span.onsale {
		background-color: <?php echo esc_attr( $primary_accent_color ); ?>;
		border: none;
		color: #fff;
	}

	<?php if ( $woo_info_bgr_color ) : ?>
	.woocommerce-info,
	.woocommerce-noreviews,
	.woocommerce-message,
	p.no-comments {
		background-color: <?php echo esc_attr( $woo_info_bgr_color ); ?>!important;
	}
	<?php endif; ?>

	body:not(.dark-mode) .add_to_cart_button,
	body:not(.dark-mode) .single_add_to_cart_button,
	body:not(.dark-mode) .checkout-button,
	body:not(.dark-mode).woocommerce-page .button,
	body:not(.dark-mode).woocommerce #respond input#submit,
	body:not(.dark-mode).woocommerce-page #payment #place_order {
	<?php if ( ! $woo_btn_bgr_color ) : ?>
		background: <?php echo esc_attr( $secondary_accent_color ); ?>!important;
	<?php endif; ?>
	}

	body:not(.dark-mode) .add_to_cart_button,
	body:not(.dark-mode) .single_add_to_cart_button,
	body:not(.dark-mode) .checkout-button,
	body:not(.dark-mode).woocommerce-page .button,
	body:not(.dark-mode).woocommerce-page #respond input#submit,
	body:not(.dark-mode).woocommerce-page #payment #place_order  {
		<?php if ( $woo_btn_bgr_color ) : ?>
		background: <?php echo esc_attr( $woo_btn_bgr_color );?>!important;
		<?php endif; ?>
		color: <?php echo $woo_btn_text_color; ?>!important;
	}

	</style>
	
	<?php
}

add_action( 'wp_head', 'prespa_customize_woocommerce_colors_css' );
