<div class="achievement-template-part">
<?php if ( has_post_thumbnail( get_the_ID() ) ) : ?>
	<?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'thumbnail' ); ?>
	<img src="<?php echo $image[0]; ?>">
<?php endif; ?>
<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
</div>
