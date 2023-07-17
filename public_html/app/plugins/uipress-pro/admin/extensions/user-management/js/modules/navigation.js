export function moduleData() {
  return {
    props: {
      data: Object,
      dataChange: Function,
    },
    data: function () {
      return {
        loading: true,
        modData: this.data,
      };
    },
    mounted: function () {
      this.loading = false;
      this.setQuery();
    },
    watch: {
      modData: {
        handler(newValue, oldValue) {
          this.dataChange(newValue);
        },
        deep: true,
      },
    },
    computed: {},
    methods: {
      setQuery() {
        if (typeof this.$route.query == 'undefined') {
          this.modData.currentPage = 'users';
        } else if (this.$route.query.section) {
          if (this.$route.query.section != '') {
            this.modData.currentPage = this.$route.query.section;
          }
        }
      },
      returnPageTitle() {
        let self = this;
        let pageTitle = '';
        for (var page in self.modData.pages) {
          let opt = self.modData.pages[page];
          if (self.modData.currentPage == opt.name) {
            pageTitle = opt.label;
          }
        }
        return pageTitle;
      },
      changePage(page) {
        let self = this;

        self.modData.currentPage = page;
        this.$router.push({ query: { section: page } });
      },
    },
    template: `
    
    <div>
      
      <div class="uip-flex uip-flex-between uip-margin-bottom-m uip-flex-center">
        <div class="uip-flex uip-flex-row uip-gap-s uip-flex-center">
          <div class="uip-w-32 uip-ratio-1-1 uip-logo"></div>
          <div class="uip-flex uip-flex-column uip-row-gap-xxs">
            <span class="uip-text-xl uip-text-bold uip-text-emphasis">{{returnPageTitle()}}</span>
          </div>
        </div>
      </div>
      
	  	<div class="uip-flex uip-gap-xs">
	  		<template v-for="page in modData.pages">
	  			<div @click="changePage(page.name)"
				  class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xs uip-padding-top-xxs uip-padding-bottom-xxs uip-text-muted uip-text-bold uip-no-wrap" :class="{'uip-background-primary uip-text-inverse hover:uip-background-primary-dark' : page.name == modData.currentPage}">{{page.label}}</div>
	  		</template>
	  	</div>
	  </div>`,
  };
  return compData;
}
