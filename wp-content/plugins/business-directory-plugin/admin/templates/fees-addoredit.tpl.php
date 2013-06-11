<?php
if (!function_exists('_wpbdp_render_category')) {
function _wpbdp_render_category($cat, $selected=array(), $level=0) {
	$html = '';

	$level_string = str_repeat('&mdash;&nbsp;', $level);
	$html .= sprintf('<option value="%s" %s>%s%s</option>', $cat->term_id,
					 in_array($cat->term_id, $selected) ? 'selected="selected"' : '',
					 $level_string, $cat->name);

	if ($cat->subcategories) {
		foreach ($cat->subcategories as $subcat) {
			$html .= _wpbdp_render_category($subcat, $selected, $level+1);
		}
	}

	return $html;	
}
}
?>

<?php
	echo wpbdp_admin_header(_x('Add Listing Fee', 'fees admin', 'WPBDM'));
?>
<?php wpbdp_admin_notices(); ?>

<?php
$api = wpbdp_fees_api();

$post_values = isset($_POST['fee']) ? $_POST['fee'] : array();
$fee = isset($fee) ? $fee : null;
?>

<form id="wpbdp-fee-form" action="" method="POST">
	<?php if (isset($fee)): ?>
	<input type="hidden" name="fee[id]" value="<?php echo $fee->id; ?>" />
	<?php endif; ?>
	<table class="form-table">
		<tbody>
			<tr class="form-field form-required">
				<th scope="row">
					<label> <?php _ex('Fee Label', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<input name="fee[label]"
						   type="text"
						   aria-required="true"
						   value="<?php echo wpbdp_getv($post_values, 'label', $fee ? $fee->label : ''); ?>" />
				</td>
			</tr>
			<tr class="form-field form-required">
				<th scope="row">
					<label> <?php _ex('Fee Amount', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<input name="fee[amount]"
						   type="text"
						   aria-required="true"
						   value="<?php echo wpbdp_getv($post_values, 'amount', $fee ? $fee->amount : ''); ?>"
						   style="width: 100px;" />
				</td>
			</tr>	
			<tr class="form-required">
				<th scope="row">
					<label> <?php _ex('Listing run in days', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<?php
						$days = wpbdp_getv($post_values, 'days', $fee ? $fee->days : '');
					?>

					<input type="radio" id="wpbdp-fee-form-days" name="_days" value="1" <?php echo $days > 0 ? 'checked="checked"' : ''; ?>/> <label for="wpbdp-fee-form-days"><?php _ex('run listing for', 'fees admin', 'WPBDM'); ?></label>
					<input id="wpbdp-fee-form-days-n"
						   type="text"
						   aria-required="true"
						   value="<?php echo wpbdp_getv($post_values, 'days', $fee ? $fee->days : '0'); ?>"
						   style="width: 80px;"
						   name="fee[days]"
						   <?php echo wpbdp_getv($post_values, 'days', $fee ? $fee->days : 0) == 0 ? 'disabled="disabled"' : ''; ?>
						   />
					<?php _ex('days', 'fees admin', 'WPBDM'); ?>					
					<span class="description">-- or --</span>

					<input type="radio" id="wpbdp-fee-form-days-0" name="_days" value="0" <?php echo $days == 0 ? 'checked="checked"' : ''; ?>/> <label for="wpbdp-fee-form-days-0"><?php _ex('run listing forever', 'fees admin', 'WPBDM'); ?></label>					
				</td>
			</tr>
			<tr class="form-field form-required">
				<th scope="row">
					<label> <?php _ex('Number of images allowed', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<input name="fee[images]"
						   type="text"
						   aria-required="true"
						   value="<?php echo wpbdp_getv($post_values, 'images', $fee ? $fee->images : '0'); ?>"
						   style="width: 80px;" />
				</td>
			</tr>
			<?php
			$post_values_categories = wpbdp_getv(isset($post_values['categories']) ? $post_values['categories'] : array(), 'categories', $fee ? $fee->categories['categories'] : array());
			if ($fee && $fee->categories['all'] && !isset($post_values['categories']['categories'])) $post_values_categories[] = 0;
			?>
			<tr class="form-field form-required">
				<th scope="row">
					<label> <?php _ex('Apply to category', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<select name="fee[categories][categories][]" multiple="multiple" size="10">
						<option value="0" <?php echo in_array(0, $post_values_categories) || empty($post_values_categories) ? 'selected="selected"' : ''; ?>><?php _ex('* All Categories *', 'fees admin', 'WPBDM'); ?></option>
						<?php
						$directory_categories = wpbdp_categories_list();
						
						foreach ($directory_categories as &$dir_category) {
							echo _wpbdp_render_category($dir_category, $post_values_categories);
						}
						?>
					</select>
				</td>
			</tr>			
	</table>

	<?php if ($fee): ?>
		<?php echo submit_button(_x('Update Fee', 'fees admin', 'WPBDM')); ?>
	<?php else: ?>
		<?php echo submit_button(_x('Add Fee', 'fees admin', 'WPBDM')); ?>
	<?php endif; ?>
</form>

<?php
	echo wpbdp_admin_footer();
?>