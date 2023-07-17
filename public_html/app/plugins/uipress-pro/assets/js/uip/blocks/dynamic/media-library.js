const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      display: String,
      name: String,
      block: Object,
      contextualData: Object,
    },
    data: function () {
      return {
        loading: true,
        range: false,
        picker: false,
        imageEditor: false,
        contextData: this.contextualData,
        dateRange: '',
        date: {
          single: '',
          dateRange: {
            start: '',
            end: '',
          },
          dateRangeComparison: {
            start: '',
            end: '',
          },
        },
        strings: {
          placeholder: __('Input placeholder...', 'uipress-pro'),
        },
      };
    },
    watch: {
      returnRange(newVal, old) {
        this.imageEditor.destroyLibrary();
        this.mountMediaLibrary();
      },
    },
    inject: ['uipData', 'uipress', 'uipMediaLibrary'],
    mounted: function () {
      this.mountMediaLibrary();
    },
    computed: {
      returnPlaceHolder() {
        let item = this.uipress.get_block_option(this.block, 'block', 'inputPlaceHolder', true);
        if (!item) {
          return '';
        }
        if (this.uipress.isObject(item)) {
          if ('string' in item) {
            return item.string;
          } else {
            return '';
          }
        }
        return item;
      },
      limitToAuthor() {
        let item = this.uipress.get_block_option(this.block, 'block', 'limitToAuthor', true);
        return item;
      },
      returnRange() {
        let range = this.uipress.get_block_option(this.block, 'block', 'photosPerPage');
        if (range) {
          if (isNaN(range)) {
            return 20;
          }
          if (range > 999) {
            return 999;
          }
          return range;
        } else {
          return 20;
        }
      },
    },
    methods: {
      returnClasses() {
        let classes = '';
        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
      mountMediaLibrary() {
        let self = this;

        let args = {
          multiple: true,
          style: 'inline',
          useType: 'browse',
          perPage: self.returnRange,
          limitToAuthor: self.limitToAuthor,
          mount: self.$refs.uipmedialibrary,
          features: ['upload', 'delete'],
        };

        this.imageEditor = new self.uipMediaLibrary(args);
        this.imageEditor.create();
      },
    },
    template: `
      <div class="uip-padding-s uip-border-box" ref="uipmedialibrary" :id="block.uid" :class="returnClasses()">
        
      </div>`,
  };
}
