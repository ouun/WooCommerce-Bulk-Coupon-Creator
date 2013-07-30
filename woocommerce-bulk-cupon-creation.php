<?php
/*
Plugin Name: WooCommerce Bulk Coupon Creator
Plugin URI: http://www.djio.com.br/woocommerce-bulk-coupon-creator/
Description: This plugin creates coupon codes inside WooCommerce in bulk with few configuration options.
Version: 0.1
Author: DJIO
Author URI: http://www.djio.com.br/wordpress/


*/

/*  Copyright 2012 DJIO (email: wordpress@djio.com.br)

	Based on the Bulk Post Creator Plus, work of Mochammad Masbuchin (email: buchin@masbuchin.com)
	Thanks to Abundant Media, Inc.  (email : sarah@howdyblog.com)
	
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
0.1 
- Add option to select publish or draft post.
*/
// Add admin menu
// Create admin form (including nonces)
// Parse results of admin form
// Create a new post for each title

class WooCommerceBulkCouponCreator {
	
	static $upgrade_message = 'Please upgrade to the current version of WordPress. Not only is it necessary for this plugin to work properly, but it will also help prevent hackers from getting into your blog through old security holes.';
	static $nonce_name = 'wc-bulk-coupon-creator-plus-create-coupons';
	
	public function WooCommerceBulkCouponCreator()
	{
		load_plugin_textdomain('djio-wcbcc', false, basename( dirname( __FILE__ ) ) . '/languages' );

		/**
		 * Check if WooCommerce is active
		 **/
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
		{
		}
		else 
		{
			// _e("WooCommerce is not installed, therefore you can't use this plugin", 'djio-wcbcc');
			// return null;	
		}
	}

	static public function bulk_coupon_add_form() {
		echo '<div class="wrap">'.PHP_EOL;
		echo '<h2>'. __('Bulk Post Creator Plus', 'djio-wcbcc') .'</h2>'.PHP_EOL;
		echo '	<div class="updated">
						<strong><p>'. sprintf(__('Thanks for using this plugin! If it works and you are satisfied with the results, Please <a href="%1$s">Donate</a> to help us to continue support and development of this <i>free</i> software!', 'djio-wcbcc'),'http://www.djio.com.br/go/donate') .'
						</p></strong>
					<div style="clear: right;"></div>
					</div>
				<div class="metabox-holder has-right-sidebar" id="poststuff">
					<div class="inner-sidebar">
						<div style="position: relative;" class="meta-box-sortabless ui-sortable" id="side-sortables">
							<div class="postbox" id="sm_pnres">
								<h3 class="hndle"><span>'. __('About this Plugin:', 'djio-wcbcc') .'</span></h3>
								<div class="inside">
									<ul>
									<li><a href="http://www.djio.com.br/wordpress/bulk-post-creator-plus/" class="sm_button sm_pluginHome">'. __('Plugin Homepage', 'djio-wcbcc') .'</a></li>
									
									<li><a href="http://www.djio.com.br/go/donate" class="sm_button sm_donatePayPal">'. __('Donate with PayPal', 'djio-wcbcc') .'</a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
					<div class="has-sidebar sm-padded">
						<div class="has-sidebar-content" id="post-body-content">
							<div class="meta-box-sortabless">
								
								<div class="postbox">
									<div title="'. __('Click to toggle', 'djio-wcbcc') .'" class="handlediv"> <br></div>
									<h3 class="hndle"> <span></span></h3>
									<div class="inside">
					'.PHP_EOL;
		if ( ! empty ($_POST['bulk_coupon_qtt']) ) {
			self::create_coupons($_POST['bulk_coupon_qtt']);
		} else {
			self::display_form();
		}
		
		echo '</div></div></div></div></div></div></div>'.PHP_EOL;
		
	}
	
	private function display_form() 
	{
		global $woocommerce;
	
		echo '<form method="post" action="">'.PHP_EOL;
		if ( function_exists('wp_nonce_field') ) 
		{
			wp_nonce_field('wc-bulk-coupon-creator-plus-create-coupons');
			//wp_nonce_field(self::$nonce_name);
		} else {
			die ('<p>'.self::$upgrade_message.'</p>');
		}
		
		echo '<table style="text-align: left; padding: 10px 30px;">
			<tr valign="top">
				<th scope="row">'. __('How many coupons do you want to create in this series?', 'djio-wcbcc') .'</th>
				<td><input type="text" name="bulk_coupon_qtt"  size="4" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">'. __('Do you want to setup pre/pos strings?<br />(if not, just leave it empty)', 'djio-wcbcc') .'</th>
				<td><input type="text" name="bulk_coupon_pre" size="8" />'. _x('CODE', 'code placeholder' , 'djio-wcbcc') .'<input type="text" name="bulk_coupon_pos" size="8" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">'. __('Coupon Status', 'djio-wcbcc') .'</th>
				<td>
					<select name="bulk_coupon_status">
						<option value="draft">'. __('Draft', 'djio-wcbcc') .'</option>
						<option value="publish">'. __('Published', 'djio-wcbcc') .'</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">'. __('Product Categories', 'djio-wcbcc') .'</th>
				<td>
					<select id="product_categories" name="product_categories[]" class="chosen_select" multiple="multiple" data-placeholder="'. __('Any category', 'djio-wcbcc') .'">';
					$category_ids = (array) get_post_meta( $post->ID, 'product_categories', true );
					$categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
					if ($categories) foreach ($categories as $cat) {
						echo '<option value="'.$cat->term_id.'"';
						if (in_array($cat->term_id, $category_ids)) echo 'selected="selected"';
						echo '>'. $cat->name .'</option>';
					}
					echo '</select>
				</td>
			</tr>
			</table>'.PHP_EOL;


		echo '<input type="hidden" name="action" value="update" />'.PHP_EOL;
		echo '<p class="submit">
			<input type="submit" class="button-primary" value="'.__('Create Now', 'djio-wcbcc').'" />
			</p>'.PHP_EOL;
	}
	
