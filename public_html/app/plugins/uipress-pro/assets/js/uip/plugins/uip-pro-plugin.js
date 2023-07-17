const { __, _x, _n, _nx } = wp.i18n;
import * as favicon from './uip-pro-favicon.min.js?ver=309';
import * as siteTitle from './uip-pro-site-title.min.js?ver=309';
import * as googleAnalytics from './uip-google-analytics.min.js?ver=309';
import * as uipWooCommerce from './uip-woocommerce.min.js?ver=309';

export function moduleData() {
  return {
    components: {
      'uip-favicon': favicon.moduleData(),
      'uip-site-title': siteTitle.moduleData(),
      'uip-google-analytics': googleAnalytics.moduleData(),
      'uip-woocommerce': uipWooCommerce.moduleData(),
    },
    props: {},
    data: function () {
      return {
        //Registered
        rg: true,
        //Show prompt
        sp: false,
        //URL
        ul: window.uipressroute,
        userKey: '',
        validating: false,
        userData: {},
        fa: 0,
        strings: {
          thankYou: __('Thank you for installing UiPress 3 Pro!', 'uipress-pro'),
          thankYouMessage: __('For pro features to work correctly, please add your pro licence below', 'uipress-pro'),
          register: __('Register', 'uipress-pro'),
        },
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    watch: {},
    mounted: function () {
      this.getData();
    },
    computed: {},
    methods: {
      getData() {
        if (this.uiTemplate.display != 'builder') {
          this.rg = true;
          return;
        }
        let self = this;
        let formData = new FormData();
        formData.append('action', 'uip_get_pro_app_data');
        formData.append('security', uip_ajax.security);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            return;
          } else {
            if (!response.uip_pro) {
              this.rg = false;
              this.register();
            } else {
              self.userData = response.uip_pro;
              setTimeout(self.consume, 240000);
            }
          }
        });
      },
      register() {
        this.sp = true;
      },
      saveData(key, response) {
        let self = this;
        let instance = false;
        if ('instance_id' in response) {
          instance = response.instance_id;
        }

        let formData = new FormData();
        formData.append('action', 'uip_save_uip_pro_data');
        formData.append('security', uip_ajax.security);
        formData.append('key', key);
        formData.append('instance', instance);
        formData.append('multisite', self.uipData.options.multisite);
        formData.append('networkActivated', self.uipData.options.networkActivated);
        formData.append('primarySite', self.uipData.options.primarySite);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            return;
          } else {
            //console.log(response);
          }
        });
      },
      removeData() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_remove_uip_pro_data');
        formData.append('security', uip_ajax.security);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {});
      },
      consume() {
        //console.log('started checks');
        let self = this;
        let domain = this.uipData.options.domain;
        let query = '';
        query += '?k=' + this.userData.key;
        //query += '?k=' + 'EB68D251-BD15-4C30-9C';
        query += '&d=' + domain;
        query += '&instance=' + this.userData.instance;
        let url = this.ul + 'validate/' + query;
        let formData = new FormData();
        //console.log(url);

        self.uipress.callServer(url, formData).then((response) => {
          if (response instanceof Error || response.state == 'false') {
            // it's an error, unable to contact licence server, do nothing and check again later
            //It's a licence key error

            //console.log('licence key error');
            self.uipress.getUserPreference('uipfa').then((response) => {
              if (!response) {
                self.fa = 2;
              } else {
                self.fa = parseInt(response);
                if (isNaN(self.fa)) {
                  self.fa = 2;
                } else {
                  self.fa += 1;
                }
              }
              if (self.fa < 12) {
                //console.log('Failed checks: ' + self.fa);
                self.uipress.saveUserPreference('uipfa', self.fa, false);
                return;
              } else {
                //Too many failed attempts so remove the key
                self.sp = true;
                self.rg = false;
                self.uipress.saveUserPreference('uipfa', 2, false);
                self.removeData();
              }
            });
          } else if (response.state == 'true') {
            //Key is valid, say no more
            //console.log('Key is valid, do nothing more');
            self.uipress.saveUserPreference('uipfa', 2, false);
            self.sp = false;
            self.rg = true;
            return;
          } else {
            //Wait two minutes and try again
            //console.log("An error I don't know about");
            self.rg = true;
            setTimeout(self.consume, 300000);
            return;
          }
        });
      },
      onSubmit() {
        let self = this;
        this.validating = true;
        let domain = this.uipData.options.domain;
        let query = '';
        query += '?k=' + this.userKey;
        query += '&d=' + domain;
        query += '&instance=';
        let url = this.ul + 'validate/' + query;
        let formData = new FormData();

        self.uipress.callServer(url, formData).then((response) => {
          if (response.error) {
            self.validating = false;
            self.uipress.notify(response.message, '', 'error', true);
            return;
          } else if (response instanceof Error) {
            // it's an error, unable to contact licence server
            self.validating = false;
            self.uipress.notify(__('Check failed', 'uipress-pro'), __('Unable to contact licencing servers. Please try again later', 'uipress-pro'), 'error', true);
          } else if (response.state == 'false') {
            //It's a licence key error
            self.validating = false;
            self.uipress.notify(response.message, '', 'error', true);
            return;
          } else if (response.state == 'true') {
            self.sp = false;
            self.validating = false;
            self.rg = true;
            self.uipress.notify(__('Thank you!', 'uipress-pro'), __('Your key is valid and you can now use pro features', 'uipress-pro'), 'success', true);
            self.saveData(self.userKey, response);
            self.uipress.saveUserPreference('uipfa', 2, false);
            return;
          } else {
            self.validating = false;
            self.uipress.notify(__('Check failed', 'uipress-pro'), __('Something went wrong. Please try again later', 'uipress-pro'), 'error', true);
            return;
          }
        });
      },
    },
    template: `
	<div id="notification-drop" class="uip-dark-mode">
		<div v-if="sp" class="uip-notification uip-slide-in-left uip-success" style="width:auto;">
			<div class="uip-flex uip-gap-xs">
				<div class="uip-w-4 uip-border-round uip-notification-status"></div>
				<div class="uip-max-w-300">
					<div class="uip-text-bold uip-text-inverse uip-margin-bottom-s">{{strings.thankYou}}</div>
					<div class="uip-text-s uip-text-muted uip-margin-bottom-s">{{strings.thankYouMessage}}</div>
					
					<form class="uip-flex uip-flex-row uip-gap-xs" @submit.prevent="onSubmit()">
						<input class="uip-input" type="text" v-model="userKey" placeholder="xxxx-xxxx-xxxx-xxxx" required>
						<label class="uip-button-primary uip-body-font uip-min-w-60 uip-flex uip-flex-middle uip-flex-row">
							<input v-show="!validating" type="submit" class="uip-blank-input uip-text-emphasis" :value="strings.register"/>
							<div><span v-show="validating" class="uip-load-spinner" ></span></div>
						</label>
					</form>
				</div>
				<div>
					<div class="uip-icon uip-margin-left-xs uip-cursor-pointer uip-link-default" @click="sp = false">close</div>
				</div>
			</div>\	
		</div>
	</div>
  <!--Pro Plugins-->
  <template v-if="rg">
  <uip-favicon></uip-favicon>
  <uip-site-title></uip-site-title>
  <uip-google-analytics></uip-google-analytics>
  <uip-woocommerce></uip-woocommerce>
  </template>`,
  };
}
