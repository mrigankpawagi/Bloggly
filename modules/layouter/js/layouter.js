'use strict';
(function($, Drupal, drupalSettings) {
  Drupal.behaviors.layouter = {
    attach: function (context, settings) {
      if (settings.layouter != undefined) {
        var active_text_formats = settings.layouter.window.active_text_formats;
        var textareas_id = settings.layouter.window.textareas_id;
        for (var textarea in textareas_id) {
          var format_selector_id = '#' + textarea.replace(/value/, 'format--2');
          var format_selector = $(format_selector_id);
          var layouter_link = $('#layouter-' + textarea);
          var selected_format = $(format_selector_id + ' option:selected').val();

          if (active_text_formats.indexOf(selected_format) == -1) {
            layouter_link.parent().hide();
          }

          format_selector.change(function () {
            var format_selector_id = $(this).attr('id');
            var textarea_id = format_selector_id.replace(/format--2/, '') + 'value';
            var layouter_link = $('#layouter-' + textarea_id);
            var selected_format = $('#' + format_selector_id + ' option:selected').val();

            if (active_text_formats.indexOf(selected_format) == -1) {
              layouter_link.parent().hide();
            }
            else {
              layouter_link.parent().show();
            }
          });
        }
      }

      $.fn.layouterAddContent = function (textarea_id, content) {
        if (content) {
          if (CKEDITOR != undefined && CKEDITOR.instances[textarea_id] != undefined) {
            CKEDITOR.instances[textarea_id].insertHtml(content);
          }
          var area = $('#' + textarea_id);
          content = area.val() + content;
          area.val(content);
        }
      };

    }
  };

})(jQuery, Drupal, drupalSettings);
