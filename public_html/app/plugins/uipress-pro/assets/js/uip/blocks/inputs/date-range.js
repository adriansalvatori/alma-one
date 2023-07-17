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
      range: {
        handler(newValue, oldValue) {
          let self = this;
          if (self.picker) {
            self.picker.destroy();
            self.mountPicker();
          }
        },
      },
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    mounted: function () {
      this.mountPicker();
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
      returnPopulated() {
        if (typeof this.contextualData === 'undefined') {
          return;
        }
        if (!this.uipress.isObject(this.contextualData)) {
          return;
        }
        if (!('formData' in this.contextualData)) {
          return;
        }

        if (this.contextualData.formData) {
          if (this.returnName in this.contextualData.formData) {
            return this.contextualData.formData[this.returnName];
          }
        }
        return '';
      },
      returnLabel() {
        let item = this.uipress.get_block_option(this.block, 'block', 'inputLabel', true);
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
      returnRequired() {
        let required = this.uipress.get_block_option(this.block, 'block', 'inputRequired');
        return required;
      },
      returnName() {
        let required = this.uipress.get_block_option(this.block, 'block', 'inputName');
        return required;
      },
      returnDateRange() {
        this.range = this.uipress.get_block_option(this.block, 'block', 'dateRange', true);
        return this.range;
      },
      returnPickerArgs() {
        let self = this;
        let range = this.returnDateRange;
        let datepicker = this.$refs.datepicker;
        let args = {
          element: datepicker,
          css: [uipProPath + 'assets/css/libs/uip-datepicker.css'],
          lang: self.uipress.uipAppData.options.locale,
          plugins: ['RangePlugin', 'PresetPlugin'],
          RangePlugin: {
            tooltip: true,
          },
        };

        if (!range) {
          args.plugins = [];
          delete args.RangePlugin;
        }
        return args;
      },
      returnClasses() {
        let classes = '';
        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
    },
    methods: {
      mountPicker() {
        let self = this;
        self.picker = new easepick.create(self.returnPickerArgs);
      },
    },
    template:
      '\
      <label class="uip-flex uip-flex-column" :class="returnClasses" :id="block.uid" ><span class="uip-input-label uip-text-muted uip-margin-bottom-xxs">{{returnLabel}}</span>\
        <div class="uip-background-muted uip-border-round uip-overflow-hidden uip-padding-xxs uip-flex uip-gap-xs uip-flex-center uip-date-input">\
          <div class="uip-icon uip-text-l">calendar_month</div>\
          <input ref="datepicker" :name="returnName" :range="returnDateRange"\
          class="uip-blank-input uip-text-s" type="text" :placeholder="returnPlaceHolder" :required="returnRequired" :value="returnPopulated">\
        </div>\
      </label>',
  };
}
