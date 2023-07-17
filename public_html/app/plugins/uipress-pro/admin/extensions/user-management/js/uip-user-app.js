//Dynamic import Import scripts
(async () => {
  ///IMPORT TRANSLATIONS
  const { __, _x, _n, _nx } = wp.i18n;
  const pluginVersion = import.meta.url.split('?ver=')[1];
  //App data
  const uipUserData = JSON.parse(uip_user_app_ajax.appData);
  //Import required classes and modules
  const uipress = new window.uipClass();

  //Import modules
  const navigation = await import(`./modules/navigation.min.js?ver=${pluginVersion}`);
  const userTable = await import(`./modules/user-table.min.js?ver=${pluginVersion}`);
  const roleSelect = await import(`./modules/select-roles.min.js?ver=${pluginVersion}`);
  const dropdown = await import(`../../../../../uipress-lite/assets/js/uip/modules/uip-dropdown.min.js?ver=${pluginVersion}`);
  const offcanvas = await import(`./modules/offcanvas.min.js?ver=${pluginVersion}`);
  const userPanel = await import(`./modules/user-panel.min.js?ver=${pluginVersion}`);
  const editUserPanel = await import(`./modules/user-edit-panel.min.js?ver=${pluginVersion}`);
  const userMessage = await import(`./modules/user-message.min.js?ver=${pluginVersion}`);
  const newUserPanel = await import(`./modules/new-user.min.js?ver=${pluginVersion}`);
  const roleTable = await import(`./modules/role-table.min.js?ver=${pluginVersion}`);
  const rolePanel = await import(`./modules/role-panel.min.js?ver=${pluginVersion}`);
  const newRole = await import(`./modules/new-role.min.js?ver=${pluginVersion}`);
  const activityTable = await import(`./modules/activity-table.min.js?ver=${pluginVersion}`);
  const batchRoleUpdate = await import(`./modules/batch-role-update.min.js?ver=${pluginVersion}`);
  const userGroups = await import(`./modules/user-groups.min.js?ver=${pluginVersion}`);
  const groupTemplate = await import(`./modules/group-template.min.js?ver=${pluginVersion}`);
  const groupSelect = await import(`./modules/group-select.min.js?ver=${pluginVersion}`);
  const iconSelect = await import(`./modules/icon-select.min.js?ver=${pluginVersion}`);
  const appView = await import(`./modules/app-view.min.js?ver=${pluginVersion}`);
  const loader = await import(`../../../../../uipress-lite/assets/js/uip/modules/uip-loading-chart.min.js?ver=${pluginVersion}`);
  const tooltip = await import(`../../../../../uipress-lite/assets/js/uip/modules/uip-tooltip.min.js?ver=${pluginVersion}`);
  const floatingPanel = await import(`./modules/floating-panel.min.js?ver=${pluginVersion}`);
  const statusSelect = await import(`./modules/activity-status-select.min.js?ver=${pluginVersion}`);

  const uipUserAppArgs = {
    data() {
      return {
        loading: true,
        screenWidth: window.innerWidth,
        appData: uipUserData.app,
      };
    },
    created: function () {
      window.addEventListener('resize', this.getScreenWidth);
    },
    provide() {
      return {
        appData: this.appData,
        uipress: uipress,
      };
    },
    computed: {},
    mounted: function () {},
    template: '<router-view></router-view>',
  };

  /**
   * Defines and create ui builder routes
   * @since 3.0.0
   */
  const routes = [
    {
      path: '/',
      name: __('List View', 'uipress-pro'),
      component: appView.moduleData(),
      query: { page: '1', search: '' },
      children: [
        {
          name: __('View user', 'uipress-pro'),
          path: '/users/:id',
          component: userPanel.moduleData(),
        },
        {
          name: __('Edit user', 'uipress-pro'),
          path: '/users/:id/edit',
          component: editUserPanel.moduleData(),
        },
        {
          name: __('New user', 'uipress-pro'),
          path: '/users/new',
          component: newUserPanel.moduleData(),
        },
        {
          name: __('Message user', 'uipress-pro'),
          path: '/message/:recipients',
          component: userMessage.moduleData(),
        },
        {
          name: __('Batch update roles', 'uipress-pro'),
          path: '/batch/roles/:users',
          component: batchRoleUpdate.moduleData(),
        },
        {
          name: __('Edit role', 'uipress-pro'),
          path: '/roles/edit/:role',
          component: rolePanel.moduleData(),
        },
        {
          name: __('New role', 'uipress-pro'),
          path: '/roles/edit/new',
          component: newRole.moduleData(),
        },
      ],
    },
  ];

  const uiUserrouter = VueRouter.createRouter({
    history: VueRouter.createWebHashHistory(),
    routes, // short for `routes: routes`
  });

  uiUserrouter.beforeEach((to, from, next) => {
    if (!to.query.section) {
      let newQuery = from.query;
      if (!newQuery.section) {
        newQuery.section = 'users';
      }
      next({ path: to.path, query: { ...newQuery } });
    } else {
      next();
    }
  });

  //:to="{path: '/', query: {...$route.query, my:query}}"

  const uipUserApp = Vue.createApp(uipUserAppArgs);
  //Allow reactive data from inject
  uipUserApp.config.unwrapInjectedRef = true;
  uipUserApp.config.devtools = true;
  uipUserApp.use(uiUserrouter);
  uipUserApp.provide('router', uiUserrouter);

  ///import components
  uipUserApp.component('build-navigation', navigation.moduleData());
  uipUserApp.component('user-table', userTable.moduleData());
  uipUserApp.component('role-select', roleSelect.moduleData());
  uipUserApp.component('drop-down', dropdown.moduleData());
  uipUserApp.component('tooltip', tooltip.moduleData());
  uipUserApp.component('offcanvas', offcanvas.moduleData());
  uipUserApp.component('user-panel', userPanel.moduleData());
  uipUserApp.component('role-table', roleTable.moduleData());
  uipUserApp.component('activity-table', activityTable.moduleData());
  uipUserApp.component('user-groups', userGroups.moduleData());
  uipUserApp.component('group-template', groupTemplate.moduleData());
  uipUserApp.component('group-select', groupSelect.moduleData());
  uipUserApp.component('icon-select', iconSelect.moduleData());
  uipUserApp.component('loading-chart', loader.moduleData());
  uipUserApp.component('floating-panel', floatingPanel.moduleData());
  uipUserApp.component('uip-tooltip', tooltip.moduleData());
  uipUserApp.component('activity-status-select', statusSelect.moduleData());

  uipUserApp.config.errorHandler = function (err, vm, info) {
    console.log(err);
  };

  uipUserApp.mount('#uip-user-management');
})();
