<?php

/**
 * Plugin Name:     Mai Config Generator
 * Plugin URI:      https://bizbudding.com/products/mai-config-generator/
 * Description:     Generate config.php content for setting defaults in a custom Mai Theme (v2 only).
 * Version:         1.0.1
 *
 * Author:          BizBudding
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Mai_Config_Generator Class.
 *
 * @since 0.1.0
 */
final class Mai_Config_Generator {

	/**
	 * @var   Mai_Config_Generator The one true Mai_Config_Generator
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main Mai_Config_Generator Instance.
	 *
	 * Insures that only one instance of Mai_Config_Generator exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    Mai_Config_Generator::setup_constants() Setup the constants needed.
	 * @uses    Mai_Config_Generator::includes() Include the required files.
	 * @uses    Mai_Config_Generator::hooks() Activate, deactivate, etc.
	 * @see     Mai_Config_Generator()
	 * @return  object | Mai_Config_Generator The one true Mai_Config_Generator
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_Config_Generator;
			// Methods.
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'MAI_CONFIG_GENERATOR_VERSION' ) ) {
			define( 'MAI_CONFIG_GENERATOR_VERSION', '1.0.1' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_CONFIG_GENERATOR_PLUGIN_DIR' ) ) {
			define( 'MAI_CONFIG_GENERATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path.
		if ( ! defined( 'MAI_CONFIG_GENERATOR_INCLUDES_DIR' ) ) {
			define( 'MAI_CONFIG_GENERATOR_INCLUDES_DIR', MAI_CONFIG_GENERATOR_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'MAI_CONFIG_GENERATOR_PLUGIN_URL' ) ) {
			define( 'MAI_CONFIG_GENERATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'MAI_CONFIG_GENERATOR_PLUGIN_FILE' ) ) {
			define( 'MAI_CONFIG_GENERATOR_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'MAI_CONFIG_GENERATOR_BASENAME' ) ) {
			define( 'MAI_CONFIG_GENERATOR_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';
		// Includes.
		// foreach ( glob( MAI_CONFIG_GENERATOR_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
	}

	/**
	 * Run the hooks.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function hooks() {

		add_action( 'admin_init', [ $this, 'updater' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu_page' ], 12 );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @uses https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return  void
	 */
	public function updater() {

		// Bail if current user cannot manage plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			return;
		}

		// Setup the updater.
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-config-generator/', __FILE__, 'mai-config-generator' );

