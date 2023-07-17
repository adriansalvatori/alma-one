const { __, _x, _n, _nx } = wp.i18n;
const uipress = new window.uipClass();
export function fetchBlocks() {
  return [
    /**
     * Text input block
     * @since 3.0.0
     */
    {
      name: __('GA charts', 'uipress-pro'),
      moduleName: 'uip-google-analytics',
      description: __('Outputs your choice of charts and options on what data to display', 'uipress-pro'),
      category: __('Analytics', 'uipress-pro'),
      group: 'analytics',
      path: uipProPath + 'assets/js/uip/blocks/analytics/google-analytics-charts.min.js',
      icon: 'bar_chart',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'chartName',
              label: __('Chart title', 'uipress-pro'),
              value: {
                string: __('Analytics chart', 'uipress-pro'),
              },
            },
            {
              option: 'defaultSelect',
              uniqueKey: 'chartDataType',
              label: __('Chart metric', 'uipress-pro'),
              args: {
                options: [
                  {
                    value: 'totalUsers',
                    label: __('Users', 'uipress-pro'),
                  },
                  {
                    value: 'totalRevenue',
                    label: __('Revenue', 'uipress-pro'),
                  },
                  {
                    value: 'sessions',
                    label: __('Sessions', 'uipress-pro'),
                  },
                  {
                    value: 'screenPageViews',
                    label: __('Page views', 'uipress-pro'),
                  },
                  {
                    value: 'purchaserConversionRate',
                    label: __('Conversion rate', 'uipress-pro'),
                  },
                  {
                    value: 'engagementRate',
                    label: __('Engagement rate', 'uipress-pro'),
                  },
                  {
                    value: 'ecommercePurchases',
                    label: __('Purchases', 'uipress-pro'),
                  },
                  {
                    value: 'checkouts',
                    label: __('Checkouts', 'uipress-pro'),
                  },
                  {
                    value: 'addToCarts',
                    label: __('Add to carts', 'uipress-pro'),
                  },
                  {
                    value: 'userEngagementDuration',
                    label: __('Average engagement time', 'uipress-pro'),
                  },
                ],
              },
              value: '',
            },
            {
              option: 'choiceSelect',
              uniqueKey: 'chartStyle',
              label: __('Chart type', 'uipress-pro'),
              args: {
                options: [
                  {
                    value: 'line',
                    label: __('Line', 'uipress-pro'),
                  },
                  {
                    value: 'bar',
                    label: __('Bar', 'uipress-pro'),
                  },
                ],
              },
              value: {
                value: 'line',
              },
            },
            { option: 'number', uniqueKey: 'dateRange', label: __('Default date range (days)', 'uipress-pro'), value: 14 },
            { option: 'simpleColorPicker', uniqueKey: 'chartColour', label: __('Line colour', 'uipress-pro') },
            { option: 'simpleColorPicker', uniqueKey: 'chartCompColour', label: __('Comparison line colour', 'uipress-pro') },
            { option: 'trueFalse', uniqueKey: 'hideChart', label: __('Hide chart', 'uipress-pro') },
            { option: 'trueFalse', uniqueKey: 'inlineAccountSwitch', label: __('Disable change account option', 'uipress-pro') },
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
          name: 'title',
          label: __('Table title', 'uipress-pro'),
          icon: 'title',
          styleType: 'style',
          class: '.uip-chart-title',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'chartCanvas',
          label: __('Chart canvas', 'uipress-pro'),
          icon: 'monitoring',
          styleType: 'style',
          class: '.uip-chart-canvas',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'chartLabels',
          label: __('Chart labels', 'uipress-pro'),
          icon: 'input',
          styleType: 'style',
          class: '.uip-chart-label',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'percentChange',
          label: __('Percentage change', 'uipress-pro'),
          icon: 'label',
          styleType: 'style',
          class: '.uip-tag-label',
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
     * Text input block
     * @since 3.0.0
     */
    {
      name: __('GA tables', 'uipress-pro'),
      moduleName: 'uip-google-analytics-tables',
      description: __('Outputs your choice of tables and options on what data to display', 'uipress-pro'),
      category: __('Analytics', 'uipress-pro'),
      group: 'analytics',
      path: uipProPath + 'assets/js/uip/blocks/analytics/google-analytics-tables.min.js',
      icon: 'table_chart',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'chartName',
              label: __('Chart title', 'uipress-pro'),
              value: {
                string: __('Analytics table', 'uipress-pro'),
              },
            },
            {
              option: 'defaultSelect',
              uniqueKey: 'chartDataType',
              label: __('Chart metric', 'uipress-pro'),
              args: {
                options: [
                  {
                    value: 'cities',
                    label: __('Visits by City', 'uipress-pro'),
                  },
                  {
                    value: 'countries',
                    label: __('Visits by Country', 'uipress-pro'),
                  },
                  {
                    value: 'sources',
                    label: __('Visits by source', 'uipress-pro'),
                  },
                  {
                    value: 'paths',
                    label: __('Visits by page', 'uipress-pro'),
                  },
                  {
                    value: 'events',
                    label: __('Events', 'uipress-pro'),
                  },
                  {
                    value: 'devices',
                    label: __('Devices', 'uipress-pro'),
                  },
                ],
              },
              value: '',
            },
            { option: 'number', uniqueKey: 'dateRange', label: __('Default date range (days)', 'uipress-pro'), value: 14 },
            { option: 'trueFalse', uniqueKey: 'inlineAccountSwitch', label: __('Disable change account option', 'uipress-pro') },
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
          name: 'title',
          label: __('Table title', 'uipress-pro'),
          icon: 'title',
          styleType: 'style',
          class: '.uip-chart-title',
          options: uipress.returnDefaultOptions(),
        },
        ///Dates
        {
          name: 'dates',
          label: __('Dates', 'uipress-pro'),
          icon: 'date_range',
          styleType: 'style',
          class: '.uip-dates',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'table',
          label: __('Table', 'uipress-pro'),
          icon: 'table',
          styleType: 'style',
          class: '.uip-table',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'percentChange',
          label: __('Percentage change', 'uipress-pro'),
          icon: 'label',
          styleType: 'style',
          class: '.uip-tag-label',
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

    {
      name: __('GA map', 'uipress-pro'),
      moduleName: 'uip-google-analytics-map',
      description: __('Outputs your visitor data to a interactive map', 'uipress-pro'),
      category: __('Analytics', 'uipress-pro'),
      group: 'analytics',
      path: uipProPath + 'assets/js/uip/blocks/analytics/google-analytics-map.min.js',
      icon: 'map',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'chartName',
              label: __('Chart title', 'uipress-pro'),
              value: {
                string: __('Analytics table', 'uipress-pro'),
              },
            },
            {
              option: 'defaultSelect',
              uniqueKey: 'chartDataType',
              label: __('Chart metric', 'uipress-pro'),
              args: {
                options: [
                  {
                    value: 'countries',
                    label: __('Visits by Country', 'uipress-pro'),
                  },
                ],
              },
              value: '',
            },
            { option: 'number', uniqueKey: 'dateRange', label: __('Default date range (days)', 'uipress-pro'), value: 14 },
            { option: 'trueFalse', uniqueKey: 'darkMode', label: __('Use dark theme for map', 'uipress-pro') },
            { option: 'trueFalse', uniqueKey: 'inlineAccountSwitch', label: __('Disable change account option', 'uipress-pro') },
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
          name: 'title',
          label: __('Table title', 'uipress-pro'),
          icon: 'title',
          styleType: 'style',
          class: '.uip-chart-title',
          options: uipress.returnDefaultOptions(),
        },
        ///Dates
        {
          name: 'dates',
          label: __('Dates', 'uipress-pro'),
          icon: 'date_range',
          styleType: 'style',
          class: '.uip-dates',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'map',
          label: __('Map', 'uipress-pro'),
          icon: 'map',
          styleType: 'style',
          class: '.uip-ga-map',
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
     * Text input block
     * @since 3.0.0
     */
    {
      name: __('GA realtime', 'uipress-pro'),
      moduleName: 'uip-google-realtime',
      description: __('Displays live visitor data about your site', 'uipress-pro'),
      category: __('Analytics', 'uipress-pro'),
      group: 'analytics',
      path: uipProPath + 'assets/js/uip/blocks/analytics/google-analytics-realtime.min.js',
      icon: 'schedule',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'chartName',
              label: __('Chart title', 'uipress-pro'),
              value: {
                string: __('Analytics realtime', 'uipress-pro'),
              },
            },
            {
              option: 'defaultSelect',
              uniqueKey: 'chartDataType',
              label: __('Chart metric', 'uipress-pro'),
              args: {
                options: [
                  {
                    value: 'activeUsers',
                    label: __('Active users', 'uipress-pro'),
                  },
                  {
                    value: 'conversions',
                    label: __('Conversions', 'uipress-pro'),
                  },
                ],
              },
              value: '',
            },
            { option: 'simpleColorPicker', uniqueKey: 'chartColour', label: __('Line colour', 'uipress-pro') },
            { option: 'trueFalse', uniqueKey: 'inlineAccountSwitch', label: __('Disable change account option', 'uipress-pro') },
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
          name: 'title',
          label: __('Table title', 'uipress-pro'),
          icon: 'title',
          styleType: 'style',
          class: '.uip-chart-title',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'chartLabels',
          label: __('Chart labels', 'uipress-pro'),
          icon: 'input',
          styleType: 'style',
          class: '.uip-chart-label',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'percentChange',
          label: __('Percentage change', 'uipress-pro'),
          icon: 'label',
          styleType: 'style',
          class: '.uip-tag-label',
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
