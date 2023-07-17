<?php
if (!defined('ABSPATH')) {
  exit();
}

class uipress_users extends uip_app
{
  public function __construct()
  {
  }

  /**
   * Loads menu actions
   * @since 2.3.5
   */

  public function run()
  {
    ///ADD ACTIOS FOR USERS PAGE
    $this->add_user_functions();
    add_action('plugins_loaded', [$this, 'start_history_logger'], 3);
    ///AVATAR FILTER
    add_filter('get_avatar', [$this, 'uip_allow_custom_avatars'], 1, 5);
    add_filter('get_avatar_url', [$this, 'uip_allow_custom_avatars_url'], 10, 3);
    //Login redirect
    add_filter('wp_login', [$this, 'redirect_on_login'], 10, 2);

    global $pagenow;
    if ($pagenow && $pagenow == 'profile.php') {
      add_action('admin_enqueue_scripts', function () {
        wp_enqueue_media();
      });
    }
    //Add profile image to user profile
    add_action('show_user_profile', [$this, 'add_custom_profile_select']);
    add_action('edit_user_profile', [$this, 'add_custom_profile_select']);
    //Save custom profile to user page
    add_action('personal_options_update', [$this, 'save_user_profile_image']);
    add_action('edit_user_profile_update', [$this, 'save_user_profile_image']);
  }

  /**
   * Adds extra profile image to user edit page
   * @since 3.0.9
   */
  public function add_custom_profile_select($user)
  {
    $image = get_the_author_meta('uip_profile_image', $user->ID); ?>
    <h2><?php _e('Profile Image', 'uipress-pro'); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="custom_image"><?php _e('Image', 'uipress-pro'); ?></label></th>
            <td>
                <input type="hidden" name="uip_profile_image" id="uip-profile-image-upload-holder" value="<?php echo $image; ?>" />
                <input type="button" class="button uip-profile-image-upload" value="<?php _e('Choose Image', 'uipress-pro'); ?>" />
                <div class="uip-custom-image-preview" style="border-radius: 4px; max-width:100px;border:1px solid rgba(1,1,1,0.1);margin-top:10px">
                    <?php if ($image): ?>
                        <img src="<?php echo esc_url($image); ?>" style="max-width: 100%;" />
                    <?php endif; ?>
                </div>
                <?php if ($image): ?>
                <input type="button" class="button uip-remove-profile" style="margin-top:10px" value="<?php _e('Remove', 'uipress-pro'); ?>">
                  <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
    $variableFormatter = "
    
      let remover = document.querySelector('.uip-remove-profile');
      if(remover){
        remover.addEventListener('click', function(event) {
            event.preventDefault;
            
            let customImageInput = document.getElementById('uip-profile-image-upload-holder');
            customImageInput.value = '';
            document.querySelector('.uip-custom-image-preview').innerHTML = '';
          
        });
      }
      document.querySelector('.uip-profile-image-upload').addEventListener('click', function(event) {
          event.preventDefault;
          
          let frame;
          
          // If the media frame already exists, reopen it.
          if (frame) {
              frame.open();
              return;
          }
      
          // Create a new media frame
          frame = wp.media({
              title: 'Select or Upload Image',
              button: {
                  text: 'Use this image'
              },
              multiple: false
          });
      
          // When an image is selected in the media frame...
          frame.on('select', function() {
            
              let customImageInput = document.getElementById('uip-profile-image-upload-holder');
              
              // Get media attachment details from the frame state
              var attachment = frame.state().get('selection').first().toJSON();
      
              // Send the attachment URL to our custom image input field.
              customImageInput.value = attachment.url;
      
              // Show the image preview
              document.querySelector('.uip-custom-image-preview').innerHTML = '<img src=\"' + attachment.url + '\" style=\"max-width: 100%;\" />';
          });
      
          // Finally, open the modal on click
          frame.open();
      });
      
      ";
    wp_print_inline_script_tag($variableFormatter, ['id' => 'uip-profile-uploader']);
  }

  /**
   * Saves user profile image on save
   * @since 3.0.9
   */
  function save_user_profile_image($user_id)
  {
    if (!current_user_can('edit_user', $user_id)) {
      return false;
    }

    if (isset($_POST['uip_profile_image'])) {
      update_user_meta($user_id, 'uip_profile_image', sanitize_text_field($_POST['uip_profile_image']));
    }
  }

  /**
   * Adds actions for USERS page
   * @since 2.3.5
   */

  public function start_history_logger()
  {
    require_once uip_pro_plugin_path . 'admin/extensions/user-management/uip-history.php';
    $history = new uip_history();
    $history->start();
  }
  /**
   * Adds actions for USERS page
   * @since 2.3.5
   */

  public function add_user_functions()
  {
    $utils = new uip_util();

    if (!is_admin()) {
      return;
    }

    $utils = new uip_util();

    require_once uip_pro_plugin_path . 'admin/extensions/user-management/uip-users-ajax.php';
    require_once uip_pro_plugin_path . 'admin/extensions/user-management/uip-user-groups.php';

    $ajaxFunctions = new uipress_users_ajax();
    $ajaxFunctions->ajax_actions();

    $userGroups = new uipress_user_groups();
    $userGroups->start();

    add_action('admin_menu', [$this, 'add_menu_item']);

    if (isset($_GET['page'])) {
      if ($_GET['page'] == 'uip-user-management') {
        add_action('admin_enqueue_scripts', [$this, 'add_scripts_for_user_management'], 0);
        add_action('admin_footer', [$this, 'add_footer_app'], 0);
      }
    }
  }

  /**
   * Adds USERS menu item
   * @since 2.3.5
   */

  public function add_menu_item()
  {
    add_submenu_page(
      'users.php', // Parent element
      __('User Management', 'uipress-pro'), // Text in browser title bar
      __('User Management', 'uipress-pro'), // Text to be displayed in the menu.
      'list_users', // Capability
      'uip-user-management', // Page slug, will be displayed in URL
      [$this, 'build_user_page'] // Callback function which displays the page
    );
    return;
  }

  /**
   * Enqueue scripts for user management page
   * @since 2.3.5
   */

