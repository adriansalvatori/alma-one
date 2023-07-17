<?php
if (!defined('ABSPATH')) {
  exit();
}

class uipress_users_ajax extends uipress_users
{
  /**
   * Adds ajax actions
   * @since 2.3.5
   */

  public function ajax_actions()
  {
    ///AJAX
    add_action('wp_ajax_uip_get_user_table_data', [$this, 'uip_get_user_table_data']);
    add_action('wp_ajax_uip_get_role_table_data', [$this, 'uip_get_role_table_data']);
    add_action('wp_ajax_uip_get_user_data', [$this, 'uip_get_user_data']);
    add_action('wp_ajax_uip_get_user_roles_for_user_management', [$this, 'uip_get_user_roles_for_user_management']);
    add_action('wp_ajax_uip_update_user', [$this, 'uip_update_user']);
    add_action('wp_ajax_uip_batch_update_roles', [$this, 'uip_batch_update_roles']);
    add_action('wp_ajax_uip_update_user_role', [$this, 'uip_update_user_role']);
    add_action('wp_ajax_uip_create_new_role', [$this, 'uip_create_new_role']);
    add_action('wp_ajax_uip_delete_role', [$this, 'uip_delete_role']);
    add_action('wp_ajax_uip_delete_roles', [$this, 'uip_delete_roles']);
    add_action('wp_ajax_uip_add_new_user', [$this, 'uip_add_new_user']);
    add_action('wp_ajax_uip_reset_password', [$this, 'uip_reset_password']);
    add_action('wp_ajax_uip_password_reset_multiple', [$this, 'uip_password_reset_multiple']);
    add_action('wp_ajax_uip_delete_user', [$this, 'uip_delete_user']);
    add_action('wp_ajax_uip_delete_multiple_users', [$this, 'uip_delete_multiple_users']);
    add_action('wp_ajax_uip_send_message', [$this, 'uip_send_message']);
    add_action('wp_ajax_uip_add_custom_capability', [$this, 'uip_add_custom_capability']);
    add_action('wp_ajax_uip_remove_custom_capability', [$this, 'uip_remove_custom_capability']);
    add_action('wp_ajax_uip_logout_user_everywhere', [$this, 'uip_logout_user_everywhere']);
    add_action('wp_ajax_uip_get_singular_role', [$this, 'uip_get_singular_role']);

    //History actions
    add_action('wp_ajax_uip_get_activity_table_data', [$this, 'uip_get_activity_table_data']);
    add_action('wp_ajax_uip_delete_all_history', [$this, 'uip_delete_all_history']);
    add_action('wp_ajax_uip_delete_multiple_actions', [$this, 'uip_delete_multiple_actions']);
  }

  /**
   * Gets data for user table
   * @since 2.3.5
   */

