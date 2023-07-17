export function moduleData() {
  return {
    props: {
      refreshTable: Function,
      closePanel: Function,
      resetclone: Function,
    },
    inject: ['appData', 'uipress'],
    data: function () {
      return {
        role: {
          editData: {
            name: '',
            label: '',
            redirect: '',
            caps: {},
          },
        },
        capSeach: '',
        activeCat: 'all',
        allcaps: this.appData.capabilities,
        translations: this.appData.translations,
      };
    },
    watch: {
      'role.editData.name': {
        handler(newValue, oldValue) {
          if (newValue && newValue.length > 0) {
            let ammended = newValue;
            ammended = ammended.replace(' ', '_');
            ammended = ammended.replace('-', '_');
            ammended = ammended.toLowerCase();

            this.role.editData.name = ammended;
          }
        },
        deep: true,
      },
    },
    mounted: function () {
      this.formatClone();
    },
    computed: {
      totalAvailableCaps() {
        let allCaps = this.appData.capabilities;
        let count = 0;
        for (var cat in allCaps) {
          let currentcat = allCaps[cat];
          let caps = currentcat.caps;
          count += caps.length;
        }
        return count;
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
      formatClone() {
        if (typeof this.appData.cloneRole === 'undefined') {
          return;
        }
        if (this.appData.cloneRole.caps) {
          this.role.editData.caps = this.appData.cloneRole.caps;
        }

        if (this.appData.cloneRole.name) {
          this.role.editData.name = this.appData.cloneRole.name + '_' + this.appData.translations.copy;
        }

        if (this.appData.cloneRole.label) {
          this.role.editData.label = this.appData.cloneRole.label + ' ' + this.appData.translations.copy;
        }

        if (this.appData.cloneRole.redirect) {
          this.role.editData.redirect = this.appData.cloneRole.redirect;
        }

        this.appData.cloneRole = false;
      },
      saveRole() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_create_new_role');
        formData.append('security', uip_ajax.security);
        formData.append('newrole', JSON.stringify(self.role.editData));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');

          self.$router.push('/roles/edit/' + self.role.editData.name);
          self.appData.refreshRoles();
        });
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
            <div class="uip-text-bold uip-text-xl">{{appData.translations.newRole}}</div>
          </div>
          
          
          <!-- EDITING USER -->
          <div class="uip-padding-m uip-flex-grow uip-border-bottom uip-overflow-auto" >
            <div class="uip-flex uip-flex-column uip-row-gap-m">
              <div v-if="role.editData.name == 'administrator'" class="uip-background-orange-wash uip-padding-xs uip-border-round">
                {{appData.translations.adminWarning}}
              </div>
              
              <div class="">
                <div class="uip-margin-bottom-xs">{{appData.translations.name}}</div>
                <input type="text" class="uip-input uip-w-100p" v-model="role.editData.name">
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
            <router-link to="/" class="uip-button-default uip-no-underline" @click="deleteRole()">{{appData.translations.cancel}}</router-link>
            <button class="uip-button-primary" @click="saveRole()">{{appData.translations.saveRole}}</button>
          </div>
          
          
        </div>
      
      
      </floating-panel>
      `,
  };
}
