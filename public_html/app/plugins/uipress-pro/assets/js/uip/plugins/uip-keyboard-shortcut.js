const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: Object,
      args: Object,
    },
    inject: ['uipress'],
    data: function () {
      return {
        option: this.formatValue(this.value),
        strings: {
          shortCutEnabled: __('Enable keybaord shortcut?', 'uipress-lite'),
          pressShortcut: __('Type your custom shortcut below', 'uipress-lite'),
          displayShortcut: __('Display shortcut in trigger', 'uipress-lite'),
        },
        isCtrlPressed: false,
        isAltPressed: false,
        isShiftPressed: false,
        shortcutKeys: [
          'Enter', // Enter
          ' ', // Space
          'ArrowLeft', // Left Arrow
          'ArrowUp', // Up Arrow
          'ArrowRight', // Right Arrow
          'ArrowDown', // Down Arrow
        ],
        shortcutKeysIcons: [
          { key: 'Enter', icon: 'keyboard_return' }, // Enter
          { key: ' ', icon: 'space_bar' }, // Space
          { key: 'ArrowLeft', icon: 'keyboard_arrow_left' }, // Left Arrow
          { key: 'ArrowUp', icon: 'keyboard_arrow_up' }, // Up Arrow
          { key: 'ArrowRight', icon: 'keyboard_arrow_right' }, // Right Arrow
          { key: 'ArrowDown', icon: 'keyboard_arrow_down' }, // Down Arrow
        ],
        selected: [],
      };
    },
    watch: {
      option: {
        handler(newValue, oldValue) {
          this.returnData(this.option);
        },
        deep: true,
      },
    },
    mounted: function () {},
    methods: {
      formatValue(val) {
        if (!val) {
          return { enabled: false };
        }

        if (!this.uipress.isObject(val)) {
          return { enabled: false };
        }

        return val;
      },
      logKeyDown(event) {
        var keyCode = event.key;
        this.pushSelected(keyCode);
      },
      logKeyUp(event) {
        event.preventDefault();

        this.selected = [];
        var keyCode = event.keyCode ? event.keyCode : event.which;
        if (keyCode >= 16 && keyCode <= 18) {
          this.updateKeyFlags(keyCode, false);
        }
        if (event.ctrlKey || event.metaKey) {
          this.updateKeyFlags(17, false);
        }
      },
      pushSelected(key) {
        if (!this.option.selected) {
          this.option.selected = [];
        }
        if (!this.option.selected.includes(key)) {
          this.option.selected.push(key);
        }
      },
      updateKeyFlags(keyCode, flag) {
        switch (keyCode) {
          case 16:
            this.isShiftPressed = flag;
            if (flag) {
              this.pushSelected('shift');
            }
            break;
          case 17:
            if (flag) {
              this.pushSelected('command');
            }
            this.isCtrlPressed = flag;
            break;
          case 18:
            if (flag) {
              this.pushSelected('alt');
            }
            this.isAltPressed = flag;
            break;
        }
      },
      returnKey(key) {
        if (key == 'Meta') {
          return '<span class="uip-command-icon uip-text-muted"></span>';
        }
        if (key == 'Alt') {
          return '<span class="uip-alt-icon uip-text-muted"></span>';
        }
        if (key == 'Shift') {
          return '<span class="uip-shift-icon uip-text-muted"></span>';
        }
        if (key == 'Control') {
          return '<span class="uip-icon uip-text-muted">keyboard_control_key</span>';
        }
        if (key == 'Backspace') {
          return '<span class="uip-icon uip-text-muted">backspace</span>';
        }

        if (this.shortcutKeys.includes(key)) {
          let keyicon = this.shortcutKeysIcons.find((x) => x.key == key);
          return `<span class="uip-icon uip-text-muted">${keyicon.icon}</span>`;
        } else {
          return `<span class="uip-text-muted uip-text-uppercase" style="line-height: 16px;font-size: 11px;">${key}</span>`;
        }
      },
    },
    template: `
    <div class="uip-flex uip-flex-column uip-row-gap-xs">\
      <div >
        <div class="uip-margin-bottom-xxs uip-text-s uip-text-muted">{{strings.shortCutEnabled}}</div>\
        <switch-select :value="option.enabled" :returnData="function(e){option.enabled = e}"></switch-select>
      </div>
      <!--Shortcut area -->
      <div v-if="option.enabled" class="uip-scale-in-top uip-flex uip-flex-column uip-row-gap-xs uip-flex-start">
        <div class="uuip-text-s uip-text-muted">{{strings.pressShortcut}}</div>\
        <input type="text" ref="shortcutInput" class="uip-input" @keydown="logKeyDown" @keyup="logKeyUp">
        <div class="uip-flex uip-flex-row uip-gap-xs uip-flex-center">
          <div v-if="option.selected" class="uip-flex uip-flex-row uip-padding-left-xxxs uip-padding-right-xxxs uip-border uip-border-round uip-text-s uip-flex-row uip-inline-flex uip-flex-center" v-html="uipress.renderKeyShortCut(option.selected)">
          </div>
          <div class="uip-inline-flex uip-icon uip-link-danger" @click="option.selected = []">delete</div>
        </div>
      </div>
      <!--End shortcut area-->
      <div v-if="option.enabled">
        <div class="uip-margin-bottom-xxs uip-text-s uip-text-muted">{{strings.displayShortcut}}</div>\
        <switch-select :value="option.display" :returnData="function(e){option.display = e}"></switch-select>
      </div>
    </div>`,
  };
}
