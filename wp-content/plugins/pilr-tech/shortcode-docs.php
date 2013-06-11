<style>
	.shortcode_ul { list-style:outside; margin:0 0 0 20px; }
	.shortcode_ul li { margin-left:10px; }
</style>
<div class="wrap">
  <div id="icon-options-general" class="icon32"><br></div>
  <h2>Shortcode Usage</h2>
  <p>A <strong>shortcode</strong> is a WordPress-specific code that lets you do nifty things with very little effort. Shortcodes can embed files or create objects that 
  would normally require lots of complicated, ugly code in just one line. <strong>Shortcode</strong> = <strong>shortcut</strong>.</p>
  <fieldset class="metabox-holder">
	<div class="postbox">
	  <h3>Blog Info</h3>
	  <div class="inside">
		<p>Blog info alows you to insert the root url of your website. For Example:</p>
        <p><code>[bloginfo]</code> will display the root address i.e. http://example.com/ including the ending forward slash.</p>
        <p><code>[bloginfo]contact-us/</code> will take you to the conact us page. <strong>*Note</strong> there is no <code>/</code> in between the shortcode and the page slug.</p>
	  </div>
	</div>
  </fieldset>
  
  <fieldset class="metabox-holder">
	<div class="postbox">
	  <h3>Recent Posts</h3>
	  <div class="inside">
		<p>The recent posts shortcode allows you to show a certain number of posts from a specific category.</p>
        <h4>Syntax:</h4>
		<p><code>[posts type="post type" category="category slug" count="number of posts"]</code></p>
        <p>"type" -> The name of any custom post type but will by default be post unless otherwise specified.</p>
        <p>"category" -> The slug of the category you would like to show i.e. news, general, upcoming-events, breaking-news, sports etc. Defaults to "general".</p>
        <p>"count" -> The number of posts to show. This can be any number but don't recomend much more than 10. -1 will show all posts in that category. Defaults to 1.</p>
        <h4>Current post types:</h4>
        <ul class="shortcode_ul">
        <?php 
		$post_types = get_post_types( array("public" => true) );
		foreach($post_types as $type)
		{
			print("<li>".$type."</li>");
		}
		?>
        </ul>
	  </div>
	</div>
  </fieldset>
  
  <fieldset class="metabox-holder">
	<div class="postbox">
	  <h3>Horizontal Rules</h3>
	  <div class="inside">
		<p>Horizontal Rules will seperate different portions of content.</p>
		<p><code>[hr]</code> or <code>[hr_invisible]</code>.</p>
        <p>By adding a space and the word "top" before the ending bracket (<code>]</code>) it will insert a "Back to Top" link to the left of the HR.</p>
        <p><code>[hr top]</code></p>
	  </div>
	</div>
  </fieldset>
  
  <fieldset class="metabox-holder">
	<div class="postbox">
	  <h3>Buttons</h3>
	  <div class="inside">
		<p>Each theme provided by PILR will come with predefined button colors.</p>
        <h4>Syntax:</h4>
        <p><code>[button color]The button text[/button]</code></p>
        <p>To add a link to you button you simply select the button text and click the "Insert/Edit Link" icon in the toolbar.
		<h4>Colors available:</h4>
        <ul class="shortcode_ul">
        	<li></li>
        </ul>
      </div>
	</div>
  </fieldset>
  
  <fieldset class="metabox-holder">
	<div class="postbox">
	  <h3>Mini Slideshow</h3>
	  <div class="inside">
		<p>The minislide show by default will include <strong><em>all</em></strong> imagery included in the post/page media gallery.
        You can see what media is attached to the post/page in the "Gallery Images" secion below the post/page content.<br>
        <strong>*Note</strong> that you may remove media from the slider by clicking the red x at the bottom left of the image.
        To add images simply click the "Add Media" button at the top left of the post/page content editor.</p>
		<p><code>[hr]</code> or <code>[hr_invisible]</code>.</p>
        <p>By adding a space and the word "top" before the ending bracket (<code>]</code>) it will insert a "Back to Top" link to the left of the HR.</p>
        <p><code>[hr top]</code></p>
	  </div>
	</div>
  </fieldset>
</div>