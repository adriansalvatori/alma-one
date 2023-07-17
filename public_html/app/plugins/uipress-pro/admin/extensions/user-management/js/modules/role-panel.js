export function moduleData() {
  return {
    props: {
      refreshTable: Function,
      closePanel: Function,
    },
    inject: ['appData', 'uipress'],
    data: function () {
      return {
        activeroleName: '',
        toggleState: false,
        translations: this.appData.translations,
        currentRole: this.$route.params.role,
        role: {
          editData: [],
        },
        capSeach: '',
        activeCat: 'all',
        allcaps: this.appData.capabilities,
        customcap: '',
        userFecthed: false,
        newCap: {
          name: '',
        },
      };
    },
    mounted: function () {
      this.setRole();
    },
    watch: {
      'newCap.name': {
        handler(newValue, oldValue) {
          if (newValue && newValue.length > 0) {
            let ammended = newValue;
            ammended = ammended.replace(' ', '_');
            ammended = ammended.replace('-', '_');
            ammended = ammended.toLowerCase();

            this.newCap.name = ammended;
          }
        },
        deep: true,
      },
    },
    computed: {
      totalAvailableCaps() {
        return this.allcaps.all.caps.length;
      },
      totalAssignedCaps() {
        let allCaps = this.role.editData.caps;
        let count = 0;
        for (var cat in allCaps) {
          let currentcap = allCaps[cat];
          if (currentcap == true) {
            count += 1;
          }
        }
        return count;
      },
    },
    methods: {
      setRole() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_get_singular_role');
        formData.append('security', uip_ajax.security);
        formData.append('role', self.currentRole);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.role.editData = data.role;
          self.userFecthed = true;
          self.activeroleName = self.role.editData.name;
        });
      },
      updateRole() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_update_user_role');
        formData.append('security', uip_ajax.security);
        formData.append('role', JSON.stringify(self.role.editData));
        formData.append('originalRoleName', self.activeroleName);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.activeroleName = self.role.editData.name;
        });
      },
      deleteRole() {
        let self = this;

        if (!confirm(self.appData.translations.confirmRoleDelete)) {
          return;
        }

        let formData = new FormData();
        formData.append('action', 'uip_delete_role');
        formData.append('security', uip_ajax.security);
        formData.append('role', JSON.stringify(self.role.editData));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.$router.push('/');
          self.appData.refreshRoles();
        });
      },
      addCustomCap() {
        let self = this;
        self.role.editData.caps[self.newCap.name] = true;

        let formData = new FormData();
        formData.append('action', 'uip_add_custom_capability');
        formData.append('security', uip_ajax.security);
        formData.append('role', JSON.stringify(self.role.editData));
        formData.append('customcap', self.newCap.name);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.newCap.name = '';
          self.allcaps = data.allcaps;
        });
      },
      toggleAllCaps() {
        let self = this;
        for (let cap of this.allcaps.all.caps) {
          self.role.editData.caps[cap] = this.toggleState;
        }

        this.toggleState = !this.toggleState;
      },
      isInCaps(cap) {
        let currentcaps = this.role.editData.caps;
        if (currentcaps[cap] && currentcaps[cap] == true) {
          return true;
        } else {
          return false;
        }
      },
      toggleCap(cap) {
        let currentcaps = this.role.editData.caps;
        if (currentcaps[cap] && currentcaps[cap] == true) {
          this.role.editData.caps[cap] = false;
        } else {
          this.role.editData.caps[cap] = true;
        }
      },
      removeCapability(cap) {
        let self = this;
        if (!confirm(self.appData.translations.confirmCapDelete)) {
          return;
        }

        let formData = new FormData();
        formData.append('action', 'uip_remove_custom_capability');
        formData.append('security', uip_ajax.security);
        formData.append('role', JSON.stringify(self.role.editData));
        formData.append('customcap', cap);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.allcaps = data.allcaps;
        });
      },
      inSearch(cap) {
        let lcCap = cap.toLowerCase();
        let lcSearch = this.capSeach.toLowerCase();
        if (lcCap.includes(lcSearch)) {
          return true;
        }
        return false;
      },
    },
    template: `
      
      <floating-panel closeRoute="/">
      
          
      
        <div class="uip-flex uip-flex-grow uip-flex-column uip-max-h-100p">
      
          <div class="uip-padding-m uip-border-bottom uip-flex uip-flex-column uip-row-gap-xs" >
            <div class="uip-text-bold uip-text-xl">{{appData.translations.editRole}}</div>
          </div>
          
          <div v-if="!userFecthed" class="uip-padding-m uip-flex uip-flex-middle uip-flex-center uip-flex-grow">
            <loading-chart></loading-chart>
          </div>
          
          <!-- EDITING USER -->
          <div v-else class="uip-padding-m uip-flex-grow uip-border-bottom uip-overflow-auto" >
            <div class="uip-flex uip-flex-column uip-row-gap-m">
              <div v-if="role.editData.name == 'administrator'" class="uip-background-orange-wash uip-padding-xs uip-border-round">
                {{appData.translations.adminWarning}}
              </div>
              
              <div class="">
                <div class="uip-margin-bottom-xs">{{appData.translations.label}}</div>
                <input type="text" class="uip-input uip-w-100p" v-model="role.editData.label">
              </div>
              
              <div class="">
                <div class="uip-margin-bottom-xs">{{appData.translations.loginRedirect}}</div>
                <input placeholder="index.php" 
                type="text" class="uip-input uip-w-100p" v-model="role.editData.redirect">
              </div>
              
              <div>
                <div class="uip-flex uip-margin-bottom-s uip-flex-middle uip-flex-center uip-flex-between uip-background-muted uip-border-rounded uip-padding-xs uip-border-round">
                    <div class="uip-text-m uip-text-bold uip-flex-grow">{{appData.translations.capabilities}}</div>
                    <div class="uip-text-muted">{{totalAssignedCaps}} / {{totalAvailableCaps}}</div>
                </div>
                
                
                <div class="uip-padding-xs uip-flex uip-gap-s">
                
                  <!--Categories-->
                  <div class="uip-w-150 uip-flex uip-flex-column uip-gap-xxs">
                    <template v-for="cat in allcaps">
                      <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-padding-xxxs uip-border-round uip-link-muted hover:uip-background-muted" 
                      :class="{'uip-text-bold uip-text-emphasis' : activeCat == cat.shortname}" @click="activeCat = cat.shortname">
                        <div class="uip-icon">{{cat.icon}}</div>
                        <div class="">{{cat.name}}</div>
                      </div>
                    </template>
                    
                    
                    <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-padding-xxxs uip-border-round uip-link-muted uip-margin-top-s" 
                    @click="toggleAllCaps(role)">
                      <span class="uip-icon">indeterminate_check_box</span>
                      <span class="uip-line-height-1">{{appData.translations.toggleAll}}</span>
                    </div>
                    
                    <!--New cap-->
                    <drop-down dropPos="bottom-left">
                      <template v-slot:trigger>
                        <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-border-round uip-button-default uip-margin-top-s">
                          <span class="uip-icon">add</span>
                          <span class="uip-line-height-1">{{appData.translations.addCapability}}</span>
                        </div>
                      </template>
                      <template v-slot:content>
                          <div class="uip-flex uip-flex-column uip-row-gap-s uip-padding-xs">
                            
                            <input :placeholder="appData.translations.addCustomCapability" class="uip-input-small uip-w-100p" type="text" v-model="newCap.name">
                                                        
                            <button class="uip-button-primary" @click="addCustomCap()">{{appData.translations.addCapability}}</button>
                          </div>
                      </template>
                    </drop-down>
                    
                    
                  </div>
                  
                  
                  <!-- caps -->
                  <div class="uip-flex-grow uip-padding-xxs uip-flex uip-flex-column uip-row-gap-xxs">
                  
                    <div class="hover:uip-background-muted uip-padding-xxs uip-border-round uip-flex uip-flex-center uip-gap-xs uip-margin-bottom-xs">
                      <span class="uip-icon">search</span>
                      <input class="uip-blank-input uip-flex-grow uip-text-s" :placeholder="appData.translations.searchCapabilities" v-model="capSeach">
                    </div>
                    
                    <template v-for="cap in allcaps[activeCat].caps">
                    
                      <div v-if="inSearch(cap)" class="uip-flex uip-flex-center uip-gap-s uip-background-muted uip-border-round uip-cursor-pointer uip-padding-xxs">
                        <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-border-round uip-cursor-pointer uip-flex-grow" @click="toggleCap(cap)">
                          <div v-if="isInCaps(cap)" class="uip-icon uip-text-accent">radio_button_checked</div>
                          <div v-if="!isInCaps(cap)" class="uip-icon" >radio_button_unchecked</div>
                          <div class="uip-flex-grow uip-text-s">{{cap}}</div>
                        </div>
                        <div>
                          <div class="uip-icon uip-link-danger" @click="removeCapability(cap)">delete</div>
                        </div>
                      </div>
                      
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="uip-padding-m uip-flex uip-flex-between">
            <button class="uip-button-danger" @click="deleteRole()">{{appData.translations.deleteRole}}</button>
            <button class="uip-button-primary" @click="updateRole()">{{appData.translations.saveRole}}</button>
          </div>
          
          
        </div>
      
      
      </floating-panel>
      `,
  };
}
