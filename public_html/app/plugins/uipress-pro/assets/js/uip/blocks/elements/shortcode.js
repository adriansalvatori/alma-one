const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      display: String,
      name: String,
      block: Object,
    },
    data: function () {
      return {
        loading: true,
        shortCode: '',
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    watch: {
      getCode: {
        handler(newValue, oldValue) {
          this.buildShortCode();
        },
        deep: true,
      },
    },
    mounted: function () {
      this.buildShortCode();
    },
    computed: {
      getCode() {
        let code = this.uipress.get_block_option(this.block, 'block', 'shortcode');
        if (!code || code == '') {
          return '';
        }
        return code;
      },
    },
    methods: {
      returnClasses() {
        let classes = '';

        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
      buildShortCode() {
        let self = this;
        let code = this.getCode;
        if (!code) {
          return '';
        }

        let formData = new FormData();
        formData.append('action', 'uip_get_shortcode');
        formData.append('security', uip_ajax.security);
        formData.append('shortCode', code);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            //self.uipress.notify(response.message, 'uipress-lite', '', 'error', true);
            //self.saving = false;
          }
          if (response.success) {
            self.shortCode = response.shortCode;
          }
        });

        self.shortCode = code;
      },
    },
    template: `
		  <div class="uip-w-100p"\
		  :class="returnClasses()" :id="block.uid" v-html="shortCode">\
		  </div>`,
  };
}
