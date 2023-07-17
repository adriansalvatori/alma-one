<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
  exit();
}

//HOOK INTO DELAYED FUNCTIONS
add_action('uip_delay_history_event', 'uip_deleyed_new_history_event', 10, 4);
add_action('uip_cleanup_activity', 'uip_remove_old_activity');

/**
 * Deletes old history
 * @since 3.0.9
 */
function uip_remove_old_activity()
{
  $utils = new uip_util();

  $history = new uip_history();
  $details = $history->uip_activity_lengths();
  $database = $history->uip_history_get_database();

  $expiryDate = date('Y-m-d', strtotime('-' . $details['days'] . ' days'));
  $max = $details['quantity'];

  $currnetTotal = $database->get_var("SELECT COUNT(*) FROM `uip_history` WHERE `post_status` = 'publish'");

  ///To many entries, let's delete some
  if ($currnetTotal > $max) {
    $difference = $currnetTotal - $max;
    if ($difference > 0 && is_numeric($difference)) {
      // Prepare the delete query to remove the 60 oldest entries.
      $delete_query = $database->prepare(
        "
          DELETE FROM `uip_history`
          WHERE `ID` IN (
              SELECT * FROM (
                  SELECT `ID`
                  FROM `uip_history`
                  ORDER BY `post_date` ASC
                  LIMIT %d
              ) AS oldest_posts
          )
      ",
        $difference
      );
      // Execute the delete query on the custom database.
      $deleted_rows = $database->query($delete_query);

      // Check the result.
      if ($deleted_rows !== false) {
        error_log("Deleted {$deleted_rows} uipress history entries. (Reason: Log over max amount)");
      } else {
        error_log('Error while deleting uipress history entries: ' . $database->last_error);
      }
    }
  }

  // Prepare the delete query to remove entries older than the given date.
  $delete_query = $database->prepare(
    "
      DELETE FROM `uip_history`
      WHERE `post_date` < %s
  ",
    $expiryDate
  );

  // Execute the delete query on the custom database.
  $deleted_rows = $database->query($delete_query);

  // Check the result.
  if ($deleted_rows !== false) {
    error_log("Deleted {$deleted_rows} uipress history entries. (Reason: Items older than set limit)");
  } else {
    error_log('Error while deleting uipress history entries: ' . $database->last_error);
  }

  error_log('uip history cleanup completed');
}
/**
 * Creates new history event
 * @since 2.3.5
 */
function uip_deleyed_new_history_event($type, $context, $userID, $ip)
{
  $postTitle = $type . '-' . time();

  if ($userID == null) {
    $userID = get_current_user_id();
  }

  $history = new uip_history();
  $database = $history->uip_history_get_database();
  $history->uip_history_prep_database($database);

  // Prepare the post data.
  $post_data = [
    'post_title' => $postTitle,
    'post_type' => 'uip-history',
    'post_author' => $userID,
    'post_date' => current_time('mysql'),
    'post_status' => 'publish',
    'uip_history_type' => $type,
    'uip_history_context' => json_encode($context),
    'uip_history_ip' => $ip,
  ];

  // Insert the post data into the remote database.
  $database->insert('uip_history', $post_data);
}

class uip_history extends uip_app
{
  public function __construct()
  {
    $this->uip_site_settings_object = false;
  }
  /**
   * Starts plugin
   * @since 1.0
   */
  public function start()
  {
    if (wp_doing_cron()) {
      return;
    }
    if (!defined('uip_site_settings')) {
      return;
    }
    $this->uip_site_settings_object = json_decode(uip_site_settings);
    $this->load_history_actions();
  }

