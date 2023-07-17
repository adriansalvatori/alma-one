const { __, _x, _n, _nx } = wp.i18n;
import '../../../libs/sortable.js';
import '../../../libs/vue-draggable.js';
export function moduleData() {
  return {
    components: {
      'uip-draggable': vuedraggable,
      'content-folder': contentFolder,
      'new-folder': newFolder,
    },
    props: {
      display: String,
      name: String,
      block: Object,
    },
    data: function () {
      return {
        loading: false,
        defaults: [],
        currentID: false,
        baseFolders: [],
        limitToauthor: this.uipress.get_block_option(this.block, 'block', 'limitToAuthor'),
        strings: {
          placeholder: __('Input placeholder...', 'uipress-pro'),
          new: __('New', 'uipress-pro'),
          loadMore: __('Load more', 'uipress-pro'),
          search: __('Search', 'uipress-pro'),
          view: __('View', 'uipress-pro'),
          edit: __('Edit', 'uipress-pro'),
          duplicate: __('Duplicate', 'uipress-pro'),
          delete: __('Delete', 'uipress-pro'),
          folders: __('Folders', 'uipress-pro'),
          newFolder: __('New folder', 'uipress-pro'),
          folderName: __('Folder name', 'uipress-pro'),
          folderColor: __('Folder colour', 'uipress-pro'),
          create: __('Create', 'uipress-pro'),
        },
      };
    },
    provide() {
      return {
        limitToauthor: this.limitToauthor,
        currentID: this.currentID,
        defaultLinkType: this.getDefaultLinkType,
        postTypes: this.getPostTypes,
      };
    },
    watch: {},
    inject: ['uipData', 'uipress', 'uiTemplate'],
    mounted: function () {
      this.getDefaults();
    },
    computed: {
      getDefaultLinkType() {
        let pos = this.uipress.get_block_option(this.block, 'block', 'defaultLink');
        if (!pos) {
          return 'editPost';
        } else {
          if ('value' in pos) {
            return pos.value;
          } else {
            return pos;
          }
        }
      },
      getPostTypes() {
        let types = this.uipress.get_block_option(this.block, 'block', 'searchPostTypes');
        return types;
      },
      returnCurrentID() {
        return JSON.stringify(this.currentID);
      },
    },
    methods: {
      //gets default folders ('post types')
      getDefaults() {
        let self = this;

        //Query already running
        if (self.loading) {
          return;
        }

        self.loading = true;
        let postTypes = [];
        if (typeof self.getPostTypes != 'undefined') {
          if (Array.isArray(self.getPostTypes)) {
            postTypes = self.getPostTypes;
          }
        }

        postTypes = JSON.stringify(postTypes);
        let limitToauthor = self.uipress.get_block_option(this.block, 'block', 'limitToAuthor');

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_get_navigator_defaults');
        formData.append('security', uip_ajax.security);
        formData.append('limitToauthor', limitToauthor);
        formData.append('postTypes', postTypes);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.loading = false;
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.baseFolders = response.baseFolders;
            self.defaults = response.postTypes;
          }
        });
      },
      //Open default folder and get contents
      getDefaultContent(type, showLoad) {
        //Its closed so we don't need to fetch content
        if (!type.open) {
          return;
        }

        if (!('page' in type)) {
          type.page = 1;
        }

        if (showLoad) {
          type.loading = true;
        }

        let self = this;
        let limitToauthor = self.uipress.get_block_option(this.block, 'block', 'limitToAuthor');
        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_get_default_content');
        formData.append('security', uip_ajax.security);
        formData.append('limitToauthor', limitToauthor);
        formData.append('postType', type.type);
        formData.append('page', type.page);
        formData.append('search', type.search);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          type.loading = false;

          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            type.totalFound = response.totalFound;
            if (type.page > 1) {
              type.content = type.content.concat(response.content);
            } else {
              type.content = response.content;
            }
          }
        });
      },
      //Open default folder and get contents
      updateItemFolder(item) {
        let self = this;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_update_item_folder');
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
      duplicateItem(item, index, list) {
        let self = this;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_duplicate_post');
        formData.append('security', uip_ajax.security);
        formData.append('postID', item.id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.uipress.notify(__('Item duplicated', 'uipress-pro'), '', 'sucess', true);
            let newItem = JSON.parse(JSON.stringify(item));
            newItem.id = response.newID;
            newItem.title = response.newTitle;
            newItem.status = 'draft';
            list.splice(index, 0, newItem);
          }
        });
      },
      deleteItem(item, index, list) {
        let self = this;
        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_delete_post_from_folder');
        formData.append('security', uip_ajax.security);
        formData.append('postID', item.id);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          console.log(response);
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
          }
          if (response.success) {
            self.uipress.notify(__('Item deleted', 'uipress-pro'), '', 'sucess', true);
            list.content.splice(index, 1);
            list.totalFound -= 1;
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
      returnClasses() {
        let classes = '';

        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
      updatePage(item) {
        this.currentID = item.id;
        if (this.getDefaultLinkType == 'editPost') {
          this.uipress.updatePage(item.edit_href);
        } else {
          this.uipress.updatePage(item.view_href);
        }
      },
      newPostType(type) {
        this.currentID = false;
        this.uipress.updatePage(type.new_href);
      },
      setDragAreaClasses() {
        let returnData = [];
        returnData.class = 'uip-flex uip-flex-column uip-row-gap-xxs uip-max-w-100p uip-max-h-400 uip-overflow-auto';

        return returnData;
      },
      setBaseFolderClass() {
        let returnData = [];
        returnData.class = 'uip-flex uip-flex-column uip-max-w-100p';

        return returnData;
      },
      loadMore(type) {
        type.page += 1;
        this.getDefaultContent(type);
      },
      checkForBlank(type) {
        if (type.search == '') {
          type.page = 1;
          this.getDefaultContent(type, true);
        }
      },
    },
    template: `
    
    <div :id="block.uid" :class="returnClasses()">
    
      <div class="uip-padding-s uip-flex uip-flex-middle uip-flex-center" v-if="loading"><loading-chart></loading-chart></div>
      
      <div class="uip-flex uip-flex-column uip-max-w-100p" v-else>
        <template v-for="type in defaults">
        
          <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-max-w-100p">
          
            <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-default uip-padding-xxs hover:uip-background-muted uip-border-round uip-no-text-select" 
            :class="type.open ? 'uip-background-muted' : ''" @click="type.open = !type.open;getDefaultContent(type, true)">
              <div class="uip-icon uip-text-l">database</div>
              <div class="uip-flex-grow">{{type.label}}</div>
              <div class="uip-text-muted">{{type.count}}</div>
            </div>
            
            <div v-if="type.open" class="uip-max-w-100p uip-scale-in-top-center">
              
              
              <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-max-w-100p uip-padding-xxxs uip-margin-bottom-xs uip-margin-left-s">
                
                <!-- Search post types -->
                <div v-if="type.count > 10" class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-no-text-select uip-max-w-100p">
                  <div class="uip-w-5 uip-ratio-1-1 uip-position-relative"><div class="uip-icon uip-position-absolute uip-top-50p uip-left-50p uip-translate-all-50p">search</div></div>
                  <input class="uip-text-s uip-blank-input uip-flex-grow" :placeholder="strings.search" v-model="type.search" @keyup="checkForBlank(type)" v-on:keyup.enter="type.page = 1; getDefaultContent(type, true)">
                  <div class="uip-icon uip-padding-xxxs uip-text-l uip-text-muted uip-border-round">keyboard_return</div>
                </div>
                
                <!-- new post type -->
                <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-default uip-border-round uip-no-text-select uip-max-w-100p" @click="newPostType(type)">
                  <div class="uip-w-5 uip-ratio-1-1 uip-position-relative"><div class="uip-icon uip-position-absolute uip-top-50p uip-left-50p uip-translate-all-50p">add</div></div>
                  <div class="uip-overflow-hidden uip-text-ellipsis uip-no-wrap uip-flex-grow">{{strings.new}} {{type.name}}</div>
                </div>
                
                <div class="uip-padding-s uip-flex uip-flex-middle uip-flex-center" v-if="type.loading"><loading-chart></loading-chart></div>
                
                <!-- Loop through type content -->
                <uip-draggable v-else 
                v-model="type.content" 
                :component-data="setDragAreaClasses()"
                :group="{ name: 'post-defaults', pull: 'clone', put: false, revertClone: true }"
                @start="drag = true" 
                @end="drag = false" 
                animation="300"
                :sort="false"
                itemKey="id">
                  <template #item="{element: item, index}">
                  
                    <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-border-round uip-no-text-select uip-max-w-100p"
                    :class="currentID == item.id ? 'uip-cursor-pointer' : 'uip-link-default'">
                      
                      <div v-if="1==2" class="uip-cursor-pointer uip-background-muted uip-icon uip-border-round uip-block-drag">drag_indicator</div>
                      
                      <!-- Post status -->
                      <div v-if="item.status == 'draft'" class="uip-w-5 uip-ratio-1-1 uip-border-circle uip-background-orange uip-display-block"></div>
                      <div v-else-if="item.status == 'publish' || item.status == 'inherit'" class="uip-w-5 uip-ratio-1-1 uip-border-circle uip-background-green uip-display-block"></div>
                      <div v-else class="uip-w-5 uip-ratio-1-1 uip-border-circle uip-background-accent uip-display-block"></div>
                      
                      <div class="uip-overflow-hidden uip-text-ellipsis uip-no-wrap uip-flex-grow" @click="updatePage(item)" :class="currentID == item.id ? 'uip-text-accent' : ''">{{item.title}}</div>
                      
                      <drop-down dropPos="right">
                        <template v-slot:trigger>
                          <div class="uip-icon uip-padding-xxxs uip-text-l hover:uip-background-muted uip-link-muted uip-border-round">more_vert</div>
                        </template>
                        <template v-slot:content>
                          
                          <div class="uip-flex uip-flex-column uip-w-200 uip-max-w-200">
                            
                            <div class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xs uip-flex-start">
                              <div class="uip-padding-xxs uip-border-round uip-text-xs uip-background-primary-wash uip-line-height-1">{{item.type}}</div>
                              <div class="">{{item.title}}</div>
                            </div>
                            
                            <div class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xxs uip-flex-start">
                              <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-muted" @click="currentID = item.id;uipress.updatePage(item.view_href)">
                                <div class="uip-icon uip-text-l">visibility</div>
                                <div class="">{{strings.view}}</div>
                              </div>
                              <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-muted" @click="currentID = item.id;uipress.updatePage(item.edit_href)">
                                <div class="uip-icon uip-text-l">edit</div>
                                <div class="">{{strings.edit}}</div>
                              </div>
                              <div v-if="item.type != 'attachment'" class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-muted" @click="duplicateItem(item, index, type.content)">
                                <div class="uip-icon uip-text-l">content_copy</div>
                                <div class="">{{strings.duplicate}}</div>
                              </div>
                            </div>
                            
                            <div v-if="item.canDelete" class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xxxs uip-flex-start">
                              <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-danger" @click="deleteItem(item, index, type)">
                                <div class="uip-icon uip-text-l">delete</div>
                                <div class="">{{strings.delete}}</div>
                              </div>
                            </div>
                            
                          </div>
                          
                        </template>
                      </drop-down>
                      
                    </div>
                  
                  </template>
                  
                </uip-draggable>
                
                <div v-if="type.content.length < type.totalFound" class="uip-padding-right-xs">
                  <div class="uip-text-s uip-link-muted uip-border-round uip-padding-xxs uip-padding-left-remove uip-display-inline-flex" @click="loadMore(type)">{{strings.loadMore}}</div>
                </div>
                  
              </div>
              
            </div>
          
          </div>
          
        </template>
        
        <!-- End base folders -->
        
        <!-- User folders -->
        
        <div class="uip-text-muted uip-padding-xxs uip-margin-bottom-xxxs uip-margin-top-xxs">{{strings.folders}}</div>
        
        <!-- Loop through top level folders -->
        <div class="uip-max-w-100p">
        
          <uip-draggable 
          v-model="baseFolders" 
          :component-data="setBaseFolderClass()"
          :group="{ name: 'user-folders', pull: true, put: true }"
          @start="drag = true" 
          @end="drag = false" 
          @change="itemAdded"
          animation="300"
          :sort="false"
          itemKey="id">
          
            <template #item="{element: folder, index}">
            
              <content-folder :folder="folder" :removeSelf="function(){baseFolders.splice(index, 1)}" :currentID="currentID" :updateID="function(d){currentID = d}"></content-folder>
            
            </template>
            
            <!--FOOTER-->
            <template #footer >
              <new-folder :list="baseFolders" parent="uipfalse"></new-folder>
            </template>
          
          </uip-draggable>
        
        </div>
        
      </div>
      
    </div>
    
    
    
    `,
  };
}

