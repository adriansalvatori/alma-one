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
      range: {
        handler(newValue, oldValue) {
          let self = this;
          if (self.picker) {
            self.picker.destroy();
            self.mountPicker();
          }
        },
      },
      dateRange: {
        handler(newValue, oldValue) {
          console.log(newValue);
        },
        deep: true,
      },
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    mounted: function () {
      this.mountPicker();
    },
    computed: {
      returnContextData() {
        if (typeof this.contextData === 'undefined') {
          this.contextData = {};
          this.contextData.groupDate = {};
          return this.contextData;
        }
        if (!this.uipress.isObject(this.contextData)) {
          this.contextData = {};
          this.contextData.groupDate = {};
          return this.contextData;
        }
        if (!('formData' in this.contextData)) {
          this.contextData.groupDate = {};
          return this.contextData;
        }
        return this.contextData;
      },
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
          plugins: ['RangePlugin', 'PresetPlugin', 'LockPlugin'],
          grid: 2,
          calendars: 2,
          LockPlugin: {
            maxDate: new Date(),
          },
          zIndex: 99,
          RangePlugin: {
            tooltip: true,
          },
          setup(picker) {
            picker.on('select', (e) => {
              let startdate = picker.getStartDate();
              let enddate = picker.getEndDate();
              self.setGroupDates(startdate, enddate);
            });
          },
        };

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
      setGroupDates(start, end) {
        this.contextData.groupDate.start = start;
        this.contextData.groupDate.end = end;
      },
      mountPicker() {
        let self = this;
        self.picker = new easepick.create(self.returnPickerArgs);
      },
    },
    template:
      '\
      <div class="">\
        <div class="uip-flex uip-flex-column uip-inline-flex" :class="returnClasses" :id="block.uid" >\
          <div class="uip-background-muted uip-border-round uip-overflow-hidden uip-padding-xxs uip-flex uip-gap-xs uip-flex-center uip-date-input">\
            <div class="uip-icon uip-text-l">calendar_month</div>\
            <input ref="datepicker" :value="dateRange" :name="returnName" :range="returnDateRange"\
            class="uip-blank-input uip-text-s" type="text" :placeholder="returnPlaceHolder" :required="returnRequired">\
          </div>\
        </div>\
        <div class="uip-date-group-area">\
          <uip-content-area :contextualData="returnContextData"\
          :content="block.content" :returnData="function(data) {block.content = data} " layout="vertical" ></uip-content-area>\
        </div>\
      </div>',
  };
}
