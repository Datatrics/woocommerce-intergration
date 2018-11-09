<?php
/*
	Plugin Name: WooCommerce Datatrics integration
	Plugin URI: https://wordpress.org/plugins/woocommerce-datatrics-integration
	Description: Allows Datatrics Pixel code to be inserted into WooCommerce store pages.
	Version: 1.0.0
	Author: Datatrics
	Author URI: https://datatrics.com
	Text Domain: woocommerce-datatrics-integration
	Domain Path: /languages/
	License: GPLv3
	License URI: http://www.gnu.org/licenses/gpl-3.0.txt

	Copyright (C) 2018 by Datatrics B.V. <https://datatrics.com> 
	and associates (see AUTHORS.txt file).

	This file is part of WooCommerce Datatrics integration plugin.

	WooCommerce Datatrics integration plugin is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	WooCommerce Datatrics integration plugin is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with WooCommerce Datatrics integration plugin; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'WC_Integration_Datatrics' ) ){
	class WC_Integration_Datatrics {

		/**
		 * Construct the plugin.
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_action_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		}
		
		/**
		 * Initialize the plugin.
		 */
		public function init() {
			// Checks if WooCommerce is installed.
			if ( class_exists( 'WC_Integration' ) ) {
				// Include our integration class.
				include_once( 'includes/class-wc-integration-datatrics-integration.php' );
				// Register the integration.
				add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
			} else {
				// throw an admin error if you like
			}
		}


		/**
		 * Add intergrations.
		 *
		 * @param   array $integrations
		 * @return  array
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'WC_Integration_Datatrics_Intergration';
			return $integrations;
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @param   mixed $links Plugin Action links.
		 * @return  array
		 */
		public function plugin_action_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=integration' ) . '" aria-label="' . esc_attr__( 'View Datatrics settings', 'General' ) . '">' . esc_html__( 'Settings', 'General' ) . '</a>',
			);
			return array_merge( $action_links, $links );
		}
		
		/**
		 * Show row meta on the plugin screen.
		 *
		 * @param   mixed $links Plugin Row Meta.
		 * @param   mixed $file  Plugin Base file.
		 * @return  array
		 */
		public function plugin_row_meta( $links, $file ) {
			if ( plugin_basename(__FILE__) === $file ) {
				$row_meta = array(
					'docs'    => '<a href="' . esc_url( apply_filters( 'datatrics_docs', 'https://help.datatrics.com' ) ) . '" aria-label="' . esc_attr__( 'View Datatrics documentation', 'General' ) . '" target="_blank">' . esc_html__( 'Docs', 'General' ) . '</a>',
				);
				return array_merge( $links, $row_meta );
			}
			return (array) $links;
		}
		
	}
	$WC_Integration_Datatrics = new WC_Integration_Datatrics( __FILE__ );
}