/**
 * Builds the main ui builder shell
 * @since 3.0.0
 */
const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    inject: ['uipData', 'router', 'uipress', 'uiTemplate', 'layersPanel', 'unsavedChanges'],
    data: function () {
      return {
        loading: true,
        firstRender: false,
        templateID: this.$route.params.templateID,
        activeBlockID: false,
        saving: false,
        helpLoaded: false,
        allUiTemplates: [],
        blockFrames: [],
        previewWidth: 2000,
        deviceSwitcherOpen: false,
        domBlocks: [],
        isScrolling: false,
        scrollDebounce: null,
        resizer: {
          startX: 0,
          startY: 0,
          width: 0,
          height: 0,
          resizing: false,
          wrap: false,
          uid: false,
          block: false,
          blockWidth: 0,
          blockHeight: 0,
          previewBlockWidth: 0,
          previewBlockHeight: 0,
        },
        ui: {
          zoom: 0.9,
          zoomoptions: false,
          viewDevice: 'desktop',
          strings: {
            backToList: __('Exit builder', 'uipress-lite'),
            toggleLayers: __('Toggle layers panel', 'uipress-lite'),
            backToList: __('Back to template list', 'uipress-lite'),
            zoomIn: __('Zoom in', 'uipress-lite'),
            zoomOut: __('Zoom out', 'uipress-lite'),
            darkMode: __('Dark mode', 'uipress-lite'),
            preview: __('Preview', 'uipress-lite'),
            import: __('Import template', 'uipress-lite'),
            export: __('Export template', 'uipress-lite'),
            templateLibrary: __('Template Library', 'uipress-lite'),
            mobile: __('Mobile', 'uipress-lite'),
            desktop: __('Desktop', 'uipress-lite'),
            tablet: __('Tablet', 'uipress-lite'),
            saveTemplate: __('Save', 'uipress-lite'),
            help: __('Help', 'uipress-lite'),
            docs: __('Documentation and guides', 'uipress-lite'),
            active: __('Active', 'uipress-lite'),
            draft: __('Draft', 'uipress-lite'),
            newTemplate: __('New template', 'uipress-lite'),
            recentTemplates: __('Recent templates', 'uipress-lite'),
            templateName: __('Template name', 'uipress-lite'),
            active: __('Active', 'uipress-lite'),
            draft: __('Draft', 'uipress-lite'),
            recenter: __('Re-center', 'uipress-lite'),
            showGridLines: __('Show grid lines', 'uipress-lite'),
            frame: __('frame', 'uipress-lite'),
            zoom100: __('Zoom to 100%', 'uipress-lite'),
          },
        },
        previewOptions: [
          {
            value: 'builder',
            label: __('Builder', 'uipress-lite'),
          },
          {
            value: 'preview',
            label: __('Preview', 'uipress-lite'),
          },
        ],
      };
    },
    provide() {
      return {
        saveTemplate: this.saveCleanTemplate,
      };
    },
    watch: {
      'ui.viewDevice': {
        handler(newValue, oldValue) {
          let self = this;
          if (newValue == 'desktop') {
            self.uiTemplate.windowWidth = '1000';
            let frame = document.getElementById('uip-preview-content');
            if (frame) {
              frame.classList.add('uip-desktop-view');
              frame.classList.remove('uip-tablet-view');
              frame.classList.remove('uip-phone-view');
            }
          }
          if (newValue == 'tablet') {
            self.uiTemplate.windowWidth = '699';
            let frame = document.getElementById('uip-preview-content');
            if (frame) {
              frame.classList.add('uip-tablet-view');
              frame.classList.remove('uip-desktop-view');
              frame.classList.remove('uip-phone-view');
            }
          }
          if (newValue == 'phone') {
            self.uiTemplate.windowWidth = '600';
            let frame = document.getElementById('uip-preview-content');
            if (frame) {
              frame.classList.add('uip-phone-view');
              frame.classList.remove('uip-tablet-view');
              frame.classList.remove('uip-desktop-view');
            }
          }
          let previewwidthChange = new CustomEvent('uip_builder_preview_change', { detail: { windowWidth: self.uiTemplate.windowWidth } });
          document.dispatchEvent(previewwidthChange);
          requestAnimationFrame(() => {
            this.scrollToPreview();
          });
        },
        deep: true,
      },
      'uiTemplate.content': {
        handler(newValue, oldValue) {
          let self = this;
          if (oldValue.length != 0) {
            this.unsavedChanges = true;
          }
          setTimeout(function () {
            self.manageBlockPositions();
          }, 400);
        },
        deep: true,
      },
      'uipData.templateDarkMode': {
        handler(newValue, oldValue) {
          let theme = 'light';
          if (newValue) {
            theme = 'dark';
          }
          let frame = document.getElementsByClassName('uip-page-content-frame');
          if (frame[0]) {
            frame[0].contentWindow.document.documentElement.setAttribute('data-theme', theme);
          }
        },
        deep: true,
      },
      'uipData.userPrefs.darkTheme': {
        handler(newValue, oldValue) {
          //Only adjust preview dark mode if we are not in prod
          if (this.uiTemplate.display != 'prod') {
            this.uipData.templateDarkMode = newValue;
          }
        },
        deep: true,
      },
      'ui.zoom': {
        handler(newValue, oldValue) {
          let self = this;
          let rounded = Math.round(newValue * 10) / 10;
          //Only adjust preview dark mode if we are not in prod
          self.uipress.saveUserPreference('builderPrefersZoom', String(rounded), false);
          self.isScrolling = true;
          setTimeout(function () {
            requestAnimationFrame(() => {
              self.isScrolling = false;
              self.manageBlockPositions();
            });
          }, 300);
        },
        deep: true,
      },
      'uiTemplate.isPreview': {
        handler(newValue, oldValue) {
          this.manageBlockPositions();
        },
        deep: true,
      },
      'uipData.userPrefs.builderPrefersZoom': {
        handler(newValue, oldValue) {
          this.ui.zoom = this.uipData.userPrefs.builderPrefersZoom;
        },
        deep: true,
      },
      '$route.params.templateID': {
        handler() {
          this.templateID = this.$route.params.templateID;
        },
      },
      '$route.params.uid': {
        handler() {
          this.activeBlockID = this.$route.params.uid;
          this.buildDomObjects();
        },
      },
    },
    created: function () {
      this.setTheme();
    },
    mounted: function () {
      let self = this;

      self.uipress.saveTemplate = this.saveCleanTemplate;
      //Set zoom level from prefs
      let zoom = parseFloat(this.uipData.userPrefs.builderPrefersZoom);
      if (zoom && typeof zoom !== 'undefined') {
        this.ui.zoom = zoom;
      }
      let isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
      let isMacLike = navigator.platform.match(/(Mac|iPhone|iPod|iPad)/i) ? true : false;
      let isIOS = navigator.platform.match(/(iPhone|iPod|iPad)/i) ? true : false;

      if (isMac || isMacLike || isIOS) {
        document.body.classList.add('macos');
      }

      window.addEventListener('keydown', function (e) {
        ///CMD S
        if (e.key === '-' && (e.metaKey || e.ctrlKey)) {
          e.preventDefault();
          self.zoomOut();
        }
        if ((e.key === '+' || e.code == 'Equal') && (e.metaKey || e.ctrlKey)) {
          e.preventDefault();
          self.zoomIn();
        }

        if (e.key === '0' && (e.metaKey || e.ctrlKey)) {
          e.preventDefault();
          self.ui.zoom = 1;
          self.uipData.userPrefs.builderPrefersZoom = 1;
        }
      });

      //Mounted
      requestAnimationFrame(() => {
        this.loading = false;
        this.firstRender = true;
        this.activeBlockID = this.$route.params.uid;
        this.$nextTick(() => {
          this.scrollToPreview();
        });
      });

      this.addDragListeners();

      let preview = document.getElementById('uip-preview-canvas');
      if (preview) {
        let observer = new MutationObserver(() => {
          self.manageBlockPositions;
        });
        observer.observe(preview, { attributes: true, childList: false, subtree: false });
      }
    },
    computed: {
      returnDomObjects() {
        if (this.loading && this.firstRender) {
          return [];
        }
        return this.domBlocks;
      },
      returnHumanZoom() {
        return parseInt(this.ui.zoom * 100) + '%';
      },
      getPreviewScale() {
        let zoom = this.ui.zoom;
        return `transform: scale( ${zoom} );`;
      },
      returnActiveBlockUID() {
        return this.activeBlockID;
      },
      returnAllUiTemplates() {
        let self = this;
        if (self.allUiTemplates.length < 1) {
          let formData = new FormData();
          formData.append('action', 'uip_get_ui_templates');
          formData.append('security', uip_ajax.security);
          formData.append('page', 1);
          formData.append('search', '');

          self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
            this.allUiTemplates = response.templates;
            return this.allUiTemplates;
          });
        } else {
          return this.allUiTemplates;
        }
      },
      returnLayout() {
        return this.uiTemplate.layout;
      },
      returnTemplateJS() {
        if (typeof this.uiTemplate.globalSettings.options === 'undefined') {
          return;
        }
        if ('advanced' in this.uiTemplate.globalSettings.options) {
          if ('js' in this.uiTemplate.globalSettings.options.advanced) {
            return this.uiTemplate.globalSettings.options.advanced.js;
          }
        }
      },
      returnTemplateCSS() {
        if (typeof this.uiTemplate.globalSettings.options === 'undefined') {
          return;
        }
        if ('advanced' in this.uiTemplate.globalSettings.options) {
          if ('css' in this.uiTemplate.globalSettings.options.advanced) {
            return this.uiTemplate.globalSettings.options.advanced.css;
          }
        }
      },
      returnCanvasWidth() {
        let width = 2000;
        if (this.ui.viewDevice == 'tablet') {
          width = 990;
        }
        if (this.ui.viewDevice == 'phone') {
          width = 700;
        }
        return 'width:' + parseFloat(width * this.ui.zoom) + 'px;';
      },
      returnCanvasWidthMax() {
        let width = 2000;
        if (this.ui.viewDevice == 'tablet') {
          width = 990;
        }
        if (this.ui.viewDevice == 'phone') {
          width = 700;
        }

        return 'min-width:' + parseFloat(width * this.ui.zoom) + 'px;';
      },
      getBlockFrames() {
        let self = this;
        self.blockFrames = [];
        self.uipress.findBYmodnameAndReturn(self.uiTemplate.content, ['uip-dropdown', 'uip-block-modal', 'uip-slide-out', 'uip-accordion'], self.blockFrames);
        return self.blockFrames;
      },
    },
    methods: {
      returnFrameWidth(uid) {
        let blockFrame = document.getElementById('block-frame-' + uid);
        if (!blockFrame) {
          return;
        }

        let width = blockFrame.getBoundingClientRect().width;
        if (width == 300) {
          return 'width:' + parseFloat(width) + 'px;';
        }
        let calc = parseFloat(width * this.ui.zoom);

        return 'width:' + calc + 'px;';
      },
      addDragListeners() {
        ///Mousedown
        let pos = { top: 0, left: 0, x: 0, y: 0 };
        let canvas = document.getElementById('uip-preview-canvas');
        let preview = document.getElementById('uip-frame-wrap');
        let blockCanvases = document.getElementsByClassName('uip-block-canvas');

        const mouseDownHandler = function (e) {
          //Right click
          if (e.button == 2) {
            return;
          }
          // Change the cursor and prevent user from selecting the text

          //Make sure we are not dragging on a block area
          for (let blockCanvas of blockCanvases) {
            if (blockCanvas.contains(e.target)) {
              return;
            }
          }

          canvas.style.cursor = 'grabbing';
          canvas.style.userSelect = 'none';

          pos = {
            // The current scroll
            left: canvas.scrollLeft,
            top: canvas.scrollTop,
            // Get the current mouse position
            x: e.clientX,
            y: e.clientY,
          };

          document.addEventListener('mousemove', mouseMoveHandler);
          document.addEventListener('mouseup', mouseUpHandler);
        };

        const mouseMoveHandler = function (e) {
          // How far the mouse has been moved
          const dx = e.clientX - pos.x;
          const dy = e.clientY - pos.y;

          // Scroll the element
          canvas.scrollTop = pos.top - dy;
          canvas.scrollLeft = pos.left - dx;
        };

        const mouseUpHandler = function () {
          document.removeEventListener('mousemove', mouseMoveHandler);
          document.removeEventListener('mouseup', mouseUpHandler);

          canvas.style.cursor = 'default';
          canvas.style.removeProperty('user-select');
        };

        canvas.addEventListener('mousedown', mouseDownHandler);
      },
      scrollToPreview() {
        let self = this;
        document.getElementById('uip-preview-content').scrollIntoView({
          behavior: 'instant',
          block: 'center',
          inline: 'center',
        });
        document.getElementById('uip-frame-wrap').scrollIntoView({
          behavior: 'instant',
          block: 'nearest',
          inline: 'center',
        });

        requestAnimationFrame(() => {
          setTimeout(function () {
            self.buildDomObjects();
          }, 200);
        });
      },
      returnColorMode() {
        if (this.uipData.userPrefs.darkTheme) {
          return 'dark';
        }
        if (this.uipData.templateDarkMode) {
          return 'dark';
        }
        return 'light';
      },

      saveTemplate() {
        let self = this;
        self.saving = true;
        let cleanTemplate = JSON.parse(JSON.stringify(self.uiTemplate.content));
        this.uipress.cleanTemplate(cleanTemplate).then((response) => {
          self.saveCleanTemplate(cleanTemplate);
        });
      },

      async saveCleanTemplate(cleanTemplate) {
        let self = this;
        let savetemplate = {};
        savetemplate.globalSettings = JSON.parse(JSON.stringify(self.uiTemplate.globalSettings));
        savetemplate.content = cleanTemplate;

        let template = JSON.stringify(savetemplate, (k, v) => (v === 'true' ? 'uiptrue' : v === true ? 'uiptrue' : v === 'false' ? 'uipfalse' : v === false ? 'uipfalse' : v === '' ? 'uipblank' : v));

        let styles = this.formatStyles();
        let stylesJson = JSON.stringify(styles, (k, v) => (v === 'true' ? 'uiptrue' : v === true ? 'uiptrue' : v === 'false' ? 'uipfalse' : v === false ? 'uipfalse' : v === '' ? 'uipblank' : v));
        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_save_ui_template');
        formData.append('security', uip_ajax.security);
        formData.append('templateID', self.templateID);
        formData.append('template', template);
        formData.append('styles', stylesJson);

        return await self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, 'uipress-lite', '', 'error', true);
            self.saving = false;
            return false;
          }
          if (response.success) {
            self.uipress.notify(__('Template saved', 'uipress-lite'), '', 'success', true);
            self.unsavedChanges = false;
            self.saving = false;

            let newTem = JSON.parse(JSON.stringify(self.uiTemplate.content));
            self.uipress.logHistoryChange(__('Template saved', 'uipress-lite'), newTem, newTem);
            return true;
          }
        });
      },
      formatStyles() {
        let styles = this.uipData.themeStyles;
        let formatted = {};
        for (let key in styles) {
          if (styles[key].value) {
            if (!formatted[styles[key].name]) {
              formatted[styles[key].name] = {};
            }
            formatted[styles[key].name].value = styles[key].value;
          }
          if (styles[key].darkValue) {
            if (!formatted[styles[key].name]) {
              formatted[styles[key].name] = {};
            }
            formatted[styles[key].name].darkValue = styles[key].darkValue;
          }
          if (styles[key].user) {
            formatted[styles[key].name].user = styles[key].user;
            formatted[styles[key].name].label = styles[key].label;
            formatted[styles[key].name].name = styles[key].name;
            formatted[styles[key].name].type = styles[key].type;
          }
        }

        return formatted;
      },
      goBackToList() {
        if (this.unsavedChanges) {
          this.uipress.confirm(__('You have unsaved changes!', 'uipress-lite'), __('If you leave this page all unsaved changes will be discarded', 'uipress-lite')).then((response) => {
            if (response) {
              this.router.push('/');
            }
          });
        } else {
          this.router.push('/');
        }
      },
      returnLoadStyle() {
        if (this.saving) {
          return 'opacity:0;';
        }
      },
      toggleLayers() {
        this.layersPanel.display = !this.layersPanel.display;

        this.uipress.saveUserPreference('builderLayers', this.layersPanel.display, false);
      },
      toggleDisplay() {
        if (this.uiTemplate.display == 'preview') {
          this.uiTemplate.display = 'builder';
        } else {
          this.uiTemplate.display = 'preview';
        }
      },
      preTemplateExport() {
        let self = this;
        let notiID = self.uipress.notify(__('Exporting layout', 'uipress-lite'), '', 'default', false, true);
        self.uipress.cleanTemplate(self.uiTemplate.content).then((response) => {
          self.exportLayout(notiID);
        });
      },
      exportLayout(notiID) {
        self = this;
        let layout = JSON.stringify(self.uiTemplate.content);
        let name = self.uiTemplate.globalSettings.name;

        let today = new Date();
        let dd = String(today.getDate()).padStart(2, '0');
        let mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        let yyyy = today.getFullYear();

        let date_today = mm + '-' + dd + '-' + yyyy;
        let filename = 'uip-ui-template-' + name + '-' + date_today + '.json';

        let dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(layout);
        let dlAnchorElem = document.getElementById('uip-export-layout');
        dlAnchorElem.setAttribute('href', dataStr);
        dlAnchorElem.setAttribute('download', filename);
        dlAnchorElem.click();
        self.uipress.notify(__('Layout exported', 'uipress-lite'), '', 'success', true);
        self.uipress.destroy_notification(notiID);
      },
      importSettings() {
        let self = this;
        let notiID = self.uipress.notify(__('Importing layout', 'uipress-lite'), '', 'default', false, true);

        let fileInput = document.getElementById('uip-import-layout');
        let thefile = fileInput.files[0];

        if (thefile.type != 'application/json') {
          self.uipress.notify('Templates must be in valid JSON format', '', 'error', true, false);
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
            if (!Array.isArray(parsed)) {
              self.uipress.notify('Template is not valid', '', 'error', true, false);
              self.uipress.destroy_notification(notiID);
              return;
            }
            self.uipress.validDateTemplate(parsed).then((response) => {
              if (!response.includes(false)) {
                self.uiTemplate.content = parsed;
                self.uipress.notify('Template imported', '', 'success', true, false);
                self.uipress.destroy_notification(notiID);
              } else {
                self.uipress.notify('File is not a valid JSON template', '', 'error', true, false);
                self.uipress.destroy_notification(notiID);
              }
            });
          } else {
            self.uipress.notify('JSON parse failed', '', 'error', true, false);
            self.uipress.destroy_notification(notiID);
          }
        };
      },
      openThemeLibrary() {
        let ID = this.$route.params.templateID;
        this.router.push('/uibuilder/' + ID + '/library');
      },
      switchLayout(id) {
        let self = this;
        if (this.unsavedChanges) {
          this.uipress.confirm(__('You have unsaved changes!', 'uipress-lite'), __('If you leave this page all unsaved changes will be discarded', 'uipress-lite')).then((response) => {
            if (response) {
              self.router.push('/uibuilder/' + id + '/');
              self.unsavedChanges = false;
            }
          });
        } else {
          self.router.push('/uibuilder/' + id + '/');
        }
      },
      confirmNewPage(id) {
        let self = this;
        if (this.unsavedChanges) {
          this.uipress.confirm(__('You have unsaved changes!', 'uipress-lite'), __('If you leave this page all unsaved changes will be discarded', 'uipress-lite')).then((response) => {
            if (response) {
              self.createNewUI();
            }
          });
        } else {
          self.createNewUI();
        }
      },
      /**
       * Creates new draft ui template
       * @since 3.0.0
       */
      createNewUI() {
        let self = this;

        let formData = new FormData();
        formData.append('action', 'uip_create_new_ui_template');
        formData.append('security', uip_ajax.security);
        formData.append('templateType', 'ui-template');

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.uipress.notify('New template created', '', 'success', true, false);
          self.router.push('/');
          self.router.push('/uibuilder/' + response.id + '/');
          self.returnAllUiTemplates;
        });
      },
      clickHandler(evt) {
        let self = this;

        let target = evt.target.closest('[block-uid]');

        if (!target) {
          return;
        }
        let targetUID = target.getAttribute('block-uid');
        //No block to select
        if (!targetUID) {
          return;
        }

        //Open block settings
        this.openBlockSettings(targetUID);
      },
      openBlockSettings(targetUID) {
        let ID = this.$route.params.templateID;
        this.router.push({
          path: '/uibuilder/' + ID + '/settings/blocks/' + targetUID,
          query: { ...this.$route.query },
        });
      },
      zoomOut() {
        this.ui.zoom -= 0.1;
        this.uipData.userPrefs.builderPrefersZoom = this.ui.zoom;
      },
      zoomIn() {
        this.ui.zoom += 0.1;
        this.uipData.userPrefs.builderPrefersZoom = this.ui.zoom;
      },
      returnThemeIcon() {
        if (this.uipData.userPrefs.darkTheme) {
          return 'light_mode';
        }
        return 'dark_mode';
      },
      addWidthWatchers(uid) {
        let self = this;
        const observer = new ResizeObserver((entries) => {
          entries.forEach((entry) => {
            if (self.resizer.resizing || self.isScrolling) {
              return;
            }
            //let wrap = document.getElementById('block-frame-wrap-' + uid);
            let wrapTitle = document.getElementById('block-frame-title-' + uid);
            //if (wrap) {
            //wrap.style.width = entry.target.getBoundingClientRect().width + 'px';
            //}
            if (wrapTitle) {
              wrapTitle.style.width = entry.target.getBoundingClientRect().width + 'px';
            }
            this.manageBlockPositions();
          });
        });

        let elm = document.getElementById('block-frame-' + uid);
        if (elm) {
          observer.observe(elm);
        }
      },
      buildDomObjects() {
        for (let block of this.domBlocks) {
          if ('observer' in block) {
            block.observer.disconnect();
          }
        }
        this.domBlocks = [];

        let uid = this.$route.params.uid;
        if (!uid) {
          this.domBlocks = [];
          return this.domBlocks;
        }
        let markedBlocks = document.querySelectorAll(`#uip-preview-canvas [block-uid='${uid}']`);
        if (!markedBlocks) {
          this.domBlocks = [];
          return this.domBlocks;
        }
        if (markedBlocks.length === 0) {
          return this.domBlocks;
        }
        for (let domBlock of markedBlocks) {
          // Start observing the target node for configured mutations
          if (domBlock.closest('[uip-block-query="true"]')) {
            continue;
          }
          let observer = new IntersectionObserver(this.manageBlockPositions);
          observer.observe(domBlock, { attributes: true, childList: false, subtree: false });
          ///
          let rect = domBlock.getBoundingClientRect();
          this.domBlocks.push({ dom: domBlock, uid: uid, left: rect.left, top: rect.top, width: rect.width, height: rect.height, observer: observer });
        }
        return this.domBlocks;
      },
      manageBlockPositions() {
        if (this.resizer.resizing) {
          return;
        }
        for (let block of this.domBlocks) {
          let rect = block.dom.getBoundingClientRect();

          if (rect.left != block.left || rect.top != block.top || rect.width != block.width || rect.height != block.height) {
            this.buildDomObjects();
          }
        }
      },
      handleScroll(event) {
        const scrollContainer = this.$refs.previewCanvas;

        clearTimeout(this.scrollDebounce);

        this.isScrolling = true;
        this.scrollDebounce = setTimeout(() => {
          this.isScrolling = false;
          this.manageBlockPositions();
        }, 150);
      },
      setSelectedWrapPos(item) {
        if (item) {
          let style = '';
          style += `width:${item.width}px;`;
          style += `height:${item.height}px;`;
          style += `left:${item.left}px;`;
          style += `top:${item.top}px;`;
          style += `pointer-events: none;`;
          return style;
        }
      },
      initResizeBlock(event, domBlock, type) {
        event.preventDefault();

        this.resizer.startX = event.clientX;
        this.resizer.startY = event.clientY;

        let parent = event.target.parentNode;

        this.resizer.resizing = true;
        //Dynamic widths set by another function will be disabled so set values before drag starts

        this.resizer.wrap = parent;
        this.resizer.width = this.resizer.wrap.getBoundingClientRect().width;
        this.resizer.height = this.resizer.wrap.getBoundingClientRect().height;
        this.resizer.uid = domBlock.uid;
        this.resizer.type = type;
        this.resizer.originalSize = this.resizer.wrap.getBoundingClientRect();
        this.resizer.domItem = domBlock.dom;

        this.uipress.searchForBlock(this.uiTemplate.content, this.resizer.uid).then((block) => {
          this.resizer.block = block;

          let width = this.uipress.checkNestedValue(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'width', 'value']);
          if (width && width != ' ') {
            this.resizer.blockWidth = width;
            this.resizer.previewBlockWidth = width;
          } else {
            let wrapWidth = this.resizer.wrap.getBoundingClientRect().width;
            let newWidth = (wrapWidth / this.ui.zoom) * 1;
            newWidth = newWidth.toFixed(0);
            this.resizer.blockWidth = newWidth;
            this.resizer.previewBlockWidth = newWidth;
          }

          let height = this.uipress.checkNestedValue(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'height', 'value']);
          if (height && height != ' ') {
            this.resizer.blockHeight = height;
            this.resizer.previewBlockHeight = height;
          } else {
            let wrapHeight = this.resizer.wrap.getBoundingClientRect().height;
            let newHeight = (wrapHeight / this.ui.zoom) * 1;
            newHeight = newHeight.toFixed(0);
            this.resizer.blockHeight = newHeight;
            this.resizer.previewBlockHeight = newHeight;
          }
        });

        this.resizer.wrap.classList.add('uip-block-resizing');

        document.addEventListener('mousemove', this.resizeBlock);
        document.addEventListener('mouseup', this.stopResizeBlock);
      },
      resizeBlock(event) {
        event.preventDefault();
        if (!this.resizer.resizing) return;
        const dx = event.clientX - this.resizer.startX;
        const dy = event.clientY - this.resizer.startY;

        let listItem = this.domBlocks.find((obj) => {
          return obj.uid === this.resizer.uid;
        });

        if (this.$refs.resizingdigits) {
          for (let helper of this.$refs.resizingdigits) {
            if (this.resizer.type == 'left' || this.resizer.type == 'left-top') {
              helper.style.left = event.clientX - 60 + 'px';
              helper.style.top = event.clientY - 10 + 'px';
            } else {
              helper.style.left = event.clientX + 20 + 'px';
              helper.style.top = event.clientY - 10 + 'px';
            }
          }
        }

        if (dx !== 0) {
          if (this.resizer.type == 'left' || this.resizer.type == 'left-top') {
            listItem.width = this.resizer.width - dx;
            let currentWidth = parseInt(this.resizer.blockWidth) - parseInt((dx / this.ui.zoom) * 1);
            this.resizer.previewBlockWidth = parseInt(currentWidth);
            listItem.left = this.resizer.originalSize.left + dx;
          } else {
            listItem.width = this.resizer.width + dx;
            let currentWidth = parseInt(this.resizer.blockWidth) + parseInt((dx / this.ui.zoom) * 1);
            this.resizer.previewBlockWidth = parseInt(currentWidth);
          }
        }
        if (dy !== 0) {
          if (this.resizer.type == 'top' || this.resizer.type == 'left-top') {
            listItem.height = this.resizer.height - dy;
            let currentHeight = parseInt(this.resizer.blockHeight) - parseInt((dy / this.ui.zoom) * 1);
            this.resizer.previewBlockHeight = parseInt(currentHeight);
            listItem.top = this.resizer.originalSize.top + dy;
          } else {
            listItem.height = this.resizer.height + dy;
            let currentHeight = parseInt(this.resizer.blockHeight) + parseInt((dy / this.ui.zoom) * 1);
            this.resizer.previewBlockHeight = parseInt(currentHeight);
          }
        }
      },
      stopResizeBlock() {
        this.resizer.wrap.classList.remove('uip-block-resizing');
        this.resizer.resizing = false;

        //Update width
        //////////////
        let wrapWidth = this.resizer.wrap.getBoundingClientRect().width;
        let percentChangeWidth = wrapWidth / this.resizer.width;

        if (percentChangeWidth != 0) {
          let width = this.uipress.checkNestedValue(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'width', 'value']);
          let widthUnits = this.uipress.checkNestedValue(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'width', 'units']);

          if (width && widthUnits) {
            let newWidth = (width * percentChangeWidth).toFixed(0);
            this.resizer.block.settings.style.options.dimensions.value.width.value = newWidth;
          }
          if (!width || width == ' ') {
            this.uipress.createNestedObject(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'width', 'value']);
            this.uipress.createNestedObject(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'width', 'units']);

            let newWidth = (wrapWidth / this.ui.zoom) * 1;
            newWidth = newWidth.toFixed(0);
            this.resizer.block.settings.style.options.dimensions.value.width.value = newWidth;
            this.resizer.block.settings.style.options.dimensions.value.width.units = 'px';
          }
        }

        ////Update height
        /////////////////

        let wrapHeight = this.resizer.wrap.getBoundingClientRect().height;
        let percentChangeHeight = wrapHeight / this.resizer.height;

        if (percentChangeHeight != 0) {
          let height = this.uipress.checkNestedValue(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'height', 'value']);
          let heightUnits = this.uipress.checkNestedValue(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'height', 'units']);

          if (height && heightUnits) {
            let newHeight = (height * percentChangeHeight).toFixed(0);
            this.resizer.block.settings.style.options.dimensions.value.height.value = newHeight;
          }

          if (!height || height == ' ') {
            this.uipress.createNestedObject(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'height', 'value']);
            this.uipress.createNestedObject(this.resizer.block, ['settings', 'style', 'options', 'dimensions', 'value', 'height', 'units']);

            let newHeight = (wrapHeight / this.ui.zoom) * 1;
            newHeight = newHeight.toFixed(0);
            this.resizer.block.settings.style.options.dimensions.value.height.value = newHeight;
            this.resizer.block.settings.style.options.dimensions.value.height.units = 'px';
          }
        }

        //Update block positions
        requestAnimationFrame(() => {
          this.buildDomObjects();
        });

        document.removeEventListener('mousemove', this.resizeBlock);
        document.removeEventListener('mouseup', this.stopResizeBlock);
      },
      returnContentDropStyles(block) {
        let options = this.uipress.checkNestedValue(block, ['settings', 'contentStyle', 'options']);

        if (!options) {
          return;
        }
        let styles = this.uipress.explodeSpecificBlockSettings(options, 'style', this.uipData.templateDarkMode, null, 'flexLayout');

        if (typeof styles == 'undefined') {
          return '';
        }
        if (styles.includes('display:grid;')) {
          this.forceFlex = true;
        } else {
          this.forceFlex = false;
        }
        return styles;
      },
      toggleDarkMode() {
        this.uipData.userPrefs.darkTheme = !this.uipData.userPrefs.darkTheme;
        this.setTheme();
        this.uipress.saveUserPreference('darkTheme', this.uipData.userPrefs.darkTheme, false);
      },
      setTheme() {
        let theme = 'light';
        if (this.uipData.userPrefs.darkTheme) {
          theme = 'dark';
          this.uipData.userPrefs.darkTheme = true;
        } else {
          this.uipData.userPrefs.darkTheme = false;
        }
        document.getElementsByTagName('html')[0].setAttribute('data-theme', theme);
        let frames = document.getElementsByClassName('uip-page-content-frame');
        if (frames[0]) {
          for (const iframe of frames) {
            iframe.contentWindow.document.documentElement.setAttribute('data-theme', theme);
          }
        }
      },
    },
    template: `
      <component is="style" scoped >
        .uip-user-frame[data-theme="light"]:not(.uip-app-frame){
        <template v-for="item in uipData.themeStyles">
           <template v-if="item.value">{{item.name}}:{{item.value}};</template>
        </template>
        }
        .uip-user-frame[data-theme="dark"]:not(.uip-app-frame) *{
        <template v-for="item in uipData.themeStyles">
           <template v-if="item.darkValue"> {{item.name}}:{{item.darkValue}};</template>
        </template>
        }
        {{returnTemplateCSS}}
      </component>
      <component is="script" scoped >
        {{returnTemplateJS}}
      </component>

      
      
      <div ref="previewcontainer" class="uip-h-100p uip-w-100p uip-flex uip-flex-column uip-max-h-100p uip-overflow-hidden">
        
        <!--preview area -->
        
        <div id="uip-preview-canvas" ref="previewCanvas" class="uip-flex-grow uip-flex-grow uip-flex-middle uip-hide-scrollbar uip-overflow-auto uip-max-w-100p uip-max-h-100p uip-w-100p uip-border-box"
        v-on:scroll.native="handleScroll($event)">
        
            <div id="uip-ui-preview-area" class="uip-user-frame uip-padding-m uip-flex uip-flex-center uip-flex-middle" style="width:20000px;height:20000px" :data-theme="returnColorMode()">
              
              <div ref="framewrap" class="uip-flex uip-gap-m uip-flex-start uip-w-auto" id="uip-frame-wrap">
                
                <!--Primary template -->
                <div class="uip-row-gap-s uip-flex uip-flex-column ">
                
                 
                  <div class="uip-background-muted uip-border uip-w-100p uip-padding-xxs uip-padding-left-xs uip-flex uip-gap-xs uip-flex-center" 
                  style="border-radius: calc( var(--uip-border-radius) + var(--uip-padding-xxs) );"
                  :style="returnCanvasWidthMax" :class="uiTemplate.isPreview ? 'uip-opacity-0' : ''">
                  
                    <div class="uip-text-muted uip-text-bold uip-flex-grow">{{uiTemplate.globalSettings.name}}</div>
                    
                    <!--Device switcher-->
                    
                    <drop-down dropPos="bottom-right" :relative="true" slotClass="uip-padding-xs">
                    
                      <template v-slot:trigger>
                  
                        <div class="uip-padding-left-xs uip-padding-right-xs uip-border-rounder uip-flex uip-gap-xs uip-flex-between uip-link-default uip-flex-center" style=""  
                        @click="deviceSwitcherOpen = !deviceSwitcherOpen">
                            <div class="">
                              <template v-if="ui.viewDevice == 'desktop'">{{ui.strings.desktop}}</template>
                              <template v-if="ui.viewDevice == 'tablet'">{{ui.strings.tablet}}</template>
                              <template v-if="ui.viewDevice == 'phone'">{{ui.strings.mobile}}</template>
                            </div>
                            <div class="uip-icon uip-text-l">expand_more</div>
                        </div>
                    
                      </template>
                      
                      <template v-slot:content>
                      
                        <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-gap-l uip-flex-between uip-link-default" 
                        @click="ui.viewDevice = 'desktop';deviceSwitcherOpen = false;">
                          
                          <div class="">{{ui.strings.desktop}}</div>
                          <div class="uip-flex uip-flex-center uip-text-muted uip-gap-xxxs">
                            <span class="uip-icon" style="line-height:0">desktop_windows</span>
                          </div>
                          
                        </div> 
                        
                        <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-gap-l uip-flex-between uip-link-default" 
                        @click="ui.viewDevice = 'tablet';deviceSwitcherOpen = false;">
                          
                          <div class="">{{ui.strings.tablet}}</div>
                          <div class="uip-flex uip-flex-center uip-text-muted uip-gap-xxxs">
                            <span class="uip-icon">tablet_mac</span>
                          </div>
                          
                        </div> 
                        
                        
                        <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-gap-l uip-flex-between uip-link-default" 
                        @click="ui.viewDevice = 'phone';deviceSwitcherOpen = false">
                          
                          <div class="">{{ui.strings.mobile}}</div>
                          <div class="uip-flex uip-flex-center uip-text-muted uip-gap-xxxs">
                            <span class="uip-icon">smartphone</span>
                          </div>
                          
                        </div> 
                      
                      </template>
                      
                    </drop-down>
                        
                    
                  </div>
                
                  <div class="uip-position-relative" :class="uiTemplate.globalSettings.type">
                    <div id="uip-preview-content" class="uip-flex uip-flex-column uip-text-normal uip-desktop-view uip-position-relative uip-block-canvas uip-position-absolute" style="width:2000px;transform-origin: 0% 0%;transform:scale(1)"  :style="getPreviewScale">
                      
                      <!--PAGE BODY-->
                      <div ref="templatebody" id="uip-template-body" class="uip-flex uip-flex-grow uip-text-normal uip-body-font uip-background-default uip-border-round uip-border"
                      @click="clickHandler($event)" >
                    
                          <!--MAIN DROP AREA-->
                          <uip-content-area :content="uiTemplate.content" :returnData="function(data) {uiTemplate.content = data}"></uip-content-area>
                          <!--END OF MAIN DROP AREA-->
                        
                      </div>
                    </div>
                  </div>
                  
                
                </div>
                <!--End primary template-->
                
                <!--Output frames-->
                
                <template v-for="block in getBlockFrames">
                
                  <div class="uip-row-gap-s uip-flex uip-flex-column" :id="'block-frame-wrap-' + block.uid" :class="uiTemplate.isPreview ? 'uip-opacity-0' : ''">
                  
                  
                    <div class="uip-background-muted uip-border uip-w-100p uip-padding-xxs uip-flex uip-gap-xs uip-flex-center uip-link-muted" 
                    :id="'block-frame-title-' + block.uid"
                    style="border-radius: calc( var(--uip-border-radius) + var(--uip-padding-xxs) );"  
                    @click="openBlockSettings(block.uid)">
                    
                      <div :title="ui.strings.preview" class="uip-icon uip-text-l uip-border-rounder" style="">crop_free</div>
                      <div class="uip-text-muted uip-text-bold uip-flex-grow uip-no-wrap uip-overflow-hidden uip-text-ellipsis">{{block.name}} {{ui.strings.frame}}</div>
                      
                    </div>
                    
                    
                    <!--Frame content-->
                    <div class="uip-position-relative">
                      <div class="uip-position-relative uip-frame-content uip-position-absolute">
                      
                        <div class="uip-text-normal uip-body-font uip-background-default uip-border-round uip-border uip-min-h-300 uip-min-w-200 uip-overflow-visible uip-block-canvas uip-flex" 
                        :id="'block-frame-' + block.uid"
                        :style="'transform: scale( ' + ui.zoom + ');transform-origin: 0% 0%;'">
                          <div class="uip-flex-grow uip-h-100p uip-overflow-visible uip-flex-no-shrink" @click="clickHandler($event)" 
                          :class="returnActiveBlockUID == block.uid ? 'uip-preview-selected-block' : ''"
                          :block-uid="block.uid">
                            <!--BLOCK MAIN DROP AREA-->
                            <uip-content-area :content="block.content" :returnData="function(data) {block.content = data}" :dropAreaStyle="returnContentDropStyles(block)"></uip-content-area>
                            <!--END OF MAIN DROP AREA-->
                          </div>
                          
                        </div>
                      
                      </div>
                    </div>
                    
                   {{addWidthWatchers(block.uid)}}
                    
                  </div>  
                
                </template>
                
                <!--Endframes-->
              
              </div>
              
            </div>
          
          
        </div>
        <!--end of preview area -->
        
        
        <div class="uip-position-fixed uip-bottom-0 uip-left-50p uip-translateX--50p uip-z-index-1" style="bottom:32px">
          
          
          
            <div class="uip-flex uip-flex-center uip-padding-xs uip-background-default uip-border-rounder uip-shadow uip-border">
            
                
                <drop-down dropPos="top-left" :dontAnimate="true" :relative="true">
                  
                  <template v-slot:trigger>
                
                    <div class="uip-background-muted uip-padding-xxs uip-border-rounder uip-flex uip-gap-s uip-flex-between uip-link-default uip-flex-center">
                        <div class="">{{returnHumanZoom}}</div>
                        <div class="uip-icon uip-text-l">expand_less</div>
                    </div>
                  
                  </template>
                  
                  <template v-slot:content>
                    <div class="uip-padding-xs uip-flex uip-flex-column uip-text-s">
                      
                      <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-gap-l uip-flex-between uip-link-default" @click="zoomOut()">
                        
                        <div class="uip-no-wrap">{{ui.strings.zoomOut}}</div>
                        <div class="uip-flex uip-flex-center uip-text-muted uip-gap-xxxs">
                          
                          <span class="uip-command-icon"></span>
                          <span class="uip-icon" style="line-height:0">remove</span>
                          
                        </div>
                        
                      </div> 
                      
                      <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-gap-l uip-flex-between uip-link-default" @click="zoomIn()">
                        
                        <div class="uip-no-wrap">{{ui.strings.zoomIn}}</div>
                        <div class="uip-flex uip-flex-center uip-text-muted uip-gap-xxxs">
                        
                          <span class="uip-command-icon"></span>
                          <span class="uip-icon" style="line-height:0">add</span>
                          
                        </div>
                        
                      </div> 
                      
                      
                      <div class="uip-flex uip-flex-row uip-flex-center uip-gap-xs uip-padding-xxs hover:uip-background-muted uip-border-rounder uip-gap-l uip-flex-between uip-link-default" @click="ui.zoom = 1">
                        
                        <div class="uip-no-wrap">{{ui.strings.zoom100}}</div>
                        <div class="uip-flex uip-flex-center uip-text-muted uip-gap-xxxs">
                        
                          <span class="uip-command-icon"></span>
                          <span class="uip-icon" style="line-height:0">exposure_zero</span>
                          
                        </div>
                        
                      </div> 
                      
                      
                      
                    </div>
                  </template>
                
                </drop-down>
                
                <div class=" uip-margin-left-xs uip-margin-right-xs uip-h-20"></div>
                
                
                
                
               
                
                <div :title="ui.strings.recenter" class="hover:uip-background-muted uip-padding-xxxs uip-border-round uip-flex uip-flex-center  uip-icon uip-link-default uip-text-xl uip-ratio-1-1 uip-line-height-1 uip-icon-medium" @click="scrollToPreview()">
                center_focus_strong
                </div>
                
                <div :title="ui.strings.showGridLines" class="hover:uip-background-muted uip-padding-xxxs uip-border-round uip-flex uip-flex-center  uip-icon uip-link-default uip-text-xl uip-ratio-1-1 uip-line-height-1 uip-icon-medium" @click="toggleDisplay()" :class="{'uip-background-grey uip-text-emphasis' : uiTemplate.display != 'preview'}">
                grid_3x3
                </div>
                
                <!-- Dark mode -->
                <div :title="ui.strings.darkMode" class="hover:uip-background-muted uip-padding-xxxs uip-border-round uip-flex uip-flex-center  uip-icon uip-link-default uip-text-xl uip-ratio-1-1 uip-line-height-1 uip-icon-medium" @click="toggleDarkMode()">
                {{returnThemeIcon()}}
                </div>
                
                
                  
                
                
                
              
            </div>
          
        </div>
        
        
        
        <!-- End right click -->
      </div>
      
      
      <div v-if="returnDomObjects.length > 0 && resizer.resizing" class="uip-position-fixed uip-top-0 uip-left-0 uip-right-0 uip-bottom-0"></div>
            
      <template v-for="item in returnDomObjects">
        <div v-show="!isScrolling && !uiTemplate.isPreview" class="uip-position-fixed uip-w-200 uip-ratio-1-1 uip-fade-in uip-block-wrap uip-block-selected-wrap" :style="setSelectedWrapPos(item)">
        
            <div class="uip-border-blue uip-background-default uip-w-8 uip-ratio-1-1 uip-border-circle uip-position-absolute uip-left-0 uip-top-0" 
            style="transform: translateY(-50%) translateX(-50%);cursor: nwse-resize;pointer-events: all;"
            @mousedown.prevent="initResizeBlock($event, item, 'left-top')"></div>
            
            <div class="uip-border-blue uip-background-default uip-w-8 uip-ratio-1-1 uip-border-circle uip-position-absolute uip-right-0 uip-top-0" 
            style="transform: translateY(-50%) translateX(50%);cursor: nesw-resize;pointer-events: all;"
            @mousedown.prevent="initResizeBlock($event, item, 'top')"></div>
            
            <div class="uip-border-blue uip-background-default uip-w-8 uip-ratio-1-1 uip-border-circle uip-position-absolute uip-left-0 uip-bottom-0" 
            style="transform: translateY(50%) translateX(-50%);cursor: nesw-resize;pointer-events: all;"
            @mousedown.prevent="initResizeBlock($event, item, 'left')"></div>
            
            <div class="uip-border-blue uip-background-default uip-w-8 uip-ratio-1-1 uip-border-circle uip-position-absolute uip-right-0 uip-bottom-0" 
            style="transform: translateY(50%) translateX(50%);cursor: nwse-resize;pointer-events: all;"
            @mousedown.prevent="initResizeBlock($event, item)"></div>
            
            <div v-show="resizer.resizing" ref="resizingdigits" class="uip-border-rounder uip-position-fixed uip-padding-xxs uip-text-xs uip-text-inverse" 
            style="background:rgba(1,1,1,0.6)">{{resizer.previewBlockWidth}} x {{resizer.previewBlockHeight}}</div>
            
        </div>
      </template>
      
      
      `,
  };
  return compData;
}
