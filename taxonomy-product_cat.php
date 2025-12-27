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

<main id="primary" class="site-main dna-line-term dna-line-term--<?php echo esc_attr(sanitize_title($term_slug)); ?>" data-dna-template="line-term-v12.6">
  <section class="dna-line-term__hero">
    <div class="dna-line-term__container">
      <h1 class="dna-line-term__title"><?php echo esc_html($title); ?></h1>

      <?php if ($desc_raw !== '') : ?>
        <div class="dna-line-term__body">
          <?php echo wp_kses_post($desc_raw); ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="dna-line-term__products">
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
                        ['class' => 'dna-line-term__img', 'loading' => 'lazy', 'decoding' => 'async']
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
