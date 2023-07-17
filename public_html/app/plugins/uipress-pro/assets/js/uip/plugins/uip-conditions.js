export function moduleData() {
  return {
    props: {
      returnData: Function,
      value: Array,
      placeHolder: String,
      args: Object,
      size: String,
    },
    data: function () {
      return {
        option: this.value,
        strings: {
          newCondition: __('New condition', 'uipress-pro'),
          conditionExplanation: __('Conditions allow you to control who sees this block. If all conditions are not met then this block will not be loaded for the user.', 'uipress-pro'),
          type: __('Type', 'uipress-pro'),
          operators: __('Operator', 'uipress-pro'),
          searchUsers: __('Search', 'uipress-pro'),
        },
        conditions: {
          types: [
            {
              value: 'userrole',
              label: __('User role', 'uipress-pro'),
            },
            {
              value: 'userlogin',
              label: __('User login', 'uipress-pro'),
            },
            {
              value: 'userid',
              label: __('User ID', 'uipress-pro'),
            },
            {
              value: 'useremail',
              label: __('User email', 'uipress-pro'),
            },
          ],
          operators: [
            {
              value: 'is',
              label: __('Is', 'uipress-pro'),
            },
            {
              value: 'isnot',
              label: __('Is not', 'uipress-pro'),
            },
          ],
        },
      };
    },
    mounted: function () {
      this.processValue();
    },
    watch: {
      option: {
        handler(newValue, oldValue) {
          this.returnData(this.option);
        },
        deep: true,
      },
    },
    methods: {
      processValue() {
        if (typeof this.option === 'undefined') {
          this.option = [];
        }

        if (!Array.isArray(this.option)) {
          this.option = [];
        }
      },
      newCondition() {
        this.option.push({
          type: false,
          operator: false,
          value: '',
        });
      },
      removeCondition(index) {
        this.option.splice(index, 1);
      },
    },
    template: `
	
	<div class="uip-flex uip-w-100p uip-flex-column uip-row-gap-xs">
	
	  <div class="uip-text-s uip-text-muted uip-margin-bottom-xs">{{strings.conditionExplanation}}</div>
	
	  <div class="uip-flex uip-flex-column uip-row-gap-xs">
		<template v-for="(element, index) in option">
		  <div class="uip-flex uip-flex-row uip-gap-xxs">
			
			<select class="uip-input-small uip-max-w-100p" v-model="element.type">
			  <option disabled>{{strings.type}}</option>
			  <template v-for="item in conditions.types">
				<option :value="item.value">{{item.label}}</option>
			  </template>
			</select>
			
			<select class="uip-input-small uip-max-w-100p" v-model="element.operator">
			  <template v-for="item in conditions.operators">
				<option :value="item.value">{{item.label}}</option>
			  </template>
			</select>
			
			<input type="text" class="uip-input-small" style="min-width:1px" v-model="element.value">
			
			<user-role-search :selected="[]" :returnType="element.type" :searchPlaceHolder="strings.searchUsers" :updateSelected="function(d){element.value = d}"></user-role-search>
			
			<div class="uip-border-round uip-border-left-square uip-border-left-remove uip-text-l uip-flex uip-icon uip-padding-xxxs uip-text-center uip-cursor-pointer uip-icon uip-link-danger uip-flex-center" @click="removeCondition(index)">delete</div>
			
			
		  </div>
		  
		</template>
	  </div>
	  
	  <!--New condition-->
	  
	  <div class="uip-padding-xxs uip-border-round uip-background-muted hover:uip-background-grey uip-cursor-pointer uip-flex uip-flex-middle uip-flex-center uip-gap-xs uip-flex-grow" @click="newCondition">
		<span class="uip-icon">add</span>
		<span>{{strings.newCondition}}</span>
	  </div>
	  
	</div>`,
  };
}
