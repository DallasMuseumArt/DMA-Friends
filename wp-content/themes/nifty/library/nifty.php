<?php
/**
 * The Nifty class launches the framework. It's the organizational structure behind the
 * entire theme. This class should be loaded and initialized before anything else within
 * the theme is called to properly use the framework.
 *
 * Child themes should add their theme setup function on the 'after_setup_theme' hook with a priority of 10.
 * This allows the class to load theme-supported features at the appropriate time.
 *
 * @since 12.09
 */

/* Exit if accessed directly */
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Nifty' ) ) :
/**
 * Main Nifty Class
 *
 * @since 12.09
 */
class Nifty
{
	/* Parent theme directory */
	public $theme_dir = '';

	/* Parent theme directory URI */
	public $theme_uri = '';

	/* Child theme directory */
	public $child_theme_dir = '';

	/* Child theme directory URI */
	public $child_theme_uri = '';

	/* Framework directory */
	public $nifty_dir = '';

	/* Framework directory URI */
	public $nifty_uri = '';

	/* Framework admin directory */
	public $nifty_admin = '';

	/* Framework functions directory */
	public $nifty_functions = '';

	/* Framework extensions directory */
	public $nifty_extensions = '';

	/**
	 * PHP4 style Constructor - Calls PHP5 Style Constructor.
	 * 
	 * @since 12.09
	 */
	public function Nifty() {
		$this->__construct();
	}

	/**
	 * PHP5 style Constructor - Initializes the theme framework.
	 * 
	 * @since 12.09
	 */
	public function __construct() {

		/* Define framework, parent theme, and child theme constants. */
		add_action( 'after_setup_theme', array( &$this, 'constants' ), 1 );

		/* Load the core functions required by the rest of the framework. */
		add_action( 'after_setup_theme', array( &$this, 'core' ), 2 );

		/* Initialize the default actions. */
		add_action( 'after_setup_theme', array( &$this, 'actions' ), 3 );

		/* Initialize the default filters. */
		add_action( 'after_setup_theme', array( &$this, 'filters' ), 4 );

		/* Language functions and translations setup. */
		add_action( 'after_setup_theme', array( &$this, 'locale' ), 5 );

		/* Function for setting up all the Nifty parent theme supported features. */
		add_action( 'after_setup_theme', array( &$this, 'defaults' ), 6 );

		/* Load the framework functions and supported features. */
		add_action( 'after_setup_theme', array( &$this, 'functions' ), 14 );

		/* Load the framework extensions. */
		add_action( 'after_setup_theme', array( &$this, 'extensions' ), 15 );

		/* Load admin files. */
		add_action( 'wp_loaded', array( &$this, 'admin' ) );
	}

	/**
	 * Defines the constant paths for use within the core framework, parent theme, and child theme.
	 * 
	 * @since 12.09
	 */
	public function constants() {

		/* Sets the path to the parent theme directory. */
		$this->theme_dir = trailingslashit( get_template_directory() );

		/* Sets the path to the parent theme directory URI. */
		$this->theme_uri = trailingslashit( get_template_directory_uri() );

		/* Sets the path to the child theme directory. */
		$this->child_theme_dir = trailingslashit( get_stylesheet_directory() );

		/* Sets the path to the child theme directory URI. */
		$this->child_theme_uri = trailingslashit( get_stylesheet_directory_uri() );

		/* Sets the path to the framework directory. */
		$this->nifty_dir = trailingslashit( $this->theme_dir ) . 'library';

		/* Sets the path to the framework directory URI. */
		$this->nifty_uri = trailingslashit( $this->theme_uri ) . 'library';

		/* Sets the path to the core framework admin directory. */
		$this->nifty_admin = trailingslashit( $this->nifty_dir ) . 'admin';

		/* Sets the path to the framework functions directory. */
		$this->nifty_functions = trailingslashit( $this->nifty_dir ) . 'functions';

		/* Sets the path to the framework extensions directory. */
		$this->nifty_extensions = trailingslashit( $this->nifty_dir ) . 'extensions';
	}

