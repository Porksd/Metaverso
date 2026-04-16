jQuery(document).ready(function ($) {
  $('#eb-pro-update-modal-overlay, #eb-pro-update-modal').show();

  // Close modal on overlay, close button, or "Continue with old design"
  $(document).on(
    'click',
    '.eb-modal__close, #eb-modal-enroll-old',
    function () {
      $('.eb-modal-overlay, .eb-modal--enroll-update').fadeOut(200);

      $.post(EBProUpdateModal.ajax_url, {
        action: 'eb_dismiss_update_modal',
        nonce: EBProUpdateModal.nonce,
      });
    }
  );

  // Handle "Switch to new design" button
  $(document).on('click', '#eb-modal-enroll-new', function () {
    // Dismiss the transient
    $.post(EBProUpdateModal.ajax_url, {
      action: 'eb_update_enroll_students_page',
      nonce: EBProUpdateModal.nonce,
    }).done(function () {
      $('.eb-modal-initial-body').hide();
      $('.eb-modal-success-body').show();
    });
  });

  // Close modal when clicking View settings button
  $(document).on('click', '.eb-modal-success-body .eb-modal__btn', function () {
    $('.eb-modal-overlay, .eb-modal--enroll-update').fadeOut(200);
  });

  // Prevent modal click from closing when clicking inside modal
  $(document).on('click', '.eb-modal', function (e) {
    e.stopPropagation();
  });
});
