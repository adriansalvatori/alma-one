const { __, _x, _n, _nx } = wp.i18n;
import * as connectGoogle from './google-connect-account.min.js';
import * as mapbox from '../../../libs/mapbox.js';
import * as countries from '../../../libs/country_data.min.js';
mapboxgl.accessToken = 'pk.eyJ1IjoibWFya2FzaHRvbiIsImEiOiJjbGRrZGYyc2IwamRuM3ZsZ2JudXdwMjRtIn0.V_oXBXMGBUBty-fZY0VXfg';

export function moduleData() {
  return {
    components: {
      'uip-connect-google': connectGoogle.moduleData(),
    },
    props: {
      display: String,
      name: String,
      block: Object,
      contextualData: Object,
    },

    data: function () {
      return {
        loading: true,
        error: false,
        apiLoaded: false,
        errorMessage: '',
        total: 0,
        comparisonTotal: 0,
        percentChange: 0,
        fetchingQuery: false,
        showGoogleConnection: false,
        requestFromGroupDate: false,
        currentRequest: false,
        startDate: '',
        endDate: '',
        currentData: [],
        chartData: {
          data: {
            main: [],
            comparison: [],
          },
          labels: {
            main: [],
            comparisons: [],
          },
          title: '',
          colors: {
            main: this.returnLineColor,
            comp: this.returnCompLineColor,
          },
        },
        strings: {
          lastPeriod: __('last period', 'uipress-pro'),
          selectDataMetric: __("Please select a chart metric in this block's options to show chart data.", 'uipress-pro'),
          changeAccount: __('Switch account', 'uipress-pro'),
          count: __('Count', 'uipress-pro'),
          change: __('Change', 'uipress-pro'),
          current: __('Current', 'uipress-pro'),
          previous: __('Previous', 'uipress-pro'),
          users: __('users', 'uipress-pro'),
        },
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    watch: {
      'block.settings.block.options.chartDataType': {
        handler(newValue, oldvalue) {
          if (this.currentRequest) {
            this.processResponse();
          }
        },
      },
      'contextualData.groupDate': {
        handler(newValue, oldValue) {
          this.getAnalytics();
        },
        deep: true,
      },
      'uiTemplate.googleAnalytics.ready': {
        handler(newValue, oldValue) {
          this.getAnalytics();
        },
        deep: true,
      },
      'uiTemplate.globalSettings.options.analytics.saveAccountToUser': {
        handler(newVal, oldVal) {
          this.refreshAnalytics();
        },
      },
    },
    mounted: function () {
      this.getAnalytics();
    },
    computed: {
      returnTableData() {
        return this.currentData;
      },
      returnTotal() {
        return this.total;
      },
      returnComparisonTotal() {
        return this.comparisonTotal;
      },
      returnChartData() {
        return this.chartData;
      },
      returnName() {
        let chartname = this.uipress.get_block_option(this.block, 'block', 'chartName', true);
        if (!chartname) {
          return '';
        }
        if (this.uipress.isObject(chartname)) {
          if ('string' in chartname) {
            return chartname.string;
          }
        } else {
          return chartname;
        }
      },
      returnChartType() {
        let chartDataType = this.uipress.get_block_option(this.block, 'block', 'chartDataType');
        return chartDataType;
      },
      returnLineColor() {
        let chartDataType = this.uipress.get_block_option(this.block, 'block', 'chartColour');
        return chartDataType;
      },
      returnCompLineColor() {
        let chartDataType = this.uipress.get_block_option(this.block, 'block', 'chartCompColour');
        return chartDataType;
      },
      hideChart() {
        let chartname = this.uipress.get_block_option(this.block, 'block', 'hideChart');
        return chartname;
      },
      inlineAccountSwitch() {
        let chartname = this.uipress.get_block_option(this.block, 'block', 'inlineAccountSwitch');
        return chartname;
      },
      darkMapTheme() {
        let darkMode = this.uipress.get_block_option(this.block, 'block', 'darkMode');
        return darkMode;
      },
      returnClasses() {
        let classes = '';
        let advanced = this.uipress.get_block_option(this.block, 'advanced', 'classes');
        classes += advanced;
        return classes;
      },
      returnRange() {
        let range = this.uipress.get_block_option(this.block, 'block', 'dateRange');
        if (range) {
          if (isNaN(range)) {
            return 14;
          }
          if (range > 60) {
            return 60;
          }
          return range;
        } else {
          return 14;
        }
      },
      hasGlobalDate() {
        if (typeof this.contextualData === 'undefined') {
          return false;
        }
        if (!this.uipress.isObject(this.contextualData)) {
          return false;
        }
        if (!('groupDate' in this.contextualData)) {
          return false;
        }
        if (!('start' in this.contextualData.groupDate)) {
          return false;
        }
        if (!('end' in this.contextualData.groupDate)) {
          return false;
        }
        return true;
      },
    },
    methods: {
      getAnalytics() {
        let self = this;
        //Reset Vars
        self.loading = true;
        self.error = false;
        self.errorMessage = '';
        self.showGoogleConnection = false;

        //Api is not ready yet. We will catch with attached watch
        if (!self.uipress.isObject(self.uiTemplate.googleAnalytics)) {
          self.apiLoaded = false;
          return;
        }
        if (!('ready' in self.uiTemplate.googleAnalytics)) {
          self.apiLoaded = false;
          return;
        }
        if (!self.uiTemplate.googleAnalytics.ready) {
          self.apiLoaded = false;
          return;
        }
        self.apiLoaded = true;
        //Dates//
        //Check for global dates
        //Dates//
        let startDate;
        let endDate;
        if (this.hasGlobalDate) {
          startDate = new Date(Date.parse(this.contextualData.groupDate.start));
          endDate = new Date(Date.parse(this.contextualData.groupDate.end));
        } else {
          //Build last two weeks date
          endDate = new Date();
          endDate.setDate(endDate.getDate() - 1);
          startDate = new Date();
          startDate.setDate(startDate.getDate() - self.returnRange);
        }

        self.startDate = startDate;
        self.endDate = endDate;

        //Send request to API
        self.uiTemplate.googleAnalytics.api('get', startDate, endDate).then((response) => {
          //The API returned an error so set relevant vars and return
          if (response.error) {
            self.loading = false;
            self.error = true;
            self.errorMessage = response.message;
            if (response.type == 'no_account') {
              self.showGoogleConnection = true;
            }
            return;
          }
          //The call was a success, so let's process it
          self.loading = false;
          self.currentRequest = response;
          self.processResponse(response);
        });

        return;
      },
      processResponse() {
        let self = this;
        let data = this.currentRequest;
        let dataType = self.returnChartType;

        if (!dataType) {
          return;
        }
        self.currentData = data[dataType].report.data;

        ///Update Cache
      },
      removeAnalyticsAccount() {
        let self = this;
        self.uiTemplate.googleAnalytics.ready = false;
        //Send request to API
        self.uiTemplate.googleAnalytics.api('removeAccount').then((response) => {
          //The API call was successful

          self.uiTemplate.googleAnalytics.api('refresh').then((response) => {
            //The API call was successful
            self.uiTemplate.googleAnalytics.ready = true;
            self.getAnalytics();
          });
        });
      },
      ///
      //Function pulled from https://stackoverflow.com/questions/3733227/javascript-seconds-to-minutes-and-seconds
      //Credit to Jakub
      secondsToTime(e) {
        if (isNaN(e)) {
          return 0;
        }
        const h = Math.floor(e / 3600)
            .toString()
            .padStart(2, '0'),
          m = Math.floor((e % 3600) / 60)
            .toString()
            .padStart(2, '0'),
          s = Math.floor(e % 60)
            .toString()
            .padStart(2, '0');

        if (m == 00) {
          return '0m ' + s + 's';
        } else {
          return m + 'm ' + s + 's';
        }
      },

      refreshAnalytics() {
        let self = this;
        self.uiTemplate.googleAnalytics.ready = false;
        //Send request to API
        self.uiTemplate.googleAnalytics.api('refresh').then((response) => {
          //The API call was successful
          if (response) {
            self.uiTemplate.googleAnalytics.ready = true;
            self.getAnalytics();
          }
        });
      },
      returnFormattedDate(d) {
        if (!d || d == '') {
          return '';
        }
        let month = d.getMonth() + 1;
        let day = d.getDate();
        let year = d.getFullYear();

        if (month < 10) {
          month = '0' + month;
        }
        if (day < 10) {
          day = '0' + day;
        }

        return year + '/' + month + '/' + day;
      },
      returnMappCss() {
        return uipProPath + 'assets/css/libs/mapbox.css';
      },
      returnErrrorMessage() {
        try {
          JSON.parse(this.errorMessage);
        } catch (error) {
          return this.errorMessage;
        }

        if (this.uipress.isObject(JSON.parse(this.errorMessage))) {
          let messs = JSON.parse(this.errorMessage);
          return `
              <h5 style="margin:0">${messs.status}</h5>
              <p style="margin-bottom:0;">${messs.message}</p>
            `;
        }

        return this.errorMessage;
      },
      buildMap() {
        requestAnimationFrame(() => {
          let countryData = countries.returnCountries();

          let theme = 'mapbox://styles/markashton/cldkdgzpx008t01rvfdmhtwet';
          if (this.darkMapTheme || this.uipData.userPrefs.darkTheme) {
            theme = 'mapbox://styles/markashton/cldklav6v009701rv47a1q7pa';
          }

          let map = new mapboxgl.Map({
            container: 'uip-ga-map',
            style: theme,
            zoom: 1,
            center: [-10, 14],
          });

          // Add zoom and rotation controls to the map.
          map.addControl(new mapboxgl.NavigationControl());

          let codes = countryData.ref_country_codes;

          // add markers to map
          for (const country of this.returnTableData) {
            //Find the country from the list
            let countryDetails = codes.find((obj) => {
              return obj.country.toLowerCase() === country.name.toLowerCase();
            });

            //Couldn't find the country details so skip this one.
            if (typeof countryDetails === 'undefined') {
              continue;
            }
            // create a HTML element for each feature
            const el = document.createElement('div');
            el.className = 'marker uip-w-10 uip-ratio-1-1 uip-background-primary-wash uip-border-circle uip-border-primary';

            let width = country.percent_total * 3;
            el.style.width = width + 'px';

            let arrow = '';
            if (country.change < 0) {
              arrow = 'arrow_downward';
            }
            if (country.change > 0) {
              arrow = 'arrow_upward';
            }

            let tooltip = `
            <div class="uip-background-default uip-boder uip-shadow uip-border-round">
              <div class="uip-background-default">
                <div class="uip-padding-xs uip-padding-top-xxs uip-padding-bottom-xxs uip-border-bottom uip-body-font uip-text-left uip-overflow-hidden uip-flex uip-flex-row uip-gap-xs uip-flex-between uip-flex-center">
                
                  <div class="uip-text-bold uip-text-emphasis">
                    ${country.name}
                  </div>
                  
                  <div class="uip-text-s uip-background-orange-wash uip-border-round uip-padding-xxxs uip-post-type-label uip-flex uip-gap-xxs uip-flex-center uip-text-bold uip-tag-label uip-text-muted">
                    <span class="uip-icon">${arrow}</span>
                    <span>${country.change}%</span>
                  </div>
                  
                </div>
                
                <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs">
                  <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-between uip-w-125">
                    <div class="uip-text-bold uip-text-primary" >${country.value + ' ' + this.strings.users}</div>
                    <div class="uip-text-s uip-text-muted">${this.strings.current}</div>
                  </div>
                  <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-between uip-w-125">
                    <div class="uip-text-bold uip-text-orange">${country.value_comp + ' ' + this.strings.users}</div>
                    <div class="uip-text-s uip-text-muted">${this.strings.previous}</div>
                  </div>
                </div>
              </div>
            </div>`;

            // make a marker for each feature and add it to the map
            new mapboxgl.Marker(el)
              .setLngLat([countryDetails.longitude, countryDetails.latitude])
              .setPopup(
                new mapboxgl.Popup({ offset: 25 }) // add popups
                  .setHTML(tooltip)
              )
              .addTo(map);
          }
        });
      },
    },
    template: `
		<div class="uip-flex uip-flex-column" :id="block.uid" :class="returnClasses">
    
      <component is="style">
        @import '{{returnMappCss()}}';
        .mapboxgl-ctrl-bottom-left, .mapboxgl-ctrl-bottom-right{
         display:none; 
        }
        .marker {
          border-radius: 50%;
          cursor: pointer;
          animation: pulse-outline 2.5s cubic-bezier(0.455, 0.03, 0.515, 0.955) -.4s infinite;
          transition: outline 5s ease, opacity .2s ease;
        }
        @keyframes pulse-outline {
          0% {
            outline: 0px solid var(--uip-color-primary-lighter);
          }
          100% {
           outline: 12px solid transparent;
          }
        }
        .mapboxgl-popup {
          max-width: 200px;
        }
        .mapboxgl-popup-content {
          padding:0;
          box-shadow:none;
          border-radius:4px;
        }
        .mapboxgl-popup-close-button {
          top:5px; 
          display:none;
        }
        .uip-dark-mode .mapboxgl-ctrl-top-right{
         filter: invert(1); 
        }
        .mapboxgl-popup-anchor-top .mapboxgl-popup-tip{
          border-bottom-color: var(--uip-color-base-0); 
        }
        .mapboxgl-popup-anchor-bottom .mapboxgl-popup-tip{
          border-top-color: var(--uip-color-base-0); 
        }
        .mapboxgl-popup-anchor-left .mapboxgl-popup-tip{
          border-right-color: var(--uip-color-base-0); 
        }
        .mapboxgl-popup-anchor-right .mapboxgl-popup-tip{
          border-left-color: var(--uip-color-base-0); 
        }
        .mapboxgl-popup-anchor-top-left .mapboxgl-popup-tip{
          border-bottom-color: var(--uip-color-base-0); 
        }
        .mapboxgl-popup-anchor-top-right .mapboxgl-popup-tip{
          border-bottom-color: var(--uip-color-base-0); 
        }
        .mapboxgl-popup-anchor-bottom-left .mapboxgl-popup-tip{
          border-top-color: var(--uip-color-base-0); 
        }
        .mapboxgl-popup-anchor-bottom-right .mapboxgl-popup-tip{
          border-top-color: var(--uip-color-base-0); 
        }
        
      </component>
      
      
      <div class="uip-flex uip-flex-between">
        <div class="uip-text-bold uip-margin-bottom-xxs uip-text-normal uip-chart-title">{{returnName}}</div>
        <drop-down dropPos="left" v-if="!inlineAccountSwitch && !showGoogleConnection">
          <template v-slot:trigger>
            <div class="uip-icon uip-link-muted">more_horiz</div>
          </template>
          <template v-slot:content>
            <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-padding-xxxs uip-link-muted" @click="removeAnalyticsAccount()">
              <div class="uip-icon">sync</div>\
              <div class="uip-padding-xxs">{{strings.changeAccount}}</div>
            </div>
          </template>
        </drop-down>  
      </div>
      <div class="uip-text-s uip-text-muted uip-margin-bottom-s uip-margin-bottom-s uip-dates">{{returnFormattedDate(startDate)}} - {{returnFormattedDate(endDate)}}</div>
      <div class="uip-margin-bottom-xxs" v-if="showGoogleConnection"><uip-connect-google :success="refreshAnalytics"></uip-connect-google></div>
      <div v-if="loading" class="uip-padding-m uip-flex uip-flex-center uip-flex-middle uip-min-w-200 uip-w-100p uip-ratio-16-10 uip-border-box"><loading-chart></loading-chart></div>
      <div v-else-if="error && errorMessage" class="uip-padding-xs uip-border-round uip-background-orange-wash uip-text-bold uip-margin-bottom-s uip-scale-in-top uip-max-w-100p" v-html="returnErrrorMessage()"></div>
      <div v-else-if="!returnChartType" class="uip-padding-xxs uip-border-round uip-background-green-wash uip-text-green uip-text-bold uip-margin-bottom-s uip-scale-in-top uip-max-w-200">{{strings.selectDataMetric}}</div>
      <div v-else class="uip-min-w-200">
        <div class="uip-flex uip-flex-column uip-row-gap-xs">
          
          <div class="uip-position-relative" 
          :class="{'uip-dark-mode' : darkMapTheme || uipData.userPrefs.darkTheme}">
            <div id='uip-ga-map' class="uip-ga-map uip-w-300 uip-h-200"></div>
          </div>
          
          {{buildMap()}}
          
          <div class="uip-flex uip-flex-row uip-flex-between">
            <div class="uip-text-s uip-text-muted uip-chart-label">{{chartData.labels.main[0]}}</div>
            <div class="uip-text-s uip-text-muted uip-chart-label">{{chartData.labels.main[chartData.labels.main.length - 1]}}</div>
          </div>
        </div>
      </div>
		 </div>`,
  };
}
