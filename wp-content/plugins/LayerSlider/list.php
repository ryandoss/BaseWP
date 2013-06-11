<?php

	// Get WPDB Object
	global $wpdb;

	// Table name
	$table_name = $wpdb->prefix . "layerslider";

	// Get sliders
	$sliders = $wpdb->get_results( "SELECT * FROM $table_name
										WHERE flag_hidden = '0' AND flag_deleted = '0'
										ORDER BY date_c ASC LIMIT 100" );

?>
<div class="wrap">
	<div class="ls-icon-layers"></div>
	<h2>
		<?php _e('LayerSlider sliders', 'LayerSlider') ?>
		<a href="?page=layerslider_add_new" class="add-new-h2"><?php _e('Add New', 'LayerSlider') ?></a>
		<a href="?page=layerslider&action=import_sample" class="add-new-h2"><?php _e('Import sample sliders', 'LayerSlider') ?></a>
	</h2>

	<div class="ls-box ls-slider-list">
		<table>
			<thead>
				<tr>
					<td>#</td>
					<td><?php _e('Name', 'LayerSlider') ?></td>
					<td><?php _e('Shortcode', 'LayerSlider') ?></td>
					<td><?php _e('Actions', 'LayerSlider') ?></td>
					<td><?php _e('Created', 'LayerSlider') ?></td>
					<td><?php _e('Modified', 'LayerSlider') ?></td>
				</tr>
			</thead>
			<tbody>
				<?php if(!empty($sliders)) : ?>
				<?php foreach($sliders as $key => $item) : ?>
				<?php $name = empty($item->name) ? 'Unnamed' : $item->name; ?>
				<tr>
					<td><?php echo ($key+1) ?></td>
					<td><a href="?page=layerslider&action=edit&id=<?php echo $item->id ?>"><?php echo $name ?></a></td>
					<td>[layerslider id="<?php echo $item->id ?>"]</td>
					<td>
						<a href="?page=layerslider&action=edit&id=<?php echo $item->id ?>"><?php _e('Edit', 'LayerSlider') ?></a> |
						<a href="?page=layerslider&action=duplicate&id=<?php echo $item->id ?>"><?php _e('Duplicate', 'LayerSlider') ?></a> |
						<a href="?page=layerslider&action=remove&id=<?php echo $item->id ?>" class="remove"><?php _e('Remove', 'LayerSlider') ?></a>
					</td>
					<td><?php echo date('M. d. Y.', $item->date_c) ?></td>
					<td><?php echo date('M. d. Y.', $item->date_m) ?></td>
				</tr>
				<?php endforeach; ?>
				<?php endif; ?>
				<?php if(empty($sliders)) : ?>
				<tr>
					<td colspan="6"><?php _e("You didn't create a slider yet.", "LayerSlider") ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post" class="ls-box ls-import-box">
		<h3 class="header"><?php _e('Import sliders', 'LayerSlider') ?></h3>
		<div class="inner">
			<textarea name="import" rows="10" cols="50"></textarea>
			<button class="button"><?php _e('Import', 'LayerSlider') ?></button>
		</div>
	</form>



	<?php

		// Array for export sliders data
		$export = array();

		// Get sliders data
		foreach($sliders as $item) {
			$export[] = json_decode($item->data, true);
		}
	?>
	<div class="ls-box ls-import-box">
		<h3 class="header"><?php _e('Export sliders', 'LayerSlider') ?></h3>
		<div class="inner">
			<textarea rows="10" cols="50" readonly="readonly"><?php echo base64_encode(json_encode($export)) ?></textarea>
			<p><?php _e('Place this export code into the import text field in your new site and press "Import".', 'LayerSlider') ?></p>
		</div>
	</div>
</div>

<!-- Help menu WP Pointer -->
<?php

// Get users data
global $current_user;
get_currentuserinfo();

if(get_user_meta($current_user->ID, 'layerslider_help_wp_pointer', true) != '1') {
add_user_meta($current_user->ID, 'layerslider_help_wp_pointer', '1'); ?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#contextual-help-link-wrap').pointer({
			pointerClass : 'ls-help-pointer',
			pointerWidth : 320,
			content: '<h3><?php _e('The documentation is here', 'LayerSlider') ?></h3><div class="inner"><?php _e('This is a WordPress contextual help menu, we use it to give you fast access to our documentation. Please keep in mind that because this menu is contextual, it only shows the relevant information to the page that you are currently viewing. So if you search something, you should visit the corresponding page first and then open this help menu.', 'LayerSlider') ?></div>',
			position: {
				edge : 'top',
				align : 'right'
			}
		}).pointer('open');
	});
</script>
<?php } ?>