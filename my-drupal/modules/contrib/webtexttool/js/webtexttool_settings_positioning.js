/**
 * @file
 * Custom admin-side JS for the webtexttool module.
 */

(function($) {
  "use strict";
  Drupal.behaviors.webtexttoolSettingsPositioning = {
    attach: function(context, settings) {

      const elements = document.querySelectorAll('[data-javascript-positioning-selector]');

      elements.forEach((element) => {
        let key = element.getAttribute('data-javascript-positioning-selector');
        let placeholder = document.querySelector('[data-javascript-positioning-placeholder="' + key + '"]');

        if (placeholder) {
          placeholder.parentNode.insertBefore(element, placeholder.nextSibling);
          placeholder.remove();
        }
      });

    }
  };
})(jQuery);
