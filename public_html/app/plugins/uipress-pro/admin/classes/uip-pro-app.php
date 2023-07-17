<?php
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Main uipress class. Loads scripts and styles and builds the main admin framework
 * @since 3.0.0
 */
class uip_pro_app
{
  public function __construct()
  {
  }

  /**
   * Starts uipress functions
   * @since 3.0.0
   */
  public function run()
  {
    add_action('plugins_loaded', [$this, 'start_uipress_pro_app'], 1);
    add_filter('plugin_action_links_uipress-pro/uipress-pro.php', [$this, 'add_builder_link_to_plugin_settings']);
    add_action('init', [$this, 'create_folder_cpt']);
    //Listen for front
    add_action('uip_trigger_pro_front', [$this, 'load_pro_front']);
    add_action('uip_import_pro_front', [$this, 'add_head_scripts']);
    //Constants
    add_action('admin_head', [$this, 'add_head_constants']);
  }

  /**
   * Creates Ui Builder posts type
   * @since 3.0.0
   */
  public function create_folder_cpt()
  {
    $labels = [
      'name' => _x('Content Folder', 'post type general name', 'uipress-lite'),
      'singular_name' => _x('Content Folder', 'post type singular name', 'uipress-lite'),
      'menu_name' => _x('Content Folders', 'admin menu', 'uipress-lite'),
      'name_admin_bar' => _x('Content Folder', 'add new on admin bar', 'uipress-lite'),
      'add_new' => _x('Add New', 'Template', 'uipress-lite'),
      'add_new_item' => __('Add New Content Folder', 'uipress-lite'),
      'new_item' => __('New Content Folder', 'uipress-lite'),
      'edit_item' => __('Edit Content Folders', 'uipress-lite'),
      'view_item' => __('View Content Folders', 'uipress-lite'),
      'all_items' => __('All Content Folders', 'uipress-lite'),
      'search_items' => __('Search Content Folders', 'uipress-lite'),
      'not_found' => __('No Content Folders found.', 'uipress-lite'),
      'not_found_in_trash' => __('No Content Folders found in Trash.', 'uipress-lite'),
    ];
    $args = [
      'labels' => $labels,
      'description' => __('Post type used for the uipress uipress folders', 'uipress-lite'),
      'public' => false,
      'publicly_queryable' => false,
      'show_ui' => false,
      'show_in_menu' => false,
      'query_var' => false,
      'has_archive' => false,
      'hierarchical' => false,
      'supports' => ['title'],
      'show_in_rest' => true,
    ];
    register_post_type('uip-ui-folder', $args);
  }

  /**
   * Loads required scripts on frontend
   * @since 3.0.97
   */
  public function load_pro_front()
  {
    add_action('wp_enqueue_scripts', [$this, 'add_scripts_and_styles']);
  }
  /**
   * Adds a link to the uiBuilder from the plugins tables
   * @since 3.0.0
   */
  public function add_builder_link_to_plugin_settings($links)
  {
    // Build and escape the URL.
    $url = esc_url(add_query_arg('page', 'uip-ui-builder', get_admin_url() . 'options-general.php'));
    // Create the link.
    $settings_link = "<a href='$url'>" . __('uiBuilder', 'uipress-lite') . '</a>';
    // Adds the link to the end of the array.
    array_push($links, $settings_link);
    return $links;
  }
  /**
   * Adds required actions and filters depending if we are on admin page, login page or uipress framed page
   * @since 3.0.0
   */
  public function start_uipress_pro_app()
  {
    //Checks to ensure uipress lite is installed and active
    $status = $this->check_for_uipress_lite();
    if ($status) {
      return;
    }

    add_action('admin_init', [$this, 'load_core_actions'], 1);
  }

  /**
   * Checks if older uipress lite is installed, if it isn't stop the plugin because we need it
   * @since 3.0.0
   */
  public function check_for_uipress_lite()
  {
    if (!function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();

    if (!isset($all_plugins['uipress-lite/uipress-lite.php'])) {
      define('uip_stop_plugin', true);
      add_action('admin_head', [$this, 'flag_uipress_lite_error']);
      return true;
    }
    if (!is_plugin_active('uipress-lite/uipress-lite.php')) {
      define('uip_stop_plugin', true);
      add_action('admin_head', [$this, 'flag_uipress_lite_error']);
      return true;
    }
    define('uip_stop_pro_plugin', false);
    return false;
  }

  /**
   * Outputs error if no uipress
   * @since 1.0
   */
  public function flag_uipress_lite_error()
  {
    $class = 'notice notice-error';
    $message = __('UiPress Pro requires UiPress lite to be installed before activating', 'uipress-pro');

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
  }

  /**
   * Loads uipress pro functions
   * @since 3.0.0
   */
  public function load_core_actions()
  {
    //check if a template is active or if we are on the builder page. Both cases require us to load up pro components.
    $pageNow = '';
    if (isset($_GET['page'])) {
      $pageNow = $_GET['page'];
    }

    $adminpage = false;
    if (defined('uip_admin_page')) {
      if (uip_admin_page) {
        $adminpage = true;
      }
    }

    if (uip_app_running || $pageNow == uip_plugin_shortname . '-ui-builder' || $adminpage) {
      add_action('admin_head', [$this, 'add_head_scripts'], 2);
      add_action('admin_enqueue_scripts', [$this, 'add_scripts_and_styles']);
    }
  }

  /**
   * Adds scripts to head
   * @since 3.0.0
   */

  public function add_head_constants()
  {
    //Output the path to uipress lite
    $variableFormatter = 'const uipProVersion = "' . esc_html(uip_pro_plugin_version) . '";';
    wp_print_inline_script_tag($variableFormatter, ['id' => 'uip-pro-constants']);
  }
  /**
   * Adds scripts to head
   * @since 3.0.0
   */

  public function add_head_scripts()
  {
    //Output the path to uipress lite
    $classPath = 'assets/js/uip/classes/uip.min.js';
    $variableFormatter =
      'const uipLitePath = "' .
      esc_url(uip_plugin_url) .
      '";
    const uipProPath = "' .
      esc_url(uip_pro_plugin_url) .
      '";';
    wp_print_inline_script_tag($variableFormatter, ['id' => 'uip-format-vars']);

    wp_print_script_tag([
      'id' => 'uip-pro-app-js',
      'src' => uip_pro_plugin_url . 'assets/js/uip/uip-pro-app.min.js?ver=' . uip_pro_plugin_version,
      'type' => 'module',
    ]);
  }

  /**
   * Loads required scripts and styles for uipress pro
   * @since 3.0.0
   */
  public function add_scripts_and_styles()
  {
    //Loads translator
    wp_enqueue_script('uip-pro-translations', uip_pro_plugin_url . 'assets/js/uip/uip-pro-translations.min.js', ['wp-i18n'], uip_pro_plugin_version);
    wp_set_script_translations('uip-pro-translations', 'uipress-pro', dirname(dirname(plugin_dir_path(__FILE__))) . '/languages/');
    //Import date picker
    wp_enqueue_script('uip-date-picker', uip_pro_plugin_url . 'assets/js/libs/easepick.min.js', [], uip_pro_plugin_version);
  }
}
