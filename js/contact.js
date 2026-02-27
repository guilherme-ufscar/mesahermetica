/* ==============================================
   MESA HERMÉTICA — CONTACT.JS
   Validação e envio do formulário de contato
   ============================================== */
(function () {
  'use strict';

  // ─────────────────────────────────────────────
  // DOM Cache
  // ─────────────────────────────────────────────
  var form = document.getElementById('contact-form');
  if (!form) return;

  var fields = {
    name: document.getElementById('name'),
    email: document.getElementById('email'),
    phone: document.getElementById('phone'),
    subject: document.getElementById('subject'),
    message: document.getElementById('message'),
    website: document.getElementById('website') // honeypot
  };

  var errors = {
    name: document.getElementById('name-error'),
    email: document.getElementById('email-error'),
    subject: document.getElementById('subject-error'),
    message: document.getElementById('message-error')
  };

  var charCount = document.getElementById('char-count');
  var submitBtn = document.getElementById('submit-btn');
  var submitText = document.getElementById('submit-text');
  var submitLoading = document.getElementById('submit-loading');
  var successMsg = document.getElementById('form-success');
  var errorMsg = document.getElementById('form-error');

  // ─────────────────────────────────────────────
  // Validation rules
  // ─────────────────────────────────────────────
  var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function validateField(name) {
    var value = fields[name].value.trim();
    var errorEl = errors[name];
    var input = fields[name];
    var message = '';

    switch (name) {
      case 'name':
        if (!value) {
          message = 'Por favor, informe seu nome.';
        } else if (value.length < 3) {
          message = 'O nome deve ter pelo menos 3 caracteres.';
        }
        break;

      case 'email':
        if (!value) {
          message = 'Por favor, informe seu e-mail.';
        } else if (!emailRegex.test(value)) {
          message = 'Informe um e-mail válido.';
        }
        break;

      case 'subject':
        if (!value) {
          message = 'Selecione um assunto.';
        }
        break;

      case 'message':
        if (!value) {
          message = 'Escreva sua mensagem.';
        } else if (value.length < 10) {
          message = 'A mensagem deve ter pelo menos 10 caracteres.';
        }
        break;
    }

    if (errorEl) {
      errorEl.textContent = message;
    }

    if (message) {
      input.classList.add('is-invalid');
      input.classList.remove('is-valid');
      return false;
    } else {
      input.classList.remove('is-invalid');
      if (value) input.classList.add('is-valid');
      return true;
    }
  }

  function validateAll() {
    var isValid = true;
    var requiredFields = ['name', 'email', 'subject', 'message'];

    requiredFields.forEach(function (name) {
      if (!validateField(name)) {
        isValid = false;
      }
    });

    return isValid;
  }

  // ─────────────────────────────────────────────
  // Character counter
  // ─────────────────────────────────────────────
  if (fields.message && charCount) {
    fields.message.addEventListener('input', function () {
      charCount.textContent = this.value.length;
    });
  }

  // ─────────────────────────────────────────────
  // Real-time validation on blur
  // ─────────────────────────────────────────────
  ['name', 'email', 'subject', 'message'].forEach(function (name) {
    if (fields[name]) {
      fields[name].addEventListener('blur', function () {
        validateField(name);
      });

      // Clear error on input
      fields[name].addEventListener('input', function () {
        if (this.classList.contains('is-invalid')) {
          validateField(name);
        }
      });
    }
  });

  // ─────────────────────────────────────────────
  // Phone mask (simple BR format)
  // ─────────────────────────────────────────────
  if (fields.phone) {
    fields.phone.addEventListener('input', function () {
      var val = this.value.replace(/\D/g, '');
      if (val.length > 11) val = val.substring(0, 11);

      if (val.length > 6) {
        this.value = '(' + val.substring(0, 2) + ') ' + val.substring(2, 7) + '-' + val.substring(7);
      } else if (val.length > 2) {
        this.value = '(' + val.substring(0, 2) + ') ' + val.substring(2);
      } else if (val.length > 0) {
        this.value = '(' + val;
      }
    });
  }

  // ─────────────────────────────────────────────
  // Form submit
  // ─────────────────────────────────────────────
  form.addEventListener('submit', function (e) {
    e.preventDefault();

    // Honeypot check
    if (fields.website && fields.website.value) {
      return;
    }

    // Validate
    if (!validateAll()) {
      // Focus first invalid field
      var firstInvalid = form.querySelector('.is-invalid');
      if (firstInvalid) firstInvalid.focus();
      return;
    }

    // Disable & show loading
    submitBtn.disabled = true;
    submitText.style.display = 'none';
    submitLoading.style.display = 'inline-flex';
    successMsg.style.display = 'none';
    errorMsg.style.display = 'none';

    // Build form data
    var formData = new FormData(form);

    // Send via fetch
    fetch(form.action, {
      method: 'POST',
      body: formData
    })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success) {
        successMsg.style.display = 'flex';
        form.reset();
        charCount.textContent = '0';

        // Remove valid classes
        Object.keys(fields).forEach(function (key) {
          if (fields[key]) {
            fields[key].classList.remove('is-valid');
          }
        });
      } else {
        errorMsg.style.display = 'flex';
        if (data.errors) {
          // Show server-side errors
          Object.keys(data.errors).forEach(function (key) {
            if (errors[key]) {
              errors[key].textContent = data.errors[key];
              fields[key].classList.add('is-invalid');
            }
          });
        }
      }
    })
    .catch(function () {
      errorMsg.style.display = 'flex';
    })
    .finally(function () {
      submitBtn.disabled = false;
      submitText.style.display = 'inline';
      submitLoading.style.display = 'none';
    });
  });
})();
