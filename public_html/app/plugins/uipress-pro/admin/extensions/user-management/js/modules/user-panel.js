var uipMediaUploader;
export function moduleData() {
  return {
    props: {
      closePanel: Function,
      sendmessage: Function,
      groups: Object,
    },
    inject: ['appData', 'uipress'],
    data: function () {
      return {
        userID: this.$route.params.id,
        panelData: [],
        userFetched: false,
        translations: this.appData.translations,
        recentActivity: {
          page: 1,
          totalPages: 0,
        },
        user: {
          editData: [],
        },
        ui: {
          editing: false,
          activityOpen: true,
          pageViewsOpen: true,
          userNotesOpen: true,
        },
      };
    },

    mounted: function () {
      this.getUserData();
      if (!this.$route.params.id || typeof this.$route.params.id === 'undefined') {
        this.$router.push('/');
      }
    },
    computed: {},
    methods: {
      editUser() {
        this.$router.push('/users/' + this.userID + '/edit');
      },
      getUserData() {
        let self = this;

        jQuery.ajax({
          url: uip_user_app_ajax.ajax_url,
          type: 'post',
          data: {
            action: 'uip_get_user_data',
            security: uip_user_app_ajax.security,
            userID: self.userID,
            activityPage: self.recentActivity.page,
          },
          success: function (response) {
            let data = JSON.parse(response);

            if (data.error) {
              ///SOMETHING WENT WRONG
              uipNotification(data.error, { pos: 'bottom-left', status: 'danger' });
              return;
            }
            self.panelData = data;
            self.userFetched = true;
            self.recentActivity.totalPages = self.panelData.history.totalPages;
            self.user.editData = JSON.parse(JSON.stringify(self.panelData.user));
          },
        });
      },
      sendPasswordReset() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_reset_password');
        formData.append('security', uip_ajax.security);
        formData.append('userID', self.panelData.user.user_id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
        });
      },
      deleteUser() {
        let self = this;
        if (!confirm(self.translations.confirmUserDelete)) {
          return;
        }

        let formData = new FormData();
        formData.append('action', 'uip_delete_user');
        formData.append('security', uip_ajax.security);
        formData.append('userID', self.panelData.user.user_id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.appData.refreshTable();
          self.$router.push('/');
        });
      },
      logoutEverywhere() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_logout_user_everywhere');
        formData.append('security', uip_ajax.security);
        formData.append('userID', self.panelData.user.user_id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
        });
      },
      changePage(direction) {
        if (direction == 'next') {
          this.recentActivity.page += 1;
        }
        if (direction == 'previous') {
          this.recentActivity.page = this.recentActivity.page - 1;
        }
        this.getUserData();
      },
      returnViewTitle(view) {
        if (view.title) {
          return view.title;
        } else {
          return view.url;
        }
      },
      returnRoles(roles) {
        this.user.editData.roles = roles;
      },
      returnGroups(groups) {
        this.user.editData.uip_user_group = groups;
      },
      sendUserMessage() {
        let self = this;
        self.closePanel();
        self.sendmessage(self.user.editData);
      },
    },
    template: ` 
      <floating-panel closeRoute="/" v-if="userFetched">
        
        
        <div class="uip-flex uip-flex-grow uip-flex-column uip-max-h-100p">
        
        
          <div class="uip-padding-m uip-padding-top-s uip-padding-bottom-s uip-border-bottom uip-flex uip-flex-column uip-row-gap-xs" >
            
            <!-- OVERVIEW -->
            <div class="uip-flex uip-flex-center uip-gap-s">
              <div class="">
                <img :src="panelData.user.image" class="uip-border-circle uip-h-50 uip-w-50">
              </div>
              <div class="uip-flex uip-flex-column uip-row-gap-xs">
                <div class="uip-text-bold uip-text-xl">{{panelData.user.username}}</div>
                <div class="uip-flex uip-gap-xs">
                  <router-link :to="'/roles/edit/' + role" v-for="role in panelData.user.roles" 
                  class="uip-background-primary-wash uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-bold uip-link-default uip-no-underline uip-text-s">
                    {{role}}
                  </router-link>
                </div>
              </div>
            </div>
            
          </div>
          
          
          
          <div class="uip-padding-m uip-flex uip-flex-grow uip-flex-column uip-max-h-100p uip-overflow-auto">
            
            
            
            <div class="uip-flex uip-flex-center uip-margin-bottom-s uip-background-muted uip-border-rounded uip-padding-xs uip-border-round uip-flex-between">
              <div class="uip-text-m uip-text-bold uip-flex-grow">{{translations.details}}</div>
              <div>
                <drop-down dropPos="left">
                  <template v-slot:trigger>
                    <div class="uip-icon uip-link-default uip-padding-xxs hover:uip-background-grey uip-border-round uip-text-l">more_vert</div>
                  </template>
                  <template v-slot:content>
                  
                    <div class="uip-flex uip-flex-column">
                      <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs uip-border-bottom">
                        <div class="uip-flex uip-gap-xs uip-border-round uip-link-default hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="editUser()">
                          <div class="uip-icon">edit</div>
                          <div>{{translations.editUser}}</div>
                        </div>
                        <div class="uip-flex uip-gap-xs uip-border-round uip-link-default hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="sendPasswordReset()">
                          <div class="uip-icon">lock</div>
                          <div>{{translations.sendPasswordReset}}</div>
                        </div>
                        <router-link :to="'/message/' + panelData.user.user_email" class="uip-flex uip-gap-xs uip-border-round uip-link-default uip-no-underline hover:uip-background-muted uip-padding-xxs uip-flex-center">
                          <div class="uip-icon">mail</div>
                          <div>{{translations.sendMessage}}</div>
                        </router-link>
                        <div class="uip-flex uip-gap-xs uip-border-round uip-link-default hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="logoutEverywhere()">
                          <div class="uip-icon">logout</div>
                          <div>{{translations.logoutEverywhere}}</div>
                        </div>
                      </div>
                      <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs">
                        <div class="uip-flex uip-gap-xs uip-border-round uip-link-danger hover:uip-background-muted uip-padding-xxs uip-flex-center" @click="deleteUser()">
                          <div class="uip-icon">delete</div>
                          <div>{{translations.deleteUser}}</div>
                        </div>
                      </div>
                      
                    </div>
                  </template>
                </drop-down>
              </div>
            </div>
            
            
            <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-s uip-margin-bottom-s">
              <div class="uip-flex uip-gap-xs uip-flex-center">
                <div class="uip-icon uip-text-muted uip-text-l">badge</div>
                <div class="uip-flex-grow">{{translations.name}}</div>
                <div class="">{{panelData.user.name}}</div>
              </div>
              <div class="uip-flex uip-gap-xs uip-flex-center">
                <div class="uip-icon uip-text-muted uip-text-l">mail</div>
                <div class="uip-flex-grow">{{translations.email}}</div>
                <div class="">{{panelData.user.user_email}}</div>
              </div>
              <div class="uip-flex uip-gap-xs uip-flex-center">
                <div class="uip-icon uip-text-muted uip-text-l">add_circle</div>
                <div class="uip-flex-grow">{{translations.accountCreated}}</div>
                <div class="">{{panelData.user.user_registered}}</div>
              </div>
              <div class="uip-flex uip-gap-xs uip-flex-center">
                <div class="uip-icon uip-text-muted uip-text-l">login</div>
                <div class="uip-flex-grow">{{translations.lastLogin}}</div>
                <div class="">{{panelData.user.uip_last_login_date}}</div>
              </div>
              <div class="uip-flex uip-gap-xs uip-flex-center">
                <div class="uip-icon uip-text-muted uip-text-l">public</div>
                <div class="uip-flex-grow">{{translations.lastLoginCountry}}</div>
                <div class="">{{panelData.user.uip_last_login_country}}</div>
              </div>
              <div class="uip-flex uip-gap-xs uip-flex-center">
                <div class="uip-icon uip-text-muted uip-text-l">article</div>
                <div class="uip-flex-grow">{{translations.totalPosts}}</div>
                <div class="">{{panelData.user.totalPosts}}</div>
              </div>
              <div class="uip-flex uip-gap-xs uip-flex-center">
                <div class="uip-icon uip-text-muted uip-text-l">comment</div>
                <div class="uip-flex-grow">{{translations.totalComments}}</div>
                <div class="">{{panelData.user.totalComments}}</div>
              </div>
            </div>
            
            
            <!-- USER NOTES -->
            <div class="uip-flex uip-flex-middle uip-flex-center uip-flex-between uip-margin-bottom-s uip-background-muted uip-border-rounded uip-padding-xs uip-border-round" v-if="panelData.user.notes != ''">
              <div class="uip-flex uip-gap-xs">
                <div class="uip-text-m uip-text-bold uip-flex-grow">{{translations.userNotes}}</div>
              </div>
              <div @click="ui.userNotesOpen = !ui.userNotesOpen ">
                <div v-if="ui.userNotesOpen == true" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-icon uip-padding-xxs" type="button">expand_more</div>
                <div v-if="ui.userNotesOpen == false" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-icon uip-padding-xxs" type="button">chevron_left</div>
              </div>
            </div>
            <div v-if="ui.userNotesOpen == true && panelData.user.notes != ''" class="uip-padding-xs uip-flex uip-flex-column uip-margin-bottom-s">
              <div v-html="panelData.user.notes"></div>
            </div>
            
            
            <!-- RECENT PAGE VIEWS -->
            <div class="uip-flex uip-flex-middle uip-flex-center uip-flex-between uip-margin-bottom-s uip-background-muted uip-border-rounded uip-padding-xs uip-border-round">
              <div class="uip-flex uip-gap-xs">
                <div class="uip-text-m uip-text-bold uip-flex-grow">{{translations.recentPageViews}}</div>
              </div>
              <div @click="ui.pageViewsOpen = !ui.pageViewsOpen ">
                <div v-if="ui.pageViewsOpen == true" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-icon uip-padding-xxs" type="button">expand_more</div>
                <div v-if="ui.pageViewsOpen == false" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-icon uip-padding-xxs" type="button">chevron_left</div>
              </div>
            </div>
            <div v-if="ui.pageViewsOpen" class="uip-padding-xs uip-flex uip-flex-column uip-margin-bottom-s">
              <div v-if="panelData.recentPageViews.length == 0">{{translations.noActivity}}</div>
              
              <table class="uip-border-collapse">
                <tbody>
                  <tr v-for="(view, index) in panelData.recentPageViews">
                  
                    <td class="uip-no-wrap uip-text-right uip-vertical-align-top">
                      <div class="uip-text-muted  uip-text-s">{{view.human_time}}</div>
                    </td>
                    
                    
                    <td class="uip-h-12 uip-w-12 uip-no-wrap uip-padding-left-m uip-padding-right-m uip-padding-top-xs">
                      <div class="uip-h-100p uip-position-relative uip-flex uip-flex-column uip-flex-center uip-row-gap-xs">
                      
                        <div class="uip-w-4 uip-h-4 uip-border-circle uip-background-primary"></div>
                        
                        <div v-if="index != panelData.recentPageViews.length - 1" class="uip-background-muted uip-w-2 uip-flex uip-flex-middle uip-overflow-visible uip-flex-grow uip-flex-start">
                          
                        </div>
                      </div>
                    </td>
                    
                    <td class="uip-w-90p uip-padding-bottom-s uip-vertical-align-top">
                      <a class="uip-link-muted uip-text-bold uip-text-normal uip-no-underline" :href="view.url">{{returnViewTitle(view)}}</a>
                    </td>
                  
                  </tr>
                </tbody>
              </table>
            </div>
            
            
            
            <!-- Activity Log -->
            <div class="uip-flex uip-flex-column">
            
              <div @click="ui.activityOpen = !ui.activityOpen" 
              class="uip-flex uip-flex-middle uip-flex-center uip-flex-between uip-margin-bottom-s uip-background-muted uip-border-rounded uip-padding-xs uip-border-round uip-cursor-pointer hover:uip-background-grey ">
                <div class="uip-flex uip-gap-xs">
                  <div class="uip-text-m uip-text-bold uip-flex-grow">{{translations.recentActivity}}</div>
                  <div class="uip-text-muted">{{panelData.history.totalFound}}</div>
                </div>
                <div>
                  <div v-if="ui.activityOpen == true" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-icon uip-padding-xxs" type="button">expand_more</div>
                  <div v-if="ui.activityOpen == false" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-icon uip-padding-xxs" type="button">chevron_left</div>
                </div>
              </div>
              
              <div v-if="ui.activityOpen == true" class="uip-padding-xs uip-flex uip-flex-column">
                <div v-if="panelData.history.totalFound == 0">{{translations.noActivity}}</div>
                
                <table class="uip-border-collapse">
                  <tbody>
                    <tr v-for="(view, index) in panelData.history.list">
                    
                      <td class="uip-no-wrap uip-text-right uip-vertical-align-top">
                        <div class="uip-text-muted  uip-text-s">{{view.human_time}}</div>
                      </td>
                      
                      <td class="uip-h-12 uip-w-12 uip-no-wrap uip-padding-left-m uip-padding-right-m uip-padding-top-xs">
                        <div class="uip-h-100p uip-position-relative uip-flex uip-flex-column uip-flex-center uip-row-gap-xs">
                        
                          <div class="uip-w-4 uip-h-4 uip-border-circle"
                          :class="[{'uip-background-primary' : view.type == 'primary'}, {'uip-background-orange' : view.type == 'warning'}, {'uip-background-red' : view.type == 'danger'}]"></div>
                          
                          <div v-if="index != panelData.history.list.length - 1" class="uip-background-muted uip-w-2 uip-flex uip-flex-middle uip-overflow-visible uip-flex-grow uip-flex-start">
                            
                          </div>
                        </div>
                      </td>
                      
                      <td class="uip-w-90p">
                        <div class="uip-padding-bottom-xs">
                          <div class="uip-text-bold uip-text-normal uip-no-underline" >{{view.title}}</div>
                          <div class="uip-text-muted" v-html="view.meta"></div>
                          <div class="uip-flex uip-gap-xxs" >
                            <template v-for="link in view.links">
                              <a :href="link.url" class="uip-link-muted uip-link-no-underline">{{link.name}}</a>
                            </template>
                          </div>
                        </div>
                      </td>
                    
                    </tr>
                  </tbody>
                </table>
                
                
              </div>
              
              <div class="uip-flex uip-gap-xs uip-margin-top-s" v-if="recentActivity.totalPages > 1">
                <button v-if="recentActivity.page > 1" class="uip-button-default" @click="changePage('previous')">{{translations.previous}}</button>
                <button class="uip-button-default" @click="changePage('next')">{{translations.next}}</button>
              </div>
            
            </div>
            
          </div>
          
        </div>
      </floating-panel>`,
  };
}
