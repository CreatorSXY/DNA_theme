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

$home_hero_image_id = function_exists( 'dna_get_theme_image_id' ) ? dna_get_theme_image_id( 'home_hero' ) : 0;
$home_hero_image_html = '';
if ( $home_hero_image_id && function_exists( 'dna_render_original_attachment_image' ) ) {
	$home_hero_image_html = dna_render_original_attachment_image(
		$home_hero_image_id,
		[
			'class'         => 'media-placeholder__img',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decoding'      => 'async',
			'alt'           => dna_attachment_alt_from_context( $home_hero_image_id, 'Home hero visual' ),
		]
	);
}
?>

<main id="primary" class="site-main home-main">
	<div class="home-first-screen">
		<section class="hero-center home-hero" data-dna-reveal data-dna-reveal-order="1">
			<div class="home-hero-plane media-placeholder media-placeholder--dark<?php echo $home_hero_image_html !== '' ? ' has-media' : ''; ?>" data-dna-depth aria-hidden="true">
				<?php if ( $home_hero_image_html !== '' ) : ?>
					<?php echo $home_hero_image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<span class="media-placeholder__label">Home Hero Visual Placeholder</span>
				<?php endif; ?>
			</div>
			<div class="home-hero-content">
				<h1 class="hero-seo-title">Merch Design &amp; Production Studio</h1>
				<h2 class="hero-title">
					ORDER.<br>
					BEAUTY.<br>
					PURITY.
				</h2>
				<div class="hero-cta-stack">
					<a href="<?php echo esc_url( home_url( '/b2b/' ) ); ?>" class="hero-b2b">I WANT MY OWN DESIGNS</a>
					<div class="hero-cta">
						<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn-primary">Shop All</a>
						<a href="<?php echo esc_url( home_url( '/line/montessori/' ) ); ?>" class="btn-ghost">Montessori Line</a>
					</div>
				</div>
			</div>
		</section>

		<section class="home-seo-sections" aria-label="Site pathways" data-dna-reveal data-dna-reveal-order="2">
			<div class="home-seo-grid">
				<article class="home-seo-card">
					<h2>B2B Services</h2>
					<p>Custom merch design, sampling, supplier coordination, and production supervision for brand teams.</p>
					<a href="<?php echo esc_url( home_url( '/b2b/' ) ); ?>">Explore B2B</a>
				</article>
				<article class="home-seo-card">
					<h2>Shop</h2>
					<p>Design-led apparel and objects, including Montessori-inspired products built for everyday use.</p>
					<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">Visit Shop</a>
				</article>
				<article class="home-seo-card">
					<h2>Case Study</h2>
					<p>See how concept, costing, and execution came together in a full production project.</p>
					<a href="<?php echo esc_url( home_url( '/case-study/' ) ); ?>">View Case Study</a>
				</article>
			</div>
		</section>
	</div>

		<section class="collection" data-dna-reveal data-dna-reveal-order="3">
		<div class="collection-head">
			<span class="index">01 — MONTESSORI-INSPIRED</span>
			<a class="see-all" href="<?php echo esc_url( home_url( '/line/montessori/' ) ); ?>">
				SEE ALL <span class="arrow" aria-hidden="true">→</span>
			</a>
		</div>

		<div class="product-grid">
			<?php if ( ! empty( $products ) ) : ?>
				<?php foreach ( $products as $p ) : ?>
					<?php
					$pid   = $p->get_id();
					$title = $p->get_name();
					?>
					<a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="product-card">
						<div class="product-media">
							<?php
							echo get_the_post_thumbnail(
								$pid,
								'large',
								[
									'alt' => esc_attr( dna_image_alt_from_context( $pid, $title . ' product image' ) ),
								]
							);
							?>
						</div>
						<div class="product-meta">
							<span class="product-title"><?php echo esc_html( $title ); ?></span>
						</div>
					</a>
				<?php endforeach; ?>
			<?php else : ?>
				<!-- No products found -->
			<?php endif; ?>
		</div>
	</section>

		<section class="manifesto" data-dna-reveal data-dna-reveal-order="4">
		<p>
			“We believe that the environment dictates the spirit.<br>
			Our designs are not just clothes, they are tools for a clearer mind.”
		</p>
	</section>
</main>

<?php get_template_part('template-parts/site-footer'); ?>

<?php get_footer(); ?>
