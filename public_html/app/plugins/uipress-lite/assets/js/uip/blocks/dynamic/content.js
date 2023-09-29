const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      display: String,
      name: String,
      block: Object,
    },
    data: function () {
      return {
        frame: false,
        loading: true,
        fullScreen: false,
        breadCrumbs: [],
        startPage: this.returnAdminPage(),
        cornertickle: false,
        currentURL: false,
        rendered: false,
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate', 'router'],
    watch: {
      'uipData.themeStyles': {
        handler(newValue, oldValue) {
          this.injectStyles();
        },
        deep: true,
      },
      'uiTemplate.globalSettings.options.advanced.css': {
        handler(newValue, oldValue) {
          this.injectStyles();
        },
        deep: true,
      },
    },
    mounted: function () {
      let self = this;
      const frameContainer = document.getElementById(self.block.uid);

      if (self.uiTemplate.display == 'prod') {
        window.addEventListener(
          'message',
          function (event) {
            if (event.data && event.data.eventName === 'uip_request_fullscreen') {
              if (!self.isFullScreen()) {
                frameContainer.classList.add('uip-fullscreen-mode');
                frameContainer.classList.add('uip-scale-in-bottom-right');
              }
            }
            if (event.data && event.data.eventName === 'uip_exit_fullscreen') {
              if (self.isFullScreen()) {
                frameContainer.classList.remove('uip-fullscreen-mode');
                frameContainer.classList.remove('uip-scale-in-bottom-right');
              }
            }
          },
          false
        );
      }

      this.frame = this.$refs.contentframe;
      //Check if we are in production and update url to current
      if (self.uiTemplate.display == 'prod') {
        self.startPage = window.location.href;

        if (self.returnHomePage) {
          let adminLink = this.uipData.dynamicOptions.viewadmin.value;
          if (self.startPage == adminLink || self.startPage == adminLink + '#/') {
            let absoluteCheck = new RegExp('^(?:[a-z+]+:)?//', 'i');
            if (!absoluteCheck.test(self.returnHomePage)) {
              self.startPage = adminLink + self.returnHomePage;
            } else {
              self.startPage = self.returnHomePage;
            }
          }
        }

        let url = new URL(self.startPage);
        url = self.formatUserUrlOptions(url);

        self.frame.contentWindow.location.replace(url);
        self.uipress.updateActiveLink(self.startPage);

        //Watch for history state changes
        window.onpopstate = function () {
          if (window.location.href != self.startPage) {
            self.startPage = window.location.href;
            //self.uipress.updateActiveLink(self.startPage.replace('#/', ''));
          }
        };
        window.onhashchange = function () {
          if (window.location.href != self.startPage) {
            self.startPage = window.location.href;
            //self.uipress.updateActiveLink(self.startPage.replace('#/', ''));
          }
        };
      }

      document.addEventListener(
        'uip_breadcrumbs_change',
        (e) => {
          self.breadCrumbs = e.detail.crumbs;
        },
        { once: false }
      );

      //Listen for other compoenents like the menu etc wanting to change frame src
      document.addEventListener(
        'uip_update_frame_url',
        (e) => {
          let url = e.detail.url;

          self.loading = true;

          //Set a timeout to stop endless loading bar if plugin doesn't trigger an iframe load
          setTimeout(function () {
            self.loading = false;
          }, 2000);

          url = self.formatUserUrlOptions(url);
          if (self.frame.contentWindow) {
            self.frame.contentWindow.location.assign(url);
            //self.frame.contentWindow.location.reload();
          }
        },
        { once: false }
      );

      //Block console errors / messages from iframe
      //this.frame.contentWindow.console.log = function () {};

      this.frame.onload = function () {
        self.rendered = true;

        self.mountUpdateListWatchers();
        self.ammendPageLinks();
        self.checkForUserFullSreen(self.frame.contentWindow.location.href);

        //Try to get contents to see if frame was loaded or blocked
        try {
          self.frame.contentWindow.title;
        } catch (error) {
          if (self.currentURL) {
            window.location.assign(self.currentURL);
          }
          return;
        }
        self.frame.contentWindow;
        self.loading = false;

        self.injectStyles();

        let title = self.frame.contentDocument.title;
        if (title && title != '') {
          document.title = title;
        }

        self.addRightClickWatchers();

        if (self.uiTemplate.display == 'prod') {
          self.updateBrowserAddress(self.frame.contentWindow.location.href);
        }

        self.uipPageChangeLoaded = new CustomEvent('uip_page_change_loaded');
        document.dispatchEvent(self.uipPageChangeLoaded);
        self.updatePageUrls();
      };

      this.iframeURLChange(self.frame, function (newURL) {
        self.uipPageChangeStarted = new CustomEvent('uip_page_change_started');
        document.dispatchEvent(self.uipPageChangeStarted);
        //Start load
        self.loading = true;
        self.currentURL = newURL;
        //Set a timeout to stop endless loading bar if plugin doesn't trigger an iframe load
        setTimeout(function () {
          self.loading = false;
        }, 2000);

        //Get new URL
        let url = new URL(newURL);

        self.uipress.forceReload(url);

        //Some plugins remove url params from the url so we need to change for attributes on the iframe html tag
        let fallBackState = false;
        if (self.frame.contentWindow.document.documentElement) {
          fallBackState = self.frame.contentWindow.document.documentElement.getAttribute('uip-framed-page');
        }

        if (typeof UIPfrontEndReload != 'undefined') {
          if (UIPfrontEndReload) {
            if (self.uiTemplate.display != 'builder') {
              if (!url.href.includes(self.returnAdminPage())) {
                window.location.assign(url);
                return;
              }
            }
          }
        }

        //Check if we have already pushed the param so we don't double load and update active URL
        if (url.searchParams.get('uip-framed-page') || fallBackState) {
          let activeItem = self.frame.contentWindow.document.querySelectorAll("#adminmenu a[aria-current='page']");
          let path = newURL;
          if (activeItem[0]) {
            path = activeItem[0].getAttribute('href');
          }

          if (path.includes('about:blank')) {
            //self.uipress.updateActiveLink(newURL);
            self.injectStyles();
            self.loading = false;

            return;
          }

          //path = path.replace('about:blank', '');
          self.uipress.updateActiveLink(path);
          self.injectStyles();
          self.loading = false;

          return;
        }
        self.frame.src = 'about:blank';
        url = self.formatUserUrlOptions(url);

        self.frame.contentWindow.location.replace(url);
      });
    },
    computed: {
      returnStartPage() {
        if (this.uiTemplate.display != 'prod') {
          return this.startPage;
        }
        return '';
      },
      returnLoadingStyle() {
        if (!this.rendered) {
          return 'opacity:0';
        }
      },
      returnHomePage() {
        let src = this.uipress.get_block_option(this.block, 'block', 'loginRedirect', true);
        if (this.uipress.isObject(src)) {
          if ('value' in src) {
            return src.value;
          } else {
            return false;
          }
        }
        return src;
      },
      returnTheme() {
        let temp = this.uipress.get_block_option(this.block, 'block', 'disableTheme');
        if (this.uipress.isObject(temp)) {
          if ('value' in temp) {
            return temp.value;
          }
          return true;
        }
        return temp;
      },
      returnNotices() {
        let self = this;
        let hideNotices = false;
        let temp = this.uipress.get_block_option(this.block, 'block', 'hidePluginNotices');
        if (this.uipress.isObject(temp)) {
          if ('value' in temp) {
            hideNotices = temp.value;
          }
          hideNotices = false;
        }

        if (JSON.stringify(self.uiTemplate.content).includes('site-notifications')) {
          hideNotices = true;
        }

        return hideNotices;
      },
      hideLoader() {
        let temp = this.uipress.get_block_option(this.block, 'block', 'hideLoader');
        if (this.uipress.isObject(temp)) {
          if ('value' in temp) {
            return temp.value;
          }
          return true;
        }
        return temp;
      },
      returnScreen() {
        let temp = this.uipress.get_block_option(this.block, 'block', 'hideScreenOptions');
        if (this.uipress.isObject(temp)) {
          if ('value' in temp) {
            return temp.value;
          }
          return true;
        }
        return temp;
      },
      returnHelp() {
        let temp = this.uipress.get_block_option(this.block, 'block', 'hideHelpTab');
        if (this.uipress.isObject(temp)) {
          if ('value' in temp) {
            return temp.value;
          }
          return true;
        }
        return temp;
      },
      disableFullScreen() {
        let temp = this.uipress.get_block_option(this.block, 'block', 'disableFullScreen');
        if (this.uipress.isObject(temp)) {
          if ('value' in temp) {
            return temp.value;
          }
          return true;
        }
        return temp;
      },
      returnClasses() {
        let classes = '';
        if (this.fullScreen) {
          classes += 'uip-fullscreen-mode uip-scale-in-bottom-right uip-z-index-9';
        }
        return classes;
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
    },
    methods: {
      checkForUserFullSreen(url) {
        if (typeof UIPFullscreenUserPages != 'undefined') {
          if (UIPFullscreenUserPages) {
            if (this.enviroment != 'builder') {
              if (Array.isArray(UIPFullscreenUserPages)) {
                for (let part of UIPFullscreenUserPages) {
                  if (url == part || url.includes(part)) {
                    this.setFullScreen();
                  }
                }
              }
            }
          }
        }
      },
      addRightClickWatchers() {
        let self = this;
        if (self.uiTemplate.display != 'prod') {
          self.frame.contentDocument.body.addEventListener('click', function () {
            self.$refs.frameContainer.click();
          });
          self.frame.contentDocument.body.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            //event = document.createEvent('Event');
            //event.initEvent('contextmenu', true, true);
            let clickPos = {
              x: e.pageX,
              y: e.pageY,
            };
            let event = new CustomEvent('right_click_frame', { 'detail': { pos: clickPos, uid: self.block.uid } });
            document.dispatchEvent(event);
          });
        }
      },
      ammendPageLinks() {
        return;
        let self = this;

        if (this.uipData.options.multisite) return;
        const links = self.frame.contentWindow.document.querySelectorAll('a');
        if (links) {
          links.forEach((link) => {
            let href = link.getAttribute('href');

            if (href) {
              if (href.charAt(0) != '#') {
                //Check for absolute
                let absoluteCheck = new RegExp('^(?:[a-z+]+:)?//', 'i');
                if (!absoluteCheck.test(href)) {
                  let admin = this.returnAdminPage();
                  if (!admin.endsWith('/')) {
                    admin += '/';
                  }
                  if (href.startsWith(this.returnAdminPath())) {
                    href = href.replace(this.returnAdminPath(), '');
                  }
                  if (href.startsWith('/')) {
                    href = href.replace('/', '');
                  }
                  href = admin + href;
                }
                let url = new URL(href);
                url = self.formatUserUrlOptions(url);
                link.setAttribute('href', url.href);
              }
            }
          });
        }
      },
      mountUpdateListWatchers() {
        let self = this;
        const form = self.frame.contentWindow.document.querySelector('form[name="upgrade-plugins"]');
        if (form) {
          form.addEventListener('submit', function (evt) {
            let url = new URL(form.action);

            if (!url.searchParams.get('uip-framed-page')) {
              evt.preventDefault();
              self.formatUserUrlOptions(url);
              form.action = url.href;
              form.submit();
            }
          });
        }

        const themeform = self.frame.contentWindow.document.querySelector('form[name="upgrade-themes"]');
        if (themeform) {
          themeform.addEventListener('submit', function (evt) {
            let url = new URL(themeform.action);

            if (!url.searchParams.get('uip-framed-page')) {
              evt.preventDefault();
              self.formatUserUrlOptions(url);
              themeform.action = url.href;
              themeform.submit();
            }
          });
        }

        const coreform = self.frame.contentWindow.document.querySelector('form[name="upgrade"]');
        if (coreform) {
          coreform.addEventListener('submit', function (evt) {
            let url = new URL(coreform.action);

            if (!url.searchParams.get('uip-framed-page')) {
              evt.preventDefault();
              self.formatUserUrlOptions(url);
              coreform.action = url.href;
              coreform.submit();
            }
          });
        }

        return;
      },
      updateBrowserAddress(url) {
        let self = this;
        let processed = self.uipress.stripUIPparams(url);
        history.pushState({}, null, processed);
      },
      returnAdminPage() {
        let url = this.uipress.checkNestedValue(this.uipData, ['dynamicOptions', 'viewadmin', 'value']);
        return url;
      },
      returnAdminPath() {
        return this.uipData.options.adminPath;
      },
      updatePageUrls() {
        let self = this;
        let allFormLinks = [];

        let adminURL = this.uipData.options.adminURL;

        //Update form actions
        allFormLinks = self.frame.contentWindow.document.querySelectorAll('form');
        for (let form of allFormLinks) {
          let formAction = form.action;

          if (typeof formAction === 'undefined' || formAction == '' || typeof formAction !== 'string') {
            continue;
          }

          if (formAction.includes(adminURL)) {
            form.action = self.formatRequiredParams(formAction);
          }
        }

        //Update form actions
        let allFormHrefs = self.frame.contentWindow.document.querySelectorAll('body.update-php a');
        for (let link of allFormHrefs) {
          let href = link.href;

          if (typeof href === 'undefined' || href == '' || typeof href !== 'string') {
            continue;
          }

          if (href.includes(adminURL)) {
            link.href = self.formatRequiredParams(href);
          } else {
            let templink = adminURL + href;
            let newLink = self.formatRequiredParams(templink);
            newLink = newLink.replace(adminURL, '');
            link.href = newLink;
          }
        }
      },
      formatUserUrlOptions(url) {
        let self = this;

        url.searchParams.set('uip-framed-page', 1);

        //Check if screen options should be hidden
        if (self.returnScreen) {
          url.searchParams.set('uip-hide-screen-options', 1);
        }
        //Check if help tab should be hidden
        if (self.returnHelp) {
          url.searchParams.set('uip-hide-help-tab', 1);
        }
        //Check if theme should be loaded
        if (self.returnTheme) {
          url.searchParams.set('uip-default-theme', 1);
        }
        //Check if notices have been hidden
        if (self.returnNotices) {
          url.searchParams.set('uip-hide-notices', 1);
        }

        url.searchParams.set('uipid', self.uiTemplate.id);

        return url;
      },
      formatRequiredParams(unformatted) {
        let self = this;
        let url = new URL(unformatted);

        url.searchParams.set('uip-framed-page', 1);

        //Check if screen options should be hidden
        if (self.returnScreen) {
          url.searchParams.set('uip-hide-screen-options', 1);
        }
        //Check if help tab should be hidden
        if (self.returnHelp) {
          url.searchParams.set('uip-hide-help-tab', 1);
        }
        //Check if theme should be loaded
        if (self.returnTheme) {
          url.searchParams.set('uip-default-theme', 1);
        }
        //Check if notices have been hidden
        if (self.returnNotices) {
          url.searchParams.set('uip-hide-notices', 1);
        }

        //if (self.uiTemplate.display == 'prod') {
        url.searchParams.set('uipid', self.uiTemplate.id);

        return url.href;
      },
      isFullScreen() {
        let container = document.getElementById(this.block.uid);
        if (!container) {
          return;
        }
        if (container.classList.contains('uip-fullscreen-mode')) {
          return true;
        } else {
          return false;
        }
      },

      toggleFullScreen() {
        let container = document.getElementById(this.block.uid);
        if (this.isFullScreen()) {
          this.removeFullScreen();
        } else {
          this.setFullScreen();
        }
      },
      setFullScreen() {
        let container = document.getElementById(this.block.uid);
        container.classList.add('uip-fullscreen-mode');
        container.classList.add('uip-scale-in-bottom-right');
      },
      removeFullScreen() {
        let container = document.getElementById(this.block.uid);
        container.classList.remove('uip-fullscreen-mode');
        container.classList.remove('uip-scale-in-bottom-right');
      },
      injectStyles() {
        let self = this;

        //Only inject custom css in prod mode
        if (self.uiTemplate.display == 'prod') {
          //this.injectCSS();
          return;
        }

        let styles = this.uipData.themeStyles;
        this.frame = this.$refs.contentframe;
        let styleArea = this.frame.contentWindow.document.getElementById('uip-theme-styles');

        //Style area doesn't exist so abort
        if (!styleArea) {
          return;
        }
        let style = 'html[data-theme="light"]{';

        for (let key in styles) {
          let item = styles[key];
          if (item.value) {
            style += item.name + ':' + item.value + ';';
          }
        }

        style += '}';

        let darkStyles = 'html[data-theme="dark"]{';

        for (let key in styles) {
          let item = styles[key];
          if (item.darkValue) {
            darkStyles += item.name + ':' + item.darkValue + ';';
          }
        }
        darkStyles += '}';

        let globalCSS = self.returnTemplateCSS;
        styleArea.innerHTML = style + darkStyles + globalCSS;
      },
      injectCSS() {
        let self = this;
        this.frame = this.$refs.contentframe;
        let styleArea = this.frame.contentWindow.document.getElementById('uip-theme-styles');
        //Style area doesn't exist so abort
        if (!styleArea) {
          return;
        }
        let globalCSS = self.returnTemplateCSS;
        styleArea.innerText = globalCSS;
      },
      iframeURLChange(iframe, callback) {
        let lastDispatched = null;

        let dispatchChange = function () {
          if (!iframe.contentWindow) {
            return;
          }
          var newHref = iframe.contentWindow.location.href;

          if (newHref !== lastDispatched) {
            callback(newHref);
            lastDispatched = newHref;
          }
        };

        let unloadHandler = function () {
          // Timeout needed because the URL changes immediately after
          // the `unload` event is dispatched.
          setTimeout(dispatchChange, 0);
        };

        function attachUnload() {
          // Remove the unloadHandler in case it was already attached.
          // Otherwise, there will be two handlers, which is unnecessary.
          iframe.contentWindow.removeEventListener('unload', unloadHandler);
          iframe.contentWindow.addEventListener('unload', unloadHandler);
        }

        iframe.addEventListener('load', function () {
          attachUnload();

          // Just in case the change wasn't dispatched during the unload event...
          dispatchChange();
        });

        attachUnload();
      },
    },
    template: `
    <div ref="frameContainer" class="uip-flex uip-flex-column uip-overflow-hidden uip-content-frame uip-overflow-hidden uip-position-relative" :class="returnClasses">
      <div class="uip-position-relative" v-if="!hideLoader">
        <div ref="loader" :class="block.uid" class="uip-ajax-loader" v-if="loading">
        <div :class="block.uid" class="uip-loader-bar"></div>
        </div>
      </div>
      
      <iframe :style="returnLoadingStyle" :src="returnStartPage" ref="contentframe" 
      class="uip-page-content-frame uip-background-default uip-scrollbar uip-w-100p uip-flex-grow"></iframe>
       
      <div @mouseover="cornertickle = true" @mouseleave="cornertickle = false" class="uip-position-absolute uip-text-muted uip-top-0 uip-right-0 uip-cursor-pointer uip-flex uip-flex-column uip-flex-middle uip-padding-xs" v-if="!disableFullScreen">
      
        <div @click="toggleFullScreen()" v-if="cornertickle" class="uip-border-box uip-background-muted uip-text-muted uip-padding-xxxs uip-link-muted hover:uip-background-grey uip-flex uip-flex-column uip-flex-middle uip-fade-in uip-shadow-small uip-border-round" style="pointer-events: all">
        <span v-if="isFullScreen()" class="uip-icon uip-text-l uip-slide-in-right uip-line-height-1">fullscreen_exit</span>
        <span v-if="!isFullScreen()" class="uip-icon uip-text-l uip-line-height-1">fullscreen</span>
        </div>
        
      </div>
      
      <template v-else>
        
        <component v-if="disableFullScreen" is="style">
        .uip-fullscreen-toggle {
         display:none; 
        }
        .uip-fullscreen-mode .uip-fullscreen-toggle {
         display:block; 
        }
        </component>
        
        <div @mouseover="cornertickle = true" @mouseleave="cornertickle = false" class="uip-position-absolute uip-text-muted uip-top-0 uip-right-0 uip-cursor-pointer uip-flex uip-flex-column uip-flex-middle uip-padding-xs" v-if="!disableFullScreen">
        
        <div @click="toggleFullScreen()" v-if="cornertickle" class="uip-border-box uip-background-muted uip-text-muted uip-padding-xxxs uip-link-muted hover:uip-background-grey uip-flex uip-flex-column uip-flex-middle uip-fade-in uip-shadow-small uip-border-round" style="pointer-events: all">
          <span v-if="isFullScreen()" class="uip-icon uip-text-l uip-slide-in-right uip-line-height-1">fullscreen_exit</span>
          <span v-if="!isFullScreen()" class="uip-icon uip-text-l uip-line-height-1">fullscreen</span>
        </div>
        
        </div>
      
      </template>
    </div>
    `,
  };
}
