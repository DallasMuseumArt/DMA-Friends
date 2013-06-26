
<?php 
echo '<PRE>';
// die;
echo 'herro';
// global $wp_query; var_dump($wp_query);
// die; 
// global $wp_query;
// $secondary_query = $wp_query;
// var_dump($secondary_query);

// die;
?>
<?php while ( $secondary_query->have_posts() ) : $secondary_query->the_post(); ?>
1
<?php endwhile; ?>
