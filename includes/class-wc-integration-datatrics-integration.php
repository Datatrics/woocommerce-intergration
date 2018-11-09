<?php

/*
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

/**
 * Datatrics Integration
 *
 * Allows tracking code to be inserted into store pages.
 *
 * @class    WC_Integration_Datatrics_Intergration
 * @extends  WC_Integration
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'WC_Integration_Datatrics_Intergration' ) ) {
	class WC_Integration_Datatrics_Intergration extends WC_Integration {
		const DATATRICS_URL = 'app.datatrics.com';

		public $id;

		public $form_text_fields = array();

		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {
			global $woocommerce;
			$this->id                 = 'datatrics';
			$this->method_title       = __( 'WooCommerce Datatrics', 'woocommerce' );
			$this->method_description = __( 'This intergartion enables you to integrate seamlessly with Datatrics.', 'woocommerce' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->datatrics_projectid                   = $this->get_option( 'datatrics_projectid' );
			$this->datatrics_standard_tracking_enabled   = $this->get_option( 'datatrics_standard_tracking_enabled' );
			$this->datatrics_ecommerce_tracking_enabled  = $this->get_option( 'datatrics_ecommerce_tracking_enabled' );
			$this->datatrics_cartupdate_tracking_enabled = $this->get_option( 'datatrics_cartupdate_tracking_enabled' );

			// Actions.
			$this->addActions();
		}

		/**
		 * Initialise Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields() {
			$this->form_fields = array(
				'datatrics_projectid'      => array(
					'title'       => __( 'Datatrics Project ID', 'woocommerce' ),
					'description' => __( 'You can find your project ID in Datatrics Project Settings.', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'default'     => ''
				),
				'datatrics_standard_tracking_enabled'   => array(
					'title'         => __( 'Tracking code', 'woocommerce' ),
					'label'         => __( 'Add tracking code to your site.', 'woocommerce' ),
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'default'       => ( $this->is_wp_datatrics_installed() ) ? 'no' : 'yes'
				),
				'datatrics_ecommerce_tracking_enabled'  => array(
					'label'         => __( 'Add eCommerce tracking code to the thankyou page', 'woocommerce' ),
					'type'          => 'checkbox',
					'checkboxgroup' => '',
					'default'       => 'yes'
				),
				'datatrics_cartupdate_tracking_enabled' => array(
					'label'         => __( 'Add cart update for add to cart actions (i.e. allows to track abandoned carts)', 'woocommerce' ),
					'type'          => 'checkbox',
					'checkboxgroup' => 'end',
					'default'       => 'yes'
				)
			);
		}


		/**
		 * Datatrics standard tracking
		 *
		 * @access public
		 * @return void
		 */
		function datatrics_tracking_code() {
			include_once( __DIR__ . '/../templates/tracking-code.php' );
		}

		/**
		 * Datatrics eCommerce order tracking
		 *
		 * @access public
		 *
		 * @param mixed $order_id
		 *
		 * @return void
		 */
		function ecommerce_tracking_code( $order_id ) {
			if ( get_post_meta( $order_id, '_datatrics_tracked', true ) == 1 ) {
				#return;
			}

			$order = new WC_Order( $order_id );
			$code  = '
	            var _paq = _paq || [];
	        ';

			if ( $order->get_items() ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $order->get_product_from_item( $item );
					$code .= '
	                _paq.push(["addEcommerceItem",
	                    "' . esc_js( $_product->get_sku() ? $_product->get_sku() : $_product->id ) . '",
	                    "' . esc_js( $item['name'] ) . '",';

					$out        = array();
					$categories = get_the_terms( $_product->id, 'product_cat' );
					if ( $categories ) {
						foreach ( $categories as $category ) {
							$out[] = $category->name;
						}
					}
					if ( count( $out ) > 0 ) {
						$code .= '["' . join( "\", \"", $out ) . '"],';
					} else {
						$code .= '[],';
					}

					$code .= '"' . esc_js( $order->get_item_total( $item ) ) . '",';
					$code .= '"' . esc_js( $item['qty'] ) . '"';
					$code .= "]);";
				}
			}

			$code .= '
	            _paq.push(["trackEcommerceOrder",
	                "' . esc_js( $order->get_order_number() ) . '",
	                "' . esc_js( $order->get_total() ) . '",
	                "' . esc_js( $order->get_total() - $order->get_total_shipping() ) . '",
	                "' . esc_js( $order->get_total_tax() ) . '",
	                "' . esc_js( $order->get_total_shipping() ) . '",
					false
	            ]);
	        ';

			echo '<script type="text/javascript">' . $code . '</script>';

			update_post_meta( $order_id, '_datatrics_tracked', 1 );
		}

		function get_cart_items_js_code() {
			global $woocommerce;

			$cart_content = $woocommerce->cart->get_cart();
			$code         = '
	            var cartItems = [];';
			foreach ( $cart_content as $item ) {

				$item_sku   = esc_js( ( $sku = $item['data']->get_sku() ) ? $sku : $item['product_id'] );
				$item_price = $item['data']->get_price();
				$item_title = $item['data']->get_title();
				$cats       = $this->getProductCategories( $item['product_id'] );

				$code .= "
	            cartItems.push({
	                    sku: \"$item_sku\",
	                    title: \"$item_title\",
	                    price: $item_price,
	                    quantity: {$item['quantity']},
	                    categories: $cats
	                });
	            ";
			}

			return $code;
		}

		/**
		 * Sends cart update request
		 */
		function update_cart() {

			$code = $this->get_cart_items_js_code();

			wc_enqueue_js( "
	            " . $code . "
	            var arrayLength = cartItems.length, revenue = 0;
	
	            for (var i = 0; i < arrayLength; i++) {
	                _paq.push(['addEcommerceItem',
	                    cartItems[i].sku,
	                    cartItems[i].title,
	                    cartItems[i].categories,
	                    cartItems[i].price,
	                    cartItems[i].quantity
	                    ]);
	
	                revenue += cartItems[i].price * cartItems[i].quantity;
	            }
	
	
	            _paq.push(['trackEcommerceCartUpdate', revenue]);
			" );
		}

		/**
		 * Ajax action to get cart
		 */
		function get_cart() {
			global $woocommerce;

			$cart_content = $woocommerce->cart->get_cart();
			$products     = array();

			foreach ( $cart_content as $item ) {
				$item_sku = esc_js( ( $sku = $item['data']->get_sku() ) ? $sku : $item['product_id'] );
				$cats     = $this->getProductCategories( $item['product_id'] );

				$products[] = array(
					'sku'        => $item_sku,
					'title'      => $item['data']->get_title(),
					'price'      => $item['data']->get_price(),
					'quantity'   => $item['quantity'],
					'categories' => $cats
				);
			}

			header( 'Content-Type: application/json; charset=utf-8' );

			echo json_encode( $products );
			exit;
		}

		function send_update_cart_request() {
			if ( ! empty( $_REQUEST['add-to-cart'] ) && is_numeric( $_REQUEST['add-to-cart'] ) ) {
				$code = $this->get_cart_items_js_code();
				wc_enqueue_js( $code . "
				    $(document).ready(function(){
	                    $('body').trigger('added_to_cart');
	                });
	            " );
			}
		}

		/**
		 * @param $itemID
		 *
		 * @return string
		 */
		protected function getProductCategories( $itemID ) {
			$out        = array();
			$categories = get_the_terms( $itemID, 'product_cat' );

			if ( $categories ) {
				foreach ( $categories as $category ) {
					$out[] = $category->name;
				}
			}

			if ( count( $out ) > 0 ) {
				$cats = '["' . join( "\", \"", $out ) . '"]';

				return $cats;
			} else {
				$cats = '[]';

				return $cats;
			}
		}

		/**
		 * Add actions using WooCommerce hooks
		 */
		protected function addActions() {
			add_action( 'woocommerce_update_options_integration_datatrics', array( $this, 'process_admin_options' ) );
			if (( ( empty( $this->datatrics_projectid ) || ! is_numeric( $this->datatrics_projectid ) ) && ! $this->is_wp_datatrics_installed() ) 
				#|| is_admin() || current_user_can( 'manage_options' ) 
			) {
				return;
			}

			if ( $this->datatrics_standard_tracking_enabled == 'yes' ) {
				add_action( 'wp_footer', array( $this, 'datatrics_tracking_code' ) );
				if ( $this->datatrics_ecommerce_tracking_enabled == 'yes' ) {
					add_action( 'woocommerce_thankyou', array( $this, 'ecommerce_tracking_code' ) );
					add_action( 'wp_ajax_nopriv_woocommerce_datatrics_get_cart', array( $this, 'get_cart' ) );
					add_action( 'wp_ajax_woocommerce_datatrics_get_cart', array( $this, 'get_cart' ) );
					add_action( 'woocommerce_after_single_product_summary', array( $this, 'product_view' ) );
					add_action( 'woocommerce_after_shop_loop', array( $this, 'category_view' ) );
				}
				if ( $this->datatrics_cartupdate_tracking_enabled == 'yes' ) {
					add_action( 'woocommerce_after_single_product', array( $this, 'send_update_cart_request' ) );
					add_action( 'woocommerce_after_cart', array( $this, 'update_cart' ) );
					$suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
					$assets_path          = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/';
					$frontend_script_path = $assets_path . '../assets/js/';
					wp_enqueue_script( 'get-cart', $frontend_script_path . 'get-cart' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );
				}
			}
		}		

		function category_view() {
			global $wp_query;

			if ( isset( $wp_query->query_vars['product_cat'] ) && ! empty( $wp_query->query_vars['product_cat'] ) ) {
				$jsCode = sprintf( "
	            _paq.push(['setEcommerceView',
	                    false,
	                    false,
	                    '%s'
	            ]);
	            _paq.push(['trackPageView']);
	            ", urlencode( $wp_query->queried_object->name ) );
				wc_enqueue_js( $jsCode );
			}
		}

		function product_view() {
			global $product;

			$jsCode = sprintf( "
	            _paq.push(['setEcommerceView',
	                    '%s',
	                    '%s',
	                    %s,
	                    %f
	            ]);
	            _paq.push(['trackPageView']);
	        ",
				$product->get_sku(),
				urlencode( $product->get_title() ),
				$this->getEncodedCategoriesByProduct( $product ),
				$product->get_price()
			);
			wc_enqueue_js( $jsCode );
		}

		protected function getEncodedCategoriesByProduct( $product ) {
			$categories = get_the_terms( $product->post->ID, 'product_cat' );

			if ( ! $categories ) {
				$categories = array();
			}

			$categories = array_map( function ( $element ) {
				return sprintf( "'%s'", urlencode( $element->name ) );
			}, $categories );

			return sprintf( "[%s]", implode( ", ", $categories ) );
		}

		protected function setup() {
			$this->setOption( 'datatrics_projectid', $_GET['projectid'] );
			$postData = [
				$this->get_field_key( "datatrics_projectid" )      => $_GET['projectid']
			];

			$this->set_post_data( $postData );
			$this->process_admin_options();

			WC_Admin_Settings::add_message( __( 'Your site has been successfuly integrated with Datatrics!', 'woocommerce' ) );
			delete_option( 'woocommerce_datatrics_ts_valid' );
			add_option( 'woocommerce_datatrics_integrated', true );
			
			$this->update_wp_datatrics_settings();
		}

		protected function setOption( $key, $value ) {
			$this->settings[ $key ] = $value;
		}

		public function validate_checkbox_field( $key, $value ) {
			return 'yes';
		}

		public function admin_options() {
			$uriParts = explode( '?', $_SERVER['REQUEST_URI'], 2 );
			$url      = 'http://' . $_SERVER['HTTP_HOST'] . $uriParts[0] . '?page=wc-settings&tab=integration';

			include_once( __DIR__ . '/../templates/admin-options.php' );

		}

		public function validate_settings_fields( $form_fields = false ) {
			parent::validate_settings_fields( array_merge( $this->form_text_fields, $this->form_fields ) );
		}

		/**
		 * @return mixed
		 */
		protected function getSiteUrl() {
			$siteUrl = str_replace( 'http://', '', get_site_url() );
			$siteUrl = str_replace( 'https://', '', $siteUrl );

			return $siteUrl;
		}

		/**
		 * Check if wp datatrics is installed
		 *
		 * @return bool
		 */
		protected function is_wp_datatrics_installed() {
			return ( isset( $GLOBALS['wp_datatrics'] ) );
		}

		protected function get_wp_datatrics_settings() {
			return get_option( 'wp-datatrics_settings' );
		}

		protected function get_wp_datatrics_global_settings() {
			return get_option( 'wp-datatrics_global-settings' );
		}

		protected function set_wp_datatrics_settings( $value ) {
			update_option( 'wp-datatrics_settings', $value );
		}

		protected function set_wp_datatrics_global_settings( $value ) {
			update_option( 'wp-datatrics_global-settings', $value );
		}

		protected function update_wp_datatrics_settings() {
			if ( ! $this->is_wp_datatrics_installed() ) {
				return;
			}

			$settings       = $this->get_wp_datatrics_settings();
			$globalSettings = $this->get_wp_datatrics_global_settings();

			if ( $projectid = $this->get_option( 'datatrics_projectid' ) ) {
				$settings['projectid']			 	 = $projectid;
				$globalSettings['add_tracking_code'] = 0;
				$this->setOption( 'datatrics_standard_tracking_enabled', 'yes' );

				$this->set_wp_datatrics_settings( $settings );
				$this->set_wp_datatrics_global_settings( $globalSettings );

				WC_Admin_Settings::add_message( __( 'Introduced changes to WP Datatrics settings. Please update them according to your needs!',
					'woocommerce' ) );
			} else {
				$settings['projectid']                 = '';
				$globalSettings['add_tracking_code'] = 0;
				$this->setOption( 'datatrics_standard_tracking_enabled', 'no' );

				$this->set_wp_datatrics_settings( $settings );
				$this->set_wp_datatrics_global_settings( $globalSettings );

				WC_Admin_Settings::add_message( __( 'Introduced changes to WP Datatrics settings. Please update them according to your needs!',
					'woocommerce' ) );
			}
		}
	}
}
