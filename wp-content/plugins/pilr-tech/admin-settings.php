  <div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
    <h2>PILR Admin</h2>
    <form method="post" action="options.php">
      <?php
      settings_fields( 'pilr_admin' );
	  ?>
      <fieldset class="metabox-holder">
        <div class="postbox">
          <?php do_settings_sections( 'pilr_admin' ); ?>
        </div>
        <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
      </fieldset>
    </form>
  </div>