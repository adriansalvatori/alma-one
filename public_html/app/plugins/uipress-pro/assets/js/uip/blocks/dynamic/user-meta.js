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
        dynamics: this.uipData.dynamicOptions,
        strings: {
          placeholder: __('Input placeholder...', 'uipress-pro'),
        },
      };
    },
    watch: {},
    inject: ['uipData', 'uipress', 'uiTemplate'],
    mounted: function () {
      //his.mountPicker();
      console.log(this.uipData.dynamicOptions);
    },
    computed: {
      returnKey() {
        let item = this.uipress.get_block_option(this.block, 'block', 'userMetaSelect', true);
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

        if (item in this.dynamics) {
          return this.dynamics[item].value;
        }
      },
      returnDirection() {
        let item = this.uipress.get_block_option(this.block, 'block', 'listDirection', true);
        if (!item) {
          return '';
        }
        if (this.uipress.isObject(item)) {
          if ('value' in item) {
            return item.value;
          } else {
            return '';
          }
        }
        return item;
      },
    },
    methods: {
      isArray(item) {
        if (typeof item === 'undefined' || item === '') {
          return false;
        }

        if (Array.isArray(item)) {
          return true;
        }
        if (typeof item !== 'undefined') {
          if (Array.isArray(JSON.parse(JSON.stringify(item)))) {
            return true;
          }
        }

        if (this.uipress.isObject(item)) {
          return true;
        }

        if (item.includes('[') && item.includes(']')) {
          let newValue = item.replace('"', "'");
          if (Array.isArray(JSON.parse(item))) {
            return true;
          }
        }

        return false;
      },
      formatValue(item) {
        if (typeof item === 'undefined' || item === '') {
          return false;
        }
        if (Array.isArray(item)) {
          return item;
        }

        if (this.uipress.isObject(item)) {
          return item;
        }

        if (item.includes('[') && item.includes(']')) {
          let newValue = item.replace('"', "'");
          if (Array.isArray(JSON.parse(item))) {
            return JSON.parse(item);
          }
        }

        return item;
      },
      returnClasses() {
        let classes = '';

        if (this.returnDirection == 'vertical') {
          classes += 'uip-flex-column';
        } else {
          classes += 'uip-flex-row';
        }

        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
    },
    template: `
    
    <div :id="block.uid">
      <div v-if="isArray(returnKey)" class="uip-meta-item uip-flex"  :class="returnClasses()">
      
        <div v-for="item in formatValue(returnKey)" class="uip-meta-item uip-padding-xxxs uip-border-round uip-background-primary-wash uip-margin-right-xxs ">
          {{item}}
        </div>
      
      </div>
      
      <div v-else class="uip-meta-item" :class="returnClasses()" :id="block.uid">{{returnKey}}</div>
    </div>
    
    
    
    `,
  };
}