  public function add_scripts_for_user_management()
  {
    $this->add_required_styles();
    //wp_enqueue_script('uip-vue', uip_plugin_url . 'assets/js/libs/vuejs.min.js', ['wp-i18n'], uip_plugin_version);
    wp_enqueue_script('uip-vue', uip_plugin_url . 'assets/js/libs/vue-dev.js', ['wp-i18n'], uip_plugin_version);
    wp_enqueue_script('uip-vue-router', uip_plugin_url . 'assets/js/libs/vue-router.min.js', ['uip-vue'], uip_plugin_version);

    wp_register_style('uip-quill-style', uip_pro_plugin_url . 'admin/extensions/user-management/js/libs/quill.snow.css', [], uip_pro_plugin_version);
    wp_enqueue_style('uip-quill-style');

    wp_enqueue_script('uip-quill', uip_pro_plugin_url . 'admin/extensions/user-management/js/libs/quill.min.js', [], uip_pro_plugin_version);
  }

  /**
   * Adds scripts to footer
   * @since 3.1.0
   */

  public function add_footer_app()
  {
    $utils = new uip_util();

    $variableFormatter = "
    var ajaxHolder = document.getElementById('uip-user-management-app-data');
    var ajaxData = ajaxHolder.getAttribute('uip_user_app_ajax');
    var uip_user_app_ajax = JSON.parse(ajaxData, (k, v) => (v === 'uiptrue' ? true : v === 'uipfalse' ? false : v === 'uipblank' ? '' : v));
      
    var ajaxDataNew = ajaxHolder.getAttribute('uip_ajax');
    var uip_ajax = JSON.parse(ajaxDataNew, (k, v) => (v === 'uiptrue' ? true : v === 'uipfalse' ? false : v === 'uipblank' ? '' : v));";

    wp_print_script_tag([
      'id' => 'uip-user-management-app-data',
      'uip_user_app_ajax' => json_encode([
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('uip-user-app-security-nonce'),
        'appData' => $this->buildAppData(),
      ]),
      'uip_ajax' => json_encode([
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('uip-security-nonce'),
        'uipAppData' => [
          'options' => [],
          'userPrefs' => [],
        ],
      ]),
    ]);

    wp_print_inline_script_tag($variableFormatter, ['id' => 'uip-menu-creator-format-vars']);

    wp_print_script_tag([
      'id' => 'uip-user-management-app-js',
      'src' => uip_pro_plugin_url . 'admin/extensions/user-management/js/uip-user-app.min.js?ver=' . uip_pro_plugin_version,
      'type' => 'module',
    ]);
  }

  /**
   * Builds Data for app
   * @since 2.3.5
   */

  public function buildAppData()
  {
    $previewImage = uip_pro_plugin_url . 'assets/img/user_management.png';
    $utils = new uip_util();

    $data = [];
    $data['app'] = [
      'currentPage' => 'users',
      'translations' => $this->build_translations(),
      'capabilities' => $utils->get_all_role_capabilities(),
      'dataConnect' => true,
      'previewImage' => $previewImage,
      'pages' => [
        [
          'name' => 'users',
          'label' => __('Users', 'uipress-pro'),
        ],
        [
          'name' => 'roles',
          'label' => __('Roles', 'uipress-pro'),
        ],
        [
          'name' => 'activity',
          'label' => __('Activity', 'uipress-pro'),
        ],
      ],
    ];

    return json_encode($data);
  }

  /**
   * Returns translations
   * @since 2.3.5
   */

