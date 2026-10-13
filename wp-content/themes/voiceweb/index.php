<?php
/**
 * Fallback template (blog index, archives, search, singular).
 *
 * The posts page uses home.php.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$has_posts = have_posts();
?>

<main class="site-inner" style="padding-top:32px;padding-bottom:32px">
	<?php if ( $has_posts ) { ?>
		<?php
		while ( $has_posts ) {
			the_post();
			?>
			<article <?php post_class( 'entry' ); ?>>
				<h2 class="entry-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>
				<?php the_excerpt(); ?>
			</article>
			<?php
			$has_posts = have_posts();
		}

		the_posts_pagination();
	} else {
		?>
		<p><?php echo esc_html( cn_translate( 'Nothing found.' ) ); ?></p>
	<?php } ?>
</main>

<?php
get_footer();
