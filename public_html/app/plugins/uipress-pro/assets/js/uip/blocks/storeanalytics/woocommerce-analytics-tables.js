const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
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
          sold: __('Sold', 'uipress-pro'),
          revenue: __('Revenue', 'uipress-pro'),
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
      'uiTemplate.wooComnmerce.ready': {
        handler(newValue, oldValue) {
          this.getAnalytics();
        },
        deep: true,
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

        //Api is not ready yet. We will catch with attached watch
        if (!self.uipress.isObject(self.uiTemplate.wooComnmerce)) {
          self.apiLoaded = false;
          return;
        }
        if (!('ready' in self.uiTemplate.wooComnmerce)) {
          self.apiLoaded = false;
          return;
        }
        if (!self.uiTemplate.wooComnmerce.ready) {
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
        self.uiTemplate.wooComnmerce.api('get', startDate, endDate).then((response) => {
          //The API returned an error so set relevant vars and return
          if (response.error) {
            self.loading = false;
            self.error = true;
            self.errorMessage = response.message;
            return;
          }
          //The call was a success, so let's process it
          self.loading = false;
          self.currentRequest = response.data;
          self.processResponse();
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
        self.currentData = data[dataType];

        console.log(self.currentData);

        ///Update Cache
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
      returnSymbolTotal(total) {
        let self = this;

        if (self.currentRequest.currency_pos == 'left') {
          return self.currentRequest.currency + total;
        }
        if (self.currentRequest.currency_pos == 'left_space') {
          return self.currentRequest.currency + ' ' + total;
        }
        if (self.currentRequest.currency_pos == 'right') {
          return total + self.currentRequest.currency;
        }
        if (self.currentRequest.currency_pos == 'right_space') {
          return total + ' ' + self.currentRequest.currency;
        }

        return total;
      },
    },
    template: `
		  <div class="uip-flex uip-flex-column" :id="block.uid" :class="returnClasses">
      <div class="uip-flex uip-flex-between">
        <div class="uip-text-bold uip-margin-bottom-xxs uip-text-normal uip-chart-title">{{returnName}}</div>
      </div>
      <div class="uip-text-s uip-text-muted uip-margin-bottom-s uip-margin-bottom-s uip-dates">{{currentRequest.start_date}} - {{currentRequest.end_date}}</div>
      <div v-if="loading" class="uip-padding-m uip-flex uip-flex-center uip-flex-middle uip-min-w-200 uip-w-100p uip-ratio-16-10 uip-border-box"><loading-chart></loading-chart></div>
      <div v-else-if="error && errorMessage" class="uip-padding-xs uip-border-round uip-background-orange-wash uip-text-bold uip-margin-bottom-s uip-scale-in-top uip-max-w-100p" v-html="returnErrrorMessage()"></div>
      <div v-else-if="!returnChartType" class="uip-padding-xxs uip-border-round uip-background-green-wash uip-text-green uip-text-bold uip-margin-bottom-s uip-scale-in-top uip-max-w-200">{{strings.selectDataMetric}}</div>
      <div v-else class="uip-min-w-200">
        <div class="uip-flex uip-flex-column uip-row-gap-xs">
          <div class="uip-max-w-100p uip-overflow-auto uip-scrollbar">
            <table class="uip-min-w-250 uip-w-100p uip-table">
              <thead>
                <tr class="">
                  <th class="uip-padding-bottom-xxs"></th>
                  <th class="uip-text-muted uip-text-weight-normal uip-text-right uip-padding-bottom-xxs">{{strings.sold}}</th>
                  <th class="uip-text-right uip-text-muted uip-text-weight-normal uip-padding-bottom-xxs">{{strings.revenue}}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in returnTableData">
                  <td class="uip-text-bold uip-padding-bottom-xxs"><a class="uip-link-default uip-no-underline" :href="item.edit_url">{{item.name}}</a></td>
                  <td class="uip-text-right uip-padding-bottom-xxs">{{item.total_sold}}</td>
                  <td class="uip-text-right uip-padding-bottom-xxs">
                    <div class=" uip-background-orange-wash uip-border-round uip-padding-xxxs uip-post-type-label uip-flex uip-gap-xxs uip-flex-center uip-text-bold uip-tag-label uip-inline-flex">
                      <span class="">{{returnSymbolTotal(item.total)}}</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="uip-flex uip-flex-row uip-flex-between">
            <div class="uip-text-s uip-text-muted uip-chart-label">{{chartData.labels.main[0]}}</div>
            <div class="uip-text-s uip-text-muted uip-chart-label">{{chartData.labels.main[chartData.labels.main.length - 1]}}</div>
          </div>
        </div>
      </div>
		 </div>`,
  };
}