  public function build_translations()
  {
    return [
      'results' => __('results', 'uipress-pro'),
      'previous' => __('Previous', 'uipress-pro'),
      'next' => __('Next', 'uipress-pro'),
      'searchUsers' => __('Search users', 'uipress-pro'),
      'filterByRole' => __('Filter by role', 'uipress-pro'),
      'filterByStatus' => __('Filter by status', 'uipress-pro'),
      'searchRoles' => __('Search roles', 'uipress-pro'),
      'searchStatuses' => __('Search statuses', 'uipress-pro'),
      'tableOptions' => __('Table options', 'uipress-pro'),
      'order' => __('Order', 'uipress-pro'),
      'ascending' => __('Ascending', 'uipress-pro'),
      'descending' => __('Descending', 'uipress-pro'),
      'sortBy' => __('Sort By', 'uipress-pro'),
      'perPage' => __('Per page', 'uipress-pro'),
      'fields' => __('Fields', 'uipress-pro'),
      'dateCreated' => __('Date created', 'uipress-pro'),
      'on' => __('On', 'uipress-pro'),
      'after' => __('After', 'uipress-pro'),
      'before' => __('Before', 'uipress-pro'),
      'dateFilters' => __('Date filters', 'uipress-pro'),
      'dateFilters' => __('Date filters', 'uipress-pro'),
      'details' => __('Details', 'uipress-pro'),
      'accountCreated' => __('Account created', 'uipress-pro'),
      'name' => __('Name', 'uipress-pro'),
      'email' => __('Email', 'uipress-pro'),
      'recentPageViews' => __('Recent page views', 'uipress-pro'),
      'recentActivity' => __('Recent activity', 'uipress-pro'),
      'next' => __('Next', 'uipress-pro'),
      'previous' => __('Previous', 'uipress-pro'),
      'lastLogin' => __('Last login', 'uipress-pro'),
      'lastLoginCountry' => __('Login location', 'uipress-pro'),
      'noActivity' => __('No recent activity', 'uipress-pro'),
      'totalPosts' => __('Total posts', 'uipress-pro'),
      'totalComments' => __('Total comments', 'uipress-pro'),
      'userOptions' => __('User Options', 'uipress-pro'),
      'firstName' => __('First name', 'uipress-pro'),
      'lastName' => __('Last name', 'uipress-pro'),
      'email' => __('Email', 'uipress-pro'),
      'assignRoles' => __('Assign roles', 'uipress-pro'),
      'roles' => __('Roles', 'uipress-pro'),
      'userNotes' => __('User notes', 'uipress-pro'),
      'cancel' => __('Cancel', 'uipress-pro'),
      'updateUser' => __('Update user', 'uipress-pro'),
      'sendPasswordReset' => __('Send password reset', 'uipress-pro'),
      'sendMessage' => __('Send message', 'uipress-pro'),
      'deleteUser' => __('Delete user', 'uipress-pro'),
      'confirmUserDelete' => __('Are you sure you want to delete this user?', 'uipress-pro'),
      'confirmUserDeleteMultiple' => __('Are you sure you want to the selected users?', 'uipress-pro'),
      'confirmUserDeleteMultipleActions' => __('Are you sure you want to the selected actions?', 'uipress-pro'),
      'confirmUserDeleteAllHistory' => __('Are you sure you want to delete all history?', 'uipress-pro'),
      'confirmUserPassReset' => __('Are you sure you want to send password reset links to the selected users?', 'uipress-pro'),
      'recipients' => __('Recipients', 'uipress-pro'),
      'subject' => __('Subject', 'uipress-pro'),
      'message' => __('Message', 'uipress-pro'),
      'sendMessage' => __('Send message', 'uipress-pro'),
      'replyTo' => __('Reply to email', 'uipress-pro'),
      'newUser' => __('New user', 'uipress-pro'),
      'saveUser' => __('Save user', 'uipress-pro'),
      'password' => __('Password', 'uipress-pro'),
      'username' => __('Username', 'uipress-pro'),
      'roleName' => __('Role name', 'uipress-pro'),
      'editRole' => __('Edit role', 'uipress-pro'),
      'saveRole' => __('Save role', 'uipress-pro'),
      'capabilities' => __('Capabilities', 'uipress-pro'),
      'adminWarning' => __(
        'You are currently editing the administrator role. This is usually the most important role on the site so please make sure not to remove nessecary capabilities.',
        'uipress'
      ),
      'deleteRole' => __('Delete role', 'uipress-pro'),
      'confirmRoleDelete' => __('Are you sure you want to delete this role?', 'uipress-pro'),
      'confirmRoleDeleteMultiple' => __('Are you sure you want to delete these roles?', 'uipress-pro'),
      'roleOptions' => __('Role options', 'uipress-pro'),
      'clone' => __('Clone', 'uipress-pro'),
      'newRole' => __('New role', 'uipress-pro'),
      'roleLabel' => __('Role label', 'uipress-pro'),
      'roleLabelDescription' => __('Single word, no spaces. Underscores and dashes allowed', 'uipress-pro'),
      'copy' => __('copy', 'uipress-pro'),
      'searchRoles' => __('search roles', 'uipress-pro'),
      'searchHistory' => __('Search history', 'uipress-pro'),
      'allActions' => __('All actions', 'uipress-pro'),
      'addCustomCapability' => __('Add custom capability (Single word, no spaces)', 'uipress-pro'),
      'addCapability' => __('Add capability', 'uipress-pro'),
      'rolesSelected' => __('roles selected', 'uipress-pro'),
      'deleteSelected' => __('Delete selected', 'uipress-pro'),
      'deselect' => __('Deselect', 'uipress-pro'),
      'deleteUsers' => __('Delete users', 'uipress-pro'),
      'deleteActions' => __('Delete actions', 'uipress-pro'),
      'clearSelection' => __('Clear selection', 'uipress-pro'),
      'recipients' => __('Recipients', 'uipress-pro'),
      'users' => __('users', 'uipress-pro'),
      'Users' => __('Users', 'uipress-pro'),
      'replaceExistingRoles' => __('Replace existing roles', 'uipress-pro'),
      'updateRoles' => __('Update roles', 'uipress-pro'),
      'usersSelected' => __('users selected', 'uipress-pro'),
      'actionsSelected' => __('actions selected', 'uipress-pro'),
      'chooseImage' => __('Choose image', 'uipress-pro'),
      'profileImage' => __('Profile image', 'uipress-pro'),
      'groups' => __('Groups', 'uipress-pro'),
      'allUsers' => __('All users', 'uipress-pro'),
      'noGroup' => __('No group', 'uipress-pro'),
      'noGroupCreated' => __('No groups created yet.', 'uipress-pro'),
      'groups' => __('Groups', 'uipress-pro'),
      'newGroup' => __('New group', 'uipress-pro'),
      'name' => __('Name', 'uipress-pro'),
      'color' => __('Color', 'uipress-pro'),
      'createGroup' => __('Create group', 'uipress-pro'),
      'groupName' => __('Group name', 'uipress-pro'),
      'editGroup' => __('Edit group', 'uipress-pro'),
      'updateGroup' => __('Update group', 'uipress-pro'),
      'removeFromGroup' => __('Remove from group', 'uipress-pro'),
      'userGroups' => __('User groups', 'uipress-pro'),
      'assignGroups' => __('Assign groups', 'uipress-pro'),
      'searchGroups' => __('Search groups', 'uipress-pro'),
      'proFeature' => __('Pro feature', 'uipress-pro'),
      'proFeatureUpgrade' => __('Upgrade to UiPress Pro to unlock the user management and activity logs', 'uipress-pro'),
      'viewPlans' => __('View Uipress Pro plans', 'uipress-pro'),
      'groupIcon' => __('Group icon', 'uipress-pro'),
      'logoutEverywhere' => __('Logout everywhere else', 'uipress-pro'),
      'openProfile' => __('Open profile', 'uipress-pro'),
      'confirmCapDelete' => __('Are you sure you want to delete this capability? This will remove the capability sitewide. Please note not all capabilities can be deleted.', 'uipress-pro'),
      'historylogLarge' => __('Activity Log is very big', 'uipress-pro'),
      'logSizeWarning' => __(
        'Your activity log now has over 20,000 entries, consider reducing how long entries are stored for, the total amount of logs that can be kept or delete all of your entries below.',
        'uipress'
      ),
      'dismiss' => __('Dismiss', 'uipress-pro'),
      'deleteAllActivity' => __('Delete all', 'uipress-pro'),
      'deleteAllActions' => __('Delete all actions', 'uipress-pro'),
      'confirmPurgeActions' => __("Are you sure you want to delete all history items? This can't be undone", 'uipress-pro'),
      'viewUser' => __('View', 'uipress-pro'),
      'editUser' => __('Edit', 'uipress-pro'),
      'chooseImage' => __('Choose image', 'uipress-pro'),
      'changeImage' => __('Change image', 'uipress-pro'),
      'imageURL' => __('Image url', 'uipress-pro'),
      'editUserPanel' => __('Edit user', 'uipress-pro'),
      'viewUserProfile' => __('View user profile', 'uipress-pro'),
      'others' => __('Others', 'uipress-pro'),
      'newMessage' => __('New message', 'uipress-pro'),
      'deleteRole' => __('Delete role', 'uipress-pro'),
      'label' => __('Label', 'uipress-pro'),
      'toggleAll' => __('Toggle all', 'uipress-pro'),
      'edit' => __('Edit', 'uipress-pro'),
      'delete' => __('Delete', 'uipress-pro'),
      'loginRedirect' => __('Login redirect', 'uipress-pro'),
      'searchCapabilities' => __('Search capabilities', 'uipress-pro'),
      'noUsers' => __('No users with this role', 'uipress-pro'),
    ];
  }

