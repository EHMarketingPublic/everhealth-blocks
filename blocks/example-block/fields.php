<?php
/**
 * Field group for blocks/example-block.
 *
 * Returned as a plain array and registered by EB_Block_Loader via
 * acf_add_local_field_group() — nothing here needs to be exported
 * through the ACF UI, this file *is* the source of truth.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields = array(
	array(
		'key'   => 'field_eb_example_heading',
		'label' => 'Heading',
		'name'  => 'heading',
		'type'  => 'text',
	),
	array(
		'key'   => 'field_eb_example_body',
		'label' => 'Body Copy',
		'name'  => 'body',
		'type'  => 'wysiwyg',
		'tabs'  => 'visual',
		'media_upload' => 0,
	),
);

// Only add the icon picker if the ACF FontAwesome add-on is actually
// active on this site — keeps the block usable on brand sites that
// don't have that add-on installed instead of throwing an ACF error.
if ( EB_Requirements::has_acf_fontawesome() ) {
	$fields[] = array(
		'key'          => 'field_eb_example_icon',
		'label'        => 'Icon',
		'name'         => 'icon',
		'type'         => 'font_awesome',
		'instructions' => 'Requires the ACF FontAwesome add-on.',
		'return_format' => 'array',
		'library'       => 'all',
	);
}

return array(
	'key'      => 'group_eb_example_block',
	'title'    => 'EverHealth: Example Block Fields',
	'fields'   => $fields,
	'location' => array(
		array(
			array(
				'param'    => 'block',
				'operator' => '==',
				'value'    => 'acf/example-block',
			),
		),
	),
);
