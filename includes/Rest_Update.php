<?php
/*
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

/**
 * Class Rest_Update
 *
 * Updates a single plugin or theme, in a way suitable for rest requests.
 * This class inherits from Base in order to be able to call the
 * set_defaults function.
 *
 * @package Fragen\GitHub_Updater
 */
class Nhymxu_Rest_Update {

	/**
	 * Holds REST Upgrader Skin.
	 *
	 * @var \Fragen\GitHub_Updater\Rest_Upgrader_Skin
	 */
	protected $upgrader_skin;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->upgrader_skin = new Nhymxu_Rest_Upgrader_Skin();
	}

	/**
	 * Update plugin.
	 *
	 * @param string $plugin_slug
	 * @param string $tag
	 *
	 * @throws \Exception
	 */
	public function update_plugin( $plugin_slug ) {

		$updates_transient = get_site_transient( 'update_plugins' );

		$plugin_info = $this->check_update( 'plugin', $plugin_slug );
		
		if( !$plugin_info ) {
			return;
		}

		$update            = array(
			'slug'        => $plugin_info['slug'],
			'plugin'      => $plugin_info['filename'],
			'new_version' => null,
			'url'         => $plugin_info['homepage'],
			'package'     => $plugin_info['download_url'],
		);

		$updates_transient->response[ $plugin_info['filename'] ] = (object) $update;
		set_site_transient( 'update_plugins', $updates_transient );

		$upgrader = new \Plugin_Upgrader( $this->upgrader_skin );
		$upgrader->upgrade( $plugin_info['filename'] );

		$activate = activate_plugin( $plugin_info['filename'] );
		if ( ! $activate ) {
			$this->upgrader_skin->messages[] = 'Plugin reactivated successfully.';
		}
	}

	/**
	 * Update a single theme.
	 *
	 * @param string $theme_slug
	 * @param string $tag
	 *
	 * @throws \Exception
	 */
	public function update_theme( $theme_slug ) {

		if ( !is_dir( WP_CONTENT_DIR . '/themes/' . $theme_slug ) || !file_exists( WP_CONTENT_DIR . '/themes/' . $theme_slug ) ) {
			throw new \UnexpectedValueException( 'Theme not found: ' . $theme_slug );
		}

		if( file_exists( WP_CONTENT_DIR . '/themes/' . $theme_slug . '/update.json' ) ) {
			$theme_info = $this->check_update( 'theme', $theme_slug );
			
			//$theme_info = get_option('puc_external_updates_theme-' . $theme_slug);
			if( !$theme_info ) {
				return;
			}
	
			$updates_transient = get_site_transient( 'update_themes' );
			$update            = array(
				'theme'       => $theme_info['slug'],
				'new_version' => null,
				'url'         => $theme_info['details_url'],
				'package'     => $theme_info['download_url'],
			);
	
			$updates_transient->response[ $theme_info['slug'] ] = $update;
			set_site_transient( 'update_themes', $updates_transient );
		}

		$upgrader = new \Theme_Upgrader( $this->upgrader_skin );
		$upgrader->upgrade( $theme_slug );
	}

	public function check_update( $type, $slug ) {
		if( $type == 'plugin' ) {
			$installed = file_get_contents( WP_CONTENT_DIR . '/plugins/' . $slug . '/update.json' );
			$installed = json_decode( $installed, true );

			$response = wp_remote_get( $installed['url'] );
			if ( is_array( $response ) ) {
				$json = json_decode( $response['body'], true );
				$json['slug'] = $slug;
				$json['filename'] = $installed['filename'];

				return $json;
			}
		} elseif( $type == 'theme' ) {
			$installed = file_get_contents( WP_CONTENT_DIR . '/themes/' . $slug . '/update.json' );
			$installed = json_decode( $installed, true );

			$response = wp_remote_get( $installed['url'] );
			if ( is_array( $response ) ) {
				$json = json_decode( $response['body'], true );
				$json['slug'] = $slug;

				return $json;
			}
		}

		return false;
	}

	/**
	 * Is there an error?
	 */
	public function is_error() {
		return $this->upgrader_skin->error;
	}

	/**
	 * Get messages during update.
	 */
	public function get_messages() {
		return $this->upgrader_skin->messages;
	}

	/**
	 * Process request.
	 *
	 * Relies on data in $_REQUEST, prints out json and exits.
	 * If the request came through a webhook, and if the branch in the
	 * webhook matches the branch specified by the url, use the latest
	 * update available as specified in the webhook payload.
	 */
	public function process_request() {
		try {
			/*
			 * 128 == JSON_PRETTY_PRINT
			 * 64 == JSON_UNESCAPED_SLASHES
			 */
			$json_encode_flags = 128 | 64;

			if ( ! isset( $_REQUEST['key'] ) ||
			     $_REQUEST['key'] !== '0b3b5a9713344fe284cd3ed4d9de1975'
			) {
				throw new \UnexpectedValueException( 'Bad api key.' );
			}

			if ( isset( $_REQUEST['plugin'] ) ) {
				$this->update_plugin( $_REQUEST['plugin'] );
			} elseif ( isset( $_REQUEST['theme'] ) ) {
				$this->update_theme( $_REQUEST['theme'] );
			} else {
				throw new \UnexpectedValueException( 'No plugin or theme specified for update.' );
			}
		} catch ( \Exception $e ) {
			//http_response_code( 417 ); //@TODO PHP 5.4
			header( 'HTTP/1.1 417 Expectation Failed' );
			header( 'Content-Type: application/json' );

			echo json_encode( array(
				'message' => $e->getMessage(),
				'error'   => true,
			), $json_encode_flags );
			exit;
		}

		header( 'Content-Type: application/json' );

		$response = array(
			'messages' => $this->get_messages(),
			'response' => @$webhook_response ?: $_GET,
		);

		if ( $this->is_error() ) {
			$response['error'] = true;
			//http_response_code( 417 ); //@TODO PHP 5.4
			header( 'HTTP/1.1 417 Expectation Failed' );
		} else {
			$response['success'] = true;
		}

		echo json_encode( $response, $json_encode_flags ) . "\n";
		exit;
	}

	/**
	 * For compatibility with PHP 5.3
	 *
	 * @param string $name $_SERVER index.
	 *
	 * @return bool
	 */
	private function is_server_variable_set( $name ) {
		return isset( $_SERVER[ $name ] );
	}
}
