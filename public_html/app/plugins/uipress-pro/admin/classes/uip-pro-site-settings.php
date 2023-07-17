<?php
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Handles UIP global settings
 * @since 3.0.92
 */
class uip_pro_site_settings extends uip_site_settings
{
  public function __construct()
  {
  }

  public function run()
  {
    add_action('plugins_loaded', [$this, 'set_site_settings'], 2);
  }

  /**
   * Calls appropiate functions if the settings are set and defined
   * @since 3.0.92
   */
  public function set_site_settings()
  {
    if (!defined('uip_site_settings')) {
      return;
    }
    $this->uip_site_settings_object = json_decode(uip_site_settings);
    $this->load_extensions();

    add_action('admin_head', [$this, 'add_head_code'], 99);
    add_action('all_plugins', [$this, 'remove_uip_plugin_table'], 10, 1);
    add_action('admin_enqueue_scripts', [$this, 'add_scripts_and_styles']);
    add_filter('admin_body_class', [$this, 'push_role_to_body_class']);
    add_action('pre_get_posts', [$this, 'limit_user_library'], 10, 1);
  }

  /**
   * Conditionally loads extensions
   * @since 3.1.1
   */
  function load_extensions()
  {
    //Menu editor
    if (!isset($this->uip_site_settings_object->extensions) || !isset($this->uip_site_settings_object->extensions->menuCreatorEnabled)) {
      $loadMenuCreator = false;
    } else {
      $loadMenuCreator = $this->uip_site_settings_object->extensions->menuCreatorEnabled;
    }

    if ($loadMenuCreator == 'uiptrue') {
      require_once uip_pro_plugin_path . 'admin/extensions/menu-creator/menu-creator.php';
      $menu_creator = new uipress_menu_creator();
      $menu_creator->run();
    }

    //User management
    if (!isset($this->uip_site_settings_object->extensions) || !isset($this->uip_site_settings_object->extensions->userManagementEnabled)) {
      $userManagementEnabled = false;
    } else {
      $userManagementEnabled = $this->uip_site_settings_object->extensions->userManagementEnabled;
    }

    if ($userManagementEnabled == 'uiptrue') {
      require_once uip_pro_plugin_path . 'admin/extensions/user-management/user-management.php';
      $userManagement = new uipress_users();
      $userManagement->run();
    }

    //Folders
    if (!isset($this->uip_site_settings_object->extensions) || !isset($this->uip_site_settings_object->extensions->foldersEnabled)) {
      $foldersEnabled = false;
    } else {
      $foldersEnabled = $this->uip_site_settings_object->extensions->foldersEnabled;
    }

    if ($foldersEnabled == 'uiptrue') {
      require_once uip_pro_plugin_path . 'admin/extensions/folders/folders.php';
      $folders = new uip_folders();
      $folders->run();
    }
  }

  /**
   * Modifies post tables and media for non admins
   * @since 3.0.7
   */
  function limit_user_library($wp_query)
  {
    if (!isset($this->uip_site_settings_object->media) || !isset($this->uip_site_settings_object->media->privateLibrary)) {
      $privateMedia = false;
    } else {
      $privateMedia = $this->uip_site_settings_object->media->privateLibrary;
    }

    if (!isset($this->uip_site_settings_object->postsPages) || !isset($this->uip_site_settings_object->postsPages->privatePosts)) {
      $privatePosts = false;
    } else {
      $privatePosts = $this->uip_site_settings_object->postsPages->privatePosts;
    }

    //Keep it admin only
    if (!is_admin()) {
      return;
    }
    if (current_user_can('administrator')) {
      return;
    }

    if ($privateMedia != 'uiptrue' && $privatePosts != 'uiptrue') {
      return;
    }

    global $current_user;

    $query = $wp_query->query;
    if (!isset($query['post_type'])) {
      return;
    }
    $postType = $wp_query->query['post_type'];
    $containsMedia = false;
    $containsPosts = false;

    if (is_array($postType)) {
      if (in_array('attachment', $postType)) {
        $containsMedia = true;
      }
      if (in_array('post', $postType) || in_array('page', $postType)) {
        $containsPosts = true;
      }
    } else {
      if ($postType == 'attachment') {
        $containsMedia = true;
      }
      if ($postType == 'post' || $postType == 'page') {
        $containsPosts = true;
      }
    }

    if ($privateMedia == 'uiptrue' && $containsMedia) {
      $wp_query->set('author', $current_user->ID);
    }

    if ($privatePosts == 'uiptrue' && $containsPosts) {
      $wp_query->set('author', $current_user->ID);
      add_filter('views_edit-post', [$this, 'fix_post_table_counts'], 10, 1);
      add_filter('views_edit-page', [$this, 'fix_post_table_counts'], 10, 1);
    }
  }

