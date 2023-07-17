var uipMediaUploader;
export function moduleData() {
  return {
    props: {
      refreshTable: Function,
      closePanel: Function,
      groups: Object,
    },
    inject: ['appData', 'uipress'],
    data: function () {
      return {
        translations: this.appData.translations,
        user: {
          editData: {
            username: '',
            first_name: '',
            last_name: '',
            roles: [],
            user_email: '',
            userNotes: '',
            passWord: '',
            uip_profile_image: '',
            uip_user_group: [],
          },
        },
      };
    },
    mounted: function () {},
    computed: {},
    methods: {
      updateUser() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_add_new_user');
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
          self.$router.push('/users/' + data.userID);
        });
      },
      returnRoles(roles) {
        this.user.editData.roles = roles;
      },
      returnGroups(groups) {
        this.user.editData.uip_user_group = groups;
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
    
      <floating-panel closeRoute="/">
      
        <div class="uip-flex uip-flex-grow uip-flex-column uip-max-h-100p" >
        
          <div class="uip-padding-m uip-border-bottom" >
            <div class="uip-text-bold uip-text-xl">{{translations.newUser}}</div>
          </div>
          <!-- EDITING USER -->
          <div class="uip-flex-grow uip-padding-m uip-border-bottom uip-overflow-auto" >
            
            <div class="uip-flex uip-flex-column uip-row-gap-s">
            
            
              <div class="">
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
              
              
              <div>
                <div class="uip-margin-bottom-xs">{{translations.username}}</div>
                <input type="text" class="uip-input uip-w-100p" v-model="user.editData.username">
              </div>
              <div>
                <div class="uip-margin-bottom-xs">{{translations.firstName}}</div>
                <input type="text" class="uip-input uip-w-100p" v-model="user.editData.first_name">
              </div>
              <div>
                <div class="uip-margin-bottom-xs">{{translations.lastName}}</div>
                <input type="text" class="uip-input uip-w-100p"  v-model="user.editData.last_name">
              </div>
              <div>
                <div class="uip-margin-bottom-xs">{{translations.email}}</div>
                <input type="text" class="uip-input uip-w-100p"  v-model="user.editData.user_email">
              </div>
              <div>
                <div class="uip-margin-bottom-xs">{{translations.password}}</div>
                <input type="password" class="uip-w-100p uip-input"  v-model="user.editData.password">
              </div>
              <div>
                <div class="uip-margin-bottom-xs">{{translations.roles}}</div>
                <role-select :selected="user.editData.roles"
                :name="translations.assignRoles"
                :translations="translations"
                :single='false'
                :placeholder="translations.searchRoles"
                :updateRoles="returnRoles"></role-select>
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
              <button class="uip-button-primary" @click="updateUser()">{{translations.saveUser}}</button>
            </div>
          </div>
        </div>
      
      </floating-panel>`,
  };
}
