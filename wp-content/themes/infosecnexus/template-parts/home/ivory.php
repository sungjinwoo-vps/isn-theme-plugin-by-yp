<?php
/** Warm, source-backed InfoSecNexus editorial homepage. */
declare(strict_types=1);
use InfoSecNexus\Theme\Cinematic_Design as Content;
use InfoSecNexus\Theme\Ivory_Media as Media;
use InfoSecNexus\Theme\Header_Builder as Header;
use InfoSecNexus\Theme\Template_Tags as Tags;
get_header();
$blog_url = Header\page_url( 'blog' );
$topics = Content\topics();
$rolling = Tags\rolling_brief_post();
$brief_url = $rolling ? get_permalink( $rolling ) : $blog_url;
$brief_date = $rolling ? Tags\post_date_data( (int) $rolling->ID ) : null;
$excluded = $rolling ? array( (int) $rolling->ID ) : array();
$highlights = array();
foreach ( $topics as $topic ) {
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 12, 'category_name' => $topic['slug'], 'post__not_in' => $excluded, 'ignore_sticky_posts' => true ) );
	$selected = Content\unique_posts( $posts, 1, $excluded );
	$post = $selected[0] ?? null;
	$highlights[] = array( 'topic' => $topic, 'post' => $post );
	if ( $post ) { $excluded[] = (int) $post->ID; }
}
$recent = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 30, 'post__not_in' => $excluded, 'ignore_sticky_posts' => true ) );
$latest = Content\unique_posts( $recent, 3, $excluded );
$critical = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 16, 'category_name' => 'critical-cves', 'ignore_sticky_posts' => true ) );
$critical = Content\unique_posts( $critical, 3, $rolling ? array( (int) $rolling->ID ) : array() );
$arrow = static function (): void { Header\icon( 'arrow-right' ); };
?>
<main id="primary" class="site-main cinematic-home ivory-home">
	<section class="cg-hero cg-shell" aria-labelledby="cg-hero-title" data-cg-hero>
		<div class="cg-hero__art" aria-hidden="true" data-cg-parallax="0.45"><?php echo Media\image( 'hero', array( 'alt' => '', 'class' => 'cg-hero__image', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw' ) ); ?></div>
		<div class="cg-hero__copy">
			<p class="cg-eyebrow cg-hero-enter"><?php esc_html_e( 'Know more. Stay ahead.', 'infosecnexus' ); ?></p>
			<h1 id="cg-hero-title" class="cg-hero-enter">Know the risks.<br>Stay <span>one step ahead.</span></h1>
			<p class="cg-hero__subtitle cg-hero-enter"><?php esc_html_e( 'Clear cybersecurity intelligence for people who need more than another headline.', 'infosecnexus' ); ?></p>
			<div class="cg-actions cg-hero-enter"><a class="button cg-button" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Explore intelligence', 'infosecnexus' ); ?><?php $arrow(); ?></a><a class="cg-text-link" href="<?php echo esc_url( $brief_url ); ?>"><?php esc_html_e( 'Read the live brief', 'infosecnexus' ); ?></a></div>
			<div class="cg-hero__principles cg-hero-enter" aria-label="<?php esc_attr_e( 'Explore our coverage', 'infosecnexus' ); ?>"><a href="<?php echo esc_url( Header\category_url( 'cybersecurity' ) ); ?>"><?php Header\icon( 'shield' ); ?><?php esc_html_e( 'Source-backed stories', 'infosecnexus' ); ?></a><a href="<?php echo esc_url( Header\category_url( 'critical-cves' ) ); ?>"><?php Header\icon( 'layers' ); ?><?php esc_html_e( 'Focused CVE analysis', 'infosecnexus' ); ?></a><a href="<?php echo esc_url( Header\category_url( 'tutorials' ) ); ?>"><?php Header\icon( 'book' ); ?><?php esc_html_e( 'Practical guidance', 'infosecnexus' ); ?></a></div>
		</div>
		<?php if ( $rolling ) : ?><aside class="cg-hero__glass" data-cg-parallax="0.2" aria-label="<?php esc_attr_e( 'Current security briefing', 'infosecnexus' ); ?>"><span class="cg-hero__glass-icon" aria-hidden="true"><?php Header\icon( 'book' ); ?></span><p><?php esc_html_e( 'Up next', 'infosecnexus' ); ?></p><h2><?php esc_html_e( 'A clearer view.', 'infosecnexus' ); ?><br><?php esc_html_e( 'Better decisions.', 'infosecnexus' ); ?></h2><time datetime="<?php echo esc_attr( $brief_date['datetime'] ); ?>"><?php echo esc_html( $brief_date['label'] ); ?></time><a href="<?php echo esc_url( $brief_url ); ?>" aria-label="<?php esc_attr_e( 'Open the live brief', 'infosecnexus' ); ?>"><?php $arrow(); ?></a></aside><?php endif; ?>
	</section>
	<section class="cg-panel cg-highlights cg-shell" aria-labelledby="cg-highlights-title" data-reveal>
		<header class="cg-section-heading"><div><p class="cg-eyebrow"><?php esc_html_e( 'Security highlights', 'infosecnexus' ); ?></p><h2 id="cg-highlights-title"><?php esc_html_e( 'What needs your attention?', 'infosecnexus' ); ?></h2></div><a class="cg-pill-link" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'View all briefings', 'infosecnexus' ); ?><?php $arrow(); ?></a></header>
		<div class="cg-highlights__grid">
			<?php foreach ( $highlights as $highlight ) : $topic = $highlight['topic']; $post = $highlight['post']; ?>
				<article class="cg-highlight" data-reveal><a class="cg-highlight__link" href="<?php echo esc_url( $post ? get_permalink( $post ) : Header\category_url( $topic['slug'] ) ); ?>"><div class="cg-highlight__media"><?php echo Media\image( $topic['key'], array( 'alt' => '', 'sizes' => '(max-width: 600px) 44vw, 230px' ) ); ?></div><div class="cg-highlight__copy"><h3><?php echo esc_html( $topic['label'] ); ?></h3><p><?php echo esc_html( $post ? wp_trim_words( get_the_title( $post ), 11, '…' ) : 'Explore this security desk.' ); ?></p><span class="cg-circle-arrow" aria-hidden="true"><?php $arrow(); ?></span></div></a></article>
			<?php endforeach; ?>
		</div>
		<details class="iv-discovery"><summary><?php Header\icon( 'search' ); ?><?php esc_html_e( 'Find a specific briefing', 'infosecnexus' ); ?><span aria-hidden="true">+</span></summary>
			<form class="cg-discovery" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search" aria-label="<?php esc_attr_e( 'Filter security intelligence', 'infosecnexus' ); ?>">
				<label class="cg-discovery__field"><span><?php esc_html_e( 'Topic', 'infosecnexus' ); ?></span><select name="category_name"><option value=""><?php esc_html_e( 'All intelligence', 'infosecnexus' ); ?></option><?php foreach ( $topics as $topic ) : ?><option value="<?php echo esc_attr( $topic['slug'] ); ?>"><?php echo esc_html( $topic['label'] ); ?></option><?php endforeach; ?></select></label>
				<label class="cg-discovery__field"><span><?php esc_html_e( 'Priority', 'infosecnexus' ); ?></span><select name="isn_severity"><option value=""><?php esc_html_e( 'All levels', 'infosecnexus' ); ?></option><option value="critical">Critical</option><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option><option value="none">None (0.0)</option><option value="known-exploited">Known exploited</option></select></label>
				<label class="cg-discovery__field cg-discovery__query"><span><?php esc_html_e( 'Search articles', 'infosecnexus' ); ?></span><input name="s" type="search" placeholder="<?php esc_attr_e( 'CVE, product, or topic…', 'infosecnexus' ); ?>" aria-label="<?php esc_attr_e( 'Search published articles', 'infosecnexus' ); ?>"></label><button class="button cg-button" type="submit"><?php Header\icon( 'search' ); ?><?php esc_html_e( 'Search', 'infosecnexus' ); ?></button>
			</form>
		</details>
	</section>
	<section class="cg-panel cg-topics cg-shell" aria-labelledby="cg-topics-title" data-reveal>
		<header class="cg-section-heading"><div><p class="cg-eyebrow"><?php esc_html_e( 'Explore your security focus', 'infosecnexus' ); ?></p><h2 id="cg-topics-title"><?php esc_html_e( 'Security. Your way.', 'infosecnexus' ); ?></h2></div><p><?php esc_html_e( 'From everyday system hardening to emerging risks, find the context you need.', 'infosecnexus' ); ?></p></header>
		<div class="cg-topics__grid"><?php foreach ( array_slice( $topics, 1 ) as $topic ) : ?><a class="cg-topic" href="<?php echo esc_url( Header\category_url( $topic['slug'] ) ); ?>"><?php echo Media\icon_image( $topic['key'], array( 'class' => 'cg-topic__icon' ) ); ?><div><h3><?php echo esc_html( $topic['label'] ); ?></h3><p><?php echo esc_html( $topic['description'] ); ?></p></div><span class="cg-circle-arrow" aria-hidden="true"><?php $arrow(); ?></span></a><?php endforeach; ?></div>
	</section>
	<div class="cg-editorial-row cg-shell">
		<section class="cg-panel cg-featured" aria-labelledby="cg-featured-title" data-reveal><p class="cg-eyebrow"><?php esc_html_e( 'Featured briefing', 'infosecnexus' ); ?></p><div class="cg-featured__inner"><div class="cg-featured__media"><?php echo Media\image( 'critical', array( 'alt' => '', 'sizes' => '(max-width: 600px) 90vw, 400px' ) ); ?></div><div><h2 id="cg-featured-title"><?php echo esc_html( $rolling ? wp_trim_words( get_the_title( $rolling ), 12, '…' ) : 'Your next security briefing.' ); ?></h2><?php if ( $brief_date ) : ?><time datetime="<?php echo esc_attr( $brief_date['datetime'] ); ?>"><?php Header\icon( 'calendar' ); ?><?php echo esc_html( $brief_date['label'] ); ?></time><?php endif; ?><p><?php echo esc_html( $rolling ? Content\excerpt( $rolling, 23 ) : 'Explore published intelligence and practical security guidance.' ); ?></p><a class="button cg-button" href="<?php echo esc_url( $brief_url ); ?>"><?php esc_html_e( 'Read the briefing', 'infosecnexus' ); ?><?php $arrow(); ?></a></div></div></section>
		<section class="cg-panel cg-desk" aria-labelledby="cg-desk-title" data-reveal><p class="cg-eyebrow"><?php esc_html_e( 'Your daily security desk', 'infosecnexus' ); ?></p><h2 id="cg-desk-title"><?php esc_html_e( 'Security without', 'infosecnexus' ); ?><br><?php esc_html_e( 'the guesswork.', 'infosecnexus' ); ?></h2><ul class="cg-desk__principles"><li><?php Header\icon( 'layers' ); ?><?php esc_html_e( 'Source context', 'infosecnexus' ); ?></li><li><?php Header\icon( 'shield' ); ?><?php esc_html_e( 'Version details', 'infosecnexus' ); ?></li><li><?php Header\icon( 'book' ); ?><?php esc_html_e( 'Practical checks', 'infosecnexus' ); ?></li><li><?php Header\icon( 'calendar' ); ?><?php esc_html_e( 'Update history', 'infosecnexus' ); ?></li></ul><?php if ( $critical ) : ?><div class="cg-desk__alerts"><?php foreach ( $critical as $post ) : $priority = Content\priority( (int) $post->ID ); preg_match( '/\bCVE-\d{4}-\d{4,}\b/i', get_the_title( $post ), $cve ); ?><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><span><?php echo esc_html( $cve[0] ?? wp_trim_words( get_the_title( $post ), 5, '…' ) ); ?></span><span class="cg-priority<?php echo $priority['critical'] ? ' cg-priority--critical' : ''; ?>"><?php echo esc_html( $priority['label'] . ( '' !== $priority['score'] ? ' · ' . $priority['score'] : '' ) ); ?></span></a><?php endforeach; ?></div><?php endif; ?></section>
	</div>
	<div class="cg-latest-row cg-shell">
		<section class="cg-panel cg-latest" aria-labelledby="cg-latest-title" data-reveal><header class="cg-section-heading"><div><p class="cg-eyebrow"><?php esc_html_e( 'Latest intelligence', 'infosecnexus' ); ?></p><h2 id="cg-latest-title"><?php esc_html_e( 'Fresh context. Better decisions.', 'infosecnexus' ); ?></h2></div></header><div class="cg-latest__list"><?php foreach ( $latest as $post ) : $date = Tags\post_date_data( (int) $post->ID ); ?><article class="cg-latest__item"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><span class="iv-story-icon" aria-hidden="true"><?php Header\icon( 'book' ); ?></span><h3><?php echo esc_html( wp_trim_words( get_the_title( $post ), 13, '…' ) ); ?></h3><p><?php echo esc_html( Content\excerpt( $post, 17 ) ); ?></p><time datetime="<?php echo esc_attr( $date['datetime'] ); ?>"><?php echo esc_html( $date['label'] ); ?></time><span class="cg-circle-arrow" aria-hidden="true"><?php $arrow(); ?></span></a></article><?php endforeach; ?></div></section>
		<section class="cg-panel cg-newsletter" id="daily-cyber-brief" aria-labelledby="cg-newsletter-title" data-reveal><div class="iv-newsletter-art" aria-hidden="true"><?php echo Media\image( 'cloud', array( 'alt' => '', 'sizes' => '440px' ) ); ?></div><div class="iv-newsletter-copy"><h2 id="cg-newsletter-title"><?php esc_html_e( 'Your next briefing.', 'infosecnexus' ); ?><br><?php esc_html_e( 'One email away.', 'infosecnexus' ); ?></h2><p><?php esc_html_e( 'Security news, analysis and practical guidance, delivered to your inbox.', 'infosecnexus' ); ?></p><?php if ( class_exists( '\\InfoSecNexus\\Theme\\Toolkit\\Newsletter' ) && \InfoSecNexus\Theme\Toolkit\module_enabled( 'newsletter' ) ) : ?><?php \InfoSecNexus\Theme\Toolkit\Newsletter::render_form( false ); ?><?php else : ?><p class="cg-newsletter__unavailable"><?php esc_html_e( 'Subscriptions are currently unavailable. Please contact the editorial team.', 'infosecnexus' ); ?></p><?php endif; ?><p class="cg-newsletter__privacy"><?php esc_html_e( 'Confirm by email. Unsubscribe any time.', 'infosecnexus' ); ?> <a href="<?php echo esc_url( Header\page_url( 'privacy-policy' ) ); ?>"><?php esc_html_e( 'Privacy policy', 'infosecnexus' ); ?></a></p></div></section>
	</div>
	<section class="cg-panel cg-final-cta cg-shell" aria-labelledby="cg-final-title" data-reveal><div class="iv-final-art" aria-hidden="true"><?php echo Media\image( 'hero', array( 'alt' => '', 'sizes' => '1280px' ) ); ?></div><div class="iv-final-copy"><p class="cg-eyebrow"><?php esc_html_e( 'Let’s stay ahead', 'infosecnexus' ); ?></p><h2 id="cg-final-title"><?php esc_html_e( 'Ready for your next move?', 'infosecnexus' ); ?></h2><p><?php esc_html_e( 'Make your next security decision an informed one.', 'infosecnexus' ); ?></p><a class="button cg-button" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Explore intelligence', 'infosecnexus' ); ?><?php $arrow(); ?></a></div><a class="iv-final-note" href="<?php echo esc_url( $brief_url ); ?>"><?php Header\icon( 'book' ); ?><span><?php esc_html_e( 'New context.', 'infosecnexus' ); ?><br><strong><?php esc_html_e( 'Clearer decisions.', 'infosecnexus' ); ?></strong></span><?php $arrow(); ?></a></section>
</main>
<?php get_footer(); ?>
