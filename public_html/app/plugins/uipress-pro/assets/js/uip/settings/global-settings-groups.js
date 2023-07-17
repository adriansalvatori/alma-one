///IMPORT TRANSLATIONS
const { __, _x, _n, _nx } = wp.i18n;
///Groups
export function fetchGroups() {
  return {
    advanced: {
      label: __('Advanced', 'uipress-pro'),
      name: 'advanced',
      icon: 'code',
    },
    whiteLabel: {
      label: __('White label', 'uipress-pro'),
      name: 'whiteLabel',
      icon: 'branding_watermark',
    },

    login: {
      label: __('Login', 'uipress-pro'),
      name: 'login',
      icon: 'login',
    },
    contentFolders: {
      label: __('Content folders', 'uipress-pro'),
      name: 'contentFolders',
      icon: 'folder',
      condition: function (data) {
        if (!('extensions' in data)) {
          return false;
        }
        if (data.extensions.foldersEnabled) {
          return true;
        }
        return false;
      },
    },
    activityLog: {
      label: __('Activity log', 'uipress-pro'),
      name: 'activityLog',
      icon: 'checklist',
      condition: function (data) {
        if (!('extensions' in data)) {
          return false;
        }
        if (data.extensions.userManagementEnabled) {
          return true;
        }
        return false;
      },
    },
    extensions: {
      label: __('Extensions', 'uipress-pro'),
      name: 'extensions',
      icon: 'extension',
    },
  };
}
//Group options
export function fetchSettings() {
  return [
    {
      component: 'switch-select',
      group: 'whiteLabel',
      uniqueKey: 'hidePlugins',
      label: __('Hide UiPress from plugin table', 'uipress-pro'),
      help: __('If enabled, both UiPress lite and pro (if installed) will be hidden from the plugins table', 'uipress-pro'),
      accepts: Boolean,
    },

    //Advanced
    {
      component: 'switch-select',
      group: 'advanced',
      uniqueKey: 'addRoleToBody',
      label: __('Add user roles as body class', 'uipress-pro'),
      help: __('If enabled, the current user roles will be added as classes to the admin body tag. This can give you more flexibility in your css for role based conditions', 'uipress-pro'),
      accepts: Boolean,
      order: 2,
    },
    {
      component: 'array-list',
      group: 'advanced',
      uniqueKey: 'enqueueStyles',
      label: __('Enqueue styles', 'uipress-pro'),
      help: __('Add stylesheets to the head of every admin page', 'uipress-pro'),
      accepts: Array,
      order: 3,
    },
    //Advanced
    {
      component: 'array-list',
      group: 'advanced',
      uniqueKey: 'enqueueScripts',
      label: __('Enqueue scripts', 'uipress-pro'),
      help: __('Add scripts to the head of every admin page', 'uipress-pro'),
      accepts: Array,
      order: 4,
    },

    //Advanced
    {
      component: 'code-editor',
      group: 'advanced',
      uniqueKey: 'htmlHead',
      label: __('HTML for head', 'uipress-pro'),
      help: __('Add HTML here to be added to every admin page head section', 'uipress-pro'),
      accepts: String,
      args: {
        language: 'html',
      },
      order: 5,
    },

    //login
    {
      component: 'switch-select',
      group: 'login',
      uniqueKey: 'darkMode',
      label: __('Dark mode', 'uipress-pro'),
      help: __('Forces dark mode on the login page', 'uipress-pro'),
      accepts: Boolean,
      order: 6,
    },
    {
      component: 'switch-select',
      group: 'login',
      uniqueKey: 'hideLanguage',
      label: __('Disable language selector', 'uipress-pro'),
      help: __('Disables the language selector on the login page', 'uipress-pro'),
      order: 7,
    },
    {
      component: 'switch-select',
      group: 'login',
      uniqueKey: 'removeBranding',
      label: __('Remove UiPress link', 'uipress-pro'),
      help: __('Removes the powered by uipress link', 'uipress-pro'),
      order: 8,
    },
    {
      component: 'code-editor',
      group: 'login',
      uniqueKey: 'panelHTML',
      label: __('Custom HTML', 'uipress-pro'),
      help: __('HTML to be added to the side panel of the login page. Only works when the login theme is enabled and not using the centered form.', 'uipress-pro'),
      accepts: String,
      order: 9,
      args: {
        language: 'html',
      },
    },
    {
      component: 'code-editor',
      group: 'login',
      uniqueKey: 'loginCSS',
      label: __('Custom CSS', 'uipress-pro'),
      help: __('CSS to be added to the login page', 'uipress-pro'),
      accepts: String,
      order: 10,
      args: {
        language: 'css',
      },
    },

    ///Media
    {
      component: 'switch-select',
      group: 'media',
      uniqueKey: 'privateLibrary',
      label: __('Private library', 'uipress-pro'),
      help: __('If enabled, users will only be able to view their own media in the media library. This does not apply to administrators', 'uipress-pro'),
      accepts: Boolean,
    },

    ///Media
    {
      component: 'switch-select',
      group: 'postsPages',
      uniqueKey: 'privatePosts',
      label: __('Private posts', 'uipress-pro'),
      help: __('If enabled, users will only be able to view their own posts and pages in the post tables. This does not apply to administrators', 'uipress-pro'),
      accepts: Boolean,
    },

    ///Extensions
    {
      component: 'switch-select',
      group: 'extensions',
      uniqueKey: 'menuCreatorEnabled',
      label: __('Menu builder', 'uipress-pro'),
      help: __('The menu builder allows you to create custom admin menus for roles and users', 'uipress-pro'),
      accepts: Boolean,
    },

    {
      component: 'switch-select',
      group: 'extensions',
      uniqueKey: 'userManagementEnabled',
      label: __('User management', 'uipress-pro'),
      help: __('The user management page allows you manage your users, roles and user activity all in one place', 'uipress-pro'),
      accepts: Boolean,
    },

    {
      component: 'switch-select',
      group: 'extensions',
      uniqueKey: 'foldersEnabled',
      label: __('Content folders', 'uipress-pro'),
      help: __('Enables a folder system for posts, pages, custom post types and media', 'uipress-pro'),
      accepts: Boolean,
    },
    ///Activity log
    {
      component: 'switch-select',
      group: 'activityLog',
      uniqueKey: 'activityLogEnabled',
      label: __('Enable activity log', 'uipress-pro'),
      help: __('If enabled, actions taken by all users, posts changes, comments, post creation etc in the activity log.', 'uipress-pro'),
      accepts: Boolean,
    },
    {
      component: 'switch-select',
      group: 'activityLog',
      uniqueKey: 'anonymizeIP',
      label: __('Anonymize user IP addresses', 'uipress-pro'),
      help: __('Each history item logs the users IP address, enable this to anonymize the address.', 'uipress-pro'),
      accepts: Boolean,
      condition: function (data) {
        if (!('activityLog' in data)) {
          return false;
        }
        if (data.activityLog.activityLogEnabled) {
          return true;
        }
        return false;
      },
    },
    {
      component: 'uip-input',
      group: 'activityLog',
      uniqueKey: 'historyMaxAmount',
      label: __('Max number of history items to keep', 'uipress-pro'),
      help: __(
        "Changing this will ensure you database doesn't get overloaded with entries, the default is 20,000. The optimum number will depend on your hosting, disk space and other factors but no more than 50,000 is recomended.",
        'uipress-pro'
      ),
      accepts: Number,
      condition: function (data) {
        if (!('activityLog' in data)) {
          return false;
        }
        if (data.activityLog.activityLogEnabled) {
          return true;
        }
        return false;
      },
    },
    {
      component: 'uip-input',
      group: 'activityLog',
      uniqueKey: 'historyMaxLength',
      label: __('How long to keep history', 'uipress-pro'),
      help: __('History items will be deleted after 60 days, enter the amount of days to keep items to change this', 'uipress-pro'),
      accepts: Number,
      condition: function (data) {
        if (!('activityLog' in data)) {
          return false;
        }
        if (data.activityLog.activityLogEnabled) {
          return true;
        }
        return false;
      },
    },

    {
      component: 'multi-select-option',
      group: 'activityLog',
      uniqueKey: 'actionsNoTrack',
      label: __('Actions not to track', 'uipress-pro'),
      help: __("Select actions you don't want uipress to log in the activity log.", 'uipress-pro'),
      accepts: Array,
      condition: function (data) {
        if (!('activityLog' in data)) {
          return false;
        }
        if (data.activityLog.activityLogEnabled) {
          return true;
        }
        return false;
      },
      args: {
        options: [
          {
            name: 'page_view',
            label: __('Page view', 'uipress-pro'),
          },
          {
            name: 'post_created',
            label: __('Post created', 'uipress-pro'),
          },
          {
            name: 'post_updated',
            label: __('Post updated', 'uipress-pro'),
          },
          {
            name: 'post_trashed',
            label: __('Post trashed', 'uipress-pro'),
          },
          {
            name: 'post_deleted',
            label: __('Post deleted', 'uipress-pro'),
          },
          {
            name: 'post_status_change',
            label: __('Post status change', 'uipress-pro'),
          },
          {
            name: 'trash_comment',
            label: __('Trashed comment', 'uipress-pro'),
          },
          {
            name: 'delete_comment',
            label: __('Deelete comment', 'uipress-pro'),
          },

          {
            name: 'plugin_activated',
            label: __('Plugin activated', 'uipress-pro'),
          },
          {
            name: 'plugin_deactivated',
            label: __('Plugin deactivated', 'uipress-pro'),
          },
          {
            name: 'plugin_deleted',
            label: __('Plugin deleted', 'uipress-pro'),
          },
          {
            name: 'user_login',
            label: __('User login', 'uipress-pro'),
          },
          {
            name: 'user_logout',
            label: __('User logout', 'uipress-pro'),
          },
          {
            name: 'option_added',
            label: __('Site option added', 'uipress-pro'),
          },
          {
            name: 'attachment_uploaded',
            label: __('Attachmnet uploaded', 'uipress-pro'),
          },
          {
            name: 'attachment_deleted',
            label: __('Attachmnet deleted', 'uipress-pro'),
          },
          {
            name: 'user_created',
            label: __('User created', 'uipress-pro'),
          },
          {
            name: 'user_deleted',
            label: __('User deleted', 'uipress-pro'),
          },
          {
            name: 'user_updated',
            label: __('User updated', 'uipress-pro'),
          },
        ],
      },
    },
    {
      component: 'uip-activity-database',
      group: 'activityLog',
      uniqueKey: 'databaseDetails',
      label: __('Use remote database', 'uipress-pro'),
      help: __('Instead of storing history in your default WordPress database you can use an alternate database by adding the details below', 'uipress-pro'),
      accepts: Number,
      condition: function (data) {
        if (!('activityLog' in data)) {
          return false;
        }
        if (data.activityLog.activityLogEnabled) {
          return true;
        }
        return false;
      },
    },

    ///Folders
    {
      component: 'switch-select',
      group: 'contentFolders',
      uniqueKey: 'limitToAuthor',
      label: __('Folders per user', 'uipress-pro'),
      help: __('If enabled, users will only see their own folders', 'uipress-pro'),
      accepts: Boolean,
    },

    {
      component: 'switch-select',
      group: 'contentFolders',
      uniqueKey: 'perType',
      label: __('Folders per type', 'uipress-pro'),
      help: __("If enabled, created folders will be unique to the current post type and won't show on all post types", 'uipress-pro'),
      accepts: Boolean,
    },

    {
      component: 'uip-select-post-types',
      group: 'contentFolders',
      uniqueKey: 'enabledForTypes',
      label: __('Enabled for post types', 'uipress-pro'),
      help: __('Choose the post types you wish to use folders with', 'uipress-pro'),
      accepts: Array,
    },
  ];
}
