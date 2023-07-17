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
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    watch: {},
    mounted: function () {},
    computed: {
      getLink() {
        let src = this.uipress.get_block_option(this.block, 'block', 'linkSelect', true);

        if (typeof src == 'undefined') {
          return 'https://uipress.co';
        }

        if (!src || src == '') {
          return 'https://uipress.co';
        }

        if (this.uipress.isObject(src)) {
          if ('value' in src) {
            return src.value;
          }
        }
        return src;
      },
    },
    methods: {
      returnClasses() {
        let classes = '';

        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
    },
    template: `
		  <iframe :src="getLink" class="uip-w-100p"\
		  :class="returnClasses()" :id="block.uid">\
		  </iframe>`,
  };
}
