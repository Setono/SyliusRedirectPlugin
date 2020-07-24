function handleAutomaticRedirectionInput($input) {
  let slugHasChanged = $input.attr('value') !== $input.val();

  if (slugHasChanged) {
    showAutomaticRedirectionInput();
  } else {
    hideAutomaticRedirectionInput();
  }
}

function hideAutomaticRedirectionInput() {
  $('.js-add-automatic-redirection-checkbox').parent().css({'display': 'none'});
}

function showAutomaticRedirectionInput() {
  $('.js-add-automatic-redirection-checkbox').parent().css({'display': 'initial'});
}
