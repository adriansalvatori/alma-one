const { __, _x, _n, _nx } = wp.i18n;

export function moduleData() {
  return {
    props: {
      selected: Array,
      name: String,
      placeholder: String,
      single: Boolean,
      translations: Object,
      updateRoles: Function,
    },
    data: function () {
      return {
        thisSearchInput: '',
        options: [
          {
            name: 'page_view',
            label: __('Page view', 'uipress-pro'),
          },
          {
            name: 'post_created',
            label: __('Post created', 'uipress-pro'),
          },
          {
            name: 'post_updated',
            label: __('Post updated', 'uipress-pro'),
          },
          {
            name: 'post_trashed',
            label: __('Post trashed', 'uipress-pro'),
          },
          {
            name: 'post_deleted',
            label: __('Post deleted', 'uipress-pro'),
          },
          {
            name: 'post_status_change',
            label: __('Post status change', 'uipress-pro'),
          },
          {
            name: 'trash_comment',
            label: __('Trashed comment', 'uipress-pro'),
          },
          {
            name: 'delete_comment',
            label: __('Deelete comment', 'uipress-pro'),
          },

          {
            name: 'plugin_activated',
            label: __('Plugin activated', 'uipress-pro'),
          },
          {
            name: 'plugin_deactivated',
            label: __('Plugin deactivated', 'uipress-pro'),
          },
          {
            name: 'plugin_deleted',
            label: __('Plugin deleted', 'uipress-pro'),
          },
          {
            name: 'user_login',
            label: __('User login', 'uipress-pro'),
          },
          {
            name: 'user_logout',
            label: __('User logout', 'uipress-pro'),
          },
          {
            name: 'option_added',
            label: __('Site option added', 'uipress-pro'),
          },
          {
            name: 'attachment_uploaded',
            label: __('Attachmnet uploaded', 'uipress-pro'),
          },
          {
            name: 'attachment_deleted',
            label: __('Attachmnet deleted', 'uipress-pro'),
          },
          {
            name: 'user_created',
            label: __('User created', 'uipress-pro'),
          },
          {
            name: 'user_deleted',
            label: __('User deleted', 'uipress-pro'),
          },
          {
            name: 'user_updated',
            label: __('User updated', 'uipress-pro'),
          },
        ],
        selectedOptions: this.selected,
        ui: {
          dropOpen: false,
        },
      };
    },
    computed: {
      formattedOptions() {
        return this.options;
      },
    },
    watch: {
      selectedOptions: {
        handler(newValue, oldValue) {
          this.updateRoles(this.selectedOptions);
        },
        deep: true,
      },
    },
    inject: ['uipress'],
    methods: {
      //////TITLE: ADDS A SELECTED OPTION//////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
      addSelected(selectedoption, options) {
        //if selected then remove it
        if (this.ifSelected(selectedoption, options)) {
          this.removeSelected(selectedoption, options);
          return;
        }
        if (this.single == true) {
          options[0] = selectedoption;
        } else {
          options.push(selectedoption);
        }
      },
      //////TITLE: REMOVES A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
      removeSelected(option, options) {
        let index = options.indexOf(option);
        if (index > -1) {
          options = options.splice(index, 1);
        }
      },

      //////TITLE:  CHECKS IF SELECTED OR NOT//////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
      ifSelected(option, options) {
        let index = options.indexOf(option);
        if (index > -1) {
          return true;
        } else {
          return false;
        }
      },
      //////TITLE:  CHECKS IF IN SEARCH//////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////DESCRIPTION: CHECKS IF ITEM CONTAINS STRING
      ifInSearch(option, searchString) {
        let item = option.toLowerCase();
        let string = searchString.toLowerCase();

        if (item.includes(string)) {
          return true;
        } else {
          return false;
        }
      },
      onClickOutside(event) {
        const path = event.path || (event.composedPath ? event.composedPath() : undefined);
        // check if the MouseClick occurs inside the component
        if (path && !path.includes(this.$el) && !this.$el.contains(event.target)) {
          this.closeThisComponent(); // whatever method which close your component
        }
      },
      openThisComponent() {
        this.ui.dropOpen = true; // whatever codes which open your component
        // You can also use Vue.$nextTick or setTimeout
        requestAnimationFrame(() => {
          document.documentElement.addEventListener('click', this.onClickOutside, false);
        });
      },
      closeThisComponent() {
        this.ui.dropOpen = false; // whatever codes which close your component
        document.documentElement.removeEventListener('click', this.onClickOutside, false);
      },
    },
    template: `
      
      <div class="uip-position-relative" @click="openThisComponent">
        <div class="uip-padding-xs hover:uip-background-muted uip-border uip-border-round uip-w-100p uip-cursor-pointer uip-border-box" :class="{'uip-active-outline' : ui.dropOpen}"> 
          <div class="uip-flex uip-flex-center">
            <div class="uip-flex-grow uip-margin-right-s">
              <div>
                <span class="uk-text-meta">{{name}}...</span>
              </div>
            </div>
            <span class="uip-icon uip-text-muted">add</span>
            <span v-if="selectedOptions.length > 0" class="uip-text-inverse uip-background-primary uip-border-round uip-text-s uip-w-18 uip-margin-left-xxs uip-text-center">
              {{selectedOptions.length}}
            </span>
          </div>
        </div>
        <div v-if="ui.dropOpen" class="uip-position-absolute uip-background-default uip-border-round uip-border uip-w-100p uip-border-box uip-z-index-9 uip-margin-top-xs uip-overflow-hidden uip-right-0">
          <div class="uip-flex uip-background-default uip-padding-xs uip-border-bottom uip-flex-center">
            <span class="uip-icon uip-text-muted uip-margin-right-xs uip-text-l">search</span>
            <input class="uip-blank-input uip-flex-grow" type="search"  
            :placeholder="placeholder" v-model="thisSearchInput" autofocus>
          </div>
          <div class="uip-max-h-280 uip-overflow-auto">
            <template v-for="option in formattedOptions">
              <div class="uip-background-default uip-padding-xs hover:uip-background-muted" 
              @click="addSelected(option.name, selectedOptions)" 
              v-if="ifInSearch(option.name, thisSearchInput)" 
              style="cursor: pointer">
                <div class="uip-flex uip-flex-row uip-flex-center">
                  <div class="uip-flex uip-flex-center uip-flex-middle uip-margin-right-xs">
                    <input type="checkbox" class="uip-checkbox" :name="option.name" :value="option.name" :checked="ifSelected(option.name, selectedOptions)">
                  </div>
                  <div class="uip-flex-grow">
                    <div class="uip-text-bold uip-text-emphasis">{{option.label}}</div>
                    <div class="uip-text-muted">{{option.name}}</div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>`,
  };
}