		// Maybe set github api token.
		if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
			$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
		}
	}

	/**
	 * Registers plugin admin menu pages.
	 * Exposes Reusable Blocks UI in backend.
	 *
	 * @link  https://www.billerickson.net/reusable-blocks-accessible-in-wordpress-admin-area
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function admin_menu_page() {
		add_submenu_page(
			'mai-theme',
			esc_html__( 'Config Generator', 'mai-config-generator' ),
			esc_html__( 'Config Generator', 'mai-config-generator' ),
			'edit_posts',
			'mai-config-generator',
			[ $this, 'get_config' ],
			null
		);
	}

	/**
	 * Config generator.
	 */
	public function get_config() {
		if ( ! function_exists( 'mai_get_config' ) ) {
			return;
		}

		wp_enqueue_script( 'mai-config-generator', MAI_CONFIG_GENERATOR_PLUGIN_URL . '/assets/js/mai-config-generator.js', '', MAI_CONFIG_GENERATOR_VERSION, true );

		$config   = [];
		$options  = mai_get_options();
		$defaults = require mai_get_dir() . 'config/_default.php';
		$keepers  = []; // Any top level settings that transfer dirctly from options to our config. I didn't see any but thought I would.

		foreach ( $options as $key => $value ) {

			// Fonts.
			if ( mai_has_string( '-typography', $key ) && $value ) {
				if ( isset( $value['font-family'] ) && isset( $value['font-weight'] ) ) {
					$config['global-styles']['fonts'][ str_replace( '-typography', '', $key ) ] = sprintf( '%s:%s', $value['font-family'], $value['font-weight'] );
				}
			}

			// Colors.
			if ( mai_has_string( 'color-', $key ) && $value ) {
				$config['global-styles']['colors'][ str_replace( 'color-', '', $key ) ] = $value;
			}

			// Sticky Header.
			if ( ( 'site-header-sticky' === $key ) && $value ) {
				$config['theme-support']['add'] = 'sticky-header';
			}

			// Transparent Header.
			if ( ( 'site-header-transparent' === $key ) && $value ) {
				$config['theme-support']['add'] = 'transparent-header';
			}

			// Boxed Container, only if true.
			if ( ( 'boxed-container' === $key ) && $value ) {
				$config['theme-support']['add'] = $value;
			}

			// Page Header.
			if ( mai_has_string( 'page-header-', $key ) && $value ) {
				$config['settings']['page-header'][ str_replace( 'page-header-', '', $key ) ] = $value;
			}

			// Content Archives.
			if ( ( 'content-archives' === $key ) && $value ) {
				$config['settings'][ $key ] = $value;
			}

			// Single Content.
			if ( ( 'single-content' === $key ) && $value ) {
				$config['settings'][ $key ] = $value;
			}

			// Archive Settings. Must be after Content Archives.
			if ( ( 'archive-settings' === $key ) && $value ) {
				$config['settings']['content-archives']['enable'] = $value;
			}

			// Single Settings. Must be after Single Content.
			if ( ( 'single-settings' === $key ) && $value ) {
				$config['settings']['single-content']['enable'] = $value;
			}

			// Site Layouts.
			if ( ( 'site-layouts' === $key ) && $value ) {
				$config['settings'][ $key ] = $value;
			}

			// After Header Menu Alignment.
			if ( ( 'after-header-menu-alignment' === $key ) && $value ) {
				$config['settings'][ $key ] = $value;
			}

			// Add keepers.
			if ( in_array( $key, $keepers ) && $value ) {
				$config[ $key ] = $value;
			}

		}

		// Unset if page header image is an ID.
		if ( isset( $config['settings']['page-header']['image'] ) && is_numeric( $config['settings']['page-header']['image'] ) ) {
			unset( $config['settings']['page-header']['image'] );
		}

		$config = $this->config_cleanup( $config, $defaults );

		$html  = '';
		$html .= '<div class="wrap">';
			$html .= '<h1>' . __( 'Config Generator', 'mai-config-generator' ) . '</h1>';
			$html .= '<p>' . sprintf( '%s <code>wp-content/themes/{theme-name-here}/config.php</code> %s', __( 'Copy this code to', 'mai-config-generator' ), __( 'to use your current theme settings as the defaults.', 'mai-config-generator' ) ) . '</p>';
			$html .= '<textarea style="width:100%;max-width:600px;">';
				$html .= $this->get_config_header();
				$html .= 'return ['. "\r\n";
				$html .= $this->get_array_html( $config, $this->get_tab() );
				$html .= '];';
				$html .= "\r\n";
			$html .= '</textarea>';
		$html .= '</div>';

		echo $html;
	}


	public function get_array_html( $array, $indent = '' ) {
		$html = '';
		foreach ( $array as $key => $values ) {
			if ( is_array( $values ) ) {
				$html .= "{$indent}'{$key}' => [" . "\r\n";
					$html .= $this->get_array_html( $values, $indent . $this->get_tab() );
				$html .= "{$indent}]," . "\r\n";
			} else {
				if ( is_numeric( $key ) ) {
					$html .= "{$indent}'{$values}'," . "\r\n";
				} elseif ( is_bool( $values ) ) {
					$string = $values ? 'true' : 'false';
					$html  .= "{$indent}'{$key}' => {$string}," . "\r\n";
				} else {
					$html .= "{$indent}'{$key}' => '{$values}'," . "\r\n";
				}
			}
		}
		return $html;
	}

	public function config_cleanup( $array, $defaults ) {
		foreach ( $array as $key => $value ) {
			// Remove layout dividers. Not sure why they are getting saved to the db anyway.
			if ( mai_has_string( '-layout-divider', $key ) || mai_has_string( '-field-divider', $key ) ) {
				unset( $array[ $key ] );
			}
			// Remove empty hr's. Not sure why they are getting saved to the db anyway.
			if ( '' == $key && '<hr>' === $value ) {
				unset( $array[ $key ] );
			}
			// Recursive array.
			elseif ( is_array( $value ) ) {
				if ( $this->has_string_keys( $value ) ) {
					$array[ $key ]    = $this->config_cleanup( $value, isset( $defaults[ $key ] ) ? $defaults[ $key ] : [] );
					if ( empty( $array[ $key ] ) ) {
						unset( $array[ $key ] );
					}
				} else {
					$array[ $key ] = array_values( $value );
				}
			}
			// Remove if empty, set to remove, or same as default.
			elseif ( ( '' === $value ) || ( isset( $defaults[ $key ] ) && ( $value === $defaults[ $key ] ) ) ) {
				unset( $array[ $key ] );
			}
		}

		return $array;
	}

	public function get_config_header() {
		return '<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */
';
	}

	public function get_tab() {
		return '	';
	}

	public function has_string_keys( $array ) {
		return count( array_filter( array_keys( $array ), 'is_string' ) ) > 0;
	}
}

/**
 * The main function for that returns Mai_Config_Generator
 *
 * The main function responsible for returning the one true Mai_Config_Generator
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_Config_Generator(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Mai_Config_Generator The one true Mai_Config_Generator Instance.
 */
function Mai_Config_Generator() {
	return Mai_Config_Generator::instance();
}

// Get Mai_Config_Generator Running.
Mai_Config_Generator();
