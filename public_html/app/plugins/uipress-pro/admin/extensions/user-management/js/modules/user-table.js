export function moduleData() {
  return {
    props: {
      data: Object,
      dataChange: Function,
    },
    provide() {
      return {
        refreshTable: this.getUserData,
      };
    },
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
          activeGroup: 'all',
        },
        tableOptions: {
          direction: 'ASC',
          sortBy: 'username',
          perPage: 20,
        },
        ui: {
          offcanvas: {
            userPanelOpen: false,
            messagePanelOpen: false,
            newUserOpen: false,
            batchRolePanelOpen: false,
          },
          activeUser: 0,
          groupsOpen: false,
          messageRecipient: [],
          batchMessageRecipient: [],
          groups: [],
        },
      };
    },
    inject: ['uipress'],

    mounted: function () {
      this.loading = false;
      this.getUserData();
      this.data.refreshTable = this.getUserData;
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
          this.getUserData();
        },
        deep: true,
      },
      tableOptions: {
        handler(newValue, oldValue) {
          this.getUserData();
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
        for (var user in self.tableData.users) {
          if (self.tableData.users[user].selected && self.tableData.users[user].selected == true) {
            count += 1;
          }
        }
        return count;
      },
      getTotalSelected() {
        let self = this;
        let selected = [];
        for (var user in self.tableData.users) {
          if (self.tableData.users[user].selected && self.tableData.users[user].selected == true) {
            selected.push(self.tableData.users[user].user_id);
          }
        }
        return selected;
      },
      getTotalSelectedUsers() {
        let self = this;
        let selected = [];
        for (var user in self.tableData.users) {
          if (self.tableData.users[user].selected && self.tableData.users[user].selected == true) {
            selected.push(self.tableData.users[user]);
          }
        }
        return selected;
      },
      returnUsersAsString() {
        let self = this;
        let selected = [];
        for (var user in self.tableData.users) {
          if (self.tableData.users[user].selected && self.tableData.users[user].selected == true) {
            selected.push(self.tableData.users[user].user_email);
          }
        }
        return selected.join(',');
      },
    },
    methods: {
      selectAllUsers() {
        let self = this;
        for (var user in self.tableData.users) {
          self.tableData.users[user].selected = true;
        }
        self.selectAll = true;
      },
      deSelectAllUsers() {
        let self = this;
        for (var user in self.tableData.users) {
          self.tableData.users[user].selected = false;
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
        this.getUserData();
      },
      updateRoles(roles) {
        this.tableFilters.roles = roles;
      },
      getUserData() {
        let self = this;

        if (self.queryRunning) {
          return;
        }
        self.queryRunning = true;

        let formData = new FormData();
        formData.append('action', 'uip_get_user_table_data');
        formData.append('security', uip_ajax.security);
        formData.append('tablePage', self.tablePage);
        formData.append('filters', JSON.stringify(self.tableFilters));
        formData.append('options', JSON.stringify(self.tableOptions));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;
          self.queryRunning = false;

          let columns = [];
          if (self.tableData.columns && self.tableData.columns.length > 0) {
            columns = self.tableData.columns;
          }

          self.tableData = data.tableData;
          if (columns.length > 0) {
            self.tableData.columns = columns;
          }
          self.selectAll = false;
        });
      },
      sendPasswordReset(user) {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_reset_password');
        formData.append('security', uip_ajax.security);
        formData.append('userID', user.user_id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
        });
      },
      deleteUser(userID) {
        let self = this;
        if (!confirm(self.data.translations.confirmUserDelete)) {
          return;
        }

        let formData = new FormData();
        formData.append('action', 'uip_delete_user');
        formData.append('security', uip_ajax.security);
        formData.append('userID', userID);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.getUserData();
        });
      },
      deleteMultiple() {
        let self = this;
        if (!confirm(self.data.translations.confirmUserDeleteMultiple)) {
          return;
        }

        let allIDS = self.getTotalSelected;

        let formData = new FormData();
        formData.append('action', 'uip_delete_multiple_users');
        formData.append('security', uip_ajax.security);
        formData.append('allIDS', JSON.stringify(allIDS));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.deSelectAllUsers();

          self.getUserData();

          if (data.undeleted.length > 0) {
            for (var error in data.undeleted) {
              let theerror = data.undeleted[error];
              let themessage = theerror.user + '<br>' + theerror.message;
              self.uipress.notify(themessage, '', 'error');
            }
          }
        });
      },
      sendMultiplePasswordReset() {
        let self = this;
        if (!confirm(self.data.translations.confirmUserPassReset)) {
          return;
        }

        let allIDS = self.getTotalSelected;

        let formData = new FormData();
        formData.append('action', 'uip_password_reset_multiple');
        formData.append('security', uip_ajax.security);
        formData.append('allIDS', JSON.stringify(allIDS));

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
              let themessage = theerror.user + '<br>' + theerror.message;
              self.uipress.notify(themessage, '', 'error');
            }
          }
          self.deSelectAllUsers();
        });
      },
      openMessenger(user) {
        this.ui.messageRecipient = user;
        this.ui.offcanvas.messagePanelOpen = true;
      },
      openMessengerBatch() {
        this.ui.offcanvas.messagePanelOpen = true;
      },
      batchRoleUpdate() {
        this.ui.offcanvas.batchRolePanelOpen = true;
      },
      getActiveGroup(groupID) {
        this.tableFilters.activeGroup = groupID;
      },
      returnGroupTag(group) {
        if (this.returnTableData.groups[group]) {
          return this.returnTableData.groups[group].title;
        }
      },
      returnGroupColour(group) {
        if (this.returnTableData.groups[group]) {
          return 'background:' + this.returnTableData.groups[group].color;
        }
      },
      viewUser(id) {
        let self = this;
        self.$router.push('/users/' + id + '/');
      },
      editUser(id) {
        let self = this;
        self.$router.push('/users/' + id + '/edit');
      },
      newUser() {
        let self = this;
        self.$router.push('/users/new');
      },
    },
    template: ` 
      <div class="uip-margin-top-m uip-text-normal uip-flex uip-flex-column">
      
        <div v-if="ui.groupsOpen" class=" uip-flex-no-shrink uip-w-100p">
          <div class="uip-margin-bottom-s uip-text-bold uip-background-muted uip-padding-xs uip-border-round uip-flex uip-flex-center uip-text-bold">{{data.translations.groups}}</div>
          <user-groups :currentGroup="tableFilters.activeGroup" :updateuserdata="getUserData" :appdata="data" :updateactivegroup="getActiveGroup"></user-groups>
        </div>

        
        
        <!--Filters-->
        <div class="uip-flex uip-gap-s uip-flex-wrap uip-row-gap-s uip-margin-bottom-s">
        
          <tooltip v-if="1==2" :tooltiptext="data.translations.userGroups">
            <div @click="ui.groupsOpen = !ui.groupsOpen" class="uip-background-muted uip-padding-xs uip-border-round hover:uip-background-grey uip-flex uip-flex-center uip-cursor-pointer">
              <span class="uip-icon uk-form-icon">group</span>
            </div>
          </tooltip>
          
          <div class="uip-position-relative" v-if="totalSelected > 0">
            <drop-down dropPos="bottom-left">
            
              <template v-slot:trigger>
                <button class="uip-button-primary uip-flex uip-gap-xs uip-flex-center">
                  <span class="uip-icon">settings</span>
                  <span>{{totalSelected + ' ' + data.translations.usersSelected}}</span>
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
                
                  <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs uip-border-bottom">
                
                    <router-link :to="'/message/' + returnUsersAsString" class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-no-underline hover:uip-background-muted uip-padding-xxs uip-flex-center">
                      <div class="uip-icon">mail</div>
                      <div>{{data.translations.sendMessage}}</div>
                    </router-link>
                    <router-link :to="'/batch/roles/' + returnUsersAsString" class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-no-underline hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="batchRoleUpdate()">
                      <div class="uip-icon">bookmarks</div>
                      <div>{{data.translations.assignRoles}}</div>
                    </router-link>
                    <div class="uip-flex uip-gap-xs uip-border-round uip-cursor-pointer hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="sendMultiplePasswordReset()">
                      <div class="uip-icon">lock</div>
                      <div>{{data.translations.sendPasswordReset}}</div>
                    </div>
                  
                  </div>
                  
                  <div class="uip-padding-xs">
                    <div class="uip-flex uip-gap-xs uip-border-round uip-link-danger hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="deleteMultiple()">
                      <div class="uip-icon">delete</div>
                      <div>{{data.translations.deleteUsers}}</div>
                    </div>
                  </div>
                  
                  
                </div>
              </template>
              
            </drop-down>
          </div>
          
          <div class="hover:uip-background-muted uip-padding-xs uip-border-round  uip-flex uip-flex-center uip-gap-xs uip-flex-grow">
            <span class="uip-icon uip-text-l">search</span>
            <input class="uip-blank-input uip-flex-grow" :placeholder="data.translations.searchUsers" v-model="tableFilters.search" />
          </div>
          
          
          
          <div class="uip-w-200">
            <role-select
              :selected="tableFilters.roles"
              :name="data.translations.filterByRole"
              :translations="data.translations"
              :single="false"
              :placeholder="data.translations.searchRoles"
              :updateRoles="updateRoles"
            ></role-select>
          </div>
          
          
          <div class="uip-flex uip-gap-xxs">
            <!-- DATE FILTERS -->
            <div class="uip-position-relative">
              <drop-down dropPos="left">
                <template v-slot:trigger>
                  <div class="uip-icon uip-link-default uip-padding-xs hover:uip-background-muted uip-border-round uip-text-l">calendar_today</div>
                </template>
                <template v-slot:content>
                  <div class="uip-padding-s uip-flex uip-flex-column uip-row-gap-s uip-flex-start">
                    <div class="uip-text-muted uip-text-bold">{{data.translations.dateCreated}}</div>
                    <div class="uip-flex uip-gap-xs uip-background-muted uip-border-round uip-padding-xxs">
                      <div
                        class="uip-padding-xxs uip-border-round uip-cursor-pointer"
                        @click="tableFilters.dateCreated.type = 'on'"
                        :class="{'uip-background-default uip-text-bold' : tableFilters.dateCreated.type == 'on'}"
                      >
                        {{data.translations.on}}
                      </div>
                      <div
                        class="uip-padding-xxs uip-border-round uip-cursor-pointer"
                        @click="tableFilters.dateCreated.type = 'after'"
                        :class="{'uip-background-default uip-text-bold' : tableFilters.dateCreated.type == 'after'}"
                      >
                        {{data.translations.after}}
                      </div>
                      <div
                        class="uip-padding-xxs uip-border-round uip-cursor-pointer"
                        @click="tableFilters.dateCreated.type = 'before'"
                        :class="{'uip-background-default uip-text-bold' : tableFilters.dateCreated.type == 'before'}"
                      >
                        {{data.translations.before}}
                      </div>
                    </div>
                    <div>
                      <input class="uip-input" type="date" v-model="tableFilters.dateCreated.date" />
                    </div>
                  </div>
                </template>
              </drop-down>
              
              <!-- DATE FILTERS NUMBER DISPLAY -->
              <span v-if="tableFilters.dateCreated.date != ''"
              class="uip-text-inverse uip-background-primary uip-border-round uip-text-s uip-w-18 uip-margin-left-xxs uip-text-center uip-position-absolute uip-right--8 uip-top--8">1</span>
            </div>
            
            <div class="uip-icon uip-link-default uip-padding-xs hover:uip-background-muted uip-border-round uip-text-l" @click="newUser()">person_add</div>
            
            
            
            <drop-down dropPos="left">
              <template v-slot:trigger>
                <div class="uip-icon uip-link-default uip-padding-xs hover:uip-background-muted uip-border-round uip-text-l">tune</div>
              </template>
              <template v-slot:content>
                <div class="uip-flex uip-flex-column">
                  <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-padding-s uip-border-bottom">
                    <div class="uip-flex uip-flex-between uip-flex-center uip-padding-xxs uip-gap-l">
                      <div class="uip-flex uip-flex-center uip-gap-xs">
                        <span class="uip-icon uip-text-l">swap_vert</span>
                        <span>{{data.translations.order}}</span>
                      </div>
                      <div>
                        <select v-model="tableOptions.direction" class="uip-input">
                          <option value="ASC">{{data.translations.ascending}}</option>
                          <option value="DESC">{{data.translations.descending}}</option>
                        </select>
                      </div>
                    </div>
            
                    <div class="uip-flex uip-flex-between uip-flex-center uip-padding-xxs uip-gap-l">
                      <div class="uip-flex uip-flex-center uip-gap-xs">
                        <span class="uip-icon uip-text-l">sort</span>
                        <span>{{data.translations.sortBy}}</span>
                      </div>
                      <div>
                        <select v-model="tableOptions.sortBy" class="uip-input">
                          <template v-for="column in returnTableData.columns">
                            <option :value="column.name">{{column.label}}</option>
                          </template>
                        </select>
                      </div>
                    </div>
            
                    <div class="uip-flex uip-flex-between uip-flex-center uip-padding-xxs uip-gap-l">
                      <div class="uip-flex uip-flex-center uip-gap-xs">
                        <span class="uip-icon uip-text-l">format_list_numbered</span>
                        <span>{{data.translations.perPage}}</span>
                      </div>
                      <div>
                        <input type="number" min="1" max="2000" class="uip-input" v-model="tableOptions.perPage" />
                      </div>
                    </div>
                  </div>
            
                  <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-padding-s">
                    <div class="uip-text-muted uip-text-bold uip-padding-xxs">{{data.translations.fields}}</div>
                    <div class="">
                      <div
                        v-for="column in returnTableData.columns"
                        class="uip-flex uip-flex-between uip-flex-center uip-padding-xxs uip-border-round hover:uip-background-muted uip-cursor-pointer"
                        @click="column.active = !column.active"
                      >
                        <div class="">{{column.label}}</div>
                        <input type="checkbox" class="uip-checkbox" v-model="column.active" />
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </drop-down>
          
          </div>
          
        </div>
        <!--End Filters-->
          

          <table class="uip-w-100p uip-border-collapse uip-margin-bottom-s">
            <thead>
              <tr class="uip-border-bottom">
                <th class="uip-text-left uip-w-28 uip-padding-xs uip-padding-bottom-s"><input type="checkbox" class="uip-checkbox" v-model="selectAll" /></th>
                <template v-for="column in returnTableData.columns">
                  <th class="uip-text-left uip-text-weight-normal uip-text-muted uip-padding-xs uip-padding-bottom-s" :class="{'uip-hidden-small' : !column.mobile}" v-if="column.active">{{column.label}}</th>
                </template>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <template v-for="user in returnTableData.users">
                <tr class="hover:uip-background-muted uip-cursor-pointer" @dblclick="viewUser(user.user_id)">
                  <td class="uip-border-bottom uip-w-28 uip-padding-xs">
                    <div class=" uip-flex uip-gap-xs uip-flex-center">
                      <input class="uip-user-check uip-checkbox" :data-id="user.user_id" type="checkbox" v-model="user.selected" />
                      <div class="uip-flex uip-w-28"><span class="uip-icon uip-cursor-drag uip-user-drag" :data-id="user.user_id" draggable="true">drag_indicator</span></div>
                    </div>
                  </td>
                  <template v-for="column in returnTableData.columns">
                    <td class="uip-text-left uip-padding-xs uip-border-bottom" v-if="column.active" :class="{'uip-hidden-small' : !column.mobile}">
                      <div v-if="column.name == 'username'" class="uip-flex uip-flex-center uip-gap-xs">
                        <div class="uip-padding-xxs uip-flex uip-flex-center">
                          <img v-if="user.image" class="uip-w-24 uip-ratio-1-1 uip-border-circle" :src="user.image" />
                        </div>
                        <span class="uip-text-bold">{{user[column.name]}}</span>
                      </div>
                      <div v-else-if="column.name == 'roles'" class="uip-flex uip-flex-wrap uip-gap-xs uip-row-gap-xxs">
                        <router-link :to="'/roles/edit/' + role" v-for="role in user.roles" 
                        class="uip-background-primary-wash uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-bold uip-link-default uip-no-underline uip-text-s">
                          {{role}}
                        </router-link>
                      </div>
                      <div v-else-if="column.name == 'uip_user_group'" class="uip-flex uip-flex-wrap uip-gap-xxs uip-row-gap-xxxs">
                        <template v-for="group in user.uip_user_group">
                          <div
                            v-if="returnTableData.groups[group]"
                            class="uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-bold uip-text-s uip-text-inverse"
                            :style="returnGroupColour(group)"
                          >
                            {{returnGroupTag(group)}}
                          </div>
                        </template>
                      </div>
                      <div v-else>{{user[column.name]}}</div>
                    </td>
                  </template>
                  <td class="uip-border-bottom">
                    <div class="uip-flex">
                      <drop-down dropPos="left">
                        <template v-slot:trigger>
                          <div class="uip-icon uip-link-default uip-padding-xs hover:uip-background-grey uip-border-round uip-text-l">more_horiz</div>
                        </template>
                        <template v-slot:content>
                          <div class="uip-flex uip-flex-column">
                            <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs uip-border-bottom">
                              <div
                                class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-flex-center hover:uip-background-muted uip-padding-xxs"
                                @click="viewUser(user.user_id)">
                                <div class="uip-icon">person</div>
                                <div>{{data.translations.viewUser}}</div>
                              </div>
                              <div
                                class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-flex-center hover:uip-background-muted uip-padding-xxs"
                                @click="editUser(user.user_id)">
                                <div class="uip-icon">edit</div>
                                <div>{{data.translations.editUser}}</div>
                              </div>
                              <div class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-flex-center hover:uip-background-muted uip-padding-xxs" @click="sendPasswordReset(user)">
                                <div class="uip-icon">lock</div>
                                <div>{{data.translations.sendPasswordReset}}</div>
                              </div>
                              <router-link :to="'/message/' + user.user_email" class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-no-underline uip-flex-center hover:uip-background-muted uip-padding-xxs">
                                <div class="uip-icon">mail</div>
                                <div>{{data.translations.sendMessage}}</div>
                              </router-link>
                              
                              
                            </div>

                            <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs">
                              <div class="uip-flex uip-gap-xs uip-border-round uip-link-danger uip-flex-center hover:uip-background-muted uip-padding-xxs" @click="deleteUser(user.user_id)">
                                <div class="uip-icon">delete</div>
                                <div>{{data.translations.deleteUser}}</div>
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
          <div class="uip-padding-top-xs uip-padding-bottom-xs uip-padding-right-xs uip-flex uip-flex-between">
            <div class="">{{returnTableData.totalFound}} {{data.translations.results}}</div>
            <div class="uip-flex uip-gap-xs">
              <button v-if="tablePage > 1" class="uip-button-default" @click="changePage('previous')">{{data.translations.previous}}</button>
              <button class="uip-button-default" @click="changePage('next')">{{data.translations.next}}</button>
            </div>
          </div>
        </div>
        
        `,
  };
  return compData;
}
