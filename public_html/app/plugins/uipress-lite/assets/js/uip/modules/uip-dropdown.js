export function moduleData() {
  return {
    props: {
      hover: Boolean,
      dropPos: String, // Dropdown position 'top-left' 'top-right' 'right' 'left' 'bottom-left' 'bottom-right'
      externalOpen: Boolean, //Allows drop to be opened from outside the component
      triggerClass: String, // Allows custom classes to be set on the trigger container
      containerClass: String, // Allows custom classes to be set on the trigger container
      onOpen: Function, //Custom function to run when the drop opens
      shortCut: [Boolean, String, Array],
      dontAnimate: Boolean,
      screenHeight: Array,
      slotClass: String,
      dropClass: String,
      relative: Boolean,
      removeOverflow: Boolean,
    },
    data: function () {
      return {
        modelOpen: false,
        dropWidth: 0,
        position: this.dropPos,
        animationClass: 'slide-down',
        hovering: false,
        isRelative: false,
      };
    },
    watch: {
      externalOpen: {
        handler(newValue, oldValue) {
          if (newValue) {
            this.openThisComponent();
          }
        },
        deep: true,
      },
    },
    mounted: function () {
      this.mountShortcut();
      if (this.externalOpen) {
        this.openThisComponent();
      }
    },
    destroyed() {
      document.removeEventListener('scroll', this.handleScroll, true);
    },
    unmounted() {
      document.removeEventListener('scroll', this.handleScroll, true);
    },
    computed: {
      returnAnimationClass() {
        if (!this.dontAnimate) {
          return this.animationClass;
        }
        return '';
      },
      returnRelativePos() {
        let style = '';
        if (this.position.includes('top')) {
          style += 'bottom: 0;';
        }
        if (this.position.includes('right')) {
          style += 'right: 0;';
        }
        if (this.position.includes('left')) {
          style += 'left: 0;';
        }
        if (this.position.includes('bottom')) {
          style += 'top: 0;';
        }
        if (this.position == 'left') {
          style = 'right: 0; top: 0;';
        }
        if (this.position == 'right') {
          style = 'left: 0; top: 0;';
        }

        return style;
      },
      returnFullHeightStyle() {
        if (this.screenHeight) {
          return `max-height: calc( 100vh - ${this.screenHeight[0]}px)`;
        }
      },
    },
    methods: {
      mountShortcut() {
        if (!this.shortCut) {
          return;
        }

        let shortcut = JSON.parse(JSON.stringify(this.shortCut));
        let pressedKeys = [];

        window.addEventListener('keydown', (event) => {
          let shortcutPressed = false;

          pressedKeys.push(event.key.toString());

          shortcutPressed = true;
          for (let item of shortcut) {
            if (!pressedKeys.includes(item)) {
              shortcutPressed = false;
              break;
            }
          }

          if (shortcutPressed) {
            this.openThisComponent();
          }
        });

        window.addEventListener('keyup', (event) => {
          pressedKeys = [];
        });
      },
      handleScroll(event) {
        this.setPosition();
      },
      onClickOutside(event) {
        if (!this.$refs.uipdrop) {
          return;
        }
        const mediaLibrary = document.getElementById('uip-media-library');

        if (this.$refs.droptrigger.contains(event.target)) {
          return;
        }
        // check if the MouseClick occurs inside the component
        if (!this.$refs.uipdrop.contains(event.target)) {
          if (mediaLibrary) {
            if (!mediaLibrary.contains(event.target)) {
              this.closeThisComponent(); // whatever method which close your component
            }
          } else {
            this.closeThisComponent(); // whatever method which close your component
          }
        }
      },
      openThisComponent(evt) {
        evt.preventDefault;
        //Already open
        if (this.modelOpen) {
          this.closeThisComponent();
          return;
        }

        this.modelOpen = true;

        if (this.modelOpen == true) {
          document.addEventListener('scroll', this.handleScroll, true);
        }
        //this.setPosition();
        // You can also use Vue.$nextTick or setTimeout
        requestAnimationFrame(() => {
          document.documentElement.addEventListener('click', this.onClickOutside, true);

          //Adds a listener for clicks to the frame
          let frame = document.getElementsByClassName('uip-page-content-frame');
          if (frame[0]) {
            frame[0].contentWindow.document.body.addEventListener('click', this.onClickOutside, true);
          }

          if (this.onOpen) {
            this.onOpen();
          }

          requestAnimationFrame(() => {
            this.setPosition();
          });
        });
      },
      closeThisComponent() {
        this.modelOpen = false; // whatever codes which close your component
        document.documentElement.removeEventListener('click', this.onClickOutside, true);
        document.removeEventListener('scroll', this.handleScroll, true);

        let frame = document.getElementsByClassName('uip-page-content-frame');
        if (frame[0]) {
          frame[0].contentWindow.document.body.removeEventListener('click', this.onClickOutside, true);
        }
      },
      setRelativePosition() {
        self = this;
        ///SET TOP

        if (!self.$el) {
          return;
        }

        let drop = self.$refs.uipdrop;
        let trigger = self.$refs.droptrigger;

        if (!drop || !trigger) {
          return;
        }

        if (!this.position) {
          this.position = 'bottom-left';
        }

        let boundary = 8;
        let POStop = 'auto';
        let POSbottom = 'auto';
        let POSleft = 'auto';
        let POSright = 'auto';

        //Setup bottom left
        if (this.position == 'bottom-left') {
          POStop = `calc(100% + ${boundary}px)`;
          POSleft = 0;
        }

        //Setup bottom right
        if (this.position == 'bottom-right') {
          POStop = `calc(100% + ${boundary}px)`;
          POSright = 0;
        }

        //Setup right
        if (this.position == 'right') {
          POStop = 0;
          POSleft = `calc(100% + ${boundary}px)`;
        }

        //Setup left
        if (this.position == 'left') {
          POStop = 0;
          POSright = `calc(100% + ${boundary}px)`;
        }

        //Setup top left
        if (this.position == 'top-left') {
          POSbottom = `calc(100% + ${boundary}px)`;
          POSleft = 0;
        }

        //Setup top right
        if (this.position == 'top-right') {
          POSbottom = `calc(100% + ${boundary}px)`;
          POSright = 0;
        }

        ///Set the animation class
        if (this.position.includes('top')) {
          this.animationClass = 'slide-up';
        }

        drop.style.bottom = POSbottom;
        drop.style.left = POSleft;
        drop.style.right = POSright;
        drop.style.top = POStop;

        if (this.screenHeight) {
          drop.style.top = this.screenHeight[0] + 'px';
          drop.style.bottom = 0;
          drop.style.left = trigger.getBoundingClientRect().right + 'px';
          drop.style.right = 'auto';
        }
      },
      setPosition() {
        self = this;
        let returnDatat = 0;
        ///SET TOP

        if (!self.$el) {
          return;
        }

        //Forced relative positioning
        if (this.relative) {
          this.isRelative = true;
          this.setRelativePosition();
          return;
        }

        //When drops are used in the template preview area, the position is off due to the container being scaled. The below works out the offset
        let preview = document.getElementById('uip-ui-preview-area');
        if (preview) {
          if (preview.contains(self.$refs.uipdrop)) {
            this.isRelative = true;
            this.setRelativePosition();
            return;
          }
        }

        this.isRelative = false;

        let drop = self.$refs.uipdrop;
        let trigger = self.$refs.droptrigger;

        if (!drop || !trigger) {
          return;
        }

        let POStop = 'auto';
        let POSbottom = 'auto';
        let POSleft = 'auto';
        let POSright = 'auto';

        if (!this.position) {
          this.position = 'bottom-left';
        }

        //If trigger is near bottom of page flip to top
        if (drop.getBoundingClientRect().bottom > window.innerHeight - 20) {
          this.position = this.dropPos.replace('bottom', 'top');
          if (this.dropPos == 'left') {
            this.position = 'left-center';
          }
          if (this.dropPos == 'right') {
            this.position = 'top-left';
          }
        }
        //If trigger is near right of page flip to left
        if (drop.getBoundingClientRect().right > window.innerWidth - 20) {
          if (this.dropPos == 'right') {
            this.position = 'left';
          }
          if (this.dropPos == 'bottom-left' || this.dropPos == 'top-left') {
            this.position = this.dropPos.replace('left', 'right');
          }
        }

        //If trigger is near left of page flip to right
        if (drop.getBoundingClientRect().left < 20) {
          if (this.dropPos == 'top-right') {
            this.position == 'top-left';
          }
          if (this.dropPos == 'left') {
            this.position = this.position.replace('left', 'right');
          }
        }

        if (!this.position || this.position == 'bottom-left') {
          POStop = trigger.getBoundingClientRect().bottom + 10 + 'px';
          POSleft = trigger.getBoundingClientRect().left + 'px';
        }

        if (this.position == 'top-left') {
          POSbottom = window.innerHeight - trigger.getBoundingClientRect().top + 10 + 'px';
          POSleft = trigger.getBoundingClientRect().left + 'px';
        }

        if (this.position == 'bottom-right') {
          POStop = trigger.getBoundingClientRect().bottom + 10 + 'px';
          POSright = window.innerWidth - trigger.getBoundingClientRect().right + 'px';
        }

        if (this.position == 'top-right') {
          POSbottom = window.innerHeight - trigger.getBoundingClientRect().top + 10 + 'px';
          POSright = window.innerWidth - trigger.getBoundingClientRect().right + 'px';
        }

        if (this.position == 'right') {
          POStop = trigger.getBoundingClientRect().top + 'px';
          POSleft = trigger.getBoundingClientRect().right + 10 + 'px';
        }

        if (this.position == 'left') {
          POStop = trigger.getBoundingClientRect().top + 'px';
          POSright = window.innerWidth - trigger.getBoundingClientRect().left + 10 + 'px';
        }

        if (this.position == 'left-center') {
          POStop = trigger.getBoundingClientRect().top - drop.offsetHeight / 2 + 'px';
          POSright = window.innerWidth - trigger.getBoundingClientRect().left + 10 + 'px';
        }

        if (this.position == 'top') {
          POSbottom = window.innerHeight - trigger.getBoundingClientRect().top + 10 + 'px';
        }

        if (this.position.includes('top')) {
          this.animationClass = 'slide-up';
        }

        drop.style.bottom = POSbottom;
        drop.style.left = POSleft;
        drop.style.right = POSright;
        drop.style.top = POStop;

        if (this.screenHeight) {
          drop.style.top = this.screenHeight[0] + 'px';
          drop.style.bottom = 0;
          drop.style.left = trigger.getBoundingClientRect().right + 'px';
          drop.style.right = 'auto';
          drop.style.zIndex = '99999';
          this.animationClass = '';
        }
      },
      openOnHover() {
        if (this.hover) {
          let self = this;
          self.hovering = true;

          setTimeout(function () {
            if (!self.hovering) {
              return;
            }
            self.modelOpen = true;
            //this.setPosition();
            // You can also use Vue.$nextTick or setTimeout
            requestAnimationFrame(() => {
              document.documentElement.addEventListener('click', self.onClickOutside, true);
              if (self.onOpen) {
                self.onOpen();
              }
              if (!self.hover) {
                return;
              }
              self.setPosition();
            });
          }, 200);
        }
      },
      closeOnHover() {
        if (this.hover) {
          let self = this;
          self.hovering = false;
          self.modelOpen = false;
        }
      },
      containerClasses() {
        let classes = '';
        if (this.slotClass) {
          classes += this.slotClass;
        }

        if (!this.removeOverflow) {
          classes += ' uip-overflow-auto';
        }

        return classes;
      },
    },
    template: `
      <div class="uip-position-relative uip-overflow-visible" @mouseover="openOnHover()" @mouseleave="closeOnHover()" :class="containerClass">
      
	  	  <div class="uip-flex uip-w-100p">
		  		<div class="uip-cursor-pointer" :class="triggerClass" ref="droptrigger" @click="openThisComponent($event)"><slot name="trigger"></slot></div>
			    <slot name="post-trigger"></slot>
		    </div>
        
        <Transition :name="returnAnimationClass" @after-enter="setPosition">
        
		      <div v-if="modelOpen && $slots.content" ref="uipdrop" class="uip-z-index-9999 uip-modal-open" :class="isRelative ? 'uip-position-absolute ' + dropClass : 'uip-position-fixed ' + dropClass">
              
                    
            <div class="uip-position-relative uip-h-100p">
              <div v-if="hover" style="position: absolute; background: rgba(0,0,0,0); inset: -20px; z-index:0;" tabIndex="0"></div>
              <div class="uip-position-relative uip-shadow uip-dropdown-conatiner uip-background-default uip-border-rounder uip-border uip-margin-xl uip-z-index-1" :class="containerClasses()"
              :style="returnFullHeightStyle">
                <slot name="content"></slot>
              </div>
            </div>
                      
              
          </div>
          
        </Transition>
        
	    </div>`,
  };
}
