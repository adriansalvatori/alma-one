export function moduleData() {
  return {
    props: {
      recipient: Object,
      closePanel: Function,
    },
    inject: ['appData', 'uipress'],
    data: function () {
      return {
        quillEditor: '',
        allRecipients: this.$route.params.recipients.split(','),
        showAllRecipients: false,
        translations: this.appData.translations,
        message: {
          recipient: this.recipient,
          subject: '',
          message: '',
          replyTo: '',
        },
      };
    },
    mounted: function () {
      this.startEditor();
    },
    computed: {
      rerturnRecipients() {
        return this.allRecipients;
      },
    },
    methods: {
      startEditor() {
        let self = this;
        let container = self.$refs.uipeditor;

        self.quillEditor = new Quill(container, {
          theme: 'snow',
        });
      },
      removeRecipient(index) {
        this.allRecipients.splice(index, 1);
      },
      sendMessage() {
        let self = this;
        self.message.message = self.quillEditor.root.innerHTML;

        let formData = new FormData();
        formData.append('action', 'uip_send_message');
        formData.append('security', uip_ajax.security);
        formData.append('allRecipients', JSON.stringify(self.rerturnRecipients));
        formData.append('message', JSON.stringify(self.message));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          let data = response;

          if (data.error) {
            self.uipress.notify(data.message, '', 'error');
            return;
          }
          self.uipress.notify(data.message, '', 'success');
          self.$router.push('/');
        });
      },
    },
    template: `
    
    <floating-panel closeRoute="/">
    
      <div class="uip-flex uip-flex-grow uip-flex-column uip-max-h-100p" >
      
        <div class="uip-padding-m uip-border-bottom uip-flex uip-flex-column uip-row-gap-xs" >
          <div class="uip-text-bold uip-text-xl">{{translations.newMessage}}</div>
        </div>
        
        <div class="uip-padding-m uip-flex-grow uip-border-bottom uip-overflow-auto">
          
          <div class="uip-flex uip-flex-column uip-row-gap-s">
            <div class="uip-flex uip-flex-column uip-flex-start">
              <div class="uip-text-muted uip-margin-bottom-xs">{{translations.recipients}}</div>
              
              
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
              <div class="uip-text-muted uip-margin-bottom-xs">{{translations.replyTo}}</div>
              <input type="email" class="uip-input uip-w-100p" v-model="message.replyTo">
            </div>
            <div class="">
              <div class="uip-text-muted uip-margin-bottom-xs">{{translations.subject}}</div>
              <input type="email" class="uip-input uip-w-100p" v-model="message.subject">
            </div>
            <div class="">
              <div class="uip-text-muted uip-margin-bottom-xs">{{translations.message}}</div>
              <div ref="uipeditor"></div>
            </div>
            
          </div>
        </div>
        
        <div class="uip-padding-m uip-flex uip-flex-between">
        
          <router-link to="/" class="uip-button-default uip-no-underline">{{translations.cancel}}</router-link>
          <button class="uip-button-primary uip-flex uip-gap-xs uip-flex-center uip-flex-center" @click="sendMessage()">
          
            <span class="uip-icon uip-text-">send</span>
            <span>{{translations.sendMessage}}</span>
            
          </button>
        </div>
      
      </div>
    </floating-panel>
      `,
  };
}
