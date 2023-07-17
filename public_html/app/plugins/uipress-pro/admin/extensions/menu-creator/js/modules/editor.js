const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    data: function () {
      return {
        loading: true,
        id: this.$route.params.id,
        saving: false,
        activeTab: 'content',
        switchOptions: {
          blocks: {
            value: 'content',
            label: __('Menu items', 'uipress-lite'),
          },
          patterns: {
            value: 'settings',
            label: __('Settings', 'uipress-lite'),
          },
        },
        strings: {
          active: __('Active', 'uipress-pro'),
          name: __('Name', 'uipress-pro'),
          autoUpdate: __('Auto update', 'uipress-pro'),
          autoUpdateDesc: __('Enabling this option will allow the menu to update automatically as you add or remove plugins', 'uipress-pro'),
          appliesTo: __('Applies to', 'uipress-pro'),
          excludes: __('Excludes', 'uipress-pro'),
          selectUsersAndRoles: __('Select users and roles', 'uipress-pro'),
          searchUsersAndRoles: __('Search users and roles', 'uipress-pro'),
          saveMenu: __('Save menu', 'uipress-pro'),
          cancel: __('Cancel', 'uipress-pro'),
          menuName: __('Menu name', 'uipress-pro'),
          menuSaved: __('Menu saved', 'uipress-pro'),
          appliesDescription: __('Who you want the menu to load for', 'uipress-pro'),
          excludesDescription: __('Who you would like the menu to not load for (optional)', 'uipress-pro'),
        },
        customMenu: {
          menu: [],
          status: false,
          appliesTo: [],
          excludes: [],
          name: '',
          autoUpdate: false,
        },
      };
    },
    inject: ['router', 'uipress', 'refreshList'],
    mounted: function () {
      this.getMenu();
    },
    watch: {},
    computed: {},
    methods: {
      closeOffcanvas() {
        let self = this;
        self.router.push('/');
      },
      getMenu() {
        let self = this;
        self.loading = true;
        let formData = new FormData();
        formData.append('action', 'uipress_get_menu');
        formData.append('security', uip_ajax.security);
        formData.append('id', self.id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            self.loading = false;
            return;
          }
          //self.customMenu = response.data;
          self.customMenu.menu = response.menuOptions.menu;
          self.customMenu.status = response.menuOptions.status;
          self.customMenu.appliesTo = response.menuOptions.appliesTo;
          self.customMenu.excludes = response.menuOptions.excludes;
          self.customMenu.name = response.menuOptions.name;
          self.customMenu.autoUpdate = response.menuOptions.autoUpdate;
          self.loading = false;
        });
      },
      saveMenu() {
        let self = this;
        self.saving = true;

        let menu = JSON.stringify(self.customMenu, (k, v) => (v === 'true' ? 'uiptrue' : v === true ? 'uiptrue' : v === 'false' ? 'uipfalse' : v === false ? 'uipfalse' : v === '' ? 'uipblank' : v));

        let formData = new FormData();
        formData.append('action', 'uipress_save_menu');
        formData.append('security', uip_ajax.security);
        formData.append('menu', menu);
        formData.append('id', self.id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            return;
          }
          self.uipress.notify(self.strings.menuSaved, '', 'success', true);
          self.saving = false;

          self.refreshList();
        });
      },
    },

    template: `
      <component is="style"> #wpadminbar{z-index:8;}#adminmenuwrap{z-index:7;} </component>

      <div
        ref="offCanvasCover"
        class="uip-position-fixed uip-w-100p uip-top-0 uip-bottom-0 uip-text-normal uip-flex uip-fade-in uip-transition-all"
        style="background:rgba(0,0,0,0.3);z-index:9;top:0;left:0;right:0;max-height:100%;backdrop-filter: blur(2px);"
      >
        <!-- MODAL GRID -->
        <div class="uip-flex uip-w-100p uip-h-100p">
          <div class="uip-flex-grow" @click="closeOffcanvas()"></div>

          <div ref="offCanvasBody" class="uip-w-600 uip-border-box uip-offcanvas-panel uip-position-relative uip-padding-s uip-padding-right-remove uip-margin-right-s" style="max-height: 100%;min-height: 100%;">
		  
            <div class="uip-flex uip-slide-in-right uip-background-default uip-overflow-hidden uip-border-rounder uip-position-relative uip-shadow uip-border uip-border-box" style="max-height: 100%;min-height: 100%;">
              <div class="uip-position-absolute uip-top-0 uip-padding-m uip-right-0 uip-z-index-1">
                <span @click="closeOffcanvas()" class="uip-icon uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer uip-link-muted uip-text-l"> close </span>
              </div>

              <div class="uip-position-relative uip-h-100p uip-flex uip-w-100p uip-flex uip-max-h-100p uip-flex uip-flex-column uip-h-100p uip-max-h-100p">
                <div class="uip-border-box uip-w-100p uip-padding-m uip-padding-bottom-remove uip-flex uip-flex-column uip-row-gap-m">
                  <div class="uip-text-xl uip-text-bold">{{customMenu.name}}</div>

                  <div class="uip-min-w-100">
                    <div class="uip-flex uip-gap-xs">
                      <template v-for="option in switchOptions">
                        <button type="button" class="uip-button-default " :class="{'uip-button-primary' : option.value == activeTab}" @click="activeTab = option.value">{{option.label}}</button>
                      </template>
                    </div>
                  </div>
                </div>

                <div v-if="loading" class="uip-w-100p uip-flex uip-flex-center uip-flex-middle uip-padding-l uip-border-box uip-w-100p">
                  <loading-chart></loading-chart>
                </div>

                <template v-else>
                  <div v-if="activeTab == 'content'" class="uip-padding-m uip-flex-grow uip-overflow-auto">
                    <menu-editor :value="customMenu.menu" :returnData="function(d){customMenu.menu = d}"></menu-editor>
                  </div>

                  <div v-if="activeTab == 'settings'" class="uip-padding-m uip-flex uip-flex-column uip-row-gap-m uip-flex-grow">
                    <div class="uip-grid-col-2 uip-grid-gap-m">
                      <div class="uip-text-bold uip-margin-bottom-xs">{{strings.name}}</div>
                      <input type="text" class="uip-input" v-model="customMenu.name" :placeholder="strings.menuName" />
                    </div>

                    <div class="uip-grid-col-2 uip-grid-gap-m">
                      <div class="uip-text-bold uip-margin-bottom-xs">{{strings.active}}</div>
                      <label class="uip-switch">
                        <input type="checkbox" v-model="customMenu.status" />
                        <span class="uip-slider"></span>
                      </label>
                    </div>

                    <div class="uip-grid-col-2 uip-grid-gap-m">
                      <div>
                        <div class="uip-text-bold uip-margin-bottom-xs">{{strings.autoUpdate}}</div>
                        <div class="uip-text-s uip-text-muted uip-margin-bottom-xs">{{strings.autoUpdateDesc}}</div>
                      </div>
                      <label class="uip-switch">
                        <input type="checkbox" v-model="customMenu.autoUpdate" />
                        <span class="uip-slider"></span>
                      </label>
                    </div>

                    <div class="uip-grid-col-2 uip-grid-gap-m">
					
					  <div>
                        <div class="uip-text-bold uip-margin-bottom-xs">{{strings.appliesTo}}</div>
					  	<div class="uip-text-s uip-text-muted uip-margin-bottom-xs">{{strings.appliesDescription}}</div>
					  </div>

                      <user-role-select
                        :selected="customMenu.appliesTo"
                        :placeHolder="strings.selectUsersAndRoles"
                        :searchPlaceHolder="strings.searchUsersAndRoles"
                        :single="false"
                        :updateSelected="function(data){fetchReturnData(data, customMenu.appliesTo)}"
                      ></user-role-select>
                    </div>

                    <div class="uip-grid-col-2 uip-grid-gap-m">
					  <div>
                        <div class="uip-text-bold uip-margin-bottom-xs">{{strings.excludes}}</div>
					    <div class="uip-text-s uip-text-muted uip-margin-bottom-xs">{{strings.excludesDescription}}</div>
					  </div>

                      <user-role-select
                        :selected="customMenu.excludes"
                        :placeHolder="strings.excludes"
                        :searchPlaceHolder="strings.excludes"
                        :single="false"
                        :updateSelected="function(data){fetchReturnData(data, customMenu.excludes)}"
                      ></user-role-select>
                    </div>
                  </div>

                  <div class="uip-border-box uip-w-100p uip-padding-m uip-flex uip-flex-row uip-flex-between uip-row-gap-s uip-border-top">
                    <button class="uip-button-default">{{strings.cancel}}</button>
                    <button class="uip-button-primary" @click="saveMenu">{{strings.saveMenu}}</button>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    `,
  };
  return compData;
}
