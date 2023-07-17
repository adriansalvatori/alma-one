const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: Object,
    },
    data: function () {
      return {
        outlineOptions: {
          width: {
            value: '',
            units: 'px',
          },
          offset: {
            value: '',
            units: 'px',
          },
          style: 'solid',
          color: {
            type: 'solid',
            value: '',
          },
        },
        strings: {
          topleft: __('Top left', 'uipress-lite'),
          topright: __('Top right', 'uipress-lite'),
          bottomleft: __('Bottom left', 'uipress-lite'),
          bottomright: __('Bottom right', 'uipress-lite'),
          colour: __('Colour', 'uipress-lite'),
          style: __('Style', 'uipress-lite'),
          width: __('Width', 'uipress-lite'),
          offset: __('Offset', 'uipress-lite'),
        },
        borderTypes: [
          {
            label: __('Solid', 'uipress-lite'),
            value: 'solid',
          },
          {
            label: __('Dashed', 'uipress-lite'),
            value: 'dashed',
          },
          {
            label: __('Dotted', 'uipress-lite'),
            value: 'dotted',
          },
          {
            label: __('Double', 'uipress-lite'),
            value: 'double',
          },
          {
            label: __('Groove', 'uipress-lite'),
            value: 'groove',
          },
          {
            label: __('Ridge', 'uipress-lite'),
            value: 'ridge',
          },
          {
            label: __('Inset', 'uipress-lite'),
            value: 'inset',
          },
        ],
      };
    },
    inject: ['uipress'],
    watch: {
      outlineOptions: {
        handler(newValue, oldValue) {
          let self = this;
          self.returnData(newValue);
        },
        deep: true,
      },
    },
    mounted: function () {
      this.formatValue(this.value);
    },
    methods: {
      formatValue(value) {
        if (typeof value === 'undefined') {
          return;
        }
        if (this.uipress.isObject(value)) {
          this.outlineOptions = { ...this.outlineOptions, ...value };
          return;
        }
      },
    },
    template: `
    
    
    <div class="uip-flex uip-flex-column uip-row-gap-s">
    
    
      <!--Color -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.colour}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
        
          <color-select :value="outlineOptions.color" :args="{modes: ['solid', 'variables']}" :returnData="function(data){outlineOptions.color = data}"></color-select>
      
        </div>
        
      </div>
      
      
      <!--Style -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.style}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
        
          <select class="uip-input-small uip-padding-top-xxxs uip-padding-bottom-xxxs uip-max-w-100p uip-w-100p uip-border-rounder" style="padding-top:2px;padding-bottom:2px;border-radius:var(--uip-border-radius-large)" v-model="outlineOptions.style">
            <template v-for="item in borderTypes">
              <option :value="item.value">{{item.label}}</option>
            </template>
          </select>
      
        </div>
        
      </div>
      
      
      <!--Width -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.width}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center uip-flex-right">
        
          <value-units :value="outlineOptions.width" :returnData="function(data){outlineOptions.width = data}"></value-units>
      
        </div>
        
      </div>
      
      
      <!--offset -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.offset}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center uip-flex-right">
        
          <value-units :value="outlineOptions.offset" :returnData="function(data){outlineOptions.offset = data}"></value-units>
      
        </div>
        
      </div>
      
      
      
        
          
    </div>`,
  };
}
