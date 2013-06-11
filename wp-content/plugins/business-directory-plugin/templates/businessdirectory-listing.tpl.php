<?php if ($is_sticky): ?>
    <?php echo $sticky_tag; ?>
<?php endif; ?>

<div class="listing-title">
    <h2 itemprop="name"><?php echo $title; ?></h2>
</div>

<?php if ($actions): ?>
    <?php echo $actions; ?>
<?php endif; ?>

<?php if ($main_image): ?>
    <div class="main-image"><?php echo $main_image; ?></div>
<?php endif; ?>

<div class="listing-details cf <?php if ($main_image): ?>with-image<?php endif; ?>">
    <?php echo $listing_fields; ?>
</div>

<?php if ($extra_images): ?>
<div class="extra-images">
    <ul>
    <?php foreach ($extra_images as $image): ?>
        <li><?php echo $image; ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>