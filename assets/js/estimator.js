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
        showMessage("This route isn't available for instant estimates yet. Please contact us for a custom quote.", false);
      }
    }

    pickup.addEventListener('change', evaluate);
    dropoff.addEventListener('change', evaluate);

    // Step navigation
    var step1 = qs('vte-step-1');
    var step2 = qs('vte-step-2');

    // Store route data for submission
    var routeData = {
      pickup: '',
      dropoff: '',
      price: '',
      distance: '',
      transit: ''
    };

    // Next step action: show user info form or redirect
    nextBtn.addEventListener('click', function(){
      // Check if we have a valid estimate (not an error message)
      var estimateVisible = estimateWrap && !estimateWrap.hidden;

      if (estimateVisible) {
        // Store route data
        routeData.pickup = pickup.value;
        routeData.dropoff = dropoff.value;
        routeData.price = priceEl.textContent;
        routeData.distance = distEl.textContent;
        routeData.transit = transitEl.textContent;

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

        // Disable submit button during submission
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'vte_submit_form');
        formData.append('nonce', vteData.nonce);
        formData.append('fullname', fullname);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('pickup', routeData.pickup);
        formData.append('dropoff', routeData.dropoff);
        formData.append('price', routeData.price);
        formData.append('distance', routeData.distance);
        formData.append('transit', routeData.transit);

        // Send AJAX request
        fetch(vteData.ajaxUrl, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(function(response) {
          return response.json();
        })
        .then(function(data) {
          if (data.success) {
            // Success - redirect to next step
            if (data.data.redirect) {
              window.location.href = data.data.redirect;
            } else {
              alert(data.data.message);
              submitBtn.disabled = false;
              submitBtn.innerHTML = '<svg class="vte-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>Next Step';
            }
          } else {
            // Error
            alert(data.data.message || 'An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg class="vte-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>Next Step';
          }
        })
        .catch(function(error) {
          console.error('Error:', error);
          alert('An error occurred. Please try again.');
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<svg class="vte-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>Next Step';
        });
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
