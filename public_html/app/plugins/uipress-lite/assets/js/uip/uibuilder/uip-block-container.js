export function moduleData() {
  return {
    props: {
      display: String,
      block: Object,
      itemIndex: Number,
      currentContent: Array,
    },
    data: function () {
      return {
        hover: false,
        delay: 200,
        selected: false,
        windowWidth: window.innerWidth,
        blockStyles: this.updateBlockStyle(),
        tips: [],
      };
    },
    inject: ['uipData', 'uiTemplate', 'router', 'uipress'],
    watch: {
      returnSettings: {
        handler(newValue, oldValue) {
          if (JSON.stringify(newValue) != JSON.stringify(oldValue)) {
            this.blockStyles = this.updateBlockStyle();
          }
        },
        deep: true,
      },
      'uipData.templateDarkMode': {
        handler(newVaue, oldValue) {
          this.blockStyles = this.updateBlockStyle();
        },
      },

      '$route.params.uid': {
        handler(newValue, oldValue) {
          //this.showInlineOptions();
        },
      },
    },
    mounted: function () {
      let self = this;

      self.showInlineOptions();

      document.addEventListener('uip_builder_preview_change', function (e) {
        //self.windowWidth = e.detail.windowWidth;
        self.blockStyles = self.updateBlockStyle();
      });
    },
    computed: {
      returnSettings() {
        return JSON.parse(JSON.stringify(this.block.settings));
      },
    },
    methods: {
      showInlineOptions() {
        let self = this;
        self.selected = false;

        if (self.$route.params.uid == self.block.uid) {
          self.selected = true;
        } else {
          self.selected = false;
        }

        let block = document.getElementById('uip-ui-preview-area').querySelector('#' + self.block.uid);

        if (block) {
          if (self.selected) {
            block.classList.add('uip-preview-selected-block');
          } else {
            block.classList.remove('uip-preview-selected-block');
          }
        }
      },

      getBlockStyles() {
        return this.blockStyles;
      },
      updateBlockStyle() {
        return this.uipress.render_block_styles(this.block.settings, this.block.uid, this.uipData.templateDarkMode, this.windowWidth);
      },
      openSettings() {
        let ID = this.$route.params.templateID;
        this.router.push('/uibuilder/' + ID + '/settings/blocks/' + this.block.uid);
      },
      removeBlock() {
        let uid = this.block.uid;
        this.currentContent.splice(this.itemIndex, 1);
      },
      duplicateBlock() {
        let item = Object.assign({}, this.currentContent[this.itemIndex]);
        item.uid = this.uipress.createUID();
        item.options = [];
        item.settings = JSON.parse(JSON.stringify(item.settings));
        this.currentContent.splice(this.itemIndex + 1, 0, item);
      },
      pushActiveHover(block, uid, evt) {
        let self = this;

        self.addHoverLogic(evt);

        return;

        self.showTooltip();
        self.hover = true;
        let index = self.uiTemplate.activePath.findIndex((x) => x.id === uid);
        let currentIndex = self.currentContent.findIndex((x) => x.uid === uid);

        if (index < 0) {
          self.uiTemplate.activePath.push({
            id: uid,
            item: block,
            container: this.currentContent,
            index: currentIndex,
          });
        }
        let elem = document.getElementById('uip-inline-block-options');
        let hover = self.$refs.container;

        let top = hover.getBoundingClientRect().top;
        let right = hover.getBoundingClientRect().right;

        elem.style.top = top + 'px';
        elem.style.left = right + 'px';
      },
      removeActiveHover() {
        let index = this.uiTemplate.activePath.findIndex((x) => x.id === this.block.uid);
        let self = this;
        self.$refs.container.classList.remove('uip-hover-selected-block');
        self.hover = false;
        self.removeTooltip();
        setTimeout(function () {
          if (!self.uiTemplate.activePathLock) {
            if (!self.hover) {
              self.uiTemplate.activePath.splice(index, 1);
            }
          } else {
            self.removeActiveHover();
          }
        }, 200);
      },
      ifHasCss() {
        if (this.block.settings.advanced) {
          if (this.block.settings.advanced.options.css) {
            if (this.block.settings.advanced.options.css.value) {
              return true;
            }
          }
        }
        return false;
      },
      ifHasJS() {
        if (this.block.settings.advanced) {
          if (this.block.settings.advanced.options.js) {
            if (this.block.settings.advanced.options.js.value) {
              return true;
            }
          }
        }
        return false;
      },
      removeTooltip() {
        let self = this;
        for (const tip of this.tips) {
          tip.remove();
        }
        self.tips = [];
      },
      showTooltip() {
        let self = this;
        if (!('tooltip' in this.block)) {
          return;
        }
        if (!('message' in this.block.tooltip)) {
          return;
        }
        if (this.block.tooltip.message == '') {
          return;
        }

        let tooltipContent = this.block.tooltip.message;

        let tip = document.createElement('div');
        tip.classList.add('uip-tooltip');
        tip.classList.add('uip-fade-in');
        tip.classList.add('uip-hidden');
        tip.setAttribute('id', 'tooltip-' + self.block.uid);
        tip.innerHTML = tooltipContent;

        let thetip = document.body.appendChild(tip);
        let content = document.getElementById(self.block.uid);
        self.tips.push(thetip);

        let bottomoftrigger = content.getBoundingClientRect().bottom;
        let triggerHalfWidth = content.getBoundingClientRect().width / 2;

        let POStop = bottomoftrigger + 10;
        let POSLeft = content.getBoundingClientRect().left;

        tip.style.top = POStop + 'px';
        tip.style.left = POSLeft + triggerHalfWidth + 'px';
        tip.style.transform = 'translateX(-50%)';

        //Delay show
        self.delay = 200;
        if (this.block.tooltip.delay && Number.isInteger(parseInt(this.block.tooltip.delay))) {
          self.delay = this.block.tooltip.delay;
        }
        setTimeout(function () {
          //We are not hovering anymore so remove
          if (!self.hover) {
            let activeTip = document.getElementById('tooltip-' + self.block.uid);
            if (activeTip) {
              activeTip.remove();
            }
            return;
          }
          let created = document.getElementById('tooltip-' + self.block.uid);
          created.classList.remove('uip-hidden');
          //Delete after 5 seconds
          setTimeout(function () {
            self.removeTooltip();
          }, 3000);
        }, self.delay);
      },
      responsiveHidden(responsive) {
        if (typeof responsive === 'undefined') {
          return true;
        }
        if (!responsive) {
          return true;
        }
        let screenWidth = window.innerWidth;
        if ('windowWidth' in this.uiTemplate) {
          screenWidth = this.uiTemplate.windowWidth;
        }
        //Hidden on mobile
        if (responsive.mobile == true && screenWidth < 699) {
          return false;
        }
        //Hidden on tablet
        if (responsive.tablet == true && screenWidth < 990 && screenWidth >= 699) {
          return false;
        }
        //Hidden on desktop
        if (responsive.desktop == true && screenWidth > 990) {
          return false;
        }
        return true;
      },

      addHoverLogic(evt) {
        let self = this;

        let target = evt.target.closest('.uip-block-builder-container');
        let targetUID = target.getAttribute('block-uid');
        if (targetUID == self.block.uid) {
          target.classList.add('uip-hover-selected-block');
          return;
        }

        self.$refs.container.classList.remove('uip-hover-selected-block');
      },
    },
    template: `
    
    <div ref="container" v-if="responsiveHidden(block.responsive)" class="uip-position-relative uip-flex uip-block-builder-container" 
    :block-uid="block.uid" :class="{'uip-border-dashed' : uiTemplate.display == 'builder'}"
    @mouseenter="pushActiveHover(block, block.uid, $event)" @mouseleave="removeActiveHover()" :id="'container-' + block.uid">
    
        <component is="style">
          {{blockStyles}}
        </component>
        
        <component is="style" v-if="ifHasCss()">{{block.settings.advanced.options.css.value}}</component>
        
        <component is="script" scoped v-if="ifHasJS()">
          {{block.settings.advanced.options.js.value}}
        </component>
        
	  		<slot name=""></slot>
        
        
		</div>
    

      
      
    `,
  };
}
