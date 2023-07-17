const { __, _x, _n, _nx } = wp.i18n;
import '../../../libs/sortable.js';
import '../../../libs/vue-draggable.js';

export function moduleData() {
  return {
    components: {
      'uip-draggable': vuedraggable,
      'uip-order-list': orderList,
    },
    props: {
      display: String,
      name: String,
      block: Object,
    },
    data: function () {
      return {
        searchString: '',
        globalLoading: false,
        strings: {
          nothingFound: __('No posts found', 'uipress-pro'),
          by: __('By', 'uipress-pro'),
          order: __('Order', 'uipress-pro'),
          status: __('Status', 'uipress-pro'),
          total: __('Total', 'uipress-pro'),
          searchOrders: __('Search by customer name or email', 'uipress-pro'),
          loadMore: __('Load more', 'uipress-pro'),
        },
        searching: false,
        states: {
          onHold: {
            page: 1,
            totalPages: 0,
            found: 0,
            label: __('On hold', 'uipress-pro'),
            name: 'on-hold',
            orders: [],
            color: 'var(--uip-color-red-lighter)',
          },
          pendingPayment: {
            page: 1,
            totalPages: 0,
            found: 0,
            label: __('Payment pending', 'uipress-pro'),
            name: 'pending',
            orders: [],
            color: 'var(--uip-color-orange-lighter)',
          },
          processing: {
            page: 1,
            totalPages: 0,
            found: 0,
            label: __('Processing', 'uipress-pro'),
            name: 'processing',
            orders: [],
            color: 'var(--uip-color-green-lighter)',
          },
          completed: {
            page: 1,
            totalPages: 0,
            found: 0,
            label: __('Completed', 'uipress-pro'),
            name: 'completed',
            orders: [],
            color: 'var(--uip-color-primary-lighter)',
          },
        },
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    mounted: function () {
      this.getOrders();
    },
    watch: {
      searchString: {
        handler(newValue, oldValue) {
          if (newValue == '') {
            for (let state in this.states) {
              this.states[state].page = 1;
            }
            this.getOrders();
          }
        },
        deep: true,
      },
    },
    computed: {
      returnPerPage() {
        return this.block.settings.block.options.postsPerPage.value;
      },
      hasSearch() {
        let chartname = this.uipress.get_block_option(this.block, 'block', 'hideSearch');
        return chartname;
      },
      returnStates() {
        return this.states;
      },
    },
    methods: {
      fireFromSearch() {
        for (let state in this.states) {
          this.states[state].page = 1;
        }
        this.getOrders();
      },
      getOrders() {
        let self = this;

        //Query already running
        if (self.globalLoading) {
          return;
        }
        self.globalLoading = true;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_get_orders_for_kanban');
        formData.append('security', uip_ajax.security);
        formData.append('states', JSON.stringify(self.returnStates));
        formData.append('search', self.searchString);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            self.globalLoading = false;
          }
          if (response.success) {
            self.globalLoading = false;
            self.states = response.states;
          }
        });
      },
      getStateOrders(state, keepPage) {
        let self = this;

        if (!keepPage) {
          state.loading = true;
          state.page = parseInt(state.page) + 1;
        }

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_get_orders_for_kanban_by_state');
        formData.append('security', uip_ajax.security);
        formData.append('state', JSON.stringify(state));
        formData.append('search', self.searchString);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            state.loading = false;
          }
          if (response.success) {
            state.loading = false;
            state.orders = response.state.orders;
            state.found = response.state.found;
          }
        });
      },
    },
    template: `
			<div :class="block.settings.advanced.options.classes.value" class="uip-flex uip-flex-column uip-row-gap-s" :id="block.uid">
        
        
        <div class="uip-flex uip-margin-bottom-xs">
          <div class="uip-flex uip-padding-xxs uip-border uip-search-block uip-border-round uip-min-w-300">
              <span class="uip-icon uip-text-muted uip-margin-right-xs uip-text-l uip-icon uip-icon-medium">search</span>
              <input class="uip-blank-input uip-flex-grow uip-text-s" type="text" :placeholder="strings.searchOrders" v-model="searchString" v-on:keyup.enter="fireFromSearch()">
              <span class="uip-icon uip-text-muted uip-margin-right-xs uip-text-l uip-icon uip-icon-medium" v-if="searchString != ''">keyboard_return</span>
          </div>
        </div>
        
			  <div class="uip-grid-col-4 uip-list-area" style="--grid-layout-gap:var(--uip-margin-m);--grid-item--min-width:220px">
        
			     <template v-for="state in returnStates">
                
                <div class="uip-flex-grow uip-flex uip-flex-column uip-gap-s">
                
                  <!--title area-->
                  <div class="uip-flex uip-gap-xs uip-flex-center">
                    <div class="uip-text-muted uip-status-title">{{state.label}}</div>
                    <div class="uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-s uip-background-muted" >
                      {{state.found}}
                    </div>
                  </div>
                  <!--title area-->
                  
                  
                  <!--Order area-->
                  <div class="uip-flex uip-flex-column uip-row-gap-xs">
                  
                    <div class="uip-flex uip-flex-center uip-flex-middle uip-padding-s" v-if="globalLoading"><loading-chart></loading-chart></div>
                    <uip-order-list v-else :parent="state" :updateOrders="function(d){state.orders == d}" :fetchMore="getStateOrders"></uip-order-list>
                    
                  </div>
                  <!--End of order area-->
                  
                </div>
                
           </template>
			  
        </div>
				  
			</div>`,
  };
}

