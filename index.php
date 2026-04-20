<?php get_header(); ?>
<main class="page">
  <div class="container">
    <?php dna_render_rank_math_breadcrumbs(); ?>
    <h1 class="page-title">Design Insights</h1>
    <?php if (have_posts()) { while (have_posts()) { the_post(); the_content(); } } ?>
  </div>
</main>
<?php get_footer(); ?>