  /**
   * Deletes old history
   * @since 2.3.5
   */
  public function load_history_actions()
  {
    //Menu editor
    if (!isset($this->uip_site_settings_object->activityLog) || !isset($this->uip_site_settings_object->activityLog->activityLogEnabled)) {
      $loadMenuCreator = false;
    } else {
      $loadMenuCreator = $this->uip_site_settings_object->activityLog->activityLogEnabled;
    }

    //Actions are disabled
    if ($loadMenuCreator != 'uiptrue') {
      return;
    }

    add_action('init', [$this, 'register_history_type']);

    //TRACK WORDPRESS ACTIONS
    add_action('wp_footer', [$this, 'track_user_views']);
    add_action('admin_footer', [$this, 'track_user_views']);
    //POST HISTORY
    add_action('save_post', [$this, 'post_created'], 10, 3);
    add_action('transition_post_status', [$this, 'post_status_changed'], 10, 3);
    add_action('wp_trash_post', [$this, 'post_trashed'], 10);
    add_action('before_delete_post', [$this, 'post_deleted'], 10);
    //COMMENTED HISTORY
    add_action('comment_post', [$this, 'new_comment'], 10, 2);
    add_action('trash_comment', [$this, 'trash_comment'], 10, 2);
    add_action('delete_comment', [$this, 'delete_comment'], 10, 2);
    //PLUGINS
    add_action('activated_plugin', [$this, 'plugin_activated'], 10, 2);
    add_action('deactivated_plugin', [$this, 'plugin_deactivated'], 10, 2);
    add_action('deleted_plugin', [$this, 'plugin_deleted'], 10, 2);
    //LOGIN
    add_action('wp_login', [$this, 'user_last_login'], 10, 2);
    add_action('clear_auth_cookie', [$this, 'user_logout'], 10);
    //WP OPTIONS
    //add_action('updated_option', [$this, 'uip_site_option_change'], 10, 3);
    add_action('added_option', [$this, 'uip_site_option_added'], 10, 2);
    //IMAGES
    add_filter('wp_generate_attachment_metadata', [$this, 'uip_log_image_upload'], 10, 3);
    add_filter('delete_attachment', [$this, 'uip_log_image_delete'], 10, 2);
    //USERS
    add_filter('wp_create_user', [$this, 'uip_log_new_user'], 10, 3);
    add_filter('wp_insert_user', [$this, 'uip_log_new_user_insert'], 10, 1);
    add_filter('delete_user', [$this, 'uip_log_new_user_delete'], 10, 3);
    add_filter('profile_update', [$this, 'uip_log_user_update'], 10, 3);
    add_filter('user_register', [$this, 'uip_log_user_register'], 10, 2);

    ///SCHEDULE HISTORY DELETION
    if (!wp_next_scheduled('uip_cleanup_activity')) {
      wp_schedule_event(time(), 'hourly', 'uip_cleanup_activity');
    }
  }

