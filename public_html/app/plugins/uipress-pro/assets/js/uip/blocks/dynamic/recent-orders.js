const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    props: {
      display: String,
      name: String,
      block: Object,
    },
    data: function () {
      return {
        searchString: '',
        results: [],
        page: 1,
        totalPages: 0,
        perPage: this.block.settings.block.options.postsPerPage.value,
        postTypes: ['shop_order'],
        limitToAuthor: false,
        loading: false,
        strings: {
          nothingFound: __('No posts found', 'uipress-lite'),
          by: __('By', 'uipress-lite'),
          order: __('Order', 'uipress-lite'),
          status: __('Status', 'uipress-lite'),
          total: __('Total', 'uipress-lite'),
          searchOrders: __('Search orders', 'uipress-lite'),
        },
        searching: false,
      };
    },
    inject: ['uipData', 'uipress', 'uiTemplate'],
    mounted: function () {
      this.getPosts();
    },
    watch: {
      page: {
        handler(newValue, oldValue) {
          if (newValue != '') {
            this.getPosts();
          }
        },
        deep: true,
      },
      perPage: {
        handler(newValue, oldValue) {
          this.getPosts();
        },
        deep: true,
      },
      searchString: {
        handler(newValue, oldValue) {
          if (this.page != 1) {
            this.page = 1;
          } else {
            this.getPosts();
          }
        },
        deep: true,
      },
      'block.settings.block.options.postsPerPage.value': {
        handler(newValue, oldValue) {
          this.getPosts();
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
    },
    methods: {
      getPosts() {
        let self = this;
        //Query already running
        if (self.loading) {
          return;
        }
        self.loading = true;

        //Build form data for fetch request
        let formData = new FormData();
        formData.append('action', 'uip_get_recent_orders');
        formData.append('security', uip_ajax.security);
        formData.append('search', self.searchString);
        formData.append('page', self.page);
        formData.append('perPage', self.returnPerPage);
        formData.append('searchString', self.searchString);

        self.uipress.callServer(uip_ajax.ajax_url, formData).then((response) => {
          if (response.error) {
            self.uipress.notify(response.message, '', 'error', true);
            self.searching = false;
            self.loading = false;
          }
          if (response.success) {
            self.loading = false;
            self.results = response.posts;
            self.totalPages = response.totalPages;
            console.log(response);
          }
        });
      },
      goBack() {
        if (this.page > 1) {
          this.page = this.page - 1;
        }
      },
      goForward() {
        if (this.page < this.totalPages) {
          this.page = this.page + 1;
        }
      },
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
    },
    template: `
			<div :class="block.settings.advanced.options.classes.value" class="uip-flex uip-flex-column uip-row-gap-s" :id="block.uid">
			  <div class="uip-flex uip-flex-column uip-row-gap-xs uip-list-area">
			  
			  
			  	<div v-if="!hasSearch" class="uip-flex uip-padding-xxs uip-border uip-search-block uip-border-round uip-margin-bottom-s">
					  <span class="uip-icon uip-text-muted uip-margin-right-xs uip-text-l uip-icon uip-icon-medium">search</span>
					  <input class="uip-blank-input uip-flex-grow uip-text-s" type="search" :placeholder="strings.searchOrders" v-model="searchString">
				</div>
				  
				  
				<div v-if="loading" class="uip-flex uip-flex-center uip-flex-middle uip-padding-m"><loading-chart></loading-chart></div>
				
				
				
				
				
				<div v-if="!loading" class="uip-max-w-100p uip-overflow-auto uip-scrollbar">
					<table class="uip-min-w-250 uip-w-100p uip-table">
					  <thead>
						<tr class="">
						  <th class="uip-text-muted uip-text-weight-normal uip-text-left uip-padding-bottom-xxs">{{strings.order}}</th>
						  <th class="uip-text-muted uip-text-weight-normal uip-text-right uip-padding-bottom-xxs">{{strings.status}}</th>
						  <th class="uip-text-right uip-text-muted uip-text-weight-normal uip-padding-bottom-xxs">{{strings.total}}</th>
						</tr>
					  </thead>
					  <tbody>
						<tr v-for="item in results">
						  <td class="uip-text-bold uip-padding-bottom-xxs"><a class="uip-link-default uip-no-underline" :href="item.editLink">{{item.name}}</a></td>
						  
						  <td class="uip-text-right uip-padding-bottom-xxs uip-flex uip-flex-right">
						  	<div class="uip-text-s uip-border-round uip-padding-xxxs uip-post-type-label" :class="returnStatusBG(item.status)">{{item.status}}</div>
						  </td>
						  
						  <td class="uip-text-right uip-padding-bottom-xxs">
							<div class=" uip-background-orange-wash uip-border-round uip-padding-xxxs uip-post-type-label uip-flex uip-gap-xxs uip-flex-center uip-text-bold uip-tag-label uip-inline-flex">
							  <span class="" v-html="item.total"></span>
							</div>
						  </td>
						</tr>
					  </tbody>
					</table>
				</div>
				
				
				
				<div v-if="results.length == 0 && searchString.length > 0" class="uip-text-muted uip-text-s">
				  {{strings.nothingFound}}
				</div>
			  </div>
			  <div class="uip-flex uip-gap-xs" v-if="totalPages > 1">
				<button @click="goBack" class="uip-button-default uip-icon uip-nav-button">chevron_left</button>
				<button @click="goForward" v-if="page < totalPages" class="uip-button-default uip-icon uip-nav-button">chevron_right</button>
			  </div>
			</div>`,
  };
}
