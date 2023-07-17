const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: Object,
    },
    data: function () {
      return {
        items: this.value.options,
        strings: {
          addNew: __('New item', 'uipress-lite'),
        },
      };
    },
    mounted: function () {},
    watch: {
      items: {
        handler(newValue, oldValue) {
          this.returnData({ options: this.items });
        },
        deep: true,
      },
    },
    computed: {
      returnItems() {
        return this.items;
      },
    },
    methods: {
      deleteTab(index) {
        this.items.splice(index, 1);
      },
      newTab() {
        this.items.push({ name: __('List item', 'uipress-pro'), icon: 'favorite' });
      },
      setdropAreaStyles() {
        let returnData = [];
        returnData.class = 'uip-flex uip-flex-column uip-row-gap-xs uip-w-100p';
        return returnData;
      },
    },
    template: `<div class="uip-flex uip-flex-column uip-row-gap-xs">
        <draggable 
          v-model="items" 
          :group="{ name: 'tabs', pull: false, put: false }"
          :component-data="setdropAreaStyles()"
          @start="drag=true"
          @end="drag=false"
          :sort="true"
          itemKey="id">
            <template #item="{element, index}">
              <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
                <div class="uip-border-round uip-padding-xxxs uip-w-18 uip-text-center uip-text-muted uip-icon uip-text-l uip-cursor-drag uip-flex-center uip-background-muted">drag_indicator</div>
                
                
                
                <inline-icon-select :value="{value: element.icon}" :returnData="function(e){element.icon = e.value}">
                  <template v-slot:trigger>
                    <div class=" uip-padding-xxxs uip-w-22 uip-text-center uip-text-muted uip-icon uip-text-l uip-flex-center">{{element.icon}}</div>
                  </template>
                </inline-icon-select>
                
                
                <input type="text" v-model="element.name" class="uip-input-small uip-blank-input uip-border-left-remove uip-border-left-square uip-border-right-square">
                <div @click="deleteTab(index)" class="uip-border-round uip-border-left-square uip-border-left-remove uip-text-l uip-flex uip-icon uip-padding-xxxs uip-text-center uip-cursor-pointer uip-icon uip-link-danger uip-flex-center">delete</div>
              </div>
            </template>
          </draggable>
          <div @click="newTab()" class="uip-padding-xxs uip-border-round uip-background-muted hover:uip-background-grey uip-cursor-pointer uip-flex uip-flex-middle uip-flex-center uip-gap-xs">
            <span class="uip-icon">add</span>
            <span>{{strings.addNew}}</span>
          </div>
      </div>`,
  };
}
