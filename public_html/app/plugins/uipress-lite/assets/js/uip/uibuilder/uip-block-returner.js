/**
 * Don't ask me why this is had to be created :(
 * @since 3.0.0
 */
import { h, Teleport } from './../../libs/vue-esm.js?ver=3.1.11';
//import { h } from '../../../js/libs/vue-esm.js';
export function moduleData() {
  return {
    inheritAttrs: false,
    props: {
      block: Object,
      OGblock: Object,
      key: String,
      returnData: Function,
    },
    //Watch for changes to the processed block
    watch: {
      block: {
        handler(newValue, oldValue) {
          console.log('nope');
          if (this.block.content) {
            //this.returnData(this.block.content);
          }
        },
        deep: true,
      },
    },
    // Create the render function
    render() {
      // Get the default slot content
      const defaultSlot = this.$slots.default;

      // If there is content in the default slot, render it
      if (defaultSlot) {
        return defaultSlot();
      }

      // Render nothing if there's no slot content
      return null;
    },
  };
}