let orderList = {
  components: {
    'uip-draggable': vuedraggable,
  },
  props: {
    parent: Object,
    search: String,
    fetchMore: Function,
  },
  data: function () {
    return {
      cancelNotes: '',
      strings: {
        loadMore: __('Load more', 'uipress-pro'),
        cancelOrder: __('Cancel order', 'uipress-pro'),
        cancellationNotes: __('Cancellation notes', 'uipress-pro'),
        emptyColumn: __('No orders found with this status'),
      },
    };
  },
  inject: ['uipress'],
  mounted: function () {},

  watch: {},
  methods: {
    returnStatusBG(status) {
      if (status == 'completed') {
        return 'uip-background-primary-wash';
      }
      if (status == 'processing') {
        return 'uip-background-green-wash';
      }
      if (status == 'failed') {
        return 'uip-background-red-wash';
      }
      return 'uip-background-orange-wash';
    },
    setDragClasses() {
      let returnData = [];
      returnData.class = 'uip-flex uip-flex-column uip-row-gap-xs';

      return returnData;
    },
    returnStateColor(state) {
      let cl = 'background-color:' + this.parent.color;
      return cl;
    },
    itemAdded(evt) {
      let self = this;
      if (evt.added) {
        this.parent.found = parseInt(this.parent.found) + 1;
        this.updateOrderStatus(evt.added.element);
      }
      if (evt.removed) {
        if (parseInt(this.parent.found) > 0) {
          this.parent.found = parseInt(this.parent.found) - 1;
        }
        setTimeout(function () {
          self.fetchMore(self.parent, true);
        }, 1000);
      }
    },
    updateOrderStatus(order, status, index) {
      let self = this;

      if (typeof status === 'undefined') {
        status = self.parent.name;
      }

      let formData = new FormData();
      formData.append('action', 'uip_update_order_status');
      formData.append('security', uip_ajax.security);
      formData.append('orderID', order.ID);
      formData.append('newStatus', status);
      formData.append('cancelNotes', self.cancelNotes);

      self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
        if (response.error) {
          self.uipress.notify(response.message, '', 'error');
        }
        if (response.success) {
          self.uipress.notify(__('Order updated', 'uipress-pro'), '', 'success');
          if (status == 'cancelled') {
            self.parent.orders.splice(index, 1);
          }
        }
      });
    },
    returnQuickEdit(link) {
      let url = new URL(link);
      url.searchParams.set('uip-framed-page', 1);
      return url.href;
    },
  },
  template: `
    <uip-draggable
    v-model="parent.orders" 
    :component-data="setDragClasses()"
    :group="{ name: 'uip-orders', pull: true, put: true, revertClone: true }"
    handle=".uip-drag-handle"
    @start="drag = true" 
    @end="drag = false" 
    @change="itemAdded"
    animation="300"
    :sort="false"
    itemKey="id">
    
      <!--HEADER-->
      <template #header >
        
          <div class="uip-border-dashed uip-border-round uip-padding-xs uip-text-center uip-text-muted" v-if="parent.orders.length < 1" style="border-color:var(--uip-border-color)">
            {{strings.emptyColumn}}
          </div>
        
      </template>
      
        
      <template #item="{element: order, index}">
        
        <div class="uip-border-round uip-padding-xs uip-border uip-shadow-small uip-background-default uip-cursor-drag uip-order-card">
        
          <div class="uip-flex uip-flex-row uip-gap-xs">
          
            <div class="uip-flex uip-gap-s uip-flex-start uip-drag-handle uip-flex-grow">
              <div class="uip-border-circle uip-w-22 uip-ratio-1-1 uip-background-cover" :style="'background-image:url(' + order.img + ')'"></div>
              
              <div class="uip-flex-grow uip-flex uip-flex-column uip-row-gap-xxs">
              
                <div class="uip-text-emphasis">{{order.customerName}}</div>
                
                <div class="uip-flex uip-gap-xxs uip-flex-center uip-text-s uip-text-muted uip-flex-wrap">
                
                  <div class="" v-html="order.total"></div>
                  
                  <div class="">{{order.modified}}</div>
                  
                </div>
              
              </div>
              
              <div class="uip-border-round uip-padding-xxxs uip-post-type-label uip-flex uip-gap-xxs uip-flex-center uip-text-bold uip-tag-label uip-text-s" :style="returnStateColor(parent)">{{order.orderID}}</div>
            </div>
            
            <div class="">
            
              <drop-down dropPos="bottom-right">
              
                <template v-slot:trigger>
                  <div class="uip-icon uip-text-l uip-link-default">more_vert</div>
                </template>
                
                <template v-slot:content>
                  
                  <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs uip-border-bottom">
                    
                    <uip-offcanvas position="right" style="padding:0;width:600px;max-width:90%">
                      <template v-slot:trigger>
                        <div class="uip-flex uip-gap-xs uip-link-default">
                          <div class="uip-icon uip-text-l">visibility</div>
                          <div class="">Quick view</div>
                        </div>
                      </template>
                      <template v-slot:content>
                        
                        <iframe class="uip-w-100p uip-h-viewport" :src="returnQuickEdit(order.editLink)"></iframe>
                        
                      </template>
                    </uip-offcanvas>
                    
                    <a class="uip-flex uip-gap-xs uip-link-default uip-no-underline" :href="order.editLink">
                      <div class="uip-icon uip-text-l">edit</div>
                      <div class="">Edit</div>
                    </a>
                  
                  </div>
                  
                  
                  <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xxs">
                  
                    <div class="uip-flex uip-gap-xs uip-link-danger uip-no-underline">
                      <div class="uip-icon uip-text-l">cancel</div>
                      
                      <drop-down dropPos="bottom-right">
                        <template v-slot:trigger>
                          <div class="">{{strings.cancelOrder}}</div>
                        </template>
                        <template v-slot:content>
                          <div class="uip-padding-xs uip-flex uip-flex-column uip-row-gap-xs">
                            <textarea rows="4" class="uip-input" v-model="cancelNotes" :placeholder="strings.cancellationNotes + '...'"></textarea>
                            <button class="uip-button-danger" @click="updateOrderStatus(order, 'cancelled', index)">{{strings.cancelOrder}}</button>
                          </div>
                        </template>
                      </drop-down>    
                    </div>
                  
                  </div>
                  
                </template>
               
              </drop-down>  
            
            </div>
            
          </div>
          
        </div>
        
      </template>
      
      <!--FOOTER-->
      <template #footer >
        
        <div class="uip-flex uip-flex-center uip-flex-middle uip-padding-s" v-if="parent.loading"><loading-chart></loading-chart></div>
      
        <div class="uip-flex uip-gap-xxs uip-link-muted uip-margin-top-xs uip-flex-center" v-if="parent.orders.length < parent.found" @click="fetchMore(parent)">
          <div class="uip-icon">add</div>
          <div>{{strings.loadMore}}</div>
        </div>
        
      </template>
    
    </uip-draggable>
    `,
};
