<?php
if (!defined('ABSPATH')) {
  exit();
}

class uip_folders extends uip_app
{
  public function __construct()
  {
    $this->uip_site_settings_object = false;
    $this->limitToAuthor = false;
    $this->limitToType = false;
    $this->enabledFor = false;
  }

  /**
   * Starts the folder features
   * @since 3.0.9
   */

  public function run()
  {
    $this->formatOptions();
    //Load folders to media template
    if (in_array('attachment', $this->enabledFor)) {
      add_action('wp_enqueue_media', function () {
        //Load Uip class
        add_action('wp_head', [$this, 'add_head_scripts'], 10);
        //Folder vars
        add_action('wp_head', [$this, 'print_folder_vars'], 10);
        add_action('admin_head', [$this, 'print_folder_vars'], 10);
        //Media template
        add_action('admin_footer', [$this, 'build_media_template'], 10);
        add_action('wp_footer', [$this, 'build_media_template'], 10);
      });
      //Add image to current folder
      add_action('add_attachment', [$this, 'add_to_current_folder']);
    }

    //Add folders to post types
    add_action('current_screen', [$this, 'start_post_folders'], 10);

    //Media modals prepare attachments
    add_filter('ajax_query_attachments_args', [$this, 'legacy_media_filter']);
    add_filter('wp_prepare_attachment_for_js', [$this, 'pull_meta_to_attachments'], 10, 3);

    //Ajax
    add_action('wp_ajax_uip_folders_get_base_folders', [$this, 'uip_folders_get_base_folders']);
    add_action('wp_ajax_uip_folders_get_folder_content', [$this, 'uip_folders_get_folder_content']);
    add_action('wp_ajax_uip_folders_update_item_folder', [$this, 'uip_folders_update_item_folder']);
    add_action('wp_ajax_uip_folders_add_item_to_folder', [$this, 'uip_folders_add_item_to_folder']);
    add_action('wp_ajax_uip_folders_create_folder', [$this, 'uip_folders_create_folder']);
    add_action('wp_ajax_uip_folders_delete_folder', [$this, 'uip_folders_delete_folder']);
    add_action('wp_ajax_uip_folders_update_folder', [$this, 'uip_folders_update_folder']);
  }

  public function formatOptions()
  {
    $utils = new uip_util();
    $limitToAuthor = $utils->return_global_option_value('contentFolders', 'limitToAuthor');
    $foldersPerType = $utils->return_global_option_value('contentFolders', 'perType');
    $enabledFor = $utils->return_global_option_value('contentFolders', 'enabledForTypes');

    if (!is_array($enabledFor)) {
      $enabledFor = [];
    }

    $this->limitToAuthor = $limitToAuthor;
    $this->limitToType = $foldersPerType;
    $this->enabledFor = $enabledFor;
  }

  /**
   * Adds folders to posts and pages
   * @since 3.1.1
   */

  public function start_post_folders()
  {
    if (!is_user_logged_in()) {
      return;
    }

    if (!is_array($this->enabledFor) || empty($this->enabledFor)) {
      return;
    }

    //Check we are on an edit screen
    $screen = get_current_screen();
    if ($screen->base != 'edit' && $screen->base != 'upload') {
      return;
    }

    add_action('pre_get_posts', [$this, 'uip_filter_by_folder']);

    //Only load folders for media this way if the mode is list
    if ($screen->post_type == 'attachment') {
      if (isset($_GET['mode'])) {
        if ($_GET['mode'] == 'grid') {
          return;
        }
      } else {
        $mode = get_user_option('media_library_mode', get_current_user_id());
        if ($mode == 'grid') {
          return;
        }
      }
    }

    if (property_exists($screen, 'post_type')) {
      if (in_array($screen->post_type, $this->enabledFor)) {
        add_action('admin_head', [$this, 'print_folder_vars'], 10);
        add_action('all_admin_notices', [$this, 'build_post_folders']);
        add_filter('manage_' . $screen->post_type . '_posts_columns', [$this, 'uip_add_drag_column']);
        add_action('manage_posts_custom_column', [$this, 'uip_add_drag_icon'], 10, 2);
        if ($screen->post_type == 'page') {
          add_action('manage_pages_custom_column', [$this, 'uip_add_drag_icon'], 10, 2);
        }
        if ($screen->post_type == 'attachment') {
          add_filter('manage_media_columns', [$this, 'uip_add_drag_column']);
          add_action('manage_media_custom_column', [$this, 'uip_add_drag_icon'], 10, 2);
        }
      }
    }
  }