	/**
	 * Loads the core framework functions.
	 *
	 * @since 12.09
	 */
	public function core() {

		/* Load the core framework functions. */
		require_once( trailingslashit( $this->nifty_functions ) . 'core.php' );

		/* Load the context-based functions. */
		require_once( trailingslashit( $this->nifty_functions ) . 'context.php' );
	}

	/**
	 * Handles the locale functions file and translations.
	 * 
	 * @since 12.09
	 */
	public function locale() {

		/* Load theme textdomain. */
		load_theme_textdomain( 'nifty', trailingslashit( $this->theme_dir ) . 'languages' );

		/* Get the user's locale. */
		$locale = get_locale();

		/* Locate a locale-specific functions file. */
		$locale_functions = locate_template( array( "languages/{$locale}.php" ) );

		/* If the locale file exists and is readable, load it. */
		if ( !empty( $locale_functions ) && is_readable( $locale_functions ) )
			require_once( $locale_functions );
	}

	/**
	 * Adds the default theme actions.
	 * 
	 * @since 12.09
	 */
	public function actions() {

		/* Add head actions. */
		add_action( 'wp_head', 'nifty_meta_generator' );
		add_action( 'wp_head', 'nifty_pingback' );
		add_action( 'wp_head', 'nifty_favicon' );
		add_action( 'wp_head', 'nifty_apple_touch_icon' );

		/* Header. */
		add_action( 'nifty_header', 'nifty_site_title' );
		add_action( 'nifty_header', 'nifty_site_description' );
		add_action( 'nifty_header', 'nifty_site_custom_header' );

		/* Before Loop */
		add_action( 'nifty_before_loop', 'nifty_loop_description' );

		/* After content. */
		add_action( 'nifty_close_content', 'nifty_navigation_links' );

		/* Entry Content. */
		add_action( 'nifty_open_entry', 'nifty_entry_title' );
		add_action( 'nifty_open_entry', 'nifty_entry_meta' );
		add_action( 'nifty_close_entry', 'nifty_entry_utility' );

		/* After singular post views. */
		add_action( 'nifty_after_singular', 'nifty_author_profile' );

		/* Footer. */
		add_action( 'nifty_footer', 'nifty_footer_insert' );

		/* Comments. */
		add_action( 'nifty_before_comment', 'nifty_comment_avatar' );
		add_action( 'nifty_before_comment', 'nifty_comment_meta' );
		add_action( 'nifty_after_comment', 'nifty_comment_reply_link' );
	}

	/**
	 * Adds the default theme filters.
	 * 
	 * @since 12.09
	 */
	public function filters() {

		/* WP Title */
		add_filter( 'wp_title', 'nifty_wp_title', 11, 3 );

		/* Add filters to user description */
		add_filter( 'get_the_author_description', 'convert_chars' );
		add_filter( 'get_the_author_description', 'wpautop' );
		add_filter( 'get_the_author_description', 'wptexturize' );

		/* Make text widgets, term descriptions, and user descriptions shortcode. */
		add_filter( 'get_the_author_description', 'do_shortcode' );
		add_filter( 'term_description', 'do_shortcode' );
		add_filter( 'widget_text', 'do_shortcode' );

		/* Feed links. */
		add_filter( 'feed_link', 'nifty_feed_link', 1, 2 );
		add_filter( 'category_feed_link', 'nifty_other_feed_link' );
		add_filter( 'author_feed_link', 'nifty_other_feed_link' );
		add_filter( 'tag_feed_link', 'nifty_other_feed_link' );
		add_filter( 'search_feed_link', 'nifty_other_feed_link' );

		/* Comment form filters. */
		add_filter( 'comment_form_defaults', 'nifty_comment_form_defaults' );

		/**
		 * The 'nifty comments_number' function has been deprecated.
		 * This line will be removed in the future.
		 */
		//add_filter( 'get_comments_number', 'nifty_comments_number' );
	}

