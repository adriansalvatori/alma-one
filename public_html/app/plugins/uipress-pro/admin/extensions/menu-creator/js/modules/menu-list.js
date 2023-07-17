const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    data: function () {
      return {
        menus: [],
        page: 1,
        totalPages: 0,
        totalFound: 0,
        loading: false,
        initialLoading: true,
        selectAll: false,
        search: '',
        strings: {
          status: __('Status', 'uipress-pro'),
          forRoles: __('Roles and users', 'uipress-pro'),
          excludes: __('Excludes', 'uipress-pro'),
          modified: __('Modified', 'uipress-pro'),
          name: __('Name', 'uipress-pro'),
          type: __('Type', 'uipress-pro'),
          active: __('Active', 'uipress-pro'),
          draft: __('Draft', 'uipress-pro'),
          results: __('results', 'uipress-pro'),
          searchTemplates: __('Search templates', 'uipress-pro'),
          templateDuplicated: __('Template duplicated', 'uipress-pro'),
          templateDeleted: __('Template deleted', 'uipress-pro'),
          deleteSelected: __('Delete selected', 'uipress-pro'),
          menuCreator: __('Menu Builder', 'uipress-pro'),
          newMenu: __('New menu', 'uipress-pro'),
          welcomeTotheUibuilder: __("It's a little quiet!", 'uipress-pro'),
          welcomeMeta: __('Create a new menu to get started with menu creator', 'uipress-pro'),
          viewDocs: __('View docs', 'uipress-pro'),
          editTemplate: __('Edit menu', 'uipress-pro'),
          duplicateTemplate: __('Duplicate menu', 'uipress-pro'),
          exportTemplate: __('Export menu', 'uipress-pro'),
          importTemplate: __('Import menu', 'uipress-pro'),
          deleteTemplate: __('Delete menu', 'uipress-pro'),
          version: __('version', 'uipress-pro'),
          tools: __('Tools', 'uipress-pro'),
          settings: __('Site settings', 'uipress-pro'),
          phpErrorLog: __('PHP error log', 'uipress-pro'),
          roleEditor: __('Role editor', 'uipress-pro'),
          pro: __('pro', 'uipress-pro'),
          uiTemplate: __('Ui Template', 'uipress-pro'),
          adminPage: __('Admin page', 'uipress-pro'),
          loginPage: __('Login page', 'uipress-pro'),
          frontEndToolbar: __('Frontend toolbar', 'uipress-pro'),
          setupWizard: __('Setup wizard', 'uipress-pro'),
          exportSelected: __('Export selected', 'uipress-pro'),
          uploadMenus: __('Import menus', 'uipress-pro'),
        },
        activeTableTab: 'all',
      };
    },
    inject: ['router', 'uipress'],
    provide() {
      return {
        refreshList: this.getMenus,
      };
    },
    mounted: function () {
      let query = this.$route.query;

      if (query) {
        if (query.page) {
          this.page = parseInt(query.page);
        }

        if (query.search) {
          this.search = query.search;
        }
      }

      this.getMenus();
    },

    watch: {
      search: {
        handler(newValue, oldValue) {
          this.page = 1;
          this.pushQueries();
          this.getMenus();
        },
        deep: true,
      },
      selectAll: {
        handler(newValue, oldValue) {
          this.selectAllItems(newValue);
        },
        deep: true,
      },
    },
    computed: {
      returnTableData() {
        return this.menus;
        //return [];
      },
      returnPage() {
        let self = this;
        return self.page;
      },
      returnSelected() {
        let self = this;
        let count = 0;

        for (const item of self.menus) {
          if (item.selected) {
            count += 1;
          }
        }

        return count;
      },
      returnSelectedIDs() {
        let self = this;
        let ids = [];

        for (const item of self.menus) {
          if (item.selected) {
            ids.push(item.id);
          }
        }

        return JSON.stringify(ids);
      },
    },
    methods: {
      selectAllItems(value) {
        let self = this;

        for (const item of self.menus) {
          item.selected = value;
        }
      },
      pushQueries() {
        this.$router.push({
          query: { search: this.search, page: this.page },
        });
      },
      getMenus() {
        let self = this;
        if (self.loading == true) {
          return;
        }
        self.loading = true;

        let formData = new FormData();
        formData.append('action', 'uipress_get_menus');
        formData.append('security', uip_ajax.security);
        formData.append('page', self.returnPage);
        formData.append('search', self.search);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.menus = response.menus;
          self.totalPages = response.totalPages;
          self.totalFound = response.totalFound;
          self.loading = false;
          self.initialLoading = false;
        });
      },
      duplicateTemplate(id) {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_duplicate_admin_menu');
        formData.append('security', uip_ajax.security);
        formData.append('id', id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            return;
          }
          self.uipress.notify(self.strings.templateDuplicated, '', 'success', true);
          self.getMenus();
        });
      },
      /**
       * Deletes templates
       * @since 3.0.0
       */
      deleteTemplate(ids) {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_delete_admin_menus');
        formData.append('security', uip_ajax.security);
        formData.append('menuids', ids);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            return;
          }
          self.uipress.notify(response.message, '', 'warning', true);
          self.getMenus();
        });
      },
      exportTemplate(id) {
        let self = this;
        self.loading = true;
        let formData = new FormData();
        formData.append('action', 'uipress_get_menu');
        formData.append('security', uip_ajax.security);
        formData.append('id', id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            self.loading = false;
            return;
          }
          //self.customMenu = response.data;
          let customMenu = {};
          customMenu.uipmenus = [response.menuOptions];
          self.exportToJson(customMenu);
          self.loading = false;
        });
      },
      exportMultiple(ids) {
        let self = this;
        self.loading = true;
        let formData = new FormData();
        formData.append('action', 'uipress_get_menus_for_export');
        formData.append('security', uip_ajax.security);
        formData.append('ids', ids);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            self.loading = false;
            return;
          }
          //self.customMenu = response.data;
          let customMenu = {};
          customMenu.uipmenus = response.menus;
          self.exportToJson(customMenu);
          self.loading = false;
        });
      },
      exportToJson(data) {
        self = this;
        let layout = JSON.stringify(data);
        let name = 'UiPress menu export';

        let today = new Date();
        let dd = String(today.getDate()).padStart(2, '0');
        let mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        let yyyy = today.getFullYear();

        let date_today = mm + '-' + dd + '-' + yyyy;
        let filename = name + '-' + date_today + '.json';

        let dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(layout);
        let dlAnchorElem = document.getElementById('uip-menu-export');
        dlAnchorElem.setAttribute('href', dataStr);
        dlAnchorElem.setAttribute('download', filename);
        dlAnchorElem.click();
        self.uipress.notify(__('Menu exported', 'uipress-lite'), '', 'success', true);
      },
      /**
       * Creates new draft ui template
       * @since 3.0.0
       */
      createNewMenu() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_create_new_admin_menu');
        formData.append('security', uip_ajax.security);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.router.push('/menucreator/' + response.id + '/');
          self.getMenus();
        });
      },
      /**
       * Confirms the deletion of template
       * @since 3.0.0
       */
      confirmDelete(id) {
        let self = this;
        self.uipress.confirm(__('Are you sure you want to delete this menu?', 'uipress-pro'), __("Deleted menus can't be recovered", 'uipress-pro'), __('Delete', 'uipress-pro')).then((response) => {
          if (response) {
            self.deleteTemplate(id);
          }
        });
      },
      confirmDeleteMultiple(id) {
        let self = this;
        self.uipress.confirm(__('Are you sure you want to multiple menus?', 'uipress-pro'), __("Deleted menus can't be recovered", 'uipress-pro'), __('Delete', 'uipress-pro')).then((response) => {
          if (response) {
            self.deleteTemplate(id);
          }
        });
      },
      editLayout(id) {
        let self = this;
        self.router.push('/menucreator/' + id + '/');
      },
      goBack() {
        if (this.page > 1) {
          this.page = this.page - 1;
          this.pushQueries();
          this.getMenus();
        }
      },
      goForward() {
        if (this.page < this.totalPages) {
          this.page = this.page + 1;

          this.pushQueries();
          this.getMenus();
        }
      },
      componentExists(name) {
        if (this.$root._.appContext.components[name]) {
          return true;
        } else {
          return false;
        }
      },
      generateVal(stat) {
        console.log(stat);
        if (stat == 'draft') {
          console.log('this should be off');
          return false;
        }
        console.log('this is on');
        return true;
      },
      updateItemStatus(item) {
        let self = this;

        if (item.status == 'publish') {
          item.status = 'draft';
        } else {
          item.status = 'publish';
        }

        let formData = new FormData();
        formData.append('action', 'uip_update_menu_status');
        formData.append('security', uip_ajax.security);
        formData.append('templateid', item.id);
        formData.append('status', item.status);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            return;
          }
          self.uipress.notify(response.message, '', 'success', true);
        });
      },
      importMenus() {
        let self = this;
        let notiID = self.uipress.notify(__('Importing menus', 'uipress-lite'), '', 'default', false, true);

        let fileInput = document.getElementById('uip-import-menu');
        let thefile = fileInput.files[0];

        if (thefile.type != 'application/json') {
          self.uipress.notify('Import file must be in valid JSON format', '', 'error', true, false);
          self.uipress.destroy_notification(notiID);
          return;
        }

        if (thefile.size > 1000000) {
          self.uipress.notify('Uploaded file is too big', '', 'error', true, false);
          self.uipress.destroy_notification(notiID);
          return;
        }

        let reader = new FileReader();
        reader.readAsText(thefile, 'UTF-8');

        reader.onload = function (evt) {
          let json_settings = evt.target.result;
          let parsed;
          try {
            parsed = JSON.parse(json_settings);
          } catch (error) {
            self.uipress.notify(error, '', 'error', true, false);
            self.uipress.destroy_notification(notiID);
            return;
          }

          if (parsed != null) {
            if (!('uipmenus' in parsed)) {
              self.uipress.notify('Template is not valid', '', 'error', true, false);
              self.uipress.destroy_notification(notiID);
              return;
            }
            if (!Array.isArray(parsed.uipmenus)) {
              self.uipress.notify('Template is not valid', '', 'error', true, false);
              self.uipress.destroy_notification(notiID);
              return;
            }
            self.importMenusFromArray(parsed.uipmenus, notiID);
          } else {
            self.uipress.notify('JSON parse failed', '', 'error', true, false);
            self.uipress.destroy_notification(notiID);
          }
        };
      },
      importMenusFromArray(importedMenus, notiID) {
        let self = this;
        self.saving = true;

        let menus = JSON.stringify(importedMenus, (k, v) => (v === 'true' ? 'uiptrue' : v === true ? 'uiptrue' : v === 'false' ? 'uipfalse' : v === false ? 'uipfalse' : v === '' ? 'uipblank' : v));

        let formData = new FormData();
        formData.append('action', 'uipress_save_menus_from_import');
        formData.append('security', uip_ajax.security);
        formData.append('menus', menus);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            return;
          }
          self.uipress.notify(__('Menus imported succesfully', 'uipress-pro'), '', 'success', true);
          self.saving = false;
          self.uipress.destroy_notification(notiID);
          self.getMenus();
        });
      },
    },
    template: `
	
	
  	<div class="uip-padding-m uip-background-default uip-body-font uip-text-normal uip-app-frame uip-border-box uip-overflow-auto" style="min-height: calc(100vh - 32px); max-height: calc(100vh - 32px)">
	  
	  	<router-view :key="$route.path"></router-view>

		  
		  
		  <div class="uip-flex uip-flex-between uip-margin-bottom-l uip-flex-center uip-padding-xs">
	  		<div class="uip-flex uip-flex-row uip-gap-s uip-flex-center">
				<div class="uip-w-32 uip-ratio-1-1 uip-logo"></div>
			  	<div class="uip-flex uip-flex-column uip-row-gap-xxs">
				
		  			<span class="uip-text-xl uip-text-bold uip-text-emphasis">{{strings.menuCreator}}</span>
				
				</div>
	  		</div>
   		</div>
	
		
				  
		<!--EMPTY-->
		<div v-if="returnTableData.length == 0 && !loading && search == ''" class="uip-flex uip-flex-center uip-flex-middle uip-padding-top-l">
			  <div class="uip-flex uip-flex-column uip-row-gap-s uip-flex-start uip-max-w-100p">
				<div class="uip-w-60 uip-ratio-1-1 uip-logo"></div>
				
				<div class="uip-text-emphasis uip-text-bold uip-text-xxl">{{strings.welcomeTotheUibuilder}}</div>
				<div class="uip-text-l uip-w-400 uip-text-muted">{{strings.welcomeMeta}}</div>
				
				<div class="uip-flex uip-flex-row uip-gap-s uip-flex-center uip-margin-top-s">
					  <div class="uip-button-primary" @click="createNewMenu">{{strings.newMenu}}</div>
				</div>
			  </div>
		</div>  
		
		
		<!--LIST-->
		<template v-else>
		
	 		<div class="uip-margin-bottom-s uip-flex uip-flex-row uip-gap-s uip-flex-center">
	 		
	   			<div class="uip-flex uip-padding-xxs uip-search-block uip-border-round uip-flex-grow uip-margin-left-xs hover:uip-background-muted">
		  			<span class="uip-icon uip-text-muted uip-margin-right-xs uip-text-l uip-icon-medium" style="font-size:20px;">search</span> 
		  			<input class="uip-blank-input uip-flex-grow" type="search" v-model="search" :placeholder="strings.searchTemplates" autofocus="">
	   			</div>
	   			
	   			<div v-if="returnSelected > 0">
			  			<button @click="confirmDeleteMultiple(returnSelectedIDs)" class="uip-button-danger" >{{strings.deleteSelected}} (<strong>{{returnSelected}}</strong>)</button>
		  		</div>
          
          <div v-if="returnSelected > 0">
              <button @click="exportMultiple(returnSelectedIDs)" class="uip-button-default" >{{strings.exportSelected}} (<strong>{{returnSelected}}</strong>)</button>
          </div>
	   			
	   			<div class="uip-button-primary uip-flex uip-flex-row uip-gap-xxs uip-flex-center" @click="createNewMenu">
						<span class="uip-icon">add</span>
						<span>{{strings.newMenu}}</span>
				  </div>
          
          <uip-tooltip :message="strings.uploadMenus">
            <label>
              <div class="uip-button-default uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
                <span class="uip-icon">file_upload</span>
              </div>
              <input hidden accept=".json" type="file" single="" id="uip-import-menu" @change="importMenus()">
            </label>
          </uip-tooltip>
	   		
	 		</div>
		 		
	 		
	  		
	  		<div class="uip-max-w-100p uip-overflow-auto uip-borde uip-border-round uip-margin-bottom-s">
          
          <div class="uip-position-relative">
            <div class="uip-ajax-loader" v-if="loading && !initialLoading">
              <div class="uip-loader-bar"></div>
            </div>
          </div>
				
					<table v-if="returnTableData.length > 0" class="uip-w-100p uip-border-collapse uip-min-w-1000">
			  		<thead>
				  		<tr class="uip-border-bottom uip-border-tp">
					  		<th class="uip-text-left uip-w-28 uip-padding-xs uip-padding-left-s uip-padding-right-s"><input v-model="selectAll" class="uip-checkbox" type="checkbox"></th>
					  		<th class="uip-text-left uip-text-weight-normal uip-text-muted uip-padding-xs uip-padding-left-s uip-padding-right">{{strings.name}}</th>
					  		<th class="uip-text-left uip-text-weight-normal uip-text-muted uip-padding-xs uip-padding-left-s uip-padding-right">{{strings.status}}</th>
					  		<th class="uip-text-left uip-text-weight-normal uip-text-muted uip-padding-xs uip-padding-left-s uip-padding-right">{{strings.forRoles}}</th>
			  		  		<th class="uip-text-left uip-text-weight-normal uip-text-muted uip-padding-xs uip-padding-left-s uip-padding-right">{{strings.excludes}}</th>
					  		<th class="uip-text-left uip-text-weight-normal uip-text-muted uip-padding-xs uip-padding-left-s uip-padding-right">{{strings.modified}}</th>
					  		<th class="uip-text-left uip-text-weight-normal uip-text-muted uip-padding-xs uip-padding-left-s uip-padding-right"></th>
			      		</tr>
					
						</thead>
		  		  <tbody v-if="!initialLoading">
		  		  
			  		  <template v-for="(item, index) in returnTableData">
						  <tr @mouseover="item.hover = true" @mouseleave="item.hover = false" class="uip-cursor-pointer hover:uip-background-muted" :class="{'uip-border-bottom' : index !== returnTableData.length - 1}">
				  		  <td class="uip-padding-s "><input v-model="item.selected" class="uip-checkbox" type="checkbox"></td>
				  		  <td class="uip-padding-s  uip-text-bold" @click="editLayout(item.id)">{{item.name}}</td>
				  		  
				  		  <td class="uip-padding-s">
							  <label class="uip-switch"><input type="checkbox" :checked="item.status == 'publish'" @click="updateItemStatus(item)"><span class="uip-slider"></span></label>
				  		  </td>
				  		  <td class="uip-padding-s ">
							  <div class="uip-flex uip-flex-row uip-gap-xxs uip-row-gap-xxs uip-flex-wrap">
					  		  <template v-for="user in item.for">
								  <div class="uip-padding-left-xxs uip-padding-right-xxs uip-background-primary-wash uip-border-round">{{user.name}}</div>
					  		  </template>
							  </div>
				  		  </td>
				  		  <td class="uip-padding-s ">
							  <div class="uip-flex uip-flex-row uip-gap-xxs uip-row-gap-xxs uip-flex-wrap">
					  		  <template v-for="user in item.excludes">
								  <div class="uip-padding-left-xxs uip-padding-right-xxs uip-background-primary-wash uip-border-round">{{user.name}}</div>
					  		  </template>
							  </div>
				  		  </td>
				  		  <td class="uip-padding-s ">{{item.modified}}</td>
				  		  <td class="uip-padding-s  uip-w-150">
							  <div v-if="item.hover" class="uip-flex uip-gap-xxs uip-flex-center uip-flex-right">
					  		  <uip-tooltip :message="strings.editTemplate" :delay="200"> <div class="uip-link-muted uip-no-underline uip-icon uip-text-l" @click="editLayout(item.id)">edit_document</div> </uip-tooltip>
					  		  <uip-tooltip :message="strings.duplicateTemplate" :delay="200"> <div class="uip-link-muted uip-no-underline  uip-icon uip-text-l" @click="duplicateTemplate(item.id)">content_copy</div> </uip-tooltip>
					  		  <div class="uip-border-right uip-h-12"></div>
                  
                  <uip-tooltip :message="strings.exportTemplate" :delay="200"> <div class="uip-link-muted uip-no-underline  uip-icon uip-text-l" @click="exportTemplate(item.id)">file_download</div> </uip-tooltip>
                  
                  <div class="uip-border-right uip-h-12"></div>
                  
					  		  <uip-tooltip :message="strings.deleteTemplate" :delay="200"> <div class="uip-link-muted uip-no-underline  uip-icon uip-text-danger uip-text-l" @click="confirmDelete(item.id)">delete</div> </uip-tooltip>
							  </div>
				  		  </td>
						  </tr>
			  		  </template>
			  		  
		  		  </tbody>
					</table>
	  		</div>
	  		<div v-if="initialLoading" class="uip-w-100p uip-flex uip-flex-center uip-flex-middle uip-h-600">
				<loading-chart></loading-chart>
	  		</div>
	  		<div class="uip-flex uip-flex-row uip-flex-between uip-flex-center">
				<div class="">{{totalFound + ' ' + strings.results}}</div>
				<div class="uip-flex uip-gap-xs uip-padding-xs" v-if="totalPages > 0">
		  		<button @click="goBack" v-if="totalPages > 1" class="uip-button-default uip-icon uip-search-nav-button">chevron_left</button>
		  		<button @click="goForward" v-if="page < totalPages" class="uip-button-default uip-icon uip-search-nav-button">chevron_right</button>
				</div>
	  		</div>
		</template>
    <a id="uip-menu-export" class="uip-hidden"></a>
	</div>`,
  };
  return compData;
}