let newFolder = {
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
  inject: ['uipress'],
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
      formData.append('action', 'uip_create_folder');
      formData.append('security', uip_ajax.security);
      formData.append('folderParent', self.parent);
      formData.append('folderName', self.newFolder.name);
      formData.append('folderColor', self.newFolder.color);

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

let contentFolder = {
  name: 'content-folder',
  components: {
    'uip-draggable': vuedraggable,
    'new-folder': newFolder,
  },
  props: {
    folder: Object,
    removeSelf: Function,
    currentID: [Number, String, Boolean],
    updateID: Function,
  },
  data: function () {
    return {
      loading: false,
      defaults: [],
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
  inject: ['uipress', 'limitToauthor', 'defaultLinkType', 'postTypes'],
  mounted: function () {},
  computed: {},
  methods: {
    //Open default folder and get contents
    getFolderContent(showLoad) {
      let self = this;
      //Its closed so we don't need to fetch content
      if (!self.folder.open) {
        return;
      }

      if (!('page' in self.folder)) {
        self.folder.page = 1;
      }

      if (showLoad) {
        self.folder.loading = true;
      }

      //Build form data for fetch request
      let formData = new FormData();
      formData.append('action', 'uip_get_folder_content');
      formData.append('security', uip_ajax.security);
      formData.append('limitToauthor', self.limitToauthor);
      formData.append('postTypes', JSON.stringify(self.postTypes));
      formData.append('page', self.folder.page);
      formData.append('search', self.folder.search);
      formData.append('id', self.folder.id);

      self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
        self.folder.loading = false;

        if (response.error) {
          self.uipress.notify(response.message, '', 'error', true);
        }
        if (response.success) {
          self.folder.totalFound = response.totalFound;
          if (self.folder.page > 1) {
            self.folder.content = self.folder.content.concat(response.content);
          } else {
            self.folder.content = response.content;
          }
        }
      });
    },
    //Open default folder and get contents
    updateItemFolder(item) {
      let self = this;

      //Build form data for fetch request
      let formData = new FormData();
      formData.append('action', 'uip_update_item_folder');
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
      formData.append('action', 'uip_delete_folder');
      formData.append('security', uip_ajax.security);
      formData.append('postTypes', JSON.stringify(self.postTypes));
      formData.append('folderId', self.folder.id);

      self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
        self.folder.loading = false;

        if (response.error) {
          self.uipress.notify(response.message, '', 'error', true);
        }
        if (response.success) {
          self.removeSelf();
          self.uipress.notify(__('Folder deleted', 'uipress-pro'), '', 'sucess', true);
        }
      });
    },
    updateFolder() {
      let self = this;

      //Build form data for fetch request
      let formData = new FormData();
      formData.append('action', 'uip_update_folder');
      formData.append('security', uip_ajax.security);
      formData.append('folderId', self.folder.id);
      formData.append('title', self.folder.title);
      formData.append('color', self.folder.color);

      self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
        if (response.error) {
          self.uipress.notify(response.message, '', 'error', true);
        }
        if (response.success) {
          self.uipress.notify(__('Folder updated', 'uipress-pro'), '', 'sucess', true);
          self.folder.showEdit = false;
        }
      });
    },
    duplicateItem(item, index) {
      let self = this;

      //Build form data for fetch request
      let formData = new FormData();
      formData.append('action', 'uip_duplicate_post');
      formData.append('security', uip_ajax.security);
      formData.append('postID', item.id);

      self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
        if (response.error) {
          self.uipress.notify(response.message, '', 'error', true);
        }
        if (response.success) {
          self.uipress.notify(__('Item duplicated', 'uipress-pro'), '', 'sucess', true);
          let newItem = JSON.parse(JSON.stringify(item));
          newItem.id = response.newID;
          newItem.title = response.newTitle;
          newItem.draft = 'draft';
          self.folder.content.splice(index, 0, newItem);
        }
      });
    },
    deleteItem(item, index) {
      let self = this;
      //Build form data for fetch request
      let formData = new FormData();
      formData.append('action', 'uip_delete_post_from_folder');
      formData.append('security', uip_ajax.security);
      formData.append('postID', item.id);

      self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
        if (response.error) {
          self.uipress.notify(response.message, '', 'error', true);
        }
        if (response.success) {
          self.uipress.notify(__('Item deleted', 'uipress-pro'), '', 'sucess', true);
          self.folder.content.splice(index, 1);
          self.folder.totalFound -= 1;
          self.folder.count -= 1;
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
    updatePage(item) {
      this.updateID(item.id);
      if (this.defaultLinkType == 'editPost') {
        this.uipress.updatePage(item.edit_href);
      } else {
        this.uipress.updatePage(item.view_href);
      }
    },
    setDragAreaClasses() {
      let returnData = [];
      returnData.class = 'uip-flex uip-flex-column uip-row-gap-xxs uip-max-w-100p uip-max-h-600 uip-overflow-auto';

      return returnData;
    },
    setBaseFolderClass() {
      let returnData = [];
      returnData.class = 'uip-flex uip-flex-column uip-max-w-100p uip-max-h-600 uip-overflow-auto';

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
  },
  template: `
    
    <div :data-id="folder.id">
  
  
      <!-- top folder -->
      <div class="uip-flex uip-flex-column uip-row-gap-xxs uip-max-w-100p">
      
        <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-default uip-padding-xxs uip-padding-top-xxxs uip-padding-bottom-xxxs hover:uip-background-muted uip-border-round uip-no-text-select" 
        :class="folder.open ? 'uip-background-muted' : ''">
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
          
          
          <div class="uip-padding-s uip-flex uip-flex-middle uip-flex-center" v-if="folder.loading"><loading-chart></loading-chart></div>
          
          <!-- Loop through type content -->
          <uip-draggable v-else 
          v-model="folder.content" 
          :component-data="setBaseFolderClass()"
          :group="{ name: 'post-defaults', pull: true, put: true, revertClone: true }"
          @start="drag = true" 
          @end="drag = false" 
          @change="itemAdded"
          animation="300"
          :sort="false"
          itemKey="id">
          
          
            <!--FOOTER-->
            <template #header >
            
              <!-- Search post types -->
              <div v-if="folder.count > 10" class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-text-muted uip-padding-xxs uip-padding-top-xxxs uip-padding-bottom-xxxs">
                <div class="uip-icon uip-text-l">search</div>
                <input class="uip-text-s uip-blank-input uip-flex-grow" :placeholder="strings.search" v-model="folder.search" @keyup="checkForBlank(folder)" v-on:keyup.enter="folder.page = 1; getFolderContent( true)">
                <div class="uip-icon uip-padding-xxxs uip-text-l uip-text-muted">keyboard_return</div>
              </div>
              
              <new-folder :list="folder.content" :incrementCount="function(e){folder.count += e}" :parent="folder.id"></new-folder>
            
            </template>
            
            
            <template #item="{element: item, index}">
            
              <content-folder v-if="item.type == 'uip-ui-folder'" :folder="item" :removeSelf="function(){folder.content.splice(index, 1)}" :currentID="currentID" :updateID="updateID"></content-folder>
            
              <div v-else class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-border-round uip-no-text-select uip-max-w-100p uip-padding-left-xxs uip-padding-right-xxs"
              :class="currentID == item.id ? 'uip-cursor-pointer' : 'uip-link-default'">
                
                
                <!-- Post status -->
                <div class="uip-flex uip-flex-center uip-flex-middle uip-w-16">
                  <div v-if="item.status == 'draft'" class="uip-w-5 uip-ratio-1-1 uip-border-circle uip-background-orange uip-display-block"></div>
                  <div v-else-if="item.status == 'publish' || item.status == 'inherit'" class="uip-w-5 uip-ratio-1-1 uip-border-circle uip-background-green uip-display-block"></div>
                  <div v-else class="uip-w-5 uip-ratio-1-1 uip-border-circle uip-background-accent uip-display-block"></div>
                </div>
                
                <div class="uip-overflow-hidden uip-text-ellipsis uip-no-wrap uip-flex-grow" @click="updatePage(item)" :class="currentID == item.id ? 'uip-text-accent' : ''">{{item.title}}</div>
                
                <drop-down dropPos="right">
                  <template v-slot:trigger>
                    <div class="uip-icon uip-padding-xxxs uip-text-l hover:uip-background-muted uip-link-muted uip-border-round">more_vert</div>
                  </template>
                  <template v-slot:content>
                    
                    <div class="uip-flex uip-flex-column uip-w-200 uip-max-w-200">
                      
                      <div class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xs uip-flex-start">
                        <div class="uip-padding-xxs uip-border-round uip-text-xs uip-background-primary-wash uip-line-height-1">{{item.type}}</div>
                        <div class="">{{item.title}}</div>
                      </div>
                      
                      <div class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xxs uip-flex-start">
                        <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-muted" @click="updateID(item.id);uipress.updatePage(item.view_href)">
                          <div class="uip-icon uip-text-l">visibility</div>
                          <div class="">{{strings.view}}</div>
                        </div>
                        <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-muted" @click="updateID(item.id);uipress.updatePage(item.edit_href)">
                          <div class="uip-icon uip-text-l">edit</div>
                          <div class="">{{strings.edit}}</div>
                        </div>
                        <div v-if="item.type != 'attachment'" class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-muted" @click="duplicateItem(item, index)">
                          <div class="uip-icon uip-text-l">content_copy</div>
                          <div class="">{{strings.duplicate}}</div>
                        </div>
                      </div>
                      
                      <div v-if="item.canDelete" class="uip-padding-xs uip-border-bottom uip-flex uip-flex-column uip-gap-xxxs uip-flex-start">
                        <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center uip-link-danger" @click="deleteItem(item, index)">
                          <div class="uip-icon uip-text-l">delete</div>
                          <div class="">{{strings.delete}}</div>
                        </div>
                      </div>
                      
                    </div>
                    
                  </template>
                </drop-down>
                
              </div>
            
            </template>
            
            <!--FOOTER-->
            <template #footer >
            
              <div v-if="folder.content.length > 1 && folder.content.length < folder.totalFound" class="uip-padding-right-xs">
                <div class="uip-text-s uip-link-muted uip-border-round uip-padding-xxs uip-padding-left-remove uip-display-inline-flex" @click="loadMore(folder)">{{strings.loadMore}}</div>
              </div>
              
            </template>
            
            
            
          </uip-draggable>
            
        </div>
        
      </div>
      <!--End folder contents -->
      
    </div>
    
    
    
    `,
};
