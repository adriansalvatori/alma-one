const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {},
    data: function () {
      return {
        ready: false,
        hasLicence: false,
        noWC: false,
        queryURL: false,
        reportRunning: false,
        token: false,
        saveAccountToUser: false,
        cache: {},
        queue: [],
        dev: false,
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    watch: {
      ready: {
        handler(newValue, oldValue) {
          this.uiTemplate.wooComnmerce.ready = this.returnStatus;
        },
        deep: true,
      },
    },
    mounted: function () {
      this.createQueue();
      this.startSetup();
      this.pushToGlobal();
    },
    computed: {
      returnStatus() {
        return this.ready;
      },
    },
    methods: {
      /**
       * Creates a queue so requests are not all sent at once
       * @since 3.0.0
       */
      createQueue() {
        const Queue = (onResolve, onReject) => {
          const items = [];

          const dequeue = () => {
            // no work to do
            if (!items[0]) return;

            items[0]()
              .then(function (response) {
                return response;
              })
              .catch(onReject)
              .then(() => items.shift())
              .then(dequeue)
              .then();
          };

          const enqueue = async (func) => {
            items.push(func);

            if (items.length === 1) {
              //let proccess = (resp) =>
              //new Promise((resolve, reject) => {
              //dequeue(resolve);
              //});

              return new Promise((resolve, reject) => {
                dequeue();
              });
            }
          };

          return enqueue;
        };

        this.queue = Queue();
      },
      /**
       * Pushes the API to the global template
       * @since 3.0.0
       */
      pushToGlobal() {
        this.uiTemplate.wooComnmerce = {};
        this.uiTemplate.wooComnmerce.api = this.uipWCAnalytics;
        this.uiTemplate.wooComnmerce.ready = this.returnStatus;
      },
      /**
       * The provided object and main base for analytics requests
       * @since 3.0.0
       */
      async uipWCAnalytics(action, startDate, endDate) {
        let self = this;

        ////No licence
        ////Return error if no account connected
        ////No licence
        if (!self.hasLicence) {
          let error = {};
          error.error = true;
          error.message = __('No pro licence found. Please add a valid pro licence to use woocommerce analytics', 'uipress-pro');
          error.type = 'no_licence';
          return error;
        }

        if (!self.noWC) {
          let error = {};
          error.error = true;
          error.message = __('Woocommerce needs to be active on this site to use woocommerce analytics blocks', 'uipress-pro');
          error.type = 'no_woocommerce';
          return error;
        }

        ////Get Report
        ////get analytics report, creates a async queue so as not to overload api calls to google
        ////Get Report
        if (action == 'get') {
          const dates = self.getDates(startDate, endDate);

          const returnAnalyticsReport = (dates, finisher) =>
            new Promise((resolve, reject) => {
              return self.runReport(dates, resolve, finisher);
            });

          return await new Promise((resolve, reject) => {
            this.queue(() => returnAnalyticsReport(dates, resolve));
          });
        }
      },

      /**
       * Set's up required features for analytics API
       * @since 3.0.0
       */
      startSetup() {
        this.getQueryURL();
      },
      /**
       * Gets analytics request URL or flags no account / no licence
       * @since 3.0.0
       */
      async getQueryURL(resolve) {
        let self = this;
        self.cache = {};

        self.hasLicence = false;
        self.noWC = false;
        self.ready = false;
        self.queryURL = false;
        //Build request
        let formData = new FormData();
        formData.append('action', 'uip_build_woocommerce_analytics_query');
        formData.append('security', uip_ajax.security);

        return await self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.ready = true;
          if (response.error) {
            //No uipress pro licence account added
            if (response.error_type == 'no_licence') {
              self.noWC = true;
              self.hasLicence = false;
            }
            if (response.error_type == 'no_woocommerce') {
              self.hasLicence = true;
              self.noWC = false;
            }

            if (typeof resolve !== 'undefined') {
              resolve(false);
            }
            return false;
          } else {
            //Set the query URL
            self.hasLicence = true;
            self.noWC = true;
            self.queryURL = response.url;
            if (typeof resolve !== 'undefined') {
              resolve(true);
            }

            return true;
          }
        });
      },
      /**
       * Fetches the google report for the given date range
       * @since 3.0.0
       */
      async runReport(dates, resolve, lastresolve) {
        let self = this;

        let query = `&sd=${dates.startDate}&ed=${dates.endDate}&sdc=${dates.startDateCom}&edc=${dates.endDateCom}`;
        let URL = self.queryURL + query;

        if (self.cache[dates.startDate + dates.endDate]) {
          resolve(true);
          lastresolve(self.cache[dates.startDate + dates.endDate]);
          return;
        }

        console.log('report running');
        //Report is running so let's queue it
        self.reportRunning = true;
        let formData = new FormData();

        formData.append('action', 'uip_run_woocommerce_analytics_query');
        formData.append('security', uip_ajax.security);
        formData.append('dates', JSON.stringify(dates));

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.reportRunning = false;
          if (response.error) {
            let errorMessage = response.message;
            let errorTitle = __('Unable to fetch analytic data', 'uipress-pro');
            self.uipress.notify(errorTitle, errorMessage, 'error', true, false);
            resolve(true);
            lastresolve(response);
          }

          self.cache[dates.startDate + dates.endDate] = response;
          resolve(true);
          lastresolve(response);
        });
      },

      /**
       * Builds required dates for request including a comparison range
       * @since 3.0.0
       */
      getDates(startDate, endDate) {
        let range = this.daysDifference(startDate, endDate);
        //let compRange = range * 2;

        //console.log(compRange);
        let dateObj = {};
        dateObj.startDate = this.returnFormattedDate(startDate);
        dateObj.endDate = this.returnFormattedDate(endDate);

        startDate.setDate(startDate.getDate() - 1);
        dateObj.endDateCom = this.returnFormattedDate(startDate);

        startDate.setDate(startDate.getDate() - range + 1);
        dateObj.startDateCom = this.returnFormattedDate(startDate);

        //console.log('start date ' + dateObj.startDate);
        //console.log('end date ' + dateObj.endDate);
        //console.log('comp start date ' + dateObj.startDateCom);
        //console.log('comp end date ' + dateObj.endDateCom);

        return dateObj;
      },
      /**
       * Formats dates for query
       * @since 3.0.0
       */
      returnFormattedDate(d) {
        let month = d.getMonth() + 1;
        let day = d.getDate();
        let year = d.getFullYear();

        if (month < 10) {
          month = '0' + month;
        }
        if (day < 10) {
          day = '0' + day;
        }

        return year + '-' + month + '-' + day;
      },
      /**
       * Gets days between two dates
       * @since 3.0.0
       */
      daysDifference(first, second) {
        let timeDif = second.getTime() - first.getTime();
        let oneDay = 1000 * 60 * 60 * 24;
        let result = Math.round(timeDif / oneDay);
        return result + 1;
      },
    },
    template: ' ',
  };
}
