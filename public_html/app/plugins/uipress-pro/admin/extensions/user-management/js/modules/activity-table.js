export function moduleData() {
  return {
    props: {
      data: Object,
      dataChange: Function,
    },
    inject: ['uipress'],
    data: function () {
      return {
        loading: true,
        modData: this.data,
        tableData: {
          totalPages: 1,
        },
        tablePage: 1,
        selectAll: false,
        queryRunning: false,
        sizeWarning: {
          dismissed: false,
        },
        tableFilters: {
          search: '',
          roles: [],
          status: [],
          dateCreated: {
            type: 'on',
            date: '',
          },
          action: '',
        },
        tableOptions: {
          direction: 'DESC',
          sortBy: 'username',
          perPage: 20,
        },
        ui: {
          userPanelOpen: false,
          messagePanelOpen: false,
          activeUser: 0,
          messageRecipient: [],
          newUserOpen: false,
          offcanvas: {
            userPanelOpen: false,
            messagePanelOpen: false,
          },
        },
      };
    },
    mounted: function () {
      this.getActivityData();
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
          this.getActivityData();
        },
        deep: true,
      },
      tableOptions: {
        handler(newValue, oldValue) {
          this.getActivityData();
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
        for (var action in self.returnTableData.activity) {
          if (self.returnTableData.activity[action].selected && self.returnTableData.activity[action].selected == true) {
            count += 1;
          }
        }
        return count;
      },
      getTotalSelected() {
        let self = this;
        let selected = [];
        for (var action in self.returnTableData.activity) {
          if (self.returnTableData.activity[action].selected && self.returnTableData.activity[action].selected == true) {
            selected.push(self.returnTableData.activity[action].id);
          }
        }
        return selected;
      },
      getTotalSelectedUsers() {
        let self = this;
        let selected = [];
        for (var action in self.returnTableData.activity) {
          if (self.returnTableData.activity[action].selected && self.returnTableData.activity[action].selected == true) {
            selected.push(self.returnTableData.activity[action]);
          }
        }
        return selected;
      },
    },
    methods: {
      deleteMultiple() {
        let self = this;
        if (!confirm(self.data.translations.confirmUserDeleteMultipleActions)) {
          return;
        }

        let allIDS = self.getTotalSelected;

        let formData = new FormData();
        formData.append('action', 'uip_delete_multiple_actions');
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

          self.getActivityData();

          if (data.undeleted.length > 0) {
            for (var error in data.undeleted) {
              let theerror = data.undeleted[error];
              let themessage = theerror.user + '<br>' + theerror.message;
              self.uipress.notify(themessage, '', 'error');
            }
          }
        });
      },
      deleteAllHistory() {
        let self = this;
        if (!confirm(self.data.translations.confirmUserDeleteAllHistory)) {
          return;
        }

        self.loading = true;

        let formData = new FormData();
        formData.append('action', 'uip_delete_all_history');
        formData.append('security', uip_ajax.security);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;
          self.loading = false;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.deSelectAllUsers();

          self.getActivityData();

          if (data.undeleted.length > 0) {
            for (var error in data.undeleted) {
              let theerror = data.undeleted[error];
              let themessage = theerror.user + '<br>' + theerror.message;
              self.uipress.notify(themessage, '', 'error');
            }
          }
        });
      },
      selectAllUsers() {
        let self = this;
        for (var action in self.returnTableData.activity) {
          self.returnTableData.activity[action].selected = true;
        }
        self.selectAll = true;
      },
      deSelectAllUsers() {
        let self = this;
        for (var action in self.returnTableData.activity) {
          self.returnTableData.activity[action].selected = false;
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
        this.getActivityData();
      },
      updateRoles(roles) {
        this.tableFilters.roles = roles;
      },
      getActivityData() {
        let self = this;

        if (self.queryRunning) {
          return;
        }

        self.loading = true;
        self.queryRunning = true;

        let allIDS = self.getTotalSelected;

        let formData = new FormData();
        formData.append('action', 'uip_get_activity_table_data');
        formData.append('security', uip_ajax.security);
        formData.append('tablePage', self.tablePage);
        formData.append('filters', JSON.stringify(self.tableFilters));
        formData.append('options', JSON.stringify(self.tableOptions));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;
          self.queryRunning = false;
          self.loading = false;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }

          let columns = [];
          if (self.tableData.columns && self.tableData.columns.length > 0) {
            columns = self.tableData.columns;
          }
          self.tableData.totalPages = data.tableData.totalPages;
          self.tableData.activity = data.tableData.activity;
          self.tableData.totalFound = data.tableData.totalFound;
          self.tableData.columns = data.tableData.columns;
          self.tableData.actions = data.tableData.actions;

          if (columns.length > 0) {
            self.tableData.columns = columns;
          }

          if (self.tablePage > data.tableData.totalPages) {
            self.tablePage = data.tableData.totalPages;
          }

          if (self.tablePage < 1) {
            self.tablePage = 1;
          }
        });
      },
      returnActionClass(type) {
        if (type == 'primary') {
          return 'uip-background-primary-wash';
        }
        if (type == 'warning') {
          return 'uip-background-orange-wash';
        }
        if (type == 'danger') {
          return 'uip-background-red-wash';
        }

        return 'uip-background-primary-wash';
      },
      openUser(id) {
        this.ui.activeUser = id;
        this.ui.offcanvas.userPanelOpen = true;
      },
      openMessenger(user) {
        this.ui.messageRecipient = user;
        this.ui.offcanvas.messagePanelOpen = true;
      },
    },

    template: `
      <div class="uip-margin-top-m uip-text-normal">
      
          <div v-if="sizeWarning.dismissed != true && returnTableData.totalFound > 20000" class="uip-margin-bottom-m uip-border-round uip-background-orange-wash uip-padding-s uip-flex uip-flex-column uip-row-gap-s">
            <div class="uip-text-emphasis uip-text-l uip-text-bold">{{data.translations.historylogLarge}}</div>
            <div class="uip-text-muted">{{data.translations.logSizeWarning}}</div>
            <div class="uip-flex uip-gap-s">
              <button @click="sizeWarning.dismissed = true" class="uip-button-primary uip-text-s">{{data.translations.dismiss}}</button>
              <button @click="deleteAllHistory()" class="uip-button-danger uip-text-s">{{data.translations.deleteAllActivity}}</button>
            </div>
          </div>
          
          
          
          <!--Filters-->
          <div class="uip-flex uip-gap-s uip-flex-wrap uip-row-gap-s uip-margin-bottom-s">
            
            <div class="uip-position-relative" v-if="totalSelected > 0">
              <drop-down dropPos="bottom-left">
              
                <template v-slot:trigger>
                  <button class="uip-button-primary uip-flex uip-gap-xs uip-flex-center">
                    <span class="uip-icon">settings</span>
                    <span>{{totalSelected + ' ' + data.translations.actionsSelected}}</span>
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
                      <div class="uip-flex uip-gap-xs uip-border-round uip-link-danger hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="deleteMultiple()">
                        <div class="uip-icon">delete</div>
                        <div>{{data.translations.deleteActions}}</div>
                      </div>
                    </div>
                    
                    
                  </div>
                </template>
                
              </drop-down>
            </div>
            
            <div class="hover:uip-background-muted uip-padding-xs uip-border-round  uip-flex uip-flex-center uip-gap-xs uip-flex-grow uip-position-relative">
              <span class="uip-icon uip-text-l">search</span>
              <input class="uip-blank-input uip-flex-grow" :placeholder="data.translations.searchHistory" v-model="tableFilters.search" />
              
              <div class="uip-ajax-loader uip-border-round uip-w-100p uip-bottom-0 uip-left-0" v-if="loading">
                <div class="uip-loader-bar"></div>
              </div>
            </div>
            
                      
            <div class="uip-w-200">
              <activity-status-select
                :selected="tableFilters.status"
                :name="data.translations.filterByStatus"
                :translations="data.translations"
                :single="false"
                :placeholder="data.translations.searchStatuses"
                :updateRoles="function(d){tableFilters.status = d}"
              ></activity-status-select>
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
              
                    <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-padding-s uip-border-bottom">
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
                    
                    <div class="uip-padding-s">
                      <button @click="deleteAllHistory()" class="uip-button-danger">{{data.translations.deleteAllActivity}}</button>
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
                      <th class="uip-text-left uip-w-28 uip-padding-xs uip-padding-bottom-s"><input type="checkbox" class="uip-checkbox" v-model="selectAll"></th>
                      <template v-for="column in returnTableData.columns">
                        <th class="uip-text-left uip-text-bold uip-text-muted uip-padding-xs uip-padding-bottom-s" v-if="column.active" :class="{'uip-hidden-small' : !column.mobile}">
                          {{column.label}}
                        </th>
                      </template>
                  </tr>
              </thead>
              <tbody>
                  <template v-for="action in returnTableData.activity">
                    <tr class="hover:uip-background-muted">
                        <td class="uip-border-bottom uip-w-28 uip-padding-xs">
                          <div class=" uip-flex uip-gap-xs uip-flex-center">
                            <input class="uip-checkbox" :data-id="action.id" type="checkbox" v-model="action.selected">
                          </div>
                        </td>
                        <template v-for="column in returnTableData.columns">
                        
                          <td class="uip-text-left uip-padding-xs uip-border-bottom" v-if="column.active" :class="{'uip-hidden-small' : !column.mobile}">
                          
                            <div v-if="column.name == 'user'" class="uip-flex uip-flex-center uip-gap-xs uip-cursor-pointer">
                              <img v-if="action.image" class="uip-w-28 uip-h-28 uip-border-circle" :src="action.image">
                              <div class="uip-flex uip-flex-column uip-row-gap-xxs">
                                <router-link :to="'/users/' + action.user_id" class="uip-text-bold uip-link-emphasis uip-no-underline">{{action[column.name]}}</router-link>
                                <div class="uip-flex uip-flex-wrap uip-gap-xs uip-row-gap-xxs">
                                  <template v-for="role in action.roles">
                                    <router-link :to="'/roles/edit/' + role" class="uip-background-primary-wash uip-link-default uip-no-underline uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-bold uip-text-s">
                                      {{role}}
                                    </router-link>
                                  </template>
                                </div>
                              </div>
                            </div>
                            
                            <div v-else-if="column.name == 'action'" class="uip-flex">
                              <div :class="returnActionClass(action.type)" class="uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-bold uip-flex uip-text-s uip-no-wrap">
                                {{action[column.name]}}
                              </div>
                            </div>
                            <div v-else-if="column.name == 'description'" class="uip-max-h-80 uip-overflow-auto">
                              <div v-html="action[column.name]"></div>
                              <div v-if="action.links" class="uip-flex uip-gap-xs">
                                <template v-for="link in action.links">
                                  <a :href="link.url" class="uip-link-muted uip-link-no-underline">{{link.name}}</a>
                                </template>
                              </div>
                            </div>
                            <div v-else>{{action[column.name]}}</div>
                          </td>
                          
                        </template>
                    </tr>
                  </template>
              </tbody>
          </table>
          <div class="uip-padding-top-xs uip-padding-bottom-xs uip-padding-right-xs uip-flex uip-flex-between">
            <div class="">{{returnTableData.totalFound}} {{data.translations.results}}</div>
            <div class="uip-flex uip-gap-xs">
              <button v-if="tablePage > 1" class="uip-button-default" @click="changePage('previous')">{{data.translations.previous}}</button>
              <button v-if="tablePage < tableData.totalPages" class="uip-button-default" @click="changePage('next')">{{data.translations.next}}</button>
            </div>
          </div>
          
	    </div>`,
  };
  return compData;
}
