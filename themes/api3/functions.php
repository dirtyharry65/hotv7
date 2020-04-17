<?php
/**
 * Api functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Api
 * @since 1.0.0
 */

/**
 * Table of Contents:
 * Theme Support
 * Required Files
 * Register Styles
 * Register Scripts
 * Register Menus
 * Custom Logo
 * WP Body Open
 * Register Sidebars
 * Enqueue Block Editor Assets
 * Enqueue Classic Editor Styles
 * Block Editor Settings
 */
include_once( dirname( __FILE__ ) . '/incl/courses.php' );
include_once( dirname( __FILE__ ) . '/incl/schools.php' );


/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function api_theme_support() {


	add_theme_support( 'post-thumbnails' );

	// Set post thumbnail size.
	set_post_thumbnail_size( 1200, 9999 );

	// Add custom image size used in Cover Template.
	add_image_size( 'api-fullscreen', 1980, 9999 );


	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'script',
			'style',
		)
	);
  include_once( dirname( __FILE__ ) . '/incl/courses.php' );
  /**
   * Load required Plugins in Theme Folder
   */

  //  if (!class_exists('CoursesClass')) {
        // include_once(get_template_directory_uri() . '/plugins/courses/index.php');
  //   }
}

add_action( 'after_setup_theme', 'api_theme_support' );

/**
 * Register and Enqueue Styles.
 */
function api_register_styles() {

	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style( 'api-style', get_template_directory_uri() . '/css/style.js', array(), $theme_version );

  wp_enqueue_style( 'react-template', get_template_directory_uri() . '/css/main.b5a744cc.chunk.css' );
  
 
}

add_action( 'wp_enqueue_scripts', 'api_register_styles' );

/**
 * Register and Enqueue Scripts.
 */
function api_register_scripts() {

	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_script( 'api-js', get_template_directory_uri() . '/js/index.js', array(), $theme_version, false );
	wp_script_add_data( 'api-js', 'async', true );

  wp_enqueue_script( 'react-template', get_template_directory_uri() . '/js/2.a1e16508.chunk.js' );
  wp_enqueue_script( 'react-chunk', get_template_directory_uri() . '/js/main.c2a8b70f.chunk.js' );
 

}

add_action( 'wp_enqueue_scripts', 'api_register_scripts' );