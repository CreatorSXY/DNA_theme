<?php get_header(); ?>
<main class="page"><div class="container"><?php if (have_posts()) { while (have_posts()) { the_post(); the_content(); } } ?></div></main>
<?php get_footer(); ?>
