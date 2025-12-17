(function () {
  'use strict';

  function qs(id) { return document.getElementById(id); }

  function createOption(value) {
    var opt = document.createElement('option');
    opt.value = value;
    opt.textContent = value;
    return opt;
  }

  function init() {
    if ( typeof vteData === 'undefined' ) return;

    var pickup = qs('vte-pickup');
    var dropoff = qs('vte-dropoff');
    var msgBox = qs('vte-message');
    var msgText = qs('vte-message-text');
    var estimateWrap = qs('vte-estimate');
    var priceEl = qs('vte-price');
    var distEl = qs('vte-distance');
    var transitEl = qs('vte-transit');
    var callBtn = qs('vte-call');
    var nextBtn = qs('vte-next');

    // Initialize intl-tel-input
    var phoneInput = qs('vte-phone');
    var iti;
    if (phoneInput && typeof window.intlTelInput !== 'undefined') {
      iti = window.intlTelInput(phoneInput, {
        initialCountry: 'us',
        preferredCountries: ['us', 'ca', 'mx'],
        separateDialCode: true,
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/utils.js'
      });
    }

    // populate selects
    vteData.states.forEach(function(s){
      pickup.appendChild(createOption(s));
      dropoff.appendChild(createOption(s));
    });

    function clearMessage() {
      msgBox.hidden = true;
      if (msgText) {
        msgText.innerHTML = '';
      }
    }

    function showMessage(text, hasCallLink) {
      if (hasCallLink) {
        // Format message with 'Call Now!' as a clickable link
        var parts = text.split("'Call Now!'");
        if (parts.length === 2) {
          msgText.innerHTML = parts[0] + '<span class="vte-call-link">\'Call Now!\'</span>' + parts[1];
        } else {
          msgText.textContent = text;
        }
      } else {
        msgText.textContent = text;
      }
      msgBox.hidden = false;
      estimateWrap.hidden = true;
    }

    function showEstimate(est) {
      priceEl.textContent = est.price;
      distEl.textContent = est.distance;
      transitEl.textContent = est.transit;
      estimateWrap.hidden = false;
      msgBox.hidden = true;
    }

    function normalizeKey(a,b){ return a + '|' + b; }

    function updateDisabledOptions() {
      var p = pickup.value;
      var d = dropoff.value;
      // enable all
      Array.prototype.forEach.call(dropoff.options, function(opt){ opt.disabled = false; });
      Array.prototype.forEach.call(pickup.options, function(opt){ opt.disabled = false; });
      if (p) {
        Array.prototype.forEach.call(dropoff.options, function(opt){ if (opt.value === p) opt.disabled = true; });
      }
      if (d) {
        Array.prototype.forEach.call(pickup.options, function(opt){ if (opt.value === d) opt.disabled = true; });
      }
    }

    function evaluate() {
      var p = pickup.value;
      var d = dropoff.value;
      updateDisabledOptions();

      if (!p || !d) {
        estimateWrap.hidden = true;
        clearMessage();
        return;
      }
      if (p === d) {
        showMessage("Pick-up and drop-off can't be the same.", false);
        return;
      }
      var key = normalizeKey(p,d);
      var est = vteData.estimates && vteData.estimates[key];
      if (est) {
        showEstimate(est);
      } else {
        showMessage("This route isn't available for instant estimates yet.\nClick 'Call Now!' for a custom quote.", true);
      }
    }

    pickup.addEventListener('change', evaluate);
    dropoff.addEventListener('change', evaluate);

    // Set call link
    if (vteData.phone) {
      var tel = vteData.phone.replace(/\s+/g,'');
      callBtn.setAttribute('href', 'tel:' + tel);
    }

    // Make 'Call Now!' text clickable in message
    if (msgText) {
      msgText.addEventListener('click', function(e) {
        if (e.target.classList.contains('vte-call-link')) {
          callBtn.click();
        }
      });
    }

    // Step navigation
    var step1 = qs('vte-step-1');
    var step2 = qs('vte-step-2');

    // Next step action: show user info form or redirect
    nextBtn.addEventListener('click', function(){
      // Check if we have a valid estimate (not an error message)
      var estimateVisible = estimateWrap && !estimateWrap.hidden;

      if (estimateVisible) {
        // Show step 2 (user info form)
        step1.hidden = true;
        step2.hidden = false;
      } else if (vteData.nextStepUrl) {
        // Fallback to redirect
        window.location.href = vteData.nextStepUrl;
      }
    });

    // Form submission
    var submitBtn = qs('vte-submit');
    if (submitBtn) {
      submitBtn.addEventListener('click', function(e) {
        e.preventDefault();

        var fullname = qs('vte-fullname').value;
        var phone = phoneInput ? phoneInput.value : '';
        var email = qs('vte-email').value;

        // Get full phone number with country code if intl-tel-input is initialized
        if (iti) {
          phone = iti.getNumber();
        }

        // Basic validation
        if (!fullname || !phone || !email) {
          alert('Please fill in all fields');
          return;
        }

        // Validate phone number if intl-tel-input is available
        if (iti && !iti.isValidNumber()) {
          alert('Please enter a valid phone number');
          return;
        }

        // Here you can add your form submission logic
        console.log('Form submitted:', {
          fullname: fullname,
          phone: phone,
          email: email
        });

        // Redirect to next step URL if configured
        if (vteData.nextStepUrl) {
          window.location.href = vteData.nextStepUrl;
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
