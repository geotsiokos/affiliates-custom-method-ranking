<?php
/**
 * Plugin Name: Affiliates Custom Method Ranking
 * Description: Implements an example method for use with Affiliates Pro.
 * Version: 1.0.1
 * Author: itthinx
 * Author URI: http://www.itthinx.com
 */
class ACM {

	/**
	 * Registers a custom referral amount method.
	 */
	public static function init() {
		if ( check_dependencies() ) {
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
	 * @param int $affiliate_id
	 * @param array $parameters
	 */
	public static function ranking_custom_method( $affiliate_id = null, $parameters = null ) {
		global $affiliates_db;
		$affiliates_relations_table = $affiliates_db->get_tablename( 'affiliates_relations' );
		$result = '0';
		$referrer_id = $affiliates_db->get_value(
			"SELECT from_affiliate_id FROM $affiliates_relations_table WHERE to_affiliate_id = %d ORDER BY from_date DESC",
			$current_affiliate_id
		);

		// when the referrer_id is empty we must stop right here
		// as there is no one who referred the current affiliate
		if ( $referrer_id && affiliates_check_affiliate_id( $referrer_id ) ) {
			$total_referrals = affiliates_get_affiliate_referrals(
				$affiliate_id,
				$from_date = null,
				$thru_date = null,
				$status    = get_option( 'aff_default_referral_status' ) ? get_option( 'aff_default_referral_status' ) : "accepted",
				$precise   = false
			);
			$rates = get_option( 'aff_ent_tier_rates', array() );
			if ( $total_referrals <= 10 ) {
				$rates[0] = 0.0066;
				$result = '0.06';
			} else if ( 10 < $total_referrals <= 100 ) {
				$rates[0] = 0.013;
				$result = '0.055';
			} else if ( 100 < $total_referrals <= 500 ) {
				$rates[0] = 0.0196;
				$result = '0.05';
			} else if ( 500 < $total_referrals <= 1500 ) {
				$rates[0] = 0.026;
				$result = '0.045';
			} else if ( 1500 < $total_referrals <= 5000 ) {
				$rates[0] = 0.0326;
				$result = '0.04';
			} else {
				$rates[0] = 0.0066;
				$result = '0.06';
			}
			update_option( 'aff_ent_tier_rates', $rates );
		} else {
			$result = '0.065';
		}

		return $result;
	}
}
add_action( 'init', array( 'ACM', 'init' ) );