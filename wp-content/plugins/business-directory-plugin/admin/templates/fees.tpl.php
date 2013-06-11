<?php
	echo wpbdp_admin_header(null, null, wpbdp_get_option('payments-on') ? array(
		array(_x('Add New Listing Fee', 'fees admin', 'WPBDM'), esc_url(add_query_arg('action', 'addfee'))),
	) : null);
?>
	<?php wpbdp_admin_notices(); ?>

	<?php if (!wpbdp_get_option('payments-on')): ?>
		<p><?php _ex('Payments are currently turned off. To manage fees you need to go to the Manage Options page and check the box next to \'Turn on payments\' under \'General Payment Settings\'', 'fees admin', 'WPBDM'); ?></p>
	<?php else: ?>

		<?php $table->views(); ?>
		<?php $table->display(); ?>

		<hr />
		<p>
			<b><?php _ex('Installed Payment Gateway Modules', 'WPBDM'); ?></b>
			<ul>
				<?php if (wpbdp_payments_api()->has_gateway('googlecheckout')): ?>
					<li style="background:url(<?php echo WPBDP_URL . 'resources/images/check.png'; ?>) no-repeat left center; padding-left:30px;">
						<?php _ex('Google Checkout', 'admin templates', 'WPBDM'); ?>
					</li>
				<?php endif; ?>
				<?php if (wpbdp_payments_api()->has_gateway('paypal')): ?>
					<li style="background:url(<?php echo WPBDP_URL . 'resources/images/check.png'; ?>) no-repeat left center; padding-left:30px;">
						<?php _ex('PayPal', 'admin templates', 'WPBDM'); ?>
					</li>
				<?php endif; ?>
				<?php if (wpbdp_payments_api()->has_gateway('2checkout')): ?>
					<li style="background:url(<?php echo WPBDP_URL . 'resources/images/check.png'; ?>) no-repeat left center; padding-left:30px;">
						<?php _ex('2Checkout', 'admin templates', 'WPBDM'); ?>
					</li>
				<?php endif; ?>
			</ul></p>

			<?php if (!wpbdp_payments_api()->has_gateway('googlecheckout') && !wpbdp_payments_api()->has_gateway('paypal') && !wpbdp_payments_api()->has_gateway('2checkout')): ?>
				<p><?php _ex("It does not appear you have any of the payment gateway modules installed. You need to purchase a payment gateway module in order to charge a fee for listings. To purchase payment gateways use the buttons below or visit", 'admin templates', "WPBDM"); ?></p>
				<p><a href="http://businessdirectoryplugin.com/premium-modules/">http://businessdirectoryplugin.com/premium-modules/</a></p>			
			<?php endif; ?>

			<?php if (!wpbdp_payments_api()->has_gateway('2checkout') || !wpbdp_payments_api()->has_gateway('paypal')): ?>
				<div style="width:100%;padding:10px;">
				<?php if (!wpbdp_payments_api()->has_gateway('paypal')): ?>
					<div style="float:left;width:22%;padding:10px;"><?php _ex("You can buy the PayPal gateway module to add PayPal as a payment option for your users.", 'admin templates', "WPBDM"); ?> <span style="display:block;color:red;padding:10px 0;font-size:22px;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/paypal-module/" style="color:green;"><?php _ex("$49.99", 'admin templates', "WPBDM"); ?></a></span></div>
				<?php endif; ?>

				<?php if (!wpbdp_payments_api()->has_gateway('2checkout')): ?>
					<div style="float:left;width:22%;padding:10px;"><?php _ex("You can buy the 2Checkout gateway module to add 2Checkout as a payment option for your users.","WPBDM"); ?> <span style="display:block;padding:10px 0;font-size:22px;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/2checkout-module/" style="color:green;"><?php _ex("$49.99", 'admin templates', "WPBDM"); ?></a></span></div>
				<?php endif; ?>

				</div><div style="clear: both;"></div>
			<?php endif; ?>

	<?php endif; ?>

<?php echo wpbdp_admin_footer(); ?>