const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {},
    data: function () {
      return {
        ui: {
          dynamicData: {
            display: false,
            index: 0,
            top: 0,
            right: 0,
            input: false,
            search: '',
            inlineSearch: '',
            cursorPosition: 0,
          },
        },
        strings: {
          dynamicData: __('Dynamic data', 'uipress-lite'),
          searchData: __('Search', 'uipress-lite'),
        },

        dynamicCategories: [
          {
            name: 'query',
            label: __('Query', 'uipress-lite'),
            icon: 'all_inclusive',
          },
          {
            name: 'array',
            label: __('Array', 'uipress-lite'),
            icon: 'data_array',
          },
          {
            name: 'image',
            label: __('Image', 'uipress-lite'),
            icon: 'image',
          },
          {
            name: 'link',
            label: __('Link', 'uipress-lite'),
            icon: 'link',
          },
          {
            name: 'text',
            label: __('Text', 'uipress-lite'),
            icon: 'title',
          },
        ],
        dynamicOptions: [
          {
            name: 'post_title',
            label: __('Post title', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_author',
            label: __('Post author', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_date',
            label: __('Post date', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_link',
            label: __('Post link', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_edit_link',
            label: __('Post edit link', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_featured_image',
            label: __('Post featured image', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_content',
            label: __('Post content', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_excerpt',
            label: __('Post excerpt', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_type',
            label: __('Post type', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_status',
            label: __('Post status', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'comment_count',
            label: __('Comment count', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'meta:key',
            label: __('Meta key', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'acf_meta:key',
            label: __('ACF Meta key', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'mb_meta:key',
            label: __('Metabox Meta key', 'uipress-lite'),
            type: 'query',
          },
          //Attachment
          {
            name: 'attachment_image',
            label: __('Attachment image', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'post_mime_type',
            label: __('Attachment type', 'uipress-lite'),
            type: 'query',
          },
          //Users
          {
            name: 'ID',
            label: __('User id', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'display_name',
            label: __('User display name', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'user_email',
            label: __('User email', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'user_login',
            label: __('User login', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'user_nicename',
            label: __('User nicename', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'user_registered',
            label: __('User registered', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'user_avatar',
            label: __('User profile image', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'meta:key',
            label: __('User meta', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'acf_user_meta:key',
            label: __('ACF User meta', 'uipress-lite'),
            type: 'query',
          },
        ],
        multisiteOptions: [
          //Multisites
          {
            name: 'registered',
            label: __('Site registered', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'domain',
            label: __('Site domain', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'last_updated',
            label: __('Site last updated', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'path',
            label: __('Site path', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'blog_id',
            label: __('Site id', 'uipress-lite'),
            type: 'query',
          },

          {
            name: 'site_name',
            label: __('Site name', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'site_home_url',
            label: __('Site home url', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'site_dashboard_url',
            label: __('Site admin url', 'uipress-lite'),
            type: 'query',
          },
          {
            name: 'meta:key',
            label: __('Site meta', 'uipress-lite'),
            type: 'query',
          },
        ],
      };
    },
    inject: ['uipress', 'uiTemplate', 'uipData'],
    watch: {},
    mounted: function () {
      this.formatDynamicData();
      this.mountWatcher();
    },
    computed: {
      dynamicOptionsWithSearch() {
        let temp = [];
        for (let item of this.dynamicOptions) {
          if (this.inSearch(item)) {
            temp.push(item);
          }
        }
        return temp;
      },
      //////////////
      //Returns menu styling
      //////////////
      returndynamicDataMenuStyle() {
        let self = this;
        let style = 'top:' + self.ui.dynamicData.top + '; right:' + self.ui.dynamicData.right + ';';
        return style;
      },
    },
    methods: {
      ////////////
      ///Formats dynamic data into groups and pushes multisite options if on multisite
      ////////////
      formatDynamicData() {
        let dynaAsArray = Object.values(this.uipData.dynamicOptions);
        dynaAsArray = dynaAsArray.sort((a, b) => {
          if (a.type < b.type) return -1;
          if (a.type > b.type) return 1;
          return 0;
        });

        if (this.uipData.options.multisite) {
          this.dynamicOptions = this.dynamicOptions.concat(this.multisiteOptions);
        }

        this.dynamicOptions = this.dynamicOptions.concat(dynaAsArray);
      },
      ////////////
      ///Mounts the main watcher for the sequence '{{'
      ////////////
      mountWatcher() {
        let self = this;
        self.mountDynamicSequence('{{', function (coordinates, input) {
          let right = window.innerWidth - coordinates.x;

          self.ui.dynamicData.right = `${right}px`;
          self.ui.dynamicData.display = true;
          self.ui.dynamicData.input = input;
          self.ui.dynamicData.search = '';
          self.ui.dynamicData.cursorPosition = self.getCaretPosition(input);

          //Check for offscreen
          if (coordinates.y + 300 > window.innerHeight) {
            self.ui.dynamicData.top = coordinates.y - 280 + 'px';
          } else {
            self.ui.dynamicData.top = `${coordinates.y}px`;
          }

          //Watch for closing
          self.ui.dynamicData.closeEvent = self.mountDynamicSequence('}}', self.closeDynamicData);
          input.addEventListener('keypress', self.watchKeyInput);
          input.addEventListener('keydown', self.watchForclosure);
          document.addEventListener('click', self.watchForOutsideClick);
        });
      },
      //////////////////////
      ////Main event watcher
      /////////////////////
      mountDynamicSequence(sequence, callback) {
        const wrappedHandleInputEvent = (event) => this.handleInputEvent(event, sequence, callback);
        document.addEventListener('input', wrappedHandleInputEvent);
        return wrappedHandleInputEvent;
      },
      //////////////////////
      ////Watches for the main sequence and triggers dynamic window
      /////////////////////
      handleInputEvent(event, sequence, callback) {
        if (event.target.tagName === 'INPUT' || event.target.classList.contains('ql-editor')) {
          const inputValue = this.returnInputValue(event.target);
          const cursor = this.getCaretPosition(event.target);

          let tempVal = inputValue.slice(cursor - sequence.length);
          let afterInput = tempVal.slice(sequence.length);
          let comparison = tempVal.replace(afterInput, '');

          if (comparison === sequence) {
            const inputRect = event.target.getBoundingClientRect();
            const cursorCoordinates = {
              x: inputRect.left + cursor,
              y: inputRect.top,
            };
            callback(cursorCoordinates, event.target);

            //Get coordinates
          }
        }
      },
      /////////////////////
      //Main function for injecting the selected dynamic data
      /////////////////////
      injectDynamicLabel(data) {
        const input = this.ui.dynamicData.input;
        let sequence = '{{';
        const textToInject = `{{${data.name}}}`;
        const cursorPosition = this.ui.dynamicData.cursorPosition;

        if (this.ui.dynamicData.inlineSearch) {
          sequence += this.ui.dynamicData.inlineSearch;
        }

        // Remove the '{{' sequence and insert the text to inject
        let inputVal = this.returnInputValue(input);
        const updatedValue = this.returnInputValue(input).slice(0, cursorPosition - sequence.length) + textToInject + inputVal.slice(cursorPosition);
        //const updatedValue = input.value.slice(0, cursorPosition - sequence.length) + textToInject + input.value.slice(cursorPosition);

        // Update the input value and set the new cursor position
        if (input.tagName === 'INPUT') {
          input.value = updatedValue;
        } else {
          input.textContent = updatedValue;
        }
        input.selectionStart = input.selectionEnd = cursorPosition - sequence.length + textToInject.length;

        input.dispatchEvent(new Event('input'));

        this.ui.dynamicData.display = false;
        this.closeDynamicData();
      },
      ////////////////////////
      //Watches key input and syncs between search and input
      ///////////////////////
      watchKeyInput(event) {
        event.preventDefault();
        if (event.target.tagName === 'INPUT' || event.target.classList.contains('ql-editor')) {
          if (event.key === 'Backspace') {
            this.ui.dynamicData.search = this.ui.dynamicData.search.slice(0, -1);
            this.ui.dynamicData.inlineSearch = this.ui.dynamicData.inlineSearch.slice(0, -1);
          } else if (event.key === 'Escape') {
            this.closeDynamicData();
          } else if (event.key === 'Enter') {
            if (this.dynamicOptionsWithSearch[this.ui.dynamicData.index]) {
              this.injectDynamicLabel(this.dynamicOptionsWithSearch[this.ui.dynamicData.index]);
            }
          } else {
            this.ui.dynamicData.search += event.key;
            this.ui.dynamicData.inlineSearch += event.key;
          }
          this.ui.dynamicData.cursorPosition = this.getCaretPosition(event.target);
        }
      },
      ////////////
      //Watches for keypresss and processes relevant actions
      /////////////
      watchForclosure(event) {
        if (event.target.tagName === 'INPUT' || event.target.classList.contains('ql-editor')) {
          if (event.key === 'Escape') {
            this.closeDynamicData();
          }

          if (event.key === 'Enter') {
            if (this.dynamicOptionsWithSearch[this.ui.dynamicData.index]) {
              this.injectDynamicLabel(this.dynamicOptionsWithSearch[this.ui.dynamicData.index]);
              return;
            }
          }

          if (event.key === 'ArrowDown') {
            let index = this.ui.dynamicData.index;
            if (index >= this.dynamicOptionsWithSearch.length - 1) {
              this.ui.dynamicData.index = 0;
            } else {
              this.ui.dynamicData.index += 1;
            }

            let name = this.dynamicOptionsWithSearch[this.ui.dynamicData.index].name;
            document.querySelector(`#uip-dynamic-list #${name}`).scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
          }
          if (event.key === 'ArrowUp') {
            let index = this.ui.dynamicData.index;
            if (index <= 0) {
              this.ui.dynamicData.index = this.dynamicOptionsWithSearch.length - 1;
            } else {
              this.ui.dynamicData.index -= 1;
            }
            let name = this.dynamicOptionsWithSearch[this.ui.dynamicData.index].name;
            document.querySelector(`#uip-dynamic-list #${name}`).scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
          }
        }
      },
      ////////////////////////
      //Watches for outside clicks and closes dynamic data
      ////////////////////////
      watchForOutsideClick(event) {
        if (!this.$refs.dynamicdatamenu) {
          this.closeDynamicData();
        }
        if (!this.$refs.dynamicdatamenu.contains(event.target)) {
          this.closeDynamicData();
        }
      },
      ////////////////////////
      //Closes dynamic data window and removes event listeners
      ////////////////////////
      closeDynamicData() {
        this.ui.dynamicData.display = false;
        this.ui.dynamicData.index = 0;
        this.ui.dynamicData.search = '';
        this.ui.dynamicData.inlineSearch = '';
        document.removeEventListener('input', this.ui.dynamicData.closeEvent);
        this.ui.dynamicData.input.removeEventListener('keydown', this.watchForClosure);
        this.ui.dynamicData.input.removeEventListener('keypress', this.watchKeyInput);
        this.ui.dynamicData.input.removeEventListener('click', this.watchKeyInput);
        document.removeEventListener('click', this.watchForOutsideClick);
      },
      //HELPERS///////////////
      ////////////////////////
      //returns input value for inputs and content editable divs
      returnInputValue(target) {
        if (target.classList.contains('ql-editor')) {
          return target.textContent;
        } else {
          return target.value;
        }
      },
      //Gets caret position of inputs and content editable divs
      /////////////////////////
      getCaretPosition(element) {
        if (element.tagName === 'INPUT') {
          return element.selectionEnd;
        }

        let caretOffset = 0;

        if (window.getSelection) {
          const selection = window.getSelection();
          if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const preCaretRange = range.cloneRange();
            preCaretRange.selectNodeContents(element);
            preCaretRange.setEnd(range.endContainer, range.endOffset);
            caretOffset = preCaretRange.toString().length;
          }
        }

        return caretOffset;
      },

      ////////////
      //Checks if something is in search
      ////////////
      inSearch(item) {
        let lowerLabel = item.label.toLowerCase();
        let lowerName = item.name.toLowerCase();
        let lowerSearch = this.ui.dynamicData.search.toLowerCase();

        if (lowerLabel.includes(lowerSearch) || lowerName.includes(lowerSearch)) {
          return true;
        }
        return false;
      },
    },
    template: `
    <Transition name="slide-down">
      <div ref="dynamicdatamenu"  class="uip-border-rounder uip-shadow uip-border uip-position-fixed uip-background-default" v-if="ui.dynamicData.display" style="z-index:99999" :style="returndynamicDataMenuStyle">
      
        <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xs">
        
          <div class="uip-flex uip-flex-between uip-flex-center uip-min-w-200">
          
            <div class="">{{strings.dynamicData}}</div>
            
            <button class="uip-button-default uip-icon uip-border-rounder uip-padding-xxxs uip-link-muted" @click="closeDynamicData()">close</button>
          
          </div>
          
          <div class="uip-border-top"></div>
          
          <div class="uip-flex uip-flex-column uip-row-gap-xs">
          
            <div class="uip-flex uip-search-block uip-border-round uip-padding-xxs uip-padding-bottom-remove uip-flex uip-gap-xxs uip-flex-center">
              <span class="uip-icon uip-text-muted uip-icon uip-icon-medium">search</span>
              <input class="uip-blank-input uip-flex-grow uip-text-s" type="search" :placeholder="strings.searchData" autofocus="" v-model="ui.dynamicData.search">
            </div>
            
          </div>  
        
        </div>
          
        <div class="uip-padding-xxs uip-flex uip-flex-column uip-max-h-200 uip-overflow-auto uip-row-gap-xs" id="uip-dynamic-list">
          
          <template v-for="cat in dynamicCategories">
          
            <div class="uip-padding-xs uip-padding-top-remove uip-padding-bottom-remove uip-margin-top-xxs uip-text-bold">{{cat.label}}</div>
            
            <div class="uip-flex uip-flex-column">
            
              <template v-for="(data, index) in dynamicOptionsWithSearch">
                
                <div v-if="cat.name == data.type" class="uip-padding-xs uip-padding-top-xxs uip-padding-bottom-xxs uip-link-muted hover:uip-background-muted uip-border-rounder uip-flex uip-flex-center uip-gap-xs" 
                @click="injectDynamicLabel(data)" :class="{'uip-background-muted uip-text-emphasis' : index == ui.dynamicData.index}" :id="data.name">
                
                  <span class="uip-icon">
                    {{cat.icon}}
                  </span>
                  
                  <span>{{data.label}}</span>
                
                </div>
                
              </template>
              
            </div>
          
          </template>
        
        </div>
        
      </div>
    </Transition>
          `,
  };
}
