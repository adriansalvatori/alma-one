const { __, _x, _n, _nx } = wp.i18n;
const uipress = new window.uipClass();
export function fetchBlocks() {
  return [
    /**
     * Iframe block
     * @since 3.0.0
     */
    {
      name: __('iFrame', 'uipress-pro'),
      moduleName: 'uip-iframe',
      description: __('Outputs a iframe block', 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'elements',
      path: uipProPath + 'assets/js/uip/blocks/elements/iframe.min.js',
      icon: 'public',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [{ option: 'linkSelect', label: __('Iframe URL', 'uipress-lite') }],
        },
        //Container options group
        {
          name: 'container',
          label: __('Block container', 'uipress-pro'),
          icon: 'crop_free',
          styleType: 'style',
          options: uipress.returnBlockConatinerOptions(),
        },
        //Container options group
        {
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Advanced options group
        {
          name: 'advanced',
          label: __('Advanced', 'uipress-pro'),
          icon: 'code',
          options: uipress.returnAdvancedOptions(),
        },
      ],
    },

    /**
     * Iframe block
     * @since 3.0.0
     */
    {
      name: __('HTML', 'uipress-pro'),
      moduleName: 'uip-custom-html',
      description: __('This block allows you to output your own HTML into the template', 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'elements',
      path: uipProPath + 'assets/js/uip/blocks/elements/custom-html.min.js',
      icon: 'code',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'customCode',
              uniqueKey: 'customHTML',
              label: __('Custom HTML', 'uipress-pro'),
              value: '<strong>Hello world</strong>',
              args: {
                language: 'html',
              },
            },
          ],
        },
        //Container options group
        {
          name: 'container',
          label: __('Block container', 'uipress-pro'),
          icon: 'crop_free',
          styleType: 'style',
          options: uipress.returnBlockConatinerOptions(),
        },
        //Container options group
        {
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Advanced options group
        {
          name: 'advanced',
          label: __('Advanced', 'uipress-pro'),
          icon: 'code',
          options: uipress.returnAdvancedOptions(),
        },
      ],
    },

    /**
     * Icon list
     * @since 3.0.0
     */
    {
      name: __('Icon list', 'uipress-pro'),
      moduleName: 'uip-icon-list-block',
      description: __('Creates a list with icons', 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'elements',
      path: uipProPath + 'assets/js/uip/blocks/elements/icon-list.min.js',
      icon: 'list',
      settings: {},
      optionsEnabled: [
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'listItemCreator',
              uniqueKey: 'blockListItems',
              label: __('List items', 'uipress-pro'),
              value: {
                options: [
                  { name: __('List item one', 'uipress-lite'), icon: 'favorite' },
                  { name: __('List item two', 'uipress-lite'), icon: 'favorite' },
                ],
              },
            },
            {
              option: 'choiceSelect',
              uniqueKey: 'listDirection',
              label: __('List direction', 'uipress-lite'),
              args: {
                options: [
                  {
                    value: 'vertical',
                    label: __('Vertical', 'uipress-lite'),
                  },
                  {
                    value: 'horizontal',
                    label: __('Horizontal', 'uipress-lite'),
                  },
                ],
              },
              value: {
                value: 'vertical',
              },
            },
            ,
          ],
        },
        //Container options group
        {
          name: 'container',
          label: __('Block container', 'uipress-pro'),
          icon: 'crop_free',
          styleType: 'style',
          options: uipress.returnBlockConatinerOptions(),
        },
        //Container options group
        {
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'icons',
          label: __('Icons', 'uipress-pro'),
          icon: 'favorite',
          styleType: 'style',
          class: '.uip-icon',
          options: uipress.returnDefaultOptions(),
        },
        //Advanced options group
        {
          name: 'advanced',
          label: __('Advanced', 'uipress-pro'),
          icon: 'code',
          options: uipress.returnAdvancedOptions(),
        },
      ],
    },

    /**
     * Iframe block
     * @since 3.0.0
     */
    {
      name: __('Shortcode', 'uipress-pro'),
      moduleName: 'uip-shortcode',
      description: __('This block allows you to output custom shortcodes into the template', 'uipress-pro'),
      category: __('Elements', 'uipress-pro'),
      group: 'elements',
      path: uipProPath + 'assets/js/uip/blocks/elements/shortcode.min.js',
      icon: 'code_blocks',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'customCode',
              uniqueKey: 'shortcode',
              label: __('Shortcode', 'uipress-pro'),
              value: '[your_short_code]',
              args: {
                language: 'html',
              },
            },
          ],
        },
        //Container options group
        {
          name: 'container',
          label: __('Block container', 'uipress-pro'),
          icon: 'crop_free',
          styleType: 'style',
          options: uipress.returnBlockConatinerOptions(),
        },
        //Container options group
        {
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Advanced options group
        {
          name: 'advanced',
          label: __('Advanced', 'uipress-pro'),
          icon: 'code',
          options: uipress.returnAdvancedOptions(),
        },
      ],
    },
  ];
}
