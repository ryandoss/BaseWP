<h3><?php echo $title; ?></h3>

<?php if (isset($message)): ?>
	<p><?php echo $message; ?></p>
<?php endif; ?>

<?php if ($payment_methods && $transaction): ?>
	<?php foreach ($payment_methods as $method): ?>
		<h4 class="paymentheader">
			<?php echo sprintf($item_text, wpbdp_get_option('currency-symbol') . $transaction->amount, $method->name); ?>
		</h4>
		<div class="paymentbuttondiv payment-gateway-<?php echo $method->id; ?>">
	        <?php echo call_user_func($method->html_callback, $transaction->id); ?>
		</div>
	<?php endforeach; ?>
<?php else: ?>
	<?php _ex('We can not process your payment at this moment. Please try again later.', 'templates', 'WPBDM'); ?>

	<?php if ($return_link): ?>
		<a href="<?php echo $return_link[0]; ?>"><?php echo $return_link[1]; ?></a>
	<?php endif; ?>	
<?php endif; ?>