  /**
   * Adds draggable column to posts for folders
   * @since 2.2
   */
  function uip_filter_by_folder($query)
  {
    if (isset($_GET['uip_folder'])) {
      $folder_id = sanitize_text_field($_GET['uip_folder']);
      if ($folder_id != '') {
        if ($folder_id == 'all') {
          return;
        }
        //Get original meta query
        $meta_query = $query->get('meta_query');

        if (!is_array($meta_query)) {
          $meta_query = [];
        }

        //Add our meta query to the original meta queries
        if ($folder_id == 'nofolder') {
          $meta_query[] = ['key' => 'admin2020_folder', 'value' => $folder_id, 'compare' => 'NOT EXISTS'];
        } else {
          $meta_query[] = [
            'key' => 'uip-folder-parent',
            'value' => serialize(strval($folder_id)),
            'compare' => 'LIKE',
          ];
        }
        $query->set('meta_query', $meta_query);
      }
    }
  }

  /**
   * Adds draggable column to posts for folders
   * @since 2.2
   */
  public function uip_add_drag_column($columns)
  {
    $newcolumns['uip_draggable'] = '';
    $result = array_merge($newcolumns, $columns);
    return $result;
  }

  /**
   * Adds draggable icon to posts
   * @since 2.2
   */
  function uip_add_drag_icon($column_id, $post_id)
  {
    switch ($column_id) {
      case 'uip_draggable':
        $data = "
		<div class='uip-flex uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-drag uip-border-round uip-ratio-1-1 uip-post-drag' data-id='{$post_id}' draggable='true'>
        	<span class='uip-icon uip-text-xl'>drag_indicator</span>
        </div>";
        echo $data;
        break;
    }
  }

  /**
   * Adds folder id to default wp media views
   * @since 1.4
   */
  public function pull_meta_to_attachments($response, $attachment, $meta)
  {
    $response['imageID'] = $attachment->ID;
    $response['properties']['imageID'] = $attachment->ID;

    if (isset($_REQUEST['query']['uip_folder_id'])) {
      $folderID = sanitize_text_field($_REQUEST['query']['uip_folder_id']);
      $response['current_folder'] = $folderID;
      $response['properties']['current_folder'] = $folderID;
    }

    return $response;
  }

  public function add_to_current_folder($attachment_id)
  {
    if (isset($_REQUEST['uip_folder_id'])) {
      $folderID = sanitize_text_field($_REQUEST['uip_folder_id']);
      if (is_numeric($folderID) && $folderID != 'all') {
        update_post_meta($attachment_id, 'uip-folder-parent', [$folderID]);
      }
    }
  }

  /**
   * Filters media by folder
   * @since 1.4
   */
  public function legacy_media_filter($args)
  {
    if (isset($_REQUEST['query']['uip_folder_id'])) {
      $folderID = sanitize_text_field($_REQUEST['query']['uip_folder_id']);

      if ($folderID != '' || $folderID != 'all') {
        $args['meta_query'] = [
          [
            'key' => 'uip-folder-parent',
            'value' => serialize(strval($folderID)),
            'compare' => 'LIKE',
          ],
        ];
      }
      if ($folderID == 'all') {
        $key = array_search('uip-folder-parent', array_column($args['meta_query'], 'key'));
        if ($key != '') {
          unset($args['meta_query'][$key]);
        }
      }
    }

    return $args;
  }

  /**
   * Updates folder
   * @since 3.0.93
   */
  public function uip_folders_update_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $folderID = sanitize_text_field($_POST['folderId']);
      $title = sanitize_text_field($_POST['title']);
      $color = sanitize_text_field($_POST['color']);

