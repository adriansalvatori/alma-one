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
        availableOptions: [],
        img: {},
        colourSelected: '',
        populated: this.returnPopulated,
        preFillSet: false,
        strings: {
          placeholder: __('Input placeholder...', 'uipress-pro'),
          replace: __('Replace', 'uipress-pro'),
          chooseImage: __('Add image', 'uipress-lite'),
        },
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate', 'uipMediaLibrary'],
    watch: {},
    mounted: function () {},
    computed: {
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
      returnOptions() {
        let options = this.uipress.get_block_option(this.block, 'block', 'selectOptions');
        this.availableOptions = options.options;
        return this.availableOptions;
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
            if (this.colourSelected == '' && !this.preFillSet) {
              this.colourSelected = this.contextualData.formData[this.returnName];
              this.preFillSet = true;
            }
            return this.colourSelected;
          }
        }
        return '';
      },
    },
    methods: {
      setVar(data) {
        console.log(data);
      },
    },
    template: `
		  <div class="uip-flex uip-flex-column"><span class="uip-input-label uip-text-muted uip-margin-bottom-xxs">{{returnLabel}}</span>
    
		  	  
        <div class="uip-background-muted uip-border-round uip-overflow-hidden uip-colour-select uip-padding-xxs">
          
          <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
            <color-picker :value="colourSelected" :returnData="function(data){ colourSelected = data}">
              <template v-slot:trigger>
                   <div class="uip-border-round uip-w-18 uip-ratio-1-1 uip-border" :style="'background-color:' + colourSelected"></div>
              </template>
            </color-picker>
            <input v-model="colourSelected" type="text" :name="returnName" :value-holder="returnPopulated" :placeholder="returnPlaceHolder" class="uip-blank-input uip-text-s" 
               style="line-height: 1.2em !important" :required="returnRequired">
          </div>
        
        </div>
        
		 </div>`,
  };
}