  /**
   * Preps the database
   * @since 3.0.9
   */
  public function uip_history_prep_database($database)
  {
    // Create the `posts` table in your custom database.
    $sql = "CREATE TABLE IF NOT EXISTS `uip_history` (
        `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
        `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `post_content` longtext NOT NULL,
        `uip_history_type` longtext NOT NULL,
        `uip_history_context` longtext NOT NULL,
        `uip_history_ip` longtext NOT NULL,
        `post_title` text NOT NULL,
        `post_excerpt` text NOT NULL,
        `post_status` varchar(20) NOT NULL DEFAULT 'publish',
        `comment_status` varchar(20) NOT NULL DEFAULT 'open',
        `ping_status` varchar(20) NOT NULL DEFAULT 'open',
        `post_password` varchar(20) NOT NULL DEFAULT '',
        `post_name` varchar(200) NOT NULL DEFAULT '',
        `to_ping` text NOT NULL,
        `pinged` text NOT NULL,
        `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `post_content_filtered` longtext NOT NULL,
        `post_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
        `guid` varchar(255) NOT NULL DEFAULT '',
        `menu_order` int(11) NOT NULL DEFAULT '0',
        `post_type` varchar(20) NOT NULL DEFAULT 'post',
        `post_mime_type` varchar(100) NOT NULL DEFAULT '',
        `comment_count` bigint(20) NOT NULL DEFAULT '0',
        PRIMARY KEY (`ID`),
        KEY `post_name` (`post_name`(191)),
        KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
        KEY `post_parent` (`post_parent`),
        KEY `post_author` (`post_author`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $database->query($sql);
  }

  /**
   * Gets the current database for history items
   * @since 3.0.9
   */
  public function uip_history_get_database()
  {
    global $wpdb;
    $database = $this->uip_activity_isRemote();

    if ($database) {
      $db = new wpdb($database->username, $database->password, $database->name, $database->host);
      //wp_set_wpdb_vars();

      if (property_exists($db, 'error')) {
        $error = $db->error;
        if (is_object($error)) {
          if (property_exists($error, 'errors')) {
            $db = $wpdb;
          }
        }
      }
    } else {
      $db = $wpdb;
    }

    return $db;
  }

  public function uip_activity_isRemote()
  {
    if (!defined('uip_site_settings')) {
      return false;
    }
    $this->uip_site_settings_object = json_decode(uip_site_settings);
    //Menu editor
    if (!isset($this->uip_site_settings_object->activityLog) || !isset($this->uip_site_settings_object->activityLog->databaseDetails)) {
      return false;
    } else {
      $details = $this->uip_site_settings_object->activityLog->databaseDetails;
    }

    if (!is_object($details)) {
      return false;
    }

    if (property_exists($details, 'enabled')) {
      if ($details->enabled != 'true' && $details->enabled != 'uiptrue') {
        return false;
      }
    }

    if (!property_exists($details, 'host') || !property_exists($details, 'username') || !property_exists($details, 'password') || !property_exists($details, 'name')) {
      return false;
    }

    if ($details->name == '' || $details->username == '' || $details->host == '' || $details->password == '') {
      return false;
    }

    return $details;
  }

  public function uip_activity_lengths()
  {
    if (!defined('uip_site_settings')) {
      return false;
    }
    $this->uip_site_settings_object = json_decode(uip_site_settings);

    //Max amount
    if (!isset($this->uip_site_settings_object->activityLog) || !isset($this->uip_site_settings_object->activityLog->historyMaxAmount)) {
      $maxAmount = 20000;
    } else {
      $maxAmount = $this->uip_site_settings_object->activityLog->historyMaxAmount;
      if (!is_numeric($maxAmount)) {
        $maxAmount = 20000;
      }
    }
    //Max length
    if (!isset($this->uip_site_settings_object->activityLog) || !isset($this->uip_site_settings_object->activityLog->historyMaxLength)) {
      $maxDays = 30;
    } else {
      $maxDays = $this->uip_site_settings_object->activityLog->historyMaxLength;
      if (!is_numeric($maxDays)) {
        $maxDays = 30;
      }
      if ($maxDays < 1) {
        $maxDays = 30;
      }
    }

    $details['quantity'] = $maxAmount;
    $details['days'] = $maxDays;

    return $details;
  }

  /**
   * Adds custom cron schedules
   * @since 2.3.5
   */
  function uip_cron_schedules($schedules)
  {
    if (!isset($schedules['1min'])) {
      $schedules['1min'] = [
        'interval' => 60,
        'display' => __('Once every 1 minutes'),
      ];
      $schedules['1hour'] = [
        'interval' => 60 * 60,
        'display' => __('Once every 1 hour'),
      ];
    }
    return $schedules;
  }

  /**
   * Creates custom post type for history
   * @since 2.3.5
   */
  public function register_history_type()
  {
    $labels = [
      'name' => _x('History', 'post type general name', 'uipress'),
      'singular_name' => _x('history', 'post type singular name', 'uipress'),
      'menu_name' => _x('History', 'admin menu', 'uipress'),
      'name_admin_bar' => _x('History', 'add new on admin bar', 'uipress'),
      'add_new' => _x('Add New', 'history', 'uipress'),
      'add_new_item' => __('Add New History', 'uipress'),
      'new_item' => __('New History', 'uipress'),
      'edit_item' => __('Edit History', 'uipress'),
      'view_item' => __('View History', 'uipress'),
      'all_items' => __('All History', 'uipress'),
      'search_items' => __('Search History', 'uipress'),
      'not_found' => __('No History found.', 'uipress'),
      'not_found_in_trash' => __('No History found in Trash.', 'uipress'),
    ];
    $args = [
      'labels' => $labels,
      'description' => __('Description.', 'Add New History'),
      'public' => false,
      'publicly_queryable' => false,
      'show_ui' => true,
      'show_in_menu' => false,
      'query_var' => false,
      'has_archive' => false,
      'hierarchical' => false,
      'supports' => ['title', 'author'],
    ];
    register_post_type('uip-history', $args);
  }

  /**
   * Capture Login Data
   * @since 1.0
   */
  public function user_last_login($user_login, $user)
  {
    update_user_meta($user->ID, 'uip_last_login', time());
    update_user_meta($user->ID, 'uip_last_login_date', date('Y-m-d'));

    $vis_ip = $this->getVisIPAddr();
    $ipdat = @json_decode(file_get_contents('http://www.geoplugin.net/json.gp?ip=' . $vis_ip));
    $country = $ipdat->geoplugin_countryName;

    update_user_meta($user->ID, 'uip_last_login_country', $country);

    $context['ip'] = $vis_ip;
    $context['country'] = $country;

    $this->create_new_history_event('user_login', $context, $user->ID);
  }

  /**
   * Get User IP
   * @since 1.0
   */
  public function getVisIpAddr()
  {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }

    if (!isset($this->uip_site_settings_object->anonymizeIP) || !isset($this->uip_site_settings_object->activityLog->anonymizeIP)) {
      $anonymizeIP = false;
    } else {
      $anonymizeIP = $this->uip_site_settings_object->activityLog->anonymizeIP;
    }

    //Anomonise IP
    if ($anonymizeIP == 'uiptrue') {
      return hash('ripemd160', $ip);
    }

    return $ip;
  }
  /**
   * Tracks page views
   * @since 2.3.5
   */
  public function track_user_views()
  {
    //$startTime = microtime(true);

    if (defined('DOING_AJAX')) {
      return;
    }

    if (is_user_logged_in()) {
      $utils = new uip_util();
      if (is_admin()) {
        $title = get_admin_page_title();
        $url = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
      } else {
        global $wp;
        $title = get_the_title();
        $url = home_url($wp->request);
      }

      $postTitle = 'PageView ' . time();
      $context['url'] = $url;
      $context['title'] = $title;

      $utils = new uip_util();

      $this->update_recent_views($url, $title);
    }

    //echo "Elapsed time is: ". (microtime(true) - $startTime) ." seconds";
  }

