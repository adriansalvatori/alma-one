/**
 * Responsible for the inline block options in the builder
 * @since 3.0.0
 */
const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      block: Object,
      parentList: Array,
      currentIndex: Number,
      smallIcons: Boolean,
      reverse: Boolean,
      disabled: Array,
      expanded: Boolean,
      closeActions: Function,
    },
    data: function () {
      return {
        option: '',
        strings: {
          savePattern: __('Save pattern', 'uipress-lite'),
          settings: __('Settings', 'uipress-lite'),
          style: __('Style', 'uipress-lite'),
          copy: __('Copy', 'uipress-lite'),
          copyBlock: __('Copy block', 'uipress-lite'),
          copyStyles: __('Copy styles', 'uipress-lite'),
          paste: __('Paste', 'uipress-lite'),
          pasteContent: __('Paste content', 'uipress-lite'),
          pasteStyles: __('Paste styles', 'uipress-lite'),
          advanced: __('Advanced', 'uipress-lite'),
          duplicate: __('Duplicate', 'uipress-lite'),
          syncPattern: __('Sync pattern', 'uipress-lite'),
          delete: __('Delete', 'uipress-lite'),
          export: __('Export', 'uipress-lite'),
          exportBlock: __('Block', 'uipress-lite'),
          exportBlockContent: __('Block content', 'uipress-lite'),
          exportTemplate: __('Template', 'uipress-lite'),
          import: __('Import', 'uipress-lite'),
          importContent: __('Content', 'uipress-lite'),
          importBlock: __('Block', 'uipress-lite'),
          importTemplate: __('Template', 'uipress-lite'),
          insert: __('Insert', 'uipress-lite'),
        },
      };
    },
    inject: {
      uipData: { from: 'uipData' },
      router: { from: 'router' },
      uipress: { from: 'uipress' },
      uiTemplate: { from: 'uiTemplate' },
      saveTemplate: { from: 'saveTemplate', default: () => ({ name: 'save not ready' }) },
      openModal: { from: 'openModal' },
    },
    watch: {},
    computed: {
      returnDisabled() {
        if (Array.isArray(this.disabled)) {
          return this.disabled;
        } else {
          return [];
        }
      },
    },
    mounted: function () {},
    methods: {
      /**
       * Opens block settings panel
       * @since 3.0.0
       */
      openSettings(uid) {
        let ID = this.$route.params.templateID;
        this.router.push({
          path: '/uibuilder/' + ID + '/settings/blocks/' + uid,
          query: { ...this.$route.query, section: 'settings' },
        });
      },

      /**
       * Opens block settings panel
       * @since 3.0.0
       */
      openStyles(uid) {
        let ID = this.$route.params.templateID;
        this.router.push({
          path: '/uibuilder/' + ID + '/settings/blocks/' + uid,
          query: { ...this.$route.query, section: 'style' },
        });
      },

      /**
       * Opens block advanced panel
       * @since 3.0.0
       */
      openAdvanced(uid) {
        let ID = this.$route.params.templateID;
        this.router.push({
          path: '/uibuilder/' + ID + '/settings/blocks/' + uid,
          query: { ...this.$route.query, section: 'advanced' },
        });
      },

      /**
       * Duplicates selected block into current list
       * @since 3.0.0
       */
      duplicateBlock(block) {
        let currentTem = JSON.parse(JSON.stringify(this.uiTemplate.content));

        //Get block parent
        let self = this;
        let currentContent;
        self.uipress.getParentByUID(self.uiTemplate.content, block.uid).then((response) => {
          if (response) {
            //Block found
            currentContent = response;
            //Duplicate it
            let item = JSON.parse(JSON.stringify(block));
            item.uid = this.uipress.createUID();
            item.options = [];

            if (item.content) {
              item.content = this.duplicateChildren(item.content);
            }

            //Get current index
            let currentIndex = currentContent.findIndex((item) => item.uid === block.uid);

            currentContent.splice(currentIndex, 0, item);

            let newTem = JSON.parse(JSON.stringify(this.uiTemplate.content));
            self.uipress.logHistoryChange(block.name + __(' duplicated', 'uipress-lite'), currentTem, newTem);
            self.uipress.notify(block.name + ' ' + __(' duplicated', 'uipress-lite'), '', 'success', true);
          }
        });

        return;
      },
      /**
       * Pastes copied block into current list
       * @since 3.0.94
       */
      pasteBlock() {
        let self = this;
        if (!self.block.content) {
          return;
        }

        if (!self.uiTemplate.copied) {
          return;
        }

        let currentTem = JSON.parse(JSON.stringify(this.uiTemplate.content));

        //Duplicate it
        let item = Object.assign({}, self.uiTemplate.copied);
        item.uid = this.uipress.createUID();
        item.options = [];
        item.settings = JSON.parse(JSON.stringify(item.settings));

        if (item.content) {
          item.content = this.duplicateChildren(item.content);
        }

        //Get current index

        self.block.content.splice(self.block.content.length, 0, item);

        let newTem = JSON.parse(JSON.stringify(this.uiTemplate.content));
        self.uipress.logHistoryChange(self.block.name + __(' pasted', 'uipress-lite'), currentTem, newTem);
        self.uipress.notify(self.block.name + ' ' + __(' pasted', 'uipress-lite'), '', 'success', true);

        self.uiTemplate.copied = false;

        self.closeActions();
      },

      pasteBlockStyles() {
        let self = this;
        if (!self.uiTemplate.stylesCopied) {
          return;
        }

        console.log(self.block.settings);
        for (let key in self.uiTemplate.stylesCopied) {
          //These are not styles so don't copy them
          if (key == 'advanced' || key == 'block' || key == 'container') {
            continue;
          }
          console.log(key);

          //Check if the new block has the same settings
          if (key in self.block.settings) {
            let options = self.uiTemplate.stylesCopied[key].options;
            self.block.settings[key] = { ...self.block.settings[key], ...self.uiTemplate.stylesCopied[key] };
            self.block.settings[key].options = { ...self.block.settings[key].options, ...options };
          }
        }

        console.log(self.block.settings);
        self.closeActions();
      },
      /**
       * Loops through children of block being duplicated and creates new UIDs
       * @since 3.0.0
       */
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
      /**
       * Deletes selected block
       * @since 3.0.0
       */
      removeBlock(block) {
        let self = this;
        self.uipress.deleteByUID(self.uiTemplate.content, block.uid).then((response) => {
          if (response) {
            self.uipress.notify(block.name + ' ' + __('block deleted', 'uipress-lite'), '', 'default', true);
            let newTem = JSON.parse(JSON.stringify(self.uiTemplate.content));
            self.uipress.logHistoryChange(block.name + ' ' + __('block deleted', 'uipress-lite'), newTem, false);
            if (self.$route.params.uid && self.$route.params.uid == block.uid) {
              let ID = self.$route.params.templateID;
              self.router.push('/uibuilder/' + ID + '/');
            }
          }
        });
      },
      /**
       * Confirms block pattern sync. If confirmed starts sync process
       * @since 3.0.0
       */
      confirmSyncPattern(block) {
        let self = this;
        this.uipress
          .confirm(
            __('Sync pattern?', 'uipress-lite'),
            __("This will update the pattern template and will sync this pattern's changes accross all templates using the same pattern", 'uipress-lite'),
            __('Sync patern', 'uipress-lite')
          )
          .then((response) => {
            if (response) {
              //this.router.push('/');

              let cleanTemplate = JSON.parse(JSON.stringify(self.uiTemplate.content));
              self.uipress.cleanTemplate(cleanTemplate).then((response) => {
                self.uipress.saveTemplate(cleanTemplate).then((response) => {
                  let notiID = self.uipress.notify(__('Pattern sync in progress', 'uipress-lite'), '', 'default', false, true);
                  //Clean block before saving to DB
                  self.uipress.blockHouseKeeping(block).then((response) => {
                    self.syncUiPatternDB(response, notiID);
                  });
                });
              });
            }
          });
      },

      /**
       * Sends pattern to db and updates all instances of pattern accross templates
       * @since 3.0.0
       */
      syncUiPatternDB(block, notiID) {
        let self = this;
        let formData = new FormData();
        let pattern = JSON.stringify(block, (k, v) => (v === 'true' ? 'uiptrue' : v === true ? 'uiptrue' : v === 'false' ? 'uipfalse' : v === false ? 'uipfalse' : v === '' ? 'uipblank' : v));

        formData.append('action', 'uip_sync_ui_pattern');
        formData.append('security', uip_ajax.security);
        formData.append('pattern', pattern);
        formData.append('patternID', block.patternID);
        formData.append('templateID', self.$route.params.templateID);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, 'uipress-lite', '', 'error', true);
            self.saving = false;
          }
          if (response.success) {
            //Original pattern was deleted so we created a new one and will reassign the patternID on the block
            if (response.newPattern) {
              block.patternID = JSON.parse(JSON.stringify(response.newPattern));
            }
            if (response.newTemplate) {
              self.uiTemplate.content = response.newTemplate;
            }
            self.uiTemplate.patterns = response.patterns;
            self.uipress.notify(__('Pattern succesfully synced', 'uipress-lite'), '', 'success', true);
            self.uipress.destroy_notification(notiID);
          }
        });
      },
      /**
       * Checks if specific action is enabled
       * @since 3.0.0
       */
      ifEnabled(item) {
        if (this.returnDisabled.includes(item)) {
          return false;
        }
        return true;
      },
      canPaste() {
        if (this.block.content && this.uiTemplate.copied) {
          return true;
        }
        return false;
      },
      canPasteStyles() {
        if (this.uiTemplate.stylesCopied) {
          return true;
        }
        return false;
      },
      copyBlock(block) {
        this.uiTemplate.copied = block;
        this.uipress.notify(__('Block copied', 'uipress-lite'), '', 'success', true);
      },
      copyBlockStyles(block) {
        console.log(block);
        this.uiTemplate.stylesCopied = block.settings;
        this.uipress.notify(__('Block styles copied', 'uipress-lite'), '', 'success', true);
      },
      exportStuff(type) {
        self = this;
        let layout;
        let namer = 'uip-ui-template-';
        if (type == 'template') {
          layout = JSON.stringify({ uipLayout: self.uiTemplate.content });
        }
        if (type == 'block') {
          layout = JSON.stringify({ uipLayout: self.block });
          namer = 'uip-ui-block-';
        }
        if (type == 'blockcontent') {
          layout = JSON.stringify({ uipBlockContent: self.block.content });
          namer = 'uip-ui-block-content-';
        }

        let name = self.uiTemplate.globalSettings.name;

        let today = new Date();
        let dd = String(today.getDate()).padStart(2, '0');
        let mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        let yyyy = today.getFullYear();

        let date_today = mm + '-' + dd + '-' + yyyy;
        let filename = namer + name + '-' + date_today + '.json';

        let dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(layout);
        let dlAnchorElem = this.$refs.templateexport;
        dlAnchorElem.setAttribute('href', dataStr);
        dlAnchorElem.setAttribute('download', filename);
        dlAnchorElem.click();
        let message = __('Layout exported', 'uipress-lite');
        if (type == 'block') {
          message = __('Block exported', 'uipress-lite');
        }
        if (type == 'blockcontent') {
          message = __('Block content exported', 'uipress-lite');
        }
        self.uipress.notify(message, '', 'success', true);
      },

      importSettings(event, type) {
        let self = this;

        let fileInput = event.target;
        let thefile = fileInput.files[0];

        if (thefile.type != 'application/json') {
          self.uipress.notify(__('Templates must be in valid JSON format', 'uipress-lite'), '', 'error', true, false);
          return;
        }

        if (thefile.size > 1000000) {
          self.uipress.notify(__('Uploaded file is too big', 'uipress-lite'), '', 'error', true, false);
          return;
        }

        let reader = new FileReader();
        reader.readAsText(thefile, 'UTF-8');

        reader.onload = function (evt) {
          let json_settings = evt.target.result;
          let parsed;

          //Check for valid JSON data
          try {
            parsed = JSON.parse(json_settings);
          } catch (error) {
            self.uipress.notify(error, '', 'error', true, false);
            return;
          }

          if (parsed != null) {
            if (!Array.isArray(parsed) && !self.uipress.isObject(parsed)) {
              self.uipress.notify('Template is not valid', '', 'error', true, false);
              return;
            }

            let temper;
            let message = __('Template imported', 'uipress-lite');
            if (type == 'template') {
              if (Array.isArray(parsed)) {
                temper = parsed;
              } else if ('uipLayout' in parsed) {
                if (Array.isArray(parsed.uipLayout)) {
                  temper = parsed.uipLayout;
                } else {
                  temper = [parsed.uipLayout];
                }
              } else {
                return self.uipress.notify(__('Template mismatch', 'uipress-lite'), '', 'error', true, false);
              }
            }
            if (type == 'block') {
              if (!('uipLayout' in parsed)) {
                return self.uipress.notify(__('Template mismatch', 'uipress-lite'), '', 'error', true, false);
              }
              message = __('Block imported', 'uipress-lite');
              temper = [parsed.uipLayout];
            }
            if (type == 'blockcontent') {
              if (!('uipBlockContent' in parsed)) {
                return self.uipress.notify(__('Template mismatch', 'uipress-lite'), '', 'error', true, false);
              }
              message = __('Content imported', 'uipress-lite');
              temper = parsed.uipBlockContent;
            }

            self.uipress.validDateTemplate(temper).then((response) => {
              if (!response.includes(false)) {
                if (type == 'template') {
                  self.uiTemplate.content = temper;
                }
                if (type == 'block') {
                  console.log(temper[0]);
                  self.block.content.push(temper[0]);
                }
                if (type == 'blockcontent') {
                  self.block.content = temper;
                }

                self.uipress.notify(message, '', 'success', true, false);
              } else {
                self.uipress.notify(__('File is not a valid JSON template', 'uipress-lite'), '', 'error', true, false);
              }
            });
          } else {
            self.uipress.notify(__('JSON parse failed', 'uipress-lite'), '', 'error', true, false);
          }
        };
      },
    },
    template: `
	<div v-if="!expanded" class="uip-flex uip-gap-xxs" :class="[{'uip-text-l' : !smallIcons},{'uip-flex-reverse' : reverse}]">
	  <div v-if="ifEnabled('settings')" class="uip-icon uip-link-muted"  @click="openSettings(block.uid)">tune</div>
	  <div v-if="ifEnabled('duplicate')" class="uip-icon uip-link-muted" @click="duplicateBlock(block)">content_copy</div>
    <div v-if="ifEnabled('pattern')" class="uip-icon uip-link-muted" @click="openModal('saveaspattern', strings.savePattern, {blockitem: block})">bookmark_add</div>
	  <div v-if="block.patternID && ifEnabled('sync')" class="uip-icon uip-link-muted" @click="confirmSyncPattern(block)">sync</div>
	  <div class="uip-border-left"></div>
	  <div v-if="ifEnabled('delete')"  class="uip-icon uip-link-danger" @click="removeBlock(block)">delete</div>
	</div>
  
  <div v-else class="uip-padding-xs">
  
    <div class="uip-flex uip-flex-column uip-row-gap-xs">
    
      <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs">
        <div class="">{{block.name}}</div>
      </div> 
    
    </div>
    
    <div class="uip-border-bottom uip-margin-top-xs uip-margin-bottom-xs"></div>
    
    <div class="uip-flex uip-flex-column">
  
      <div v-if="ifEnabled('settings')" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="openSettings(block.uid);closeActions()">
        <div class="uip-icon">tune</div>
        <div class="">{{strings.settings}}</div>
      </div> 
      
      <div v-if="ifEnabled('settings')" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="openStyles(block.uid);closeActions()">
        <div class="uip-icon">palette</div>
        <div class="">{{strings.style}}</div>
      </div>  
      
      <div v-if="ifEnabled('settings')" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="openAdvanced(block.uid);closeActions()">
        <div class="uip-icon">code</div>
        <div class="">{{strings.advanced}}</div>
      </div>  
      
    </div>
    
    <div class="uip-border-bottom uip-margin-top-xs uip-margin-bottom-xs"></div>
    
    <div class="uip-flex uip-flex-column"> 
    
    
      <!--Copy options-->
      <drop-down dropPos="right" :hover="true" triggerClass="uip-w-100p" containerClass="uip-w-100p">
      
        <template v-slot:trigger>
        
          <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between">
            <div class="uip-icon uip-text-l">chevron_right</div>
            <div class="">{{strings.copy}}</div>
          </div> 
        
        </template>
        
        <template v-slot:content>
        
          <div class="uip-flex uip-flex-column uip-padding-xs">
          
            <div v-if="ifEnabled('copy')" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="copyBlock(block);">
              <div class="uip-icon">copy_all</div>
              <div class="">{{strings.copyBlock}}</div>
            </div> 
            
            <div v-if="ifEnabled('copy')" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="copyBlockStyles(block);">
              <div class="uip-icon">difference</div>
              <div class="">{{strings.copyStyles}}</div>
            </div> 
          
          </div>
        
        </template>
      
      </drop-down>
      
      
      <!--Paste options-->
      <drop-down dropPos="right" :hover="true" triggerClass="uip-w-100p" containerClass="uip-w-100p">
      
        <template v-slot:trigger>
        
          <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between">
            <div class="uip-icon uip-text-l">chevron_right</div>
            <div class="">{{strings.paste}}</div>
          </div> 
        
        </template>
        
        <template v-slot:content>
        
          <div class="uip-flex uip-flex-column uip-padding-xs">
          
            <div :class="canPaste() ? '' : 'uip-link-disabled'" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="pasteBlock();">
              <div class="uip-icon">content_paste</div>
              <div class="">{{strings.pasteContent}}</div>
            </div>
            
            <div :class="canPasteStyles() ? '' : 'uip-link-disabled'" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="pasteBlockStyles();">
              <div class="uip-icon">content_paste</div>
              <div class="">{{strings.pasteStyles}}</div>
            </div> 
          
          </div>
        
        </template>
      
      </drop-down>
    
      
      
       
      
      <div v-if="ifEnabled('duplicate')" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="duplicateBlock(block);closeActions()">
        <div class="uip-icon">content_copy</div>
        <div class="">{{strings.duplicate}}</div>
      </div>  
      
      <div v-if="ifEnabled('pattern')" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="openModal('saveaspattern', strings.savePattern, {blockitem: block});closeActions()">  
        <div class="uip-icon">bookmark_add</div>
        <div class="">{{strings.savePattern}}</div>
      </div>
      
      <div v-if="block.patternID && ifEnabled('sync')" class="uip-flex uip-flex-center uip-flex-row uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="confirmSyncPattern(block);closeActions()">  
        <div class="uip-icon">sync</div>
        <div class="">{{strings.syncPattern}}</div>
      </div>  
      
    </div>
    
    <div class="uip-border-bottom uip-margin-top-xs uip-margin-bottom-xs"></div>
    
    <div class="uip-flex uip-flex-column">
    
      <!--Export options-->
      <drop-down dropPos="right" :hover="true" triggerClass="uip-w-100p" containerClass="uip-w-100p">
      
        <template v-slot:trigger>
        
          <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between">
            <div class="uip-icon uip-text-l">chevron_right</div>
            <div class="">{{strings.export}}</div>
          </div> 
        
        </template>
        
        <template v-slot:content>
        
          <div class="uip-flex uip-flex-column uip-padding-xs">
          
            <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" 
            @click="exportStuff('block');">
              <div class="uip-icon">download</div>
              <div class="">{{strings.exportBlock}}</div>
            </div>
            
            <div :class="block.content ? '' : 'uip-link-disabled'" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" 
            @click="exportStuff('blockcontent');">
              <div class="uip-icon">download</div>
              <div class="">{{strings.exportBlockContent}}</div>
            </div>
            
            <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" 
            @click="exportStuff('template');">
              <div class="uip-icon">download</div>
              <div class="">{{strings.exportTemplate}}</div>
            </div> 
            
            <a class="uip-hidden" ref="templateexport"></a>
          
          </div>
        
        </template>
      
      </drop-down>
      
      
      <!--Import options-->
      <drop-down dropPos="right" :hover="true" triggerClass="uip-w-100p" containerClass="uip-w-100p">
      
        <template v-slot:trigger>
        
          <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between">
            <div class="uip-icon uip-text-l">chevron_right</div>
            <div class="">{{strings.import}}</div>
          </div> 
        
        </template>
        
        <template v-slot:content>
        
          <div class="uip-flex uip-flex-column uip-padding-xs">
          
            <label :class="block.content ? '' : 'uip-link-disabled'" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" >
              <div class="uip-icon">upload</div>
              <div class="">{{strings.importBlock}}</div>
              <input hidden accept=".json" type="file" single="" @change="importSettings($event, 'block')">
            </label>
            
            
            
            <label :class="block.content ? '' : 'uip-link-disabled'" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" >
              <div class="uip-icon">upload</div>
              <div class="">{{strings.importContent}}</div>
              <input hidden accept=".json" type="file" single="" @change="importSettings($event, 'blockcontent')">
            </label>
            
            <label class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" >
              <div class="uip-icon">upload</div>
              <div class="">{{strings.importTemplate}}</div>
              <input hidden accept=".json" type="file" single="" @change="importSettings($event, 'template')">
            </label> 
            
          
          </div>
        
        </template>
      
      </drop-down>
      
      
      
      
      <!--Insert options-->
      <drop-down dropPos="right" :hover="true" triggerClass="uip-w-100p" containerClass="uip-w-100p">
      
        <template v-slot:trigger>
        
          <div :class="block.content ? '' : 'uip-link-disabled'"
          class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-muted uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between">
            <div class="uip-icon uip-text-l">chevron_right</div>
            <div class="">{{strings.insert}}</div>
          </div> 
        
        </template>
        
        <template v-slot:content v-if="block.content">
        
          <div class="uip-flex uip-flex-column uip-padding-xs uip-w-400 uip-max-h-300 uip-overflow-auto">
          
            <builder-blocks-list mode="click" :insertArea="block.content"></builder-blocks-list>
            
          </div>
        
        </template>
      
      </drop-down>
    
    </div>
    
    <div class="uip-border-bottom uip-margin-top-xs uip-margin-bottom-xs"></div>
    
    
    <div class="uip-flex uip-flex-column">
    
      <div v-if="ifEnabled('delete')" class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-link-danger uip-padding-xxxs uip-padding-left-xxs uip-padding-right-xxs hover:uip-background-muted uip-border-rounder uip-flex-reverse uip-gap-m uip-flex-between" @click="removeBlock(block);closeActions()">
        <div class="uip-icon">delete</div>
        <div class="">{{strings.delete}}</div>
      </div>  
    
    </div>
  
  </div>
  
  `,
  };
}
