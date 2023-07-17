export function moduleData() {
  return {
    props: {
      closePanel: Function,
    },
    inject: ['appData', 'uipress'],
    data: function () {
      return {
        allRecipients: this.$route.params.users.split(','),
        showAllRecipients: false,
        translations: this.appData.translations,
        settings: {
          roles: [],
          replaceExisting: false,
        },
      };
    },
    mounted: function () {
      console.log('helloo');
    },
    computed: {
      rerturnRecipients() {
        return this.allRecipients;
      },
    },
    methods: {
      removeRecipient(index) {
        this.allRecipients.splice(index, 1);
      },
      returnRoles(roles) {
        this.settings.roles = roles;
      },
      updateUsers() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_batch_update_roles');
        formData.append('security', uip_ajax.security);
        formData.append('allRecipients', JSON.stringify(self.rerturnRecipients));
        formData.append('settings', JSON.stringify(self.settings));

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
    },
    template: `
      <floating-panel closeRoute="/">
        
        <div class="uip-flex uip-flex-grow uip-flex-column uip-max-h-100p" >
          
          <div class="uip-padding-m uip-border-bottom uip-flex uip-flex-column uip-row-gap-xs" >
            <div class="uip-text-bold uip-text-xl">{{translations.updateRoles}}</div>
          </div>
      
          <div class="uip-padding-m uip-flex-grow uip-border-bottom uip-overflow-auto">
            <div class="uip-flex uip-flex-column uip-row-gap-s">
              <div class="uip-flex uip-flex-column uip-flex-start">
                <div class="uip-text-muted uip-margin-bottom-xs">{{translations.Users}}</div>
                
                
                
                <div v-if="rerturnRecipients.length > 2" class="uip-flex uip-flex-wrap uip-max-h-280 uip-overflow-auto uip-margin-left-xxs uip-flex-center uip-margin-bottom-xs uip-overflow-visible" @click="showAllRecipients = !showAllRecipients">
                  <template v-for="(item, index) in rerturnRecipients">
                    <div v-if="index < 10" class="uip-w-20 uip-ratio-1-1 uip-text-s uip-background-primary-wash uip-border-circle uip-border-match uip-text-capitalize 
                    uip-text-center uip-line-height-1 uip-text-center uip-flex uip-flex-center uip-flex-middle uip-margin-left--8">
                      <uip-tooltip :message="item" :delay="50">
                        <span class="uip-link-default">{{item[0]}}</span>
                      </uip-tooltip>  
                    </div>
                    
                    <div v-else-if="index < 11" class="uip-link-muted uip-text-s uip-margin-left-xs">+{{rerturnRecipients.length - 10}} {{translations.others}}</div>
                  </template>
                </div>
                
                
                <div v-if="showAllRecipients || rerturnRecipients.length < 3" class="uip-flex uip-flex-wrap uip-gap-xxs uip-row-gap-xxs uip-max-h-280 uip-overflow-auto">
                  <template v-for="(item, index) in rerturnRecipients">
                    <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-flex uip-gap-xxs uip-flex-center">
                      {{item}}
                      <span class="uip-icon uip-link-muted uip-cursor-icon" @click="removeRecipient(index)">cancel</span>
                    </div>
                  </template>
                </div>
                
                
                
                
              </div>
              <div class="">
                <div class="uip-text-muted uip-margin-bottom-xs">{{translations.roles}}</div>
                
                <role-select :selected="settings.roles"
                :name="translations.assignRoles"
                :translations="translations"
                :single='false'
                :placeholder="translations.searchRoles"
                :updateRoles="returnRoles"></role-select>
              </div>
              <label class="uip-flex uip-flex-row uip-gap-xs uip-flex-center">
              
                <input type="checkbox" class="uip-checkbox uip-w-100p uip-margin-remove" v-model="settings.replaceExisting">
                <div class="uip-text-muted ">{{translations.replaceExistingRoles}}</div>
                
              </label>
            </div>
          </div>
          
          <div class="uip-padding-m uip-flex uip-flex-between">
          
            <router-link to="/" class="uip-button-default uip-no-underline">{{translations.cancel}}</router-link>
            <button class="uip-button-primary uip-flex uip-gap-xxs uip-flex-center" @click="updateUsers()">
              <span>{{translations.updateRoles}}</span>
              <span class="uip-icon">bookmarks</span>
            </button>
          </div>
        
        </div>
      
      </floating-panel>
      `,
  };
}
