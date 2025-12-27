<?php
/**
 * DNA — WooCommerce product archive (Shop / Category / Tag)
 * Path: wp-content/themes/dna/woocommerce/archive-product.php
 */
defined('ABSPATH') || exit;

get_header('shop');

// Remove default Woo "store UI" noise (only affects this template render)
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

if (is_product_category()) {
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

  $paged = max(1, (int)get_query_var('paged'));
  $total = (int)($GLOBALS['wp_query']->max_num_pages ?? 1);
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

        <?php if (have_posts()) : ?>
          <div class="dna-line-term__grid" role="list">
            <?php while (have_posts()) : the_post();
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
            <?php endwhile; ?>
          </div>

          <?php
            $pagination = paginate_links([
              'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
              'format'    => '?paged=%#%',
              'current'   => $paged,
              'total'     => $total,
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

  <?php
  get_footer('shop');
  return;
}


?>
<section class="dna-shop" aria-label="Shop">
  <header class="dna-shop__header">
    <div class="dna-shop__kicker">01 — MONTESSORI-INSPIRED</div>
    <h1 class="dna-shop__title">Collection</h1>
    <p class="dna-shop__desc">A small collection, intentionally designed.</p>
  </header>

  <?php if (woocommerce_product_loop()) : ?>
    <?php do_action('woocommerce_before_shop_loop'); ?>

    <div class="dna-shop__grid" role="list">
      <?php while (have_posts()) : the_post();
        $product = wc_get_product(get_the_ID());
        if (!$product) continue;

        $title = get_the_title();
        $permalink = get_permalink();
        $price_html = $product->get_price_html();
        $thumb_id = get_post_thumbnail_id(get_the_ID());
      ?>
        <article class="dna-card" role="listitem">
          <a class="dna-card__link" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($title); ?>">
            <div class="dna-card__media">
              <?php
                if ($thumb_id) {
                  // Use full-size source to avoid Woo's 300x300 thumbnails on archives.
                  // We'll also disable srcset on shop pages via functions.php to prevent the browser picking tiny variants.
                  echo wp_get_attachment_image(
                    $thumb_id,
                    'full',
                    false,
                    array(
                      'class' => 'dna-card__img',
                      'loading' => 'lazy',
                      'decoding' => 'async',
                      'alt' => esc_attr($title),
                      'sizes' => '520px',
                    )
                  );
                } else {
                  echo wc_placeholder_img('full');
                }
              ?>
            </div>

            <div class="dna-card__meta">
              <h2 class="dna-card__name"><?php echo esc_html($title); ?></h2>
              <?php if ($price_html) : ?>
                <div class="dna-card__price"><?php echo wp_kses_post($price_html); ?></div>
              <?php endif; ?>
            </div>
          </a>
        </article>
      <?php endwhile; ?>
    </div>

    <div class="dna-shop__footer">
      <?php woocommerce_pagination(); ?>
    </div>

    <?php do_action('woocommerce_after_shop_loop'); ?>
  <?php else : ?>
    <?php do_action('woocommerce_no_products_found'); ?>
  <?php endif; ?>
</section>
<?php
get_footer('shop');
