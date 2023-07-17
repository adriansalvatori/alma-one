export function moduleData() {
  return {
    props: {
      //array of selected items
      selected: Array,
      //Search Placeholder
      searchPlaceHolder: String,
      //Function to return selected on change
      updateSelected: Function,
      returnType: String,
    },
    data: function () {
      return {
        loading: false,
        thisSearchInput: '',
        options: [],
        selectedOptions: this.selected,
        ui: {
          dropOpen: false,
        },
      };
    },
    inject: ['uipress'],
    computed: {
      formattedOptions() {
        return this.options;
      },
    },
    watch: {
      selectedOptions: {
        handler(newValue, oldValue) {
          return;
          this.updateSelected(this.selectedOptions);
        },
        deep: true,
      },
      thisSearchInput: function (newValue, oldValue) {
        self = this;

        if (newValue == '') {
          return;
        }

        let type = 'users';
        if (self.returnType == 'userrole') {
          type = 'roles';
        }

        let formData = new FormData();
        formData.append('action', 'uip_search_user_roles');
        formData.append('security', uip_ajax.security);
        formData.append('searchString', newValue);
        formData.append('type', type);
        self.loading = true;

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.loading = false;
          if (response.error) {
            self.uipress.notify(response.error, 'error');
            return;
          }
          self.options = response.roles;
        });
      },
    },
    methods: {
      //////TITLE: ADDS A SELECTED OPTION//////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
      addSelected(selectedoption) {
        //if selected then remove it
        if (self.returnType == 'userrole') {
          this.updateSelected(selectedoption.label);
        }
        if (self.returnType == 'userlogin') {
          this.updateSelected(selectedoption.login);
        }
        if (self.returnType == 'userid') {
          this.updateSelected(selectedoption.id);
        }
        if (self.returnType == 'useremail') {
          this.updateSelected(selectedoption.email);
        }
      },
      //////TITLE:  CHECKS IF IN SEARCH//////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////DESCRIPTION: CHECKS IF ITEM CONTAINS STRING
      ifInSearch(option, searchString) {
        let item = option.name.toLowerCase();
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
    
		  <div class="uip-padding-xxs uip-background-muted uip-border-round uip-w-100p uip-max-w-400 uip-cursor-pointer uip-border-box" :class="{'uip-active-outline' : ui.dropOpen}"> 
		    <div class="uip-flex uip-flex-center">
			   <span class="uip-icon uip-text-muted">search</span>
		    </div>
		  </div>
      
		  <div v-if="ui.dropOpen" class="uip-position-absolute uip-background-default uip-border-rounder uip-shadow uip-border uip-w-250 uip-max-w-400 uip-border-box uip-z-index-9 uip-overflow-hidden uip-right-0">
      
		    <div class="uip-flex uip-background-default uip-padding-xs uip-border-bottom uip-flex-center">
			   <span class="uip-icon uip-text-muted uip-margin-right-xs">search</span>
			   <input class="uip-blank-input uip-flex-grow" type="search"  
			   :placeholder="searchPlaceHolder" v-model="thisSearchInput" autofocus>
		    </div>
        
		    <div class="uip-max-h-280 uip-overflow-auto uip-padding-xxs" v-if="formattedOptions.length > 0 || loading">
          <div v-if="loading" class="uip-padding-s uip-flex uip-flex-center uip-flex-middle"><loading-chart></loading-chart></div>
			    <template v-if="!loading" v-for="option in formattedOptions">
			      <div class="uip-background-default uip-padding-xxs uip-border-rounder uip-link-default hover:uip-background-muted" 
			      @click="addSelected(option)" 
			      v-if="ifInSearch(option, thisSearchInput)" 
			      style="cursor: pointer">
            
				      <div class="uip-text-s">
					        <div class="uip-text-bold uip-text-emphasis">{{option.label}}</div>
					        <div class="uip-text-muted">{{option.type}}</div>
				      </div>
            
			      </div>
			    </template>
		    </div>
		  </div>
	  </div>`,
  };
}
