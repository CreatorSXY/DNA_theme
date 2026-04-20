<?php
/**
 * taxonomy-product_cat.php
 *
 * DNA theme — Line term template for all WooCommerce product category archives.
 * /line/{category} renders here when product category base is set to /line.
 */

defined('ABSPATH') || exit;

get_header();

if (!class_exists('WooCommerce')) : ?>
  <main id="primary" class="site-main">
    <div class="container" style="padding:72px 22px;">
      <?php dna_render_rank_math_breadcrumbs(); ?>
      <h1><?php echo esc_html(get_the_archive_title()); ?></h1>
      <p><?php echo esc_html__('WooCommerce is not active.', 'dna'); ?></p>
    </div>
  </main>
<?php
  get_footer();
  return;
endif;

$term = get_queried_object();
$term_name = ($term instanceof WP_Term && !empty($term->name)) ? (string)$term->name : '';
$term_slug = ($term instanceof WP_Term && !empty($term->slug)) ? (string)$term->slug : 'line';

$title = trim($term_name) !== '' ? $term_name : single_term_title('', false);

$desc_raw = '';
if ($term instanceof WP_Term) {
  $desc_raw = (string)term_description($term, 'product_cat');
}
$desc_raw = trim($desc_raw);
if ($desc_raw !== '' && stripos($desc_raw, '<p') === false) {
  $desc_raw = wpautop($desc_raw);
}

$per_page = 24;
$paged = max(1, (int)get_query_var('paged'));
$line_hero_image_id = function_exists( 'dna_get_theme_image_id' ) ? dna_get_theme_image_id( 'line_hero' ) : 0;
$line_hero_image_html = '';
if ( $line_hero_image_id && function_exists( 'dna_render_original_attachment_image' ) ) {
  $line_hero_image_html = dna_render_original_attachment_image(
    $line_hero_image_id,
    [
      'class'    => 'media-placeholder__img',
      'loading'  => 'eager',
      'decoding' => 'async',
      'alt'      => dna_attachment_alt_from_context( $line_hero_image_id, 'Line hero visual' ),
    ]
  );
}

$q = new WP_Query([
  'post_type'           => 'product',
  'post_status'         => 'publish',
  'posts_per_page'      => $per_page,
  'paged'               => $paged,
  'ignore_sticky_posts' => true,
  'tax_query'           => [
    [
      'taxonomy' => 'product_cat',
      'field'    => 'term_id',
      'terms'    => ($term instanceof WP_Term) ? [(int)$term->term_id] : [],
    ],
  ],
  'orderby' => 'date',
  'order'   => 'DESC',
]);
?>

<main id="primary" class="site-main dna-line-term dna-line-term--<?php echo esc_attr(sanitize_title($term_slug)); ?>" data-dna-template="line-term-v19.4">
  <?php dna_render_rank_math_breadcrumbs(); ?>
  <section class="dna-line-term__hero" data-dna-reveal data-dna-reveal-order="1">
    <div class="dna-line-term__container">
      <h1 class="dna-line-term__title"><?php echo esc_html($title); ?></h1>
      <div class="dna-line-term__hero-visual media-placeholder media-placeholder--light<?php echo $line_hero_image_html !== '' ? ' has-media' : ''; ?>" data-dna-depth aria-hidden="true">
        <?php if ( $line_hero_image_html !== '' ) : ?>
          <?php echo $line_hero_image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php else : ?>
          <span class="media-placeholder__label">Line Visual Placeholder</span>
        <?php endif; ?>
      </div>

      <?php if ($desc_raw !== '') : ?>
        <div class="dna-line-term__body">
          <?php echo wp_kses_post($desc_raw); ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="dna-line-term__products" data-dna-reveal data-dna-reveal-order="2">
    <div class="dna-line-term__container">
      <h2 class="dna-line-term__subtitle">Products</h2>

      <?php if ($q->have_posts()) : ?>
        <div class="dna-line-term__grid" role="list">
          <?php while ($q->have_posts()) : $q->the_post();
            $pid  = get_the_ID();
            $link = get_permalink($pid);
            $name = get_the_title($pid);
          ?>
            <article class="dna-line-term__card" role="listitem">
              <a class="dna-line-term__cardlink" href="<?php echo esc_url($link); ?>" aria-label="<?php echo esc_attr($name); ?>">
                <div class="dna-line-term__media">
                  <?php if (has_post_thumbnail($pid)) : ?>
                    <?php
                      echo get_the_post_thumbnail(
                        $pid,
                        'large',
                        [
                          'class' => 'dna-line-term__img',
                          'loading' => 'lazy',
                          'decoding' => 'async',
                          'alt' => esc_attr(dna_image_alt_from_context($pid, $name . ' category product image')),
                        ]
                      );
                    ?>
                  <?php else : ?>
                    <div class="dna-line-term__placeholder" aria-hidden="true"></div>
                  <?php endif; ?>
                </div>
                <h3 class="dna-line-term__name"><?php echo esc_html($name); ?></h3>
              </a>
            </article>
          <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php
          $pagination = paginate_links([
            'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format'    => '?paged=%#%',
            'current'   => $paged,
            'total'     => (int)$q->max_num_pages,
            'type'      => 'list',
            'prev_text' => 'PREV',
            'next_text' => 'NEXT',
          ]);
        ?>

        <?php if ($pagination) : ?>
          <nav class="dna-line-term__pagination" aria-label="Pagination">
            <?php echo wp_kses_post($pagination); ?>
          </nav>
        <?php endif; ?>
      <?php else : ?>
        <p class="dna-line-term__empty">Now in development. Looking forward to meeting with you.</p>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>