	private function create_coupons($count = 0, $size = 5) 
	{
		check_admin_referer('wc-bulk-coupon-creator-plus-create-coupons');
		//check_admin_referer(self::$nonce_name);
		if ($count) 
		{
			
			$pre = $_POST['bulk_coupon_pre'];
			$pos = $_POST['bulk_coupon_pos'];
			$status = $_POST['bulk_coupon_status'];
			$categories = $_POST['product_categories'];
			$sum = 0;
			
			echo '<ul>'.PHP_EOL;
			
			for ($i = 1; $i <= $count; $i++) 
			{
				$title = self::keygen($size, $pre, $pos);
			
				if ($new_draft_id = self::create_coupon($title, 'shop_coupon', $status)) 
				{
					update_post_meta( $new_draft_id, 'discount_type', 'percent_product' );
					update_post_meta( $new_draft_id, 'coupon_amount', 100 );
					update_post_meta( $new_draft_id, 'usage_limit', 1 );
					update_post_meta( $new_draft_id, 'product_categories', $categories);

					echo '<li>'. __('Created', 'djio-wcbcc') . ' #' . $i . ': <a href="post.php?post='.$new_draft_id.'&action=edit">'.$title.'</a>'.PHP_EOL;
					$sum++;
				}
			}
		
			echo '</ul>'.PHP_EOL;
		
			echo '<p>'. sprintf(__('All done! <a href="%s">See all Coupons &raquo;</a>', 'djio-wcbcc'), 'edit.php?post_type=shop_coupon') .'</p>'.PHP_EOL;
			echo '<p>'. __('Total os Coupons created', 'djio-wcbcc') . ': ' . $sum .'</p>'.PHP_EOL;
			
		}
	}
	
	private function create_coupon($title = null, $type = 'shop_coupon', $status = 'draft') {
		if ( ! empty($title)) {
			global $wpdb;
			
			$new_draft_post = array(
			  'post_content' => '',
			  'post_status' => $status,
			  'post_title' => $title,
			  'post_type' => $type,
			);
			
			if ( $new_draft_id = wp_insert_post( $new_draft_post ) ) {
				// do_action( 'woocommerce_process_shop_coupon_meta', $new_draft_id, $new_draft_post );
				return $new_draft_id;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	static public function set_plugin_meta($links, $file) {
		$plugin = plugin_basename(__FILE__);

		// create link
		if ($file == $plugin) {
			return array_merge(
				$links,
				array( sprintf( '<a href="edit.php?page=%s">%s</a>', $plugin, __('Settings', 'djio-wcbcc') ) )
			);
			$settings_link = '<a href="options-general.php?page=custom-field-template.php">' . __('Settings', 'djio-wcbcc') . '</a>';
			$links = array_merge( array($settings_link), $links);
		}
		return $links;
	}
	
	static public function add_plugin_menu() {
		add_posts_page( __('Bulk Coupon Creator', 'djio-wcbcc'), __('Create Bulk Coupon', 'djio-wcbcc'), 'edit_posts', 'woocommerce-bulk-cupon-creator/woocommerce-bulk-cupon-creator.php', array('WooCommerceBulkCouponCreator','bulk_coupon_add_form'));
	}
	
	// Generates a random Key
	static private function keygen($length = 5, $pre = '', $pos = '')
	{
		$key = '';
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		
		$inputs = array_merge(range('z','a'),range(0,9),range('A','Z'));
	
		for($i=0; $i<$length; $i++)
		{
			$key .= $inputs{mt_rand(0,61)};
		}
		
		return $pre . strtoupper($key) . $pos;
	}
	
	
}

$np_bulk_coupon_creator = new WooCommerceBulkCouponCreator();

add_filter( 'plugin_row_meta', array('WooCommerceBulkCouponCreator','set_plugin_meta'), 10, 2 );
add_action( 'admin_menu', array('WooCommerceBulkCouponCreator','add_plugin_menu') );