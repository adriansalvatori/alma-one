const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    inject: ['uipData', 'uipress', 'uiTemplate'],
    props: {
      translations: Object,
      success: Function,
    },
    data: function () {
      return {
        googliconNoHover: uipProPath + 'assets/img/ga_btn_light.png',
        googliconHover: uipProPath + 'assets/img/ga_btn_dark.png',
        hover: false,
      };
    },
    mounted: function () {},
    computed: {
      returnHoverImg() {
        return this.googliconHover;
      },
      returnNoHoverImg() {
        return this.googliconNoHover;
      },
      returnAccountOnUser() {
        if (typeof this.uiTemplate.globalSettings.options === 'undefined') {
          return false;
        }

        if ('analytics' in this.uiTemplate.globalSettings.options) {
          if ('saveAccountToUser' in this.uiTemplate.globalSettings.options.analytics) {
            return this.uiTemplate.globalSettings.options.analytics.saveAccountToUser;
          }
        }
        return false;
      },
    },
    methods: {
      gauthWindow() {
        let self = this;
        var url =
          'https://accounts.google.com/o/oauth2/auth/oauthchooseaccount?response_type=code&client_id=285756954789-dp7lc40aqvjpa4jcqnfihcke3o43hmt1.apps.googleusercontent.com&redirect_uri=https://analytics.uipress.co&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fanalytics.readonly&access_type=offline&approval_prompt=force&flowName=GeneralOAuthFlow';

        var y = window.outerHeight / 2 + window.screenY - 600 / 2;
        var x = window.outerWidth / 2 + window.screenX - 450 / 2;

        var newWindow = window.open(url, 'name', 'height=600,width=450,top=' + y + ', left=' + x);

        if (window.focus) {
          newWindow.focus();
        }

        window.onmessage = function (e) {
          if (e.origin == 'https://analytics.uipress.co' && e.data) {
            try {
              var analyticsdata = JSON.parse(e.data);

              if (analyticsdata.code && analyticsdata.view) {
                newWindow.close();
                self.uip_save_analytics(analyticsdata);
              }
            } catch (err) {
              ///ERROR
            }
          }
        };
      },
      uip_save_analytics(anadata) {
        let self = this;
        if (!('view' in anadata) || !('code' in anadata)) {
          self.uipress.notify(__('Connection failed', 'uipress-pro'), __('Incorrect credentials returned from account. Please try again', 'uipress-pro'), 'error', true);
          return;
        }

        let formData = new FormData();
        formData.append('action', 'uip_save_google_analytics');
        formData.append('security', uip_ajax.security);
        formData.append('analytics', JSON.stringify(anadata));
        formData.append('saveAccountToUser', self.returnAccountOnUser);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(__('Unable to save account', 'uipress-pro'), response.message, 'error', true);
            return;
          } else {
            self.success();
          }
        });
      },
      returnStyle(hover) {
        if (hover) {
          return 'opacity:1';
        }

        return 'opacity:0';
      },
    },
    template: `
		<a @mouseenter="hover = true" @mouseleave="hover = false" class="uip-position-relative uip-cursor-pointer" @click="gauthWindow()" style="width:120px;height:26px">
			<img width="120"  :src="returnNoHoverImg">
			<img width="120" class="uip-position-absolute uip-left-0" :style="returnStyle(hover)" :src="returnHoverImg">
		</a>`,
  };
}
