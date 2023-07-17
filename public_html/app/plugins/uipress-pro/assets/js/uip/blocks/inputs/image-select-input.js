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
        imageSelected: '',
        populated: this.returnPopulated,
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
            if (this.imageSelected == '') {
              this.imageSelected = this.contextualData.formData[this.returnName];
            }
            return this.imageSelected;
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
      chooseImage() {
        let self = this;
        let args = {
          multiple: false,
          style: 'modal',
          features: ['stock', 'upload'],
          fileTypes: ['image/*'],
        };
        let imageEditor = new self.uipMediaLibrary(args);
        imageEditor.create();

        imageEditor.on('uip_media_selected', function (evt) {
          if (evt.detail.files) {
            self.imageSelected = evt.detail.files.url;
          }
        });
      },
    },
    template:
      '\
		  <div class="uip-flex uip-flex-column" :id="block.uid" :class="returnClasses"><span class="uip-input-label uip-text-muted uip-margin-bottom-xxs">{{returnLabel}}</span>\
		    <input v-model="imageSelected" type="text" :name="returnName" :value-holder="returnPopulated" style="opacity:0;max-height:0;min-height:0;overflow:hidden;" :required="returnRequired">\
		  	  \
        <div class="uip-background-muted uip-border-round uip-overflow-hidden uip-image-select">\
          \
          <div v-if="imageSelected" class="uip-background-grey uip-flex uip-flex-center uip-flex-middle uip-position-relative uip-scale-in-center">\
            <img v-if="imageSelected" class="uip-max-h-120" :src="imageSelected">\
          </div>\
          \
          <div class="uip-flex uip-flex-column uip-padding-xs uip-row-gap-xs">\
            <div v-if="imageSelected" class="uip-flex uip-flex-column uip-flex-start uip-scale-in-center">\
              <div class="uip-no-wrap uip-text-ellipsis uip-overflow-hidden uip-max-w-100p uip-text-s">{{imageSelected.split(\'/\').pop()}}</div>\
            </div>\
            <div class="uip-flex uip-flex-row uip-flex-between uip-flex-center">\
              <div class="uip-flex uip-flex-row uip-gap-xs">\
                <div @click="chooseImage()">\
                  <div v-if="imageSelected" class="uip-link-default uip-text-bold">{{strings.replace}}</div>\
                  <div v-if="!imageSelected" class="uip-flex uip-gap-xxs uip-flex-center">\
                    <div class="uip-icon uip-icon-medium">add</div>\
                    <div class="uip-link-default uip-text-bold">{{strings.chooseImage}}</div>\
                  </div>\
                </div>\
              </div>\
              <div v-if="imageSelected" class="uip-icon uip-text-l uip-icon-medium uip-link-danger" @click="imageSelected = \'\'">delete</div>\
            </div>\
          </div>\
        </div>\
        \
		 </div>',
  };
}
