<?php
/**
 * Render template for blocks/example-block.
 *
 * @param array  $block      Block settings/attributes.
 * @param string $content    Inner block content (unused for this block).
 * @param bool   $is_preview True during block editor preview render.
 * @param int    $post_id    Post ID this block is rendered on.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading = get_field( 'heading' );
$body    = get_field( 'body' );
$icon    = get_field( 'icon' ); // Only populated if ACF FontAwesome is active.

$wrapper_classes = array( 'eb-example-block' );
if ( ! empty( $block['className'] ) ) {
	$wrapper_classes[] = $block['className'];
}

$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
?>
<div<?php echo $anchor; // phpcs:ignore ?> class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
	<?php if ( $icon && is_array( $icon ) && ! empty( $icon['svg'] ) ) : ?>
		<span class="eb-example-block__icon"><?php echo $icon['svg']; // phpcs:ignore -- ACF-provided markup ?></span>
	<?php endif; ?>

	<?php if ( $heading ) : ?>
		<h2 class="eb-example-block__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php elseif ( $is_preview ) : ?>
		<h2 class="eb-example-block__heading">Example Block Heading</h2>
	<?php endif; ?>

	<?php if ( $body ) : ?>
		<div class="eb-example-block__body"><?php echo wp_kses_post( $body ); ?></div>
	<?php endif; ?>
</div>
