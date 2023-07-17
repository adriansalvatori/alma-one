export function moduleData() {
  return {
    props: {
      options: Object,
      activeValue: [String, Boolean],
      returnValue: Function,
      small: Boolean,
      dontAccentActive: Boolean,
    },
    data: function () {
      return {
        open: false,
        showBack: true,
        bgStyle: '',
      };
    },
    watch: {
      options: {
        handler() {
          this.returnBGstyle();
        },
        deep: true,
      },
      activeValue: {
        handler() {
          this.returnBGstyle();
        },
        deep: true,
      },
    },
    mounted: function () {
      this.returnBGstyle();
    },
    computed: {
      returnItemStyle() {
        let optionKeys = Object.keys(this.options);
        let width = 100 / optionKeys.length;
        let style = `width:calc(${width}%);`;
        return style;
      },
    },
    methods: {
      returnBGstyle() {
        let val = this.activeValue;
        if (val === true) {
          val = 'true';
        }
        if (val === false) {
          val = 'false';
        }

        let optionKeys = Object.keys(this.options);
        let index = optionKeys.indexOf(val);

        if (index < 0) {
          this.showBack = false;
          return;
        } else {
          this.showBack = true;
        }

        let width = 100 / optionKeys.length;
        let left = width * index;
        this.bgStyle = `width:calc(${width}% - 4px);left:calc(${left}% + 2px);`;
      },
      returnData(data) {
        this.returnValue(data);
      },
      isLastItem(index) {
        if (index == this.options[Object.keys(this.options)[Object.keys(this.options).length - 1]].value) {
          return true;
        }
        return false;
      },
      returnTip(item) {
        if ('tip' in item) {
          return item.tip;
        }
        if ('label' in item) {
          return item.label;
        }
        return;
      },
      returnActiveClass(item) {
        if (item.value != this.activeValue) {
          return '';
        }
        let classes = 'uip-text-emphasis';
        return classes;
      },
    },
    template: `
    <div class="uip-border-box uip-min-w-100" :class="{'uip-w-100p' : !small}">
        <div class="uip-position-relative uip-background-muted uip-border-rounder uip-flex uip-flex-wrap uip-w-100p" 
        :class="{'uip-min-h-30' : !small}"
        style="border-radius: calc(var(--uip-border-radius-large) + 2px)">
        
          <div v-if="showBack" class="uip-position-absolute uip-top-2 uip-bottom-2 uip-transition-all uip-background-highlight uip-border uip-shadow-small uip-border-rounder" :style="bgStyle"></div>
          
          <template v-for="(item, index) in options">
              <a type="button" :title="returnTip(item)"
              class="uip-link-muted uip-no-wrap uip-text-muted uip-z-index-1 uip-padding-xxs uip-padding-left-xs uip-padding-right-xs uip-text-center uip-cursor-pointer uip-border-box uip-flex uip-gap-xs uip-flex-center uip-flex-middle"
              :style="returnItemStyle"
              :class="returnActiveClass(item)"
              @click="returnData(item.value)">
              
                <span class="uip-icon" v-if="item.icon" :class="{'uip-text-l' : !small}">{{item.icon}}</span>
                <span class="" v-if="item.label">{{item.label}}</span>
                
              </a>
          </template>
        </div>
      </div>`,
  };
}