  /**
   * Creates new history event
   * @since 2.3.5
   */
  public function create_new_history_event($type, $context, $userID = null)
  {
    if (!isset($this->uip_site_settings_object->actionsNoTrack) || !isset($this->uip_site_settings_object->activityLog->actionsNoTrack)) {
      $noActions = [];
    } else {
      $noActions = $this->uip_site_settings_object->activityLog->actionsNoTrack;
    }

    if (is_array($noActions) && in_array($type, $noActions)) {
      return;
    }

    $args = [
      'type' => $type,
      'context' => $context,
      'userID' => get_current_user_id(),
      'ip' => $this->getVisIpAddr(),
    ];

    wp_schedule_single_event(time() + 10, 'uip_delay_history_event', $args);
    return;
  }

  /**
   * Logs recent page views
   * @since 2.3.5
   */
  public function update_recent_views($url, $title)
  {
    $userID = get_current_user_id();
    $views = get_user_meta($userID, 'recent_page_views', true);

    ///CHECK IF NO HISTORY
    if (!is_array($views)) {
      $views = [];
      $currentpage['title'] = $title;
      $currentpage['time'] = time();
      $currentpage['url'] = $url;
      array_push($views, $currentpage);
    } else {
      $length = count($views);

      ///ONLY KEEP 5 RECORDS
      if ($length > 4) {
        array_shift($views);
        $currentpage['title'] = $title;
        $currentpage['time'] = time();
        $currentpage['url'] = $url;
        array_push($views, $currentpage);
      } else {
        $currentpage['title'] = $title;
        $currentpage['time'] = time();
        $currentpage['url'] = $url;
        array_push($views, $currentpage);
      }
    }

    update_user_meta($userID, 'recent_page_views', $views);
  }

  /**
   * Logs post creation / modification
   * @since 2.3.5
   */
  public function post_created($post_id, $post, $update)
  {
    if (get_post_type($post_id) == 'uip-history') {
      return;
    }
    $context['title'] = $post->post_title;
    $context['url'] = get_permalink($post_id);
    $context['post_id'] = $post_id;

    if (!$update) {
      $this->create_new_history_event('post_created', $context);
    }
  }

