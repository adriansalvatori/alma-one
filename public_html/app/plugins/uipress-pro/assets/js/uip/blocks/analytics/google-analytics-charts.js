const { __, _x, _n, _nx } = wp.i18n;
import * as connectGoogle from './google-connect-account.min.js';
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
      returnChartStyle() {
        let chartDataType = this.uipress.get_block_option(this.block, 'block', 'chartStyle');
        if ('value' in chartDataType) {
          if (chartDataType.value != '') {
            return chartDataType.value;
          }
        }
        return 'line';
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
        console.log(data);
        let chartDates = data.timeline.report.dates;
        let comparisonDates = data.timeline.report_comparison.dates;
        let dataType = self.returnChartType;

        let chartData = [];
        let chartComparisonData = [];

        for (let [index, value] of data.timeline.report.data.entries()) {
          ///Fix engagement values
          if ('userEngagementDuration' == dataType) {
            let totalTime = value[dataType];
            let totalUsers = value.totalUsers;
            let actualValue = 0;
            if (totalTime != 0 || totalUsers != 0) {
              actualValue = (totalTime / totalUsers).toFixed(2);
            }
            chartData.push(actualValue);
          } else {
            chartData.push(value[dataType]);
          }

          //Get comparison data
          if (typeof data.timeline.report_comparison.data[index] !== 'undefined') {
            if (dataType in data.timeline.report_comparison.data[index]) {
              if ('userEngagementDuration' == dataType) {
                let totalTime = data.timeline.report_comparison.data[index][dataType];
                let totalUsers = data.timeline.report_comparison.data[index].totalUsers;
                let actualValue = 0;
                if (totalTime != 0 || totalUsers != 0) {
                  actualValue = (totalTime / totalUsers).toFixed(2);
                }
                chartComparisonData.push(actualValue);
              } else {
                chartComparisonData.push(data.timeline.report_comparison.data[index][dataType]);
              }
            } else {
              chartComparisonData.push('0');
            }
          } else {
            chartComparisonData.push('0');
          }
        }

        ////
        ////
        ////
        //Set data
        self.chartData.colors.main = self.returnLineColor;
        self.chartData.colors.comp = self.returnCompLineColor;
        self.chartData.title = self.returnName;
        self.chartData.type = self.returnChartStyle;
        self.chartData.data.main = chartData;
        self.chartData.data.comparison = chartComparisonData;
        self.chartData.labels.main = chartDates;
        self.chartData.labels.comparison = comparisonDates;
        self.total = data.timeline.report.totals[dataType];
        if (dataType in data.timeline.report_comparison.totals) {
          self.comparisonTotal = data.timeline.report_comparison.totals[dataType];
        } else {
          self.comparisonTotal = 'NA';
        }
        self.percentChange = data.timeline.report.totals_change[dataType];

        if (self.total != '') {
          self.total = new Intl.NumberFormat(self.uipress.uipAppData.options.locale).format(self.total);
        }
        if (self.comparisonTotal != '' && self.comparisonTotal != 'NA') {
          self.comparisonTotal = new Intl.NumberFormat(self.uipress.uipAppData.options.locale).format(self.comparisonTotal);
        }

        if ('engagementRate' == dataType) {
          self.total = (self.total / data.timeline.report.dates.length).toFixed(2);
          self.total += '%';
          self.comparisonTotal = (self.comparisonTotal / data.timeline.report_comparison.dates.length).toFixed(2);
          self.comparisonTotal += '%';
        }
        if ('purchaserConversionRate' == dataType) {
          self.total += '%';
          self.comparisonTotal += '%';
        }

        if ('userEngagementDuration' == dataType) {
          self.total = self.secondsToTime(data.timeline.report.totals[dataType] / data.timeline.report.totals.totalUsers);
          self.comparisonTotal = self.secondsToTime(data.timeline.report_comparison.totals[dataType] / data.timeline.report_comparison.totals.totalUsers);
        }

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
    },
    template: `
		  <div class="uip-flex uip-flex-column" :id="block.uid" :class="returnClasses">
      <div class="uip-flex uip-flex-between">
        <div class="uip-margin-bottom-xxs uip-text-normal uip-chart-title">{{returnName}}</div>
        <drop-down dropPos="left" v-if="!inlineAccountSwitch && !showGoogleConnection">
          <template v-slot:trigger>
            <div class="uip-icon uip-link-muted uip-text-l">more_horiz</div>
          </template>
          <template v-slot:content>
            <div class="uip-flex uip-flex-row uip-gap-xxs uip-flex-center uip-padding-xxxs uip-link-muted" @click="removeAnalyticsAccount()">
              <div class="uip-icon">sync</div>\
              <div class="uip-padding-xxs">{{strings.changeAccount}}</div>
            </div>
          </template>
        </drop-down>  
      </div>
      <div class="uip-margin-bottom-xxs"><uip-connect-google v-if="showGoogleConnection" :success="refreshAnalytics"></uip-connect-google></div>
      <div v-if="loading" class="uip-padding-m uip-flex uip-flex-center uip-flex-middle uip-min-w-200 uip-w-100p uip-ratio-16-10 uip-border-box"><loading-chart></loading-chart></div>
      <div v-else-if="error && errorMessage" class="uip-padding-xs uip-border-round uip-background-orange-wash uip-text-bold uip-margin-bottom-s uip-scale-in-top uip-max-w-100p" v-html="returnErrrorMessage()"></div>
      <div v-else-if="!returnChartType" class="uip-padding-xxs uip-border-round uip-background-green-wash uip-text-green uip-text-bold uip-margin-bottom-s uip-scale-in-top uip-max-w-200">{{strings.selectDataMetric}}</div>
      <div v-else class="uip-min-w-200">
        <div class="uip-flex uip-flex-column uip-row-gap-xs">
          <div class="uip-flex uip-flex-between uip-gap-xxs uip-flex-center">
            <div class="uip-text-xl uip-text-bold">{{returnTotal}}</div>
            <div class="uip-text-s uip-background-orange-wash uip-border-round uip-padding-xxxs uip-padding-left-xs uip-padding-right-xs uip-post-type-label uip-flex uip-gap-xxs uip-flex-center uip-text-bold uip-tag-label">
              <span v-if="percentChange > 0" class="uip-icon">arrow_upward</span>
              <span v-if="percentChange < 0" class="uip-icon">arrow_downward</span>
              <span>{{percentChange}}%</span>
            </div>
          </div>
          <div class="uip-text-muted uip-margin-bottom-xs">vs. {{returnComparisonTotal}} {{strings.lastPeriod}}</div>
          <uip-chart v-if="!hideChart" :chartData="returnChartData"></uip-chart>
          <div class="uip-flex uip-flex-row uip-flex-between" v-if="!hideChart">
            <div class="uip-text-s uip-text-muted uip-chart-label">{{chartData.labels.main[0]}}</div>
            <div class="uip-text-s uip-text-muted uip-chart-label">{{chartData.labels.main[chartData.labels.main.length - 1]}}</div>
          </div>
        </div>
      </div>
		 </div>`,
  };
}
