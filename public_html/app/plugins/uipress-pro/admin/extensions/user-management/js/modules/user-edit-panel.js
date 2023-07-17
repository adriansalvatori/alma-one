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
      updateUser() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_update_user');
        formData.append('security', uip_ajax.security);
        formData.append('user', JSON.stringify(self.user.editData));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.appData.refreshTable();
        });
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
      chooseImage() {
        self = this;
        uipMediaUploader = wp.media.frames.file_frame = wp.media({
          title: self.translations.chooseImage,
          button: {
            text: self.translations.chooseImage,
          },
          multiple: false,
        });
        uipMediaUploader.on('select', function () {
          var attachment = uipMediaUploader.state().get('selection').first().toJSON();
          self.user.editData.uip_profile_image = attachment.url;
        });
        uipMediaUploader.open();
      },
    },
    template: ` 
      <floating-panel closeRoute="/" v-if="userFetched">
        <!-- EDITING USER -->
        <div class="uip-flex uip-flex-grow uip-flex-column uip-max-h-100p" >
        
          <div class="uip-padding-m uip-border-bottom uip-flex uip-flex-column uip-row-gap-xs" >
            <div class="uip-text-bold uip-text-xl">{{panelData.user.username}}</div>
            <router-link :to="'/users/' + userID" class="uip-link-muted uip-no-underline">{{translations.viewUserProfile}}</router-link>
          </div>
          
          <div class="uip-flex-grow uip-padding-m uip-border-bottom uip-overflow-auto">
        
            <div class="uip-flex uip-flex-column uip-row-gap-m">
              <div class="uip-w-50p">
                <div class="uip-margin-bottom-xs">{{translations.profileImage}}</div>
                
                
                 <div class="">
                   <div class="uip-background-muted uip-border-round uip-overflow-hidden uip-margin-top-s">
                     <div v-if="user.editData.uip_profile_image" class="uip-background-grey uip-flex uip-flex-center uip-flex-middle uip-position-relative uip-scale-in-center">
                       <img class="uip-max-h-120" :src="user.editData.uip_profile_image">
                     </div>
                     <div class="uip-flex uip-flex-column uip-padding-xs uip-padding-bottom-remove uip-row-gap-xs">
                       <div class="uip-flex uip-flex-column uip-flex-start uip-scale-in-center">
                         <input class="uip-blank-input uip-no-wrap uip-text-ellipsis uip-overflow-hidden uip-w-100p" v-model="user.editData.uip_profile_image" :placeholder="translations.imageURL">
                       </div>
                     </div>
                     <div class="uip-flex uip-flex-row uip-flex-between uip-flex-center uip-padding-xs">
                       <div class="uip-flex uip-flex-row uip-gap-xs">
                         <div class="uip-position-relative">
                           <div class="uip-flex">
                             <div class="uip-cursor-pointer" @click="chooseImage()">
                               <div v-if="user.editData.uip_profile_image" class="uip-link-default uip-text-bold">{{translations.changeImage}}</div>
                               <div v-else class="uip-link-default uip-text-bold">{{translations.chooseImage}}</div>
                             </div>
                           </div>
                         </div>
                       </div>
                       <div v-if="user.editData.uip_profile_image" @click="user.editData.uip_profile_image = ''" class="uip-icon uip-text-l uip-icon-medium uip-link-danger">delete</div>
                     </div>
                   </div>
                 </div>
                
              </div>
              <div class="uip-grid-col-2 uip-grid-gap-m">
                <div class="">
                  <div class="uip-margin-bottom-xs">{{translations.firstName}}</div>
                  <input type="text" class="uip-input uip-w-100p" v-model="user.editData.first_name">
                </div>
                <div class="">
                  <div class="uip-margin-bottom-xs">{{translations.lastName}}</div>
                  <input type="text" class="uip-input uip-w-100p"  v-model="user.editData.last_name">
                </div>
              </div>
              <div>
                <div class="uip-margin-bottom-xs">{{translations.email}}</div>
                <input type="text" class="uip-input uip-w-100p"  v-model="user.editData.user_email">
              </div>
              <div class="uip-grid-col-2 uip-grid-gap-m">
                <div class="">
                  <div class="uip-margin-bottom-xs">{{translations.roles}}</div>
                  <role-select :selected="user.editData.roles"
                  :name="translations.assignRoles"
                  :translations="translations"
                  :single='false'
                  :placeholder="translations.searchRoles"
                  :updateRoles="returnRoles"></role-select>
                </div>
              </div>
              <div>
                <div class="uip-margin-bottom-xs">{{translations.userNotes}}</div>
                <textarea type="text" class="uip-input uip-w-100p" rows="10" v-model="user.editData.notes"></textarea>
              </div>
              
            </div>
          
          </div>
          
          <div class="uip-padding-m">
            <div class="uip-flex uip-flex-between">
              <router-link :to="'/'" class="uip-button-default uip-no-underline">{{translations.cancel}}</router-link>
              <button class="uip-button-primary" @click="updateUser()">{{translations.updateUser}}</button>
            </div>
          </div>
          
        </div>
      </floating-panel>`,
  };
}
