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
        loading: false,
        baseFolders: [],
        foldersOpen: true,
        dragging: false,
        allFiles: 0,
        count: 0,
        newFolder: {
          name: '',
          color: 'rgb(108, 76, 203)',
        },
        strings: {
          newFolder: __('New folder', 'uipress-pro'),
          folderName: __('Folder name', 'uipress-pro'),
          folderColor: __('Folder colour', 'uipress-pro'),
          create: __('Create', 'uipress-pro'),
          allItems: __('All items', 'uipress-pro'),
          folders: __('Folders', 'uipress-pro'),
          removeFromFolder: __('Remove from folder', 'uipress-pro'),
        },
      };
    },
    watch: {
      baseFolders: {
        handler(newValue, oldValue) {
          //Mount of wp media library
          if (this.postTypes[0] == 'attachment' && wp.media) {
            this.mountMediaLibrary();
          } else {
            this.mounPostTable();
          }
        },
        deep: true,
      },
    },
    inject: ['uipress', 'postTypes', 'setActiveFolderID', 'getActiveFolderID', 'isDragging', 'setDrag', 'limitToAuthor', 'limitToType', 'filterTableItems'],
    mounted: function () {
      let self = this;
      this.getBaseFolders(true);

      ///

      //Mount of wp media library
      if (this.postTypes[0] == 'attachment' && wp.media) {
        this.mountMediaLibrary();
      } else {
        this.mounPostTable();
      }
      //Watch for changes
      document.addEventListener(
        'uip_update_folder',
        (e) => {
          let id = e.detail.folder_id;
          if (id == 'uipfalse' || id == 'false') {
            self.getBaseFolders();
          }
        },
        { once: false }
      );

      //Watch for dom changes
      document.addEventListener(
        'uip_update_listeners',
        (e) => {
          //Mount of wp media library
          if (self.postTypes[0] == 'attachment' && wp.media) {
            self.mountMediaLibrary();
          } else {
            self.mounPostTable();
          }
        },
        { once: false }
      );
    },
    computed: {},
    methods: {
      mounPostTable() {
        let self = this;
        let postTable = document.querySelector('.wp-list-table');
        if (postTable) {
          postTable.addEventListener('dragstart', (event) => {
            let allIDS = [];

            //First let's check if we are dragging a single file or multiple
            let files = document.querySelectorAll('tbody .check-column input[type=checkbox]:checked');
            if (files && files.length > 0) {
              files.forEach((div) => {
                allIDS.push(div.getAttribute('value'));
              });
            } else {
              let id = event.target.getAttribute('data-id');
              //No id so lets exit
              if (!id) return;
              allIDS.push(id);
            }

            self.setDrag(true);

            let folderID = event.target.getAttribute('data-folder-id');
            //Update event
            event.dataTransfer.setData('itemID', JSON.stringify(allIDS));
            event.dataTransfer.setData('parentFolder', folderID);
            //Create drag image
            self.createDragImage(event, allIDS);
          });
          postTable.addEventListener('dragend', (event) => {
            self.setDrag(false);
          });
        }
      },

      mountMediaLibrary() {
        let self = this;
        let queued_images = 0;
        let uploaded_images = 0;
        let notificationStarted = false;

        //Extend the uploader
        if (wp.Uploader) {
          jQuery.extend(wp.Uploader.prototype, {
            init: function () {
              this.uploader.bind('FileFiltered', function (up, file) {
                queued_images++;
              });
              this.uploader.bind('BeforeUpload', function (uploader, file) {
                let params = uploader.settings.multipart_params;
                params.uip_folder_id = self.getActiveFolderID();
                if (!notificationStarted) {
                  notificationStarted = self.uipress.notify(__('Uploading ') + queued_images + ' ' + __('files'), '', 'success', false, true);
                }
                uploaded_images++;
              });
              this.uploader.bind('UploadComplete', function (up, files) {
                let activeID = self.getActiveFolderID();
                self.setActiveFolderID();
                let eventNew = new CustomEvent('uip_update_folder', { 'detail': { folder_id: activeID } });
                document.dispatchEvent(eventNew);
                self.uipress.destroy_notification(notificationStarted);
                notificationStarted = false;
                queued_images = 0;
                uploaded_images = 0;
              });
            },
          });
        }

        let mediaBrowser = document.querySelector('.attachments-browser');
        if (mediaBrowser) {
          mediaBrowser.addEventListener('dragstart', (event) => {
            let allIDS = [];

            //First let's check if we are dragging a single file or multiple
            let files = document.querySelectorAll('.attachments-browser .attachments .attachment[aria-checked=true]');
            if (files && files.length > 0) {
              files.forEach((div) => {
                allIDS.push(div.getAttribute('data-id'));
              });
            } else {
              let id = event.target.getAttribute('data-id');
              //No id so lets exit
              if (!id) return;
              allIDS.push(id);
            }

            self.setDrag(true);

            let folderID = event.target.getAttribute('data-folder-id');

            event.dataTransfer.setData('itemID', JSON.stringify(allIDS));
            event.dataTransfer.setData('parentFolder', folderID);
            //Create drag image
            self.createDragImage(event, allIDS);
          });
          mediaBrowser.addEventListener('dragend', (event) => {
            self.setDrag(false);
          });
        }
      },
      createDragImage(event, allIDS) {
        let self = this;

        let message = allIDS.length + ' ' + __('item', 'uipress-pro');
        if (allIDS.length > 1) {
          message = allIDS.length + ' ' + __('items', 'uipress-pro');
        }
        var elem = document.createElement('div');
        elem.id = 'uip-content-drag';
        elem.innerHTML = message;
        elem.style.position = 'absolute';
        elem.style.top = '-1000px';
        document.body.appendChild(elem);
        event.dataTransfer.setDragImage(elem, 0, 0);
      },

      getBaseFolders(showLoad) {
        let self = this;

        //Query already running
        if (self.loading) {
          return;
        }

        if (showLoad) {
          self.loading = true;
        }

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_folders_get_base_folders');
        formData.append('security', uip_ajax.security);
        formData.append('limitToAuthor', self.limitToAuthor);
        formData.append('postTypes', JSON.stringify(this.postTypes));
        formData.append('limitToType', self.limitToType);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.loading = false;

          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.baseFolders = response.baseFolders;
            self.allFiles = response.total;
          }
        });
      },
      updateItemFolder(item) {
        let self = this;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_folders_update_item_folder');
        formData.append('security', uip_ajax.security);
        formData.append('item', JSON.stringify(item));
        formData.append('newParent', 'uipfalse');

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            item.parent = 'uipfalse';
          }
        });
      },
      itemAdded(evt) {
        let self = this;
        if (evt.added) {
          if (evt.added.element.type !== 'uip-ui-folder') {
            this.baseFolders.splice(evt.added.newIndex, 1);
            this.uipress.notify(__('Item removed from folder', 'uipress-pro'), '', 'error');
          }
          //CHECK IF ITEM ALREADY EXISTS IN FOLDER
          let index = this.baseFolders.filter((x) => x.id === evt.added.element.id);
          //It exists so remove it
          if (index.length > 1) {
            this.baseFolders.splice(evt.added.newIndex, 1);
            return;
          }

          this.baseFolders.sort(function (a, b) {
            let textA = a.title.toUpperCase();
            let textB = b.title.toUpperCase();
            return textA < textB ? -1 : textA > textB ? 1 : 0;
          });

          self.updateItemFolder(evt.added.element);
        }
      },
      removeFilters() {
        let self = this;
        //Set global folder
        self.setActiveFolderID('all');
      },
      addDropClass(evt, folder) {
        evt.preventDefault();

        let target = evt.target.closest('.uip-remove-drop');
        target.classList.add('uip-border-dashed-emphasis');
        this.counter++;
      },

      removeDropClass(evt, folder) {
        evt.preventDefault();
        this.counter--;
        if (this.counter === 0) {
          let target = evt.target.closest('.uip-remove-drop');
          target.classList.remove('uip-border-dashed-emphasis');
        }
      },
      addToFolder(evt, folder) {
        let self = this;

        //Remove classes
        let target = evt.target.closest('.uip-remove-drop');
        target.classList.remove('uip-border-dashed-emphasis');
        self.counter = 0;

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

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_folders_add_item_to_folder');
        formData.append('security', uip_ajax.security);
        formData.append('IDS', JSON.stringify(itemIDs));
        formData.append('newParent', 'uipfalse');
        formData.append('parentFolder', parentFolder);
        formData.append('postTypes', JSON.stringify(this.postTypes));
        formData.append('limitToType', self.limitToType);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.uipress.notify(__('Item removed from folder', 'uipress-pro'), '', 'success', true);

            if (response.folder) {
              self.baseFolders.push(response.folder);
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
        let index = this.baseFolders.findIndex((item) => item.id === id);
        this.baseFolders.splice(index, 1);
      },
    },
    template: `
		<!-- Loop through top level folders -->
    <div class="uip-max-w-100p uip-padding-s uip-background-default uip-border-round uip-flex uip-flex-column uip-row-gap-xs uip-transition-all" 
    :class="{'uip-padding-remove uip-border' : !foldersOpen}">
    
      <div @click="foldersOpen = !foldersOpen"
      class="uip-padding-xs uip-border-round uip-background-muted uip-flex uip-flex-between uip-flex-center hover:uip-background-grey uip-cursor-pointer">
        <div v-if="foldersOpen">{{strings.folders}}</div>
        <div v-if="foldersOpen" class="uip-icon uip-text-l">expand_more</div>
        <div v-if="!foldersOpen" class="uip-icon uip-text-l">folder</div>
      </div>
      
      <template v-if="foldersOpen">
      
        <div @click="removeFilters()" class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-default uip-padding-xxs uip-padding-top-xxxs uip-padding-bottom-xxxs hover:uip-background-muted uip-border-round uip-no-text-select uip-margin-top-xs"  :class="{'uip-background-muted' : getActiveFolderID() == 'all'}">
          <div class="uip-icon uip-text-l">apps</div>
          <div class="uip-flex-grow">{{strings.allItems}}</div>
          <div class="uip-text-muted">{{allFiles}}</div>
          <div class="uip-position-relative uip-opacity-0">
            <div class="uip-flex">
                <div class="uip-icon uip-padding-xxxs uip-text-l hover:uip-background-muted uip-link-muted uip-border-round">more_vert</div>
            </div>
          </div>
        </div>
      
      
      
        <div class="uip-padding-xs uip-flex uip-flex-middle uip-flex-center" v-if="foldersOpen && loading"><loading-chart></loading-chart></div>
          
        <div class="uip-flex uip-flex-column uip-max-w-100p uip-w-300 uip-row-gap-xxxs">
          <template v-for="(folder, index) in baseFolders"  :key="folder.id">
          
            <content-folder :folder="folder" :removeSelf="function(id){deleteFromList(id)}"></content-folder>
          
          </template>
        </div>
        
        <!--Remove from folder-->
        <div 
        @drop="addToFolder($event, folder)" 
        @dragenter="addDropClass($event, folder)"
        @dragleave="removeDropClass($event, folder)"
        @dragover.prevent
        @dragenter.prevent
        v-if="isDragging()" class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-default uip-padding-xxs hover:uip-background-muted uip-border-round uip-no-text-select uip-margin-top-xs  uip-background-muted uip-remove-drop">
          <div class="uip-flex-grow uip-text-muted">{{strings.removeFromFolder}}</div>
        </div>
        
        <!--FOOTER-->
        <div class="uip-margin-top-xs">
          <new-folder :list="baseFolders" parent="uipfalse"></new-folder>
        </div>
        
      
      </template>
      
    </div>
		`,
  };
}
