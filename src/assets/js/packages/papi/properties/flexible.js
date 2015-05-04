import $ from 'jquery';
import Repeater from 'papi/properties/repeater';

class Flexible extends Repeater {

  /**
   * The template to use.
   *
   * @var {function}
   */

  get template() {
    return window.wp.template('papi-property-flexible-row');
  }

  /**
   * Initialize Property Flexible.
   */

  static init() {
    new Flexible().binds();
  }

  /**
   * Add a new row to the flexible repeater.
   *
   * @param {object} $tbody
   * @param {int} counter
   * @param {array} items
   */

  addRow($tbody, counter, res) {
    let heads = [];
    let columns = [];

    for (let i = 0, l = res.html.length; i < l; i++) {
      let layoutSlug = this.properties[i].slug.substring(0, this.properties[i].slug.length - 1) + '_layout]';
      if (i === l - 1) {
        heads.push('<td class="flexible-td-last">');
        columns.push('<td class="flexible-td-last">');
      } else {
        heads.push('<td>');
        columns.push('<td>');
      }
      columns.push('<input type="hidden" name="' +  layoutSlug + '" value="' + this.currentLayout + '" />');
      columns.push(res.html[i] + '</td>');
      heads.push(this.properties[i].title + '</td>');
    }

    let $row = this.getHtml({
      heads: heads.join(''),
      columns: columns.join(''),
      counter: counter
    });

    $row.appendTo($tbody);

    // Trigger the property that we just added
    $row
      .find('[name*="_property"]')
      .trigger('papi/property/repeater/added');

    this.scrollDownTable($tbody);
    this.updateDatabaseRowNumber($tbody);
  }

  /**
   * Bind elements with functions.
   */

  binds() {
    const self = this;

    $('.repeater-tbody').sortable({
      revert: true,
      handle: '.handle',
      helper: function (e, ui) {
        ui.children().each(function() {
          $(this).width($(this).width());
        });
        return ui;
      },
      stop: function () {
        self.updateRowNumber($(this).closest('.repeater-tbody'));
      }
    });

    $(document).on('click', '.papi-property-flexible .bottom a.button', function (e) {
      e.preventDefault();
      $(this).prev().removeClass('papi-hide');
    });

    $(document).on('click', '.papi-property-flexible .flexible-layouts li', function (e) {
      e.preventDefault();
      $(this).closest('.flexible-layouts').addClass('papi-hide');
      self.add($(this));
    });

    $(document).on('mouseup', 'body', function (e) {
      const $layouts = $('.flexible-layouts:not(.papi-hide)');
      if (!$layouts.is(e.target) && $layouts.has(e.target).length === 0) {
        $layouts.addClass('papi-hide');
      }
    });

    $(document).on('click', '.papi-property-flexible .repeater-remove-item', function (e) {
      e.preventDefault();
      self.remove($(this));
    });
  }

  /**
   * Prepare properties.
   *
   * @param {array} properties
   * @param {int} counter
   */

  prepareProperties(jsonText, counter) {
    const properties   = $.parseJSON(jsonText);
    this.currentLayout = properties.layout;
    this.properties    = super.prepareProperties(properties.properties, counter);
    return this.properties;
  }

  /**
   * Remove item from the flexible repeater.
   *
   * @param {object} e
   */

  remove($this) {
    const $tbody = $this.closest('.papi-property-flexible').find('.repeater-tbody');

    $this.closest('tr').remove();

    this.updateRowNumber($tbody);
  }

  /**
   * Update database row number.
   *
   * @param {object} $el
   */

  updateDatabaseRowNumber($tbody) {
    $tbody
      .closest('.papi-property-repeater-top')
      .find('.papi-property-repeater-rows')
      .val($tbody.find('tr tbody tr').length);
  }

}

export default Flexible;