  /**
   * Adds a module tag to uip-user-app
   * @since 2.3.5
   */

  public function add_type_attribute($tag, $handle, $src)
  {
    // if not your script, do nothing and return original $tag
    if ('uip-user-management-app' !== $handle) {
      return $tag;
    }
    // change the script tag by adding type="module" and return it.
    $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
    return $tag;
  }

  /**
   * Adds a module tag to uip-user-app
   * @since 3.0.9
   */

  public function redirect_on_login($user_login, $user)
  {
    $utils = new uip_util();
    $redirects = $utils->get_uip_option('role_redirects');

    //Get current role
    $user_roles = $user->roles;
    $user_role = array_shift($user_roles);

    error_log(json_encode($redirects));

    if (is_array($redirects)) {
      if (isset($redirects[$user_role])) {
        if ($redirects[$user_role] != '' && $redirects[$user_role] != 'uipblank' && $redirects[$user_role] != false) {
          //Check if absolute or relative
          $userRedirect = strtolower($redirects[$user_role]);
          if (strpos($userRedirect, 'https') !== false || strpos($userRedirect, 'http') !== false) {
            $url = $userRedirect;
          } else {
            $url = admin_url($userRedirect);
          }
          wp_safe_redirect($url);
          exit();
        }
      }
    }

    //return $redirect_to;
  }

  /**
   * Builds users page
   * @since 2.3.5
   */

  public function build_user_page()
  {
    ///LOAD UP WP IMAGE MODALS
    wp_enqueue_media(); ?>
	<style>#wpcontent{padding:0;background:var(--uip-color-base-0)}#wpfooter{display:none}</style>
	<div id="uip-user-management" style="min-height:calc(100vh - 32px)"></div>
	<?php
  }

  /**
   * Allow custom images as avatar
   * @since 2.3.5
   */

  function uip_allow_custom_avatars_url($url, $id_or_email, $args)
  {
    $user = false;

    if (is_numeric($id_or_email)) {
      $id = (int) $id_or_email;
      $user = get_user_by('id', $id);
    } elseif (is_object($id_or_email)) {
      if (!empty($id_or_email->user_id)) {
        $id = (int) $id_or_email->user_id;
        $user = get_user_by('id', $id);
      }
    } else {
      $user = get_user_by('email', $id_or_email);
    }

    if ($user && is_object($user)) {
      $thepath = get_user_meta($user->data->ID, 'uip_profile_image', true);

      if ($thepath) {
        $url = $thepath;
      }
    }

    return $url;
  }

  /**
   * Allow custom images as avatar
   * @since 2.3.5
   */

  public function uip_allow_custom_avatars($avatar, $id_or_email, $size, $default, $alt)
  {
    $user = false;

    if (is_numeric($id_or_email)) {
      $id = (int) $id_or_email;
      $user = get_user_by('id', $id);
    } elseif (is_object($id_or_email)) {
      if (!empty($id_or_email->user_id)) {
        $id = (int) $id_or_email->user_id;
        $user = get_user_by('id', $id);
      }
    } else {
      $user = get_user_by('email', $id_or_email);
    }

    if ($user && is_object($user)) {
      $thepath = get_user_meta($user->data->ID, 'uip_profile_image', true);

      if ($thepath) {
        $avatar = $thepath;
        $avatar = $avatar;
        $avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
      }
    }

    return $avatar;
  }

  /**
   * Gets capabilities from exisitng roles
   * Original code modified from members plugin by Justin Tadlock
   * @since 2.3.5
   */

