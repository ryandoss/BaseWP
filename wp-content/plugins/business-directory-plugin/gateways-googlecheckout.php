<?php

class WPBDP_GoogleCheckoutGateway {

	public function __construct() {
		add_action('wpbdp_modules_init', array($this, '_bd_integration'));
	}

	public function _bd_integration() {
        $payments_api = wpbdp_payments_api();
        $payments_api->register_gateway('googlecheckout', array(
        	'name' => _x('Google Checkout', 'googlecheckout', 'WPBDM'),
            'check_callback' => array($this, 'check_config'),
        	'html_callback' => array($this, 'googlecheckout_button'),
        	'process_callback' => array($this, 'process_payment')
        ));
	}

	public function googlecheckout_button($transaction_id) {
    	$api = wpbdp_payments_api();
    	$transaction = $api->get_transaction($transaction_id);

    	$html = '';

    	$item_name = '';
    	$item_description = '';
    	if ($transaction->payment_type == 'upgrade-to-sticky') {
    		$item_name = _x('Upgrade to featured listing.', 'googlecheckout', 'WPBDM');
    		$item_description = sprintf(_x('Payment for upgrading to featured listing "%s" with listing ID: %s.', 'googlecheckout', 'WPBDM'), get_the_title($transaction->listing_id), $transaction->listing_id);
    	} else {
    		$item_name = _x('Listing payment.', 'googlecheckout', 'WPBDM');
    		$item_description = sprintf(_x('Payment for listing "%s" with listing ID: %s.', 'paypal-module', 'WPBDM'), get_the_title($transaction->listing_id), $transaction->listing_id);
    	}
    	
    	$item_name = esc_attr($item_name);
		$item_description = esc_attr($item_description);

    	$url = $api->in_test_mode() ? sprintf('https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant/%s', wpbdp_get_option('googlecheckout-merchant')) :
    		    sprintf('https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/%s', wpbdp_get_option('googlecheckout-merchant'));

    	$html .= sprintf('<form action="%s" id="BB_BuyButtonForm" method="post" name="BB_BuyButtonForm" accept-charset="utf-8">',
    					 $url);
	
	   	$html .= sprintf('<input type="hidden" name="item_name_1" value="%s" />', $item_name);
    	$html .= sprintf('<input type="hidden" name="item_description_1" value="%s" />', $item_description);
    	$html .= sprintf('<input type="hidden" name="item_price_1" value="%s" />', number_format($transaction->amount, 2, '.', ''));
    	$html .= sprintf('<input type="hidden" name="item_currency_1" value="%s" />', wpbdp_get_option('currency'));
    	$html .= '<input type="hidden" name="item_quantity_1" value="1" />';
    	$html .= '<input type="hidden" name="shopping-cart.items.item-1.digital-content.display-disposition" value="OPTIMISTIC" />';
    	$html .= sprintf('<input type="hidden" name="shopping-cart.items.item-1.digital-content.description" value="%s" />',
    					 _x('Your listing has not been fully submitted yet. To complete the process you need to click the link below.', 'googlecheckout', 'WPBDM')
    					);
    	$html .= sprintf('<input type="hidden" name="shopping-cart.items.item-1.digital-content.url" value="%s" />',
    					 $api->get_processing_url('googlecheckout', $transaction));
    	$html .= '<input type="hidden" name="_charset_" value="utf-8" />';

    	$button_url = '';
    	if ($api->in_test_mode()) {
    		$button_url = sprintf('https://sandbox.google.com/checkout/buttons/buy.gif?merchant_id=%s&w=117&h=48&style=white&variant=text', wpbdp_get_option('googlecheckout-merchant'));
    	} else {
    		$button_url = sprintf('https://checkout.google.com/buttons/buy.gif?merchant_id=%s&w=117&h=48&style=white&variant=text', wpbdp_get_option('googlecheckout-merchant'));
    	}

    	$html .= sprintf('<input type="image" src="%s" alt="%s" />', $button_url, _x('Pay With Google Checkout', 'googlecheckout', 'WPBDM'));


		// 	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"shopping-cart.items.item-1.digital-content.key\" value=\"$wpbusdirmanlistingpostid\"/>";

    	$html .= '</form>';

		return $html;
	}

    public function process_payment($args) {
        $api = wpbdp_payments_api();

        if ($transaction = $api->get_transaction_from_uri_id()) {
            $transaction->gateway = 'googlecheckout';
            $transaction->processed_by = 'gateway';
            $transaction->processed_on = current_time('mysql');
            $transaction->status = 'approved';

            $api->save_transaction($transaction);            
            return $transaction->id;
        }

        return 0;
    }

    public function check_config() {
        $merchant = trim(wpbdp_get_option('googlecheckout-merchant'));

        $errors = array();
        
        if (empty($merchant))
            $errors[] = _x('"Merchant ID" is missing.', 'googlecheckout', 'WPBDM');
        
        return $errors;
    }

}

new WPBDP_GoogleCheckoutGateway();