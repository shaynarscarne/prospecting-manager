<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class prespa_notice_welcome extends prespa_notice {

	public function __construct() {
		
		add_action( 'wp_loaded', array( $this, 'welcome_notice' ), 20 );
		add_action( 'wp_loaded', array( $this, 'hide_notices' ), 15 );

	}

	public function welcome_notice() {
		
		$this_notice_was_dismissed = $this->get_notice_status('welcome-notice');
		
		if ( !$this_notice_was_dismissed ) {
			if ( isset($_GET['page']) && 'prespa-doc' == $_GET['page'] ) {
				return;
			}

			add_action( 'admin_notices', array( $this, 'welcome_notice_markup' ) ); // Display this notice.
		}
	}

	/**
	 * Show welcome notice.
	 */
	public function welcome_notice_markup() {
		
		$dismiss_url = wp_nonce_url(
			remove_query_arg( array( 'activated' ), add_query_arg( 'prespa-hide-new-notice', 'welcome-notice' ) ),
			'prespa_hide_notices_nonce',
			'_prespa_notice_nonce'
		);

		$theme_data	 = wp_get_theme();

		?>
		<div id="message" class="notice notice-success nasiothemes-notice nasiothemes-welcome-notice">
			<a class="nasiothemes-message-close notice-dismiss" href="<?php echo esc_url( $dismiss_url ); ?>"></a>

			<div class="nasiothemes-message-content">
				<div class="nasiothemes-message-image">
					<a href="<?php echo esc_url( admin_url( 'themes.php?page=prespa-doc' ) ); ?>"><img class="nasiothemes-screenshot" src="<?php echo esc_url( get_template_directory_uri() ); ?>/admin/img/theme-logo.jpg" alt="<?php esc_attr_e( 'Prespa', 'prespa' ); ?>" /></a>
				</div><!-- ws fix
				--><div class="nasiothemes-message-text">
					<h2 class="nasiothemes-message-heading"><?php echo /* translators: %1$s: theme name */ sprintf(__('Thank you for choosing %1$s!', 'prespa' ), esc_html($theme_data->name)); ?></h2>
					<?php
					echo '<p>';
					printf(
						/* translators: %1$s: opening <a> tag, %2$s: URL, %3$s: theme name, %4$s: closing </a> tag */
						__( 
							'To take advantage of everything that this theme can offer, please take a look at the %1$s%2$sGet Started with %3$s%4$s page.', 
							'prespa'
						),
						'<a href="',
						esc_url( admin_url( 'themes.php?page=prespa-doc' ) ) . '">',
						esc_html( $theme_data->Name ),
						'</a>'
					);
					echo '</p>';

					echo '<p class="notice-buttons"><a href="'. esc_url( admin_url( 'themes.php?page=prespa-doc' ) ) .'" class="button button-primary">';
					/* translators: %s theme name */
					printf( esc_html__( 'Get started with %s', 'prespa' ), esc_html( $theme_data->Name ) );
					echo '</a>';
					echo ' <a href="'. esc_url( PRESPA_THEME_OPTIONS ) .'" target="_blank" rel="noopener" class="button button-primary nasiothemes-button"><span class="dashicons dashicons-admin-generic"></span> ';
					echo esc_html__( 'Theme Options', 'prespa' );
					echo '</a></p>';
					?>
				</div><!-- .nasiothemes-message-text -->
			</div><!-- .nasiothemes-message-content -->
		</div><!-- #message -->
		<?php
	}

}

new prespa_notice_welcome();