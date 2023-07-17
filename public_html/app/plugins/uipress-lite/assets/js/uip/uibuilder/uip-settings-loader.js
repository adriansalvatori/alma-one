/**
 * Builds the main ui builder shell
 * @since 3.0.0
 */
const { __, _x, _n, _nx } = wp.i18n;
export function moduleData() {
  return {
    data: function () {
      return {};
    },
    template: `
      <div class="uip-position-fixed uip-padding-s uip-right-0 uip-z-index-9 uip-w-400" style="height: calc(100vh - 80px);">
      
        <div class="uip-h-100p uip-w-100p uip-max-h-100p uip-max-w-100p uip-flex-grow uip-flex uip-flex-column uip-border-rounder uip-shadow uip-background-default">
            <uip-block-settings></uip-block-settings>
        </div>
      
      </div>
      
      `,
  };
}
