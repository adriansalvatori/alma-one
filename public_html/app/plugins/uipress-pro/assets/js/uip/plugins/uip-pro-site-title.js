const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {},
    data: function () {
      return {};
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    watch: {},
    mounted: function () {
      let self = this;
      document.addEventListener(
        'uip_page_change',
        (e) => {
          setTimeout(self.getSiteTitle, 600);
        },
        { once: false }
      );
    },
    computed: {
      returnTitle() {
        if (!this.uipress.isObject(this.uiTemplate.globalSettings)) {
          return false;
        }
        if (!('options' in this.uiTemplate.globalSettings)) {
          return false;
        }
        if (!('whiteLabel' in this.uiTemplate.globalSettings.options)) {
          return false;
        } else {
          if ('siteTitle' in this.uiTemplate.globalSettings.options.whiteLabel) {
            return this.returnValue(this.uiTemplate.globalSettings.options.whiteLabel.siteTitle);
          }
        }
        return false;
      },
    },
    methods: {
      getSiteTitle() {
        let title = this.returnTitle;
        if (title) {
          this.setTtitle(title);
        }
      },
      returnValue(option) {
        //Dynamic Images
        if (option.dynamic) {
          let dynkey = option.dynamicKey;
          let pos = option.dynamicPos;
          if (this.uipData.dynamicOptions[dynkey]) {
            let dynValue = this.uipData.dynamicOptions[dynkey].value;

            let userinput = '';
            if (typeof option.string !== 'undefined') {
              userinput = option.string;
            }
            let value = '';
            if (pos == 'left') {
              value = dynValue + userinput;
            } else if (pos == 'right') {
              value = userinput + dynValue;
            } else {
              value = dynValue + userinput;
            }
            return value;
          }
        }

        if ('string' in option) {
          if (option.string != '') {
            return option.string;
          }
        }
        return false;
      },
      setTtitle(title) {
        let siteTitle = document.title;
        let frames = document.getElementsByClassName('uip-page-content-frame');
        if (frames[0]) {
          siteTitle = frames[0].contentDocument.title;
        }
        let newTitle = siteTitle.replace('WordPress', title);
        document.title = newTitle;
      },
    },
    template: '{{getSiteTitle()}}',
  };
}
