const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: Object,
    },
    data: function () {
      return {
        loading: true,
        shadowOptions: {
          position: 'outside',
          horizDistance: {
            value: 0,
          },
          verticalDistance: {
            value: 8,
          },
          blur: {
            value: 16,
          },
          color: {
            value: 'rgba(135, 135, 135, 0.32)',
            units: 'px',
          },
        },
        positionOptions: {
          outside: {
            value: 'outside',
            label: __('Outside', 'uipress-lite'),
          },
          inside: {
            value: 'inside',
            label: __('Inside', 'uipress-lite'),
          },
        },
        strings: {
          horizDistance: __('Horizontal distance', 'uipress-lite'),
          verticalDistance: __('Vertical distance', 'uipress-lite'),
          blur: __('Blur', 'uipress-lite'),
          shadowColour: __('Colour', 'uipress-lite'),
          position: __('Position', 'uipress-lite'),
        },
      };
    },
    inject: ['uipress'],
    watch: {
      shadowOptions: {
        handler(newValue, oldValue) {
          this.returnData(this.shadowOptions);
        },
        deep: true,
      },
    },
    mounted: function () {
      this.processInput(this.value);
    },
    computed: {
      returnShadow() {
        return this.shadowOptions;
      },
    },
    methods: {
      processInput(value) {
        if (typeof value === 'undefined') {
          return;
        }

        if (this.uipress.isObject(value)) {
          if ('horizDistance' in value) {
            this.shadowOptions.horizDistance = { ...this.shadowOptions.horizDistance, ...value.horizDistance };
          }
          if ('verticalDistance' in value) {
            this.shadowOptions.verticalDistance = { ...this.shadowOptions.verticalDistance, ...value.verticalDistance };
          }
          if ('blur' in value) {
            this.shadowOptions.blur = { ...this.shadowOptions.blur, ...value.blur };
          }
          if ('color' in value) {
            this.shadowOptions.color = { ...this.shadowOptions.color, ...value.color };
          }
          if ('position' in value) {
            this.shadowOptions.position = value.position;
          }

          return;
        }
      },
    },
    template: `
    <div class="uip-flex uip-flex-column uip-row-gap-xs">
    
    
      
        <!--Position -->
        <div class="uip-grid-col-1-3">
        
          <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.position}}</span></div>
          
          <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
          
            <toggle-switch :options="positionOptions" :activeValue="shadowOptions.position" :returnValue="function(data){ shadowOptions.position = data}"></toggle-switch>
          
          </div>
          
        </div>
        
    
      
      
        <!--Colour -->
        <div class="uip-grid-col-1-3">
        
          <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.shadowColour}}</span></div>
          
          <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
          
            <color-select :value="returnShadow.color" :args="{modes: ['solid', 'variables']}" :returnData="function(data){shadowOptions.color = data}"></color-select>
          
          </div>
          
        </div>
        
    
        <!--Horizontal -->
        <div class="uip-grid-col-1-3">
        
          <div class="uip-text-muted uip-flex uip-flex-center"><span>X</span></div>
          
          <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
            
            <input type="number" min="0" class="uip-input uip-remove-steps uip-background-remove uip-padding-xxxs uip-flex-grow" style="width: 30px;" v-model="shadowOptions.horizDistance.value">
            
            <div class="uip-padding-xxs uip-border-rounder uip-background-muted uip-flex uip-gap-xxs uip-no-text-select">
              <div class="uip-link-muted uip-icon uip-text-l" @click="shadowOptions.horizDistance.value = parseInt(shadowOptions.horizDistance.value) - 1">remove</div>
              <div class="uip-border-right"></div>
              <div class="uip-link-muted uip-icon uip-text-l" @click="shadowOptions.horizDistance.value = parseInt(shadowOptions.horizDistance.value) + 1">add</div>
            </div>
          
          </div>
          
        </div>
        
        
        <!--Vertical -->
        <div class="uip-grid-col-1-3">
        
          <div class="uip-text-muted uip-flex uip-flex-center"><span>Y</span></div>
          
          <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
            
            <input type="number" min="0" class="uip-input uip-remove-steps uip-background-remove uip-padding-xxxs uip-flex-grow" style="width: 30px;" v-model="shadowOptions.verticalDistance.value">
            
            <div class="uip-padding-xxs uip-border-rounder uip-background-muted uip-flex uip-gap-xxs uip-no-text-select">
              <div class="uip-link-muted uip-icon uip-text-l" @click="shadowOptions.verticalDistance.value = parseInt(shadowOptions.verticalDistance.value) - 1">remove</div>
              <div class="uip-border-right"></div>
              <div class="uip-link-muted uip-icon uip-text-l" @click="shadowOptions.verticalDistance.value = parseInt(shadowOptions.verticalDistance.value) + 1">add</div>
            </div>
          
          </div>
          
        </div>
        
        
        <!--blur -->
        <div class="uip-grid-col-1-3">
        
          <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.blur}}</span></div>
          
          <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
          
            <input type="number" min="0" class="uip-input uip-remove-steps uip-background-remove uip-padding-xxxs uip-flex-grow" style="width: 30px;" v-model="shadowOptions.blur.value">
          
            <div class="uip-padding-xxs uip-border-rounder uip-background-muted uip-flex uip-gap-xxs uip-no-text-select">
               <div class="uip-link-muted uip-icon uip-text-l" @click="shadowOptions.blur.value = parseInt(shadowOptions.blur.value) - 1">remove</div>
               <div class="uip-border-right"></div>
               <div class="uip-link-muted uip-icon uip-text-l" @click="shadowOptions.blur.value = parseInt(shadowOptions.blur.value) + 1">add</div>
            </div>
          
          </div>
          
        </div>
        
        
    
    
        
    </div>`,
  };
}
