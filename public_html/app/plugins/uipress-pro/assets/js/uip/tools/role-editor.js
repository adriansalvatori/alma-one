const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {},
    data: function () {
      return {
        loading: true,
        allRoles: [],
        allCaps: {},
        activeCapGroup: 'all',
        toggleState: true,
        strings: {
          roleEditor: __('Role editor', 'uipress-pro'),
          searchRoles: __('Search roles', 'uipress-pro'),
          permissions: __('Permissions', 'uipress-pro'),
          users: __('Users', 'uipress-pro'),
          others: __('others', 'uipress-pro'),
          noUsers: __('No users with this role', 'uipress-pro'),
          label: __('Label', 'uipress-pro'),
          capabilities: __('Capabilities', 'uipress-pro'),
          duplicate: __('Duplicate', 'uipress-pro'),
          delete: __('Delete', 'uipress-pro'),
          save: __('Save', 'uipress-pro'),
          adminWarning: __(
            'You are currently editing the administrator role. This is usually the most important role on the site so please make sure not to remove nessecary capabilities.',
            'uipress-pro'
          ),
          areYouSure: __('Are you sure?', 'uipress-pro'),
          confirmDelete: __('Please double check the role you are deleting and ensure you no longer require it', 'uipress-pro'),
          yesDeleteRole: __('Yes, delete role', 'uipress-pro'),
          newRole: __('New role', 'uipress-pro'),
          roleName: __('Role name', 'uipress-pro'),
          roleLabel: __('Role label', 'uipress-pro'),
          createRole: __('Create role', 'uipress-pro'),
          cloneRole: __('Clone role', 'uipress-pro'),
          toggleAll: __('Toggle all', 'uipress-pro'),
          newCapability: __('New capability', 'uipress-pro'),
          createCap: __('Create capability', 'uipress-pro'),
          capName: __('Capability name', 'uipress-pro'),
        },
        filters: {
          search: '',
        },
        newRole: {
          name: '',
          label: '',
        },
        newCap: {
          name: '',
        },
      };
    },
    inject: ['uipData', 'uipress'],
    watch: {
      filters: {
        handler(newval, oldval) {
          this.getRoles();
        },
        deep: true,
      },
      'newRole.name': {
        handler(newValue, oldValue) {
          if (newValue && newValue.length > 0) {
            let ammended = newValue;
            ammended = ammended.replace(' ', '-');
            ammended = ammended.toLowerCase();

            this.newRole.name = ammended;
          }
        },
        deep: true,
      },
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
    mounted: function () {
      this.getRoles();
    },
    computed: {
      returnAllCaps() {
        let self = this;
        if (!('all' in self.allCaps)) {
          return 0;
        }

        return self.allCaps.all.caps.length;
      },
    },
    methods: {
      getRoles() {
        let self = this;

        let filters = JSON.stringify(self.filters);

        let formData = new FormData();
        formData.append('action', 'uip_get_all_roles');
        formData.append('security', uip_ajax.security);
        formData.append('filters', filters);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.loading = false;
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true, false);
            self.loading = false;
          }
          if (response.success) {
            self.allRoles = response.roles;
          }
        });
      },
      openRole(role) {
        let self = this;

        //Role is already closed so we are probably closing it
        if (role.open) {
          role.open = false;
          return;
        }
        //We have already fetched caps so let's not do that again
        if ('all' in self.allCaps) {
          role.open = true;
        } else {
          self.getAllCaps(role);
        }
      },
      getAllCaps(role) {
        let self = this;
        let formData = new FormData();
        formData.append('action', 'uip_get_all_capabilities');
        formData.append('security', uip_ajax.security);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.loading = false;
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true, false);
          }
          if (response.success) {
            self.allCaps = response.caps;

            if (role) {
              role.open = true;
            }
          }
        });
      },
      returnAllSelectedCapsCount(role) {
        let count = 0;
        for (let cap in role.caps) {
          if (role.caps[cap]) {
            count += 1;
          }
        }
        return count;
      },
      updateRole(role) {
        let self = this;
        let roleJson = JSON.stringify(role);

        let formData = new FormData();
        formData.append('action', 'uip_update_role');
        formData.append('security', uip_ajax.security);
        formData.append('role', roleJson);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true, false);
          }
          if (response.success) {
            self.uipress.notify(response.message, '', 'success', true, false);
          }
        });
      },
      deleteRole(role, index) {
        let self = this;
        let roleJson = JSON.stringify(role);
        //Ok let's delete the role

        let formData = new FormData();
        formData.append('action', 'uip_delete_role');
        formData.append('security', uip_ajax.security);
        formData.append('role', roleJson);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true, false);
          }
          if (response.success) {
            self.uipress.notify(response.message, '', 'success', true, false);
            self.allRoles.splice(index, 1);
          }
        });
        //End delete
      },
      createRole(role) {
        let self = this;

        if (self.newRole.name == '') {
          self.uipress.notify(__('Role name can not be emtpty', 'uipress-pro'), '', 'error', true, false);
          return;
        }

        if (self.newRole.label == '') {
          self.uipress.notify(__('Role label can not be emtpty', 'uipress-pro'), '', 'error', true, false);
          return;
        }

        let roleJson = JSON.stringify(self.newRole);

        let caps = JSON.stringify({});
        if (role) {
          caps = JSON.stringify(role.caps);
        }

        let formData = new FormData();
        formData.append('action', 'uip_create_role');
        formData.append('security', uip_ajax.security);
        formData.append('role', roleJson);
        formData.append('caps', caps);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true, false);
          }
          if (response.success) {
            self.uipress.notify(response.message, '', 'success', true, false);
            this.getRoles();
            self.newRole.name = '';
            self.newRole.label = '';
          }
        });
      },

      toggleAllCaps(role) {
        for (let cap of this.allCaps.all.caps) {
          role.caps[cap] = this.toggleState;
        }

        this.toggleState = !this.toggleState;
      },
      removeCap(role, cap) {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_delete_cap');
        formData.append('security', uip_ajax.security);
        formData.append('rolename', role.name);
        formData.append('rolelabel', role.label);
        formData.append('cap', cap);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true, false);
          }
          if (response.success) {
            self.uipress.notify(response.message, '', 'success', true, false);
            self.getAllCaps(false);
          }
        });
      },
      createCap(role) {
        let self = this;

        if (self.newCap.name == '') {
          self.uipress.notify(__('Capability name can not be emtpty', 'uipress-pro'), '', 'error', true, false);
          return;
        }
        let cap = self.newCap.name;

        let formData = new FormData();
        formData.append('action', 'uip_create_cap');
        formData.append('security', uip_ajax.security);
        formData.append('rolename', role.name);
        formData.append('rolelabel', role.label);
        formData.append('cap', cap);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true, false);
          }
          if (response.success) {
            self.uipress.notify(response.message, '', 'success', true, false);
            self.newCap.name = '';
            self.getAllCaps(false);
          }
        });
      },
    },
    template: `
      <div class="uip-flex uip-flex-column uip-row-gap-xs">
      
      
        <!-- Roles title -->
        <div class="uip-flex uip-cursor-pointer uip-margin-bottom-xs uip-background-muted uip-border-rounded uip-padding-xs uip-border-round uip-text-bold uip-text-emphasis">
          {{strings.roleEditor}}
        </div>
        
        
        <!-- Search roles -->
        <div class="uip-padding-xs uip-flex-grow uip-flex uip-flex uip-gap-xxs">
          <div class="uip-flex uip-flex-center uip-flex-grow">
            <span class="uip-icon uip-text-muted uip-margin-right-xs uip-text-l uip-icon-medium">search</span>
            <input class="uip-blank-input uip-flex-grow uip-text-s" type="search" :placeholder="strings.searchRoles" v-model="filters.search" autofocus="">
          </div>
          
          <drop-down dropPos="bottom-right">
            <template v-slot:trigger>
              <button class="uip-button-default uip-flex uip-gap-xxs">
                <span class="uip-icon">add</span>
                <span class="uip-text-s">{{strings.newRole}}</span>
              </button>
            </template>
            <template v-slot:content>
              <div class="uip-flex uip-flex-column uip-row-gap-xs uip-padding-xs">
                <div class="">
                  <div class="uip-margin-bottom-xxs uip-text-muted">{{strings.roleName}}</div>
                  <input class="uip-input-small uip-w-100p" type="text" v-model="newRole.name">
                </div>
                
                <div class="">
                  <div class="uip-margin-bottom-xxs uip-text-muted">{{strings.roleLabel}}</div>
                  <input class="uip-input-small uip-w-100p" type="text" v-model="newRole.label">
                </div>
                
                <button class="uip-button-primary" @click="createRole()">{{strings.createRole}}</button>
              </div>
            </template>
          </drop-down>
        </div>
        
        <div v-if="loading" class="uip-flex uip-flex-center uip-flex-middle uip-padding-m"><loading-chart></loading-chart></div>
        <!-- Roles list -->
        <div v-else class="uip-flex uip-flex-column uip-row-gap-xxxs">
          
          <template v-for="(role, index) in allRoles">
            
            <div>
              <!-- Role overview -->
              <div class="uip-flex uip-flex-row uip-flex-between uip-gap-xxs uip-padding-xs uip-border-round hover:uip-background-muted uip-cursor-pointer" @click="openRole(role)" :class="role.open ? 'uip-background-muted' : ''">
                
                <div class="uip-flex uip-flex-column uip-flex-no-wrap uip-flex-grow uip-row-gap-xxs">
                  <div class="uip-flex uip-gap-xxs uip-flex-center">
                    <span class="uip-text-m uip-text-bold">{{role.label}}</span>\
                    <span class="uip-text-muted">({{role.name}})</span>
                  </div>
                  <div class="uip-flex uip-gap-xxs uip-flex-center">
                    
                    
                    
                    <div v-if="role.users.length > 0" class="uip-flex uip-flex-reverse uip-margin-left-xxs">
                      <template v-for="(user,index) in role.users">
                        <div class="uip-w-20 uip-ratio-1-1 uip-text-s uip-background-primary-wash uip-border-circle uip-border-match uip-text-capitalize 
                        uip-text-center uip-line-height-1 uip-text-center uip-flex uip-flex-center uip-flex-middle uip-margin-left--8">
                          <uip-tooltip :message="user" :delay="50">
                            <span>{{user[0]}}</span>
                          </uip-tooltip>
                        </div>
                      </template>
                    </div>
                    
                    <div v-if="role.usersCount > role.users.length" class="uip-text-muted uip-text-s">+{{role.usersCount - role.users.length}} {{strings.others}}</div>
                    
                    <div v-else-if="role.users.length == 0" class="uip-text-muted uip-text-s">{{strings.noUsers}}</div>
                    
                    
                  </div>
                </div>
                
                <div class="">
                  
                </div>
                
              </div>
              
              <!-- Role editor-->
              
              <div v-if="role.open" class="uip-padding-xs uip-scale-in-top uip-margin-bottom-m  uip-margin-top-s uip-flex uip-flex-column uip-flex-no-wrap uip-row-gap-m">
                

                <div class="uip-grid-col-2">
                  <div class="">
                    <div class="uip-margin-bottom-xxs uip-text-muted">{{strings.label}}</div>
                    <input class="uip-input-small uip-w-100p" type="text" v-model="role.label">
                  </div>
                
                </div>
                
                <div v-if="role.name == 'administrator'" class="uip-background-orange-wash uip-padding-xs uip-border-round">
                  {{strings.adminWarning}}
                </div>
                
                
                
                <div class="uip-flex uip-cursor-pointer uip-text-s uip-background-muted uip-border-rounded uip-padding-xs uip-border-round uip-text-emphasis uip-flex uip-flex-between">
                  <span>{{strings.capabilities}}</span>
                  <span class="uip-text-muted">{{returnAllSelectedCapsCount(role)}} / {{returnAllCaps}} granted</span>
                </div>
                
                <!-- Capability editor -->
                <div class="">
                
                  <div class="uip-flex uip-flex-row uip-flex-no-wrap uip-gap-xs">
                  
                    <!-- Categories -->
                    <div class="uip-flex uip-flex-column uip-w-125 uip-row-gap-xxs">
                      <template v-for="capCat in allCaps">
                        <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-padding-xxxs uip-border-round uip-link-muted hover:uip-background-muted" 
                        :class="activeCapGroup == capCat.shortname ? 'uip-text-normal uip-text-bold' : ''" @click="activeCapGroup = capCat.shortname">
                          <span class="uip-icon">{{capCat.icon}}</span>
                          <span class="uip-line-height-1">{{capCat.name}}</span>
                        </div>
                      </template>
                      
                      <div class=" uip-margin-top-xs uip-margin-bottom-xs"></div>
                      
                      <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-padding-xxxs uip-border-round uip-link-muted" 
                      @click="toggleAllCaps(role)">
                        <span class="uip-icon">indeterminate_check_box</span>
                        <span class="uip-line-height-1">{{strings.toggleAll}}</span>
                      </div>
                      <drop-down dropPos="bottom-left">
                        <template v-slot:trigger>
                          <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-padding-xxxs uip-border-round uip-link-muted">
                            <span class="uip-icon">add</span>
                            <span class="uip-line-height-1">{{strings.newCapability}}</span>
                          </div>
                        </template>
                        <template v-slot:content>
                            <div class="uip-flex uip-flex-column uip-row-gap-xs uip-padding-xs">
                              
                              <div class="">
                                <div class="uip-margin-bottom-xxs uip-text-muted">{{strings.capName}}</div>
                                <input class="uip-input-small uip-w-100p" type="text" v-model="newCap.name">
                              </div>
                              
                              <button class="uip-button-primary" @click="createCap(role)">{{strings.createCap}}</button>
                            </div>
                        </template>
                      </drop-down>
                    </div>
                    
                    <!-- Caps -->
                    <div class="uip-flex-grow uip-flex uip-flex-column uip-gap-xxs uip-flex-no-wrap uip-max-h-300 uip-overflow-auto uip-padding-right-xs">
                      <template v-for="cap in allCaps[activeCapGroup].caps">
                        
                        <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-padding-xxs uip-border-round uip-cursor-pointer"
                        :class="role.caps[cap] ? 'uip-background-muted' : ''">\
                          <div v-if="role.caps[cap]" class="uip-icon uip-text-accent" @click="role.caps[cap] = !role.caps[cap]">radio_button_checked</div>
                          <div v-if="!role.caps[cap]" class="uip-icon" @click="role.caps[cap] = !role.caps[cap]">radio_button_unchecked</div>
                          <div class="uip-flex-grow uip-line-height-1 uip-text-s" @click="role.caps[cap] = !role.caps[cap]">{{cap}}</div>
                          <div class="uip-icon uip-link-danger" @click="removeCap(role, cap)">delete</div>
                        </div>
                        
                      </template>
                    </div>
                    
                  </div>
                  
                </div>
                <!-- End capabaility editor -->
                
                <div class="uip-flex uip-flex-row uip-gap-xs">
                  
                  <drop-down dropPos="bottom-left">
                    <template v-slot:trigger>
                      <button class="uip-button-danger uip-flex uip-gap-xxs uip-flex-center">
                        <span class="uip-icon">delete</span>
                        <span>{{strings.delete}}</span>
                      </button>
                    </template>
                    <template v-slot:content>
                      <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xs uip-flex-start uip-max-w-260">
                        <div class="uip-text-emphasis">{{strings.areYouSure}}</div>
                        <div class="uip-text-muted">{{strings.confirmDelete}}</div>
                        
                        <button class="uip-button-danger uip-flex uip-gap-xxs uip-flex-center" @click="deleteRole(role, index)">
                          <span>{{strings.yesDeleteRole}}</span>
                        </button>
                      </div>
                    </template>
                  </drop-down>
                
                  
                  <drop-down dropPos="bottom-right">
                    <template v-slot:trigger>
                      <button class="uip-button-default uip-flex uip-gap-xxs">
                        <span class="uip-icon">content_copy</span>
                        <span class="">{{strings.cloneRole}}</span>
                      </button>
                    </template>
                    <template v-slot:content>
                      <div class="uip-flex uip-flex-column uip-row-gap-xs uip-padding-xs">
                        <div class="">
                          <div class="uip-margin-bottom-xxs uip-text-muted">{{strings.roleName}}</div>
                          <input class="uip-input-small uip-w-100p" type="text" v-model="newRole.name">
                        </div>
                        
                        <div class="">
                          <div class="uip-margin-bottom-xxs uip-text-muted">{{strings.roleLabel}}</div>
                          <input class="uip-input-small uip-w-100p" type="text" v-model="newRole.label">
                        </div>
                        
                        <button class="uip-button-primary" @click="createRole(role)">{{strings.cloneRole}}</button>
                      </div>
                    </template>
                  </drop-down>
                  
                  <div class="uip-flex-grow uip-flex uip-flex-right">
                  
                    <button class="uip-button-primary uip-flex uip-gap-xxs uip-flex-center uip-flex-right" @click="updateRole(role)">
                      <span class="uip-icon">save</span>
                      <span>{{strings.save}}</span>
                    </button>
                  
                  </div>
                
                </div>
                
                
              </div>
              
            </div>
          
          </template>
        </div>
        
      </div>`,
  };
}
