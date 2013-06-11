<?php

	global $lsPluginPath;

	$slider = get_option('layerslider-slides');
	$slider = is_array($slider) ? $slider : unserialize($slider);

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
												<td><input type="text" name="level" value="3"></td>
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

<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post" class="wrap" id="ls-slider-form">

	<input type="hidden" name="posted_add" value="1">

	<!-- Title -->
	<div class="ls-icon-layers"></div>
	<h2>
		<?php _e('Add new LayerSlider', 'LayerSlider') ?>
		<a href="?page=layerslider" class="add-new-h2"><?php _e('Back to the list', 'LayerSlider') ?></a>
	</h2>

	<!-- Main menu bar -->
	<div id="ls-main-nav-bar">
		<a href="#" class="settings active"><?php _e('Global Settings', 'LayerSlider') ?></a>
		<a href="#" class="layers"><?php _e('Layers', 'LayerSlider') ?></a>
		<a href="#" class="callbacks"><?php _e('Event Callbacks', 'LayerSlider') ?></a>
		<a href="#" class="support unselectable"><?php _e('Documentation', 'LayerSlider') ?></a>
		<a href="#" class="clear unselectable"></a>
	</div>

	<!-- Pages -->
	<div id="ls-pages">

		<!-- Global Settings -->
		<div class="ls-page ls-settings active">

			<div id="post-body-content">
				<div id="titlediv">
					<div id="titlewrap">
						<input type="text" name="title" value="" id="title" autocomplete="off" placeholder="<?php _e('Type your slider name here', 'LayerSlider') ?>">
					</div>
				</div>
			</div>

			<div class="ls-box ls-settings">
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
							<td><?php _e('Width', 'LayerSlider') ?></td>
							<td><input type="text" name="width" value="600" class="input"></td>
							<td class="desc">(px) <?php _e('The slider width in pixels. For compatibility reasons, we still support percentage values, but for responsive layout, you should use pixels.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Height', 'LayerSlider') ?></td>
							<td><input type="text" name="height" value="300" class="input"></td>
							<td class="desc">(px) <?php _e('The slider height in pixels.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Responsive', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="responsive" checked="checked"></td>
							<td class="desc"><?php _e('Enable this option to turn LayerSlider into a responsive slider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Full-width slider', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="forceresponsive"></td>
							<td class="desc"><?php _e('When you are using a responsiveness or percentage dimensions for the slider, it will respond the parent element size changes. With tis option you can bypass this behaviour and LayerSlider will be a full-width slider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Responsive under', 'LayerSlider') ?></td>
							<td><input type="text" name="responsiveunder" value="0"></td>
							<td class="desc">(px) <?php _e('You can force the slider to change automatically into responsive mode but only if the slider width is smaller than responsiveUnder pixels. It can be used if you need a full-width slider with fixed height but you also need it to be responsive if the browser is smaller... Important! If you enter a value higher than 0, the normal responsive mode will be switched off automatically!', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Sublayer Container', 'LayerSlider') ?></td>
							<td><input type="text" name="sublayercontainer" value="0"></td>
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
							<td><input type="checkbox" name="autostart" checked="checked"></td>
							<td class="desc"><?php _e('If enabled, slideshow will automatically start after loading the page.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Pause on hover', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="pauseonhover" checked="checked"></td>
							<td class="desc"><?php _e('Slideshow will pause when mouse pointer is over LayerSlider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('First layer', 'LayerSlider') ?></td>
							<td><input type="text" name="firstlayer" value="1" class="input"></td>
							<td class="desc"><?php _e('LayerSlider will start with this layer (you can type the word <i>random</i> if you want the slider to start with a random layer).', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Animate first layer', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="animatefirstlayer" checked="checked"></td>
							<td class="desc"><?php _e('If enabled, first layer will animate (slide in) instead of fading.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Random slideshow', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="randomslideshow"></td>
							<td class="desc"><?php _e("LayerSlider will change to a random layer instead of changing to the next / prev layer. Note that 'loops' feature won't work with this option.", "LayerSlider") ?></td>
						</tr>
						<tr>
							<td><?php _e('Two way slideshow', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="twowayslideshow" checked="checked"></td>
							<td class="desc"><?php _e('If enabled, slideshow will go backwards if you click the prev button.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Loops', 'LayerSlider') ?></td>
							<td>
								<select name="loops">
									<?php for($c = 0; $c < 11; $c++) : ?>
									<option><?php echo $c ?></option>
									<?php endfor; ?>
								</select>
							</td>
							<td class="desc"><?php _e('Number of loops if automatically start slideshow is enabled (0 means infinite!)', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Force the number of loops', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="forceloopnum" checked="checked"></td>
							<td class="desc"><?php _e('If enabled, the slider will always stop at the given number of loops even if the user restarts the slideshow.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Automatically play videos', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="autoplayvideos" checked="checked"></td>
							<td class="desc"><?php _e('If enabled, the slider will automatically play youtube and vimeo videos.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Automatically pause slideshow', 'LayerSlider') ?></td>
							<td>
								<select name="autopauseslideshow">
									<option value"auto"><?php _e('auto', 'LayerSlider') ?></option>
									<option value="enabled"><?php _e('enabled', 'LayerSlider') ?></option>
									<option value="disabled"><?php _e('disabled', 'LayerSlider') ?></option>
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
							<td><input type="checkbox" name="keybnav" checked="checked"></td>
							<td class="desc"><?php _e('You can navigate with the left and right arrow keys.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Touch navigation', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="touchnav" checked="checked"></td>
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
									<?php if($entry == 'defaultskin') { ?>
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
									<input type="text" name="backgroundcolor" value="transparent" class="input color">
									<div class="ls-colorpicker">colorpicker</div>
								</div>
							</td>
							<td class="desc"><?php _e('Background color of LayerSlider. You can use all CSS methods, like hexa colors, rgb(r,g,b) method, color names, etc. Note, that background sublayers are covering the background.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Background image', 'LayerSlider') ?></td>
							<td>
								<div class="reset-parent">
									<input type="text" name="backgroundimage" class="input ls-upload">
									<span class="ls-reset">x</span>
								</div>
							</td>
							<td class="desc"><?php _e('Background image of LayerSlider. This will be a fixed background image of LayerSlider by default. Note, that background sublayers are covering the global background image.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Slider style', 'LayerSlider') ?></td>
							<td>
								<div class="reset-parent">
									<input type="text" name="sliderstyle" class="input">
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
							<td><input type="checkbox" name="navprevnext" checked="checked"></td>
							<td class="desc"><?php _e('If disabled, Prev and Next buttons will be invisible.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Start and Stop buttons', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="navstartstop" checked="checked"></td>
							<td class="desc"><?php _e('If disabled, Start and Stop buttons will be invisible.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Navigation buttons', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="navbuttons" checked="checked"></td>
							<td class="desc"><?php _e('If disabled, slide buttons will be invisible.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Prev and next buttons on hover', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="hoverprevnext" checked="checked"></td>
							<td class="desc"><?php _e('If enabled, the prev and next buttons will be shown only if you move your mouse cursor over the slider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Bottom navigation on hover', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="hoverbottomnav"></td>
							<td class="desc"><?php _e('The bottom navigation controls (with also thumbnails) will be shown only if you move your mouse cursor over the slider.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail navigation', 'LayerSlider') ?></td>
							<td>
								<select name="thumb_nav">
									<option value="disabled"><?php _e('disabled', 'LayerSlider') ?></option>
									<option value="hover" selected="selected"><?php _e('hover', 'LayerSlider') ?></option>
									<option value="always"><?php _e('always', 'LayerSlider') ?></option>
								</select>
							</td>
							<td class="desc"></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail width', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_width" value="100"></td>
							<td class="desc"><?php _e('The width of the thumbnails in the navigation area.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail height', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_height" value="60"></td>
							<td class="desc"><?php _e('The height of the thumbnails in the navigation area.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail container width', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_container_width" value="60%"></td>
							<td class="desc"><?php _e('The width of the thumbnail navigation area.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail active opacity', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_active_opacity" value="35"></td>
							<td class="desc"><?php _e('The selected thumbnail opacity (0-100).', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Thumbnail inactive opacity', 'LayerSlider') ?></td>
							<td><input type="text" name="thumb_inactive_opacity" value="100"></td>
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
							<td><input type="checkbox" name="imgpreload" checked="checked"></td>
							<td class="desc"><?php _e('Preloads all images and background-images of the next layer.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('Use relative URLs', 'LayerSlider') ?></td>
							<td><input type="checkbox" name="relativeurls"></td>
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
									<input type="text" name="yourlogo" class="input ls-upload">
									<span class="ls-reset">x</span>
								</div>
							</td>
							<td class="desc"><?php _e('This is a fixed layer that will be shown above of LayerSlider container. For example if you want to display your own logo, etc., you can upload an image or choose one from the Media Library.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('YourLogo style', 'LayerSlider') ?></td>
							<td><input type="text" name="yourlogostyle" value="left: 10px; top: 10px;" class="input"></td>
							<td class="desc"><?php _e('You can style your logo. You can use any CSS properties, for example you can add left and top properties to place the image inside the LayerSlider container anywhere you want.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('YourLogo link', 'LayerSlider') ?></td>
							<td>
								<div class="reset-parent">
									<input type="text" name="yourlogolink" class="input">
									<span class="ls-reset">x</span>
								</div>
							</td>
							<td class="desc"><?php _e('You can add a link to your logo. Set false is you want to display only an image without a link.', 'LayerSlider') ?></td>
						</tr>
						<tr>
							<td><?php _e('YourLogo link target', 'LayerSlider') ?></td>
							<td>
								<select name="yourlogotarget">
									<option>_self</option>
									<option>_blank</option>
								</select>
							</td>
							<td class="desc"><?php _e("If '_blank', the clicked url will open in a new window.", "LayerSlider") ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Layers -->
		<div class="ls-page">

			<div id="ls-layer-tabs">
				<a href="#" class="active">Layer #1 <span>x</span></a>
				<a href="#" class="unsortable" id="ls-add-layer"><?php _e('Add new layer', 'LayerSlider') ?></a>
				<div class="unsortable clear"></div>
			</div>
			<div id="ls-layers">
				<div class="ls-box ls-layer-box active">
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
							<tr class="active">
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
													<input type="text" name="image"  class="<?php echo $uploadClass ?>" value="">
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
															<td><input type="text" name="level" value="3"></td>
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
		</div>

		<!-- Event Callbacks -->
		<div class="ls-page ls-callback-page">
			<div class="ls-box ls-callback-box">
				<h3 class="header">cbInit</h3>
				<div class="inner">
					<textarea name="cbinit" cols="20" rows="5">function(element) { }</textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbStart</h3>
				<div class="inner">
					<textarea name="cbstart" cols="20" rows="5">function(data) { }</textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box side">
				<h3 class="header">cbStop</h3>
				<div class="inner">
					<textarea name="cbstop" cols="20" rows="5">function(data) { }</textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbPause</h3>
				<div class="inner">
					<textarea name="cbpause" cols="20" rows="5">function(data) { }</textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbAnimStart</h3>
				<div class="inner">
					<textarea name="cbanimstart" cols="20" rows="5">function(data) { }</textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box side">
				<h3 class="header">cbAnimStop</h3>
				<div class="inner">
					<textarea name="cbanimstop" cols="20" rows="5">function(data) { }</textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbPrev</h3>
				<div class="inner">
					<textarea name="cbprev" cols="20" rows="5">function(data) { }</textarea>
				</div>
			</div>

			<div class="ls-box ls-callback-box">
				<h3 class="header">cbNext</h3>
				<div class="inner">
					<textarea name="cbnext" cols="20" rows="5">function(data) { }</textarea>
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