  /**
   * Corrects post count for post tables when private library is enabled
   * @since 3.0.7
   */
  function fix_post_table_counts($views)
  {
    global $current_user, $wp_query;

    $postType = $wp_query->query['post_type'];
    if (is_array($postType)) {
      if (in_array('post', $postType)) {
        $current = 'post';
      }
      if (in_array('page', $postType)) {
        $current = 'page';
      }
    } else {
      $current = $postType;
    }

    unset($views['mine']);
    $types = [['status' => null], ['status' => 'publish'], ['status' => 'draft'], ['status' => 'pending'], ['status' => 'trash']];
    foreach ($types as $type) {
      $query = [
        'author' => $current_user->ID,
        'post_type' => $current,
        'post_status' => $type['status'],
      ];
      $result = new WP_Query($query);
      if ($type['status'] == null):
        $class = $wp_query->query_vars['post_status'] == null ? ' class="current"' : '';
        $views['all'] = sprintf('<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>', admin_url('edit.php?post_type=' . $current), $class, $result->found_posts, __('All'));
      elseif ($type['status'] == 'publish'):
        $class = $wp_query->query_vars['post_status'] == 'publish' ? ' class="current"' : '';
        $views['publish'] = sprintf(
          '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
          admin_url('edit.php?post_status=publish&post_type=' . $current),
          $class,
          $result->found_posts,
          __('Publish')
        );
      elseif ($type['status'] == 'draft'):
        $class = $wp_query->query_vars['post_status'] == 'draft' ? ' class="current"' : '';
        $views['draft'] = sprintf(
          '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
          admin_url('edit.php?post_status=draft&post_type=' . $current),
          $class,
          $result->found_posts,
          __('Draft')
        );
      elseif ($type['status'] == 'pending'):
        $class = $wp_query->query_vars['post_status'] == 'pending' ? ' class="current"' : '';
        $views['pending'] = sprintf(
          '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
          admin_url('edit.php?post_status=pendingpost_type=' . $current),
          $class,
          $result->found_posts,
          __('Pending')
        );
      elseif ($type['status'] == 'trash'):
        $class = $wp_query->query_vars['post_status'] == 'trash' ? ' class="current"' : '';
        $views['trash'] = sprintf(
          '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
          admin_url('edit.php?post_status=trash&post_type=' . $current),
          $class,
          $result->found_posts,
          __('Trash')
        );
      endif;
    }

    return $views;
  }

  /**
   * Adds current roles as body classes on the admin
   * @since 3.0.3
   */
  function push_role_to_body_class($classes)
  {
    if (!isset($this->uip_site_settings_object->advanced) || !isset($this->uip_site_settings_object->advanced->addRoleToBody)) {
      return $classes;
    }

    $addHead = $this->uip_site_settings_object->advanced->addRoleToBody;

    if ($addHead == 'uiptrue') {
      $user = new WP_User(get_current_user_id());

      if (!empty($user->roles) && is_array($user->roles)) {
        foreach ($user->roles as $role) {
          $classes .= ' ' . strtolower($role);
        }
      }
    }

    return $classes;
  }

  /**
   * Adds user enqueued scripts and styles
   * @since 3.0.92
   */
  public function add_scripts_and_styles()
  {
    if (!isset($this->uip_site_settings_object->advanced)) {
      return;
    }

    if (isset($this->uip_site_settings_object->advanced->enqueueScripts)) {
      $scripts = $this->uip_site_settings_object->advanced->enqueueScripts;

      if (is_array($scripts)) {
        foreach ($scripts as $script) {
          wp_enqueue_script($script->id, $script->value, []);
        }
      }
    }

    if (!isset($this->uip_site_settings_object->advanced->enqueueStyles)) {
      return;
    }

    $styles = $this->uip_site_settings_object->advanced->enqueueStyles;

    if (is_array($styles)) {
      foreach ($styles as $style) {
        wp_register_style($style->id, $style->value, []);
        wp_enqueue_style($style->id);
      }
    }
  }

  /**
   * Adds user code to the head of admin pages
   * @since 3.0.92
   */
  public function add_head_code()
  {
    if (!isset($_GET['uip-framed-page']) || $_GET['uip-framed-page'] != '1') {
      return;
    }
    $utils = new uip_util();
    if (!isset($this->uip_site_settings_object->advanced) || !isset($this->uip_site_settings_object->advanced->htmlHead)) {
      return;
    }

    $code = $this->uip_site_settings_object->advanced->htmlHead;
    if ($code == '' || $code == 'uipblank') {
      return;
    }

    echo $utils->clean_ajax_input_width_code(html_entity_decode($code));
  }

  /**
   * Hides uipress from plugins table
   * @since 3.0.92
   */
  public function remove_uip_plugin_table($all_plugins)
  {
    if (!isset($this->uip_site_settings_object->whiteLabel) || !isset($this->uip_site_settings_object->whiteLabel->hidePlugins)) {
      return $all_plugins;
    }

    $hidden = $this->uip_site_settings_object->whiteLabel->hidePlugins;

    if ($hidden == 'uiptrue') {
      unset($all_plugins['uipress-lite/uipress-lite.php']);
      unset($all_plugins['uipress-pro/uipress-pro.php']);
      return $all_plugins;
    }
    return $all_plugins;
  }
}
