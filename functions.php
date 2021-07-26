<?php
/**
 * ------------------------------------------------------------------------
 * Theme's Includes
 * ------------------------------------------------------------------------
 * The `function.php` file is should responsible only for including theme's
 * components. Your theme custom logic should be distributed
 * across separate files in the `/src` directory.
 */

require_once 'src/styles.php';
require_once 'src/scripts.php';

require_once 'src/navs.php';
require_once 'src/supports.php';
require_once 'src/post-types.php';
require_once 'src/thumbnails.php';


/**
 * Proper way to register and enqueue scripts and styles
 */
function common_design_theme_stylesheets() {
	wp_enqueue_style ('common-design-normalize', get_template_directory_uri().'/resources/assets/css/normalize.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-hidden', get_template_directory_uri().'/resources/assets/css/hidden.module.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-button', get_template_directory_uri().'/resources/assets/css/cd-button/cd-button.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-flow', get_template_directory_uri().'/resources/assets/css/cd-flow/cd-flow.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-utilities', get_template_directory_uri().'/resources/assets/css/cd-utilities/cd-utilities.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-core', get_template_directory_uri().'/resources/assets/css/cd.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-article', get_template_directory_uri().'/resources/assets/css/cd-article/cd-article.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-form', get_template_directory_uri().'/resources/assets/css/cd-form/cd-form.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-page-title', get_template_directory_uri().'/resources/assets/css/cd-page-title/cd-page-title.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-teaser', get_template_directory_uri().'/resources/assets/css/cd-teaser/cd-teaser.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-typography', get_template_directory_uri().'/resources/assets/css/cd-typography/cd-typography.css', array(), null, 'all' );
	wp_enqueue_style ('common-design-overrides', get_template_directory_uri().'/resources/assets/css/cd-overrides.css', array(), null, 'all' );
	wp_enqueue_style ('implementation-overrides', get_template_directory_uri().'/resources/assets/css/styles.css', array(), null, 'all' );
}

function common_design_theme_scripts() {
	wp_enqueue_script( 'script', get_template_directory_uri() . '/resources/assets/js/cd-dropdown.js', array(), null, true );
}

add_action('enqueue_block_editor_assets', function() {
	wp_enqueue_script('common-design-gutenberg-filters', get_template_directory_uri() . '/resources/assets/js/gutenberg-filters.js', ['wp-edit-post']);
});

add_action( 'wp_enqueue_scripts', 'common_design_theme_stylesheets' );
add_action( 'wp_enqueue_scripts', 'common_design_theme_scripts' );

/* Add Common Design styles to the Gutenburg blocks on the Edit page */
function common_design_theme_editor_styles(){
	add_theme_support( 'editor-styles' );
	add_editor_style( get_template_directory_uri().'/resources/assets/css/style-editor.css' );
}
add_action( 'after_setup_theme', 'common_design_theme_editor_styles' );

/**
 * Add "menu-item--expanded" class to parent menu items.
 */
function cd_add_menu_parent_class( $items ) {
	$parents = array();
	foreach ( $items as $item ) {
		//Check if the item is a parent item
		if ( $item->menu_item_parent && $item->menu_item_parent > 0 ) {
			$parents[] = $item->menu_item_parent;
		}
	}

	foreach ( $items as $item ) {
		if ( in_array( $item->ID, $parents ) ) {
			//Add "menu-parent-item" class to parents
			$item->classes[] = 'menu-item--expanded';
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'cd_add_menu_parent_class' );

/**
 * Add "menu-item--active-trail" class to menu items.
 */
function cd_active_nav_class($classes, $item){
	if( in_array( 'current-menu-item', $classes ) ||
	    in_array( 'current-menu-ancestor', $classes ) ||
	    in_array( 'current-menu-parent', $classes ) ||
	    in_array( 'current_page_parent', $classes ) ||
	    in_array( 'current_page_ancestor', $classes )
	) {

		$classes[] = 'menu-item--active-trail ';
	}

	return $classes;
}
add_filter('nav_menu_css_class' , 'cd_active_nav_class' , 10 , 2);

/**
 * Add "menu" class to sub menu.
 */
function cd_nav_menu_submenu_css_class( $classes ) {
	$classes[] = 'menu cd-main-menu__dropdown';
	return $classes;
}
add_filter( 'nav_menu_submenu_css_class', 'cd_nav_menu_submenu_css_class' );


/**
 * Add an ID attribute and value to nav menu links.
 */
add_filter( 'nav_menu_link_attributes', function ( $atts, $item, $args ) {
	$atts['id'] = 'cd-main-menu-item-' . $item->ID;
	return $atts;
}, 10, 3 );


/**
 * Custom walker class.
 * This outputs the Main Menu markup needed for the cd-dropdown.js to work when menu items that have children.
 * It adds ids, classes and custom data attributes to the menu, menu items and menu item links.
 */
class cd_MainNav_Walker extends Walker_Nav_Menu {
	private $curItem;
	/**
	 * Starts the list before the elements are added.
	 *
	 * Adds classes to the unordered list sub-menus.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   An array of arguments. @see wp_nav_menu()
	 */
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		// Depth-dependent classes.
		$indent = ( $depth > 0  ? str_repeat( "\t", $depth ) : '' ); // code indent
		$display_depth = ( $depth + 1); // because it counts the first submenu as 0
		$classes = array(
			'menu',
			'cd-main-menu__dropdown',
			( $display_depth % 2  ? 'menu-odd' : 'menu-even' ),
			( $display_depth >=2 ? 'sub-sub-menu' : '' ),
			'menu-depth-' . $display_depth
		);
		$class_names = implode( ' ', $classes );

		// Get the ID of the
//		var_dump($this->curItem );
		$curItemID = $this->curItem->ID;
		$curItemTitle = $this->curItem->title;

		$atts           = array();
		$atts['data-cd-icon'] = 'arrow-down';
		$atts['data-cd-component'] = 'cd-main-menu';

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}
		// Build HTML for output.
		$output .= "\n" . $indent . '
		<ul id="cd-main-menu-' . $depth . '-' . $curItemID . '" 
			data-cd-replace="cd-main-menu-item-' . $depth . '-' . $curItemID . '"  
			data-cd-toggable="' . $curItemTitle . '" 
			class="' . $class_names . '" 
		' . $attributes . '>' . "\n";
	}

	function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		global $wp_query;
		$this->curItem = $item;

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = ' class="' . esc_attr( $class_names ) . '"';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = strlen( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $value . $class_names .'>';

		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
		$attributes .= ' id="cd-main-menu-item-' . $depth .'-' . $item->ID .'"';

		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	/**
	 * @see Walker::end_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Page data object. Not used.
	 * @param int $depth Depth of page. Not Used.
	 */
	function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= "</li>\n";
	}
}