	/**
	 * Function for setting up all the Nifty Theme Framework and WordPress Core supported features.
	 * 
	 * @since 12.09
	 */
	public function defaults() {

		/* Add support for the core SEO feature. */
		add_theme_support( 'nifty-core-seo' );

		/* Add support for the core Menu feature. */
		add_theme_support( 'nifty-core-menus', array( 'primary', 'secondary' ) );

		/* Add support for the Theme Layouts. */
		add_theme_support( 'nifty-core-theme-layouts', array( '1c', '2c-l', '2c-r', '3c-l', '3c-r', '3c-c' ) );

		/* Add support for the Sidebars. */
		add_theme_support( 'nifty-core-sidebars', array( 'primary', 'secondary', 'subsidiary' ) );

		/* Add editor style */
		add_editor_style( 'editor-style.css?' . time() );

		/* Add support for automatic feed links. */
		add_theme_support( 'automatic-feed-links' );

		/* Add support for Post Thumbnails. */
		add_theme_support( 'post-thumbnails' );

		/* Add support for custom background. */
		add_theme_support( 'custom-background' );

		/* Add support for Breadcrumbs Plus. */
		add_theme_support( 'breadcrumbs-plus' );

		/* Add support for Loop Pagination. */
		add_theme_support( 'loop-pagination' );

		/* Add support for Improving Caption. */
		add_theme_support( 'improving-caption' );

		/* Add support for Improving Caption. */
		add_theme_support( 'cleaner-gallery' );
	}

	/**
	 * Loads the framework functions and supported features.
	 * 
	 * @since 12.09
	 */
	public function functions() {

		/* Load the filters for theme. */
		require_once( trailingslashit( $this->nifty_functions ) . 'filters.php' );

		/* Load the comments functions. */
		require_once( trailingslashit( $this->nifty_functions ) . 'comments.php' );

		/* Load media-related functions. */
		require_once( trailingslashit( $this->nifty_functions ) . 'media.php' );

		/* Load the shortcodes. */
		require_once( trailingslashit( $this->nifty_functions ) . 'shortcodes.php' );

		/* Load the utility functions. */
		require_once( trailingslashit( $this->nifty_functions ) . 'utility.php' );

		/* Load the templates functions. */
		require_once( trailingslashit( $this->nifty_functions ) . 'templates.php' );

		/* Load the widgets functions. */
		require_once( trailingslashit( $this->nifty_functions ) . 'widgets.php' );

		/* Load the core SEO component if supported. */
		require_if_theme_supports( 'nifty-core-seo', trailingslashit( $this->nifty_functions ) . 'core-seo.php' );

		/* Load the core Menu component if supported. */
		require_if_theme_supports( 'nifty-core-menus', trailingslashit( $this->nifty_functions ) . 'menus.php' );

		/* Load the core sidebars if supported. */
		require_if_theme_supports( 'nifty-core-sidebars', trailingslashit( $this->nifty_functions ) . 'sidebars.php' );

		/* Load the Post layouts functions if supported. */
		require_if_theme_supports( 'nifty-core-theme-layouts', trailingslashit( $this->nifty_functions ) . 'theme-layouts.php' );
	}

	/**
	 * Load extensions.
	 * 
	 * @since 12.09
	 */
	public function extensions() {

		/* Load the Breadcrumbs Plus extension if supported. */
		require_if_theme_supports( 'breadcrumbs-plus', trailingslashit( $this->nifty_extensions ) . 'breadcrumbs-plus.php' );

		/* Load the Loop Pagination extension if supported. */
		require_if_theme_supports( 'loop-pagination', trailingslashit( $this->nifty_extensions ) . 'loop-pagination.php' );

		/* Load the Improving Caption extension if supported. */
		require_if_theme_supports( 'improving-caption', trailingslashit( $this->nifty_extensions ) . 'improving-caption.php' );

		/* Load the Cleaner Gallery extension if supported. */
		require_if_theme_supports( 'cleaner-gallery', trailingslashit( $this->nifty_extensions ) . 'cleaner-gallery.php' );
	}

	/**
	 * Load admin files.
	 * 
	 * @since 12.09
	 */
	public function admin() {

		if ( is_admin() ) {

			/* Load the post meta box. */
			require_once( trailingslashit( $this->nifty_admin ) . 'admin.php' );

			/* Load the theme settings page. */
			require_once( trailingslashit( $this->nifty_admin ) . 'theme-settings.php' );
		}
	}
}

/* Enjoy!!! */
$GLOBALS['nifty'] = new Nifty();

endif; /* class_exists check */
