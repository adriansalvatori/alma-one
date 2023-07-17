const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    name: 'content-folder',
    props: {
      folder: Object,
      removeSelf: Function,
      updateID: Function,
    },
    data: function () {
      return {
        loading: false,
        defaults: [],
        counter: 0,
        newFolder: {
          name: '',
          color: 'rgb(108, 76, 203)',
        },
        strings: {
          placeholder: __('Input placeholder...', 'uipress-pro'),
          new: __('New', 'uipress-pro'),
          loadMore: __('Load more', 'uipress-pro'),
          search: __('Search', 'uipress-pro'),
          view: __('View', 'uipress-pro'),
          edit: __('Edit', 'uipress-pro'),
          delete: __('Delete', 'uipress-pro'),
          folders: __('Folders', 'uipress-pro'),
          duplicate: __('Duplicate', 'uipress-pro'),
          folderName: __('Folder name', 'uipress-pro'),
          folderColor: __('Folder colour', 'uipress-pro'),
          update: __('Update', 'uipress-pro'),
          edit: __('Edit', 'uipress-pro'),
        },
      };
    },
    watch: {},
    inject: ['uipress', 'postTypes', 'setActiveFolderID', 'getActiveFolderID', 'isDragging', 'setDrag', 'limitToAuthor', 'limitToType', 'filterTableItems'],
    mounted: function () {
      this.mountDragOnFiles();
    },
    computed: {},
    methods: {
      mountDragOnFiles() {
        let self = this;

        document.addEventListener(
          'uip_update_folder',
          (e) => {
            let id = e.detail.folder_id;
            if (self.folder.id == id) {
              self.getFolderContent();
            }
          },
          { once: false }
        );
      },

      //Open default folder and get contents
      getFolderContent(showLoad) {
        let self = this;

        if (!('page' in self.folder)) {
          self.folder.page = 1;
        }

        if (showLoad) {
          self.folder.loading = true;
        }

        //Set global folder
        self.setActiveFolderID(self.folder.id);

        //Set for wp media files

        if (!wp.media) {
          this.filterTableItems(self.folder.id);
        }

        //Build form data for fetch request

        let formData = new FormData();
        formData.append('action', 'uip_folders_get_folder_content');
        formData.append('security', uip_ajax.security);
        formData.append('limitToAuthor', self.limitToAuthor);
        formData.append('postTypes', JSON.stringify(self.postTypes));
        formData.append('id', self.folder.id);
        formData.append('limitToType', self.limitToType);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.folder.loading = false;

          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.folder.totalFound = response.totalFound;
            self.folder.count = response.folderCount;
            self.folder.content = response.content;
          }
        });
      },
      //Open default folder and get contents
      updateItemFolder(item) {
        let self = this;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_folders_update_item_folder');
        formData.append('security', uip_ajax.security);
        formData.append('item', JSON.stringify(item));
        formData.append('newParent', self.folder.id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.folder.loading = false;

          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            item.parent = self.folder.id;
          }
        });
      },
      deleteFolder() {
        let self = this;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_folders_delete_folder');
        formData.append('security', uip_ajax.security);
        formData.append('postTypes', JSON.stringify(self.postTypes));
        formData.append('folderId', self.folder.id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.folder.loading = false;

          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.removeSelf(self.folder.id);
            self.uipress.notify(__('Folder deleted', 'uipress-pro'), '', 'success', true);
          }
        });
      },
      updateFolder() {
        let self = this;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_folders_update_folder');
        formData.append('security', uip_ajax.security);
        formData.append('folderId', self.folder.id);
        formData.append('title', self.folder.title);
        formData.append('color', self.folder.color);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.uipress.notify(__('Folder updated', 'uipress-pro'), '', 'success', true);
            self.folder.showEdit = false;
          }
        });
      },
      itemAdded(evt) {
        let self = this;
        if (evt.added) {
          //CHECK IF ITEM ALREADY EXISTS IN FOLDER
          let index = this.folder.content.filter((x) => x.id === evt.added.element.id);
          //It exists so remove it
          if (index.length > 1) {
            this.folder.content.splice(evt.added.newIndex, 1);
            return;
          }

          this.folder.content.sort(function (a, b) {
            let textA = a.title.toUpperCase();
            let textB = b.title.toUpperCase();
            return textA < textB ? -1 : textA > textB ? 1 : 0;
          });

          self.folder.count += 1;
          self.updateItemFolder(evt.added.element);
        }
        if (evt.removed) {
          self.folder.count -= 1;
          self.folder.totalFound -= 1;
        }
      },

      setDragAreaClasses() {
        let returnData = [];
        returnData.class = 'uip-flex uip-flex-column uip-row-gap-xxs uip-max-w-100p';

        return returnData;
      },
      setBaseFolderClass() {
        let returnData = [];
        returnData.class = 'uip-flex uip-flex-column uip-max-w-100p';

        return returnData;
      },
      loadMore(folder) {
        folder.page += 1;
        this.getFolderContent();
      },
      checkForBlank(type) {
        if (type.search == '') {
          type.page = 1;
          this.getFolderContent(true);
        }
      },
      addDropClass(evt, folder) {
        evt.preventDefault();

        let target = evt.target.closest('.uip-folder-drop');
        target.classList.add('uip-border-dashed-emphasis');
        target.classList.add('uip-background-grey');
        this.counter++;
      },

      removeDropClass(evt, folder) {
        evt.preventDefault();
        this.counter--;
        if (this.counter === 0) {
          let target = evt.target.closest('.uip-folder-drop');
          target.classList.remove('uip-border-dashed-emphasis');
          target.classList.remove('uip-background-grey');
        }
      },
      finishDrag(evt) {
        evt.preventDefault();
        let target = evt.target.closest('.uip-folder-drop');
        target.classList.remove('uip-border-dashed-emphasis');
        target.classList.remove('uip-background-grey');
        this.setDrag(false);
      },
      setFolderDataTransfer(evt) {
        let self = this;
        evt.dataTransfer.setData('itemID', JSON.stringify([self.folder.id]));
        evt.dataTransfer.setData('parentFolder', self.folder.parent);
        self.setDrag(true);
      },
      addToFolder(evt, folder) {
        let self = this;
        self.finishDrag(evt);
        let itemIDs = evt.dataTransfer.getData('itemID');

        //Not set / incompatable drag
        if (!itemIDs) {
          return;
        }
        //Parse and check for length
        itemIDs = JSON.parse(itemIDs);
        if (itemIDs.length < 1) {
          return;
        }

        let parentFolder = evt.dataTransfer.getData('parentFolder');
        self.folder.count += itemIDs.length;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_folders_add_item_to_folder');
        formData.append('security', uip_ajax.security);
        formData.append('IDS', JSON.stringify(itemIDs));
        formData.append('newParent', self.folder.id);
        formData.append('parentFolder', parentFolder);
        formData.append('postTypes', JSON.stringify(self.postTypes));
        formData.append('limitToType', self.limitToType);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.folder.loading = false;

          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.uipress.notify(response.message, '', 'success', true);

            if (self.folder.open) {
              self.getFolderContent();
            }
            //If we removed an item from a folder then dispatch an event telling it to update
            if (parentFolder) {
              let eventNew = new CustomEvent('uip_update_folder', { 'detail': { folder_id: parentFolder } });
              document.dispatchEvent(eventNew);
            }
          }
        });
      },
      deleteFromList(id) {
        let index = this.folder.content.findIndex((item) => item.id === id);
        this.folder.content.splice(index, 1);
      },
    },
    template: `
      
      <div :data-id="folder.id" :data-parent-folder="folder.parent" class="uip-folder" 
      >
    
    
        <!-- top folder -->
        <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-max-w-100p">
        
          <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-default uip-padding-xxs uip-padding-top-xxxs uip-padding-bottom-xxxs hover:uip-background-muted uip-border-round uip-no-text-select uip-folder-drop" 
          :class="[{'uip-background-muted' : getActiveFolderID() == folder.id}]"
          :data-id="folder.id"
          
          @drop="addToFolder($event, folder)" 
          @dragenter="addDropClass($event, folder)"
          @dragleave="removeDropClass($event, folder)"
          @dragover.prevent
          @dragenter.prevent
          
          @dragstart="setFolderDataTransfer($event)"
          @dragend="setDrag(false)" 
          draggable="true"
          >
          
          
          
            <div class="uip-icon uip-text-l" v-if="!folder.open" :style="'color:' + folder.color" @click="folder.open = !folder.open;getFolderContent(true)">folder</div>
            <div class="uip-icon uip-text-l" v-if="folder.open" :style="'color:' + folder.color" @click="folder.open = !folder.open;getFolderContent(true)">folder_open</div>
            <div class="uip-flex-grow" @click="folder.open = !folder.open;getFolderContent(true)">{{folder.title}}</div>
            <div class="uip-text-muted">{{folder.count}}</div>
            <drop-down dropPos="right">
              <template v-slot:trigger>
                <div class="uip-icon uip-padding-xxxs uip-text-l hover:uip-background-muted uip-link-muted uip-border-round">more_vert</div>
              </template>
              <template v-slot:content>
                
                <div class="uip-flex uip-flex-column uip-w-200 uip-max-w-200">
                
                  
                  <!-- Update folders -->
                  <div class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xxs" v-if="folder.showEdit">
                  
                    <div class="uip-text-muted">{{strings.folderName}}</div>
                    <input type="text" v-model="folder.title" class="uip-text-s uip-input-small">
                    
                    <div class="uip-text-muted uip-margin-top-xs">{{strings.folderColor}}</div>
                    <div class="uip-background-muted uip-border-round uip-overflow-hidden uip-padding-xxs">
                      <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
                        <color-picker :value="folder.color" :returnData="function(data){ folder.color = data}">
                          <template v-slot:trigger>
                            <div class="uip-border-round uip-w-18 uip-ratio-1-1 uip-border" :style="'background-color:' + folder.color"></div>
                          </template>
                        </color-picker>
                        <input v-model="folder.color" type="text" class="uip-blank-input uip-text-s" style="line-height: 1.2em !important">
                      </div>
                    </div>
                    
                    <button class="uip-button-primary uip-text-s uip-margin-top-s" @click="updateFolder()">{{strings.update}}</button>
                  
                  </div>
                  
                  <div class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xxs uip-flex-start">
                    <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-muted" @click="folder.showEdit = !folder.showEdit">
                      <div class="uip-icon uip-text-l">edit</div>
                      <div class="">{{strings.edit}}</div>
                    </div>
                  </div>
                  
                  <div v-if="folder.canDelete"  class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xxxs uip-flex-start">
                    <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-danger" @click="deleteFolder()">
                      <div class="uip-icon uip-text-l">delete</div>
                      <div class="">{{strings.delete}}</div>
                    </div>
                  </div>
                  
                </div>
                
              </template>
            </drop-down>
            
            
            
            
          </div>
          
        </div> 
        
        <!-- Folder contents -->
        
        <div v-if="folder.open" class="uip-max-w-100p uip-scale-in-top-center">
          
          <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-max-w-100p uip-padding-xxxs uip-margin-bottom-xs uip-margin-left-xs uip-padding-left-xs uip-padding-bottom-remove uip-before-border">
            
            <!--HEADER-->
            <div class="uip-margin-top-xs uip-margin-bottom-xs uip-text-s">
              <new-folder :list="folder.content" :incrementCount="function(e){folder.count += e}" :parent="folder.id"></new-folder>
            </div>
            
            <div class="uip-padding-xs uip-flex uip-flex-middle uip-flex-center" v-if="folder.loading"><loading-chart></loading-chart></div>
              
            <div class="uip-flex uip-flex-column uip-max-w-100p uip-row-gap-xxx">
              <template v-for="(folder, index) in folder.content"  :key="folder.id">
              
                <content-folder :folder="folder" :removeSelf="function(id){deleteFromList(id)}"></content-folder>
              
              </template>
            </div>
            
              
          </div>
          
        </div>
        <!--End folder contents -->
        
      </div>
      
      
      
      `,
  };
}
