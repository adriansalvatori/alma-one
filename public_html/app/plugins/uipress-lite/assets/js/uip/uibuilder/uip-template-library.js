/**
 * Builds the main ui builder shell
 * @since 3.0.0
 */
const { __, _x, _n, _nx } = wp.i18n;

export function moduleData() {
  return {
    inject: ['uipData', 'router', 'uipress', 'uiTemplate'],

    data: function () {
      return {
        loading: true,
        themes: [],
        search: '',
        activeTemplate: false,
        imageIndex: 0,
        imageHover: false,
        draggingBlock: false,
        strings: {
          missingMessage: __('This block no longer exists', 'uipress-lite'),
          templateLibrary: __('Template library', 'uipress-lite'),
          pro: __('Pro', 'uipress-lite'),
          searchTemplates: __('Search templates', 'uipress-lite'),
          backToAllThemes: __('Back to all templates', 'uipress-lite'),
          includesProBlocks: __('Includes pro blocks', 'uipress-lite'),
          createdBy: __('Created by', 'uipress-lite'),
          addToTemplate: __('Add to template', 'uipress-lite'),
          replaceContent: __('Replace current layout', 'uipress-lite'),
          addAtEndOfContent: __('Add to end of layout', 'uipress-lite'),
          requires: __('Requires uipress', 'uipress-lite'),
          downloadcount: __('How many times this template has been downloaded', 'uipress-lite'),
          sortBy: __('Sort by', 'uipress-lite'),
          include: __('Include', 'uipress-lite'),
          uiTemplates: __('UI templates', 'uipress-lite'),
          pages: __('Pages', 'uipress-lite'),
          sections: __('Sections', 'uipress-lite'),
          patterns: __('Patterns', 'uipress-lite'),
          patternTitle: __('Name', 'uipress-lite'),
          patternType: __('Type', 'uipress-lite'),
          savePattern: __('Save pattern', 'uipress-lite'),
          description: __('Description', 'uipress-lite'),
          patternIcon: __('Icon', 'uipress-lite'),
        },
        patternTypes: {
          layout: { name: 'layout', label: __('Layout', 'uipress-lite') },
          block: { name: 'block', label: __('Block', 'uipress-lite') },
        },
        sortby: 'newest',
        sortOptions: {
          downloads: {
            label: __('Downloads', 'uipress-lite'),
            value: 'downloads',
          },
          newest: {
            label: __('Newest', 'uipress-lite'),
            value: 'newest',
          },
          oldest: {
            label: __('Oldest', 'uipress-lite'),
            value: 'oldest',
          },
        },
        typeOptions: {
          uiTemplates: {
            label: __('UI templates', 'uipress-lite'),
            value: 'ui-template',
            selected: true,
          },
          adminPages: {
            label: __('Admin pages', 'uipress-lite'),
            value: 'ui-admin-page',
            selected: true,
          },
        },
      };
    },
    watch: {
      sortby: {
        handler(newValue, oldValue) {
          this.fetchThemes();
        },
      },
      typeOptions: {
        handler(newValue, oldValue) {
          this.fetchThemes();
        },
        deep: true,
      },
    },
    mounted: function () {
      this.fetchThemes();
    },
    computed: {
      returnThemes() {
        let filteredArray = this.themes.filter((obj) => obj.type === 'Layout');
        return filteredArray;
      },
      returnPages() {
        let filteredArray = this.themes.filter((obj) => obj.type === 'Admin Page');
        return filteredArray;
      },
      returnSections() {
        let filteredArray = this.themes.filter((obj) => obj.type === 'Section');
        return filteredArray;
      },
      returnSortBy() {
        return this.sortby;
      },

      returnFilter() {
        let filter = '';
        for (let key in this.typeOptions) {
          if (this.typeOptions[key].selected) {
            filter += this.typeOptions[key].value + '||';
          }
        }
        if (filter == '') {
          this.typeOptions.uiTemplates.selected = true;
        }
        return filter;
      },
    },
    methods: {
      goBack() {
        let ID = this.$route.params.templateID;
        this.router.push('/uibuilder/' + ID + '/');
      },
      fetchThemes() {
        let self = this;
        self.loading = true;
        let formData = new FormData();
        let URL = `https://api.uipress.co/templates/list/?sort=${this.returnSortBy}&filter=${this.returnFilter}&v321=true`;

        self.uipress.callServer(URL, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, 'uipress-lite', '', 'error', true);
            self.loading = false;
          }
          self.loading = false;
          self.themes = response;
        });
      },
      navImagesForward(theme) {
        if (theme.imageIndex + 1 > theme.images.length - 1) {
          theme.imageIndex = 0;
        } else {
          theme.imageIndex += 1;
        }
      },
      navImagesBackward(theme) {
        if (theme.imageIndex - 1 < 0) {
          theme.imageIndex = theme.images.length - 1;
        } else {
          theme.imageIndex -= 1;
        }
      },
      returnThemeIndex(theme) {
        if (!('imageIndex' in theme)) {
          theme.imageIndex = 0;
        }
        return theme.imageIndex;
      },
      clone(template) {
        let URL = 'https://api.uipress.co/templates/get/?templateid=' + template.ID;

        let item = {
          remote: true,
          path: URL,
        };

        return item;
      },

      importThis(template, replaceContent) {
        let URL = 'https://api.uipress.co/templates/get/?templateid=' + template.ID;

        let item = {
          remote: true,
          path: URL,
        };
        if (replaceContent) {
          this.uiTemplate.content = [item];
        } else {
          this.uiTemplate.content.push(item);
        }
      },

      ////////////
      ////PATTERNS
      ////////////
      importThisPattern(template) {
        this.uiTemplate.content.push(this.clonePattern(template));
      },
      returnIcon(pattern) {
        if (pattern.icon && pattern.icon != '') {
          return pattern.icon;
        }
        if (pattern.type == 'layout') {
          return 'account_tree';
        } else if (pattern.type == 'block') {
          return 'add_box';
        }
        return 'interests';
      },
      deleteThisItem(postID) {
        let self = this;
        this.uipress.deletePost(postID).then((response) => {
          if (response) {
            self.getPatterns();
          }
        });
      },
      getPatterns() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_get_ui_patterns_list');
        formData.append('security', uip_ajax.security);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, 'uipress-lite', '', 'error', true);
          }
          if (response.success) {
            self.uiTemplate.patterns = response.patterns;
          }
        });
      },
      clonePattern(block) {
        let itemFreshIDs = this.duplicateBlock(block.template);
        let item = JSON.parse(JSON.stringify(itemFreshIDs));
        return item;
      },
      //Iterate over content and create new UIDs
      duplicateBlock(block) {
        let item = Object.assign({}, block);
        item.uid = this.uipress.createUID();
        item.options = [];
        item.settings = JSON.parse(JSON.stringify(item.settings));

        if (item.content) {
          item.content = this.duplicateChildren(item.content);
        }

        return item;
      },

      duplicateChildren(content) {
        let returnChildren = [];

        for (let block of content) {
          let item = Object.assign({}, block);
          item.uid = this.uipress.createUID();
          item.settings = JSON.parse(JSON.stringify(item.settings));

          if (item.content) {
            item.content = this.duplicateChildren(item.content);
          }

          returnChildren.push(item);
        }

        return returnChildren;
      },
    },
    template: `
    
    <div class="uip-background-default uip-flex uip-flex-column uip-border-box uip-max-h-100p">
        
        <div v-if="loading" class="uip-padding-m uip-flex uip-flex-center uip-flex-middle"><loading-chart></loading-chart></div>
        
		    <div v-else class="uip-flex uip-flex-column uip-h-100p uip-max-h-100p uip-row-gap-xs">
            
            
            
            <!--Ui templates-->
            <drop-down dropPos="right" :hover='true' class="uip-w-100p" triggerClass="uip-w-100p" dropClass="uip-max-h-100p uip-overflow-hidden" :screenHeight="[68,0]" slotClass="uip-max-h-100p uip-overflow-auto" :dontAnimate="true"> 
            
              <template v-slot:trigger>
                <div class="uip-flex uip-gap-s uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-flex-center">
                
                    <div class="uip-icon uip-text-xl uip-background-muted uip-border-rounder uip-padding-xxs">
                      space_dashboard
                    </div>   
                    <div class="uip-text-emphasis uip-flex-grow">{{strings.uiTemplates}}</div>             
                    
                    <div class="uip-icon uip-text-l">chevron_right</div>
                </div>
              </template>
              
              <template v-slot:content>
                
                
                  <uip-draggable 
                  :list="returnThemes" 
                  class="uip-padding-m uip-flex uip-flex-column uip-row-gap-m uip-w-400 uip-margin-bottom-l"
                  handle=".uip-block-drag"
                  :group="{ name: 'uip-blocks', pull: 'clone', put: false, revertClone: true }"
                  animation="300"
                  :sort="false"
                  :clone="clone"
                  itemKey="name"
                  >
                    <template v-for="(theme, index) in returnThemes" :key="theme.ID" :index="index">
                    
                        <div class="uip-cursor-pointer">
                        
                          <div class="uip-flex uip-flex-column uip-row-gap-xs">
                          
                              
                              <div class="uip-w-100p uip-border-rounder uip-ratio-16-10 uip-block-drag uip-background-grey uip-background-no-repeat uip-background-center uip-background-cover" :style="'background-image:url(' + theme.images[returnThemeIndex(theme)] + ');'" >
                              </div>
                              
                              <div class="uip-flex uip-gap-xs uip-flex-center">
                              
                                <div class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon" @click="theme.showInfo = !theme.showInfo">lightbulb</div>
                                <div class="uip-flex-grow uip-text-bold">{{theme.name}}</div>
                                
                                <div v-if="theme.images.length > 1" class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="navImagesBackward(theme)">chevron_left</div>
                                <div v-if="theme.images.length > 1" class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="navImagesForward(theme)">chevron_right</div>
                                
                                <div class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="importThis(theme, true)">download</div>
                              </div>
                              
                              <template v-if="theme.showInfo">
                              
                                <div class="uip-text-muted" style="line-height:1.6">
                                  {{theme.description}}
                                </div>
                                
                                <div class="uip-flex uip-flex-gap-xs uip-flex-between">
                                  
                                  <div class="uip-text-xs uip-background-primary-wash uip-border-rounder uip-padding-left-xxxs uip-padding-right-xxxs uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
                                    <span class="uip-icon">file_download</span>
                                    <span>{{theme.downloads}}</span>
                                  </div>
                                  
                                  <div class="uip-text-muted uip-text-s">
                                    {{strings.createdBy}} <a href="https://uipress.co" class="uip-link-default">{{theme.created_by}}</a>
                                  </div>
                                  
                                </div>
                              
                              </template>
                            
                            
                          </div>
                        </div>
                      
                    </template>
                  
                  </uip-draggable>
                
                
              </template>
            
            </drop-down>
            
            
            
            <!--Ui Pages-->
            <drop-down dropPos="right" :hover='true' class="uip-w-100p" triggerClass="uip-w-100p" dropClass="uip-max-h-100p uip-overflow-hidden" :screenHeight="[68,0]" slotClass="uip-max-h-100p uip-overflow-auto" :dontAnimate="true">
            
              <template v-slot:trigger>
                <div class="uip-flex uip-gap-s uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-flex-center">
                
                    <div class="uip-icon uip-text-xl uip-background-muted uip-border-rounder uip-padding-xxs">
                      article
                    </div>   
                    <div class="uip-text-emphasis uip-flex-grow">{{strings.pages}}</div>             
                    
                    <div class="uip-icon uip-text-l">chevron_right</div>
                </div>
              </template>
              
              <template v-slot:content>
                
                <uip-draggable 
                :list="returnPages" 
                class="uip-padding-m uip-flex uip-flex-column uip-row-gap-m uip-w-400"
                handle=".uip-block-drag"
                :group="{ name: 'uip-blocks', pull: 'clone', put: false, revertClone: true }"
                animation="300"
                :sort="false"
                :clone="clone"
                itemKey="name"
                >
                  <template v-for="(theme, index) in returnPages" :key="theme.ID" :index="index">
                  
                      <div class="uip-cursor-pointer">
                      
                        <div class="uip-flex uip-flex-column uip-row-gap-xs">
                        
                            
                            <div class="uip-w-100p uip-border-rounder uip-ratio-16-10 uip-block-drag uip-background-grey uip-background-no-repeat uip-background-center uip-background-cover" :style="'background-image:url(' + theme.images[returnThemeIndex(theme)] + ');'" >
                            </div>
                            
                            <div class="uip-flex uip-gap-xs uip-flex-center">
                            
                              <div class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon" @click="theme.showInfo = !theme.showInfo">lightbulb</div>
                              <div class="uip-flex-grow uip-text-bold">{{theme.name}}</div>
                              
                              <div v-if="theme.images.length > 1" class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="navImagesBackward(theme)">chevron_left</div>
                              <div v-if="theme.images.length > 1" class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="navImagesForward(theme)">chevron_right</div>
                              
                              <div class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="importThis(theme, true)">download</div>
                            </div>
                            
                            <template v-if="theme.showInfo">
                            
                              <div class="uip-text-muted" style="line-height:1.6">
                                {{theme.description}}
                              </div>
                              
                              <div class="uip-flex uip-flex-gap-xs uip-flex-between">
                                
                                <div class="uip-text-xs uip-background-primary-wash uip-border-rounder uip-padding-left-xxxs uip-padding-right-xxxs uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
                                  <span class="uip-icon">file_download</span>
                                  <span>{{theme.downloads}}</span>
                                </div>
                                
                                <div class="uip-text-muted uip-text-s">
                                  {{strings.createdBy}} <a href="https://uipress.co" class="uip-link-default">{{theme.created_by}}</a>
                                </div>
                                
                              </div>
                            
                            </template>
                          
                          
                        </div>
                      </div>
                    
                  </template>
                
                </uip-draggable>
                
              </template>
            
            </drop-down>
            
            
            
            
            <!--sections-->
            <drop-down dropPos="right" :hover='true' class="uip-w-100p" triggerClass="uip-w-100p" dropClass="uip-max-h-100p uip-overflow-hidden" :screenHeight="[68,0]" slotClass="uip-max-h-100p uip-overflow-auto" :dontAnimate="true">
            
              <template v-slot:trigger>
                <div class="uip-flex uip-gap-s uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-flex-center">
                
                    <div class="uip-icon uip-text-xl uip-background-muted uip-border-rounder uip-padding-xxs">
                      calendar_view_day
                    </div>   
                    <div class="uip-text-emphasis uip-flex-grow">{{strings.sections}}</div>             
                    
                    <div class="uip-icon uip-text-l">chevron_right</div>
                </div>
              </template>
              
              <template v-slot:content>
                
                <uip-draggable 
                :list="returnSections" 
                class="uip-padding-m uip-flex uip-flex-column uip-row-gap-m uip-w-400"
                handle=".uip-block-drag"
                :group="{ name: 'uip-blocks', pull: 'clone', put: false, revertClone: true }"
                animation="300"
                :sort="false"
                :clone="clone"
                itemKey="name"
                >
                  <template v-for="(theme, index) in returnSections" :key="theme.ID" :index="index">
                  
                      <div class="uip-cursor-pointer">
                      
                        <div class="uip-flex uip-flex-column uip-row-gap-xs">
                        
                            <div class="uip-w-100p uip-border-rounder uip-ratio-16-10 uip-block-drag uip-background-grey uip-background-no-repeat uip-background-center" :style="'background-image:url(' + theme.images[returnThemeIndex(theme)] + ');background-size:90%;'" >
                            </div>
                            
                            <div class="uip-flex uip-gap-xs uip-flex-center">
                            
                              <div class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon" @click="theme.showInfo = !theme.showInfo">lightbulb</div>
                              <div class="uip-flex-grow uip-text-bold">{{theme.name}}</div>
                              
                              <div v-if="theme.images.length > 1" class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="navImagesBackward(theme)">chevron_left</div>
                              <div v-if="theme.images.length > 1" class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="navImagesForward(theme)">chevron_right</div>
                              
                              <div class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l" @click="importThis(theme)">download</div>
                            </div>
                            
                            <template v-if="theme.showInfo">
                            
                              <div class="uip-text-muted" style="line-height:1.6">
                                {{theme.description}}
                              </div>
                              
                              <div class="uip-flex uip-flex-gap-xs uip-flex-between">
                                
                                <div class="uip-text-xs uip-background-primary-wash uip-border-rounder uip-padding-left-xxxs uip-padding-right-xxxs uip-flex uip-flex-row uip-gap-xxs uip-flex-center">
                                  <span class="uip-icon">file_download</span>
                                  <span>{{theme.downloads}}</span>
                                </div>
                                
                                <div class="uip-text-muted uip-text-s">
                                  {{strings.createdBy}} <a href="https://uipress.co" class="uip-link-default">{{theme.created_by}}</a>
                                </div>
                                
                              </div>
                            
                            </template>
                          
                          
                        </div>
                      </div>
                    
                  </template>
                
                </uip-draggable>
                
              </template>
            
            </drop-down>
            
            
            
            <div class="uip-border-top uip-margin-top-s uip-margin-bottom-s"></div>
            
            
            
            
            <!--Patterns-->
            <drop-down dropPos="right" :hover='true' class="uip-w-100p" triggerClass="uip-w-100p" dropClass="uip-h-100p uip-overflow-hidden" :screenHeight="[68,0]" slotClass="uip-max-h-100p uip-overflow-auto uip-h-100p" :dontAnimate="true">
            
              <template v-slot:trigger>
                <div class="uip-flex uip-gap-s uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-flex-center">
                
                    <div class="uip-icon uip-text-xl uip-background-muted uip-border-rounder uip-padding-xxs">
                      texture
                    </div>   
                    <div class="uip-text-emphasis uip-flex-grow">{{strings.patterns}}</div>             
                    
                    <div class="uip-icon uip-text-l">chevron_right</div>
                </div>
              </template>
              
              <template v-slot:content>
              
                <p v-if="uiTemplate.patterns.length < 1" class="uip-padding-m uip-text-muted uip-text-center">No patterns created yet</p>
                
                <uip-draggable 
                :list="uiTemplate.patterns" 
                class="uip-padding-s uip-flex uip-flex-column uip-row-gap-s uip-w-300 uip-h-100p"
                handle=".uip-block-drag"
                :group="{ name: 'uip-blocks', pull: 'clone', put: false, revertClone: true }"
                animation="300"
                :sort="false"
                :clone="clonePattern"
                itemKey="name"
                >
                  <template v-for="(theme, index) in uiTemplate.patterns" :key="theme.id" :index="index">
                      
                        <div class="uip-flex uip-flex-column uip-block-drag uip-row-gap-xs">
                        
                            <div class="uip-flex uip-gap-xs uip-flex-center hover:uip-background-grey uip-padding-xxs uip-border-rounder uip-cursor-pointer" @dblclick="importThisPattern(theme)">
                            
                              <div class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-l">{{returnIcon(theme)}}</div>
                              <div class="uip-flex-grow uip-text-bold">{{theme.name}}</div>
                              
                              <div v-if="!theme.editing" class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon" @click="theme.editing = true">edit</div>
                              <div v-if="theme.editing" class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon uip-text-green" @click="theme.editing = false">done</div>
                              
                              <div class="uip-button-default uip-icon uip-border-rounder uip-padding-xxs uip-link-muted uip-icon" @click="deleteThisItem(theme.id)">delete</div>
                            </div>
                            
                            <div v-if="theme.editing" class="uip-grid-col-1-3 uip-padding-s uip-padding-right-remove">
                              
                              <!--Name -->
                              <div class="uip-flex uip-flex-center">
                                <div class="uip-text-s uip-text-muted">{{strings.patternTitle}}</div>
                              </div>
                              <div class="uip-flex uip-flex-center">
                                <input class="uip-input uip-input-small uip-w-100p" type="text" v-model="theme.name">
                              </div>
                              
                              <!--Icon-->
                              <div class="uip-flex uip-flex-center">
                                <div class="uip-text-s uip-text-muted">{{strings.patternIcon}}</div>
                              </div>
                              <div class="uip-flex uip-flex-center">
                                <icon-select :value="{value: theme.icon}" :returnData="function(data) {theme.icon = data.value}"/>
                              </div>
                              
                              <!--Description-->
                              <div class="uip-flex uip-flex-center">
                                <div class="uip-text-s uip-text-muted">{{strings.description}}</div>
                              </div>
                              <div class="uip-flex uip-flex-center">
                                <textarea class="uip-input uip-w-100p" rows="4" v-model="theme.description"></textarea>
                              </div>
                              
                              <!--Description-->
                              <div class="uip-flex uip-flex-center">
                                <div class="uip-text-s uip-text-muted">{{strings.patternType}}</div>
                              </div>
                              <div class="uip-flex uip-flex-center">
                                <toggle-switch :options="patternTypes" :activeValue="theme.type" :returnValue="function(data){ theme.type = data}"></toggle-switch>
                              </div>
                              
                            
                            </div>
                            
                        </div>
                    
                  </template>
                
                </uip-draggable>
                
              </template>
            
            </drop-down>
            
            
            
          
        </div>
		  </div>`,
  };
  return compData;
}