  /**
   * Logs post status change
   * @since 2.3.5
   */
  public function post_status_changed($new_status, $old_status, $post)
  {
    if (get_post_type($post->ID) == 'uip-history') {
      return;
    }

    if ($old_status != $new_status) {
      $context['title'] = $post->post_title;
      $context['url'] = get_permalink($post->ID);
      $context['post_id'] = $post->ID;
      $context['old_status'] = $old_status;
      $context['new_status'] = $new_status;
      $this->create_new_history_event('post_status_change', $context);
    }
  }

  /**Logs post trashing
   * @since 2.3.5
   */
  public function post_trashed($post_id)
  {
    if (get_post_type($post_id) == 'uip-history') {
      return;
    }
    $context['title'] = get_the_title($post_id);
    $context['url'] = get_permalink($post_id);
    $context['post_id'] = $post_id;

    $this->create_new_history_event('post_trashed', $context);
  }

  /**
   * Logs post permanent delete
   * @since 2.3.5
   */
  public function post_deleted($post_id)
  {
    if (get_post_type($post_id) == 'uip-history') {
      return;
    }

    if (wp_is_post_revision($post_id)) {
      return;
    }

    $context['title'] = get_the_title($post_id);
    $context['url'] = get_permalink($post_id);
    $context['post_id'] = $post_id;

    $this->create_new_history_event('post_deleted', $context);
  }

  /**
   * Logs new comment
   * @since 2.3.5
   */
  public function new_comment($comment_ID, $comment_approved)
  {
    $theComment = get_comment($comment_ID);
    $comment_post_id = $theComment->comment_post_ID;
    $context['author'] = $theComment->comment_author;
    $context['content'] = $theComment->comment_content;
    $context['comment_id'] = $comment_ID;
    $context['post_id'] = $comment_post_id;

    $this->create_new_history_event('new_comment', $context);
  }

  /**
   * Logs deleted comment
   * @since 2.3.5
   */
  public function trash_comment($comment_ID, $comment_approved)
  {
    $theComment = get_comment($comment_ID);
    $comment_post_id = $theComment->comment_post_ID;
    $context['author'] = $theComment->comment_author;
    $context['content'] = $theComment->comment_content;
    $context['comment_id'] = $comment_ID;
    $context['post_id'] = $comment_post_id;

    $this->create_new_history_event('trash_comment', $context);
  }

  /**
   * Logs deleted comment
   * @since 2.3.5
   */
  public function delete_comment($comment_ID, $comment_approved)
  {
    $theComment = get_comment($comment_ID);
    $comment_post_id = $theComment->comment_post_ID;
    $context['author'] = $theComment->comment_author;
    $context['content'] = $theComment->comment_content;
    $context['comment_id'] = $comment_ID;
    $context['post_id'] = $comment_post_id;

    $this->create_new_history_event('delete_comment', $context);
  }

  /**
   * Logs plugin activation
   * @since 2.3.5
   */
  public function plugin_activated($plugin, $network_activation)
  {
    $pluginObject = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
    $context['plugin_name'] = $pluginObject['Name'];
    $context['plugin_path'] = $plugin;

    $this->create_new_history_event('plugin_activated', $context);
  }

  /**
   * Logs plugin deactivation
   * @since 2.3.5
   */
  public function plugin_deactivated($plugin, $network_activation)
  {
    $pluginObject = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
    $context['plugin_name'] = $pluginObject['Name'];
    $context['plugin_path'] = $plugin;

    $this->create_new_history_event('plugin_deactivated', $context);
  }

  /**
   * Logs plugin deletion
   * @since 2.4.1
   */
  public function plugin_deleted($plugin, $deleted)
  {
    if (!$deleted) {
      return;
    }
    $context['plugin_name'] = $plugin;
    $context['plugin_path'] = $plugin;

    $this->create_new_history_event('plugin_deleted', $context);
  }

  /**
   * Logs user logout
   * @since 2.3.5
   */
  public function user_logout()
  {
    $vis_ip = $this->getVisIPAddr();
    $ipdat = @json_decode(file_get_contents('http://www.geoplugin.net/json.gp?ip=' . $vis_ip));
    $country = $ipdat->geoplugin_countryName;

    $context['ip'] = $vis_ip;
    $context['country'] = $country;

    $this->create_new_history_event('user_logout', $context, get_current_user_id());
  }

