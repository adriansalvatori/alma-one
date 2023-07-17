const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: Array,
    },
    data: function () {
      return {
        menu: this.uipData.adminMenu.menu,
        OGmenu: this.uipData.adminMenu.menu,
        selected: this.value,
        strings: {
          renameItem: __('Rename item', 'uipress-pro'),
          openMenuEditor: __('Open menu editor', 'uipress-pro'),
          itemName: __('Item name', 'uipress-pro'),
          separator: __('Separator', 'uipress-pro'),
          itemVisibility: __('Item visibiity', 'uipress-pro'),
          toggleSubmenu: __('Show submenu', 'uipress-pro'),
          addToMenu: __('Add to menu', 'uipress-pro'),
          newSeparator: __('New separator', 'uipress-pro'),
          newLink: __('New link', 'uipress-pro'),
          deleteItem: __('Delete item', 'uipress-pro'),
          advancedMenuEditor: __('Advanced menu editor', 'uipress-pro'),
          advanced: __('Advanced', 'uipress-pro'),
          customClasses: __('Custom classes', 'uipress-pro'),
          resetToDefault: __('Reset to default', 'uipress-pro'),
          openInNewTab: __('Open in a new tab?', 'uipress-pro'),
          withoutFrame: __('Open outside frame', 'uipress-pro'),
          withoutUiPress: __('Open without UiPress', 'uipress-pro'),
        },
      };
    },
    inject: ['uipData', 'uipress'],
    watch: {
      menu: {
        handler(newValue, oldValue) {
          if (typeof this.returnData === 'undefined') {
            return;
          }
          this.returnData(this.menu);
        },
        deep: true,
      },
    },

    mounted: function () {
      this.setCustomMenu();
    },
    methods: {
      setCustomMenu() {
        if (typeof this.value === 'undefined') {
          return;
        }
        if (!Array.isArray(this.value)) {
          return;
        }
        if (this.value.length === 0) {
          return;
        } else {
          this.menu = this.value;
        }
      },
      returnIcon(id) {
        if (!this.uipress.isObject(this.selected)) {
          this.selected[id] = {};
          this.selected[id].icon = {};
          this.selected[id].title = '';
          return '';
        }
        if (Object.hasOwn(this.selected, id)) {
          return this.selected[id].icon;
        } else {
          this.selected[id] = {};
          this.selected[id].icon = {};
          this.selected[id].title = '';
          return '';
        }
      },
      returnTitle(id) {
        if (!this.uipress.isObject(this.selected)) {
          this.selected[id] = {};
          this.selected[id].icon = {};
          this.selected[id].title = '';
          return '';
        }
        if (Object.hasOwn(this.selected, id)) {
          return this.selected[id].title;
        } else {
          this.selected[id] = {};
          this.selected[id].icon = {};
          this.selected[id].title = '';
          return '';
        }
      },
      setdropAreaStyles() {
        let returnData = [];
        returnData.class = 'uip-flex uip-flex-column uip-row-gap-xs';
        return returnData;
      },
      newMenuItem(type, list, submenu) {
        let newItem = {
          name: __('Custom menu item', 'uipress-pro'),
          icon: 'favorite',
          url: '',
          submenu: [],
          uid: this.uipress.createUID(),
          custom: true,
        };

        if (type == 'sep') {
          newItem.type = 'sep';
          newItem.name = '';
        }
        if (submenu) {
          if (!Array.isArray(list.submenu) || typeof list.submenu === 'undefined') {
            list.submenu = [newItem];
          }
        } else {
          list.push(newItem);
        }
      },
      deleteItem(item, index, list) {
        if (!item.custom) {
          return;
        }
        list.splice(index, 1);
      },
      returnFormattedIcon(icon) {
        if (typeof icon === 'undefined' || !icon) {
          return '';
        }
        if (icon.includes('uipblank')) {
          return icon.replace('uipblank', '');
        }

        return icon;
      },
    },
    template: `
    
    <uip-offcanvas style='width:auto;'>
      
      <template v-slot:trigger>
        <button class="uip-button-default uip-flex uip-flex-row uip-gap-xxs">
          <span class="uip-icon">edit_note</span>
          <span>{{strings.openMenuEditor}}</span>
        </button>
      </template>
      
      <template v-slot:content>
          
          <div class="">
          
            <div class="uip-text-bold uip-text-l uip-margin-bottom-m uip-flex uip-flex-row uip-gap-xs uip-flex-center">
              <span class="uip-icon uip-text-l">edit_note</span>
              <span>{{strings.advancedMenuEditor}}</span>
            </div>
          
            <draggable 
            v-model="menu" 
            :group="{ name: 'menuItems', pull: true, put: true }"
            :component-data="setdropAreaStyles()"
            @start="drag=true"
            @end="drag=false"
            handle=".uip-drag-handle"
            :sort="true"
            animation="300"
            itemKey="uid">
              <template #item="{element: parent, index}">
              
                <div>
                  <!-- top level items-->
                  <div class="uip-flex uip-flex-row uip-gap-s uip-flex-center  uip-background-muted uip-border-round uip-padding-xs uip-w-400">
                    
                    <div class="uip-icon uip-drag-handle uip-cursor-drag uip-padding-xxxs uip-border-round uip-background-grey">drag_indicator</div>
                    
                    <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-grow" :class="{'uip-opacity-20' : parent.hidden}" >
                    
                      <inline-icon-select :value="{value: parent.icon}" :returnData="function(e){parent.icon = e.value}">
                        <template v-slot:trigger>
                          <div class="uip-padding-xxxs uip-w-14 uip-ratio-1-1 uip-border uip-border-round">
                            <div class="uip-icon" v-html="returnFormattedIcon(parent.icon)"></div>
                          </div>
                        </template>
                      </inline-icon-select>
                      <div class=" uip-flex uip-flex-row uip-gap-xxxs">
                        <input class="uip-blank-input" type="text" v-model="parent.name" :placeholder="strings.itemName">
                        <div v-if="parent.type == 'sep'" class="uip-text-s uip-padding-xxxs uip-border-round uip-background-orange-wash">{{strings.separator}}</div>
                      </div>
                      
                    </div>
                    
                    <!--Item options -->
                    <div class="uip-flex uip-flex-row uip-gap-xxs">
                      <drop-down v-if="parent.type != 'sep'" dropPos="left">
                        <template v-slot:trigger>
                          <div class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l">link</div>
                        </template>
                        <template v-slot:content>
                          <div class="uip-padding-xxs">
                            <input class="uip-input uip-input-small" type="text" v-model="parent.url">
                          </div>
                        </template>
                      </drop-down>
                      
                      <uip-tooltip :message="strings.itemVisibility">
                        <div @click="parent.hidden = !parent.hidden" class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l uip-cursor-pointer"
                        :class="{'uip-text-danger' : parent.hidden}" >visibility</div>
                      </uip-tooltip>
                      
                     
                      <drop-down dropPos="left">
                        <template v-slot:trigger>
                          <uip-tooltip :message="strings.advanced">
                            <div class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l">code</div>
                          </uip-tooltip>
                        </template>
                        <template v-slot:content>
                          <div class="uip-flex uip-flex-column uip-border-bottom">
                            <div class="uip-padding-xs">
                              <div class="uip-text-s uip-text-muted uip-margin-bottom-xxs">{{strings.customClasses}}</div>
                              <input class="uip-input uip-input-small" type="text" v-model="parent.customClasses">
                            </div>
                          </div>
                          <div class="uip-flex uip-flex-column uip-row-gap-xs uip-padding-xs">
                          
                            <label class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-flex-between">
                              <div class="uip-text-s uip-text-muted">{{strings.openInNewTab}}</div>
                              <input class="uip-checkbox" type="checkbox" v-model="parent.newTab">
                            </label>
                            
                            <label class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-flex-between">
                              <div class="uip-text-s uip-text-muted">{{strings.withoutFrame}}</div>
                              <input class="uip-checkbox" type="checkbox" v-model="parent.withoutFrame">
                            </label>
                            
                            <label class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-flex-between">
                              <div class="uip-text-s uip-text-muted">{{strings.withoutUiPress}}</div>
                              <input class="uip-checkbox" type="checkbox" v-model="parent.withoutUiPress">
                            </label>
                            
                          </div>
                        </template>
                      </drop-down>
                      
                      <div v-if="parent.type != 'sep'" class="uip-border-left uip-margin-left-xxs uip-margin-right-xxs"></div>
                      
                      
                      <uip-tooltip v-if="parent.type != 'sep'" :message="strings.toggleSubmenu">
                        <div  v-if="!parent.subOpen" @click="parent.subOpen = !parent.subOpen" class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l uip-cursor-pointer">chevron_left</div>
                        <div v-else @click="parent.subOpen = !parent.subOpen" class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l uip-cursor-pointer">expand_more</div>
                      </uip-tooltip>
                      
                      <uip-tooltip v-if="parent.custom" :message="strings.deleteItem">
                        <div  @click="deleteItem(parent, index, menu)" class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l uip-cursor-pointer uip-link-danger">delete</div>
                      </uip-tooltip>
                    </div>
                    <!--End item options -->
                    
                  </div>
                  <!--End top level items-->
                  
                  
                  
                  
                  
                  
                  <!-- SUB LEVEL ITEMS -->
                  <div class="uip-margin-left-m uip-margin-top-s uip-margin-bottom-m uip-scale-in" v-if="parent.type != 'sep' && parent.subOpen">
                    
                    <draggable 
                    v-model="parent.submenu" 
                    :group="{ name: 'menuItems', pull: true, put: true }"
                    :component-data="setdropAreaStyles()"
                    @start="drag=true"
                    @end="drag=false"
                    handle=".uip-drag-handle"
                    :sort="true"
                    animation="300"
                    itemKey="uid">
                    
                      <template #item="{element: subitem, index}">
                        
                        
                          <div class="uip-flex uip-flex-row uip-gap-s uip-flex-center hover:uip-background-muted uip-border-round uip-padding-xxs">
                            
                            <div class="uip-icon uip-drag-handle uip-cursor-drag uip-padding-xxxs uip-border-round uip-background-grey">drag_indicator</div>
                            
                            <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-grow" :class="{'uip-opacity-20' : subitem.hidden}" >
                            
                              <div class=" uip-flex uip-flex-row uip-gap-xxxs">
                                <input class="uip-blank-input" type="text" v-model="subitem.name" :placeholder="strings.itemName">
                                <div v-if="subitem.type == 'sep'" class="uip-text-s uip-padding-xxxs uip-border-round uip-background-orange-wash">{{strings.separator}}</div>
                              </div>
                              
                            </div>
                            
                            <!--Item options -->
                            <div class="uip-flex uip-flex-row uip-gap-xxs">
                              <drop-down v-if="subitem.type != 'sep'" dropPos="left">
                                <template v-slot:trigger>
                                  <div class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l">link</div>
                                </template>
                                <template v-slot:content>
                                  <div class="uip-padding-xxs">
                                    <input class="uip-input uip-input-small" type="text" v-model="subitem.url">
                                  </div>
                                </template>
                              </drop-down>
                              
                              <uip-tooltip :message="strings.itemVisibility">
                                <div @click="subitem.hidden = !subitem.hidden" class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l uip-cursor-pointer"
                                :class="{'uip-text-danger' : subitem.hidden}" >visibility</div>
                              </uip-tooltip>
                              
                              
                              
                              <drop-down dropPos="left">
                                <template v-slot:trigger>
                                  <uip-tooltip :message="strings.advanced">
                                    <div class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l">code</div>
                                  </uip-tooltip>
                                </template>
                                <template v-slot:content>
                                  <div class="uip-flex uip-flex-column uip-border-bottom">
                                    <div class="uip-padding-xs">
                                      <div class="uip-text-s uip-text-muted uip-margin-bottom-xxs">{{strings.customClasses}}</div>
                                      <input class="uip-input uip-input-small" type="text" v-model="subitem.customClasses">
                                    </div>
                                  </div>
                                  <div class="uip-flex uip-flex-column uip-row-gap-xs uip-padding-xs">
                                  
                                    <label class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-flex-between">
                                      <div class="uip-text-s uip-text-muted">{{strings.openInNewTab}}</div>
                                      <input class="uip-checkbox" type="checkbox" v-model="subitem.newTab">
                                    </label>
                                    
                                    <label class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-flex-between">
                                      <div class="uip-text-s uip-text-muted">{{strings.withoutFrame}}</div>
                                      <input class="uip-checkbox" type="checkbox" v-model="subitem.withoutFrame">
                                    </label>
                                    
                                    <label class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-flex-between">
                                      <div class="uip-text-s uip-text-muted">{{strings.withoutUiPress}}</div>
                                      <input class="uip-checkbox" type="checkbox" v-model="subitem.withoutUiPress">
                                    </label>
                                    
                                  </div>
                                </template>
                              </drop-down>
                              
                              
                              
                              <uip-tooltip v-if="subitem.custom" :message="strings.deleteItem">
                                <div  @click="deleteItem(subitem, index, parent.submenu)" class="uip-icon uip-padding-xxxs hover:uip-background-grey uip-border-round uip-text-l uip-cursor-pointer uip-link-danger">delete</div>
                              </uip-tooltip>
                            </div>
                            <!--End item options -->
                            
                          </div>
                        
                      </template>
                      
                      <template #footer >\
                        
                        <div class="uip-flex uip-flex-right">
                          <drop-down dropPos="bottom-left">
                            
                            <template v-slot:trigger>
                              <button class="uip-button-default uip-flex uip-flex-row uip-gap-xxs">
                                <span class="uip-icon">add</span>
                                <span>{{strings.addToMenu}}</span>
                              </button>
                            </template>
                            
                            <template v-slot:content>
                              <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs">
                                <div class="uip-link-muted uip-flex uip-gap-xs uip-flex-center"  @click="newMenuItem('sep', parent, true)">
                                  <span class="uip-icon">title</span>
                                  <span>{{strings.newSeparator}}</span>
                                </div>
                                <div class="uip-link-muted uip-flex uip-gap-xs uip-flex-center" @click="newMenuItem('link', parent, true)">
                                  <span class="uip-icon">link</span>
                                  <span>{{strings.newLink}}</span>
                                </div>
                              </div>
                            </template>
                            
                          </drop-down>
                        </div>
                        
                      </template>
                      
                    </draggable>
                    
                  </div>
                  <!-- END SUB LEVEL ITEMS -->
                  
                  
                  
                  
                  
                  
                
                </div>
                
              </template>
              
              <template #header >\
                
                <div class="uip-flex uip-flex-right">
                  <drop-down dropPos="bottom-left">
                    
                    <template v-slot:trigger>
                      <button class="uip-button-default uip-flex uip-flex-row uip-gap-xxs">
                        <span class="uip-icon">add</span>
                        <span>{{strings.addToMenu}}</span>
                      </button>
                    </template>
                    
                    <template v-slot:content>
                      <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs">
                        <div class="uip-link-muted uip-flex uip-gap-xs uip-flex-center" @click="newMenuItem('sep', menu)">
                          <span class="uip-icon">title</span>
                          <span>{{strings.newSeparator}}</span>
                        </div>
                        <div class="uip-link-muted uip-flex uip-gap-xs uip-flex-center" @click="newMenuItem('link', menu)">
                          <span class="uip-icon">link</span>
                          <span>{{strings.newLink}}</span>
                        </div>
                      </div>
                    </template>
                    
                  </drop-down>
                </div>
                
              </template>
              
              <template #footer >\
                
                <div class="uip-flex uip-flex-right">
                  <drop-down dropPos="bottom-left">
                    
                    <template v-slot:trigger>
                      <button class="uip-button-default uip-flex uip-flex-row uip-gap-xxs">
                        <span class="uip-icon">add</span>
                        <span>{{strings.addToMenu}}</span>
                      </button>
                    </template>
                    
                    <template v-slot:content>
                      <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs">
                        <div class="uip-link-muted uip-flex uip-gap-xs uip-flex-center" @click="newMenuItem('sep', menu)">
                          <span class="uip-icon">title</span>
                          <span>{{strings.newSeparator}}</span>
                        </div>
                        <div class="uip-link-muted uip-flex uip-gap-xs uip-flex-center" @click="newMenuItem('link', menu)">
                          <span class="uip-icon">link</span>
                          <span>{{strings.newLink}}</span>
                        </div>
                      </div>
                    </template>
                    
                  </drop-down>
                </div>
                
              </template>
              
              
              
            </draggable>
            
            <button class="uip-button-danger uip-margin-top-m uip-flex uip-flex-row uip-gap-xs uip-flex-center" @click="menu = OGmenu">
              <span class="uip-icon uip-text-l">delete</span>
              <span>{{strings.resetToDefault}}</span>
            </button>
          
          </div>
          
      </template> 
      
    </uip-offcanvas>`,
  };
}
