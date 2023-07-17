const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: String,
    },
    data: function () {
      return {
        data: this.formatVal(this.value),
        search: '',
        strings: {
          username: __('Username', 'uipress-pro'),
          name: __('Name', 'uipress-pro'),
          password: __('Password', 'uipress-pro'),
          testConnection: __('Test connection', 'uipress-pro'),
          host: __('Host', 'uipress-pro'),
          enabled: __('Enabled', 'uipress-pro'),
        },
      };
    },
    inject: ['uipData', 'uipress'],
    watch: {
      data: {
        handler(newValue, oldValue) {
          this.returnData(this.data);
        },
        deep: true,
      },
    },

    mounted: function () {},
    computed: {
      returnDynamicData() {
        const ordered = Object.keys(this.dynamics)
          .sort()
          .reduce((obj, key) => {
            obj[key] = this.dynamics[key];
            return obj;
          }, {});
        return ordered;
      },
    },
    methods: {
      formatVal(val) {
        if (typeof val === 'undefined') {
          return {};
        }
        if (this.uipress.isObject(val)) {
          return val;
        }
        return {};
      },
      testDB() {
        let self = this;

        let data = JSON.stringify(self.data);

        let formData = new FormData();
        formData.append('action', 'uip_test_remote_database');
        formData.append('security', uip_ajax.security);
        formData.append('dataBase', data);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          self.loading = false;
          if (response.error) {
            self.uipress.notify(response.message, response.details, 'error', true, false);
            self.loading = false;
            return;
          }
          self.uipress.notify(response.message, '', 'success', true, false);
        });
      },
    },
    template: `
      
      <div class="uip-flex uip-flex-column uip-row-gap-s">
      
        <div class="">
          <switch-select :value="data.enabled" :returnData="function(d){data.enabled = d}">
        </div>
      
        <div class="" v-if="data.enabled">
          <div class="uip-text-s uip-text-muted uip-margin-bottom-xxs">{{strings.host}}</div>
          <input placeholder="path.com/to/host:port" type="text" class="uip-input uip-w-100p" v-model="data.host">
        </div>
        
        <div class="uip-grid-col-2" v-if="data.enabled">
        
          <div class="">
            <div class="uip-text-s uip-text-muted uip-margin-bottom-xxs">{{strings.username}}</div>
            <input :placeholder="strings.username" type="text" class="uip-input" v-model="data.username">
          </div>
          
          <div class="">
            <div class="uip-text-s uip-text-muted uip-margin-bottom-xxs">{{strings.name}}</div>
            <input :placeholder="strings.name" type="text" class="uip-input" v-model="data.name">
          </div>
          
          <div class="">
            <div class="uip-text-s uip-text-muted uip-margin-bottom-xxs">{{strings.password}}</div>
            <input :placeholder="strings.password" type="password" class="uip-input" v-model="data.password">
          </div>
        
        </div>
        
        <div class="uip-margin-top-xs" v-if="data.enabled">
          <button @click="testDB()" class="uip-button-default">{{strings.testConnection}}</button>
        </div>
        
      </div>
      
    
    `,
  };
}
