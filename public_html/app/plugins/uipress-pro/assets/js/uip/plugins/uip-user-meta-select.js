const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: String,
    },
    data: function () {
      return {
        selected: this.value,
        dynamics: this.uipData.dynamicOptions,
        search: '',
        strings: {
          chooseMetaKey: __('Choose meta key', 'uipress-pro'),
          searchKeys: __('Search keys', 'uipress-pro'),
          changeKey: __('Change key', 'uipress-pro'),
        },
      };
    },
    inject: ['uipData', 'uipress'],
    watch: {
      selected: {
        handler(newValue, oldValue) {
          this.returnData(this.selected);
        },
        deep: true,
      },
    },

    mounted: function () {},
    computed: {
      returnDynamicData() {
        const ordered = Object.keys(this.dynamics)
          .sort()
          .reduce((obj, key) => {
            obj[key] = this.dynamics[key];
            return obj;
          }, {});
        return ordered;
      },
    },
    methods: {
      inSearch(option) {
        let item = option.label.toLowerCase();
        let string = this.search.toLowerCase();

        if (item.includes(string)) {
          return true;
        } else {
          return false;
        }
      },
    },
    template: `
    
    <drop-down dropPos="bottom-left">
      <template v-slot:trigger>
        <div v-if="selected == ''" class="uip-background-muted uip-border-round uip-padding-xs hover:uip-background-grey uip-cursor-pointer">{{strings.chooseMetaKey}}</div>
        <div v-else class="uip-background-muted uip-border-round uip-padding-xs hover:uip-background-grey uip-cursor-pointer">
          <span class="uip-text-s uip-padding-xxs uip-background-primary-wash uip-border-round uip-margin-right-xs">{{selected}}</span>
          <span>{{strings.changeKey}}</span>
        </div>
      </template>
      <template v-slot:content>
      
          <div class="uip-flex uip-search-block uip-border-round  uip-border-bottom uip-padding-xs">
            <span class="uip-icon uip-text-muted uip-margin-right-xs uip-text-l uip-icon uip-icon-medium">search</span>
            <input class="uip-blank-input uip-flex-grow uip-text-s" type="search" :placeholder="strings.searchKeys" v-model="search" autofocus="">
          </div>
          
          <div class="uip-padding-xxs uip-flex uip-flex-column uip-max-h-300 uip-overflow-auto">
            <template v-for="item in returnDynamicData">
              <div v-if="item.type == 'user_meta' && inSearch(item)" class="uip-padding-xxs hover:uip-background-muted uip-border-round uip-flex uip-flex-column uip-cursor-pointer" @click="selected = item.key">
                  <div class="">{{item.label}}</div>
                  <div class="uip-text-s uip-text-muted">{{item.key}}</div>
              </div>
            </template>
          </div>
          
      </template>
    </drop-down>
    
    `,
  };
}
