<?php
if (!defined('ABSPATH')) {
  exit();
}

class uipress_menu_creator extends uip_app
{
  public function __construct()
  {
    $this->uipMasterMenu = [];
    $this->discoveredItems = [];
  }

  /**
   * Loads menu editor actions
   * @since 1.0
   */

  public function run()
  {
    ///REGISTER THIS COMPONENT
    add_filter('uipress_register_settings', [$this, 'menu_creator_settings_options'], 1, 2);

    if (function_exists('is_network_admin')) {
      if (is_network_admin()) {
        return;
      }
    }

    add_action('admin_menu', [$this, 'add_menu_item']);
    add_action('init', [$this, 'uipress_create_menu_cpt'], 0);

    if (isset($_GET['page'])) {
      if ($_GET['page'] == uip_plugin_shortname . '-menu-creator') {
        add_action('admin_head', [$this, 'add_head_scripts'], 1);
        add_action('admin_enqueue_scripts', [$this, 'add_scripts']);
        add_action('admin_menu', [$this, 'capture_wp_menu_for_editor'], 998);
        add_action('admin_footer', [$this, 'add_footer_scripts'], 0);
      }
    }

    add_action('admin_menu', [$this, 'fetch_custom_menu'], 999);

    //Ajax
    add_action('wp_ajax_uipress_get_menus', [$this, 'uipress_get_menus']);
    add_action('wp_ajax_uip_create_new_admin_menu', [$this, 'uip_create_new_admin_menu']);
    add_action('wp_ajax_uip_update_menu_status', [$this, 'uip_update_menu_status']);
    add_action('wp_ajax_uip_delete_admin_menus', [$this, 'uip_delete_admin_menus']);
    add_action('wp_ajax_uipress_save_menu', [$this, 'uipress_save_menu']);
    add_action('wp_ajax_uipress_get_menu', [$this, 'uipress_get_menu']);
    add_action('wp_ajax_uipress_get_menus_for_export', [$this, 'uipress_get_menus_for_export']);
    add_action('wp_ajax_uipress_save_menus_from_import', [$this, 'uipress_save_menus_from_import']);
    add_action('wp_ajax_uip_duplicate_admin_menu', [$this, 'uip_duplicate_admin_menu']);
  }

  /**
   * Gets wordpress menu and process it, stores in variable for front end app
   * @since 3.0.9
   */
  public function fetch_custom_menu()
  {
    $current_user = wp_get_current_user();
    $id = $current_user->ID;

    $username = $current_user->user_login;

    $roles = [];
    if ($id == 1) {
      $roles[] = 'Super Admin';
    }

    //Get current roles
    $user = new WP_User($id);

    if (!empty($user->roles) && is_array($user->roles)) {
      foreach ($user->roles as $role) {
        $roles[] = $role;
      }
    }

    $idAsString = strval($id);

    //Fetch templates from primary multsite installation Multisite
    $multiSiteActive = false;
    if (is_multisite() && is_plugin_active_for_network(uip_plugin_path_name . '/uipress-lite.php') && !is_main_site()) {
      $mainSiteId = get_main_site_id();
      switch_to_blog($mainSiteId);
      $multiSiteActive = true;
    }

    //Loop through roles and build query
    $roleQuery = [];
    $roleQuery['relation'] = 'AND';
    //Check user id is not excluded
    $roleQuery[] = [
      'key' => 'uip-menu-excludes-users',
      'value' => serialize($idAsString),
      'compare' => 'NOT LIKE',
    ];
    //Check rolename is not excluded
    foreach ($roles as $role) {
      $roleQuery[] = [
        'key' => 'uip-menu-excludes-roles',
        'value' => serialize($role),
        'compare' => 'NOT LIKE',
      ];
    }

    ////Multisite Only///
    ////Push a check to see if the template is multisite enabled
    ////Multisite only///
    if ($multiSiteActive) {
      $roleQuery[] = [
        'key' => 'uip-menu-subsites',
        'value' => 'uiptrue',
        'compare' => '==',
      ];
    }
    //Check at least one option (roles or users) has a value
    $secondLevel = [];
    $secondLevel['relation'] = 'OR';
    $secondLevel[] = [
      'key' => 'uip-menu-for-users',
      'value' => serialize([]),
      'compare' => '!=',
    ];
    $secondLevel[] = [
      'key' => 'uip-menu-for-roles',
      'value' => serialize([]),
      'compare' => '!=',
    ];

    //Check user if user id is in selected
    $thirdLevel = [];
    $thirdLevel['relation'] = 'OR';
    $thirdLevel[] = [
      'key' => 'uip-menu-for-users',
      'value' => serialize($idAsString),
      'compare' => 'LIKE',
    ];

    foreach ($roles as $role) {
      $thirdLevel[] = [
        'key' => 'uip-menu-for-roles',
        'value' => serialize($role),
        'compare' => 'LIKE',
      ];
    }

    //Push to meta query
    $roleQuery[] = $secondLevel;
    $roleQuery[] = $thirdLevel;

    //Build query
    $args = [
      'post_type' => 'uip-admin-menu',
      'posts_per_page' => 1,
      'post_status' => 'publish',
      'meta_query' => $roleQuery,
    ];

    $query = new WP_Query($args);
    $totalFound = $query->found_posts;
    $foundPosts = $query->get_posts();

    if ($totalFound > 0) {
      $menuID = $foundPosts[0]->ID;
      $this->capture_wp_menu_for_editor();
      $this->apply_custom_menu($menuID);
      if ($multiSiteActive) {
        restore_current_blog();
      }
      add_action('adminmenu', function () {
        ?>
        <style>
          .wp-submenu .wp-menu-separator {
            height: auto !important;
            margin: 0 !important;
            margin-top: 8px !important;
            font-weight: bold !important;
          }
          .wp-submenu .wp-menu-separator a{
            cursor: default !important;
            pointer-events: none;
          }
          .wp-submenu .wp-menu-separator a:focus, .wp-submenu .wp-menu-separator a:hover, .wp-submenu .wp-menu-separator a:hover, .wp-submenu .wp-menu-separator>a:focus{
            box-shadow: none !important;
            color: inherit !important;
          }
        </style>
        <?php
      });
    } else {
      if ($multiSiteActive) {
        restore_current_blog();
      }
    }
  }

