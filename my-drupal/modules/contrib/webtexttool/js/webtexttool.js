/**
 * @file
 * Custom admin-side JS for the webtexttool module.
 */

(function($) {
  "use strict";
  Drupal.behaviors.webtexttooladmin = {
    attach: function(context, settings) {
      var wait;

      //toggle
      document
        .querySelectorAll(".wtt-label:not([data-toggleable])")
        .forEach(function(label) {
          label.dataset.toggleable = true;

          let toggle = function() {
            label.parentNode.classList.toggle("active");
          };

          label.addEventListener("click", toggle);
        });

      // // highlight textarea queries:
      // let highlightTextareas = function(activator) {
      //   let highlightedTextQuery = activator.childNodes[0].textContent.trim();
      //
      //   $("textarea").each(function(item) {
      //     let el = $(this);
      //     let isWebTextTool = el.closest("#webtexttool-analyse");
      //     if (isWebTextTool.length === 0) {
      //       let style = window.getComputedStyle(el[0]);
      //
      //       let padding = {
      //         top: style.getPropertyValue("padding-top"),
      //         left: style.getPropertyValue("padding-left"),
      //         right: style.getPropertyValue("padding-right"),
      //         bottom: style.getPropertyValue("padding-bottom")
      //       };
      //
      //       let lineHeight = style.getPropertyValue("line-height");
      //
      //       el.highlightWithinTextarea({
      //         highlight: highlightedTextQuery
      //       });
      //
      //       el.closest(".hwt-container")[0].style.setProperty(
      //         "--padding",
      //         `${padding.top} ${padding.right} ${padding.bottom} ${padding.left}`
      //       );
      //       el.closest(".hwt-container")[0].style.setProperty(
      //         "--line-height",
      //         `${lineHeight}`
      //       );
      //     }
      //   });
      // };

      // add event handler once for each info-item:
      document
        .querySelectorAll(
          "#webtexttool-analyse .info-item:not([data-clickable])"
        )
        .forEach(function(item) {
          item.dataset.clickable = true;

          item.addEventListener("click", function() {
            highlightTextareas(item);
          });
        });

      // Only executing on pageload
      if (context === document) {
        if ($("#webtexttool-tabs-wrapper").length) {
          $("#webtexttool-tabs-wrapper")
            .once()
            .tabs();
        }
        // Start analysing.
        $("#edit-analyse-seo").triggerHandler("click");
        $(".node-form").keypress(function() {
          clearTimeout(wait);
          wait = setTimeout(function() {
            $("#edit-analyse-seo").triggerHandler("click");
          }, 1000);
        });

        if (typeof CKEDITOR !== "undefined") {
          CKEDITOR.on("instanceReady", function(ev) {
            var editor = ev.editor;
            // Check if there is a change on the editor.
            editor.on("change", function() {
              clearTimeout(wait);
              wait = setTimeout(function() {
                $("#edit-analyse-seo").triggerHandler("click");
              }, 1000);
            });
          });
        }
      }
    }
  };
})(jQuery);
