document.addEventListener('DOMContentLoaded', function () {
  // Confirmation dialogs are used only on forms that explicitly request them.
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  // Use browser validation and focus the first invalid field.
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
      }
    });
  });

  // Quantity fields should never accept zero, negative or fractional values.
  document.querySelectorAll('input[type="number"][name="quantity"]').forEach(function (field) {
    field.min = field.min || '1';
    field.step = field.step || '1';
    field.addEventListener('change', function () {
      var value = parseInt(field.value, 10);
      var minimum = parseInt(field.min, 10) || 1;
      var maximum = field.max ? parseInt(field.max, 10) : Number.MAX_SAFE_INTEGER;
      if (!Number.isFinite(value)) value = minimum;
      field.value = Math.min(Math.max(value, minimum), maximum);
    });
  });

  // Avoid accidental double-submission on action forms.
  document.querySelectorAll('form[data-single-submit]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var submitButton = form.querySelector('button[type="submit"], button:not([type])');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Please wait…';
      }
    });
  });
});
