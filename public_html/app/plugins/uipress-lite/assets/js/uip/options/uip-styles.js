const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: Object,
    },
    data: function () {
      return {
        styles: {
          opacity: 1,
          overflow: 'visible',
          fill: {},
          backgroundImage: {},
          outline: {},
          borders: [],
          shadows: [],
          radius: {
            sync: true,
            topleft: '',
            topright: '',
            bottomleft: '',
            bottomright: '',
            units: 'px',
          },
        },
        strings: {
          opacity: __('Opacity', 'uipress-lite'),
          overflow: __('Overflow', 'uipress-lite'),
          fill: __('Fill', 'uipress-lite'),
          backgroundImage: __('Background', 'uipress-lite'),
          border: __('Border', 'uipress-lite'),
          addImage: __('Add image', 'uipress-lite'),
          shadow: __('Shadow', 'uipress-lite'),
          radius: __('Radius', 'uipress-lite'),
          topleft: __('Top left', 'uipress-lite'),
          topright: __('Top right', 'uipress-lite'),
          bottomleft: __('Bottom left', 'uipress-lite'),
          bottomright: __('Bottom right', 'uipress-lite'),
          outline: __('Outline', 'uipress-lite'),
        },
        overflowOptions: [
          {
            value: 'visible',
            label: __('Visible', 'uipress-lite'),
          },
          {
            value: 'auto',
            label: __('Auto', 'uipress-lite'),
          },
          {
            value: 'hidden',
            label: __('Hidden', 'uipress-lite'),
          },
          {
            value: 'scroll',
            label: __('Scroll', 'uipress-lite'),
          },
        ],
        spacingOptions: {
          0: {
            value: '0',
            label: '0',
            tip: __('Remove', 'uipress-lite'),
          },
          XS: {
            value: 'XS',
            label: 'XS',
            tip: __('Extra small', 'uipress-lite'),
          },
          S: {
            value: 'S',
            label: 'S',
            tip: __('Small', 'uipress-lite'),
          },
          M: {
            value: 'M',
            label: 'M',
            tip: __('Medium', 'uipress-lite'),
          },
          L: {
            value: 'L',
            label: 'L',
            tip: __('Large', 'uipress-lite'),
          },
          XL: {
            value: 'XL',
            label: 'XL',
            tip: __('Extra large', 'uipress-lite'),
          },
          custom: {
            value: 'custom',
            icon: 'more_vert',
            tip: __('Custom', 'uipress-lite'),
          },
        },
      };
    },
    inject: ['uipress'],
    created: function () {
      this.formatValue(this.value);
    },
    watch: {
      styles: {
        handler(newValue, oldValue) {
          let self = this;
          self.returnData(self.styles);
        },
        deep: true,
      },
    },
    computed: {
      returnStyles() {
        return this.styles;
      },
      returnBorders() {
        return this.styles.borders;
      },
    },
    methods: {
      formatValue(value) {
        if (typeof value === 'undefined') {
          return;
        }
        if (this.uipress.isObject(value)) {
          if ('opacity' in value) {
            this.styles.opacity = value.opacity;
          }
          if ('overflow' in value) {
            this.styles.overflow = value.overflow;
          }
          if ('borders' in value) {
            this.styles.borders = value.borders;
          }
          if ('outline' in value) {
            this.styles.outline = value.outline;
          }
          if ('fill' in value) {
            this.styles.fill = { ...this.styles.fill, ...value.fill };
          }
          if ('backgroundImage' in value) {
            this.styles.backgroundImage = { ...this.styles.backgroundImage, ...value.backgroundImage };
          }
          if ('radius' in value) {
            this.styles.radius = { ...this.styles.radius, ...value.radius };
          }
          if ('shadows' in value) {
            this.styles.shadows = value.shadows;
          }
          return;
        }
      },

      returnBorderColor(border) {
        let color = border.color;
        let style = '';
        if (color) {
          if (color.value) {
            if (color.value.includes('--')) {
              style = 'background-color:var(' + color.value + ');';
            } else {
              style = 'background-color:' + color.value;
            }
          }
        }
        return style;
      },
      returnShadowColor(shadow, index) {
        let color = shadow.color;
        let style = '';
        if (color) {
          if (color.value) {
            if (color.value.includes('--')) {
              style = 'background-color:var(' + color.value + ');';
            } else {
              style = 'background-color:' + color.value;
            }
          }
        }
        return style;
      },
      returnBackgroundImage() {
        let img = this.styles.backgroundImage.url;
        if (img) {
          return `background-image: url(${img})`;
        }
        return '';
      },
    },
    template: `
    
    
    <div class="uip-flex uip-flex-column uip-row-gap-xs">
    
    
      <!--Opacity -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.opacity}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
        
          <input type="number" class="uip-input uip-padding-xxxs uip-w-60 uip-border-rounder" style="border-radius:var(--uip-border-radius-large)" v-model="styles.opacity">
          
          <input type="range" min="0" max="1" v-model="styles.opacity" step="0.1" class="uip-range uip-w-100">
      
        </div>
        
      </div>
      
      
      <!--Fill -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.fill}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
        
          <color-select :value="returnStyles.fill" :args="{ modes: ['solid', 'gradient', 'variables'] }" :returnData="function(d){styles.fill = d}"></color-select>
      
        </div>
        
      </div>
      
      
      <!--BackgroundImage -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.backgroundImage}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
        
          <inline-image-select :value="styles.backgroundImage" :returnData="function(d){styles.backgroundImage = d}"></inline-image-select>
        
        </div>
        
      </div>
      
      
      <!--Border -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.border}}</span></div>
        
        <div v-if="styles.borders.length < 1" class="uip-flex uip-gap-xs uip-flex-column uip-row-gap-xs uip-flex-right">
          <button class="uip-button-default uip-icon uip-text-l uip-border-rounder uip-padding-xxs" @click="styles.borders.push({})">add</button>
        </div>
        
        <template v-for="(border, index) in styles.borders">
        
          <!--Spacer-->
          <div v-if="index > 0"></div>
        
          <div class="uip-flex uip-gap-xs uip-row-gap-xs uip-flex-center">
          
              <drop-down dropPos="left" triggerClass="uip-flex uip-flex-grow uip-w-100p" containerClass="uip-flex uip-flex-grow uip-w-100p" :dontAnimate="true">
                <template v-slot:trigger>
                  <div class="uip-background-muted uip-border-rounder uip-padding-xxs uip-flex uip-gap-xs uip-w-100p">
                    <div class="uip-border uip-border-round uip-w-18 uip-ratio-1-1 uip-flex" :style="returnBorderColor(border, index)"></div>
                    <div class="uip-text-capitalize">{{border.style}} <span v-if="border.style" class="uip-text-muted">|</span> {{border.position}}</div>
                  </div>
                </template>
                <template v-slot:content>
                  <div class="uip-padding-s uip-border-bottom uip-text-bold">
                    {{strings.border}}
                  </div>
                  <div class="uip-padding-s uip-flex">
                    <border-designer :value="border" :returnData="function(d){styles.borders[index] = d}"></border-designer>
                  </div>
                </template>
              </drop-down>
              
              <button class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted" @click="styles.borders.splice(index, 1)">close</button>
            
            </div>
        
        </template>
        
        <template v-if="returnBorders.length > 0">
          <!--Spacer-->
          <div></div>
          
          <button class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted" @click="styles.borders.push({})">add</button>
        
        
        </template>
      
      </div>
      
      
      
      
      
      <!--Radius -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.radius}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-right uip-flex-column uip-row-gap-xs">
        
          <div class="uip-flex uip-flex-row uip-gap-xxxs uip-flex-right">
          
            <div class="uip-flex uip-flex-row uip-padding-xxxs uip-background-muted uip-border-rounder">
              <div class="uip-icon uip-icon-m uip-text-xl uip-link-muted uip-border-rounder" @click="styles.radius.sync = true"
              :class="{'uip-background-default uip-text-emphasis' : styles.radius.sync}">crop_square</div>
              <div class="uip-icon uip-icon-m uip-text-xl uip-link-muted uip-border-rounder" @click="styles.radius.sync = false"
              :class="{'uip-background-default uip-text-emphasis' : !styles.radius.sync}">crop_free</div>
            </div>
            
            <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-round uip-text-center uip-flex-grow" v-if="styles.radius.sync">
                <input class="uip-blank-input uip-text-center uip-w-100p uip-text-s" v-model="styles.radius.topleft" >
            </div>
            
            <div class="uip-background-muted uip-border-round">
             <units-select :value="styles.radius.units" :returnData="function(e){styles.radius.units = e}"></units-select>
            </div>
          
          </div>
        
        </div>  
        
        <!--Spacer-->
        <div v-if="!styles.radius.sync"></div>
        
        
        <div class="uip-flex uip-flex-row uip-gap-xxxs uip-flex-right" v-if="!styles.radius.sync">
        
          <div class="uip-flex uip-flex-row uip-gap-xxxs uip-flex-right">
            
              <uip-tooltip :message="strings.topleft" :delay="0" containerClass="uip-flex">
                <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-rounder uip-text-center">
                    <input class="uip-blank-input uip-text-center uip-w-28 uip-text-s" v-model="styles.radius.topleft" >
                </div>
              </uip-tooltip>
              
              <uip-tooltip :message="strings.topright" :delay="0" containerClass="uip-flex">
                <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-rounder uip-text-center">
                    <input class="uip-blank-input uip-text-center uip-w-28 uip-text-s" v-model="styles.radius.topright" >
                </div>
              </uip-tooltip>
              
              <uip-tooltip :message="strings.bottomleft" :delay="0" containerClass="uip-flex">
                <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-rounder uip-text-center">
                    <input class="uip-blank-input uip-text-center uip-w-28 uip-text-s" v-model="styles.radius.bottomleft" >
                </div>
              </uip-tooltip>
              
              <uip-tooltip :message="strings.bottomright" :delay="0" containerClass="uip-flex">
                <div class="uip-flex uip-flex-row uip-padding-xxs uip-background-muted uip-border-rounder uip-text-center" >
                    <input class="uip-blank-input uip-text-center uip-w-28 uip-text-s" v-model="styles.radius.bottomright" >
                </div>
              </uip-tooltip>
            
          </div>
          
        </div>
        
      </div>
      
      
      <!--Overflow -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.overflow}}</span></div>
        
        <div class="uip-flex uip-gap-xs uip-flex-no-wrap uip-flex-center">
        
          <select class="uip-input-small uip-padding-top-xxxs uip-padding-bottom-xxxs uip-max-w-100p  uip-w-100p uip-border-rounder" style="padding-top:2px;padding-bottom:2px;border-radius:var(--uip-border-radius-large)" v-model="styles.overflow">
            <template v-for="item in overflowOptions">
              <option :value="item.value">{{item.label}}</option>
            </template>
          </select>
      
        </div>
        
      </div>
      
      
      
      
      <!--Shadows -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.shadow}}</span></div>
        
        <div v-if="styles.shadows.length < 1" class="uip-flex uip-gap-xs uip-flex-column uip-row-gap-xs uip-flex-right">
          <button class="uip-button-default uip-icon uip-text-l uip-border-rounder uip-padding-xxs" @click="styles.shadows.push({})">add</button>
        </div>
        
        <template v-for="(shadow, index) in styles.shadows">
        
          <!--Spacer-->
          <div v-if="index > 0"></div>
        
          <div class="uip-flex uip-gap-xs uip-row-gap-xs uip-flex-center">
          
              <drop-down dropPos="left" triggerClass="uip-flex uip-flex-grow uip-w-100p" containerClass="uip-flex uip-flex-grow uip-w-100p" :dontAnimate="true">
                <template v-slot:trigger>
                  <div class="uip-background-muted uip-border-rounder uip-padding-xxs uip-flex uip-gap-xs uip-w-100p">
                    <div class="uip-border uip-border-round uip-w-18 uip-ratio-1-1 uip-flex" :style="returnShadowColor(shadow, index)"></div>
                    <div class="uip-text-capitalize">{{shadow.style}} <span v-if="shadow.style" class="uip-text-muted">|</span> {{strings.shadow}}</div>
                  </div>
                </template>
                <template v-slot:content>
                  <div class="uip-padding-s uip-border-bottom uip-text-bold">
                    {{strings.shadow}}
                  </div>
                  <div class="uip-padding-s uip-flex">
                    <shadow-designer :value="shadow" :returnData="function(d){styles.shadows[index] = d}"></shadow-designer>
                  </div>
                </template>
              </drop-down>
              
              <button class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted" @click="styles.shadows.splice(index, 1)">close</button>
            
            </div>
        
        </template>
        
        <template v-if="styles.shadows.length > 0">
          <!--Spacer-->
          <div></div>
          
          <button class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted" @click="styles.shadows.push({})">add</button>
          
        
        </template>
      
      </div>
      
      
      
      <!--Outline -->
      <div class="uip-grid-col-1-3">
      
        <div class="uip-text-muted uip-flex uip-flex-center"><span>{{strings.outline}}</span></div>
        
        
        <div class="uip-flex uip-gap-xs uip-row-gap-xs uip-flex-center">
        
            <drop-down dropPos="left" triggerClass="uip-flex uip-flex-grow uip-w-100p" containerClass="uip-flex uip-flex-grow uip-w-100p" :dontAnimate="true">
              <template v-slot:trigger>
                <div class="uip-background-muted uip-border-rounder uip-padding-xxs uip-flex uip-gap-xs uip-w-100p">
                  <div class="uip-border uip-border-round uip-w-18 uip-ratio-1-1 uip-flex" :style="returnBorderColor(styles.outline)"></div>
                  <div class="uip-text-capitalize">{{styles.outline.style}}</div>
                </div>
              </template>
              <template v-slot:content>
                <div class="uip-padding-s uip-border-bottom uip-text-bold">
                  {{strings.outline}}
                </div>
                <div class="uip-padding-s uip-flex">
                  <outline-designer :value="styles.outline" :returnData="function(d){styles.outline = d}"></outline-designer>
                </div>
              </template>
            </drop-down>
            
            <button class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted" @click="styles.outline = {}">close</button>
          
        </div>
        
      
      </div>
      
      
      
      
      
    
    </div>
    
    `,
  };
}
