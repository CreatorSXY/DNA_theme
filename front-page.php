<?php
get_header();

// Collect selected products (Customizer), fallback to latest 3 published products.
$product_ids = array_filter( array(
	absint( get_theme_mod( 'dna_home_product_1', 0 ) ),
	absint( get_theme_mod( 'dna_home_product_2', 0 ) ),
	absint( get_theme_mod( 'dna_home_product_3', 0 ) ),
) );

if ( count( $product_ids ) < 3 && function_exists( 'wc_get_products' ) ) {
	$fallback = wc_get_products( array(
		'status' => 'publish',
		'limit'  => 3,
		'return' => 'ids',
	) );

	$product_ids = array_values( array_unique( array_merge( $product_ids, $fallback ) ) );
	$product_ids = array_slice( $product_ids, 0, 3 );
}

$products = array();
if ( function_exists( 'wc_get_products' ) && ! empty( $product_ids ) ) {
	$products = wc_get_products( array(
		'include' => $product_ids,
		'orderby' => 'include',
		'limit'   => 3,
	) );
}
?>

<section class="hero-center">
	<h1 class="hero-title">
		ORDER.<br>
		BEAUTY.<br>
		PURITY.
	</h1>
	<p class="hero-sub">
		Clean, contemporary design objects and apparel — made with intention, built to last, and styled to disappear into your life.
	</p>
	<div class="hero-cta-stack">
		<div class="hero-cta">
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn-primary">Shop All</a>
			<a href="<?php echo esc_url( home_url( '/line/montessori/' ) ); ?>" class="btn-ghost">Montessori Line</a>
		</div>
		<a href="<?php echo esc_url( home_url( '/b2b/' ) ); ?>" class="hero-b2b">I WANT MY OWN DESIGNS</a>
	</div>
</section>

<section class="collection">
	<div class="collection-head">
		<span class="index">01 — MONTESSORI-INSPIRED</span>
		<a class="see-all" href="<?php echo esc_url( home_url( '/line/montessori/' ) ); ?>">
			SEE ALL <span class="arrow" aria-hidden="true">→</span>
		</a>
	</div>

	<div class="product-grid">
		<?php if ( ! empty( $products ) ) : ?>
			<?php foreach ( $products as $p ) : ?>
				<?php $pid = $p->get_id(); ?>
				<a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="product-card">
					<div class="product-media">
						<?php echo get_the_post_thumbnail( $pid, 'large' ); ?>
					</div>
					<div class="product-meta">
						<span class="product-title"><?php echo esc_html( $p->get_name() ); ?></span>
					</div>
				</a>
			<?php endforeach; ?>
		<?php else : ?>
			<!-- No products found -->
		<?php endif; ?>
	</div>
</section>

<section class="manifesto">
	<p>
		“We believe that the environment dictates the spirit.<br>
		Our designs are not just clothes, they are tools for a clearer mind.”
	</p>
</section>

<?php get_template_part('template-parts/site-footer'); ?>

<?php get_footer(); ?>
