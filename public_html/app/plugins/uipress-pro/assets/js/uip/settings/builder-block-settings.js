///IMPORT TRANSLATIONS
const { __, _x, _n, _nx } = wp.i18n;

export function getSettings(globalDynamic, context) {
  return {
    keyboardShortcut: {
      value: {},
      component: 'keyboard-shortcut',
      renderStyle(value) {
        return value;
      },
    },
    advancedMenuEditing: {
      value: [],
      component: 'advanced-menu-editor',
      renderStyle(value) {
        return value;
      },
    },
    userMetaSelect: {
      value: '',
      component: 'user-meta-select',
      renderStyle(value) {
        return value;
      },
    },
    searchPostTypes: {
      value: [],
      component: 'search-post-type-select',
      renderStyle(value) {
        return value;
      },
    },
    listItemCreator: {
      value: [],
      component: 'list-item-creator',
      renderStyle(value) {
        return value;
      },
    },
    youtubeEmbed: {
      value: '',
      component: 'code-editor',
      type: 'blockOption',
      args: {
        language: 'html',
      },
      renderStyle(value) {
        return value;
      },
    },
    startPage: {
      value: {},
      component: 'link-select',
      renderStyle(value) {
        return value;
      },
    },
    onClickCode: {
      value: '',
      component: 'code-editor',
      args: {
        language: 'javascript',
      },
    },
    hidePluginNotices: {
      value: false,
      component: 'switch-select',
      renderStyle(value) {
        return value;
      },
    },
    conditions: {
      value: '',
      component: 'uip-conditions',
      type: Array,
      renderStyle(value) {
        return value;
      },
    },
  };
}
