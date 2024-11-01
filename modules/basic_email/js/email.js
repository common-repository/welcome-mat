jQuery(document).ready(function($) {

  var ajaxObj = new window.maxFoundry.wm.maxAjax;

  ajaxObj.setFunction('spinner', function(target) {
    var spinner = '<div class="maxajax-load-spinner"></div>';
      $(target).next().html(spinner);
  });

  $(document).on('maxajax_success_remove-single-email', function (e, result, status)
  {
      result = JSON.parse(result);

        var id = result.item_deleted;

        $('.overview .item-' + id).fadeOut();
        console.log(result);

  });









});
