</main>

<?php
/**
 * Footer — keep structural integrity 100% correct.
 * We keep the homepage ending block (manifesto) as the primary footer experience.
 */
?>

<?php if ( ! is_front_page() ) : ?>
  <?php get_template_part('template-parts/site-footer'); ?>
<?php endif; ?>

<?php
  // Global Contact popup (appears on every page)
  get_template_part('template-parts/contact-popup');
?>

<?php wp_footer(); ?>
</body>
</html>
