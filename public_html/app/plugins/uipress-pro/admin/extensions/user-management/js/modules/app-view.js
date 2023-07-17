export function moduleData() {
  return {
    data() {
      return {
        loading: true,
        screenWidth: window.innerWidth,
      };
    },
    inject: ['appData', 'uipress'],
    created: function () {
      window.addEventListener('resize', this.getScreenWidth);
      var self = this;
    },
    computed: {},
    mounted: function () {
      this.loading = false;
    },
    methods: {
      updateGloablData(data) {
        this.appData = data;
      },
    },

    template: `
    
      <div class="uip-padding-m">
          
          <router-view :key="$route.path"></router-view>
          
          <build-navigation :data="appData" :dataChange="updateGloablData"></build-navigation>
          <user-table v-if="appData.currentPage == 'users'" :data="appData" :dataChange="updateGloablData"></user-table>
          <role-table v-if="appData.currentPage == 'roles'" :data="appData" :dataChange="updateGloablData"></role-table>
          <activity-table v-if="appData.currentPage == 'activity'" :data="appData" :dataChange="updateGloablData"></activity-table>
          
      </div>`,
  };
  return compData;
}
