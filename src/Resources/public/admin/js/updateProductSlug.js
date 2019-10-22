$(function () {
  let $body = $('body');

  $body.on('click', '.toggle-product-slug-modification', function () {
    let $parent = $(this).parent();
    let $input = $parent.find('input');

    let isDisabled = $input.attr('readonly') === 'readonly';
  });
});
