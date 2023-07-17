const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      parent: [String, Number],
      list: Array,
      incrementCount: Function,
    },
    data: function () {
      return {
        newFolder: {
          name: '',
          color: 'rgb(108, 76, 203)',
        },
        strings: {
          newFolder: __('New folder', 'uipress-pro'),
          folderName: __('Folder name', 'uipress-pro'),
          folderColor: __('Folder colour', 'uipress-pro'),
          create: __('Create', 'uipress-pro'),
        },
      };
    },
    watch: {},
    inject: ['uipress', 'limitToType', 'postTypes'],
    methods: {
      //Open default folder and get contents
      createNewFolder() {
        let self = this;

        if (self.newFolder.name == '') {
          self.uipress.notify(__('Folder name can not be blank', 'uipress-pro'), '', 'error', true);
          return;
        }
        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_folders_create_folder');
        formData.append('security', uip_ajax.security);
        formData.append('folderParent', self.parent);
        formData.append('folderName', self.newFolder.name);
        formData.append('folderColor', self.newFolder.color);
        formData.append('limitToType', self.limitToType);
        formData.append('postTypes', JSON.stringify(self.postTypes));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            if (typeof self.incrementCount !== 'undefined') {
              self.incrementCount(1);
            }
            self.uipress.notify(__('Folder created', 'uipress-pro'), '', 'success', true);
            if (self.uipress.isObject(response.folder)) {
              self.list.push(response.folder);
            }
          }
        });
      },
    },
    template: `
		<drop-down dropPos="right">
		  <template v-slot:trigger>
			<div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-muted uip-border-round uip-no-text-select uip-max-w-100p uip-padding-xxs uip-padding-top-xxxs uip-padding-bottom-xxxs">
			  <div class="uip-icon uip-text-l">add</div>
			  <div class="uip-overflow-hidden uip-text-ellipsis uip-no-wrap uip-flex-grow">{{strings.newFolder}}</div>
			</div>
		  </template>
		  <template v-slot:content>
			<div class="uip-padding-s uip-flex uip-flex-column uip-row-gap-xxs">
			  <div class="uip-text-muted">{{strings.folderName}}</div>
			  <input type="text" v-model="newFolder.name" class="uip-text-s uip-input-small">
			  
			  <div class="uip-text-muted uip-margin-top-xs">{{strings.folderColor}}</div>
			  <div class="uip-background-muted uip-border-round uip-overflow-hidden uip-padding-xxs">
				<div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
				  <color-picker :value="newFolder.color" :returnData="function(data){ newFolder.color = data}">
					<template v-slot:trigger>
					  <div class="uip-border-round uip-w-18 uip-ratio-1-1 uip-border" :style="'background-color:' + newFolder.color"></div>
					</template>
				  </color-picker>
				  <input v-model="newFolder.color" type="text" class="uip-blank-input uip-text-s" style="line-height: 1.2em !important">
				</div>
			  </div>
			  
			  <button class="uip-button-primary uip-text-s uip-margin-top-s" @click="createNewFolder()">{{strings.create}}</button>
			</div>
		  </template>
		</drop-down>
		`,
  };
}