      //No folder id
      if (!$folderID || $folderID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No folder to update', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Folder does not exist
      if (!get_post_status($folderID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Folder does not exist', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Incorrect caps
      if (!current_user_can('edit_post', $folderID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('You do not have the correct capabilities to update this folder', 'uipress-pro');
        wp_send_json($returndata);
      }

      //Tittle is blank
      if (!$title || $title == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Folder title is required', 'uipress-pro');
        wp_send_json($returndata);
      }

      $my_post = [
        'ID' => $folderID,
        'post_title' => wp_strip_all_tags($title),
      ];

      //Update the post into the database
      $status = wp_update_post($my_post);

      //Something went wrong
      if (!$status) {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to update the folder right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      if ($color && $color != '') {
        update_post_meta($folderID, 'uip-folder-color', $color);
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Deletes folder
   * @since 3.0.93
   */
  public function uip_folders_delete_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $folderID = sanitize_text_field($_POST['folderId']);
      $postTypes = $utils->clean_ajax_input(json_decode(stripslashes($_POST['postTypes'])));

      //No folder id
      if (!$folderID || $folderID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No folder to delete', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Folder does not exist
      if (!get_post_status($folderID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('Folder does not exist', 'uipress-pro');
        wp_send_json($returndata);
      }
      //Incorrect caps
      if (!current_user_can('delete_post', $folderID)) {
        $returndata['error'] = true;
        $returndata['message'] = __('You do not have the correct capabilities to delete this folder', 'uipress-pro');
        wp_send_json($returndata);
      }

      $status = wp_delete_post($folderID, true);

      //Something went wrong
      if (!$status) {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to delete the folder right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      $this->removeFromFolder($folderID, $postTypes);

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Creates new folder
   * @since 3.0.93
   */
  public function uip_folders_create_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $folderParent = sanitize_text_field($_POST['folderParent']);
      $folderName = sanitize_text_field($_POST['folderName']);
      $folderColor = sanitize_text_field($_POST['folderColor']);
      $postTypes = $utils->clean_ajax_input(json_decode(stripslashes($_POST['postTypes'])));

      if (!$folderParent || $folderParent == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to create content folder right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!$folderName || $folderName == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Folder name is required', 'uipress-pro');
        wp_send_json($returndata);
      }

      $updateArgs = [
        'post_title' => wp_strip_all_tags($folderName),
        'post_status' => 'publish',
        'post_type' => 'uip-ui-folder',
      ];

      $updatedID = wp_insert_post($updateArgs);

      if (!$updatedID || $updatedID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('Unable to create content folder right now', 'uipress-pro');
        wp_send_json($returndata);
      }

      if ($folderParent != 'uipfalse') {
        $folderParent = [$folderParent];
      }
      update_post_meta($updatedID, 'uip-folder-parent', $folderParent);
      update_post_meta($updatedID, 'uip-folder-color', $folderColor);
      update_post_meta($updatedID, 'uip-folder-for', $postTypes[0]);

      $temp = [];
      $temp['id'] = $updatedID;
      $temp['title'] = $folderName;
      $temp['parent'] = $folderParent;
      $temp['count'] = 0;
      $temp['color'] = $folderColor;
      $temp['content'] = [];
      $temp['canDelete'] = true;
      $temp['type'] = 'uip-ui-folder';

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;
      $returndata['folder'] = $temp;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Updates item folder after drag and drop
   * @since 3.0.93
   */
  public function uip_folders_update_item_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $item = $utils->clean_ajax_input(json_decode(stripslashes($_POST['item'])));
      $newParent = sanitize_text_field($_POST['newParent']);

      if (!$item || empty($item)) {
        $returndata['error'] = true;
        $returndata['message'] = __('No item to update', 'uipress-pro');
        wp_send_json($returndata);
      }

      if ($item->type == 'uip-ui-folder') {
        if ($newParent != 'uipfalse') {
          $newParent = [$newParent];
        }
        update_post_meta($item->id, 'uip-folder-parent', $newParent);
      } else {
        $current = get_post_meta($item->id, 'uip-folder-parent', true);

        if (!$current || !is_array($current)) {
          $current = [];
        }

        //If old parent is in current parent, remove it
        if (in_array($item->parent, $current)) {
          $currentid = $item->parent;

          $new = [];
          foreach ($current as $fol) {
            if ($fol == $currentid) {
              $fol = $newParent;
            }
            $new[] = $fol;
          }
          $current = array_values(array_unique($new));
        } else {
          array_push($current, $newParent);
        }
        update_post_meta($item->id, 'uip-folder-parent', $current);
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Adds item to folder
   * @since 3.0.93
   */
  public function uip_folders_add_item_to_folder()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $ids = $utils->clean_ajax_input(json_decode(stripslashes($_POST['IDS'])));
      $newParent = sanitize_text_field($_POST['newParent']);
      $parentFolder = sanitize_text_field($_POST['parentFolder']);
      $limitToType = sanitize_text_field($_POST['limitToType']);
      $postTypes = $utils->clean_ajax_input(json_decode(stripslashes($_POST['postTypes'])));

      if (!$ids || !is_array($ids)) {
        $returndata['error'] = true;
        $returndata['message'] = __('No items to update', 'uipress-pro');
        wp_send_json($returndata);
      }

      $returndata = [];

      //Loop through item ids
      foreach ($ids as $itemid) {
        $postType = get_post_type($itemid);

        if ($itemid == $newParent) {
          continue;
        }

        //Update for folder
        if ($postType == 'uip-ui-folder') {
          if ($newParent != 'uipfalse') {
            $newParent = [$newParent];
          }
          update_post_meta($itemid, 'uip-folder-parent', $newParent);
          $returndata['folder'] = $this->format_folder_for_app($itemid, $limitToType, $newParent, $postTypes);
        }
        //Update for other post types
        else {
          $current = get_post_meta($itemid, 'uip-folder-parent', true);

          if (!$current || !is_array($current)) {
            $current = [];
          }

          $current[] = $newParent;
          $current = array_values(array_unique($current));

          //If moving out of folder
          if ($parentFolder && $parentFolder != 'all' && $parentFolder > 0) {
            $current = array_diff($current, [$parentFolder]);
          }

          update_post_meta($itemid, 'uip-folder-parent', $current);
        }
      }

      $message = __('Item moved to folder', 'uipress-pro');
      if (count($ids) > 1) {
        $message = __('Items moved to folder', 'uipress-pro');
      }

      //Return data to app
      $returndata['success'] = true;
      $returndata['message'] = $message;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Formats a folder for the frontend app
   * @since 3.0.9
   */
  public function format_folder_for_app($id, $limitToType, $parent, $postTypes)
  {
    $link = get_permalink($id);
    $editLink = get_edit_post_link($id, '&');
    $type = get_post_type($id);
    $canDelete = current_user_can('delete_post', $id);

    $temp = [];
    $temp['id'] = $id;
    $temp['title'] = get_the_title($id);
    $temp['status'] = get_post_status($id);
    $temp['edit_href'] = $editLink;
    $temp['view_href'] = $link;
    $temp['type'] = $type;
    $temp['canDelete'] = $canDelete;
    $temp['parent'] = $parent;

    if ($type == 'uip-ui-folder') {
      $temp['count'] = $this->get_folder_content_count($id, $postTypes, $authorLimit, $limitToType);
      $temp['color'] = get_post_meta($id, 'uip-folder-color', true);
    }

    return $temp;
  }

  /**
   * Gets content for give folder
   * @since 3.0.9
   */
  public function uip_folders_get_folder_content()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $postTypes = $utils->clean_ajax_input(json_decode(stripslashes($_POST['postTypes'])));
      $folderID = sanitize_text_field($_POST['id']);
      $authorLimit = sanitize_text_field($_POST['limitToAuthor']);
      $limitToType = sanitize_text_field($_POST['limitToType']);
      $ogTypes = $postTypes;

      if (!$folderID || $folderID == '') {
        $returndata['error'] = true;
        $returndata['message'] = __('No folder given to fetch content for', 'uipress-pro');
        wp_send_json($returndata);
      }

      if (!is_array($postTypes) || empty($postTypes)) {
        $postTypes = [];
      }

      if (!in_array('uip-ui-folder', $postTypes)) {
        $postTypes[] = 'uip-ui-folder';
      }

      //Get folder contents
      $args = [
        'post_type' => 'uip-ui-folder',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft', 'inherit'],
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => [
          [
            'key' => 'uip-folder-parent',
            'value' => serialize(strval($folderID)),
            'compare' => 'LIKE',
          ],
        ],
      ];

      if ($limitToType == true && $limitToType != 'uipfalse') {
        $args['meta_query'][] = [
          'relation' => 'OR',
          [
            'key' => 'uip-folder-for',
            'value' => $ogTypes[0],
            'compare' => '=',
          ],
          [
            'key' => 'uip-folder-for',
            'compare' => 'NOT EXISTS',
          ],
        ];
      }

      if ($authorLimit == true) {
        $args['author'] = get_current_user_id();
      }

      $query = new WP_Query($args);
      $totalFound = $query->found_posts;
      $foundPosts = $query->get_posts();

      $formatted = [];
      foreach ($foundPosts as $post) {
        $link = get_permalink($post->ID);
        $editLink = get_edit_post_link($post->ID, '&');
        $type = get_post_type($post->ID);
        $canDelete = current_user_can('delete_post', $post->ID);

        $temp = [];
        $temp['id'] = $post->ID;
        $temp['title'] = $post->post_title;
        $temp['status'] = $post->post_status;
        $temp['edit_href'] = $editLink;
        $temp['view_href'] = $link;
        $temp['type'] = $type;
        $temp['canDelete'] = $canDelete;
        $temp['parent'] = $folderID;

        if ($type == 'uip-ui-folder') {
          $temp['count'] = $this->get_folder_content_count($post->ID, $postTypes, $authorLimit, $limitToType);
          $temp['color'] = get_post_meta($post->ID, 'uip-folder-color', true);
        }

        $formatted[] = $temp;
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;
      $returndata['content'] = $formatted;
      $returndata['totalFound'] = $totalFound;
      $returndata['folderCount'] = $this->get_folder_content_count($folderID, $postTypes, $authorLimit, $limitToType);

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Gtes user base folders
   * @since 3.0.9
   */
  public function uip_folders_get_base_folders()
  {
    if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uip-security-nonce', 'security') > 0) {
      $utils = new uip_util();
      $authorLimit = sanitize_text_field($_POST['limitToAuthor']);
      $limitToType = sanitize_text_field($_POST['limitToType']);
      $types = (array) $utils->clean_ajax_input(json_decode(stripslashes($_POST['postTypes'])));
      $ogTypes = $types;

      if (!is_array($types) || empty($types)) {
        $types = [];
      }

      ////
      ///Get base folders
      ////
      $args = [
        'post_type' => 'uip-ui-folder',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => [
          [
            'relation' => 'OR',
            [
              'key' => 'uip-folder-parent',
              'value' => 'uipfalse',
              'compare' => '=',
            ],
          ],
        ],
      ];

      if ($limitToType == true && $limitToType != 'uipfalse') {
        $metaQuery[] = [
          'relation' => 'OR',
          [
            'key' => 'uip-folder-for',
            'value' => $ogTypes[0],
            'compare' => '=',
          ],
          [
            'key' => 'uip-folder-for',
            'compare' => 'NOT EXISTS',
          ],
        ];
        $args['meta_query'][] = $metaQuery;
      }

      if ($authorLimit == true) {
        $args['author'] = get_current_user_id();
      }

      $query = new WP_Query($args);
      $foundFolders = $query->get_posts();

      $formattedFolders = [];
      foreach ($foundFolders as $folder) {
        $canDelete = current_user_can('delete_post', $folder->ID);

        $temp = [];
        $temp['id'] = $folder->ID;
        $temp['title'] = $folder->post_title;
        $temp['parent'] = 'uipfalse';
        $temp['count'] = $this->get_folder_content_count($folder->ID, $types, $authorLimit, $limitToType);
        $temp['color'] = get_post_meta($folder->ID, 'uip-folder-color', true);
        $temp['type'] = 'uip-ui-folder';
        $temp['content'] = [];
        $temp['canDelete'] = $canDelete;
        $formattedFolders[] = $temp;
      }

      //Get total
      $totals = (array) wp_count_posts($ogTypes[0]);
      $total = 0;
      foreach ($totals as $key => $value) {
        if ($key != 'trash' && $key != 'auto-draft' && $key) {
          $total = $total + $value;
        }
      }

      //Return data to app
      $returndata = [];
      $returndata['success'] = true;
      $returndata['baseFolders'] = $formattedFolders;
      $returndata['total'] = $total;

      wp_send_json($returndata);
    }
    die();
  }

  /**
   * Counts folder content
   * @since 3.0.92
   */

  public function get_folder_content_count($folderID, $postTypes, $authorLimit, $limitToType)
  {
    $ogTypes = $postTypes;
    if (!$postTypes || empty($postTypes)) {
      $args = ['public' => true];
      $output = 'names';
      $operator = 'and';
      $types = get_post_types($args, $output, $operator);
      $postTypes = [];
      foreach ($types as $type) {
        $postTypes[] = $type;
      }
    }

    if (!in_array('uip-ui-folder', $postTypes)) {
      $postTypes[] = 'uip-ui-folder';
    }
    //Get folder count
    $args = [
      'post_type' => $postTypes,
      'posts_per_page' => -1,
      'post_status' => ['publish', 'draft', 'inherit'],
      'fields' => 'ids',
      'meta_query' => [
        [
          'key' => 'uip-folder-parent',
          'value' => serialize(strval($folderID)),
          'compare' => 'LIKE',
        ],
      ],
    ];

    if ($limitToType == true && $limitToType != 'uipfalse') {
      $args['meta_query'][] = [
        'relation' => 'OR',
        [
          'key' => 'uip-folder-for',
          'value' => $ogTypes[0],
          'compare' => '=',
        ],
        [
          'key' => 'uip-folder-for',
          'compare' => 'NOT EXISTS',
        ],
      ];
    }

    if ($authorLimit == true) {
      $args['author'] = get_current_user_id();
    }

    $query = new WP_Query($args);
    $totalInFolder = $query->found_posts;
    if ($totalInFolder == null) {
      $totalInFolder = 0;
    }
    return $totalInFolder;
  }

  /**
   * Removes folder from items
   * @since 3.0.93
   */
  public function removeFromFolder($folderID, $postTypes)
  {
    //Get all posts in this folder and remove the id
    if (!$postTypes || empty($postTypes)) {
      $args = ['public' => true];
      $output = 'names';
      $operator = 'and';
      $types = get_post_types($args, $output, $operator);
      $postTypes = [];
      foreach ($types as $type) {
        $postTypes[] = $type;
      }
    }

    if (!in_array('uip-ui-folder', $postTypes)) {
      $postTypes[] = 'uip-ui-folder';
    }
    //Get folder contents
    $args = [
      'post_type' => $postTypes,
      'posts_per_page' => -1,
      'post_status' => ['publish', 'draft', 'inherit'],
      'meta_query' => [
        [
          'key' => 'uip-folder-parent',
          'value' => serialize(strval($folderID)),
          'compare' => 'LIKE',
        ],
      ],
    ];

    $query = new WP_Query($args);
    $foundPosts = $query->get_posts();

    foreach ($foundPosts as $post) {
      $currentFolders = get_post_meta($post->id, 'uip-folder-parent', true);
      $type = get_post_type($post->ID);

      if (!is_array($currentFolders)) {
        $currentFolders = [];
      }

      if ($type != 'uip-ui-folder') {
        if (in_array($folderID, $currentFolders)) {
          $new = [];
          foreach ($current as $fol) {
            if ($fol != $folderID) {
              $new[] = $fol;
            }
          }
          $current = array_values(array_unique($new));
          update_post_meta($post->id, 'uip-folder-parent', $current);
        }
      }

      //Recursively remove folders inside folders

      if ($type == 'uip-ui-folder') {
        if (current_user_can('delete_post', $post->ID)) {
          wp_delete_post($post->ID, true);
        }
        $this->removeFromFolder($post->ID, $postTypes);
      }
    }
  }

  /**
   * Builds media template
   * @since 3.0.9
   */
  public function build_media_template()
  {
    if (is_rtl()) {
      $styleSRC = uip_plugin_url . 'assets/css/uip-app-rtl.css';
    } else {
      $styleSRC = uip_plugin_url . 'assets/css/uip-app.css';
    }
    $icons = uip_plugin_url . 'assets/css/uip-icons.css';
    ?>
	
	
	<script type="text/html" id="tmpl-media-frame_custom">
			
		  <style>
		  	@import "<?php echo $styleSRC; ?>";
			  @import "<?php echo $icons; ?>";
			  .uploader-window{
				  display:none !important;
			  }
		  </style>
		  
		  
		  <div class="uip-flex uip-flex-wrap uip-h-100p uip-text-normal uip-flex-no-wrap uip-flex-wrap-mobile uip-gap-s">
		  
		  	
			<div class="uip-w-100p-mobile uip-body-font uip-position-relative" id="uip-folder-app" style="font-size:14px;margin-top:12px">
			</div>
		  
			<div class="uip-flex-grow uip-position-relative">
		  
			  <div class="media-frame-title" id="media-frame-title"></div>
			  <h2 class="media-frame-menu-heading"><?php _ex('Actions', 'media modal menu actions'); ?></h2>
			  <button type="button" class="button button-link media-frame-menu-toggle" aria-expanded="false">
				<?php _ex('Menu', 'media modal menu'); ?>
				<span class="dashicons dashicons-arrow-down" aria-hidden="true"></span>
			  </button>
			  <div class="media-frame-menu"></div>
		  
			  <div class="media-frame-tab-panel">
				<div class="media-frame-router"></div>
				<div class="media-frame-content"></div>
			  </div>
			</div>
		  
		  </div>
		  
		  <div class="media-frame-toolbar"></div>
		  <div class="media-frame-uploader"></div>
		  
		  <?php wp_print_script_tag([
      'id' => 'uip-folders-app',
      'src' => uip_pro_plugin_url . 'admin/extensions/folders/js/uip-folders.min.js?ver=' . uip_pro_plugin_version,
      'type' => 'module',
      'defer' => true,
      'postType' => 'attachment',
      'limitToAuthor' => '' . $this->limitToAuthor . '',
      'limitToType' => '' . $this->limitToType . '',
    ]); ?>
		  
		</script>
		
		<script>
		  document.addEventListener('DOMContentLoaded', function () {
		
			if( typeof wp.media.view.Attachment != 'undefined' ){
			  wp.media.view.MediaFrame.prototype.template = wp.media.template( 'media-frame_custom' );
			  
			  wp.media.view.Attachment.Library = wp.media.view.Attachment.Library.extend({
				attributes:  function () { 
					return {
						draggable: "true", 
						'data-id':  this.model.get( 'imageID' ), 
						'data-folder-id':  this.model.get( 'current_folder' )
						}
					},
				});
		
		
			} 
		  });
		  
		</script>
		<?php
  }

  /**
   * Builds folder app for post types
   * @since 3.0.9
   */
  public function build_post_folders()
  {
    //Get post type
    $screen = get_current_screen();
    $postType = $screen->post_type;

    $icons = uip_plugin_url . 'assets/css/uip-icons.css';
    $styleSRC = uip_plugin_url . 'assets/css/uip-app.css';
    if (is_rtl()) {
      $styleSRC = uip_plugin_url . 'assets/css/uip-app-rtl.css';
    }

    $appContainer = '
	  <div class="uip-folder-wrap uip-position-absolute">
	  	<div class="uip-w-100p-mobile uip-body-font uip-position-relative" id="uip-folder-app" style="font-size:14px;margin-top:12px">
	  	</div>
	  </div>';

    echo $appContainer;

    $styles = "
	<style>
	  @import '{$styleSRC}';
	  @import '{$icons}';
	  .column-uip_draggable{width:28px;}
	  .wrap{padding-left:348px !important;}
	</style>";

    echo $styles;

    wp_print_script_tag([
      'id' => 'uip-folders-app',
      'src' => uip_pro_plugin_url . 'admin/extensions/folders/js/uip-folders.min.js?ver=' . uip_pro_plugin_version,
      'type' => 'module',
      'defer' => true,
      'postType' => $postType,
      'limitToAuthor' => '' . $this->limitToAuthor . '',
      'limitToType' => '' . $this->limitToType . '',
    ]);
  }

  /**
   * Prints required vars
   * @since 3.0.9
   */
  public function print_folder_vars()
  {
    if (defined('uip_vars_outputted')) {
      if (uip_vars_outputted) {
        return;
      }
    }

    $data = json_encode([
      'ajax_url' => admin_url('admin-ajax.php'),
      'security' => wp_create_nonce('uip-security-nonce'),
      'uipAppData' => [
        'options' => [],
        'userPrefs' => [],
      ],
    ]);

    $variableFormatter = "
	  if(typeof uip_ajax === 'undefined'){
	  	var uip_ajax = JSON.parse('{$data}', (k, v) => (v === 'uiptrue' ? true : v === 'uipfalse' ? false : v === 'uipblank' ? '' : v));
	  }";

    wp_print_inline_script_tag($variableFormatter, ['id' => 'uip-menu-creator-format-vars']);
  }
}