  public function uip_get_role_capabilities()
  {
    // Set up an empty capabilities array.
    $categories = [
      'read' => [
        'shortname' => 'read',
        'name' => __('Read', 'uipress-pro'),
        'caps' => [],
        'icon' => 'bookmark',
      ],
      'edit' => [
        'shortname' => 'edit',
        'name' => __('Edit', 'uipress-pro'),
        'caps' => [],
        'icon' => 'edit_note',
      ],
      'publish' => [
        'shortname' => 'publish',
        'name' => __('Publish', 'uipress-pro'),
        'caps' => [],
        'icon' => 'publish',
      ],
      'create' => [
        'shortname' => 'create',
        'name' => __('Create', 'uipress-pro'),
        'caps' => [],
        'icon' => 'add_circle',
      ],
      'delete' => [
        'shortname' => 'delete',
        'name' => __('Delete', 'uipress-pro'),
        'caps' => [],
        'icon' => 'delete',
      ],
      'view' => [
        'shortname' => 'view',
        'name' => __('View', 'uipress-pro'),
        'caps' => [],
        'icon' => 'visibility',
      ],
      'manage' => [
        'shortname' => 'manage',
        'name' => __('Manage', 'uipress-pro'),
        'caps' => [],
        'icon' => 'tune',
      ],
      'export' => [
        'shortname' => 'export',
        'name' => __('Export', 'uipress-pro'),
        'caps' => [],
        'icon' => 'file_download',
      ],
      'import' => [
        'shortname' => 'import',
        'name' => __('Import', 'uipress-pro'),
        'caps' => [],
        'icon' => 'file_upload',
      ],
      'custom' => [
        'shortname' => 'custom',
        'name' => __('Custom', 'uipress-pro'),
        'caps' => [],
        'icon' => 'settings',
      ],
    ];
    $capabilities = [];

    global $wp_roles;

    $usercaps = [];
    // Loop through each role object because we need to get the caps.
    foreach ($wp_roles->role_objects as $key => $role) {
      // Make sure that the role has caps.
      if (is_array($role->capabilities)) {
        // Add each of the role's caps (both granted and denied) to the array.
        foreach ($role->capabilities as $cap => $grant) {
          $usercaps[] = $cap;
        }
      }
    }

    $postypeCaps = $this->uip_post_type_caps();

    $allcaps = array_merge($usercaps, $postypeCaps);
    $allcaps = array_unique($allcaps);

    foreach ($allcaps as $cap) {
      if (strpos($cap, 'view') !== false) {
        $categories['view']['caps'][] = $cap;
      } elseif (strpos($cap, 'read') !== false) {
        $categories['read']['caps'][] = $cap;
      } elseif (strpos($cap, 'edit') !== false) {
        $categories['edit']['caps'][] = $cap;
      } elseif (strpos($cap, 'delete') !== false || strpos($cap, 'remove') !== false) {
        $categories['delete']['caps'][] = $cap;
      } elseif (
        strpos($cap, 'manage') !== false ||
        strpos($cap, 'install') !== false ||
        strpos($cap, 'update') !== false ||
        strpos($cap, 'switch') !== false ||
        strpos($cap, 'moderate') !== false ||
        strpos($cap, 'activate') !== false
      ) {
        $categories['manage']['caps'][] = $cap;
      } elseif (strpos($cap, 'export') !== false) {
        $categories['export']['caps'][] = $cap;
      } elseif (strpos($cap, 'import') !== false) {
        $categories['import']['caps'][] = $cap;
      } elseif (strpos($cap, 'publish') !== false) {
        $categories['publish']['caps'][] = $cap;
      } elseif (strpos($cap, 'create') !== false || strpos($cap, 'upload') !== false) {
        $categories['create']['caps'][] = $cap;
      } else {
        $categories['custom']['caps'][] = $cap;
      }
    }

    // Return the capabilities array, making sure there are no duplicates.
    return $categories;
  }

  /**
   * Gets capabilities for post types
   * Original code modified from members plugin by Justin Tadlock
   * @since 2.3.5
   */

  public function uip_post_type_caps()
  {
    $postypecaps = [];
    foreach (get_post_types([], 'objects') as $type) {
      // Skip revisions and nave menu items.
      if (in_array($type->name, ['revision', 'nav_menu_item', 'custom_css', 'customize_changeset'])) {
        continue;
      }

      $post_type = $type->name;
      // Get the post type caps.
      $caps = (array) get_post_type_object($post_type)->cap;

      // remove meta caps.
      unset($caps['edit_post']);
      unset($caps['read_post']);
      unset($caps['delete_post']);

      // Get the cap names only.
      $caps = array_values($caps);

      // If this is not a core post/page post type.
      if (!in_array($post_type, ['post', 'page'])) {
        // Get the post and page caps.
        $post_caps = array_values((array) get_post_type_object('post')->cap);
        $page_caps = array_values((array) get_post_type_object('page')->cap);

        // Remove post/page caps from the current post type caps.
        $caps = array_diff($caps, $post_caps, $page_caps);
      }

      // If attachment post type, add the `unfiltered_upload` cap.
      if ('attachment' === $post_type) {
        $caps[] = 'unfiltered_upload';
      }

      if (is_array($caps)) {
        foreach ($caps as $cap) {
          $postypecaps[] = $cap;
        }
      }
    }

    // Make sure there are no duplicates and return.
    return array_unique($postypecaps);
  }

  /**
   * Gets users recent page views
   * @since 2.3.5
   */
  public function get_user_activity($activityPage, $userID = null)
  {
    if (!$userID) {
      return [];
    }

    //Get and prep database
    $history = new uip_history();
    $database = $history->uip_history_get_database();
    $history->uip_history_prep_database($database);

    $author_ids_string = implode(',', array_map('intval', [$userID]));
    $author_filter_query = " AND `post_author` IN ({$author_ids_string})";

    //SET DIRECTION
    $perpage = '10';
    $offset = ($activityPage - 1) * $perpage;

    // Perform the paged query on the custom database.
    $all_history = $database->get_results(
      $database->prepare("SELECT * FROM `uip_history` WHERE `post_status` = 'publish' {$author_filter_query} ORDER BY `post_date` DESC LIMIT %d OFFSET %d", $perpage, $offset)
    );
    //Post count
    $total_history = $database->get_var("SELECT COUNT(*) FROM `uip_history` WHERE `post_status` = 'publish' {$author_filter_query}");

    //Get total pages
    $totalpages = 0;
    if ($total_history > 0) {
      $totalpages = ceil($total_history / $perpage);
    }

    $actions = [];
    if (is_array($all_history)) {
      foreach ($all_history as $action) {
        $temp = $this->format_user_activity($action);
        $actions[] = $temp;
      }
    }

    $data['list'] = $actions;
    $data['totalFound'] = $total_history;
    $data['totalPages'] = $totalpages;

    return $data;
  }