function cd_get_language_attributes( $doctype = 'html' ) {
	$attributes = array();

	if ( function_exists( 'is_rtl' ) && is_rtl() ) {
		$attributes[] = 'dir="rtl"';
	} else {
		$attributes[] = 'dir="ltr"';
	}

	$output = implode( ' ', $attributes );

	/**
	 * Filters the language attributes for display in the html tag.
	 *
	 * @since 2.5.0
	 * @since 4.3.0 Added the `$doctype` parameter.
	 *
	 * @param string $output A space-separated list of language attributes.
	 * @param string $doctype The type of html document (xhtml|html).
	 */
	return apply_filters( 'cd_language_attributes', $output, $doctype );
}

function cd_language_attributes( $doctype = 'html' ) {
	echo cd_get_language_attributes( $doctype );
}
add_filter( 'language_attributes', 'cd_language_attributes' );






// Allow SVG
add_filter( 'wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {

  global $wp_version;
  if ( $wp_version !== '4.7.1' ) {
     return $data;
  }

  $filetype = wp_check_filetype( $filename, $mimes );

  return [
      'ext'             => $filetype['ext'],
      'type'            => $filetype['type'],
      'proper_filename' => $data['proper_filename']
  ];

}, 10, 4 );

function cc_mime_types( $mimes ){
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter( 'upload_mimes', 'cc_mime_types' );

function fix_svg() {
  echo '<style type="text/css">
        .attachment-266x266, .thumbnail img {
             width: 100% !important;
             height: auto !important;
        }
        </style>';
}
add_action( 'admin_head', 'fix_svg' );







add_filter( 'get_custom_logo', 'change_logo_class' );


function change_logo_class( $html ) {

    $html = str_replace( 'custom-logo-link', 'cd-site-logo', $html );

    return $html;
}



function which_template_is_loaded() {
//	if ( is_super_admin() ) {
		global $template;
		print_r( $template );
//	}
}

add_action( 'wp_footer', 'which_template_is_loaded' );



/**
widgets
 */
add_theme_support('widgets');

/**
register side bars
 */
function my_sidebars() {
	register_sidebar(
		array(
			'name' => 'Sidebar First',
			'id' => 'sidebar-first',
			'before-title' => '<h4 class="widget-title">',
			'after_title' => '<h4>'
		)
	);

	register_sidebar(
		array(
			'name' => 'Sidebar Second',
			'id' => 'sidebar-second',
			'before-title' => '<h4 class="widget-title">',
			'after_title' => '<h4>'
		)
	);
}
add_action('widgets_init', 'my_sidebars');


/* Disable Gutenberg block types we don't need */
/* https://rudrastyh.com/gutenberg/remove-default-blocks.html */
add_filter( 'allowed_block_types', 'common_design_allowed_block_types', 10, 2 );

function common_design_allowed_block_types( $allowed_blocks, $post ) {

	$allowed_blocks = array(
		'core/buttons',
		'core/image',
		'core/paragraph',
		'core/heading',
		'core/list'
	);

	if( $post->post_type === 'page' ) {
		$allowed_blocks[] = 'core/gallery';
	}

	return $allowed_blocks;

}

function common_design_disable_gutenberg_typography_settings() {
	add_theme_support( 'editor-font-sizes' );
	add_theme_support( 'disable-custom-font-sizes' );
}
add_action( 'after_setup_theme', 'common_design_disable_gutenberg_typography_settings' );

function common_design_disable_drop_cap_editor_settings(array $editor_settings): array {
	$editor_settings['__experimentalFeatures']['defaults']['typography']['dropCap'] = false;
	return $editor_settings;
}
add_filter('block_editor_settings', 'common_design_disable_drop_cap_editor_settings');

function common_design_disable_gutenberg_color_settings() {
	add_theme_support( 'disable-custom-colors' );
	add_theme_support( 'disable-custom-colors' );
	add_theme_support( 'editor-color-palette' );
	add_theme_support( 'editor-gradient-presets', [] );
	add_theme_support( 'disable-custom-gradients' );
}
add_action( 'after_setup_theme', 'common_design_disable_gutenberg_color_settings' );




// Image size for teaser thumbnails
add_image_size( 'thumbnail-teaser', 600, 450 );
//add_image_size( 'sidebar-thumb', 120, 120, true ); // Hard Crop Mode
//add_image_size( 'homepage-thumb', 220, 180 ); // Soft Crop Mode
//add_image_size( 'singlepost-thumb', 590, 9999 ); // Unlimited Height Mode
