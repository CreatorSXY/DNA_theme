<?php get_header(); ?>
<main class="page"><div class="container"><?php dna_render_rank_math_breadcrumbs(); ?><?php if (have_posts()) { while (have_posts()) { the_post(); ?><h1 class="page-title"><?php the_title(); ?></h1><?php the_content(); } } ?></div></main>
<?php get_footer(); ?>