  public function format_user_activity($action)
  {
    $action = (array) $action;
    $returnData = [];
    $type = $action['uip_history_type'];
    $context = (array) json_decode($action['uip_history_context']);
    $ip = $action['uip_history_ip'];

    //POST TIME
    $view_time = date('u', strtotime($action['post_date']));
    $human_time = human_time_diff(date('U', strtotime($action['post_date'])));

    //GET AUTHOR DETAILS
    $authorID = $action['post_author'];
    $user_meta = get_userdata($authorID);

    if ($user_meta) {
      $username = $user_meta->user_login;
      $roles = $user_meta->roles;
      $image = get_avatar_url($authorID, ['default' => 'retro']);
    } else {
      $username = __('User no longer exists', 'uipress-pro');
      $roles = [];
      $image = get_avatar_url($authorID, ['default' => 'retro']);
    }

    $returnData['human_time'] = sprintf(__('%s ago', 'uipress-pro'), $human_time);
    $returnData['ip_address'] = $ip;
    $returnData['id'] = $action['ID'];
    $returnData['user'] = $username;
    $returnData['user_id'] = $authorID;
    $returnData['image'] = $image;
    $returnData['roles'] = $roles;
    $returnData['time'] = date(get_option('time_format'), strtotime($action['post_date']));
    $returnData['date'] = date(get_option('date_format'), strtotime($action['post_date']));

    if ($type == 'page_view' && is_array($context)) {
      $returnData['title'] = __('Page view', 'uipress-pro');
      $returnData['type'] = 'primary';
      $returnData['meta'] = __('Viewed page', 'uipress-pro') . " <a class='uip-link-muted uip-no-underline uip-text-bold' href='{$context['url']}'>{$context['title']}</a>";
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'post_created' && is_array($context)) {
      $post_id = $context['post_id'];
      $url = get_the_permalink($post_id);
      $title = get_the_title($post_id);
      $returnData['title'] = __('Post created', 'uipress-pro');
      $returnData['type'] = 'primary';
      $returnData['meta'] = __('Created post', 'uipress-pro') . " <a class='uip-link-muted uip-no-underline uip-text-bold' href='{$url}'>{$title}</a>";
      $returnData['links'] = [
        [
          'name' => __('View page', 'uipress-pro'),
          'url' => $url,
        ],
        [
          'name' => __('Edit page', 'uipress-pro'),
          'url' => get_edit_post_link($post_id),
        ],
      ];
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'post_updated' && is_array($context)) {
      $post_id = $context['post_id'];
      $url = get_the_permalink($post_id);
      $title = get_the_title($post_id);
      $returnData['title'] = __('Post modified', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] = __('Modified post', 'uipress-pro') . " <a class='uip-link-muted uip-no-underline uip-text-bold' href='{$url}'>{$title}</a>";
      $returnData['links'] = [
        [
          'name' => __('View page', 'uipress-pro'),
          'url' => $url,
        ],
        [
          'name' => __('Edit page', 'uipress-pro'),
          'url' => get_edit_post_link($post_id),
        ],
      ];
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'post_trashed' && is_array($context)) {
      $post_id = $context['post_id'];
      $url = get_the_permalink($post_id);
      $title = get_the_title($post_id);
      $returnData['title'] = __('Post moved to trash', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] = __('Moved post to trash', 'uipress-pro') . " <a class='uip-link-muted uip-no-underline uip-text-bold' href='{$url}'>{$title}</a>";
      $returnData['links'] = [
        [
          'name' => __('Edit page', 'uipress-pro'),
          'url' => get_edit_post_link($post_id),
        ],
      ];
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'post_deleted' && is_array($context)) {
      $returnData['title'] = __('Post deleted', 'uipress-pro');
      $returnData['type'] = 'danger';
      $returnData['meta'] = __('Deleted post', 'uipress-pro') . " <strong>{$context['title']}</strong> (ID:{$context['post_id']})";
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'post_status_change' && is_array($context)) {
      $post_id = $context['post_id'];
      $url = get_the_permalink($post_id);
      $title = get_the_title($post_id);
      $returnData['title'] = __('Post status change', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] =
        sprintf(__('Post status changed from %s to %s', 'uipress-pro'), $context['old_status'], $context['new_status']) .
        " <a class='uip-link-muted uip-no-underline uip-text-bold' href='{$url}'>{$title}</a> (ID:{$context['post_id']})";
      $returnData['links'] = [
        [
          'name' => __('View page', 'uipress-pro'),
          'url' => $url,
        ],
        [
          'name' => __('Edit page', 'uipress-pro'),
          'url' => get_edit_post_link($post_id),
        ],
      ];
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'new_comment' && is_array($context)) {
      $post_id = $context['post_id'];
      $url = get_the_permalink($post_id);
      $title = get_the_title($post_id);

      $returnData['title'] = __('Posted a comment', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] = __('Posted a comment on post', 'uipress-pro') . " <a class='uip-link-muted uip-no-underline uip-text-bold' href='{$url}'>{$title}</a>";
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];

      $com = get_comment($context['comment_id']);

      if ($com) {
        $comlink = get_comment_link($com);
        $editlink = get_edit_comment_link($context['comment_id']);
        $returnData['links'] = [
          [
            'name' => __('View comment', 'uipress-pro'),
            'url' => $comlink,
          ],
          [
            'name' => __('Edit comment', 'uipress-pro'),
            'url' => $editlink,
          ],
        ];
      }
    }

    if ($type == 'trash_comment' && is_array($context)) {
      $post_id = $context['post_id'];
      $url = get_the_permalink($post_id);
      $title = get_the_title($post_id);

      $returnData['title'] = __('Trashed a comment', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] = __('Moved a comment to the trash', 'uipress-pro');
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];

      $com = get_comment($context['comment_id']);

