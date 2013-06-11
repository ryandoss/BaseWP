<?php
$_transaction_types = array(
        'initial' => _x('Initial Payment', 'admin infometabox', 'WPBDM'),
        'edit' => _x('Listing Edit', 'admin infometabox', 'WPBDM'),
        'renewal' => _x('Listing Renewal', 'admin infometabox', 'WPBDM'),
        'upgrade-to-sticky' => _x('Upgrade to sticky', 'admin infometabox', 'WPBDM'),
);

?>
<div id="listing-metabox-transactions">
<strong><?php _ex('Transaction History', 'admin', 'WPBDM'); ?></strong>

<?php foreach ($transactions as $transaction): ?>
<div class="transaction">
    <div class="summary">
        <span class="handle"><a href="#" title="<?php _ex('Click for more details', 'admin infometabox', 'WPBDM'); ?>">+</a></span>
        <span class="date"><?php echo date_i18n(get_option('date_format'), strtotime($transaction->created_on)); ?></span>
        <span class="type"><?php echo $_transaction_types[$transaction->payment_type]; ?></span>
        <span class="status tag <?php echo $transaction->status;?> "><?php echo $transaction->status; ?></span>
    </div>
    <div class="details">
        <dl>
            <dt><?php echo _ex('Date', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $transaction->created_on; ?></dd>

            <dt><?php _ex('Amount', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo wpbdp_get_option('currency-symbol'); ?><?php echo $transaction->amount; ?></dd>

            <dt><?php _ex('Gateway', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $transaction->gateway ? $transaction->gateway : '--'; ?></dd>

            <dt><?php _ex('Payer Info', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd>
                Name: <span class="name"><?php echo wpbdp_getv($transaction->payerinfo, 'name', '--'); ?></span><br />
                Email: <span class="email"><?php echo wpbdp_getv($transaction->payerinfo, 'email', '--'); ?></span>
            </dd>

            <?php if ($transaction->processed_on): ?>
            <dt><?php _ex('Processed on', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $transaction->processed_on; ?></dd>
            <dt><?php _ex('Processed by', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $transaction->processed_by; ?></dd>
            <?php endif; ?>
        </dl>

        <?php if (!$transaction->processed_on): ?>
        <?php if (current_user_can('administrator')): ?>
        <p>
            <a href="<?php echo add_query_arg(array('wpbdmaction' => 'approvetransaction', 'transaction_id' => $transaction->id)); ?>" class="button-primary">
                <?php _ex('Approve payment', 'admin infometabox', 'WPBDM'); ?>
            </a>&nbsp;
            <a href="<?php echo add_query_arg(array('wpbdmaction' => 'rejecttransaction', 'transaction_id' => $transaction->id)); ?>" class="button">
                <?php _ex('Reject payment', 'admin infometabox', 'WPBDM'); ?>
            </a>
        </p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>