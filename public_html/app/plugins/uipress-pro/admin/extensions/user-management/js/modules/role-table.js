export function moduleData() {
  return {
    props: {
      data: Object,
      dataChange: Function,
    },
    inject: ['appData', 'uipress'],
    data: function () {
      return {
        loading: true,
        modData: this.data,
        tableData: [],
        tablePage: 1,
        selectAll: false,
        queryRunning: false,
        tableFilters: {
          search: '',
          roles: [],
          dateCreated: {
            type: 'on',
            date: '',
          },
        },
        tableOptions: {
          direction: 'ASC',
          sortBy: 'username',
          perPage: 20,
        },
        ui: {
          rolePanelOpen: false,
          activeRole: [],
          newRoleOpen: false,
          cloneRole: {},
        },
      };
    },
    mounted: function () {
      this.loading = false;
      this.getRoleData();
      this.appData.refreshRoles = this.getRoleData;
    },
    watch: {
      modData: {
        handler(newValue, oldValue) {
          this.dataChange(newValue);
        },
        deep: true,
      },
      tableFilters: {
        handler(newValue, oldValue) {
          this.getRoleData();
        },
        deep: true,
      },
      tableOptions: {
        handler(newValue, oldValue) {
          this.getRoleData();
        },
        deep: true,
      },
      selectAll: {
        handler(newValue, oldValue) {
          if (newValue == true) {
            this.selectAllUsers();
          } else {
            this.deSelectAllUsers();
          }
        },
      },
    },
    computed: {
      returnTableData() {
        return this.tableData;
      },
      totalSelected() {
        let self = this;
        let count = 0;
        for (var user in self.tableData.roles) {
          if (self.tableData.roles[user].selected && self.tableData.roles[user].selected == true) {
            count += 1;
          }
        }
        return count;
      },
      getTotalSelected() {
        let self = this;
        let selected = [];
        for (var user in self.tableData.roles) {
          if (self.tableData.roles[user].selected && self.tableData.roles[user].selected == true) {
            selected.push(self.tableData.roles[user]);
          }
        }
        return selected;
      },
    },
    methods: {
      selectAllUsers() {
        let self = this;
        for (var user in self.tableData.roles) {
          self.tableData.roles[user].selected = true;
        }
        self.selectAll = true;
      },
      deSelectAllUsers() {
        let self = this;
        for (var user in self.tableData.roles) {
          self.tableData.roles[user].selected = false;
        }
        self.selectAll = false;
      },

      changePage(direction) {
        if (direction == 'next') {
          this.tablePage += 1;
        }
        if (direction == 'previous') {
          this.tablePage = this.tablePage - 1;
        }
        this.getRoleData();
      },
      updateRoles(roles) {
        this.tableFilters.roles = roles;
      },
      getRoleData() {
        let self = this;

        if (self.queryRunning) {
          return;
        }
        self.queryRunning = true;

        let formData = new FormData();
        formData.append('action', 'uip_get_role_table_data');
        formData.append('security', uip_ajax.security);
        formData.append('tablePage', self.tablePage);
        formData.append('filters', JSON.stringify(self.tableFilters));
        formData.append('options', JSON.stringify(self.tableOptions));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;
          self.queryRunning = false;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }

          let columns = [];
          if (self.tableData.columns && self.tableData.columns.length > 0) {
            columns = self.tableData.columns;
          }
          self.tableData = data.tableData;

          if (columns.length > 0) {
            self.tableData.columns = columns;
          }
        });
      },
      deleteRole(role) {
        let self = this;
        if (!confirm(self.data.translations.confirmRoleDelete)) {
          return;
        }

        let formData = new FormData();
        formData.append('action', 'uip_delete_role');
        formData.append('security', uip_ajax.security);
        formData.append('role', JSON.stringify(role));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.getRoleData();
        });
      },
      bulkDeleteRoles() {
        let self = this;

        if (!confirm(self.data.translations.confirmRoleDeleteMultiple)) {
          return;
        }

        let allRoles = self.getTotalSelected;

        let formData = new FormData();
        formData.append('action', 'uip_delete_roles');
        formData.append('security', uip_ajax.security);
        formData.append('roles', JSON.stringify(allRoles));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');

          if (data.undeleted.length > 0) {
            for (var error in data.undeleted) {
              let theerror = data.undeleted[error];
              let themessage = theerror.role + '<br>' + theerror.message;
              self.uipress.notify(themessage, '', 'error');
            }
          }

          self.getRoleData();
        });
      },
      openMessenger(user) {
        this.ui.messageRecipient = user;
        this.ui.messagePanelOpen = true;
      },
      cloneRole(role) {
        let self = this;
        self.appData.cloneRole = role;
        self.$router.push('/roles/edit/new');
      },
      goTo(path) {
        let self = this;
        self.$router.push(path);
      },
    },
    template: `
      <div class="uip-margin-top-m uip-text-normal">
      
      
          <div class="uip-margin-bottom-s uip-flex uip-gap-s">
          
            <drop-down v-if="totalSelected > 0" dropPos="bottom-left">
              <template v-slot:trigger>
                <button class="uip-button-primary uip-flex uip-gap-xs uip-flex-center">
                  <span class="uip-icon">settings</span>
                  <span>{{totalSelected + ' ' + data.translations.rolesSelected}}</span>
                </button>
              </template>
              <template v-slot:content>
              
                <div class="uip-flex uip-flex-column">
                
                  <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs uip-border-bottom">
                    
                    <div class="uip-flex uip-gap-xs uip-border-round uip-cursor-pointer hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="deSelectAllUsers()">
                      <div class="uip-icon">backspace</div>
                      <div>{{data.translations.clearSelection}}</div>
                    </div>
                    
                  </div>
                  
                  <div class="uip-padding-xs">
                    <div class="uip-flex uip-gap-xs uip-border-round uip-link-danger hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="bulkDeleteRoles()">
                      <div class="uip-icon">delete</div>
                      <div>{{data.translations.deleteSelected}}</div>
                    </div>
                  </div>
                  
                </div>
                
              </template>
            </drop-down>
          
            <div class="hover:uip-background-muted uip-padding-xs uip-border-round  uip-flex uip-flex-center uip-gap-xs uip-flex-grow">
              <span class="uip-icon uip-text-l">search</span>
              <input class="uip-blank-input uip-flex-grow" :placeholder="data.translations.searchRoles" v-model="tableFilters.search" />
            </div>
            
            <uip-tooltip :message="data.translations.newRole">
              <router-link to="/roles/edit/new" class="uip-icon uip-link-default uip-no-underline uip-padding-xs hover:uip-background-muted uip-border-round uip-text-l">add_circle</router-link>
            </uip-tooltip>
              
          </div>
          
          
          
          
	  	    <table class="uip-w-100p uip-border-collapse uip-margin-bottom-s">
              <thead>
                  <tr class="uip-border-bottom" >
                      <th class="uip-text-left uip-w-28 uip-padding-xs uip-padding-top-s uip-padding-bottom-s"><input type="checkbox" class="uip-checkbox" v-model="selectAll"></th>
                      <template v-for="column in returnTableData.columns">
                        
                        <template v-if="column.active">
                        
                          
                          <th  class="uip-text-left uip-text-bold uip-text-muted uip-padding-xs uip-padding-top-s uip-padding-bottom-s uip-text-left" 
                          :class="[{'uip-hidden-small' : !column.mobile}, {'uip-text-right uip-max-w-100' : column.name == 'users' || column.name == 'granted'}]">
                            {{column.label}}
                          </th>
                        
                        </template>
                        
                      </template>
                      <th></th>
                  </tr>
              </thead>
              <tbody>
                  <template v-for="role in returnTableData.roles">
                    <tr class="hover:uip-background-muted uip-cursor-pointer" @dblclick="goTo('/roles/edit/' + role.name)">
                        <td class="uip-border-bottom uip-w-28 uip-padding-xs"><input class="uip-checkbox" type="checkbox" v-model="role.selected"></td>
                        <template v-for="column in returnTableData.columns">
                          <td class="uip-text-left uip-padding-xs uip-border-bottom" v-if="column.active" :class="{'uip-hidden-small' : !column.mobile}">
                            <div v-if="column.name == 'label'" class="uip-flex uip-flex-center uip-gap-xs uip-text-bold">
                              <router-link :to="'/roles/edit/' + role.name" class="uip-link-emphasis uip-no-underline">{{role[column.name]}}</router-link>
                            </div>
                            
                            
                            <div v-else-if="column.name == 'users'" class="uip-flex uip-flex-center uip-gap-xs uip-text-bold uip-flex-right uip-padding-xxs">
                              
                              
                              <div class="uip-flex uip-gap-xxs uip-flex-center">
                                                  
                                                  
                                <div v-if="role[column.name].length > 0" class="uip-flex uip-flex-reverse uip-margin-left-xxs">
                                  <template v-for="(user,index) in role[column.name]">
                                    <div class="uip-w-20 uip-ratio-1-1 uip-text-s uip-background-primary-wash uip-border-circle uip-border-match uip-text-capitalize 
                                    uip-text-center uip-line-height-1 uip-text-center uip-flex uip-flex-center uip-flex-middle uip-margin-left--8">
                                      <uip-tooltip :message="user" :delay="50">
                                        <span>{{user[0]}}</span>
                                      </uip-tooltip>
                                    </div>
                                  </template>
                                </div>
                                
                                <div v-if="role.usersCount > role.users.length" class="uip-text-muted uip-text-s">+{{role.usersCount - role.users.length}} {{appData.translations.others}}</div>
                                
                                <div v-else-if="role.users.length == 0" class="uip-text-muted uip-text-s">{{appData.translations.noUsers}}</div>
                                
                                
                              </div>
                              
                              
                            </div>
                            
                            <div v-else-if="column.name == 'granted'" class="uip-flex uip-flex-center uip-gap-xs uip-text-bold uip-flex-right">
                              <span class="uip-background-primary-wash uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-bold">{{role[column.name]}}</span>
                            </div>
                            
                            
                            <div v-else-if="column.name == 'roles'" class="uip-flex uip-flex-wrap uip-gap-xs uip-row-gap-xxs">
                              <div v-for="role in role.roles" class="uip-background-primary-wash uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-bold uip-text-s">
                                {{role}}
                              </div>
                            </div>
                            <div v-else>{{role[column.name]}}</div>
                          </td>
                        </template>
                        <td class="uip-border-bottom">
                          <div class="uip-flex uip-flex-right">
                          
                            
                            <drop-down dropPos="left">
                              <template v-slot:trigger>
                                <div class="uip-icon uip-link-default uip-padding-xs hover:uip-background-grey uip-border-round uip-text-l">more_horiz</div>
                              </template>
                              <template v-slot:content>
                              
                                <div class="uip-flex uip-flex-column">
                                  <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs uip-border-bottom">
                                  
                                    <router-link :to="'/roles/edit/' + role.name"
                                     class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-no-underline hover:uip-background-muted uip-padding-xxs uip-flex-center">
                                      <div class="uip-icon">edit</div>
                                      <div>{{data.translations.edit}}</div>
                                    </router-link>
                                    
                                    <div class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-no-underline hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="cloneRole(role)">
                                      <div class="uip-icon">content_copy</div>
                                      <div>{{data.translations.clone}}</div>
                                    </div>
                                    
                                  </div>
                                  
                                  <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs">
                                    <div class="uip-flex uip-gap-xs uip-border-round uip-link-danger uip-no-underline hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="deleteRole(role)">
                                      <div class="uip-icon">delete</div>
                                      <div>{{data.translations.delete}}</div>
                                    </div>
                                  </div>
                                  
                                </div>
                                
                              </template>
                            </drop-down>
                            
                            
                            
                          </div>
                        </td>
                    </tr>
                  </template>
              </tbody>
          </table>
	    </div>
      
      `,
  };
  return compData;
}
