<?php

/**
 * Kicksite Calendar Block
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 *          This is either the post ID currently being displayed inside a query loop,
 *          or the post ID of the post hosting this block.
 * @param   array $context The context provided to the block by the post or its parent block.
 */

$block_name = 'kicksite-calendar';

/**
 * Get the section classes.
 * Create class attribute allowing for custom "className" and "align" values.
 */

// Theme class names
$class_name = $block_name;
if ( ! empty( $block[ 'className' ] ) ) {
  $class_name .= ' ' . $block[ 'className' ];
}
if ( ! empty( $block['align'] ) ) {
  $class_name .= ' align' . $block['align'];
} else {
  $class_name .= ' alignfull';
}

/**
 * Custom colors and override variables
 */
// Custom colors set in block appearance settings
$section_styles = [];

if ( ! empty( $block['style']['color']['text']) ) {
  $section_styles[] = "color: " . $block['style']['color']['text'];
} else if ( ! empty( $block['textColor'] ) ) {
	$section_styles[] = 'color: var(--wp--preset--color--' . $block['textColor'] . ')';
}

if ( ! empty( $block['style']['color']['background'] ) ) {
  $section_styles[] = "background-color: " . $block['style']['color']['background'];
} else if ( ! empty( $block['backgroundColor'] ) ) {
  $section_styles[] = 'background-color: var(--wp--preset--color--' . $block['backgroundColor'] . ')';
}

if ( ! empty( $block['style']['color']['gradient']) ) {
  $section_styles[] = "background-image: " . $block['style']['color']['gradient'];
} else if ( ! empty( $block['gradient'] ) ) {
  $section_styles[] = 'background-image: var(--wp--preset--gradient--' . $block['gradient'] . ')';
}

/**
 * ************************************************************
 * Block Spacing Options
 * Functions found in the Strength 2 theme
 * "../functions/components/section-margin.php"
 * "../functions/components/section-padding.php"
 * ************************************************************
 */
if ( ! empty( $block["style"]["spacing"]["padding"] ) ) {
  $padding = $block["style"]["spacing"]["padding"];
  $padding_styles = set_block_padding( $padding );
  if ( ! empty( $padding_styles ) ) {
    $section_styles[] = $padding_styles;
  }
}

$section_styles = implode( "; ", $section_styles );

/*
 * ************************************************************
 * Drop Shadow Options
 * Function found in the Strength 2 theme
 * "../functions/components/drop-shadow.php"
 * ************************************************************
 */
$content_cards_drop_shadow_styles = '';

if (! empty($block["style"]["shadow"])) {
  $shadow = $block["style"]["shadow"];
  $drop_shadow = set_drop_shadow($shadow);
  $content_cards_drop_shadow_styles = "--{$block_name}-card-drop-shadow: {$drop_shadow}";
}

$config = [
  'block_name'          => $block_name,
  'class_name'          => $class_name,
  'section_styles'      => $section_styles,
  'drop_shadow'         => $content_cards_drop_shadow_styles,
  'heading'             => get_field('heading'),
  'content'             => get_field('content'),
  'program_ids'         => get_field('program_ids'),
  'success_message'     => get_field('success_message'),
  'header_styles'       => get_field('header'),
  'day_styles'          => get_field('day'),
  'card_styles'         => get_field('card'),
  'button_colors'       => get_field('button_colors'),
  'button_hover_colors' => get_field('button_hover_colors'),
];

echo ( new Kicksite_Calendar_Renderer() )->render( $config );