  public function uip_get_user_table_data()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $page = sanitize_text_field($_POST['tablePage']);
      $filters = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['filters'])));
      $options = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['options'])));

      //SET SEARCH QUERY
      $s_query = '';
      if (isset($filters['search'])) {
        $s_query = $filters['search'];
      }

      //SET ROLE FILTERS
      $roles = [];
      if (isset($filters['roles']) && is_array($filters['roles'])) {
        $roles = $filters['roles'];
      }

      //SET DIRECTION
      $direction = 'ASC';
      if (isset($options['direction']) && $options['direction'] != '') {
        $direction = $options['direction'];
      }

      //SET DIRECTION
      $perpage = '20';
      if (isset($options['perPage']) && $options['perPage'] != '') {
        $perpage = $options['perPage'];
      }

      $args = [
        'number' => $perpage,
        'role__in' => $roles,
        'search' => '*' . $s_query . '*',
        'paged' => $page,
        'order' => $direction,
      ];

      //SET ORDERBY
      $sortBy = 'username';
      if (isset($options['sortBy']) && $options['sortBy'] != '') {
        $sortBy = $options['sortBy'];
      }

      //SET FOLDER FILTERS
      if (isset($filters['activeGroup']) && $filters['activeGroup'] != '' && $filters['activeGroup'] != 'all') {
        if ($filters['activeGroup'] == 'nofolder') {
          $args['meta_query'] = [
            'relation' => 'OR',
            [
              'key' => 'uip_user_group',
              'compare' => 'NOT EXISTS',
            ],
            [
              'key' => 'uip_user_group',
              'value' => '',
              'compare' => '=',
            ],
            [
              'key' => 'uip_user_group',
              'value' => '',
              'compare' => '[]',
            ],
          ];
        } else {
          $args['meta_query'] = [
            [
              'key' => 'uip_user_group',
              'value' => '"' . $filters['activeGroup'] . '"',
              'compare' => 'LIKE',
            ],
          ];
        }
      }

      //SET ORDER BY
      $metakeys = ['first_name', 'last_name', 'last_name', 'uip_last_login_date', 'uip_user_group'];

      if (in_array($sortBy, $metakeys)) {
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = $sortBy;
      } elseif ($sortBy == 'roles') {
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'wp_capabilities';
      } else {
        $args['orderby'] = $sortBy;
      }

      if (isset($filters['dateCreated']) && is_object($filters['dateCreated'])) {
        $dateFilters = (array) $filters['dateCreated'];
        if (isset($dateFilters['date']) && $dateFilters['date'] != '') {
          $dateCreated = $dateFilters['date'];
          $dataComparison = $dateFilters['type'];

          $args = $this->returnDateFilter($dateCreated, $dataComparison, $args);
        }
      }

      $user_query = new WP_User_Query($args);
      $all_users = $user_query->get_results();
      $total_users = $user_query->get_total();

      $args = [
        'numberposts' => -1,
        'post_type' => 'uip_user_group',
        'orderby' => 'title',
        'order' => 'ASC',
      ];

      $groups = get_posts($args);
      $formattedGroups = [];
      foreach ($groups as $group) {
        $temp = [];
        $temp['color'] = get_post_meta($group->ID, 'color_tag', true);
        $temp['title'] = $group->post_title;
        $temp['id'] = $group->ID;
        $temp['icon'] = get_post_meta($group->ID, 'group_icon', true);
        $formattedGroups[$group->ID] = $temp;
      }

      $returnData['tableData']['totalFound'] = number_format($total_users);
      $returnData['tableData']['users'] = $this->uip_format_user_data($all_users);
      $returnData['tableData']['groups'] = $formattedGroups;

      $returnData['tableData']['columns'] = $this->uip_build_user_table_columns();

      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Gets data for user table
   * @since 3.0.9
   */

  public function uip_get_activity_table_data()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $page = sanitize_text_field($_POST['tablePage']);
      $filters = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['filters'])));
      $options = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['options'])));

      //SET SEARCH QUERY
      $s_query = '';
      if (isset($filters['search'])) {
        $s_query = $filters['search'];
      }

      //SET ROLE FILTERS
      $roles = [];
      $user_ids = [];
      if (isset($filters['roles']) && is_array($filters['roles'])) {
        $roles = $filters['roles'];

        if (count($roles) > 0) {
          $userargs = [
            'role__in' => $roles,
            'fields' => ['ID'],
          ];

          $users = get_users($userargs);

          foreach ($users as $user) {
            $user_ids[] = $user->ID;
          }

          if (count($user_ids) === 0) {
            $user_ids = [0];
          }
        }
      }

      //SET DIRECTION
      $direction = 'ASC';
      if (isset($options['direction']) && $options['direction'] != '') {
        $direction = $options['direction'];
      }

      //SET DIRECTION
      $perpage = '20';
      if (isset($options['perPage']) && $options['perPage'] != '') {
        $perpage = $options['perPage'];
      }

      //Get and prep database
      $history = new uip_history();
      $database = $history->uip_history_get_database();
      $history->uip_history_prep_database($database);

      //Build search query
      $search_query = '';
      if ($s_query != '') {
        $search_term = '%' . $database->esc_like($s_query) . '%';
        $search_query = $database->prepare(
          ' AND (`post_title` LIKE %s OR `post_content` LIKE %s OR `uip_history_type` LIKE %s OR `uip_history_context` LIKE %s OR `uip_history_ip` LIKE %s)',
          $search_term,
          $search_term,
          $search_term,
          $search_term,
          $search_term
        );
      }

      $author_filter_query = '';
      if (count($user_ids) > 0) {
        // Convert the author IDs array to a comma-separated string for use in the SQL query.
        $author_ids_string = implode(',', array_map('intval', $user_ids));
        $author_filter_query = " AND `post_author` IN ({$author_ids_string})";
      }

      $date_filter_query = '';
      if (isset($filters['dateCreated']) && is_object($filters['dateCreated'])) {
        $dateFilters = (array) $filters['dateCreated'];
        if (isset($dateFilters['date']) && $dateFilters['date'] != '') {
          $dateCreated = $dateFilters['date'];
          $dataComparison = $dateFilters['type'];

          if ($dataComparison == 'before') {
            $date_filter_query = $database->prepare(' AND DATE(`post_date`) < %s', $dateCreated);
          } elseif ($dataComparison == 'after') {
            $date_filter_query = $database->prepare(' AND DATE(`post_date`) > %s', $dateCreated);
          } else {
            $date_filter_query = $database->prepare(' AND DATE(`post_date`) = %s', $dateCreated);
          }
        }
      }

      ///Status filters
      $status_filter_query = '';
      if (isset($filters['status']) && is_array($filters['status']) && count($filters['status']) > 0) {
        $statuses = (array) $filters['status'];
        // Convert the array of statuses to a comma-separated string surrounded by quotes for use in the SQL query.
        $statuses_string = implode(
          ', ',
          array_map(function ($status) use ($database) {
            return $database->prepare('%s', $status);
          }, $statuses)
        );
        $status_filter_query = " AND `uip_history_type` IN ({$statuses_string})";
      }

      // Calculate the offset.
      $offset = ($page - 1) * $perpage;

      // Perform the paged query on the custom database.
      $all_history = $database->get_results(
        $database->prepare(
          "SELECT * FROM `uip_history` WHERE `post_status` = 'publish' {$search_query} {$author_filter_query} {$status_filter_query} {$date_filter_query} ORDER BY `post_date` DESC LIMIT %d OFFSET %d",
          $perpage,
          $offset
        )
      );

      //Post count
      $total_history = $database->get_var("SELECT COUNT(*) FROM `uip_history` WHERE `post_status` = 'publish' {$search_query} {$author_filter_query} {$status_filter_query} {$date_filter_query}");

      //Get total pages
      $totalpages = 0;
      if ($total_history > 0) {
        $totalpages = ceil($total_history / $perpage);
      }

      $formatted = [];
      foreach ($all_history as $action) {
        $formatted[] = $this->format_user_activity($action);
      }

      $returnData['tableData']['totalFound'] = number_format($total_history);
      $returnData['tableData']['activity'] = $formatted;
      $returnData['tableData']['totalPages'] = $totalpages;

      $returnData['tableData']['columns'] = $this->uip_build_activity_table_columns();
      $returnData['tableData']['actions'] = $this->uip_return_history_actions();

      echo json_encode($returnData);
    }
    die();
  }

  /**
   * Gets singular role data
   * @since 3.0.9
   */

  public function uip_get_singular_role()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $role = sanitize_text_field($_POST['role']);

      if (!$role || $role == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('No role given', 'uipress-pro');
        wp_send_json($returnData);
      }

      $value = get_role($role);

      $roleData = [];
      $roleData['name'] = $role;
      $roleData['label'] = $role_name = $role ? wp_roles()->get_names()[$role] : '';
      $roleData['caps'] = $value->capabilities;
      $roleData['all'] = $value;
      $roleData['granted'] = count($value->capabilities);
      $roleData['redirect'] = '';

      $redirects = $utils->get_uip_option('role_redirects');

      error_log(json_encode($redirects));

      if (is_array($redirects)) {
        if (isset($redirects[$role])) {
          $roleData['redirect'] = $redirects[$role];
        }
      }

      $returnData['role'] = $roleData;
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Gets data for role table
   * @since 2.3.5
   */

  public function uip_get_role_table_data()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $page = sanitize_text_field($_POST['tablePage']);
      $filters = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['filters'])));
      $options = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['options'])));

      //SET SEARCH QUERY
      $s_query = '';
      if (isset($filters['search'])) {
        $s_query = $filters['search'];
      }

      global $wp_roles;

      $allroles = [];

      global $wp_roles;
      $all_roles = [];

      foreach ($wp_roles->roles as $key => $value) {
        $temp = [];

        if (!isset($value['name']) || $value['name'] == '') {
          continue;
        }

        if ($s_query != '') {
          if (strpos(strtolower($value['name']), strtolower($s_query)) === false) {
            continue;
          }
        }

        $temp['name'] = $key;
        $temp['label'] = $value['name'];
        $temp['caps'] = $value['capabilities'];
        $temp['granted'] = count($value['capabilities']);

        $args = [
          'number' => -1,
          'role__in' => [$key],
        ];

        $user_query = new WP_User_Query($args);
        $allUsers = $user_query->get_results();

        $count = 0;
        $userHolder = [];
        if (!empty($allUsers)) {
          foreach ($allUsers as $user) {
            $userHolder[] = $user->user_login;
            $count += 1;
            if ($count > 4) {
              break;
            }
          }
        }

        $temp['users'] = $userHolder;
        $temp['usersCount'] = $user_query->get_total();

        array_push($all_roles, $temp);
      }

      usort($all_roles, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
      });

      $returnData['tableData']['totalFound'] = count($wp_roles->role_objects);
      $returnData['tableData']['roles'] = $all_roles;

      $returnData['tableData']['columns'] = $this->uip_build_role_table_columns();

      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Gets data for specific user
   * @since 2.3.5
   */

  public function uip_get_user_data()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-user-app-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $userID = $utils->clean_ajax_input($_POST['userID']);
      $activityPage = $utils->clean_ajax_input($_POST['activityPage']);

      $user_meta = get_userdata($userID);

      $first_name = $user_meta->first_name;
      $last_name = $user_meta->last_name;
      $full_name = $first_name . ' ' . $last_name;
      $roles = $user_meta->roles;

      //$hasimage = get_avatar($user->ID);
      $image = get_avatar_url($user_meta->ID, ['default' => 'retro']);

      $expiry = get_user_meta($user_meta->ID, 'uip-user-expiry', true);
      $last_login = get_user_meta($user_meta->ID, 'uip_last_login_date', true);
      $last_login_country = get_user_meta($user_meta->ID, 'uip_last_login_country', true);
      $user_notes = get_user_meta($user_meta->ID, 'uip_user_notes', true);
      $profileImage = get_user_meta($user_meta->ID, 'uip_profile_image', true);
      $groups = get_user_meta($user_meta->ID, 'uip_user_group', true);

      if (!is_array($groups)) {
        $groups = [];
      }

      if ($last_login) {
        $last_login = date(get_option('date_format'), strtotime($last_login));
      }

      if (!$last_login_country || $last_login_country == '') {
        $last_login_country = __('Unknown', 'uipress-pro');
      }

      $dateformat = get_option('date_format');
      $formattedCreated = date($dateformat, strtotime($user_meta->user_registered));

      $temp['username'] = $user_meta->user_login;
      $temp['user_email'] = $user_meta->user_email;
      $temp['name'] = $full_name;
      $temp['first_name'] = $user_meta->first_name;
      $temp['last_name'] = $user_meta->last_name;
      $temp['uip_last_login_date'] = $last_login;
      $temp['uip_last_login_country'] = $last_login_country;
      $temp['roles'] = $roles;
      $temp['image'] = $image;
      $temp['initial'] = strtoupper($user_meta->user_login[0]);
      $temp['user_id'] = $user_meta->ID;
      $temp['expiry'] = $expiry;
      $temp['user_registered'] = $formattedCreated;
      $temp['notes'] = $user_notes;
      $temp['uip_profile_image'] = $profileImage;
      $temp['uip_user_group'] = $groups;

      $args = [
        'user_id' => $userID,
        'count' => true,
      ];
      $comments = get_comments($args);

      $args = [
        'public' => true,
      ];

      $output = 'names'; // 'names' or 'objects' (default: 'names')

      $post_types = get_post_types($args, $output);
      $formatted = [];
      foreach ($post_types as $type) {
        $formatted[] = $type;
      }

      $postcount = count_user_posts($userID, $formatted, true);

      $temp['totalComments'] = $comments;
      $temp['totalPosts'] = $postcount;

      $returnData['user'] = $temp;
      $returnData['recentPageViews'] = $this->get_user_page_views($userID);
      $returnData['history'] = $this->get_user_activity($activityPage, $userID);

      echo json_encode($returnData);
    }
    die();
  }

  /**
   * Gets data for user table
   * @since 2.3.5
   */

  public function uip_get_user_roles_for_user_management()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      global $wp_roles;
      $all_roles = [];

      foreach ($wp_roles->roles as $key => $value) {
        $temp = [];
        $temp['name'] = $key;
        $temp['label'] = $value['name'];
        array_push($all_roles, $temp);
      }

      usort($all_roles, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
      });

      $returnData['roles'] = $all_roles;

      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Updates user info
   * @since 2.3.5
   */

  public function uip_update_user()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $user = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['user'])));

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to edit users", 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!filter_var($user['user_email'], FILTER_VALIDATE_EMAIL)) {
        $returnData['error'] = true;
        $returnData['message'] = __('Email is not valid', 'uipress-pro');
        wp_send_json($returnData);
      }

      $user_info = get_userdata($user['user_id']);
      $currentemail = $user_info->user_email;

      //CHECK IF SAME EMAIL - IF NOT CHECK IF NEW ONE EXISTS
      if ($currentemail != $user['user_email']) {
        if (email_exists($user['user_email'])) {
          $returnData['error'] = true;
          $returnData['message'] = __('Email already exists', 'uipress-pro');
          wp_send_json($returnData);
        }
      }

      wp_update_user([
        'ID' => $user['user_id'], // this is the ID of the user you want to update.
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => '',
        'user_email' => $user['user_email'],
      ]);

      if (isset($user['roles']) && is_array($user['roles'])) {
        $userObj = new WP_User($user['user_id']);

        foreach ($user['roles'] as $role) {
          $userObj->add_role($role);
        }
      }

      update_user_meta($user['user_id'], 'uip_user_notes', $user['notes']);
      update_user_meta($user['user_id'], 'uip_profile_image', $user['uip_profile_image']);

      if (isset($user['uip_user_group']) && is_array($user['uip_user_group'])) {
        update_user_meta($user['user_id'], 'uip_user_group', $user['uip_user_group']);
      }

      $returnData['message'] = __('User saved', 'uipress-pro');
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Batch updates roles
   * @since 2.3.5
   */

  public function uip_batch_update_roles()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $allUsers = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['allRecipients'])));
      $settings = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['settings'])));

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to edit users", 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!is_array($allUsers) || count($allUsers) < 1) {
        $returnData['error'] = true;
        $returnData['message'] = __('No users sent to update', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($settings['roles']) || count($settings['roles']) < 1) {
        $returnData['error'] = true;
        $returnData['message'] = __('No roles sent to update', 'uipress-pro');
        wp_send_json($returnData);
      }

      foreach ($allUsers as $user) {
        $userObj = get_user_by('email', $user);

        if ($settings['replaceExisting'] == true) {
          $currentroles = $userObj->roles;

          foreach ($currentroles as $temprole) {
            $userObj->remove_role($temprole);
          }
        }

        foreach ($settings['roles'] as $role) {
          $userObj->add_role($role);
        }
      }

      $returnData['message'] = __('User roles updated', 'uipress-pro');
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Updates role info
   * @since 2.3.5
   */

  public function uip_update_user_role()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $newrole = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['role'])));
      $ogrolename = sanitize_text_field($_POST['originalRoleName']);

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to manage roles", 'uipress-pro');
        wp_send_json($returnData);
      }

      if ($ogrolename == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Original role name required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($newrole['label']) || $newrole['label'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      $capabilities = [];
      if (is_object($newrole['caps'])) {
        foreach ($newrole['caps'] as $key => $value) {
          if ($value == 'true' || $value == true || $value == 'uiptrue') {
            $capabilities[$key] = true;
          } else {
            $capabilities[$key] = false;
          }
        }
      }

      remove_role($ogrolename);
      $status = add_role($ogrolename, $newrole['label'], $capabilities);

      if ($status == null) {
        $returnData['error'] = true;
        $returnData['message'] = __('Something has gone wrong', 'uipress-pro');
        wp_send_json($returnData);
      }

      $redirects = $utils->get_uip_option('role_redirects');

      if (!is_array($redirects)) {
        $redirects = [];
      }

      if (isset($newrole['redirect'])) {
        $redirects[$ogrolename] = $newrole['redirect'];
        $utils->update_uip_option('role_redirects', $redirects);
      }

      $returnData['message'] = __('Role updated', 'uipress-pro');
      wp_send_json($returnData);
      die();
    }
    die();
  }

  /**
   * Logsout user everywhere
   * @since 2.3.5
   */

  public function uip_logout_user_everywhere()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $userid = sanitize_text_field($_POST['userID']);

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __('You don\'t have access to this action', 'uipress-pro');
        wp_send_json($returnData);
      }

      global $wp_session;
      $user_id = $userid;
      $session = wp_get_session_token();
      $sessions = WP_Session_Tokens::get_instance($user_id);
      $sessions->destroy_others($session);

      $returnData['message'] = __('User logged out everywhere', 'uipress-pro');
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * creates new role
   * @since 2.3.5
   */

  public function uip_create_new_role()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $newrole = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['newrole'])));

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to manage roles", 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($newrole['label']) || $newrole['label'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role label is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($newrole['name']) || $newrole['name'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (strpos($newrole['name'], ' ') !== false) {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name cannot contain spaces', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($newrole['label']) || $newrole['label'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role label is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      $capabilities = [];
      if (is_object($newrole['caps'])) {
        foreach ($newrole['caps'] as $key => $value) {
          if ($value == 'true' || $value == true || $value == 'uiptrue') {
            $capabilities[$key] = true;
          } else {
            $capabilities[$key] = false;
          }
        }
      }

      $status = add_role(strtolower($newrole['name']), $newrole['label'], $capabilities);

      $redirects = $utils->get_uip_option('role_redirects');

      if (!is_array($redirects)) {
        $redirects = [];
      }

      if (isset($newrole['redirect'])) {
        $redirects[strtolower($newrole['name'])] = $newrole['redirect'];
        $utils->update_uip_option('role_redirects', $redirects);
      }

      if ($status == null) {
        $returnData['error'] = true;
        $returnData['message'] = __('Unable to add role. Make sure role name is unique', 'uipress-pro');
        wp_send_json($returnData);
      }

      $returnData['message'] = __('Role created', 'uipress-pro');
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Updates role info
   * @since 2.3.5
   */

  public function uip_delete_role()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $role = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['role'])));

      if (!current_user_can('delete_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to manage roles", 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($role['name']) || $role['name'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      $user = wp_get_current_user();
      $currentRoles = $user->roles;

      if (in_array($role['name'], $currentRoles)) {
        $returnData['error'] = true;
        $returnData['message'] = __("You can't delete a role that is currently assigned to yourself", 'uipress-pro');
        wp_send_json($returnData);
      }

      remove_role($role['name']);

      $returnData['message'] = __('Role deleted', 'uipress-pro');
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Deletes multiple roles
   * @since 2.3.5
   */

  public function uip_delete_roles()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $roles = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['roles'])));

      if (!current_user_can('delete_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to manage roles", 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!is_array($roles)) {
        $returnData['error'] = true;
        $returnData['message'] = __('No roles to delete', 'uipress-pro');
        wp_send_json($returnData);
      }

      $errors = [];
      $user = wp_get_current_user();
      $currentRoles = $user->roles;

      foreach ($roles as $role) {
        if (!isset($role->name) || $role->name == '') {
          $errors[] = [
            'message' => __('Role name is required', 'uipress-pro'),
            'role' => $role->name,
          ];
        }

        if (in_array($role->name, $currentRoles)) {
          $errors[] = [
            'message' => __("You can't delete a role that is currently assigned to yourself", 'uipress-pro'),
            'role' => $role->name,
          ];
          continue;
        }

        remove_role($role->name);
      }

      $returnData['message'] = __('Roles deleted', 'uipress-pro');
      $returnData['undeleted'] = $errors;
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Updates role info
   * @since 2.3.5
   */

  public function uip_add_custom_capability()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $role = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['role'])));
      $customcap = sanitize_text_field($_POST['customcap']);

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to add this capability", 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($role['name']) || $role['name'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($role['label']) || $role['label'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (strpos($role['name'], ' ') !== false) {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name cannot contain spaces', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (strpos($customcap, ' ') !== false) {
        $returnData['error'] = true;
        $returnData['message'] = __('Capability name cannot contain spaces', 'uipress-pro');
        wp_send_json($returnData);
      }

      $customcap = strtolower($customcap);

      $currentRole = get_role($role['name']);
      $currentRole->add_cap($customcap, false);
      $currentcaps = $currentRole->capabilities;

      remove_role($role['name']);
      $status = add_role($role['name'], $role['label'], $currentcaps);

      if ($status == null) {
        $returnData['error'] = true;
        $returnData['message'] = __('Unable to add capability. Make sure role name is unique', 'uipress-pro');
        wp_send_json($returnData);
      }

      $returnData['message'] = __('Capability added', 'uipress-pro');
      $returnData['allcaps'] = $utils->get_all_role_capabilities();
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Updates role info
   * @since 2.3.5
   */

  public function uip_remove_custom_capability()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $role = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['role'])));
      $customcap = sanitize_text_field($_POST['customcap']);

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to add this capability", 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($role['name']) || $role['name'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($role['label']) || $role['label'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (strpos($role['name'], ' ') !== false) {
        $returnData['error'] = true;
        $returnData['message'] = __('Role name cannot contain spaces', 'uipress-pro');
        wp_send_json($returnData);
      }

      $customcap = strtolower($customcap);

      $currentRole = get_role($role['name']);
      $currentRole->remove_cap($customcap, false);
      $currentcaps = $currentRole->capabilities;

      remove_role($role['name']);
      $status = add_role($role['name'], $role['label'], $currentcaps);

      if ($status == null) {
        $returnData['error'] = true;
        $returnData['message'] = __('Unable to delete capability. Make sure role name is unique', 'uipress-pro');
        wp_send_json($returnData);
      }

      $returnData['message'] = __('Capability deleted', 'uipress-pro');
      $returnData['allcaps'] = $utils->get_all_role_capabilities();
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Updates user info
   * @since 2.3.5
   */

  public function uip_add_new_user()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $user = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['user'])));

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to create users", 'uipress-pro');
        wp_send_json($returnData);
      }

      //CHECK USERNAME EXISTS
      if (username_exists($user['username'])) {
        $returnData['error'] = true;
        $returnData['message'] = __('Username already exists', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!validate_username($user['username'])) {
        $returnData['error'] = true;
        $returnData['message'] = __('Username is not valid', 'uipress-pro');
        wp_send_json($returnData);
      }

      //CHECK IF SAME EMAIL - IF NOT CHECK IF NEW ONE EXISTS
      if (email_exists($user['user_email'])) {
        $returnData['error'] = true;
        $returnData['message'] = __('Email already exists', 'uipress-pro');
        wp_send_json($returnData);
      }

      //CHECK IF EMAIL IS VALID
      if (!filter_var($user['user_email'], FILTER_VALIDATE_EMAIL)) {
        $returnData['error'] = true;
        $returnData['message'] = __('Email is not valid', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($user['password']) || ($user['password'] = '')) {
        $returnData['error'] = true;
        $returnData['error'] = __('Password is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      $user_id = wp_create_user($user['username'], $user['password'], $user['user_email']);

      if (is_wp_error($user_id)) {
        $error_string = $user_id->get_error_message();
        $returnData['error'] = true;
        $returnData['error'] = $error_string;
        wp_send_json($returnData);
      }

      wp_update_user([
        'ID' => $user_id, // this is the ID of the user you want to update.
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => '',
        'user_email' => $user['user_email'],
      ]);

      if (isset($user['roles']) && is_array($user['roles'])) {
        $userObj = new WP_User($user_id);

        foreach ($user['roles'] as $role) {
          $userObj->add_role($role);
        }
      }

      if (isset($user['notes'])) {
        update_user_meta($user_id, 'uip_user_notes', $user['notes']);
      }
      if (isset($user['uip_profile_image'])) {
        update_user_meta($user_id, 'uip_profile_image', $user['uip_profile_image']);
      }
      if (isset($user['uip_user_group']) && is_array($user['uip_user_group'])) {
        update_user_meta($user_id, 'uip_user_group', $user['uip_user_group']);
      }

      $returnData['message'] = __('User created', 'uipress-pro');
      $returnData['userID'] = $user_id;
      wp_send_json($returnData);
      die();
    }
    die();
  }

  /**
   * Sends user reset pass
   * @since 2.3.5
   */

  public function uip_reset_password()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $userid = sanitize_text_field($_POST['userID']);

      if (!current_user_can('edit_users')) {
        $returnData['error'] = __("You don't have sufficent priviledges to edit this user", 'uipress-pro');
        echo json_encode($returnData);
        die();
      }

      $user = get_user_by('id', $userid);
      $username = $user->user_login;
      $status = retrieve_password($username);

      if ($status === true) {
        $returnData['message'] = __('Password reset link sent', 'uipress-pro');
        wp_send_json($returnData);
      } else {
        $returnData['error'] = true;
        $returnData['message'] = __('Unable to send password reset email at the moment', 'uipress-pro');
        wp_send_json($returnData);
      }
    }
    die();
  }

  /**
   * Sends user reset pass
   * @since 2.3.5
   */

  public function uip_password_reset_multiple()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $allIDS = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['allIDS'])));

      if (!is_array($allIDS)) {
        $returnData['error'] = true;
        $returnData['message'] = __('No users sent to reset passwords!', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!current_user_can('edit_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to edit this user", 'uipress-pro');
        wp_send_json($returnData);
      }

      $errors = [];
      foreach ($allIDS as $userID) {
        $current = get_user_by('id', $userID);
        $username = $current->user_login;

        $status = retrieve_password($username);

        if (!$status) {
          $errors[] = [
            'message' => __('Unable to send password reset email', 'uipress-pro'),
            'user' => sprintf(__('User ID: %s', 'uipress-pro'), $userID),
          ];
          continue;
        }
      }

      $returnData['message'] = __('Password reset links succesfully sent', 'uipress-pro');
      $returnData['undeleted'] = $errors;
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Sends message to given user
   * @since 2.3.5
   */

  public function uip_send_message()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $message = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['message'])));
      $allrecip = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['allRecipients'])));

      if (!isset($message['subject']) || $message['subject'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Subject is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($message['replyTo']) || $message['replyTo'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Reply to email is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!isset($message['message']) || $message['message'] == '') {
        $returnData['error'] = true;
        $returnData['message'] = __('Message is required', 'uipress-pro');
        wp_send_json($returnData);
      }

      //ARE WE BATCHING
      $email = $allrecip;
      $batchemail = true;

      $subject = $message['subject'];
      $content = stripslashes(html_entity_decode($message['message']));
      $replyTo = $message['replyTo'];

      $headers[] = 'From: ' . ' ' . get_bloginfo('name') . '<' . $replyTo . '>';
      $headers[] = 'Reply-To: ' . ' ' . $replyTo;
      $headers[] = 'Content-Type: text/html; charset=UTF-8';

      $wrap = '<table style="box-sizing:border-box;border-color:inherit;text-indent:0;padding:0;margin:64px auto;width:464px"><tbody>';
      $wrapend = '</tbody></table>';
      $formatted = $wrap . $content . $wrapend;

      add_action('wp_mail_failed', [$this, 'log_uip_mail_error'], 10, 1);

      foreach ($email as $mail) {
        $headers[] = 'Bcc: ' . $mail;
      }

      $status = wp_mail($replyTo, $subject, $formatted, $headers);

      if (!$status) {
        $returnData['error'] = true;
        $returnData['message'] = __('Unable to send mail at this time', 'uipress-pro');
        wp_send_json($returnData);
      }

      $returnData['message'] = __('Message sent', 'uipress-pro');
      wp_send_json($returnData);
    }
    die();
  }

  public function log_uip_mail_error($wp_error)
  {
    error_log(json_encode($wp_error));
  }

  /**
   * Deletes user
   * @since 2.3.5
   */

  public function uip_delete_user()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $userID = sanitize_text_field($_POST['userID']);

      if (get_current_user_id() == $userID) {
        $returnData['error'] = true;
        $returnData['message'] = __("You can't delete yourself!", 'uipress-pro');
        wp_send_json($returnData);
      }

      if (current_user_can('delete_users')) {
        $currentID = get_current_user_id();
        $status = wp_delete_user($userID, $currentID);

        if ($status) {
          $returnData['message'] = __('User successfully deleted', 'uipress-pro');
          wp_send_json($returnData);
        } else {
          $returnData['error'] = true;
          $returnData['message'] = __("You don't have sufficent priviledges to delete this user", 'uipress-pro');
          wp_send_json($returnData);
        }
      } else {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to delete this user", 'uipress-pro');
        wp_send_json($returnData);
      }
    }
    die();
  }

  /**
   * Deletes user
   * @since 2.39
   */

  public function uip_delete_multiple_users()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $allIDS = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['allIDS'])));

      if (!is_array($allIDS)) {
        $returnData['error'] = true;
        $returnData['message'] = __('No users sent to delete!', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!current_user_can('delete_users')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to delete this user", 'uipress-pro');
        wp_send_json($returnData);
      }

      $errors = [];
      $currentID = get_current_user_id();
      foreach ($allIDS as $userID) {
        if (get_current_user_id() == $userID) {
          $errors[] = [
            'message' => __("You can't delete yourself", 'uipress-pro'),
            'user' => sprintf(__('User ID: %s', 'uipress-pro'), $userID),
          ];
          continue;
        }

        $status = wp_delete_user($userID, $currentID);

        if (!$status) {
          $errors[] = [
            'message' => __('Unable to delete this user', 'uipress-pro'),
            'user' => sprintf(__('User ID: %s', 'uipress-pro'), $userID),
          ];
          continue;
        }
      }

      $returnData['message'] = __('Users successfully deleted', 'uipress-pro');
      $returnData['undeleted'] = $errors;
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Deletes actions
   * @since 2.3.9
   */

  public function uip_delete_multiple_actions()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $allIDS = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['allIDS'])));

      if (!is_array($allIDS)) {
        $returnData['error'] = true;
        $returnData['message'] = __('No actions sent to delete!', 'uipress-pro');
        wp_send_json($returnData);
      }

      if (!current_user_can('delete_posts')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to delete these actions", 'uipress-pro');
        wp_send_json($returnData);
      }

      //Get and prep database
      $history = new uip_history();
      $database = $history->uip_history_get_database();
      $history->uip_history_prep_database($database);

      // Convert the post IDs array to a comma-separated string for use in the SQL query.
      $post_ids_string = implode(',', array_map('intval', $allIDS));

      // Prepare the delete query with the IN operator.
      $delete_query = "DELETE FROM `uip_history` WHERE `ID` IN ({$post_ids_string})";

      // Execute the delete query on the custom database.
      $deleted_rows = $database->query($delete_query);

      $errors = [];

      $returnData['message'] = __('Actions successfully deleted', 'uipress-pro');
      $returnData['undeleted'] = $errors;
      wp_send_json($returnData);
    }
    die();
  }

  /**
   * Deletes all history
   * @since 2.3.9
   */

  public function uip_delete_all_history()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();

      if (!current_user_can('delete_posts')) {
        $returnData['error'] = true;
        $returnData['message'] = __("You don't have sufficent priviledges to delete all actions", 'uipress-pro');
        wp_send_json($returnData);
      }

      //Get and prep database
      $history = new uip_history();
      $database = $history->uip_history_get_database();
      $history->uip_history_prep_database($database);

      // Prepare the delete query to truncate the posts table.
      $delete_query = 'TRUNCATE `uip_history`';

      // Execute the delete query on the custom database.
      $deleted_rows = $database->query($delete_query);

      // Check the result.
      if ($deleted_rows !== false) {
        $returnData['message'] = __('Actions successfully deleted', 'uipress-pro');
        $returnData['undeleted'] = [];
        wp_send_json($returnData);
      } else {
        $returnData['error'] = true;
        $returnData['message'] = __('Unable to delete all histroy items', 'uipress-pro');
        wp_send_json($returnData);
      }
    }
    die();
  }
}
