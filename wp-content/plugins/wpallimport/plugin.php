<?php
/*
Plugin Name: WP All Import
Plugin URI: http://www.wpallimport.com/
Description: The most powerful solution for importing XML and CSV files to WordPress. Import to Posts, Pages, and Custom Post Types. Support for imports that run on a schedule, ability to update existing imports, and much more.
Version: 3.1.7
Author: Soflyy
*/

if( ! defined( 'PMXI_SESSION_COOKIE' ) )
	define( 'PMXI_SESSION_COOKIE', '_pmxi_session' );

/**
 * Plugin root dir with forward slashes as directory separator regardless of actuall DIRECTORY_SEPARATOR value
 * @var string
 */
if ( class_exists('PMXI_Plugin') and PMXI_EDITION == "free"){

	function pmxi_notice(){
		
		?>
		<div class="error"><p>
			<?php printf(__('Please de-activate and remove the free version of WP All Import before activating the paid version.', 'pmwi_plugin'));
			?>
		</p></div>
		<?php				

		deactivate_plugins(str_replace('\\', '/', dirname(__FILE__)) . '/plugin.php');

	}

	add_action('admin_notices', 'pmxi_notice');	

}
else {

	define('PMXI_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
	/**
	 * Plugin root url for referencing static content
	 * @var string
	 */
	define('PMXI_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));
	/**
	 * Plugin prefix for making names unique (be aware that this variable is used in conjuction with naming convention,
	 * i.e. in order to change it one must not only modify this constant but also rename all constants, classes and functions which
	 * names composed using this prefix)
	 * @var string
	 */
	define('PMXI_PREFIX', 'pmxi_');

	define('PMXI_VERSION', '3.1.4');

	define('PMXI_EDITION', 'paid');

	/**
	 * Main plugin file, Introduces MVC pattern
	 *
	 * @singletone
	 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
	 */
	final class PMXI_Plugin {
		/**
		 * Singletone instance
		 * @var PMXI_Plugin
		 */
		protected static $instance;

		/**
		 * Plugin options
		 * @var array
		 */
		protected $options = array();

		/**
		 * Plugin root dir
		 * @var string
		 */
		const ROOT_DIR = PMXI_ROOT_DIR;
		/**
		 * Plugin root URL
		 * @var string
		 */
		const ROOT_URL = PMXI_ROOT_URL;
		/**
		 * Prefix used for names of shortcodes, action handlers, filter functions etc.
		 * @var string
		 */
		const PREFIX = PMXI_PREFIX;
		/**
		 * Plugin file path
		 * @var string
		 */
		const FILE = __FILE__;
		/**
		 * Max allowed file size (bytes) to import in default mode
		 * @var int
		 */
		const LARGE_SIZE = 0; // all files will importing in large import mode	

		public static $session;		

		/**
		 * Return singletone instance
		 * @return PMXI_Plugin
		 */
		static public function getInstance() {
			if (self::$instance == NULL) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Common logic for requestin plugin info fields
		 */
		public function __call($method, $args) {
			if (preg_match('%^get(.+)%i', $method, $mtch)) {
				$info = get_plugin_data(self::FILE);
				if (isset($info[$mtch[1]])) {
					return $info[$mtch[1]];
				}
			}
			throw new Exception("Requested method " . get_class($this) . "::$method doesn't exist.");
		}

		/**
		 * Get path to plagin dir relative to wordpress root
		 * @param bool[optional] $noForwardSlash Whether path should be returned withot forwarding slash
		 * @return string
		 */
		public function getRelativePath($noForwardSlash = false) {
			$wp_root = str_replace('\\', '/', ABSPATH);
			return ($noForwardSlash ? '' : '/') . str_replace($wp_root, '', self::ROOT_DIR);
		}

		/**
		 * Check whether plugin is activated as network one
		 * @return bool
		 */
		public function isNetwork() {
			if ( !is_multisite() )
			return false;

			$plugins = get_site_option('active_sitewide_plugins');
			if (isset($plugins[plugin_basename(self::FILE)]))
				return true;

			return false;
		}

		/**
		 * Check whether permalinks is enabled
		 * @return bool
		 */
		public function isPermalinks() {
			global $wp_rewrite;

			return $wp_rewrite->using_permalinks();
		}

		/**
		 * Return prefix for plugin database tables
		 * @return string
		 */
		public function getTablePrefix() {
			global $wpdb;
			
			//return ($this->isNetwork() ? $wpdb->base_prefix : $wpdb->prefix) . self::PREFIX;
			return $wpdb->prefix . self::PREFIX;
		}

		/**
		 * Return prefix for wordpress database tables
		 * @return string
		 */
		public function getWPPrefix() {
			global $wpdb;
			return ($this->isNetwork()) ? $wpdb->base_prefix : $wpdb->prefix;
		}

		/**
		 * Class constructor containing dispatching logic
		 * @param string $rootDir Plugin root dir
		 * @param string $pluginFilePath Plugin main file
		 */
		protected function __construct() {
			
			$this->load_plugin_textdomain();

			// regirster autoloading method
			if (function_exists('__autoload') and ! in_array('__autoload', spl_autoload_functions())) { // make sure old way of autoloading classes is not broken
				spl_autoload_register('__autoload');
			}
			spl_autoload_register(array($this, '__autoload'));

			// register helpers
			if (is_dir(self::ROOT_DIR . '/helpers')) foreach (PMXI_Helper::safe_glob(self::ROOT_DIR . '/helpers/*.php', PMXI_Helper::GLOB_RECURSE | PMXI_Helper::GLOB_PATH) as $filePath) {
				require_once $filePath;
			}

			// create history folder
			$uploads = wp_upload_dir();
			if (!is_dir($uploads['basedir'] . '/wpallimport_history')) wp_mkdir_p($uploads['basedir'] . '/wpallimport_history');
			// create logs folder
			if (!is_dir($uploads['basedir'] . '/wpallimport_logs')) wp_mkdir_p($uploads['basedir'] . '/wpallimport_logs');

			// init plugin options
			$option_name = get_class($this) . '_Options';
			$options_default = PMXI_Config::createFromFile(self::ROOT_DIR . '/config/options.php')->toArray();
			$this->options = array_intersect_key(get_option($option_name, array()), $options_default) + $options_default;
			$this->options = array_intersect_key($options_default, array_flip(array('info_api_url'))) + $this->options; // make sure hidden options apply upon plugin reactivation
			if ('' == $this->options['cron_job_key']) $this->options['cron_job_key'] = url_title(rand_char(12));

			update_option($option_name, $this->options);
			$this->options = get_option(get_class($this) . '_Options');

			register_activation_hook(self::FILE, array($this, '__activation'));

			// register action handlers
			if (is_dir(self::ROOT_DIR . '/actions')) if (is_dir(self::ROOT_DIR . '/actions')) foreach (PMXI_Helper::safe_glob(self::ROOT_DIR . '/actions/*.php', PMXI_Helper::GLOB_RECURSE | PMXI_Helper::GLOB_PATH) as $filePath) {
				require_once $filePath;
				$function = $actionName = basename($filePath, '.php');
				if (preg_match('%^(.+?)[_-](\d+)$%', $actionName, $m)) {
					$actionName = $m[1];
					$priority = intval($m[2]);
				} else {
					$priority = 10;
				}
				add_action($actionName, self::PREFIX . str_replace('-', '_', $function), $priority, 99); // since we don't know at this point how many parameters each plugin expects, we make sure they will be provided with all of them (it's unlikely any developer will specify more than 99 parameters in a function)
			}

			// register filter handlers
			if (is_dir(self::ROOT_DIR . '/filters')) foreach (PMXI_Helper::safe_glob(self::ROOT_DIR . '/filters/*.php', PMXI_Helper::GLOB_RECURSE | PMXI_Helper::GLOB_PATH) as $filePath) {
				require_once $filePath;
				$function = $actionName = basename($filePath, '.php');
				if (preg_match('%^(.+?)[_-](\d+)$%', $actionName, $m)) {
					$actionName = $m[1];
					$priority = intval($m[2]);
				} else {
					$priority = 10;
				}
				add_filter($actionName, self::PREFIX . str_replace('-', '_', $function), $priority, 99); // since we don't know at this point how many parameters each plugin expects, we make sure they will be provided with all of them (it's unlikely any developer will specify more than 99 parameters in a function)
			}

			// register shortcodes handlers
			if (is_dir(self::ROOT_DIR . '/shortcodes')) foreach (PMXI_Helper::safe_glob(self::ROOT_DIR . '/shortcodes/*.php', PMXI_Helper::GLOB_RECURSE | PMXI_Helper::GLOB_PATH) as $filePath) {
				$tag = strtolower(str_replace('/', '_', preg_replace('%^' . preg_quote(self::ROOT_DIR . '/shortcodes/', '%') . '|\.php$%', '', $filePath)));
				add_shortcode($tag, array($this, 'shortcodeDispatcher'));
			}

			// register admin page pre-dispatcher
			add_action('admin_init', array($this, '__adminInit'));

			global $wpdb;

			if (function_exists('is_multisite') && is_multisite()) {
		        // check if it is a network activation - if so, run the activation function for each blog id
		        if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
		            $old_blog = $wpdb->blogid;
		            // Get all blog ids
		            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		            foreach ($blogids as $blog_id) {
		                switch_to_blog($blog_id);
		                $this->__add_feed_type_fix(); // feature to version 2.22
		            }
		            switch_to_blog($old_blog);		            
	            	return;
		        }
		    }

			$this->__add_feed_type_fix(); // feature to version 2.22

		}

		/**
		 * pre-dispatching logic for admin page controllers
		 */
		public function __adminInit() {
			$input = new PMXI_Input();
			$page = strtolower($input->getpost('page', ''));
			if (preg_match('%^' . preg_quote(str_replace('_', '-', self::PREFIX), '%') . '([\w-]+)$%', $page)) {
				$this->adminDispatcher($page, strtolower($input->getpost('action', 'index')));
			}
		}

		/**
		 * Dispatch shorttag: create corresponding controller instance and call its index method
		 * @param array $args Shortcode tag attributes
		 * @param string $content Shortcode tag content
		 * @param string $tag Shortcode tag name which is being dispatched
		 * @return string
		 */
		public function shortcodeDispatcher($args, $content, $tag) {

			$controllerName = self::PREFIX . preg_replace('%(^|_).%e', 'strtoupper("$0")', $tag); // capitalize first letters of class name parts and add prefix
			$controller = new $controllerName();
			if ( ! $controller instanceof PMXI_Controller) {
				throw new Exception("Shortcode `$tag` matches to a wrong controller type.");
			}
			ob_start();
			$controller->index($args, $content);
			return ob_get_clean();
		}

		/**
		 * Dispatch admin page: call corresponding controller based on get parameter `page`
		 * The method is called twice: 1st time as handler `parse_header` action and then as admin menu item handler
		 * @param string[optional] $page When $page set to empty string ealier buffered content is outputted, otherwise controller is called based on $page value
		 */
		public function adminDispatcher($page = '', $action = 'index') {
			static $buffer = NULL;
			static $buffer_callback = NULL;
			if ('' === $page) {
				if ( ! is_null($buffer)) {
					echo '<div class="wrap">';
					echo $buffer;
					do_action('pmxi_action_after');
					echo '</div>';
				} elseif ( ! is_null($buffer_callback)) {
					echo '<div class="wrap">';
					call_user_func($buffer_callback);
					do_action('pmxi_action_after');
					echo '</div>';
				} else {
					throw new Exception('There is no previousely buffered content to display.');
				}
			} else {
				$controllerName =  preg_replace('%(^' . preg_quote(self::PREFIX, '%') . '|_).%e', 'strtoupper("$0")', str_replace('-', '_', $page)); // capitalize prefix and first letters of class name parts
				$actionName = str_replace('-', '_', $action);
				if (method_exists($controllerName, $actionName)) {
					$this->_admin_current_screen = (object)array(
						'id' => $controllerName,
						'base' => $controllerName,
						'action' => $actionName,
						'is_ajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest',
						'is_network' => is_network_admin(),
						'is_user' => is_user_admin(),
					);
					add_filter('current_screen', array($this, 'getAdminCurrentScreen'));
					add_filter('admin_body_class', create_function('', 'return "' . PMXI_Plugin::PREFIX . 'plugin";'));

					$controller = new $controllerName();
					if ( ! $controller instanceof PMXI_Controller_Admin) {
						throw new Exception("Administration page `$page` matches to a wrong controller type.");
					}

					if ($this->_admin_current_screen->is_ajax) { // ajax request
						$controller->$action();
						do_action('pmxi_action_after');
						die(); // stop processing since we want to output only what controller is randered, nothing in addition
					} elseif ( ! $controller->isInline) {
						ob_start();
						$controller->$action();
						$buffer = ob_get_clean();
					} else {
						$buffer_callback = array($controller, $action);
					}
				} else { // redirect to dashboard if requested page and/or action don't exist
					wp_redirect(admin_url()); die();
				}
			}
		}

		protected $_admin_current_screen = NULL;
		public function getAdminCurrentScreen()
		{
			return $this->_admin_current_screen;
		}

		/**
		 * Autoloader
		 * It's assumed class name consists of prefix folloed by its name which in turn corresponds to location of source file
		 * if `_` symbols replaced by directory path separator. File name consists of prefix folloed by last part in class name (i.e.
		 * symbols after last `_` in class name)
		 * When class has prefix it's source is looked in `models`, `controllers`, `shortcodes` folders, otherwise it looked in `core` or `library` folder
		 *
		 * @param string $className
		 * @return bool
		 */
		public function __autoload($className) {
			$is_prefix = false;
			$filePath = str_replace('_', '/', preg_replace('%^' . preg_quote(self::PREFIX, '%') . '%', '', strtolower($className), 1, $is_prefix)) . '.php';
			if ( ! $is_prefix) { // also check file with original letter case
				$filePathAlt = $className . '.php';
			}
			foreach ($is_prefix ? array('models', 'controllers', 'shortcodes', 'classes') : array('libraries') as $subdir) {
				$path = self::ROOT_DIR . '/' . $subdir . '/' . $filePath;
				if (is_file($path)) {
					require $path;
					return TRUE;
				}
				if ( ! $is_prefix) {
					$pathAlt = self::ROOT_DIR . '/' . $subdir . '/' . $filePathAlt;
					if (is_file($pathAlt)) {
						require $pathAlt;
						return TRUE;
					}
				}
			}
			
			return FALSE;
		}

		/**
		 * Get plugin option
		 * @param string[optional] $option Parameter to return, all array of options is returned if not set
		 * @return mixed
		 */
		public function getOption($option = NULL) {
			if (is_null($option)) {
				return $this->options;
			} else if (isset($this->options[$option])) {
				return $this->options[$option];
			} else {
				throw new Exception("Specified option is not defined for the plugin");
			}
		}
		/**
		 * Update plugin option value
		 * @param string $option Parameter name or array of name => value pairs
		 * @param mixed[optional] $value New value for the option, if not set than 1st parameter is supposed to be array of name => value pairs
		 * @return array
		 */
		public function updateOption($option, $value = NULL) {
			is_null($value) or $option = array($option => $value);
			if (array_diff_key($option, $this->options)) {
				throw new Exception("Specified option is not defined for the plugin");
			}
			$this->options = $option + $this->options;
			update_option(get_class($this) . '_Options', $this->options);

			return $this->options;
		}

		/**
		 * Plugin activation logic
		 */
		public function __activation() {
			// uncaught exception doesn't prevent plugin from being activated, therefore replace it with fatal error so it does
			set_exception_handler(create_function('$e', 'trigger_error($e->getMessage(), E_USER_ERROR);'));

			// create plugin options
			$option_name = get_class($this) . '_Options';
			$options_default = PMXI_Config::createFromFile(self::ROOT_DIR . '/config/options.php')->toArray();
			update_option($option_name, $options_default);

			// create/update required database tables
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			require self::ROOT_DIR . '/schema.php';
			global $wpdb;

			if (function_exists('is_multisite') && is_multisite()) {
		        // check if it is a network activation - if so, run the activation function for each blog id	        
		        if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
		            $old_blog = $wpdb->blogid;
		            // Get all blog ids
		            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		            foreach ($blogids as $blog_id) {
		                switch_to_blog($blog_id);
		                require self::ROOT_DIR . '/schema.php';
		                dbDelta($plugin_queries);
		                $this->__ver_1_04_transition_fix();

						// sync data between plugin tables and wordpress (mostly for the case when plugin is reactivated)
						
						$post = new PMXI_Post_Record();
						$wpdb->query('DELETE FROM ' . $post->getTable() . ' WHERE post_id NOT IN (SELECT ID FROM ' . $wpdb->posts . ')');
		            }
		            switch_to_blog($old_blog);
		            return;	         
		        }	         
		    }

			dbDelta($plugin_queries);

			$this->__ver_1_04_transition_fix();

			// sync data between plugin tables and wordpress (mostly for the case when plugin is reactivated)
			
			$post = new PMXI_Post_Record();
			$wpdb->query('DELETE FROM ' . $post->getTable() . ' WHERE post_id NOT IN (SELECT ID FROM ' . $wpdb->posts . ')');

		}

		/**
		 * Load Localisation files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present
		 *
		 * @access public
		 * @return void
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'pmxi_plugin' );							
			
			load_plugin_textdomain( 'pmxi_plugin', false, dirname( plugin_basename( __FILE__ ) ) . "/i18n/languages" );
		}

		/**
		 * Method perfoms transition from version when file uploads has been stored in dabase to the solution when it stored on disk
		 * NOTE: the function can be removed when plugin version progress and it's sure matter nobody has ver 1.03
		 */
		public function __ver_1_04_transition_fix() {
			$uploads = wp_upload_dir();

			if ( ! is_dir($uploads['basedir'] . '/wpallimport_history') or ! is_writable($uploads['basedir'] . '/wpallimport_history')) {
				die(sprintf(__('Uploads folder %s must be writable', 'pmxi_plugin'), $uploads['basedir'] . '/wpallimport_history'));
			}

			if ( ! is_dir($uploads['basedir'] . '/wpallimport_logs') or ! is_writable($uploads['basedir'] . '/wpallimport_logs')) {
				die(sprintf(__('Uploads folder %s must be writable', 'pmxi_plugin'), $uploads['basedir'] . '/wpallimport_logs'));
			}

			$table = $table = $this->getTablePrefix() . 'files';
			global $wpdb;
			$tablefields = $wpdb->get_results("DESCRIBE {$table};");
			// For every field in the table
			foreach ($tablefields as $tablefield) {
				if ('contents' == $tablefield->Field) {
					$list = new PMXI_File_List();
					for ($i = 1; $list->getBy(NULL, 'id', $i, 1)->count(); $i++) {
						foreach ($list->convertRecords() as $file) {
							$file->save(); // resave file for file to be stored in uploads folder
						}
					}

					$wpdb->query("ALTER TABLE {$table} DROP " . $tablefield->Field);
					break;
				}
			}
		}

		public function __add_feed_type_fix(){

			$table = $this->getTablePrefix() . 'imports';
			global $wpdb;
			$tablefields = $wpdb->get_results("DESCRIBE {$table};");
			$large_import = false;
			$root_element = false;
			$processing = false;
			$triggered = false;
			$queue_chunk_number = false;
			$current_post_ids = false;
			$first_import = false;
			$count = false;
			$friendly_name = false;
			$imported = false;
			$created = false;
			$updated = false;
			$skipped = false;
			$fix_characters = false;
			$feed_type = false;

			// Check if field exists
			foreach ($tablefields as $tablefield) {
				if ('feed_type' == $tablefield->Field) $feed_type = true;
				if ('large_import' == $tablefield->Field) $large_import = true;
				if ('root_element' == $tablefield->Field) $root_element = true;
				if ('processing' == $tablefield->Field) $processing = true;
				if ('triggered' == $tablefield->Field) $triggered = true;
				if ('current_post_ids' == $tablefield->Field) $current_post_ids = true;
				if ('queue_chunk_number' == $tablefield->Field) $queue_chunk_number = true;
				if ('first_import' == $tablefield->Field) $first_import = true;
				if ('count' == $tablefield->Field) $count = true;
				if ('friendly_name' == $tablefield->Field) $friendly_name = true;
				if ('imported' == $tablefield->Field) $imported = true;
				if ('created' == $tablefield->Field) $created = true;
				if ('updated' == $tablefield->Field) $updated = true;
				if ('skipped' == $tablefield->Field) $skipped = true;			
			}
			if ($feed_type) $wpdb->query("ALTER TABLE {$table} CHANGE `feed_type` `feed_type` ENUM( 'xml', 'csv', 'zip', 'gz', '' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");					
			if (!$feed_type) $wpdb->query("ALTER TABLE {$table} ADD `feed_type` ENUM( 'xml','csv','zip','gz','' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
			if (!$large_import) $wpdb->query("ALTER TABLE {$table} ADD `large_import` ENUM( 'Yes', 'No' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'No';");
			if (!$root_element) $wpdb->query("ALTER TABLE {$table} ADD `root_element` VARCHAR(255) NOT NULL DEFAULT '';");
			if (!$processing) $wpdb->query("ALTER TABLE {$table} ADD `processing` BOOL NOT NULL DEFAULT 0;");
			if (!$triggered) $wpdb->query("ALTER TABLE {$table} ADD `triggered` BOOL NOT NULL DEFAULT 0;");
			if (!$queue_chunk_number) $wpdb->query("ALTER TABLE {$table} ADD `queue_chunk_number` BIGINT(20) NOT NULL DEFAULT 0;");
			if (!$current_post_ids) $wpdb->query("ALTER TABLE {$table} ADD `current_post_ids` TEXT;");
			if (!$first_import) $wpdb->query("ALTER TABLE {$table} ADD `first_import` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");
			if (!$count) $wpdb->query("ALTER TABLE {$table} ADD `count` BIGINT(20) NOT NULL DEFAULT 0;");
			if (!$imported) $wpdb->query("ALTER TABLE {$table} ADD `imported` BIGINT(20) NOT NULL DEFAULT 0;");
			if (!$created) $wpdb->query("ALTER TABLE {$table} ADD `created` BIGINT(20) NOT NULL DEFAULT 0;");
			if (!$updated) $wpdb->query("ALTER TABLE {$table} ADD `updated` BIGINT(20) NOT NULL DEFAULT 0;");
			if (!$skipped) $wpdb->query("ALTER TABLE {$table} ADD `skipped` BIGINT(20) NOT NULL DEFAULT 0;");
			if (!$friendly_name) $wpdb->query("ALTER TABLE {$table} ADD `friendly_name` VARCHAR(255) NOT NULL DEFAULT '';");

			$table = $this->getTablePrefix() . 'templates';
			global $wpdb;
			$tablefields = $wpdb->get_results("DESCRIBE {$table};");
			$is_leave_html = false;
			// Check if field exists
			foreach ($tablefields as $tablefield) {
				if ('is_leave_html' == $tablefield->Field) $is_leave_html = true;
				if ('fix_characters' == $tablefield->Field) $fix_characters = true;
			}
			if (!$is_leave_html) $wpdb->query("ALTER TABLE {$table} ADD `is_leave_html` TINYINT( 1 ) NOT NULL DEFAULT 0;");
			if (!$fix_characters) $wpdb->query("ALTER TABLE {$table} ADD `fix_characters` TINYINT( 1 ) NOT NULL DEFAULT 0;");

			$table = $this->getTablePrefix() . 'posts';
			global $wpdb;
			$tablefields = $wpdb->get_results("DESCRIBE {$table};");
			$product_key = false;
			// Check if field exists
			foreach ($tablefields as $tablefield) {
				if ('product_key' == $tablefield->Field) $product_key = true;			
			}
			if (!$product_key) $wpdb->query("ALTER TABLE {$table} ADD `product_key` TEXT NOT NULL DEFAULT '';");

		}

		/**
		 * Method returns default import options, main utility of the method is to avoid warnings when new
		 * option is introduced but already registered imports don't have it
		 */
		public static function get_default_import_options() {
			return array(
				'type' => 'post',
				'custom_type' => '',
				'categories' => '',
				'tags' => '',
				'tags_delim' => ',',
				'categories_delim' => ',',
				'categories_auto_nested' => 0,
				'featured_delim' => ',',
				'atch_delim' => ',',
				'post_taxonomies' => array(),
				'parent' => '',
				'order' => '0',
				'status' => 'publish',
				'page_template' => 'default',
				'page_taxonomies' => array(),
				'date_type' => 'specific',
				'date' => 'now',
				'date_start' => 'now',
				'date_end' => 'now',
				'custom_name' => array(),
				'custom_value' => array(),
				'comment_status' => 'open',
				'ping_status' => 'open',
				'create_draft' => 'no',
				'author' => '',
				'featured_image' => '',
				'attachments' => '',
				'is_import_specified' => 0,
				'import_specified' => '',
				'is_delete_source' => 0,
				'is_cloak' => 0,
				'unique_key' => '',
				'feed_type' => 'auto',

				'is_delete_missing' => 0,
				'is_update_missing_cf' => 0,
				'is_keep_former_posts' => 'no',
				'is_keep_status' => 0,
				'is_keep_content' => 0,
				'is_keep_title' => 0,
				'is_keep_excerpt' => 0,
				'is_keep_categories' => 0,
				'is_keep_attachments' => 0,
				'is_keep_images' => 0,
				'is_duplicates' => 0,
				'is_keep_dates' => 0,
				'is_keep_menu_order' => 0,
				'is_keep_parent' => 0,
				'is_keep_attachments_on_update' => 0,
				'records_per_request' => 10,
				'not_create_records' => 0,
				'no_create_featured_image' => 0,

				'duplicate_indicator' => 'title',
				'duplicate_action' => 'keep',
				'is_update_previous' => 0,
				'is_scheduled' => '',
				'scheduled_period' => '',
				'post_excerpt' => '',
				'post_slug' => '',
				'keep_custom_fields' => 0,
				'keep_custom_fields_specific' => '',
				'friendly_name' => '',
				'custom_duplicate_name' => '',
				'custom_duplicate_value' => '',
				'duplicate_matching' => 'auto',
				'create_chunks' => 0,
				'update_missing_cf_name' => '',
				'update_missing_cf_value' => '',
				'auto_rename_images' => 0,
				'auto_rename_images_suffix' => '',
				'images_name' => 'auto',
				'is_add_newest_categories' => 0
			);
		}

		/*
		 * Convert csv to xml using yahoo API
		 */
		public static function csv_to_xml($csv_url){

			include_once(self::ROOT_DIR.'/libraries/XmlImportCsvParse.php');

			$csv = new PMXI_CsvParser($csv_url);

			$wp_uploads = wp_upload_dir();
			$tmpname = wp_unique_filename($wp_uploads['path'], str_replace("csv", "xml", basename($csv_url)));
			$xml_file = $wp_uploads['path']  .'/'. $tmpname;
			file_put_contents($xml_file, $csv->toXML());
			return $xml_file;

		}

		public static function is_ajax(){
			return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false ;
		}

	}

	PMXI_Plugin::getInstance();

	new PMXI_Updater( 'http://wp-updates.com/api/1/plugin', 185, plugin_basename(__FILE__) );
}