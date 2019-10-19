<?php

/**
 * Settings class for licenses
 *
 * @since 1.9
 */
class PLL_Settings_Licenses extends PLL_Settings_Module {
	protected $items;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param object $polylang polylang object
	 */
	public function __construct( &$polylang ) {
		parent::__construct(
			$polylang,
			array(
				'module'      => 'licenses',
				'title'       => __( 'License keys', 'polylang' ),
				'description' => __( 'Manage licenses for Polylang Pro and add-ons.', 'polylang' ),
			)
		);

		$this->buttons['cancel'] = sprintf( '<button type="button" class="button button-secondary cancel">%s</button>', __( 'Close', 'polylang' ) );

		$this->items = apply_filters( 'pll_settings_licenses', array() );

		add_action( 'wp_ajax_pll_deactivate_license', array( $this, 'deactivate_license' ) );
	}

	/**
	 * Tells if the module is active
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_active() {
		return ! empty( $this->items );
	}

	/**
	 * Displays the settings form
	 *
	 * @since 1.9
	 */
	protected function form() {
		if ( ! empty( $this->items ) ) { ?>
			<table id="pll-licenses-table" class="form-table">
				<?php
				foreach ( $this->items as $item ) {
					echo $this->get_row( $item ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</table>
			<?php
		}
	}

	/**
	 * Get the html for a row (one per license key) for display
	 *
	 * @since 1.9
	 *
	 * @param array $item licence id, name and key
	 * @return string
	 */
	protected function get_row( $item ) {
		if ( ! empty( $item->license_data ) ) {
			$license = $item->license_data;
		}

		$class = 'license-null';

		$out = sprintf(
			'<td><label for="pll-licenses[%1$s]">%2$s</label></td>' .
			'<td><input name="licenses[%1$s]" id="pll-licenses[%1$s]" type="text" value="%3$s" class="regular-text code" />',
			esc_attr( $item->id ),
			esc_attr( $item->name ),
			esc_html( $item->license_key )
		);

		if ( ! empty( $license ) && is_object( $license ) ) {
			$now = current_time( 'timestamp' );
			$expiration = strtotime( $license->expires, $now );

			// Special case: the license expired after the last check
			if ( $license->success && $expiration < $now ) {
				$license->success = false;
				$license->error = 'expired';
			}

			if ( false === $license->success ) {
				$class = 'notice-error notice-alt';

				switch ( $license->error ) {
					case 'expired':
						$message = sprintf(
							/* translators: %1$s is a date, %2$s is link start tag, %3$s is link end tag. */
							__( 'Your license key expired on %1$s. Please %2$srenew your license key%3$s.', 'polylang' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
							sprintf( '<a href="%s" target="_blank">', 'https://polylang.pro/checkout/?edd_license_key=' . $item->license_key ),
							'</a>'
						);
						break;

					case 'missing':
						$message = sprintf(
							/* translators: %1$s is link start tag, %2$s is link end tag. */
							__( 'Invalid license. Please %1$svisit your account page%2$s and verify it.', 'polylang' ),
							sprintf( '<a href="%s" target="_blank">', 'https://polylang.pro/account' ),
							'</a>'
						);
						break;

					case 'invalid':
					case 'site_inactive':
						$message = sprintf(
							/* translators: %1$s is a product name, %2$s is link start tag, %3$s is link end tag. */
							__( 'Your %1$s license key is not active for this URL. Please %2$svisit your account page%3$s to manage your license key URLs.', 'polylang' ),
							$item->name,
							sprintf( '<a href="%s" target="_blank">', 'https://polylang.pro/account' ),
							'</a>'
						);
						break;

					case 'item_name_mismatch':
						/* translators: %s is a product name */
						$message = sprintf( __( 'This is not a %s license key.', 'polylang' ), $item->name );
						break;

					case 'no_activations_left':
						$message = sprintf(
							/* translators: %1$s is link start tag, %2$s is link end tag */
							__( 'Your license key has reached its activation limit. %1$sView possible upgrades%2$s now.', 'polylang' ),
							sprintf( '<a href="%s" target="_blank">', 'https://polylang.pro/account' ),
							'</a>'
						);
						break;
				}
			} else {
				$class = 'license-valid';

				$out .= sprintf( '<button id="deactivate_%s" type="button" class="button button-secondary pll-deactivate-license">%s</button>', $item->id, __( 'Deactivate', 'polylang' ) );

				if ( 'lifetime' === $license->expires ) {
					$message = __( 'The license key never expires.', 'polylang' );
				} elseif ( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {
					$class = 'notice-warning notice-alt';
					$message = sprintf(
						/* translators: %1$s is a date, %2$s is link start tag, %3$s is link end tag. */
						__( 'Your license key will expire soon! Precisely, it will expire on %1$s. %2$sRenew your license key today!%3$s.', 'polylang' ),
						date_i18n( get_option( 'date_format' ), strtotime( $license->expires, $now ) ),
						sprintf( '<a href="%s" target="_blank">', 'https://polylang.pro/checkout/?edd_license_key=' . $item->license_key ),
						'</a>'
					);
				} else {
					$message = sprintf(
						/* translators: %s is a date */
						__( 'Your license key expires on %s.', 'polylang' ),
						date_i18n( get_option( 'date_format' ), strtotime( $license->expires, $now ) )
					);
				}
			}
		}

		if ( ! empty( $message ) ) {
			$out .= '<p>' . $message . '</p>';
		}

		return sprintf( '<tr id="pll-license-%s" class="%s">%s</tr>', $item->id, $class, $out );
	}

	/**
	 * Ajax method to save the license keys and activate the licenses at the same time
	 * Overrides parent's method
	 *
	 * @since 1.9
	 */
	public function save_options() {
		check_ajax_referer( 'pll_options', '_pll_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( isset( $_POST['module'] ) && $this->module === $_POST['module'] && ! empty( $_POST['licenses'] ) ) {
			$x = new WP_Ajax_Response();
			foreach ( $this->items as $item ) {
				if ( ! empty( $_POST['licenses'][ $item->id ] ) ) {
					$updated_item = $item->activate_license( sanitize_key( $_POST['licenses'][ $item->id ] ) );
					$x->Add( array( 'what' => 'license-update', 'data' => $item->id, 'supplemental' => array( 'html' => $this->get_row( $updated_item ) ) ) );
				}
			}

			// Updated message
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 'polylang' ), 'updated' );
			ob_start();
			settings_errors();
			$x->Add( array( 'what' => 'success', 'data' => ob_get_clean() ) );
			$x->send();
		}
	}

	/**
	 * Ajax method to deactivate a license
	 *
	 * @since 1.9
	 */
	public function deactivate_license() {
		check_ajax_referer( 'pll_options', '_pll_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( ! isset( $_POST['id'] ) ) {
			wp_die( 0 );
		}

		$id = substr( sanitize_text_field( wp_unslash( $_POST['id'] ) ), 11 );
		wp_send_json(
			array(
				'id'   => $id,
				'html' => $this->get_row( $this->items[ $id ]->deactivate_license() ),
			)
		);
	}
}