  /**
   * Logs option change
   * @since 2.3.5
   */
  public function uip_site_option_change($option_name, $old_value, $option_value)
  {
    if (strpos($option_name, 'transient') !== false || strpos($option_name, 'cron') !== false || strpos($option_name, 'action_scheduler') !== false) {
      return;
    }

    $oldvalue = $old_value;
    $newvalue = $option_value;

    if (is_array($oldvalue)) {
      $oldvalue = json_encode($oldvalue);
    }

    if (is_array($newvalue)) {
      $newvalue = json_encode($newvalue);
    }

    if ($oldvalue == $newvalue) {
      return;
    }

    $context['option_name'] = $option_name;
    $context['old_value'] = $old_value;
    $context['new_value'] = $option_value;

    $this->create_new_history_event('option_change', $context, get_current_user_id());
  }

  /**
   * Logs option change
   * @since 2.3.5
   */
  public function uip_site_option_added($option_name, $option_value)
  {
    if (strpos($option_name, 'transient') !== false || strpos($option_name, 'cron') !== false || strpos($option_name, 'action_scheduler') !== false) {
      return;
    }

    $newvalue = $option_value;

    if (is_array($newvalue)) {
      $newvalue = json_encode($newvalue);
    }

    $context['option_name'] = $option_name;
    $context['new_value'] = $option_value;

    $this->create_new_history_event('option_added', $context, get_current_user_id());
  }

  /**
   * Logs image upload
   * @since 2.3.5
   */
  public function uip_log_image_upload($metadata, $attachment_id, $context)
  {
    $data['name'] = get_the_title($attachment_id);

    $data = [];
    if (isset($metadata['file'])) {
      $data['path'] = $metadata['file'];
    }
    $data['image_id'] = $attachment_id;

    $this->create_new_history_event('attachment_uploaded', $data, get_current_user_id());

    return $metadata;
  }

  /**
   * Logs image delete
   * @since 2.3.5
   */
  public function uip_log_image_delete($attachment_id, $post)
  {
    $data['name'] = get_the_title($attachment_id);
    $data['image_id'] = $attachment_id;

    $this->create_new_history_event('attachment_deleted', $data, get_current_user_id());
  }

  /**
   * Logs user creation
   * @since 2.3.5
   */
  public function uip_log_new_user($username, $password, $email)
  {
    $data['username'] = $username;
    $data['email'] = $email;

    $this->create_new_history_event('user_created', $data, get_current_user_id());
  }

  /**
   * Logs user creation
   * @since 2.3.5
   */
  public function uip_log_user_register($userid, $userdata)
  {
    $userObj = new WP_User($userid);

    $data['username'] = $userObj->user_login;
    $data['email'] = $userObj->user_email;
    $data['user_id'] = $userid;

    $this->create_new_history_event('user_created', $data, get_current_user_id());
  }

  /**
   * Logs user creation
   * @since 2.3.5
   */
  public function uip_log_new_user_insert($user)
  {
    $data['username'] = $user->user_login;
    $data['email'] = $user->user_email;

    $this->create_new_history_event('user_created', $data, get_current_user_id());
  }

  /**
   * Logs user deletion
   * @since 2.3.5
   */
  public function uip_log_new_user_delete($id, $reassign, $user)
  {
    $data['username'] = $user->user_login;
    $data['email'] = $user->user_email;
    $data['user_id'] = $id;

    $this->create_new_history_event('user_deleted', $data, get_current_user_id());
  }

  /**
   * Logs user update
   * @since 2.3.5
   */
  public function uip_log_user_update($user_id, $old_user_data, $userdata)
  {
    $userObj = new WP_User($user_id);

    $data['username'] = $userObj->user_login;
    $data['email'] = $userObj->user_email;
    $data['user_id'] = $user_id;
    $data['old_value'] = $old_user_data;
    $data['new_value'] = $userdata;

    $this->create_new_history_event('user_updated', $data, get_current_user_id());
  }
}
