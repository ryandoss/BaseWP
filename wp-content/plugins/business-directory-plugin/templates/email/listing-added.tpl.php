<?php
	_ex( 'A new listing has been submitted to the directory. Listing details can be found below.', 'emails', 'WPBDM' );
?>

----

<?php _ex('ID', 'notify email', 'WPBDM'); ?>:
    <?php echo $id; ?>


<?php _ex('Title', 'notify email', 'WPBDM'); ?>:
    <?php echo $title; ?>


<?php if ( $url ): ?><?php _ex('URL', 'notify email', 'WPBDM'); ?>:
    <?php echo $url; ?><?php endif; ?>


<?php _ex('Categories', 'notify email', 'WPBDM'); ?>:
    <?php echo $categories; ?>


<?php _ex('Posted By', 'notify email', 'WPBDM'); ?>:
	<?php echo $user_name; ?> ( <?php echo $user_email; ?> ) 