      if ($com) {
        $comlink = get_comment_link($com);
        $editlink = get_edit_comment_link($context['comment_id']);

        $returnData['links'] = [
          [
            'name' => __('View comment', 'uipress-pro'),
            'url' => $comlink,
          ],
          [
            'name' => __('Edit comment', 'uipress-pro'),
            'url' => $editlink,
          ],
        ];
      }
    }

    if ($type == 'delete_comment' && is_array($context)) {
      $com = $context['comment_id'];

      $returnData['title'] = __('Deleted a comment', 'uipress-pro');
      $returnData['type'] = 'danger';
      $returnData['meta'] = __('Permanently deleted a comment', 'uipress-pro') . " (ID:{$com})";
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'plugin_activated' && is_array($context)) {
      $returnData['title'] = __('Plugin activated', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] = sprintf(__('A plugin called %s was activated', 'uipress-pro'), $context['plugin_name']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'plugin_deactivated' && is_array($context)) {
      $returnData['title'] = __('Plugin deactivated', 'uipress-pro');
      $returnData['type'] = 'danger';
      $returnData['meta'] = sprintf(__('A plugin called %s was deactivated', 'uipress-pro'), $context['plugin_name']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'plugin_deleted' && is_array($context)) {
      $returnData['title'] = __('Plugin deleted', 'uipress-pro');
      $returnData['type'] = 'danger';
      $returnData['meta'] = sprintf(__('A plugin called %s was deleted', 'uipress-pro'), $context['plugin_name']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'user_login' && is_array($context)) {
      $returnData['title'] = __('User logged in', 'uipress-pro');
      $returnData['type'] = 'primary';
      $returnData['meta'] = sprintf(__('Logged in with ip address %s. Country: %s', 'uipress-pro'), $context['ip'], $context['country']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'user_logout' && is_array($context)) {
      $returnData['title'] = __('User logged out', 'uipress-pro');
      $returnData['type'] = 'primary';
      $returnData['meta'] = sprintf(__('Logged out with ip address %s. Country: %s', 'uipress-pro'), $context['ip'], $context['country']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'option_added' && is_array($context)) {
      $newvalue = $context['new_value'];

      if (is_array($newvalue) || is_object($newvalue)) {
        $newvalue = json_encode($newvalue);
      }
      $returnData['title'] = __('Site option added', 'uipress-pro');
      $returnData['type'] = 'danger';
      $returnData['meta'] = sprintf(__('Site option (%s) was added with a value of (%s)', 'uipress-pro'), $context['option_name'], $newvalue);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'attachment_uploaded') {
      $name = '';
      if (isset($context['name'])) {
        $name = $context['name'];
      }
      $returnData['title'] = __('Uploaded attachment', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] = sprintf(__('Attachment called (%s) was uploaded to (%s). Attachment ID: %s', 'uipress-pro'), $name, $context['path'], $context['image_id']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];

      $attachment = get_edit_post_link($context['image_id'], '&');

      if ($attachment) {
        $returnData['links'] = [
          [
            'name' => __('View attachment', 'uipress-pro'),
            'url' => $attachment,
          ],
        ];
      }
    }

    if ($type == 'attachment_deleted' && is_array($context)) {
      $returnData['title'] = __('Deleted attachment', 'uipress-pro');
      $returnData['type'] = 'danger';
      $returnData['meta'] = sprintf(__('Attachment called (%s) was deleted. Attachment ID: %s', 'uipress-pro'), $context['name'], $context['image_id']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'user_created' && is_array($context)) {
      $returnData['title'] = __('User created', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] = sprintf(__('New user created with username (%s) and email (%s)', 'uipress-pro'), $context['username'], $context['email']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'user_deleted' && is_array($context)) {
      $returnData['title'] = __('User deleted', 'uipress-pro');
      $returnData['type'] = 'danger';
      $returnData['meta'] = sprintf(__('A user with username (%s) and email (%s) was deleted', 'uipress-pro'), $context['username'], $context['email']);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    if ($type == 'user_updated' && is_array($context)) {
      $oldvalue = $context['old_value'];
      $newvalue = $context['new_value'];

      if (is_array($oldvalue) || is_object($oldvalue)) {
        $oldvalue = json_encode($oldvalue, JSON_PRETTY_PRINT);
      }

      if (is_array($newvalue) || is_object($newvalue)) {
        $newvalue = json_encode($newvalue, JSON_PRETTY_PRINT);
      }

      if (strlen($oldvalue) > 20) {
        $fullvalue = $oldvalue;
        $short = substr($oldvalue, 0, 20) . ' ... ';
        $oldvalue = '<inline-drop>';
        $oldvalue .= "<trigger><strong>{$short}</strong></trigger>";
        $oldvalue .= "<drop-content class='uip-padding-xs uip-shadow uip-border-round uip-max-h-200 uip-max-w-300 uip-overflow-auto uip-background-default' style='left:50%;transform:translateX(-50%)'><pre>{$fullvalue}</pre><drop-content>";
        $oldvalue .= '</inline-drop>';
      }

      if (strlen($newvalue) > 20) {
        $fullvalue = $newvalue;
        $short = substr($newvalue, 0, 20) . ' ... ';
        $newvalue = '<inline-drop >';
        $newvalue .= "<trigger><strong>{$short}</strong></trigger>";
        $newvalue .= "<drop-content class='uip-padding-xs uip-shadow uip-border-round uip-max-h-200 uip-max-w-300 uip-overflow-auto uip-background-default' style='left:50%;transform:translateX(-50%)'><pre>{$fullvalue}</pre><drop-content>";
        $newvalue .= '</inline-drop>';
      }

      $returnData['title'] = __('User updated', 'uipress-pro');
      $returnData['type'] = 'warning';
      $returnData['meta'] = sprintf(__('A user with username (%s) and email (%s) was updated from (%s) to (%s)', 'uipress-pro'), $context['username'], $context['email'], $oldvalue, $newvalue);
      $returnData['action'] = $returnData['title'];
      $returnData['description'] = $returnData['meta'];
    }

    return $returnData;
  }

  /**
   * Gets users recent page views
   * @since 2.3.5
   */
  public function get_user_page_views($userID)
  {
    $recent_page_views = get_user_meta($userID, 'recent_page_views', true);
    $page_views = [];

    if (is_array($recent_page_views)) {
      foreach ($recent_page_views as $view) {
        $view_time = $view['time'];
        $human_time = human_time_diff($view_time);

        $view['human_time'] = sprintf(__('%s ago', 'uipress-pro'), $human_time);
        array_push($page_views, $view);
      }
    }

    $page_views = array_reverse($page_views);

    return $page_views;
  }

  public function returnDateFilter($date, $type, $args)
  {
    if ($type == 'on') {
      $year = date('Y', strtotime($date));
      $month = date('m', strtotime($date));
      $day = date('d', strtotime($date));

      $args['date_query'] = [
        'year' => $year,
        'month' => $month,
        'day' => $day,
      ];
    } else {
      if ($type == 'before') {
        $args['date_query'] = [
          [
            'before' => date('Y-m-d', strtotime($date)),
            'inclusive' => true,
          ],
        ];
      } elseif ($type == 'after') {
        $args['date_query'] = [
          [
            'after' => date('Y-m-d', strtotime($date)),
            'inclusive' => true,
          ],
        ];
      }
    }

    return $args;
  }

  /**
   * Builds colums for user table
   * @since 2.3.5
   */

  public function uip_format_user_data($all_users)
  {
    $allusers = [];
    foreach ($all_users as $user) {
      $user_meta = get_userdata($user->ID);
      $first_name = $user_meta->first_name;
      $last_name = $user_meta->last_name;
      $full_name = $first_name . ' ' . $last_name;
      $roles = $user->roles;

      //$hasimage = get_avatar($user->ID);
      $image = get_avatar_url($user->ID, ['default' => 'retro']);

      $expiry = get_user_meta($user->ID, 'uip-user-expiry', true);
      $last_login = get_user_meta($user->ID, 'uip_last_login_date', true);
      $group = get_user_meta($user->ID, 'uip_user_group', true);

      if ($last_login) {
        $last_login = date(get_option('date_format'), strtotime($last_login));
      }

      $dateformat = get_option('date_format');
      $formattedCreated = date($dateformat, strtotime($user->user_registered));

      $temp['username'] = $user->user_login;
      $temp['user_email'] = $user->user_email;
      $temp['name'] = $full_name;
      $temp['first_name'] = $user->first_name;
      $temp['last_name'] = $user->last_name;
      $temp['uip_last_login_date'] = $last_login;
      $temp['roles'] = $roles;
      $temp['image'] = $image;
      $temp['initial'] = strtoupper($user->user_login[0]);
      $temp['user_id'] = $user->ID;
      $temp['expiry'] = $expiry;
      $temp['user_registered'] = $formattedCreated;
      $temp['uip_user_group'] = $group;
      $allusers[] = $temp;
    }

    return $allusers;
  }

  /**
   * Builds colums for user table
   * @since 2.3.5
   */

  public function uip_build_user_table_columns()
  {
    return [
      [
        'name' => 'username',
        'label' => __('Username', 'uipress-pro'),
        'active' => true,
        'mobile' => true,
      ],
      [
        'name' => 'name',
        'label' => __('Name', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],
      [
        'name' => 'user_email',
        'label' => __('Email', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],

      [
        'name' => 'first_name',
        'label' => __('First Name', 'uipress-pro'),
        'active' => false,
        'mobile' => false,
      ],
      [
        'name' => 'last_name',
        'label' => __('Last Name', 'uipress-pro'),
        'active' => false,
        'mobile' => false,
      ],
      [
        'name' => 'uip_last_login_date',
        'label' => __('Last Login', 'uipress-pro'),
        'active' => false,
        'mobile' => false,
      ],
      [
        'name' => 'roles',
        'label' => __('Roles', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],
      [
        'name' => 'user_id',
        'label' => __('User ID', 'uipress-pro'),
        'active' => false,
        'mobile' => false,
      ],
      [
        'name' => 'user_registered',
        'label' => __('User created', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],
    ];
  }

  /**
   * Builds colums for user table
   * @since 2.3.5
   */

  public function uip_build_role_table_columns()
  {
    return [
      [
        'name' => 'label',
        'label' => __('Role name', 'uipress-pro'),
        'active' => true,
        'mobile' => true,
      ],
      [
        'name' => 'name',
        'label' => __('Role', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],
      [
        'name' => 'users',
        'label' => __('Users', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],

      [
        'name' => 'granted',
        'label' => __('Permissions granted', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],
    ];
  }

  /**
   * Builds colums for user table
   * @since 2.3.5
   */

  public function uip_build_activity_table_columns()
  {
    return [
      [
        'name' => 'id',
        'label' => __('ID', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],
      [
        'name' => 'user',
        'label' => __('User', 'uipress-pro'),
        'active' => true,
        'mobile' => true,
      ],
      [
        'name' => 'ip_address',
        'label' => __('IP Address', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],

      [
        'name' => 'action',
        'label' => __('Action', 'uipress-pro'),
        'active' => true,
        'mobile' => true,
      ],
      [
        'name' => 'description',
        'label' => __('Description', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],
      [
        'name' => 'date',
        'label' => __('Date', 'uipress-pro'),
        'active' => true,
        'mobile' => true,
      ],

      [
        'name' => 'time',
        'label' => __('Time', 'uipress-pro'),
        'active' => true,
        'mobile' => false,
      ],
    ];
  }

  /**
   * Returns list of history actions
   * @since 2.3.5
   */

  public function uip_return_history_actions()
  {
    return [
      [
        'name' => 'page_view',
        'label' => __('Page view', 'uipress-pro'),
      ],
      [
        'name' => 'post_created',
        'label' => __('Post created', 'uipress-pro'),
      ],
      [
        'name' => 'post_updated',
        'label' => __('Post updated', 'uipress-pro'),
      ],
      [
        'name' => 'post_trashed',
        'label' => __('Post trashed', 'uipress-pro'),
      ],
      [
        'name' => 'post_deleted',
        'label' => __('Post deleted', 'uipress-pro'),
      ],
      [
        'name' => 'post_status_change',
        'label' => __('Post status change', 'uipress-pro'),
      ],
      [
        'name' => 'trash_comment',
        'label' => __('Trashed comment', 'uipress-pro'),
      ],
      [
        'name' => 'delete_comment',
        'label' => __('Deelete comment', 'uipress-pro'),
      ],

      [
        'name' => 'plugin_activated',
        'label' => __('Plugin activated', 'uipress-pro'),
      ],
      [
        'name' => 'plugin_deactivated',
        'label' => __('Plugin deactivated', 'uipress-pro'),
      ],
      [
        'name' => 'plugin_deleted',
        'label' => __('Plugin deleted', 'uipress-pro'),
      ],
      [
        'name' => 'user_login',
        'label' => __('User login', 'uipress-pro'),
      ],
      [
        'name' => 'user_logout',
        'label' => __('User logout', 'uipress-pro'),
      ],
      [
        'name' => 'option_change',
        'label' => __('Option change', 'uipress-pro'),
      ],
      [
        'name' => 'option_added',
        'label' => __('Site option added', 'uipress-pro'),
      ],
      [
        'name' => 'attachment_uploaded',
        'label' => __('Attachmnet uploaded', 'uipress-pro'),
      ],
      [
        'name' => 'attachment_deleted',
        'label' => __('Attachmnet deleted', 'uipress-pro'),
      ],
      [
        'name' => 'user_created',
        'label' => __('User created', 'uipress-pro'),
      ],
      [
        'name' => 'user_deleted',
        'label' => __('User deleted', 'uipress-pro'),
      ],
      [
        'name' => 'user_updated',
        'label' => __('User updated', 'uipress-pro'),
      ],
    ];
  }
}
