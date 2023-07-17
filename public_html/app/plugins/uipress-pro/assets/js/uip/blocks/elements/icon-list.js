const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      display: String,
      name: String,
      block: Object,
    },
    data: function () {
      return {
        loading: true,
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    watch: {},
    mounted: function () {},
    computed: {
      getListItems() {
        let items = this.uipress.get_block_option(this.block, 'block', 'blockListItems', true);

        if (typeof items === 'undefined') {
          return [];
        }

        if (!items) {
          return [];
        }

        if (!('options' in items)) {
          return [];
        }

        if (!Array.isArray(items.options)) {
          return [];
        }

        return items.options;
      },
      getListDirection() {
        let items = this.uipress.get_block_option(this.block, 'block', 'listDirection', true);

        if (typeof items === 'undefined') {
          return 'vertical';
        }

        if (!items) {
          return 'vertical';
        }

        if (!('value' in items)) {
          return 'vertical';
        }

        return items.value;
      },
    },
    methods: {
      returnClasses() {
        let classes = '';

        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
    },
    template: `
		  <div  class="uip-w-100p"\
		  :class="returnClasses()" :id="block.uid">\
        <div v-if="getListDirection == 'vertical'" class="uip-flex uip-flex-column uip-row-gap-xs">
          <div v-for="item in getListItems" class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
            <span class="uip-icon">{{item.icon}}</span>
            <span>{{item.name}}</span>
          </div>
        </div>\
        <div v-if="getListDirection == 'horizontal'" class="uip-flex uip-flex-row uip-gap-xs">
          <div v-for="item in getListItems" class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
            <span class="uip-icon">{{item.icon}}</span>
            <span>{{item.name}}</span>
          </div>
        </div>\
		  </div>`,
  };
}
