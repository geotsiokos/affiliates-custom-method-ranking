<?php
/**
 * Plugin Name: Affiliates Custom Method Ranking
 * Description: Implements an example method for use with Affiliates Pro and Enterprise plugins.
 * Version: 1.0.1
 * Author: itthinx
 * Author URI: http://www.itthinx.com
 */
class ACM {

	/**
	 * Registers a custom referral amount method.
	 */
	public static function init() {
		if ( self::check_dependencies() ) {
			if ( class_exists( 'Affiliates_Referral' ) ) {
				Affiliates_Referral::register_referral_amount_method( array( __CLASS__, 'ranking_custom_method' ) );
			}
		}
	}

	public static function check_dependencies() {
		$result = true;
		$active_plugins = get_option( 'active_plugins', array() );
		$affiliates_is_active = in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );

		if ( !$affiliates_is_active ) {
			echo "<div class='error'><strong>Affiliates Tiers Custom Rates</strong> plugin requires <a href='http://www.itthinx.com/shop/affiliates-enterprise/'>Affiliates Enterprise</a> plugins to be installed and activated.</div>";
			$result = false;
		}
		return $result;
	}

	/**
	 * Custom referral amount method implementation.
	 *
	 * @param int $affiliate_id
	 * @param array $parameters
	 */
	public static function ranking_custom_method( $affiliate_id = null, $parameters = null ) {
		global $affiliates_db;

		$result = '0';
		if ( null !== $parameters && $parameters['type'] == 'sale' ) {
			$amount = $parameters['base_amount'];
			$affiliates_relations_table = $affiliates_db->get_tablename( 'affiliates_relations' );
			$referrer_id = $affiliates_db->get_value(
				"SELECT from_affiliate_id FROM $affiliates_relations_table WHERE to_affiliate_id = %d ORDER BY from_date DESC",
				$affiliate_id
			);

			// when the referrer_id is empty we must stop right here
			// as there is no one who referred the current affiliate
			if ( $referrer_id && affiliates_check_affiliate_id( $referrer_id ) ) {

				$rates = get_option( 'aff_ent_tier_rates', array() );
				$total_referrals = affiliates_get_affiliate_referrals(
					$affiliate_id,
					$from_date = null,
					$thru_date = null,
					$status    = get_option( 'aff_default_referral_status' ) ? get_option( 'aff_default_referral_status' ) : "pending",
					$precise   = false
				);

				if ( $total_referrals <= 10 ) {
					$rates[0] = 0.06;
					$referrer_rate = '0.0066';
				} else if ( $total_referrals > 10 && $total_referrals <= 100 ) {
					$rates[0] = 0.055;
					$referrer_rate = '0.013';
				} else if ( $total_referrals >100 && $total_referrals <= 500 ) {
					$rates[0] = 0.05;
					$referrer_rate = '0.0196';
				} else if ( $total_referrals > 500 && $total_referrals <= 1500 ) {
					$rates[0] = 0.045;
					$referrer_rate = '0.026';
				} else if ( $total_referrals >1500 && $total_referrals <= 5000 ) {
					$rates[0] = 0.04;
					$referrer_rate = '0.0326';
				} else {
					$rates[0] = 0.06;
					$referrer_rate = '0.0066';
				}

			} else {
				$rates[0] = 0;
				$referrer_rate = '0.065';
			}
			update_option( 'aff_ent_tier_rates', $rates );
			if( $parameters['type'] == 'sale' ) {
				$order = wc_get_order( $parameters['post_id'] );
				$amount = $order->get_total(); 
			}
			$result = bcmul( $referrer_rate, $amount, affiliates_get_referral_amount_decimals() );
		}
		return $result;
	}
}
add_action( 'init', array( 'ACM', 'init' ) );