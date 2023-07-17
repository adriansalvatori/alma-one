const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: Object,
    },
    data: function () {
      return {
        borderOptions: {
          width: {
            value: '',
            units: 'px',
          },
          style: 'solid',
          color: {
            type: 'solid',
            value: '',
          },
          position: 'solid',
          radius: {
            value: {
              sync: true,
              topleft: '',
              topright: '',
              bottomleft: '',
              bottomright: '',
              units: 'px',
            },
          },
        },
        strings: {
          radius: __('Radius', 'uipress-lite'),
          topleft: __('Top left', 'uipress-lite'),
          topright: __('Top right', 'uipress-lite'),
          bottomleft: __('Bottom left', 'uipress-lite'),
          bottomright: __('Bottom right', 'uipress-lite'),
          colour: __('Colour', 'uipress-lite'),
          style: __('Style', 'uipress-lite'),
          width: __('Width', 'uipress-lite'),
          position: __('Position', 'uipress-lite'),
        },
        borderPositions: {
          solid: {
            icon: 'border_outer',
            value: 'solid',
          },
          left: {
            icon: 'border_left',
            value: 'left',
          },
          top: {
            icon: 'border_top',
            value: 'top',
          },
          right: {
            icon: 'border_right',
            value: 'right',
          },
          bottom: {
            icon: 'border_bottom',
            value: 'bottom',
          },
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
      borderOptions: {
        handler(newValue, oldValue) {
          let self = this;
          self.returnData(newValue);
        },
        deep: true,
      },
      'borderOptions.radius.value.topleft': {
        handler(newValue, oldValue) {
          if (this.borderOptions.radius.value.sync) {
            this.borderOptions.radius.value.topright = this.borderOptions.radius.value.topleft;
            this.borderOptions.radius.value.bottomleft = this.borderOptions.radius.value.topleft;
            this.borderOptions.radius.value.bottomright = this.borderOptions.radius.value.topleft;
          }
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
          this.borderOptions = { ...this.borderOptions, ...value };
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
        
          <color-select :value="borderOptions.color" :args="{modes: ['solid', 'variables']}" :returnData="function(data){borderOptions.color = data}"></color-select>
      
        </div>
        
      </div>
    
    
      <!--Position -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.position}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
        
          <toggle-switch :options="borderPositions" :activeValue="borderOptions.position" :returnValue="function(data){ borderOptions.position = data}"></toggle-switch>
      
        </div>
        
      </div>
      
      
      <!--Style -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.style}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
        
          <select class="uip-input-small uip-padding-top-xxxs uip-padding-bottom-xxxs uip-max-w-100p uip-w-100p uip-border-rounder" style="padding-top:2px;padding-bottom:2px;border-radius:var(--uip-border-radius-large)" v-model="borderOptions.style">
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
        
          <value-units :value="borderOptions.width" :returnData="function(data){borderOptions.width = data}"></value-units>
      
        </div>
        
      </div>
      
      
      <!--Radius -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.radius}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-right uip-flex-column uip-row-gap-xs">
        
          <div class="uip-flex uip-flex-row uip-gap-xxxs uip-flex-right">
          
            <div class="uip-flex uip-flex-row uip-padding-xxxs uip-background-muted uip-border-round">
              <div class="uip-icon uip-icon-m uip-text-xl uip-link-muted  uip-border-round" @click="borderOptions.radius.value.sync = true"
              :class="{'uip-background-default uip-text-emphasis' : borderOptions.radius.value.sync}">crop_square</div>
              <div class="uip-icon uip-icon-m uip-text-xl uip-link-muted  uip-border-round" @click="borderOptions.radius.value.sync = false"
              :class="{'uip-background-default uip-text-emphasis' : !borderOptions.radius.value.sync}">crop_free</div>
            </div>
            
            <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-round uip-text-center" v-if="borderOptions.radius.value.sync">
                <input class="uip-blank-input uip-text-center uip-w-60 uip-text-s" v-model="borderOptions.radius.value.topleft" >
            </div>
            
            <div class="uip-background-muted uip-border-round">
             <units-select :value="borderOptions.radius.value.units" :returnData="function(e){borderOptions.radius.value.units = e}"></units-select>
            </div>
          
          </div>
        
        </div>  
        
        <!--Spacer-->
        <div v-if="!borderOptions.radius.value.sync"></div>
        
        
        <div class="uip-flex uip-flex-row uip-gap-xxxs uip-flex-right" v-if="!borderOptions.radius.value.sync">
        
          <div class="uip-flex uip-flex-row uip-gap-xxxs uip-flex-right">
            
              <uip-tooltip :message="strings.topleft" :delay="0" containerClass="uip-flex">
                <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-round uip-text-center">
                    <input class="uip-blank-input uip-text-center uip-w-28 uip-text-s" v-model="borderOptions.radius.value.topleft" >
                </div>
              </uip-tooltip>
              
              <uip-tooltip :message="strings.topright" :delay="0" containerClass="uip-flex">
                <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-round uip-text-center">
                    <input class="uip-blank-input uip-text-center uip-w-28 uip-text-s" v-model="borderOptions.radius.value.topright" >
                </div>
              </uip-tooltip>
              
              <uip-tooltip :message="strings.bottomleft" :delay="0" containerClass="uip-flex">
                <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-round uip-text-center">
                    <input class="uip-blank-input uip-text-center uip-w-28 uip-text-s" v-model="borderOptions.radius.value.bottomleft" >
                </div>
              </uip-tooltip>
              
              <uip-tooltip :message="strings.bottomright" :delay="0" containerClass="uip-flex">
                <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-round uip-text-center" >
                    <input class="uip-blank-input uip-text-center uip-w-28 uip-text-s" v-model="borderOptions.radius.value.bottomright" >
                </div>
              </uip-tooltip>
            
            </div>
          
          </div>
        
        </div>
        
          
    </div>`,
  };
}
