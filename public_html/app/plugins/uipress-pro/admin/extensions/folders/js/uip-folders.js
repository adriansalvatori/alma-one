///IMPORT TRANSLATIONS

//Dynamic import Import scripts
(async () => {
  const { __, _x, _n, _nx } = wp.i18n;
  const pluginVersion = import.meta.url.split('?ver=')[1];
  //Import Vue modules
  const vueFuncs = await import(`../../../../assets/js/libs/vue-esm.js?ver=${pluginVersion}`);
  const createApp = vueFuncs.createApp;

  //Import required classes and modules
  const uipress = new window.uipClass();

  //Import main ui view
  const contentFolder = await import(`./modules/content-folder.min.js?ver=${pluginVersion}`);
  const newFolder = await import(`./modules/new-folder.min.js?ver=${pluginVersion}`);
  const folderApp = await import(`./modules/folder-app.min.js?ver=${pluginVersion}`);
  const loadingChart = await import(`../../../../../uipress-lite/assets/js/uip/modules/uip-loading-chart.min.js?ver=${pluginVersion}`);
  const dropdown = await import(`../../../../../uipress-lite/assets/js/uip/modules/uip-dropdown.min.js?ver=${pluginVersion}`);
  const colorPicker = await import(`../../../../../uipress-lite/assets/js/uip/options/uip-color-picker.min.js?ver=${pluginVersion}`);

  //Get post type from script tag
  let scriptHolder = document.getElementById('uip-folders-app');
  let postType = scriptHolder.getAttribute('postType');
  let limitToAuthorVal = scriptHolder.getAttribute('limitToAuthor');
  let limitToType = scriptHolder.getAttribute('limitToType');

  if (!limitToAuthorVal || limitToAuthorVal == '') {
    limitToAuthorVal = 'uipfalse';
  }
  if (!limitToType || limitToType == '') {
    limitToType = 'uipfalse';
  }

  /**
   * Builds main args for uip menu creator app
   * @since 3.0.0
   */
  const uipFolderArgs = {
    data() {
      return {
        loading: true,
        activeFolderID: 'all',
        dragging: false,
      };
    },
    provide() {
      return {
        uipress: uipress,
        postTypes: [postType],
        limitToAuthor: limitToAuthorVal,
        limitToType: limitToType,
        setActiveFolderID: this.updateFolderID,
        getActiveFolderID: this.getFolderID,
        isDragging: this.isDragging,
        setDrag: this.setDragging,
        filterTableItems: this.filterTableItems,
      };
    },
    created: function () {
      //window.addEventListener('resize', this.getScreenWidth);
    },
    mounted: function () {},
    computed: {
      returnGlobalData() {
        return this.uipGlobalData;
      },
    },
    methods: {
      isDragging() {
        return this.dragging;
      },
      setDragging(d) {
        this.dragging = d;
      },
      updateFolderID(d) {
        this.activeFolderID = d;
        if (wp.media) {
          if (wp.media.frames.browse) {
            let collection = wp.media.frames.browse.content.get().collection;
            if (typeof collection !== 'undefined') {
              collection.props.set({ uip_folder_id: 'remove' });
              collection.props.set({ uip_folder_id: d });
            }
          } else {
            let collection = wp.media.frame.content.get().collection;
            if (typeof collection !== 'undefined') {
              collection.props.set({ uip_folder_id: 'remove' });
              collection.props.set({ uip_folder_id: d });
            }
          }
        } else {
          this.filterTableItems(d);
        }
      },
      getFolderID() {
        return this.activeFolderID;
      },
      filterTableItems(folderID) {
        let self = this;
        let searchParams = new URLSearchParams(window.location.search);
        let current = window.location.origin;
        searchParams.set('uip_folder', folderID);
        let newRelativePathQuery = current + window.location.pathname + '?' + searchParams.toString();

        jQuery.ajax(newRelativePathQuery, {
          success: function (data) {
            let parser = new DOMParser();
            let newData = data.trim();
            let doc = parser.parseFromString(newData, 'text/html');
            let newContent = doc.querySelector('#wpbody-content .wrap');
            document.querySelector('#wpbody-content .wrap').replaceWith(newContent);
            //jQuery('#wpbody-content .wrap').html(jQuery(data).find('#wpbody-content .wrap').html());
            let eventNew = new CustomEvent('uip_update_listeners');
            document.dispatchEvent(eventNew);
          },
          error: function (jqXHR, textStatus, errorThrown) {
            self.uipress.notify(errorThrown, '', 'error', true);
          },
        });
      },
    },
    template: '<folder-app></folder-app>',
  };

  /**
   * Define app
   * @since 3.0.0
   */
  const uipFolderApp = createApp(uipFolderArgs);
  //Allow reactive data from inject
  uipFolderApp.config.unwrapInjectedRef = true;
  uipFolderApp.config.devtools = true;

  //Import
  uipFolderApp.component('content-folder', contentFolder.moduleData());
  uipFolderApp.component('new-folder', newFolder.moduleData());
  uipFolderApp.component('folder-app', folderApp.moduleData());
  uipFolderApp.component('loading-chart', loadingChart.moduleData());
  uipFolderApp.component('drop-down', dropdown.moduleData());
  uipFolderApp.component('color-picker', colorPicker.moduleData());
  /**
   * Handles app errors
   * @since 3.0.0
   */
  uipFolderApp.config.errorHandler = function (err, vm, info) {
    uipress.notify(err, info, 'error');
    console.log(err);
  };

  /**
   * Register vue components
   * @since 3.0.0
   */
  //uipApp.component('uip-main-app', uipMainView.moduleData());
  uipFolderApp.mount('#uip-folder-app');
})();
