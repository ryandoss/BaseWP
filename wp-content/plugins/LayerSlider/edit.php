<?php

	// Get WPDB Object
	global $wpdb;
	global $lsPluginPath;

	// Table name
	$table_name = $wpdb->prefix . "layerslider";

	// Get the IF of the slider
	$id = (int) $_GET['id'];

	// Get slider
	$slider = $wpdb->get_row("SELECT * FROM $table_name WHERE id = ".(int)$id." ORDER BY date_c DESC LIMIT 1" , ARRAY_A);
	$slider = json_decode($slider['data'], true);

	if(function_exists( 'wp_enqueue_media' )) {
		$uploadClass = 'ls-mass-upload';
	} else {
		$uploadClass = 'ls-upload';
	}
?>
<div id="ls-sample">
	<div class="ls-box ls-layer-box">
		<input type="hidden" name="layerkey" value="0">
		<table>
			<thead class="ls-layer-options-thead">
				<tr>
					<td colspan="7">
						<span id="ls-icon-layer-options"></span>
						<h4>
							<?php _e('Layer Options', 'LayerSlider') ?>
							<a href="#" class="duplicate ls-layer-duplicate"><?php _e('Duplicate this layer', 'LayerSlider') ?></a>
						</h4>
					</td>
				</tr>
			</thead>
			<tbody class="ls-slide-options">
				<tr>
					<td class="right"><?php _e('Slide options', 'LayerSlider') ?></td>
					<td class="right"><?php _e('Background', 'LayerSlider') ?></td>
					<td>
						<div class="reset-parent">
							<input type="text" name="background" class="ls-upload" value="">
							<span class="ls-reset">x</span>
						</div>
					</td>
					<td class="right"><?php _e('Direction', 'LayerSlider') ?></td>
					<td>
						<select name="slidedirection">
							<option value="top"><?php _e('top', 'LayerSlider') ?></option>
							<option value="right" selected="selecter"><?php _e('right', 'LayerSlider') ?></option>
							<option value="bottom"><?php _e('bottom', 'LayerSlider') ?></option>
							<option value="left"><?php _e('left', 'LayerSlider') ?></option>
						</select>
					</td>
					<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
					<td><input type="text" name="slidedelay" value="4000"> (ms)</td>
				</tr>
				<tr>
					<td class="right"><?php _e('Slide in animation', 'LayerSlider') ?></td>
					<td class="right"><?php _e('Duration', 'LayerSlider') ?></td>
					<td><input type="text" name="durationin" value="1500"> (ms)</td>
					<td class="right"><?php _e('Easing', 'LayerSlider') ?></td>
					<td>
						<select name="easingin">
							<option>linear</option>
							<option>swing</option>
							<option>easeInQuad</option>
							<option>easeOutQuad</option>
							<option>easeInOutQuad</option>
							<option>easeInCubic</option>
							<option>easeOutCubic</option>
							<option>easeInOutCubic</option>
							<option>easeInQuart</option>
							<option>easeOutQuart</option>
							<option>easeInOutQuart</option>
							<option>easeInQuint</option>
							<option>easeOutQuint</option>
							<option selected="selected">easeInOutQuint</option>
							<option>easeInSine</option>
							<option>easeOutSine</option>
							<option>easeInOutSine</option>
							<option>easeInExpo</option>
							<option>easeOutExpo</option>
							<option>easeInOutExpo</option>
							<option>easeInCirc</option>
							<option>easeOutCirc</option>
							<option>easeInOutCirc</option>
							<option>easeInElastic</option>
							<option>easeOutElastic</option>
							<option>easeInOutElastic</option>
							<option>easeInBack</option>
							<option>easeOutBack</option>
							<option>easeInOutBack</option>
							<option>easeInBounce</option>
							<option>easeOutBounce</option>
							<option>easeInOutBounce</option>
						</select>
					</td>
					<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
					<td><input type="text" name="delayin" value="0"> (ms)</td>
				</tr>
				<tr>
					<td class="right"><?php _e('Slide out animation', 'LayerSlider') ?></td>
					<td class="right"><?php _e('Duration', 'LayerSlider') ?></td>
					<td><input type="text" name="durationout" value="1500"> (ms)</td>
					<td class="right"><?php _e('Easing', 'LayerSlider') ?></td>
					<td>
						<select name="easingout">
							<option>linear</option>
							<option>swing</option>
							<option>easeInQuad</option>
							<option>easeOutQuad</option>
							<option>easeInOutQuad</option>
							<option>easeInCubic</option>
							<option>easeOutCubic</option>
							<option>easeInOutCubic</option>
							<option>easeInQuart</option>
							<option>easeOutQuart</option>
							<option>easeInOutQuart</option>
							<option>easeInQuint</option>
							<option>easeOutQuint</option>
							<option selected="selected">easeInOutQuint</option>
							<option>easeInSine</option>
							<option>easeOutSine</option>
							<option>easeInOutSine</option>
							<option>easeInExpo</option>
							<option>easeOutExpo</option>
							<option>easeInOutExpo</option>
							<option>easeInCirc</option>
							<option>easeOutCirc</option>
							<option>easeInOutCirc</option>
							<option>easeInElastic</option>
							<option>easeOutElastic</option>
							<option>easeInOutElastic</option>
							<option>easeInBack</option>
							<option>easeOutBack</option>
							<option>easeInOutBack</option>
							<option>easeInBounce</option>
							<option>easeOutBounce</option>
							<option>easeInOutBounce</option>
						</select>
					</td>
					<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
					<td><input type="text" name="delayout" value="0"> (ms)</td>
				</tr>
				<tr>
					<td class="right"><?php _e('Misc', 'LayerSlider') ?></td>
					<td class="right"><?php _e('#ID', 'LayerSlider') ?></td>
					<td><input type="text" name="id" value=""></td>
					<td class="right"><?php _e('Deeplink', 'LayerSlider') ?></td>
					<td><input type="text" name="deeplink"></td>
					<td class="right"><?php _e('Hidden', 'LayerSlider') ?></td>
					<td><input type="checkbox" name="skip" class="checkbox"></td>
				</tr>
				<tr>
					<td class="right"><?php _e('Navigation', 'LayerSlider') ?></td>
					<td class="right"><?php _e('Thumbnail', 'LayerSlider') ?></td>
					<td colspan="5">
						<div class="reset-parent">
							<input type="text" name="thumbnail" class="ls-upload" value="">
							<span class="ls-reset">x</span>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<table>
			<thead>
				<tr>
					<td>
						<span id="ls-icon-preview"></span>
						<h4><?php _e('Preview', 'LayerSlider') ?></h4>
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="ls-preview-td">
						<div class="ls-preview-wrapper">
							<div class="ls-preview">
								<div class="draggable"></div>
							</div>
							<div class="ls-real-time-preview"></div>
							<button class="button ls-preview-button"><?php _e('Enter Preview', 'LayerSlider') ?></button>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<table>
			<thead>
				<tr>
					<td>
						<span id="ls-icon-sublayers"></span>
						<h4><?php _e('Sublayers', 'LayerSlider') ?></h4>
					</td>
				</tr>
			</thead>
			<tbody class="ls-sublayers ls-sublayer-sortable">
				<tr>
					<td>
						<div class="ls-sublayer-wrapper">
							<span class="ls-sublayer-number">1</span>
							<span class="ls-highlight"><input type="checkbox"></span>
							<input type="text" name="subtitle" class="ls-sublayer-title" value="Sublayer #1">
							<div class="clear"></div>
							<div class="ls-sublayer-nav">
								<a href="#" class="active"><?php _e('Basic', 'LayerSlider') ?></a>
								<a href="#"><?php _e('Options', 'LayerSlider') ?></a>
								<a href="#"><?php _e('Link', 'LayerSlider') ?></a>
								<a href="#"><?php _e('Style', 'LayerSlider') ?></a>
								<a href="#"><?php _e('Attributes', 'LayerSlider') ?></a>
								<a href="#" title="<?php _e('Remove this sublayer', 'LayerSlider') ?>" class="remove">x</a>
							</div>
							<div class="ls-sublayer-pages">
								<div class="ls-sublayer-page ls-sublayer-basic active">
									<select name="type">
										<option selected="selected">img</option>
										<option>div</option>
										<option>p</option>
										<option>span</option>
										<option>h1</option>
										<option>h2</option>
										<option>h3</option>
										<option>h4</option>
										<option>h5</option>
										<option>h6</option>
									</select>

									<div class="ls-sublayer-types">
										<span class="ls-type">
											<span class="ls-icon-img"></span><br>
											<?php _e('Image', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-div"></span><br>
											<?php _e('Div', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-p"></span><br>
											<?php _e('Paragraph', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-span"></span><br>
											<?php _e('Span', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-h1"></span><br>
											<?php _e('H1', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-h2"></span><br>
											<?php _e('H2', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-h3"></span><br>
											<?php _e('H3', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-h4"></span><br>
											<?php _e('H4', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-h5"></span><br>
											<?php _e('H5', 'LayerSlider') ?>
										</span>

										<span class="ls-type">
											<span class="ls-icon-h6"></span><br>
											<?php _e('H6', 'LayerSlider') ?>
										</span>
									</div>

									<div class="ls-image-uploader">
										<img src="<?php echo plugins_url('/img/transparent.png', __FILE__) ?>" alt="sublayer image">
										<input type="text" name="image" class="<?php echo $uploadClass ?>" value="">
										<p>
											<?php _e('Click into this text field to open WordPress Media Library where you can upload new images or select previously used ones.', 'LayerSlider') ?>
										</p>
									</div>

									<div class="ls-html-code">
										<h5><?php _e('Custom HTML content', 'LayerSlider') ?></h5>
										<textarea name="html" cols="50" rows="5"></textarea>
									</div>
								</div>
								<div class="ls-sublayer-page ls-sublayer-options">
									<table>
										<tbody>
											<tr>
												<td><?php _e('Slide in animation', 'LayerSlider') ?></td>
												<td class="right"><?php _e('Direction', 'LayerSlider') ?></td>
												<td>
													<select name="slidedirection">
														<option value="auto"><?php _e('auto', 'LayerSlider') ?></option>
														<option value="fade"><?php _e('fade', 'LayerSlider') ?></option>
														<option value="top"><?php _e('top', 'LayerSlider') ?></option>
														<option value="right"><?php _e('right', 'LayerSlider') ?></option>
														<option value="bottom"><?php _e('bottom', 'LayerSlider') ?></option>
														<option value="left"><?php _e('left', 'LayerSlider') ?></option>
													</select>
												</td>
												<td class="right"><?php _e('Duration', 'LayerSlider') ?></td>
												<td><input type="text" name="durationin" value="1500"> (ms)</td>
												<td class="right"><?php _e('Easing', 'LayerSlider') ?></td>
												<td>
													<select name="easingin">
														<option>linear</option>
														<option>swing</option>
														<option>easeInQuad</option>
														<option>easeOutQuad</option>
														<option>easeInOutQuad</option>
														<option>easeInCubic</option>
														<option>easeOutCubic</option>
														<option>easeInOutCubic</option>
														<option>easeInQuart</option>
														<option>easeOutQuart</option>
														<option>easeInOutQuart</option>
														<option>easeInQuint</option>
														<option>easeOutQuint</option>
														<option selected="selected">easeInOutQuint</option>
														<option>easeInSine</option>
														<option>easeOutSine</option>
														<option>easeInOutSine</option>
														<option>easeInExpo</option>
														<option>easeOutExpo</option>
														<option>easeInOutExpo</option>
														<option>easeInCirc</option>
														<option>easeOutCirc</option>
														<option>easeInOutCirc</option>
														<option>easeInElastic</option>
														<option>easeOutElastic</option>
														<option>easeInOutElastic</option>
														<option>easeInBack</option>
														<option>easeOutBack</option>
														<option>easeInOutBack</option>
														<option>easeInBounce</option>
														<option>easeOutBounce</option>
														<option>easeInOutBounce</option>
													</select>
												</td>
												<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
												<td><input type="text" name="delayin" value="0"> (ms)</td>
											</tr>

											<tr>
												<td><?php _e('Slide out animation', 'LayerSlider') ?></td>
												<td class="right"><?php _e('Direction', 'LayerSlider') ?></td>
												<td>
													<select name="slideoutdirection">
														<option value="auto"><?php _e('auto', 'LayerSlider') ?></option>
														<option value="fade"><?php _e('fade', 'LayerSlider') ?></option>
														<option value="top"><?php _e('top', 'LayerSlider') ?></option>
														<option value="right"><?php _e('right', 'LayerSlider') ?></option>
														<option value="bottom"><?php _e('bottom', 'LayerSlider') ?></option>
														<option value="left"><?php _e('left', 'LayerSlider') ?></option>
													</select>
												</td>
												<td class="right"><?php _e('Duration', 'LayerSlider') ?></td>
												<td><input type="text" name="durationout" value="1500"> (ms)</td>
												<td class="right"><?php _e('Easing', 'LayerSlider') ?></td>
												<td>
													<select name="easingout">
														<option>linear</option>
														<option>swing</option>
														<option>easeInQuad</option>
														<option>easeOutQuad</option>
														<option>easeInOutQuad</option>
														<option>easeInCubic</option>
														<option>easeOutCubic</option>
														<option>easeInOutCubic</option>
														<option>easeInQuart</option>
														<option>easeOutQuart</option>
														<option>easeInOutQuart</option>
														<option>easeInQuint</option>
														<option>easeOutQuint</option>
														<option selected="selected">easeInOutQuint</option>
														<option>easeInSine</option>
														<option>easeOutSine</option>
														<option>easeInOutSine</option>
														<option>easeInExpo</option>
														<option>easeOutExpo</option>
														<option>easeInOutExpo</option>
														<option>easeInCirc</option>
														<option>easeOutCirc</option>
														<option>easeInOutCirc</option>
														<option>easeInElastic</option>
														<option>easeOutElastic</option>
														<option>easeInOutElastic</option>
														<option>easeInBack</option>
														<option>easeOutBack</option>
														<option>easeInOutBack</option>
														<option>easeInBounce</option>
														<option>easeOutBounce</option>
														<option>easeInOutBounce</option>
													</select>
												</td>
												<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
												<td><input type="text" name="delayout" value="0"> (ms)</td>
											</tr>

											<tr>
												<td><?php _e('Other options', 'LayerSlider') ?></td>
												<td class="right"><?php _e('P. Level', 'LayerSlider') ?></td>
												<td><input type="text" name="level" value="2"></td>
												<td class="right"><?php _e('Show until', 'LayerSlider') ?></td>
												<td><input type="text" name="showuntil" value="0"> (ms)</td>
												<td class="right"><?php _e('Hidden', 'LayerSlider') ?></td>
												<td><input type="checkbox" name="skip" class="checkbox"></td>
												<td colspan="3"><button class="button duplicate"><?php _e('Duplicate this sublayer', 'LayerSlider') ?></button></td>
											</tr>
									</table>
								</div>
								<div class="ls-sublayer-page ls-sublayer-link">
									<table>
										<tbody>
											<tr>
												<td><?php _e('URL', 'LayerSlider') ?></td>
												<td class="url"><input type="text" name="url" value=""></td>
												<td>
													<select name="target">
														<option>_self</option>
														<option>_blank</option>
													</select>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="ls-sublayer-page ls-sublayer-style">
									<table>
										<tbody>
											<tr>
												<td><?php _e('Basic style settings', 'LayerSlider') ?></td>
												<td class="right"><?php _e('Top', 'LayerSlider') ?></td>
												<td><input type="text" name="top"></td>
												<td class="right"><?php _e('Left', 'LayerSlider') ?></td>
												<td><input type="text" name="left"></td>
												<td class="right"><?php _e('Word-wrap', 'LayerSlider') ?></td>
												<td><input type="checkbox" name="wordwrap" class="checkbox"></td>
											</tr>
											<tr>
												<td><?php _e('Custom style settings', 'LayerSlider') ?></td>
												<td class="right"><?php _e('Custom styles', 'LayerSlider') ?></td>
												<td colspan="4"><textarea rows="5" cols="50" name="style" class="style"></textarea></td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="ls-sublayer-page ls-sublayer-attributes">
									<table>
										<tbody>
											<tr>
												<td><?php _e('Attributes', 'LayerSlider') ?></td>
												<td class="right"><?php _e('ID', 'LayerSlider') ?></td>
												<td><input type="text" name="id" value=""></td>
												<td class="right"><?php _e('Classes', 'LayerSlider') ?></td>
												<td><input type="text" name="class" value=""></td>
												<td class="right"><?php _e('Title', 'LayerSlider') ?></td>
												<td><input type="text" name="title" value=""></td>
												<td class="right"><?php _e('Alt', 'LayerSlider') ?></td>
												<td><input type="text" name="alt" value=""></td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<a href="#" class="ls-add-sublayer"><?php _e('Add new sublayer', 'LayerSlider') ?></a>
	</div>
</div>

<form action="<?php echo $_SERVER['REQUEST_URI']?>" method="post" class="wrap" id="ls-slider-form">

	<input type="hidden" name="posted_edit" value="1">

	<!-- Title -->
	<div class="ls-icon-layers"></div>
	<h2>
		<?php _e('Edit this LayerSlider', 'LayerSlider') ?>
		<a href="?page=layerslider" class="add-new-h2"><?php _e('Back to the list', 'LayerSlider') ?></a>
	</h2>

	<!-- Main menu bar -->
	<div id="ls-main-nav-bar">
		<a href="#" class="settings"><?php _e('Global Settings', 'LayerSlider') ?></a>
		<a href="#" class="layers active"><?php _e('Layers', 'LayerSlider') ?></a>
		<a href="#" class="callbacks"><?php _e('Event Callbacks', 'LayerSlider') ?></a>
		<a href="#" class="support unselectable"><?php _e('Documentation', 'LayerSlider') ?></a>
		<a href="#" class="clear unselectable"></a>
	</div>

	<!-- Pages -->
	<div id="ls-pages">

		<!-- Global Settings -->
		<div class="ls-page ls-settings">

			<div id="post-body-content">
				<div id="titlediv">
					<div id="titlewrap">
						<input type="text" name="title" value="<?php echo $slider['properties']['title'] ?>" id="title" autocomplete="off" placeholder="<?php _e('Type your slider name here', 'LayerSlider') ?>">
					</div>
				</div>
			</div>

			<div class="ls-box">
				<h3 class="header"><?php _e('Global Settings', 'LayerSlider') ?></h3>
				<table>
					<thead>
						<tr>
							<td colspan="3">
								<span id="ls-icon-basic"></span>
								<h4><?php _e('Basic', 'LayerSlider') ?></h4>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php _e('Slider width', 'LayerSlider') ?></td>
							<td><input type="text" name="width" value="<?php echo $slider['properties']['width'] ?>" class="input"></td>
							<td class="desc">(px) <?php _e('The slider width in pixels. For compatibility reasons, we still support percentage values, but for responsive layout, you should use pixels.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Slider height', 'LayerSlider') ?></td>
							<td><input type="text" name="height" value="<?php echo $slider['properties']['height'] ?>" class="input"></td>
							<td class="desc">(px) <?php _e('The slider height in pixels.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Responsive', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="responsive" <?php echo isset($slider['properties']['responsive']) ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('Enable this option to turn LayerSlider into a responsive slider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Full-width slider', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="forceresponsive" <?php echo ( isset($slider['properties']['forceresponsive']) && $slider['properties']['forceresponsive'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('When you are using a responsiveness or percentage dimensions for the slider, it will respond the parent element size changes. With tis option you can bypass this behaviour and LayerSlider will be a full-width slider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Responsive under', 'LayerSlider') ?></td>
							<td><input type="text" name="responsiveunder" value="<?php echo !empty($slider['properties']['responsiveunder']) ? $slider['properties']['responsiveunder'] : '0' ?>"></td>
							<td class="desc">(px) <?php _e('You can force the slider to change automatically into responsive mode but only if the slider width is smaller than responsiveUnder pixels. It can be used if you need a full-width slider with fixed height but you also need it to be responsive if the browser is smaller... Important! If you enter a value higher than 0, the normal responsive mode will be switched off automatically!', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Sublayer Container', 'LayerSlider') ?></td>
							<td><input type="text" name="sublayercontainer" value="<?php echo !empty($slider['properties']['sublayercontainer']) ? $slider['properties']['sublayercontainer'] : '0' ?>"></td>
							<td class="desc">(px) <?php _e('This feature is needed if you are using a full-width slider and you need that your sublayers forced to positioning inside a centered custom width container. Just specify the width of this container in pixels! Note, that this feature is working only with pixel-positioned sublayers, but of course if you add left: 50% position to a sublayer it will be positioned horizontally to the center, as before!', 'LayerSlider') ?></td>
						</tr>
					</tbody>
					<thead>
						<tr>
							<td colspan="3">
								<span id="ls-icon-slideshow"></span>
								<h4><?php _e('Slideshow', 'LayerSlider') ?></h4>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php _e('Automatically start slideshow', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="autostart" <?php echo ( isset($slider['properties']['autostart']) && $slider['properties']['autostart'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If enabled, slideshow will automatically start after loading the page.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Pause on hover', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="pauseonhover" <?php echo ( isset($slider['properties']['pauseonhover']) && $slider['properties']['pauseonhover'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('Slideshow will pause when mouse pointer is over LayerSlider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('First layer', 'LayerSlider') ?></td>
							<td><input type="text" name="firstlayer" value="<?php echo $slider['properties']['firstlayer'] ?>" class="input"></td>
							<td class="desc"><?php _e('LayerSlider will start with this layer (you can type the word <i>random</i> if you want the slider to start with a random layer).', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Animate first layer', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="animatefirstlayer" <?php echo ( isset($slider['properties']['animatefirstlayer']) && $slider['properties']['animatefirstlayer'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If enabled, first layer will animate (slide in) instead of fading.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Random slideshow', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="randomslideshow" <?php echo ( isset($slider['properties']['randomslideshow']) && $slider['properties']['randomslideshow'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e("LayerSlider will change to a random layer instead of changing to the next / prev layer. Note that 'loops' feature won't work with this option.", "LayerSlider") ?></td>
						</tr>
						<tr>
							<td><?php _e('Two way slideshow', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="twowayslideshow" <?php echo ( isset($slider['properties']['twowayslideshow']) && $slider['properties']['twowayslideshow'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If enabled, slideshow will go backwards if you click the prev button.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Loops', 'LayerSlider') ?></td>
							<td>
								<select name="loops">
									<?php for($c = 0; $c < 11; $c++) : ?>
									<?php if($slider['properties']['loops'] == $c) { ?>
									<option selected="selected"><?php echo $c ?></option>
									<?php } else {  ?>
									<option><?php echo $c ?></option>
									<?php } ?>
									<?php endfor; ?>
								</select>
							</td>
							<td class="desc"><?php _e('Number of loops if automatically start slideshow is enabled (0 means infinite!)', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Force the number of loops', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="forceloopnum" <?php echo ( isset($slider['properties']['forceloopnum']) && $slider['properties']['forceloopnum'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If enabled, the slider will always stop at the given number of loops even if the user restarts the slideshow.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Automatically play videos', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="autoplayvideos" <?php echo ( isset($slider['properties']['autoplayvideos']) && $slider['properties']['autoplayvideos'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If enabled, the slider will automatically play youtube and vimeo videos.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Automatically pause slideshow', 'LayerSlider') ?></td>
							<td>
								<select name="autopauseslideshow">
									<option <?php echo ($slider['properties']['autopauseslideshow'] == 'auto') ? 'selected="selected"' : '' ?>>auto</option>
									<option <?php echo ($slider['properties']['autopauseslideshow'] == 'enabled') ? 'selected="selected"' : '' ?>>enabled</option>
									<option <?php echo ($slider['properties']['autopauseslideshow'] == 'disabled') ? 'selected="selected"' : '' ?>>disabled</option>
								</select>
							</td>
							<td class="desc"><?php _e("If you enabled automatically play videos, the auto value means that the slideshow will stop UNTIL the video is playing and after that it continues. Enabled means slideshow will stop and it won't continue after video is played.", "LayerSlider") ?></td>
						</tr>
						<tr>
							<td><?php _e('Youtube preview', 'LayerSlider') ?></td>
							<td>
								<select name="youtubepreview">
									<option value="maxresdefault.jpg"><?php _e('Maximum quality', 'LayerSlider') ?></option>
									<option value="hqdefault.jpg"><?php _e('High quality', 'LayerSlider') ?></option>
									<option value="mqdefault.jpg"><?php _e('Medium quality', 'LayerSlider') ?></option>
									<option value="default.jpg"><?php _e('Default quality', 'LayerSlider') ?></option>
								</select>
							</td>
							<td class="desc"><?php _e('Default thumbnail picture of YouTube videos. Note, that Maximum quaility is not available to all (not HD) videos.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Keyboard navigation', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="keybnav" <?php echo ( isset($slider['properties']['keybnav']) && $slider['properties']['keybnav'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('You can navigate with the left and right arrow keys.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Touch navigation', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="touchnav" <?php echo ( isset($slider['properties']['touchnav']) && $slider['properties']['touchnav'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('Touch-control (on mobile devices).', 'LayerSlider') ?></td>
						</tr>
					</tbody>
					<thead>
						<tr>
							<td colspan="3">
								<span id="ls-icon-appearance"></span>
								<h4><?php _e('Appearance', 'LayerSlider') ?></h4>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php _e('Skin', 'LayerSlider') ?></td>
							<td>
								<select name="skin">
									<?php $files = scandir(dirname(__FILE__) . '/skins'); ?>
									<?php foreach($files as $entry) : ?>
									<?php if($entry == '.' || $entry == '..' || $entry == 'preview') continue; ?>
									<?php if($entry == $slider['properties']['skin']) { ?>
									<option selected="selected"><?php echo $entry ?></option>
									<?php } else { ?>
									<option><?php echo $entry ?></option>
									<?php } ?>
									<?php endforeach; ?>
								</select>
							</td>
							<td class="desc"><?php _e("You can change the skin of the slider. The 'noskin' skin is a border- and buttonless skin. Your custom skins will appear in the list when you create their folders as well.", "LayerSlider") ?></td>
						</tr>
						<tr>
							<td><?php _e('Background color', 'LayerSlider') ?></td>
							<td>
								<div class="reset-parent">
									<input type="text" name="backgroundcolor" value="<?php echo $slider['properties']['backgroundcolor'] ?>" class="input color">
									<div class="ls-colorpicker">colorpicker</div>
								</div>
							</td>
							<td class="desc"><?php _e('Background color of LayerSlider. You can use all CSS methods, like hexa colors, rgb(r,g,b) method, color names, etc. Note, that background sublayers are covering the background.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Background image', 'LayerSlider') ?></td>
							<td>
								<div class="reset-parent">
									<input type="text" name="backgroundimage" value="<?php echo $slider['properties']['backgroundimage'] ?>" class="input ls-upload">
									<span class="ls-reset">x</span>
								</div>
							</td>
							<td class="desc"><?php _e('Background image of LayerSlider. This will be a fixed background image of LayerSlider by default. Note, that background sublayers are covering the global background image.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Slider style', 'LayerSlider') ?></td>
							<td>
								<div class="reset-parent">
									<input type="text" name="sliderstyle" value="<?php echo isset($slider['properties']['sliderstyle']) ? $slider['properties']['sliderstyle'] : '' ?>" class="input">
									<span class="ls-reset">x</span>
								</div>
							</td>
							<td class="desc"><?php _e('Here you can apply your custom CSS style settings to the slider.', 'LayerSlider') ?></td>
						</tr>
					</tbody>
					<thead>
						<tr>
							<td colspan="3">
								<span id="ls-icon-nav"></span>
								<h4><?php _e('Navigation', 'LayerSlider') ?></h4>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php _e('Prev and Next buttons', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="navprevnext" <?php echo ( isset($slider['properties']['navprevnext']) && $slider['properties']['navprevnext'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If disabled, Prev and Next buttons will be invisible.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Start and Stop buttons', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="navstartstop" <?php echo ( isset($slider['properties']['navstartstop']) && $slider['properties']['navstartstop'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If disabled, Start and Stop buttons will be invisible.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Navigation buttons', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="navbuttons" <?php echo ( isset($slider['properties']['navbuttons']) && $slider['properties']['navbuttons'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If disabled, slide buttons will be invisible.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Prev and next buttons on hover', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="hoverprevnext" <?php echo ( isset($slider['properties']['hoverprevnext']) && $slider['properties']['hoverprevnext'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If enabled, the prev and next buttons will be shown only if you move your mouse cursor over the slider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Bottom navigation on hover', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="hoverbottomnav" <?php echo ( isset($slider['properties']['hoverbottomnav']) && $slider['properties']['hoverbottomnav'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('The bottom navigation controls (with also thumbnails) will be shown only if you move your mouse cursor over the slider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail navigation', 'LayerSlider') ?></td>
							<td>
								<?php $slider['properties']['thumb_nav'] = !empty($slider['properties']['thumb_nav']) ? $slider['properties']['thumb_nav'] : 'hover'; ?>
								<select name="thumb_nav">
									<option <?php echo ($slider['properties']['thumb_nav'] == 'disabled') ? 'selected="selected"' : '' ?>>disabled</option>
									<option <?php echo ($slider['properties']['thumb_nav'] == 'hover') ? 'selected="selected"' : '' ?>>hover</option>
									<option <?php echo ($slider['properties']['thumb_nav'] == 'always') ? 'selected="selected"' : '' ?>>always</option>
								</select>
							</td>
							<td class="desc"></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail width', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_width" value="<?php echo !empty($slider['properties']['thumb_width']) ? $slider['properties']['thumb_width'] : '100' ?>"></td>
							<td class="desc"><?php _e('The width of the thumbnails in the navigation area.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail height', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_height" value="<?php echo !empty($slider['properties']['thumb_height']) ? $slider['properties']['thumb_height'] : '60' ?>"></td>
							<td class="desc"><?php _e('The height of the thumbnails in the navigation area.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail container width', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_container_width" value="<?php echo !empty($slider['properties']['thumb_container_width']) ? $slider['properties']['thumb_container_width'] : '60%' ?>"></td>
							<td class="desc"><?php _e('The width of the thumbnail navigation area.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail active opacity', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_active_opacity" value="<?php echo !empty($slider['properties']['thumb_active_opacity']) ? $slider['properties']['thumb_active_opacity'] : '35' ?>"></td>
							<td class="desc"><?php _e('The selected thumbnail opacity (0-100).', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail inactive opacity', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_inactive_opacity" value="<?php echo !empty($slider['properties']['thumb_inactive_opacity']) ? $slider['properties']['thumb_inactive_opacity'] : '100' ?>"></td>
							<td class="desc"><?php _e('The opacity of inactive thumbnails (0-100).', 'LayerSlider') ?></td>
						</tr>
					</tbody>
					<thead>
						<tr>
							<td colspan="3">
								<span id="ls-icon-misc"></span>
								<h4><?php _e('Misc', 'LayerSlider') ?></h4>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php _e('Image preload', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="imgpreload" <?php echo ( isset($slider['properties']['imgpreload']) && $slider['properties']['imgpreload'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('Preloads all images and background-images of the next layer.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Use relative URLs', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="relativeurls" <?php echo ( isset($slider['properties']['relativeurls']) && $slider['properties']['relativeurls'] != 'false') ? 'checked="checked"' : '' ?>></td>
							<td class="desc"><?php _e('If enabled, LayerSlider WP will use relative URLs for images.', 'LayerSlider') ?></td>
						</tr>
					</tbody>
					<thead>
						<tr>
							<td colspan="3">
								<span id="ls-icon-yourlogo"></span>
								<h4><?php _e('YourLogo', 'LayerSlider') ?></h4>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php _e('YourLogo', 'LayerSlider') ?></td>
							<td>
								<div class="reset-parent">
									<input type="text" name="yourlogo" value="<?php echo $slider['properties']['yourlogo'] ?>" class="input ls-upload">
									<span class="ls-reset">x</span>
								</div>
							</td>
							<td class="desc"><?php _e('This is a fixed layer that will be shown above of LayerSlider container. For example if you want to display your own logo, etc., you can upload an image or choose one from the Media Library.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('YourLogo style', 'LayerSlider') ?></td>
							<td><input type="text" name="yourlogostyle" value="<?php echo $slider['properties']['yourlogostyle'] ?>" class="input"></td>
							<td class="desc"><?php _e('You can style your logo. You can use any CSS properties, for example you can add left and top properties to place the image inside the LayerSlider container anywhere you want.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('YourLogo link', 'LayerSlider') ?></td>
							<td>
								<div class="reset-parent">
									<input type="text" name="yourlogolink" value="<?php echo $slider['properties']['yourlogolink'] ?>" class="input">
									<span class="ls-reset">x</span>
								</div>
							</td>
							<td class="desc"><?php _e('You can add a link to your logo. Set false is you want to display only an image without a link.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('YourLogo link target', 'LayerSlider') ?></td>
							<td>
								<select name="yourlogotarget">
									<option <?php echo ($slider['properties']['yourlogotarget'] == '_self') ? 'selected="selected"' : '' ?>>_self</option>
									<option <?php echo ($slider['properties']['yourlogotarget'] == '_blank') ? 'selected="selected"' : '' ?>>_blank</option>
								</select>
							</td>
							<td class="desc"><?php _e("If '_blank', the clicked url will open in a new window.", "LayerSlider") ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Layers -->
		<div class="ls-page active">

			<div id="ls-layer-tabs">
				<?php foreach($slider['layers'] as $key => $layer) : ?>
				<?php $active = empty($key) ? 'active' : '' ?>
				<a href="#" class="<?php echo $active ?>">Layer #<?php echo ($key+1) ?><span>x</span></a>
				<?php endforeach; ?>
				<a href="#" class="unsortable" id="ls-add-layer"><?php _e('Add new layer', 'LayerSlider') ?></a>
				<div class="unsortable clear"></div>
			</div>
			<div id="ls-layers">
				<?php if(!empty($slider['layers'])) : ?>
				<?php foreach($slider['layers'] as $key => $layer) : ?>
				<?php $active = empty($key) ? 'active' : '' ?>
				<div class="ls-box ls-layer-box <?php echo $active ?>">
					<input type="hidden" name="layerkey" value="0">
					<table>
						<thead class="ls-layer-options-thead">
							<tr>
								<td colspan="7">
									<span id="ls-icon-layer-options"></span>
									<h4>
										<?php _e('Layer Options', 'LayerSlider') ?>
										<a href="#" class="duplicate ls-layer-duplicate"><?php _e('Duplicate this layer', 'LayerSlider') ?></a>
									</h4>
								</td>
							</tr>
						</thead>
						<tbody class="ls-slide-options">
							<tr>
								<td class="right"><?php _e('Slide options', 'LayerSlider') ?></td>
								<td class="right"><?php _e('Background', 'LayerSlider') ?></td>
								<td>
									<div class="reset-parent">
										<input type="text" name="background" class="ls-upload" value="<?php echo $layer['properties']['background']?>">
										<span class="ls-reset">x</span>
									</div>
								</td>
								<td class="right"><?php _e('Direction', 'LayerSlider') ?></td>
								<td>
									<select name="slidedirection">
										<option value="top" <?php echo ($layer['properties']['slidedirection'] == 'top') ? 'selected="selected"' : '' ?>><?php _e('top', 'LayerSlider') ?></option>
										<option value="right" <?php echo ($layer['properties']['slidedirection'] == 'right') ? 'selected="selected"' : '' ?>><?php _e('right', 'LayerSlider') ?></option>
										<option value="bottom" <?php echo ($layer['properties']['slidedirection'] == 'bottom') ? 'selected="selected"' : '' ?>><?php _e('bottom', 'LayerSlider') ?></option>
										<option value="left" <?php echo ($layer['properties']['slidedirection'] == 'left') ? 'selected="selected"' : '' ?>><?php _e('left', 'LayerSlider') ?></option>
									</select>
								</td>
								<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
								<td><input type="text" name="slidedelay" value="<?php echo $layer['properties']['slidedelay']?>"> (ms)</td>
							</tr>
							<tr>
								<td class="right"><?php _e('Slide in animation', 'LayerSlider') ?></td>
								<td class="right"><?php _e('Duration', 'LayerSlider') ?></td>
								<td><input type="text" name="durationin" value="<?php echo $layer['properties']['durationin']?>"> (ms)</td>
								<td class="right"><?php _e('Easing', 'LayerSlider') ?></td>
								<td>
									<select name="easingin">
										<option <?php echo ($layer['properties']['easingin'] == 'linear') ? 'selected="selected"' : '' ?>>linear</option>
										<option <?php echo ($layer['properties']['easingin'] == 'swing') ? 'selected="selected"' : '' ?>>swing</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInQuad') ? 'selected="selected"' : '' ?>>easeInQuad</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutQuad') ? 'selected="selected"' : '' ?>>easeOutQuad</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutQuad') ? 'selected="selected"' : '' ?>>easeInOutQuad</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInCubic') ? 'selected="selected"' : '' ?>>easeInCubic</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutCubic') ? 'selected="selected"' : '' ?>>easeOutCubic</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutCubic') ? 'selected="selected"' : '' ?>>easeInOutCubic</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInQuart') ? 'selected="selected"' : '' ?>>easeInQuart</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutQuart') ? 'selected="selected"' : '' ?>>easeOutQuart</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutQuart') ? 'selected="selected"' : '' ?>>easeInOutQuart</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInQuint') ? 'selected="selected"' : '' ?>>easeInQuint</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutQuint') ? 'selected="selected"' : '' ?>>easeOutQuint</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutQuint') ? 'selected="selected"' : '' ?>>easeInOutQuint</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInSine') ? 'selected="selected"' : '' ?>>easeInSine</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutSine') ? 'selected="selected"' : '' ?>>easeOutSine</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutSine') ? 'selected="selected"' : '' ?>>easeInOutSine</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInExpo') ? 'selected="selected"' : '' ?>>easeInExpo</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutExpo') ? 'selected="selected"' : '' ?>>easeOutExpo</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutExpo') ? 'selected="selected"' : '' ?>>easeInOutExpo</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInCirc') ? 'selected="selected"' : '' ?>>easeInCirc</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutCirc') ? 'selected="selected"' : '' ?>>easeOutCirc</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutCirc') ? 'selected="selected"' : '' ?>>easeInOutCirc</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInElastic') ? 'selected="selected"' : '' ?>>easeInElastic</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutElastic') ? 'selected="selected"' : '' ?>>easeOutElastic</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutElastic') ? 'selected="selected"' : '' ?>>easeInOutElastic</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInBack') ? 'selected="selected"' : '' ?>>easeInBack</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutBack') ? 'selected="selected"' : '' ?>>easeOutBack</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutBack') ? 'selected="selected"' : '' ?>>easeInOutBack</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInBounce') ? 'selected="selected"' : '' ?>>easeInBounce</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeOutBounce') ? 'selected="selected"' : '' ?>>easeOutBounce</option>
										<option <?php echo ($layer['properties']['easingin'] == 'easeInOutBounce') ? 'selected="selected"' : '' ?>>easeInOutBounce</option>
									</select>
								</td>
								<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
								<td><input type="text" name="delayin" value="<?php echo $layer['properties']['delayin']?>"> (ms)</td>
							</tr>
							<tr>
								<td class="right"><?php _e('Slide out animation', 'LayerSlider') ?></td>
								<td class="right"><?php _e('Duration', 'LayerSlider') ?></td>
								<td><input type="text" name="durationout" value="<?php echo $layer['properties']['durationout']?>"> (ms)</td>
								<td class="right"><?php _e('Easing', 'LayerSlider') ?></td>
								<td>
									<select name="easingout">
										<option <?php echo ($layer['properties']['easingout'] == 'linear') ? 'selected="selected"' : '' ?>>linear</option>
										<option <?php echo ($layer['properties']['easingout'] == 'swing') ? 'selected="selected"' : '' ?>>swing</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInQuad') ? 'selected="selected"' : '' ?>>easeInQuad</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutQuad') ? 'selected="selected"' : '' ?>>easeOutQuad</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutQuad') ? 'selected="selected"' : '' ?>>easeInOutQuad</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInCubic') ? 'selected="selected"' : '' ?>>easeInCubic</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutCubic') ? 'selected="selected"' : '' ?>>easeOutCubic</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutCubic') ? 'selected="selected"' : '' ?>>easeInOutCubic</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInQuart') ? 'selected="selected"' : '' ?>>easeInQuart</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutQuart') ? 'selected="selected"' : '' ?>>easeOutQuart</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutQuart') ? 'selected="selected"' : '' ?>>easeInOutQuart</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInQuint') ? 'selected="selected"' : '' ?>>easeInQuint</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutQuint') ? 'selected="selected"' : '' ?>>easeOutQuint</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutQuint') ? 'selected="selected"' : '' ?>>easeInOutQuint</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInSine') ? 'selected="selected"' : '' ?>>easeInSine</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutSine') ? 'selected="selected"' : '' ?>>easeOutSine</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutSine') ? 'selected="selected"' : '' ?>>easeInOutSine</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInExpo') ? 'selected="selected"' : '' ?>>easeInExpo</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutExpo') ? 'selected="selected"' : '' ?>>easeOutExpo</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutExpo') ? 'selected="selected"' : '' ?>>easeInOutExpo</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInCirc') ? 'selected="selected"' : '' ?>>easeInCirc</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutCirc') ? 'selected="selected"' : '' ?>>easeOutCirc</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutCirc') ? 'selected="selected"' : '' ?>>easeInOutCirc</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInElastic') ? 'selected="selected"' : '' ?>>easeInElastic</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutElastic') ? 'selected="selected"' : '' ?>>easeOutElastic</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutElastic') ? 'selected="selected"' : '' ?>>easeInOutElastic</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInBack') ? 'selected="selected"' : '' ?>>easeInBack</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutBack') ? 'selected="selected"' : '' ?>>easeOutBack</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutBack') ? 'selected="selected"' : '' ?>>easeInOutBack</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInBounce') ? 'selected="selected"' : '' ?>>easeInBounce</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeOutBounce') ? 'selected="selected"' : '' ?>>easeOutBounce</option>
										<option <?php echo ($layer['properties']['easingout'] == 'easeInOutBounce') ? 'selected="selected"' : '' ?>>easeInOutBounce</option>
									</select>
								</td>
								<td class="right"><?php _e('Delay', 'LayerSlider')  ?></td>
								<td><input type="text" name="delayout" value="<?php echo $layer['properties']['delayout']?>"> (ms)</td>
							</tr>
							<tr>
								<td class="right"><?php _e('Misc', 'LayerSlider') ?></td>
								<td class="right"><?php _e('#ID', 'LayerSlider') ?></td>
								<td><input type="text" name="id" value="<?php echo $layer['properties']['id'] ?>"></td>
								<td class="right"><?php _e('Deeplink', 'LayerSlider') ?></td>
								<td><input type="text" name="deeplink" value="<?php echo isset($layer['properties']['deeplink']) ? $layer['properties']['deeplink'] : '' ?>"></td>
								<td class="right"><?php _e('Hidden', 'LayerSlider') ?></td>
								<td><input type="checkbox" name="skip" class="checkbox" <?php echo isset($layer['properties']['skip']) ? 'checked="checked"' : '' ?>></td>
							</tr>
							<tr>
								<td class="right"><?php _e('Navigation', 'LayerSlider') ?></td>
								<td class="right"><?php _e('Thumbnail', 'LayerSlider') ?></td>
								<td colspan="5">
									<div class="reset-parent">
										<input type="text" name="thumbnail" class="ls-upload" value="<?php echo isset($layer['properties']['thumbnail']) ? $layer['properties']['thumbnail'] : '' ?>">
										<span class="ls-reset">x</span>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<table>
						<thead>
							<tr>
								<td>
									<span id="ls-icon-preview"></span>
									<h4><?php _e('Preview', 'LayerSlider') ?></h4>
								</td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="ls-preview-td">
									<div class="ls-preview-wrapper">
										<div class="ls-preview">
											<div class="draggable"></div>
										</div>
										<div class="ls-real-time-preview"></div>
										<button class="button ls-preview-button"><?php _e('Enter Preview', 'LayerSlider') ?></button>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<table>
						<thead>
							<tr>
								<td>
									<span id="ls-icon-sublayers"></span>
									<h4><?php _e('Sublayers', 'LayerSlider') ?></h4>
								</td>
							</tr>
						</thead>
						<tbody class="ls-sublayers ls-sublayer-sortable">
							<?php if(!empty($layer['sublayers'])) : ?>
							<?php foreach($layer['sublayers'] as $key => $sublayer) : ?>
							<?php $active = (count($layer['sublayers']) == ($key+1)) ? ' class="active"' : '' ?>
							<?php $title = empty($sublayer['subtitle']) ? 'Sublayer #'.($key+1).'' : htmlspecialchars(stripslashes($sublayer['subtitle'])); ?>
							<tr<?php echo $active ?>>
								<td>
									<div class="ls-sublayer-wrapper">
										<span class="ls-sublayer-number"><?php echo ($key + 1) ?></span>
										<span class="ls-highlight"><input type="checkbox"></span>
										<input type="text" name="subtitle" class="ls-sublayer-title" value="<?php echo $title ?>">
										<div class="clear"></div>
										<div class="ls-sublayer-nav">
											<a href="#" class="active"><?php _e('Basic', 'LayerSlider') ?></a>
											<a href="#"><?php _e('Options', 'LayerSlider') ?></a>
											<a href="#"><?php _e('Link', 'LayerSlider') ?></a>
											<a href="#"><?php _e('Style', 'LayerSlider') ?></a>
											<a href="#"><?php _e('Attributes', 'LayerSlider') ?></a>
											<a href="#" title="Remove this sublayer" class="remove">x</a>
										</div>
										<div class="ls-sublayer-pages">
											<div class="ls-sublayer-page ls-sublayer-basic active">
												<select name="type">
													<option <?php echo ($sublayer['type'] == 'img') ? 'selected="selected"' : '' ?>>img</option>
													<option <?php echo ($sublayer['type'] == 'div') ? 'selected="selected"' : '' ?>>div</option>
													<option <?php echo ($sublayer['type'] == 'p') ? 'selected="selected"' : '' ?>>p</option>
													<option <?php echo ($sublayer['type'] == 'span') ? 'selected="selected"' : '' ?>>span</option>
													<option <?php echo ($sublayer['type'] == 'h1') ? 'selected="selected"' : '' ?>>h1</option>
													<option <?php echo ($sublayer['type'] == 'h2') ? 'selected="selected"' : '' ?>>h2</option>
													<option <?php echo ($sublayer['type'] == 'h3') ? 'selected="selected"' : '' ?>>h3</option>
													<option <?php echo ($sublayer['type'] == 'h4') ? 'selected="selected"' : '' ?>>h4</option>
													<option <?php echo ($sublayer['type'] == 'h5') ? 'selected="selected"' : '' ?>>h5</option>
													<option <?php echo ($sublayer['type'] == 'h6') ? 'selected="selected"' : '' ?>>h6</option>
												</select>

												<div class="ls-sublayer-types">
													<span class="ls-type">
														<span class="ls-icon-img"></span><br>
														<?php _e('Image', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-div"></span><br>
														<?php _e('Div', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-p"></span><br>
														<?php _e('Paragraph', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-span"></span><br>
														<?php _e('Span', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-h1"></span><br>
														<?php _e('H1', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-h2"></span><br>
														<?php _e('H2', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-h3"></span><br>
														<?php _e('H3', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-h4"></span><br>
														<?php _e('H4', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-h5"></span><br>
														<?php _e('H5', 'LayerSlider') ?>
													</span>

													<span class="ls-type">
														<span class="ls-icon-h6"></span><br>
														<?php _e('H6', 'LayerSlider') ?>
													</span>
												</div>

												<div class="ls-image-uploader">
													<?php $imageSrc = !empty($sublayer['image']) ? $sublayer['image'] : plugins_url('/img/transparent.png', __FILE__) ?>
													<img src="<?php echo $imageSrc ?>" alt="sublayer image">
													<input type="text" name="image" class="<?php echo $uploadClass ?>" value="<?php echo $sublayer['image'] ?>">
													<p>
														<?php _e('Click into this text field to open WordPress Media Library where you can upload new images or select previously used ones.', 'LayerSlider') ?>
													</p>
												</div>

												<div class="ls-html-code">
													<h5><?php _e('Custom HTML content', 'LayerSlider') ?></h5>
													<textarea name="html" cols="50" rows="5"><?php echo stripslashes($sublayer['html']) ?></textarea>
												</div>
											</div>
											<div class="ls-sublayer-page ls-sublayer-options">
												<table>
													<tbody>
														<tr>
															<td><?php _e('Slide in animation', 'LayerSlider') ?></td>
															<td class="right"><?php _e('Direction', 'LayerSlider') ?></td>
															<td>
																<select name="slidedirection">
																	<option value="auto" <?php echo ($sublayer['slidedirection'] == 'auto') ? 'selected="selected"' : '' ?>><?php _e('auto', 'LayerSlider') ?></option>
																	<option value="fade" <?php echo ($sublayer['slidedirection'] == 'fade') ? 'selected="selected"' : '' ?>><?php _e('fade', 'LayerSlider') ?></option>
																	<option value="top" <?php echo ($sublayer['slidedirection'] == 'top') ? 'selected="selected"' : '' ?>><?php _e('top', 'LayerSlider') ?></option>
																	<option value="right" <?php echo ($sublayer['slidedirection'] == 'right') ? 'selected="selected"' : '' ?>><?php _e('right', 'LayerSlider') ?></option>
																	<option value="bottom" <?php echo ($sublayer['slidedirection'] == 'bottom') ? 'selected="selected"' : '' ?>><?php _e('bottom', 'LayerSlider') ?></option>
																	<option value="left" <?php echo ($sublayer['slidedirection'] == 'left') ? 'selected="selected"' : '' ?>><?php _e('left', 'LayerSlider') ?></option>
																</select>
															</td>
															<td class="right"><?php _e('Duration', 'LayerSlider') ?></td>
															<td><input type="text" name="durationin" value="<?php echo $sublayer['durationin'] ?>"> (ms)</td>
															<td class="right"><?php _e('Easing', 'LayerSlider') ?></td>
															<td>
																<select name="easingin">
																	<option <?php echo ($sublayer['easingin'] == 'linear') ? 'selected="selected"' : '' ?>>linear</option>
																	<option <?php echo ($sublayer['easingin'] == 'swing') ? 'selected="selected"' : '' ?>>swing</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInQuad') ? 'selected="selected"' : '' ?>>easeInQuad</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutQuad') ? 'selected="selected"' : '' ?>>easeOutQuad</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutQuad') ? 'selected="selected"' : '' ?>>easeInOutQuad</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInCubic') ? 'selected="selected"' : '' ?>>easeInCubic</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutCubic') ? 'selected="selected"' : '' ?>>easeOutCubic</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutCubic') ? 'selected="selected"' : '' ?>>easeInOutCubic</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInQuart') ? 'selected="selected"' : '' ?>>easeInQuart</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutQuart') ? 'selected="selected"' : '' ?>>easeOutQuart</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutQuart') ? 'selected="selected"' : '' ?>>easeInOutQuart</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInQuint') ? 'selected="selected"' : '' ?>>easeInQuint</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutQuint') ? 'selected="selected"' : '' ?>>easeOutQuint</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutQuint') ? 'selected="selected"' : '' ?>>easeInOutQuint</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInSine') ? 'selected="selected"' : '' ?>>easeInSine</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutSine') ? 'selected="selected"' : '' ?>>easeOutSine</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutSine') ? 'selected="selected"' : '' ?>>easeInOutSine</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInExpo') ? 'selected="selected"' : '' ?>>easeInExpo</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutExpo') ? 'selected="selected"' : '' ?>>easeOutExpo</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutExpo') ? 'selected="selected"' : '' ?>>easeInOutExpo</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInCirc') ? 'selected="selected"' : '' ?>>easeInCirc</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutCirc') ? 'selected="selected"' : '' ?>>easeOutCirc</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutCirc') ? 'selected="selected"' : '' ?>>easeInOutCirc</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInElastic') ? 'selected="selected"' : '' ?>>easeInElastic</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutElastic') ? 'selected="selected"' : '' ?>>easeOutElastic</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutElastic') ? 'selected="selected"' : '' ?>>easeInOutElastic</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInBack') ? 'selected="selected"' : '' ?>>easeInBack</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutBack') ? 'selected="selected"' : '' ?>>easeOutBack</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutBack') ? 'selected="selected"' : '' ?>>easeInOutBack</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInBounce') ? 'selected="selected"' : '' ?>>easeInBounce</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeOutBounce') ? 'selected="selected"' : '' ?>>easeOutBounce</option>
																	<option <?php echo ($sublayer['easingin'] == 'easeInOutBounce') ? 'selected="selected"' : '' ?>>easeInOutBounce</option>
																</select>
															</td>
															<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
															<td><input type="text" name="delayin" value="<?php echo $sublayer['delayin'] ?>"> (ms)</td>
														</tr>

														<tr>
															<td><?php _e('Slide out animation', 'LayerSlider') ?></td>
															<td class="right"><?php _e('Direction', 'LayerSlider') ?></td>
															<td>
																<select name="slideoutdirection">
																	<option value="auto" <?php echo ($sublayer['slideoutdirection'] == 'auto') ? 'selected="selected"' : '' ?>>auto</option>
																	<option value="fade" <?php echo ($sublayer['slideoutdirection'] == 'fade') ? 'selected="selected"' : '' ?>>fade</option>
																	<option value="top" <?php echo ($sublayer['slideoutdirection'] == 'top') ? 'selected="selected"' : '' ?>>top</option>
																	<option value="right" <?php echo ($sublayer['slideoutdirection'] == 'right') ? 'selected="selected"' : '' ?>>right</option>
																	<option value="bottom" <?php echo ($sublayer['slideoutdirection'] == 'bottom') ? 'selected="selected"' : '' ?>>bottom</option>
																	<option value="left" <?php echo ($sublayer['slideoutdirection'] == 'left') ? 'selected="selected"' : '' ?>>left</option>
																</select>
															</td>
															<td class="right"><?php _e('Duration', 'LayerSlider') ?></td>
															<td><input type="text" name="durationout" value="<?php echo $sublayer['durationout'] ?>"> (ms)</td>
															<td class="right"><?php _e('Easing', 'LayerSlider') ?></td>
															<td>
																<select name="easingout">
																	<option <?php echo ($sublayer['easingout'] == 'linear') ? 'selected="selected"' : '' ?>>linear</option>
																	<option <?php echo ($sublayer['easingout'] == 'swing') ? 'selected="selected"' : '' ?>>swing</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInQuad') ? 'selected="selected"' : '' ?>>easeInQuad</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutQuad') ? 'selected="selected"' : '' ?>>easeOutQuad</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutQuad') ? 'selected="selected"' : '' ?>>easeInOutQuad</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInCubic') ? 'selected="selected"' : '' ?>>easeInCubic</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutCubic') ? 'selected="selected"' : '' ?>>easeOutCubic</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutCubic') ? 'selected="selected"' : '' ?>>easeInOutCubic</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInQuart') ? 'selected="selected"' : '' ?>>easeInQuart</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutQuart') ? 'selected="selected"' : '' ?>>easeOutQuart</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutQuart') ? 'selected="selected"' : '' ?>>easeInOutQuart</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInQuint') ? 'selected="selected"' : '' ?>>easeInQuint</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutQuint') ? 'selected="selected"' : '' ?>>easeOutQuint</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutQuint') ? 'selected="selected"' : '' ?>>easeInOutQuint</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInSine') ? 'selected="selected"' : '' ?>>easeInSine</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutSine') ? 'selected="selected"' : '' ?>>easeOutSine</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutSine') ? 'selected="selected"' : '' ?>>easeInOutSine</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInExpo') ? 'selected="selected"' : '' ?>>easeInExpo</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutExpo') ? 'selected="selected"' : '' ?>>easeOutExpo</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutExpo') ? 'selected="selected"' : '' ?>>easeInOutExpo</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInCirc') ? 'selected="selected"' : '' ?>>easeInCirc</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutCirc') ? 'selected="selected"' : '' ?>>easeOutCirc</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutCirc') ? 'selected="selected"' : '' ?>>easeInOutCirc</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInElastic') ? 'selected="selected"' : '' ?>>easeInElastic</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutElastic') ? 'selected="selected"' : '' ?>>easeOutElastic</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutElastic') ? 'selected="selected"' : '' ?>>easeInOutElastic</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInBack') ? 'selected="selected"' : '' ?>>easeInBack</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutBack') ? 'selected="selected"' : '' ?>>easeOutBack</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutBack') ? 'selected="selected"' : '' ?>>easeInOutBack</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInBounce') ? 'selected="selected"' : '' ?>>easeInBounce</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeOutBounce') ? 'selected="selected"' : '' ?>>easeOutBounce</option>
																	<option <?php echo ($sublayer['easingout'] == 'easeInOutBounce') ? 'selected="selected"' : '' ?>>easeInOutBounce</option>
																</select>
															</td>
															<td class="right"><?php _e('Delay', 'LayerSlider') ?></td>
															<td><input type="text" name="delayout" value="<?php echo $sublayer['delayout'] ?>"> (ms)</td>
														</tr>

														<tr>
															<td><?php _e('Other options', 'LayerSlider') ?></td>
															<td class="right"><?php _e('P. Level', 'LayerSlider') ?></td>
															<td><input type="text" name="level" value="<?php echo $sublayer['level'] ?>"></td>
															<td class="right"><?php _e('Show until', 'LayerSlider') ?></td>
															<td><input type="text" name="showuntil" value="<?php echo !empty($sublayer['showuntil']) ? $sublayer['showuntil'] : '0'  ?>"> (ms)</td>
															<td class="right"><?php _e('Hidden', 'LayerSlider') ?></td>
															<td><input type="checkbox" name="skip" class="checkbox" <?php echo isset($sublayer['skip']) ? 'checked="checked"' : '' ?>></td>
															<td colspan="3"><button class="button duplicate"><?php _e('Duplicate this sublayer', 'LayerSlider') ?></button></td>
														</tr>
												</table>
											</div>
											<div class="ls-sublayer-page ls-sublayer-link">
												<table>
													<tbody>
														<tr>
															<td><?php _e('URL', 'LayerSlider') ?></td>
															<td class="url"><input type="text" name="url" value="<?php echo $sublayer['url'] ?>"></td>
															<td>
																<select name="target">
																	<option <?php echo ($sublayer['target'] == '_self') ? 'selected="selected"' : '' ?>>_self</option>
																	<option <?php echo ($sublayer['target'] == '_blank') ? 'selected="selected"' : '' ?>>_blank</option>
																</select>
															</td>
														</tr>
													</tbody>
												</table>
											</div>
											<div class="ls-sublayer-page ls-sublayer-style">
												<table>
													<tbody>
														<tr>
															<td><?php _e('Basic style settings', 'LayerSlider') ?></td>
															<td class="right"><?php _e('Top', 'LayerSlider') ?></td>
															<td><input type="text" name="top" value="<?php echo $sublayer['top'] ?>"></td>
															<td class="right"><?php _e('Left', 'LayerSlider') ?></td>
															<td><input type="text" name="left" value="<?php echo $sublayer['left'] ?>"></td>
															<td class="right"><?php _e('Word-wrap', 'LayerSlider') ?></td>
															<td><input type="checkbox" name="wordwrap" class="checkbox" <?php echo isset($sublayer['wordwrap']) ? 'checked="checked"' : '' ?>></td>
														</tr>
														<tr>
															<td><?php _e('Custom style settings', 'LayerSlider') ?></td>
															<td class="right"><?php _e('Custom styles', 'LayerSlider') ?></td>
															<td colspan="4"><textarea rows="5" cols="50" name="style" class="style"><?php echo stripslashes($sublayer['style']) ?></textarea></td>
														</tr>
													</tbody>
												</table>
											</div>
											<div class="ls-sublayer-page ls-sublayer-attributes">
												<table>
													<tbody>
														<tr>
															<td><?php _e('Attributes', 'LayerSlider') ?></td>
															<td class="right"><?php _e('ID', 'LayerSlider') ?></td>
															<td><input type="text" name="id" value="<?php echo $sublayer['id'] ?>"></td>
															<td class="right"><?php _e('Classes', 'LayerSlider') ?></td>
															<td><input type="text" name="class" value="<?php echo $sublayer['class'] ?>"></td>
															<td class="right"><?php _e('Title', 'LayerSlider') ?></td>
															<td><input type="text" name="title" value="<?php echo $sublayer['title'] ?>"></td>
															<td class="right"><?php _e('Alt', 'LayerSlider') ?></td>
															<td><input type="text" name="alt" value="<?php echo $sublayer['alt'] ?>"></td>
														</tr>
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
					<a href="#" class="ls-add-sublayer"><?php _e('Add new sublayer', 'LayerSlider') ?></a>
				</div>
				<?php endforeach; ?>
			<?php endif; ?>
			</div>
		</div>

		<!-- Event Callbacks -->
		<div class="ls-page ls-callback-page">
			<div class="ls-box ls-callback-box">
				<h3 class="header">cbInit</h3>
				<div class="inner">
					<textarea name="cbinit" cols="20" rows="5"><?php echo stripslashes($slider['properties']['cbinit']) ?></textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbStart</h3>
				<div class="inner">
					<textarea name="cbstart" cols="20" rows="5"><?php echo stripslashes($slider['properties']['cbstart']) ?></textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box side">
				<h3 class="header">cbStop</h3>
				<div class="inner">
					<textarea name="cbstop" cols="20" rows="5"><?php echo stripslashes($slider['properties']['cbstop']) ?></textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbPause</h3>
				<div class="inner">
					<textarea name="cbpause" cols="20" rows="5"><?php echo stripslashes($slider['properties']['cbpause']) ?></textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbAnimStart</h3>
				<div class="inner">
					<textarea name="cbanimstart" cols="20" rows="5"><?php echo stripslashes($slider['properties']['cbanimstart']) ?></textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box side">
				<h3 class="header">cbAnimStop</h3>
				<div class="inner">
					<textarea name="cbanimstop" cols="20" rows="5"><?php echo stripslashes($slider['properties']['cbanimstop']) ?></textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbPrev</h3>
				<div class="inner">
					<textarea name="cbprev" cols="20" rows="5"><?php echo stripslashes($slider['properties']['cbprev']) ?></textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbNext</h3>
				<div class="inner">
					<textarea name="cbnext" cols="20" rows="5"><?php echo stripslashes($slider['properties']['cbnext']) ?></textarea>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>

	<div class="ls-box ls-publish">
		<h3 class="header"><?php _e('Publish', 'LayerSlider') ?></h3>
		<div class="inner">
			<button class="button-primary"><?php _e('Save changes', 'LayerSlider') ?></button>
			<p class="ls-saving-warning"></p>
			<div class="clear"></div>
		</div>
	</div>
</form>


<script type="text/javascript">
	var pluginPath = '<?php echo $GLOBALS['lsPluginPath'] ?>';
	<?php if(function_exists( 'wp_enqueue_media' )) { ?>
	var newMediaUploader = true;
	<?php } else { ?>
	var newMediaUploader = false;
	<?php } ?>
</script>