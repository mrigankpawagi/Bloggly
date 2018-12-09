(function ($, Drupal, jscolor) {

  Drupal.behaviors.jscolorPicker = {
    attach: function attach(context) {
      var $context = $(context);
      $context.find('[data-jscolor]').each(function () {
        // Immediately return if jscolor is already attached.
        if (this.jscolor) {
          return;
        }

        var $element = $(this);
        var options = {};
        try {
          options = (new Function ('return (' + $element.data('jscolor') + ')'))();
        }
        catch(e) {
          // Intentionally left empty.
        }

        this.jscolor = new jscolor(this, options);
        $('~ [data-jscolor-value]', this.parentNode).off('click.jscolor').on('click.jscolor', function (e) {
          var dataAttribute = $(this).data('jscolorValue');
          var value = $element.data(dataAttribute);
          if (!dataAttribute || !value || !$element[0].jscolor) {
            return;
          }
          $element[0].jscolor.fromString(value);
          $element.trigger('change');
          e.preventDefault();
          e.stopImmediatePropagation();
        });
      });
    },
    detach: function detach(context) {
      var $context = $(context);
      $context.find('[data-jscolor]').each(function () {
        delete this.jscolor;
        this.parentNode.replaceChild(this.cloneNode(true), this);
      });
    }
  };
})(jQuery, Drupal, jscolor);
