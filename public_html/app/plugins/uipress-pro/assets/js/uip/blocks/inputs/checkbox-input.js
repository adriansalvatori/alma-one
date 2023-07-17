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
        arrayString: '',
        populated: this.returnPopulated,
        strings: {
          placeholder: __('Input placeholder...', 'uipress-lite'),
        },
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
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
            if (this.arrayString == '') {
              this.arrayString = this.contextualData.formData[this.returnName];
            }

            return this.arrayString;
          }
        }
        return '';
      },
      returnClasses() {
        let classes = '';
        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
    },
    methods: {
      formatChange(item) {
        let parsed;
        try {
          parsed = JSON.parse(this.arrayString);
        } catch (error) {
          parsed = [];
        }

        if (!Array.isArray(parsed)) {
          parsed = [];
        }
        if (parsed.includes(item.name)) {
          let index = parsed.indexOf(item.name);
          parsed.splice(index, 1);
        } else {
          parsed.push(item.name);
        }

        this.arrayString = JSON.stringify(parsed);
      },
      ifChecked(item) {
        let parsed;
        try {
          parsed = JSON.parse(this.arrayString);
        } catch (error) {
          parsed = [];
        }

        if (!Array.isArray(parsed)) {
          parsed = [];
        }
        if (parsed.includes(item.name)) {
          return true;
        } else {
          return false;
        }
      },
    },
    template:
      '\
		  <div class="uip-flex uip-flex-column" :class="returnClasses" :id="block.uid" ><span class="uip-input-label uip-text-muted uip-margin-bottom-xxs">{{returnLabel}}</span>\
		  <input v-model="arrayString" type="text" :name="returnName" :value-holder="returnPopulated" :required="returnRequired" style="opacity:0;max-height:0;min-height:0;overflow:hidden;">\
		  	<div class="uip-flex uip-flex-column uip-row-gap-xxs">\
		  		<template v-for="(item,index) in returnOptions">\
			  		<label class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center">\
					  <input class="uip-checkbox" type="checkbox" @change="formatChange(item)" :checked="ifChecked(item)" :key="index">\
					  <div>{{item.label}}</div>\
					</label>\
				</template>\
			</div>\
		 </div>',
  };
}