  /**
   * Applies a custom menu to the menu
   * @since 3.0.8
   */
  public function apply_custom_menu($menuID)
  {
    global $menu, $submenu;

    $ogMenuItems = $menu;
    $formattedMenu = $this->uipMasterMenu;
    $customMenuSettings = get_post_meta($menuID, 'uip_menu_settings', true);
    $customMenu = $customMenuSettings->menu;
    $returnMenu = [];
    $returnSubMenu = [];
    //Holders
    $allCustom = [];
    $allCustomSub = [];

    $allItems = [];
    //Loop through og menu and fetch all items
    foreach ($formattedMenu['menu'] as $item) {
      $allItems[] = $item;
    }
    //Loop submenus
    foreach ($formattedMenu['submenu'] as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $sub) {
          $allItems[] = $sub;
        }
      }
    }
    //Loop top level items to check for custom urls in custom items so we can update submenu
    foreach ($customMenu->menu as $item) {
      if (!property_exists($item, 'customItem') || $item->customItem != 'uiptrue') {
        continue;
      }
      //Check for custom url
      if (property_exists($item->custom, 'url') && $item->custom->url != '' && $item->custom->url != 'uipblank') {
        $key = $item->{2};
        if (isset($customMenu->submenu->{$key})) {
          $customMenu->submenu->{$item->custom->url} = $customMenu->submenu->{$key};
          unset($customMenu->submenu->{$key});
        }
      }
    }
    //Loop top level custom menu
    foreach ($customMenu->menu as $item) {
      $allCustom[] = $item;
      $processed = $this->process_menu_item($item, $allItems, $customMenuSettings, false);
      if ($processed) {
        $returnMenu[] = $processed;
      }
    }
    //Loop sub level custom menu
    foreach ($customMenu->submenu as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $sub) {
          $allCustom[] = $sub;
          $allCustomSub[] = $sub;
          $processed = $this->process_menu_item($sub, $allItems, $customMenuSettings, true);
          if ($processed) {
            if (!isset($returnSubMenu[$key])) {
              $returnSubMenu[$key] = [];
            }
            $returnSubMenu[$key][] = $processed;
          }
        }
      }
    }

    //Loop over original menu and see if new items have been added
    if ($customMenuSettings->autoUpdate == 'uiptrue') {
      //Top level items
      foreach ($ogMenuItems as $item) {
        $itemOBJ = (object) $item;
        $foundIndex = array_search($itemOBJ->uip_uid, array_column($allCustom, 'uip_uid'));

        //Item not found
        if (!$foundIndex || !is_numeric($foundIndex)) {
          $processed = $this->process_menu_item($itemOBJ, $allItems, $customMenuSettings, false);
          $returnMenu[] = $processed;
        }
      }
      //Loop over subitems
      foreach ($submenu as $key => $value) {
        if (is_array($value)) {
          foreach ($value as $item) {
            $itemOBJ = (object) $item;
            $foundIndex = array_search($itemOBJ->uip_uid, array_column($allCustom, 'uip_uid'));

            //Item not found
            if (!$foundIndex || !is_numeric($foundIndex)) {
              $processed = $this->process_menu_item($itemOBJ, $allItems, $customMenuSettings, false);
              if (!isset($returnSubMenu[$key])) {
                $returnSubMenu[$key] = [];
              }
              $returnSubMenu[$key][] = $processed;
            }
          }
        }
      }
    }

    //echo '<script>console.log(' . json_encode($this->discoveredItems) . ');</script>';
    //echo '<script>console.log(' . json_encode($allItems) . ');</script>';
    $menu = $returnMenu;
    $submenu = $returnSubMenu;
    return;
  }

  /**
   * Processes a singular menu item
   * @since 2.2
   */
  public function process_menu_item($item, $allItems, $customMenuSettings, $currentIsSub)
  {
    //Search list of original items to update against
    $ogItem = array_search($item->uip_uid, array_column($allItems, 'uip_uid'));

    if (is_numeric($ogItem)) {
      isset($allItems[$ogItem][0]) ? ($item->{0} = $allItems[$ogItem][0]) : false;
      isset($allItems[$ogItem][1]) ? ($item->{1} = $allItems[$ogItem][1]) : false;
      isset($allItems[$ogItem][2]) ? ($item->{2} = $allItems[$ogItem][2]) : false;
      isset($allItems[$ogItem][3]) ? ($item->{3} = $allItems[$ogItem][3]) : false;
      isset($allItems[$ogItem][4]) ? ($item->{4} = $allItems[$ogItem][4]) : false;
      isset($allItems[$ogItem][5]) ? ($item->{5} = $allItems[$ogItem][5]) : false;
      isset($allItems[$ogItem][6]) ? ($item->{6} = $allItems[$ogItem][6]) : false;
    } else {
      //Remove item if auto update is on and the original item can't be found
      if ($customMenuSettings->autoUpdate == 'uiptrue') {
        //Make sure item is not custom first
        if (!property_exists($item, 'customItem')) {
          remove_menu_page($item->{2});
          return false;
        }
      }
    }

    //Check if item is hidden
    if (property_exists($item->custom, 'hidden') && $item->custom->hidden == 'uiptrue') {
      remove_menu_page($item->{2});
      return false;
    }

    //Update name
    if (property_exists($item->custom, 'name') && $item->custom->name != '') {
      $item->{0} = $item->custom->name;
    }

    //Check for custom icon
    if (property_exists($item->custom, 'icon')) {
      if ($item->custom->icon && $item->custom->icon != '' && $item->custom->icon != 'uipblank') {
        $item->{6} = "dashicons-uip-icon-{$item->custom->icon}";
      }
    }

    //Check for custom classes
    if (property_exists($item->custom, 'classes') && $item->custom->classes != '' && $item->custom->classes != 'uipblank') {
      $item->{4} .= ' ' . $item->custom->classes;
    }

    //Check for custom url
    if (property_exists($item->custom, 'url') && $item->custom->url != '' && $item->custom->url != 'uipblank') {
      $item->{2} = $item->custom->url;
    }

    //Check for custom capability
    if (property_exists($item->custom, 'capabilities') && $item->custom->capabilities != '' && $item->custom->capabilities != 'uipblank') {
      $item->{1} = $item->custom->capabilities;
    }

    if ($item->{0} == 'uipblank') {
      $item->{0} = '';
    }

    //Patch for submenu items added as top levels
    !isset($item->{3}) ? ($item->{3} = '') : false;
    !isset($item->{4}) ? ($item->{4} = '') : false;
    !isset($item->{5}) ? ($item->{5} = $item->{1}) : false;
    !isset($item->{6}) ? ($item->{6} = '') : false;

    if (!$currentIsSub && $item->type != 'sep') {
      $item->{4} .= ' menu-top';
    }

    //Remove item from all items
    if ($customMenuSettings->autoUpdate == 'uiptrue') {
      unset($allItems[$ogItem]);
    }

    return (array) $item;
  }

  /**
   * Gets wordpress menu and process it, stores in variable for front end app
   * @since 2.2
   */
  public function capture_wp_menu_for_editor()
  {
    $utils = new uip_util();
    ///CHECK FOR CUSTOM MENU FIRST

    ///NO CUSTOM MENU SO PREPARE DEFAULT MENU
    global $menu, $submenu;
    //CREATE MENU CONSTRUCTOR OBJECT
    $newMenu = [];
    $newSubmenu = [];

    foreach ($menu as $key => $item) {
      //Check if this item is a separator
      $item['order'] = $key;
      $item['type'] = 'item';
      if (isset($item[4])) {
        if ($item[4] != '' && strpos($item[4], 'wp-menu-separator') !== false) {
          $item['type'] = 'sep';
        }
      }

      if ($item['type'] == 'sep') {
        $item['uip_uid'] = hash('ripemd160', $item[2] . $item[4]);
      } else {
        $item['uip_uid'] = hash('ripemd160', $item[2] . $item[5]);
      }

      //Strip name from html
      $title = wptexturize($item[0]);
      $nameParts = explode('<', $item[0]);
      $item['cleanName'] = $nameParts[0];
      $item['custom'] = new stdClass();

      $newMenu[] = $item;
    }
    //Loop submenus
    foreach ($submenu as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $sub) {
          if (!isset($newSubmenu[$key])) {
            $newSubmenu[$key] = [];
          }

          //Check if this item is a separator
          $sub['type'] = 'item';
          if (isset($sub[4])) {
            if ($sub[4] != '' && strpos($sub[4], 'wp-menu-separator') !== false) {
              $sub['type'] = 'sep';
            }
          }

          if ($sub['type'] == 'sep') {
            $sub['uip_uid'] = hash('ripemd160', $sub[2] . $sub[4]);
          } else {
            $sub['uip_uid'] = hash('ripemd160', $sub[1] . $sub[2]);
          }

          //Strip name from html
          $title = wptexturize($sub[0]);
          $nameParts = explode('<', $sub[0]);
          $sub['cleanName'] = $nameParts[0];
          $sub['custom'] = new stdClass();

          $newSubmenu[$key][] = $sub;
        }
      }
    }

    usort($newMenu, function ($a, $b) {
      return $a['order'] <=> $b['order'];
    });

    //echo '<script>console.log(' . json_encode($newMenu) . ');</script>';

    $menu = $newMenu;
    $submenu = $newSubmenu;

    //echo '<script>console.log(' . json_encode($newSubmenu) . ');</script>';
    $mastermenu['menu'] = $menu;
    $mastermenu['submenu'] = $submenu;

    $this->uipMasterMenu = $mastermenu;
  }

  /**
   * Adds scripts to footer
   * @since 3.1.0
   */

  public function add_footer_scripts()
  {
    $utils = new uip_util();
    //Check if the main app is running, if it is then we don't need to re-add ajax and required script data

    $menu = $this->uipMasterMenu;

    $menuString = json_encode($utils->clean_ajax_input_width_code($menu));
    if (!$menuString) {
      $menu = [];
      $menu['menu'] = [];
      $menu['submenu'] = [];
    }

    if (!uip_app_running) {
      $variableFormatter = "
      var uipMenuBuilderMenu = {$menuString};";
      wp_print_inline_script_tag($variableFormatter, ['id' => 'uip-master-app']);
    }

    if (!uip_app_running) {
      $variableFormatter = "
      let ajaxHolder = document.getElementById('uip-app-data');
      let ajaxData = ajaxHolder.getAttribute('uip_ajax');
      const uip_ajax = JSON.parse(ajaxData, (k, v) => (v === 'uiptrue' ? true : v === 'uipfalse' ? false : v === 'uipblank' ? '' : v));";

      wp_print_script_tag([
        'id' => 'uip-app-data',
        'uip_ajax' => json_encode([
          'ajax_url' => admin_url('admin-ajax.php'),
          'security' => wp_create_nonce('uip-security-nonce'),
          'uipAppData' => [
            'options' => $utils->clean_ajax_input_width_code([]),
            'userPrefs' => $utils->clean_ajax_input_width_code([]),
          ],
        ]),
      ]);
      wp_print_inline_script_tag($variableFormatter, ['id' => 'uip-menu-creator-format-vars']);
    }

    wp_print_script_tag([
      'id' => 'uip-menu-creator-js',
      'src' => uip_pro_plugin_url . 'admin/extensions/menu-creator/js/menu-creator.min.js?ver=' . uip_pro_plugin_version,
      'type' => 'module',
    ]);
  }

  /**
   * Gets menu icon
   * @since 2.2
   */

  public function get_icon($menu_item)
  {
    /// LIST OF AVAILABLE MENU ICONS
    $icons = [
      'dashicons-dashboard' => 'grid_view',
      'dashicons-admin-post' => 'article',
      'dashicons-database' => 'perm_media',
      'dashicons-admin-media' => 'collections',
      'dashicons-admin-page' => 'description',
      'dashicons-admin-comments' => 'forum',
      'dashicons-admin-appearance' => 'palette',
      'dashicons-admin-plugins' => 'extension',
      'dashicons-admin-users' => 'people',
      'dashicons-admin-tools' => 'build_circle',
      'dashicons-chart-bar' => 'analytics',
      'dashicons-admin-settings' => 'tune',
    ];

    // SET MENU ICON
    $theicon = '';
    $wpicon = $menu_item[6];

    if (isset($menu_item['icon'])) {
      if ($menu_item['icon'] != '') {
        ob_start(); ?><span class="uk-icon-button" uk-icon="icon:<?php echo $menu_item['icon']; ?>;ratio:0.8"></span><?php return ob_get_clean();
      }
    }

    if (isset($icons[$wpicon])) {
      //ICON IS SET BY ADMIN 2020
      ob_start(); ?><span class="material-icons-outlined"><?php echo $icons[$wpicon]; ?></span><?php return ob_get_clean();
    }

    if (!$theicon) {
      if (strpos($wpicon, 'http') !== false || strpos($wpicon, 'data:') !== false) {
        ///ICON IS IMAGE
        ob_start(); ?><span class="uip-icon-image uip-background-muted uip-border-round uip-h-18 uip-w-18" style="background-image: url(<?php echo $wpicon; ?>);"></span><?php return ob_get_clean();
      } else {
        ///ICON IS ::BEFORE ELEMENT
        ob_start(); ?><div class="wp-menu-image dashicons-before <?php echo $wpicon; ?> uip-background-muted uip-border-round uip-h-18 uip-w-18 uip-icon-image"></div><?php return ob_get_clean();
      }
    }
  }

  /**
   * Returns settings options for settings page
   * @since 2.2
   */
  public function menu_creator_settings_options($settings, $network)
  {
    $utils = new uipress_util();

    ///////FOLDER OPTIONS
    $moduleName = 'menu-creator';
    $category = [];
    $options = [];
    //
    $category['module_name'] = $moduleName;
    $category['label'] = __('Menu Creator', 'uipress-pro');
    $category['description'] = __('Creates custom admin menus.', 'uipress-pro');
    $category['icon'] = 'segment';

    $temp = [];
    $temp['name'] = __('Disable Menu Creator?', 'uipress-pro');
    $temp['description'] = __('If disabled, the menu creator will not be available to any users.', 'uipress-pro');
    $temp['type'] = 'switch';
    $temp['optionName'] = 'status';
    $temp['value'] = $utils->get_option($moduleName, $temp['optionName']);
    $options[$temp['optionName']] = $temp;

    $category['options'] = $options;
    $settings[$moduleName] = $category;

    return $settings;
  }

  public function deliver_custom_menu($menu)
  {
    $custommenu = false;
    $mainSiteId = false;
    $multisiteMenu = false;

    if (!is_main_site() && is_multisite()) {
      $multisiteMenu = $this->get_multisite_menus();

      if ($multisiteMenu != false) {
        return $multisiteMenu;
      }
    }

    $args = [
      'post_type' => 'uip-admin-menu',
      'post_status' => 'publish',
      'numberposts' => -1,
      'meta_query' => [
        [
          'key' => 'status',
          'value' => 'true',
          'compare' => '=',
        ],
      ],
    ];

    $menus = get_posts($args);

    foreach ($menus as $menu) {
      $temp = [];
      $temp['id'] = $menu->ID;
      $temp['items'] = get_post_meta($menu->ID, 'items', true);
      $temp['subsites'] = get_post_meta($menu->ID, 'subsites', true);
      $temp['status'] = get_post_meta($menu->ID, 'status', true);
      $temp['roleMode'] = get_post_meta($menu->ID, 'role_mode', true);
      $temp['appliedTo'] = get_post_meta($menu->ID, 'applied_to', true);

      if ($temp['status'] == 'false') {
        continue;
      }

      $status = false;

      if (is_array($temp['appliedTo']) && count($temp['appliedTo']) > 0) {
        $status = $this->menu_valid_for_user($temp['appliedTo'], $temp['roleMode']);
      }

      if ($status && $temp['roleMode'] == 'inclusive') {
        if (is_array($temp['items']) && count($temp['items']) > 0) {
          $custommenu['menu'] = $temp['items'];
          $custommenu['autoUpdate'] = get_post_meta($menu->ID, 'autoUpdate', true);
          $custommenu['availableTop'] = get_post_meta($menu->ID, 'availableTop', true);
          $custommenu['availableSub'] = get_post_meta($menu->ID, 'availableSub', true);
          break;
        }
      }

      if (!$status && $temp['roleMode'] == 'exclusive') {
        if (is_array($temp['items']) && count($temp['items']) > 0) {
          $custommenu['menu'] = $temp['items'];
          $custommenu['autoUpdate'] = get_post_meta($menu->ID, 'autoUpdate', true);
          $custommenu['availableTop'] = get_post_meta($menu->ID, 'availableTop', true);
          $custommenu['availableSub'] = get_post_meta($menu->ID, 'availableSub', true);
          break;
        }
      }
    }

    return json_decode(html_entity_decode(json_encode($custommenu)));
  }

  public function get_multisite_menus()
  {
    //GET USER INFO BEFORE BLOG SWITCH
    $current_user = wp_get_current_user();
    $current_roles = $current_user->roles;
    $current_name = $current_user->display_name;

    ///SWITCH TO PRIMARY BLOG
    $mainSiteId = get_main_site_id();
    switch_to_blog($mainSiteId);
    $custommenu = false;
    $args = [
      'post_type' => 'uip-admin-menu',
      'post_status' => 'publish',
      'numberposts' => -1,
      'meta_query' => [
        [
          'key' => 'status',
          'value' => 'true',
          'compare' => '=',
        ],
      ],
    ];

    $menus = get_posts($args);

    foreach ($menus as $menu) {
      $temp = [];
      $temp['id'] = $menu->ID;
      $temp['items'] = get_post_meta($menu->ID, 'items', true);
      $temp['subsites'] = get_post_meta($menu->ID, 'subsites', true);
      $temp['status'] = get_post_meta($menu->ID, 'status', true);
      $temp['roleMode'] = get_post_meta($menu->ID, 'role_mode', true);
      $temp['appliedTo'] = get_post_meta($menu->ID, 'applied_to', true);

      $status = false;

      if (isset($temp['subsites']) && $temp['subsites'] == 'false') {
        continue;
      }

      if (is_array($temp['appliedTo']) && count($temp['appliedTo']) > 0) {
        $status = $this->menu_valid_for_user_multisite($temp['appliedTo'], $temp['roleMode'], $current_name, $current_roles, $current_user->ID);
      }

      if ($status && $temp['roleMode'] == 'inclusive') {
        if (is_array($temp['items']) && count($temp['items']) > 0) {
          $custommenu = $temp['items'];
          break;
        }
      }

      if (!$status && $temp['roleMode'] == 'exclusive') {
        if (is_array($temp['items']) && count($temp['items']) > 0) {
          $custommenu = $temp['items'];
          break;
        }
      }
    }

    restore_current_blog();

    return $custommenu;
  }

  /**
   * Checks if options apply to user / role for multisite
   * @since 1.4
   */
  public function menu_valid_for_user_multisite($rolesandusernames, $mode, $current_name, $current_roles, $current_user_id)
  {
    if (!function_exists('wp_get_current_user')) {
      return false;
    }

    $formattedroles = [];
    $all_roles = wp_roles()->get_names();

    if (in_array($current_name, $rolesandusernames)) {
      return true;
    }

    ///MULTISITE SUPER ADMIN
    if (is_super_admin() && is_multisite()) {
      if (in_array('Super Admin', $rolesandusernames)) {
        return true;
      } else {
        return false;
      }
    }

    ///NORMAL SUPER ADMIN
    if ($current_user_id === 1) {
      if (in_array('Super Admin', $rolesandusernames)) {
        return true;
      } else {
        return false;
      }
    }

    foreach ($current_roles as $role) {
      $role_name = $all_roles[$role];
      if (in_array($role_name, $rolesandusernames)) {
        return true;
      }
    }
  }

  /**
   * Checks if options apply to user / role
   * @since 1.4
   */

  public function menu_valid_for_user($rolesandusernames, $mode)
  {
    if (!function_exists('wp_get_current_user')) {
      return false;
    }

    $current_user = wp_get_current_user();
    $current_name = $current_user->display_name;
    $current_roles = $current_user->roles;

    $formattedroles = [];
    $all_roles = wp_roles()->get_names();

    if (in_array($current_name, $rolesandusernames)) {
      return true;
    }

    ///MULTISITE SUPER ADMIN
    if (is_super_admin() && is_multisite()) {
      if (in_array('Super Admin', $rolesandusernames)) {
        return true;
      } else {
        return false;
      }
    }

    ///NORMAL SUPER ADMIN
    if ($current_user->ID === 1) {
      if (in_array('Super Admin', $rolesandusernames)) {
        return true;
      } else {
        return false;
      }
    }

    foreach ($current_roles as $role) {
      $role_name = $all_roles[$role];
      if (in_array($role_name, $rolesandusernames)) {
        return true;
      }
    }
  }
  /**
   * Creates custom folder post type
   * @since 1.4
   */
  public function uipress_create_menu_cpt()
  {
    $labels = [
      'name' => _x('Admin Menu', 'post type general name', 'uipress-pro'),
      'singular_name' => _x('admin menu', 'post type singular name', 'uipress-pro'),
      'menu_name' => _x('Admin Menus', 'admin menu', 'uipress-pro'),
      'name_admin_bar' => _x('Admin Menu', 'add new on admin bar', 'uipress-pro'),
      'add_new' => _x('Add New', 'Admin Menu', 'uipress-pro'),
      'add_new_item' => __('Add New Admin Menu', 'uipress-pro'),
      'new_item' => __('New Admin Menu', 'uipress-pro'),
      'edit_item' => __('Edit Admin Menu', 'uipress-pro'),
      'view_item' => __('View Admin Menu', 'uipress-pro'),
      'all_items' => __('All Admin Menus', 'uipress-pro'),
      'search_items' => __('Search Admin Menus', 'uipress-pro'),
      'not_found' => __('No Admin Menus found.', 'uipress-pro'),
      'not_found_in_trash' => __('No Admin Menus found in Trash.', 'uipress-pro'),
    ];
    $args = [
      'labels' => $labels,
      'description' => __('Description.', 'Add New Admin Menu'),
      'public' => false,
      'publicly_queryable' => false,
      'show_ui' => false,
      'show_in_menu' => false,
      'query_var' => false,
      'has_archive' => false,
      'hierarchical' => false,
    ];
    register_post_type('uip-admin-menu', $args);
  }

  /**
   * Fetches users and roles
   * @since 2.0.8
   */

  public function uipress_get_menus()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $page = sanitize_text_field($_POST['page']);
      $search = sanitize_text_field($_POST['search']);

      //Get template
      $args = [
        'post_type' => 'uip-admin-menu',
        'posts_per_page' => 15,
        'paged' => $page,
        's' => $search,
      ];

      $query = new WP_Query($args);
      $foundPosts = $query->get_posts();

      $formattedmenus = [];

      foreach ($query->get_posts() as $menu) {
        $modified = get_the_modified_date('U', $menu->ID);
        $humandate = human_time_diff($modified, strtotime(date('Y-D-M'))) . ' ' . __('ago', 'uipress-pro');

        $temp = [];
        $temp['name'] = get_the_title($menu->ID);
        $temp['id'] = $menu->ID;
        $temp['modified'] = $humandate;
        $temp['status'] = get_post_status($menu->ID);

        $options = get_post_meta($menu->ID, 'uip_menu_settings', true);
        if (is_object($options)) {
          $temp['for'] = $options->appliesTo;
          $temp['excludes'] = $options->excludes;
        } else {
          $temp['for'] = [];
          $temp['excludes'] = [];
        }

        $formattedmenus[] = $temp;
      }

      $returndata['menus'] = $formattedmenus;
      $returndata['totalFound'] = $query->found_posts;
      $returndata['totalPages'] = $query->max_num_pages;
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Deletes menus: accepts either single id or array of ids
   * @since 3.0.9
   */
  public function uip_delete_admin_menus()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $menuids = $utils->clean_ajax_input(json_decode(stripslashes($_POST['menuids'])));

      if (!current_user_can('delete_posts')) {
        $returndata['error'] = true;
        $returndata['message'] = __('You don\'t have the correct permissions to delete menus', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!is_array($menuids) && is_numeric($menuids)) {
        wp_delete_post($menuids, true);
        $returndata = [];
        $returndata['success'] = true;
        $returndata['message'] = __('Menu deleted', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (is_array($menuids)) {
        foreach ($menuids as $id) {
          wp_delete_post($id, true);
        }

        $returndata = [];
        $returndata['success'] = true;
        $returndata['message'] = __('Menus deleted', 'uipress-pro');
        wp_send_json($returndata);
      }
    }
    die();
  }

  /**
   * Gets menu for editing
   * @since 3.0.9
   */
  public function uipress_get_menu()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $id = sanitize_text_field($_POST['id']);

      if (!$id || $id == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Uanble to load menu', 'uipress-pro');
        wp_send_json($returndata);
      }

      $menuOptions = get_post_meta($id, 'uip_menu_settings', true);
      if (is_object($menuOptions)) {
        $menuOptions->name = get_the_title($id);
      } else {
        $menuOptions = new stdClass();
        $menuOptions->menu = [];
        $menuOptions->appliesTo = [];
        $menuOptions->excludes = [];
        $menuOptions->name = get_the_title($id);
        $menuOptions->autoUpdate = 'uipfalse';
        $menuOptions->status = 'uipfalse';
      }

      $returndata = [];
      $returndata['menuOptions'] = $menuOptions;
      $returndata['success'] = true;
      $returndata['message'] = __('Menu fetched', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Gets menu for editing
   * @since 3.0.9
   */
  public function uipress_get_menus_for_export()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $ids = $utils->clean_ajax_input(json_decode(stripslashes($_POST['ids'])));

      if (!$ids || $ids == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Uanble to load menu', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!is_array($ids) && is_numeric($ids)) {
        $ids = [$ids];
      }

      $returndata = [];
      $returndata['menus'] = [];
      $returndata['success'] = true;
      $returndata['message'] = __('Menu fetched', 'uipress-pro');
      foreach ($ids as $id) {
        $returndata['menus'][] = $this->get_formatted_medu($id);
      }
      wp_send_json($returndata);
    }
    die();
  }

  public function get_formatted_medu($id)
  {
    $menuOptions = get_post_meta($id, 'uip_menu_settings', true);
    if (is_object($menuOptions)) {
      $menuOptions->name = get_the_title($id);
    } else {
      $menuOptions = new stdClass();
      $menuOptions->menu = [];
      $menuOptions->appliesTo = [];
      $menuOptions->excludes = [];
      $menuOptions->name = get_the_title($id);
      $menuOptions->autoUpdate = 'uipfalse';
      $menuOptions->status = 'uipfalse';
    }

    return $menuOptions;
  }

  /**
   * Deletes menus: accepts either single id or array of ids
   * @since 3.0.9
   */
  public function uip_duplicate_admin_menu()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $copyID = sanitize_text_field($_POST['id']);

      if (!current_user_can('edit_posts')) {
        $returndata['error'] = true;
        $returndata['message'] = __('You don\'t have the correct permissions to edit this menu', 'uipress-pro');
        wp_send_json($returndata);
      }

      $updateArgs = [
        'post_title' => wp_strip_all_tags(get_the_title($copyID) . ' ' . __('copy')),
        'post_status' => 'draft',
        'post_type' => 'uip-admin-menu',
      ];

      $updatedID = wp_insert_post($updateArgs);

      //Update meta
      update_post_meta($updatedID, 'uip_menu_settings', get_post_meta($copyID, 'uip_menu_settings', true));
      update_post_meta($updatedID, 'uip-menu-for-roles', get_post_meta($copyID, 'uip-menu-for-roles', true));
      update_post_meta($updatedID, 'uip-menu-for-users', get_post_meta($copyID, 'uip-menu-for-users', true));
      update_post_meta($updatedID, 'uip-menu-excludes-roles', get_post_meta($copyID, 'uip-menu-excludes-roles', true));
      update_post_meta($updatedID, 'uip-menu-excludes-users', get_post_meta($copyID, 'uip-menu-excludes-users', true));

      $returndata = [];
      $returndata['success'] = true;
      $returndata['message'] = __('Menu duplicated', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Deletes menus: accepts either single id or array of ids
   * @since 3.0.9
   */
  public function uipress_save_menu()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $menu = $utils->clean_ajax_input(json_decode(stripslashes($_POST['menu'])));
      $id = sanitize_text_field($_POST['id']);

      if (!current_user_can('edit_post', $id)) {
        $returndata['error'] = true;
        $returndata['message'] = __('You don\'t have the correct permissions to edit this menu', 'uipress-pro');
        wp_send_json($returndata);
      }

      $status = 'draft';
      if (is_object($menu)) {
        if (property_exists($menu, 'status')) {
          $status = $menu->status;
        }
      }

      if ($status == 'uiptrue') {
        $status = 'publish';
      } else {
        $status = 'draft';
      }

      $updateArgs = [
        'ID' => $id,
        'post_title' => wp_strip_all_tags($menu->name),
        'post_status' => $status,
      ];

      $updated = wp_update_post($updateArgs);

      update_post_meta($id, 'uip_menu_settings', $menu);

      //Template for
      if (is_array($menu->appliesTo)) {
        $rolesAndUsers = $menu->appliesTo;
      } else {
        $rolesAndUsers = [];
      }
      $roles = [];
      $users = [];
      foreach ($rolesAndUsers as $item) {
        if ($item->type == 'User') {
          $users[] = $item->id;
        }

        if ($item->type == 'Role') {
          $roles[] = $item->name;
        }
      }
      //Template not for
      if (is_array($menu->excludes)) {
        $excludeRolesAndUsers = $menu->excludes;
      } else {
        $excludeRolesAndUsers = [];
      }
      $excludeRoles = [];
      $excludeUsers = [];
      foreach ($excludeRolesAndUsers as $item) {
        if ($item->type == 'User') {
          $excludeUsers[] = $item->id;
        }

        if ($item->type == 'Role') {
          $excludeRoles[] = $item->name;
        }
      }

      update_post_meta($id, 'uip-menu-for-roles', $roles);
      update_post_meta($id, 'uip-menu-for-users', $users);
      update_post_meta($id, 'uip-menu-excludes-roles', $excludeRoles);
      update_post_meta($id, 'uip-menu-excludes-users', $excludeUsers);

      $returndata = [];
      $returndata['success'] = true;
      $returndata['message'] = __('Menu saved', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Deletes menus: accepts either single id or array of ids
   * @since 3.0.9
   */
  public function uipress_save_menus_from_import()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $menus = $utils->clean_ajax_input(json_decode(stripslashes($_POST['menus'])));

      if (!current_user_can('edit_posts')) {
        $returndata['error'] = true;
        $returndata['message'] = __('You don\'t have the correct permissions to edit this post type', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!is_array($menus)) {
        $returndata['error'] = true;
        $returndata['message'] = __('No menus found to import', 'uipress-pro');
        wp_send_json($returndata);
      }

      foreach ($menus as $menu) {
        $status = 'draft';
        if (is_object($menu)) {
          if (property_exists($menu, 'status')) {
            $status = $menu->status;
          }
        }

        if ($status == 'uiptrue') {
          $status = 'publish';
        } else {
          $status = 'draft';
        }

        $updateArgs = [
          'post_title' => wp_strip_all_tags($menu->name),
          'post_status' => $status,
          'post_type' => 'uip-admin-menu',
        ];

        $updated = wp_insert_post($updateArgs);
        $id = $updated;

        update_post_meta($id, 'uip_menu_settings', $menu);

        //Template for
        if (is_array($menu->appliesTo)) {
          $rolesAndUsers = $menu->appliesTo;
        } else {
          $rolesAndUsers = [];
        }
        $roles = [];
        $users = [];
        foreach ($rolesAndUsers as $item) {
          if ($item->type == 'User') {
            $users[] = $item->id;
          }

          if ($item->type == 'Role') {
            $roles[] = $item->name;
          }
        }
        //Template not for
        if (is_array($menu->excludes)) {
          $excludeRolesAndUsers = $menu->excludes;
        } else {
          $excludeRolesAndUsers = [];
        }
        $excludeRoles = [];
        $excludeUsers = [];
        foreach ($excludeRolesAndUsers as $item) {
          if ($item->type == 'User') {
            $excludeUsers[] = $item->id;
          }

          if ($item->type == 'Role') {
            $excludeRoles[] = $item->name;
          }
        }

        update_post_meta($id, 'uip-menu-for-roles', $roles);
        update_post_meta($id, 'uip-menu-for-users', $users);
        update_post_meta($id, 'uip-menu-excludes-roles', $excludeRoles);
        update_post_meta($id, 'uip-menu-excludes-users', $excludeUsers);
      }

      $returndata = [];
      $returndata['success'] = true;
      $returndata['message'] = __('Menus imported', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Creates new admin menu
   * @since 3.0.8
   */
  public function uip_create_new_admin_menu()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $my_post = [
        'post_title' => __('Admin menu (Draft)', 'uipress-pro'),
        'post_status' => 'draft',
        'post_type' => 'uip-admin-menu',
      ];

      // Insert the post into the database.
      $postID = wp_insert_post($my_post);

      if ($postID) {
        $returndata = [];
        $returndata['success'] = true;
        $returndata['id'] = $postID;
        $returndata['message'] = __('Menu created', 'uipress-pro');
        wp_send_json($returndata);
      } else {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to create menu', 'uipress-pro');
        wp_send_json($returndata);
      }
    }
    die();
  }

  /**
   * Updates menu status from the table
   * @since 3.0.8
   */
  public function uip_update_menu_status()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $menuId = sanitize_text_field($_POST['templateid']);
      $status = sanitize_text_field($_POST['status']);

      if (!$menuId || !$status) {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to update menu status', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!current_user_can('edit_post', $menuId)) {
        $returndata['error'] = true;
        $returndata['message'] = __('You don\'t have the correct permissions to edit this menu', 'uipress-pro');
        wp_send_json($returndata);
      }

      $updateArgs = [
        'ID' => $menuId,
        'post_status' => $status,
      ];

      $updated = wp_update_post($updateArgs);

      $options = get_post_meta($menuId, 'uip_menu_settings', true);

      if (!is_object($options)) {
        $options = new stdClass();
        $options->menu = [];
        $options->appliesTo = [];
        $options->excludes = [];
        $options->name = get_the_title($id);
        $options->autoUpdate = 'uipfalse';
        $options->status = 'uipfalse';
      }
      if ($status == 'publish') {
        $options->status = 'uiptrue';
      } else {
        $options->status = 'uipfalse';
      }

      update_post_meta($menuId, 'uip_menu_settings', $options);

      if (!$updated) {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to update menu status', 'uipress-pro');
        wp_send_json($returndata);
      }

      $returndata = [];
      $returndata['success'] = true;
      $returndata['message'] = __('Menu status updated', 'uipress-pro');
      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Grabs unmodified menu
   * @since 1.4
   */

  public function set_menu($parent_file)
  {
    global $menu, $submenu;
    $this->menu = $this->sort_menu_settings($menu);
    $this->submenu = $this->sort_sub_menu_settings($this->menu, $submenu);

    return $parent_file;
  }

  /**
   * Enqueue menu editor scripts
   * @since 1.4
   */

  public function add_scripts()
  {
    //Add vue & router
    if (!uip_app_running) {
      $this->add_required_styles();
      //load vue
      //wp_enqueue_script('uip-vue', uip_plugin_url . 'assets/js/libs/vuejs.min.js', ['wp-i18n'], uip_plugin_version);
      wp_enqueue_script('uip-vue', uip_plugin_url . 'assets/js/libs/vue-dev.js', ['wp-i18n'], uip_plugin_version);
      wp_enqueue_script('uip-vue-router', uip_plugin_url . 'assets/js/libs/vue-router.min.js', ['uip-vue'], uip_plugin_version);
    }

    wp_enqueue_script('uip-sortable', uip_plugin_url . 'assets/js/libs/sortable.js', ['uip-vue'], uip_plugin_version);
    wp_enqueue_script('uip-daggable', uip_plugin_url . 'assets/js/libs/vue-draggable.js', ['uip-vue'], uip_plugin_version);
  }

  /**
   * Adds menu editor page to settings
   * @since 1.4
   */

  public function add_menu_item()
  {
    add_options_page(__('UIP Menu Builder', 'uipress-pro'), __('Menu Builder', 'uipress-pro'), 'manage_options', 'uip-menu-creator', [$this, 'start_menu_creator_app']);
  }

  /**
   * Creates menu editor page
   * @since 1.4
   */

  public function start_menu_creator_app()
  {
    ?>
    <style>
        #wpcontent{
          padding-left: 0;
        }
        #wpfooter{
          display: none;
        }
        #wpbody-content{
          padding:0;
        }
    </style>
    <div id="uip-menu-creator-app" class="uip-text-normal uip-background-default"></div>
    <?php return;
  }
}
