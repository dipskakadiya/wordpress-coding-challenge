<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 *
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types( [ 'public' => true ] );
		$class_name = $attributes['className'];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;

		ob_start();

		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php esc_html_e( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
				<?php
				foreach ( $post_types as $post_type_slug ) :
					$post_type_object = get_post_type_object( $post_type_slug );
					$post_count       = wp_count_posts( $post_type_slug );
					?>
					<li>
						<?php
						// translators: 1: Post counts, 2: Post type label.
						echo sprintf( esc_html__( 'There are %1$d %2$s.', 'site-counts' ), esc_html( $post_count->publish ), esc_html( $post_type_object->labels->name ) );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! empty( $post_id ) ) { ?>
				<p>
					<?php
					// translators: 1: Post ID.
					echo sprintf( esc_html__( 'The current post ID is %d.', 'site-counts' ), esc_html( $post_id ) );
					?>
				</p>
			<?php } ?>
			<?php
			$query = new WP_Query(
				[
					'post_type'      => [ 'post', 'page' ],
					'post_status'    => 'any',
					'date_query'     => [
						[
							'hour'    => 9,
							'compare' => '>=',
						],
						[
							'hour'    => 17,
							'compare' => '<=',
						],
					],
					'tag'            => 'foo',
					'category_name'  => 'baz',
					'fields'         => 'ids',
					'posts_per_page' => 6,
				]
			);

			if ( $query->have_posts() ) :
				?>
				<h2><?php esc_html_e( '5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
					<?php
					$post_count = 0;
					foreach ( $query->posts as $post_id ) {
						if ( get_the_ID() === $post_id ) {
							continue;
						}
						$post_count ++;
						?>
						<li><?php echo wp_kses_post( get_the_title( $post_id ) ); ?></li>
						<?php
						if ( 5 === $post_count ) {
							break;
						}
					}
					?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
