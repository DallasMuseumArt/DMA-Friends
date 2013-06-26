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

	function DMA_Tax_Setup( $singular, $plural = '', $object_types = array( 'checkin', 'dma-step', 'activity' ), $args = array() ) {
		DMA_Tax_Setup::__construct( $singular, $plural, $object_types, $args );
	}

	public function __construct( $singular, $plural = '', $object_types = array( 'checkin', 'dma-step', 'activity' ), $args = array() ) {

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

		if ( empty( $submenu['badgestack_badgestack']) )
			return;

		$rest_of_the_array = array_slice( $submenu['badgestack_badgestack'], 5 );

		$submenu['badgestack_badgestack'] = array_slice( $submenu['badgestack_badgestack'], 0, 5 );

		$submenu['badgestack_badgestack'][] = array( $args['menu_title'], $args['capability'], $args['menu_slug'], $args['page_title'] );

		foreach ( $rest_of_the_array as $piece )
			array_push( $submenu['badgestack_badgestack'], $piece );
	}

}

/**
 * Register our custom taxonomies using the helper class
 */
$badge_category        = new DMA_Tax_Setup( 'Badge Category', 'Badge Categories', array( 'badge' ) );
$activity_type         = new DMA_Tax_Setup( 'Activity Type', 'Activity Type', array( 'checkin', 'activity', 'dma-step' ) );
$activity_category     = new DMA_Tax_Setup( 'Activity Category', 'Activity Categories', array( 'checkin', 'activity', 'dma-step' ) );
$activity_trigger_type = new DMA_Tax_Setup( 'Activity Trigger Type', 'Activity Trigger Types', array( 'checkin', 'activity', 'dma-step', 'dma-event' ) );
$event_type            = new DMA_Tax_Setup( 'Event Type', 'Event Types', array( 'dma-event', 'checkin' ) );
$event_category        = new DMA_Tax_Setup( 'Event Category', 'Event Categories', array( 'dma-event', 'checkin' ) );


/**
 * Register aditional taxonomies
 */
add_action( 'init', 'dma_register_other_taxonomies' );
function dma_register_other_taxonomies() {

	register_taxonomy(
		'special-step-earning-option',
		array( 'dma-step' ),
		array(
			'hierarchical' => false,
			'labels'       => array(
				'name'              => _x( 'Special Earning Options', 'taxonomy general name' ),
				'singular_name'     => _x( 'Special Earning Option', 'taxonomy singular name' ),
				'search_items'      => __( 'Search Special Earning Options' ),
				'all_items'         => __( 'All Special Earning Options' ),
				'parent_item'       => __( 'Parent Special Earning Options' ),
				'parent_item_colon' => __( 'Parent Special Earning Option:' ),
				'edit_item'         => __( 'Edit Special Earning Option' ),
				'update_item'       => __( 'Update Special Earning Option' ),
				'add_new_item'      => __( 'Add New Special Earning Option' ),
				'new_item_name'     => __( 'New Special Earning Option Name' ),
				'menu_name'         => __( 'Special Earning Options' ),
			),
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => false,
		)
	);

	register_taxonomy(
		'step-measurement',
		array( 'dma-step' ),
		array(
			'hierarchical' => false,
			'labels'       => array(
				'name'              => _x( 'Step Measurement', 'taxonomy general name' ),
				'singular_name'     => _x( 'Step Measurement', 'taxonomy singular name' ),
				'search_items'      => __( 'Search Step Measurements' ),
				'all_items'         => __( 'All Step Measurements' ),
				'parent_item'       => __( 'Parent Step Measurements' ),
				'parent_item_colon' => __( 'Parent Step Measurement:' ),
				'edit_item'         => __( 'Edit Step Measurement' ),
				'update_item'       => __( 'Update Step Measurement' ),
				'add_new_item'      => __( 'Add New Step Measurement' ),
				'new_item_name'     => __( 'New Step Measurement Name' ),
				'menu_name'         => __( 'Step Measurements' ),
			),
			'show_ui'      => false,
			'query_var'    => true,
			'rewrite'      => false,
		)
	);
}
