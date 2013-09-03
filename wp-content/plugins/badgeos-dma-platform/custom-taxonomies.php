<?php
/**
 * Plugin helper class for registering custom taxonomies.
 *
 */
class DMA_Tax_Setup {

	private $singular;
	private $plural;
	private $slug;
	private $object_types;
	private $args;

	public $taxonomy;

	function DMA_Tax_Setup( $singular, $plural = '', $object_types = array( 'checkin', 'step', 'activity' ), $args = array() ) {
		DMA_Tax_Setup::__construct( $singular, $plural, $object_types, $args );
	}

	public function __construct( $singular, $plural = '', $object_types = array( 'checkin', 'step', 'activity' ), $args = array() ) {

		if( ! $singular )
			wp_die( 'No taxonomy ID given' );

		$this->singular     = $singular;
		$this->plural       = ( empty( $plural ) ) ? $singular .'s' : $plural;

		if ( isset( $args['registered_slug'] ) ) {
			$this->slug      = $args['registered_slug'];
			unset( $args['registered_slug'] );
		} else {
			$this->slug      = str_replace( ' ', '-', strtolower( $this->singular ) );
		}

		$this->object_types = (array) $object_types;
		$args    		     = (array) $args;

		$labels = array(
			'name'              => $this->plural,
			'singular_name'     => $this->singular,
			'search_items'      =>  'Search '.$this->plural,
			'all_items'         => 'All '.$this->plural,
			'parent_item'       => 'Parent '.$this->singular,
			'parent_item_colon' => 'Parent '.$this->singular.':',
			'edit_item'         => 'Edit '.$this->singular,
			'update_item'       => 'Update '.$this->singular,
			'add_new_item'      => 'Add New '.$this->singular,
			'new_item_name'     => 'New '.$this->singular.' Name',
		);

		$hierarchical = true;

		if ( isset( $args['hierarchical'] ) && $args['hierarchical'] == false ) {
			$labels = array_merge(
				$labels,
				array(
					'popular_items'              => 'Popular '.$this->plural,
					'separate_items_with_commas' => 'Separate '.$this->plural.' with commas',
					'add_or_remove_items'        => 'Add or remove '.$this->plural,
					'choose_from_most_used'      => 'Choose from the most used '.$this->plural,
				)
			);
			$hierarchical = false;
		}

		$defaults = array(
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => array(
				'hierarchical' => $hierarchical,
				'slug'         => $this->slug ),
		);

		$this->args = wp_parse_args( $args, $defaults );

		$this->taxonomy = array(
			'singular'     => $this->singular,
			'plural'       => $this->plural,
			'slug'         => $this->slug,
			'object_types' => $this->object_types,
			'args'         => $this->args
		);

		add_action( 'init', array( $this, 'register_tax' ) );
		foreach ( $this->object_types as $object_type ) {
			add_filter( 'manage_edit-'. $object_type .'_columns', array( $this, 'columns' ) );
		}
		add_action( 'manage_posts_custom_column', array( $this, 'columns_display' ) );

		add_action( 'admin_init', array( $this, 'submenu_pages' ) );
	}

	public function register_tax() {
		register_taxonomy( $this->slug, $this->object_types, $this->args );
	}

	/**
	 * Adds Custom Taxonomy columns to post listing edit page
	 *
	 */
	public function columns( $columns ) {

		$columns[$this->slug.'_column'] = $this->singular;
		return $columns;

	}

	/**
	 * Display Custom Taxonomy columns on post listing edit page
	 *
	 */
	public function columns_display( $column ) {
		global $post;
		if ( $column == $this->slug.'_column' ) {
			$id = $post->ID;
			$categories = get_the_terms( $id, $this->slug );
			if ( !empty( $categories ) ) {
				$out = array();
				foreach ( $categories as $c ) {
					$out[] = sprintf( '<a href="%s">%s</a>',
					esc_url( add_query_arg( array( 'post_type' => $post->post_type, $this->slug => $c->slug ), 'edit.php' ) ),
					esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, $this->slug, 'display' ) )
					);
				}
				echo join( ', ', $out );
			} else {
				_e( 'No '. $this->plural .' Specified' );
			}

		}
	}

	/**
	 * Display Custom Taxonomy columns on post listing edit page
	 *
	 */
	public function submenu_pages( $args = array() ) {

		$defaults = array(
			'menu_title' => $this->plural,
			'capability' => 'manage_options',
			'menu_slug'  => add_query_arg( 'taxonomy', $this->slug, admin_url( '/edit-tags.php' ) ),
			'page_title' => $this->plural
		);
		$args = wp_parse_args( $args, $defaults );

		global $submenu;

		if ( empty( $submenu['badgeos_badgeos']) )
			return;

		$rest_of_the_array = array_slice( $submenu['badgeos_badgeos'], 5 );

		$submenu['badgeos_badgeos'] = array_slice( $submenu['badgeos_badgeos'], 0, 5 );

		$submenu['badgeos_badgeos'][] = array( $args['menu_title'], $args['capability'], $args['menu_slug'], $args['page_title'] );

		foreach ( $rest_of_the_array as $piece )
			array_push( $submenu['badgeos_badgeos'], $piece );
	}

}

/**
 * Register our custom taxonomies using the helper class
 */
$badge_category        = new DMA_Tax_Setup( 'Badge Category', 'Badge Categories', array( 'badge' ) );
$activity_type         = new DMA_Tax_Setup( 'Activity Type', 'Activity Type', array( 'activity', 'step' ) );
$activity_category     = new DMA_Tax_Setup( 'Activity Category', 'Activity Categories', array( 'activity', 'step' ) );
$activity_trigger_type = new DMA_Tax_Setup( 'Activity Trigger Type', 'Activity Trigger Types', array( 'activity', 'step' ) );
$event_type            = new DMA_Tax_Setup( 'Event Type', 'Event Types', array( 'dma-event', 'step' ) );
$event_category        = new DMA_Tax_Setup( 'Event Category', 'Event Categories', array( 'dma-event', 'step' ) );
