/**
 * Builds the main ui builder shell
 * @since 3.0.0
 */
const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    inject: ['uipData', 'router', 'uipress', 'uiTemplate', 'unsavedChanges'],
    data: function () {
      return {
        history: {
          states: [],
          index: 0,
        },

        ui: {
          strings: {
            undo: __('Undo', 'uipress-lite'),
            redo: __('Redo', 'uipress-lite'),
            historyStates: __('History states', 'uipress-lite'),
          },
        },
      };
    },
    watch: {
      'history.index': {
        handler(newValue, oldValue) {
          this.changehistoryState(newValue);
        },
        deep: true,
      },
    },
    mounted: function () {
      let self = this;

      //set default state
      let newTem = JSON.parse(JSON.stringify(self.uiTemplate.content));
      self.logHistoryState(__('Project opened', 'uipress-lite'), newTem, newTem);

      //hooks into layout changes in template and logs them
      document.addEventListener(
        'uip_builder_history_change',
        (e) => {
          self.unsavedChanges = true;
          self.logHistoryState(e.detail.type, e.detail.oldTemplate, e.detail.newTemplate);
        },
        { once: false }
      );

      document.addEventListener('keydown', this.handleKeyCombination);
    },
    beforeDestroy() {
      document.removeEventListener('keydown', this.handleKeyCombination);
    },
    computed: {
      returnHistoryStates() {
        return this.history.states;
      },
    },

    methods: {
      handleKeyCombination() {
        // Check if Command key is pressed on macOS or Control key is pressed on Windows
        const ctrlOrCmdPressed = event.metaKey || event.ctrlKey;
        const shiftKeyPressed = event.shiftKey;
        const zKeyPressed = event.which === 90;
        if (ctrlOrCmdPressed && zKeyPressed && !shiftKeyPressed) {
          this.historyBackward();
        }
        if (ctrlOrCmdPressed && zKeyPressed && shiftKeyPressed) {
          this.historyForward();
        }
      },
      changehistoryState(index) {
        let newHistory = this.history.states[index].template;
        this.uiTemplate.content = newHistory;
      },
      logHistoryState(context, oldValue, newValue) {
        let states = this.history.states;

        var d = new Date();
        let time = d.getHours() + ':' + String(d.getMinutes()).padStart(2, '0');

        if (newValue == false) {
          newValue = JSON.parse(JSON.stringify(this.uiTemplate.content));
        }
        let newstate = { name: context, time: time, template: newValue };
        states.splice(0, 0, newstate);

        //Only keep ten history items
        if (states.length > 10) {
          states.pop();
        }
        this.history.index = 0;
        this.history.states = states;
      },
      historyBackward() {
        let index = this.history.index;

        if (index < this.history.states.length - 1) {
          this.history.index += 1;
          return true;
        }
        return false;
      },
      historyForward() {
        let index = this.history.index;

        if (index > 0) {
          this.history.index = this.history.index - 1;
          return true;
        }
        return false;
      },
    },
    template: `
    
      <div class="uip-flex uip-gap-xxs uip-flex-center uip-background-muted uip-border-rounder uip-padding-xs uip-padding-top-xxs uip-padding-bottom-xxs">
  			<div :title="ui.strings.undo" 
        @click="historyBackward()" class="hover:uip-background-muted uip-border-round uip-flex uip-flex-center  uip-icon uip-link-default uip-text-xl uip-ratio-1-1 uip-line-height-1 uip-icon-medium" :class="{'uip-text-muted' : history.index == history.states.length - 1 || history.states.length == 0}">
				undo
  			</div>
        
  			<div :title="ui.strings.redo" 
        @click="historyForward()" class="hover:uip-background-muted uip-border-round uip-flex uip-flex-center  uip-icon uip-link-default uip-text-xl uip-ratio-1-1 uip-line-height-1 uip-icon-medium" 
  			:class="{'uip-text-muted' : history.index < 1 || history.states.length == 0}">
				redo
  			</div>
        
        <div class="uip-border-right uip-h-18 uip-margin0-left-xxs uip-margin-right-xxs"></div>
        
  		  <drop-down dropPos="bottom-left" :title="ui.strings.historyStates">
				  <template v-slot:trigger>
	  				  <div :title="ui.strings.historyStates" 
              class="hover:uip-background-muted uip-border-round uip-flex uip-flex-center  uip-icon uip-link-default uip-text-xl uip-ratio-1-1 uip-line-height-1 uip-icon-medium">
						  history
	  				  </div>
				  </template>
				  <template v-slot:content>
	  				  <div class="uip-flex uip-flex-column uip-row-gap-xs uip-padding-s uip-min-w-200" dropPos="bottom-left">
						  <div class="uip-text-bold">{{ui.strings.historyStates}}</div>
						  <template v-for="(state, index) in returnHistoryStates">
		  					  <div @click="history.index = index" class="uip-background-muted uip-border-round uip-padding-xxs uip-flex uip-flex-column uip-cursor-pointer hover:uip-background-grey" :class="{'uip-background-primary-wash' :index == history.index}">
								  <div>{{state.name}}</div>
								  <div class="uip-text-muted uip-text-xs">{{state.time}}</div>
		  					  </div>
						  </template>
	  				  </div>
				  </template>
  		  </drop-down>
      </div>  
	  	`,
  };
  return compData;
}
