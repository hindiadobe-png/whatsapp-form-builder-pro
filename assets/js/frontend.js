(function(){
    // Initialize one form instance
    window.wafbp_init_form = function(formId, options) {
        try {
            var wafbpForm = document.getElementById('wafbpForm-' + formId);
            if (!wafbpForm) return;

            var whatsappNumber = options.wa_phone || '';
            // simple client-side validation: digits only
            var validNumber = /^\d+$/;
            if (!validNumber.test(whatsappNumber)) {
                // keep console warning but don't block display
                console.warn('WAFBP: invalid WhatsApp number configured for form ' + formId);
            }

            wafbpForm.addEventListener('submit', function(e){
                e.preventDefault();

                var nameField = this.querySelector('input[name="wafbp_name"]');
                var phoneField = this.querySelector('input[name="wafbp_phone"]');
                var cityField = this.querySelector('input[name="wafbp_city"]');
                var subjectField = this.querySelector('input[name="wafbp_subject"]');
                var messageField = this.querySelector('textarea[name="wafbp_message"]');
                var optInField = this.querySelector('input[name="wafbp_opt_in"]');

                var name = nameField ? nameField.value.trim() : '';
                var phone = phoneField ? phoneField.value.trim() : '';
                var city = cityField ? cityField.value.trim() : '';
                var subject = subjectField ? subjectField.value.trim() : '';
                var message = messageField ? messageField.value.trim() : '';
                var pageUrl = window.location.href;

                // Check if save_leads is enabled and opt-in is checked
                if (typeof wafbpAjax !== 'undefined' && wafbpAjax.save_leads && optInField && optInField.checked) {
                    // Send AJAX request to save lead
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', wafbpAjax.ajax_url, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    
                    var params = 'action=wafbp_save_lead';
                    params += '&nonce=' + encodeURIComponent(wafbpAjax.nonce);
                    params += '&form_id=' + encodeURIComponent(formId);
                    params += '&name=' + encodeURIComponent(name);
                    params += '&phone=' + encodeURIComponent(phone);
                    params += '&city=' + encodeURIComponent(city);
                    params += '&subject=' + encodeURIComponent(subject);
                    params += '&message=' + encodeURIComponent(message);
                    params += '&page_url=' + encodeURIComponent(pageUrl);
                    params += '&opt_in=1';
                    
                    xhr.send(params);
                    
                    // Note: We don't wait for the response, as we want to proceed with WhatsApp
                }

                var msgParts = [];

                if ( options.fields.name.show && name ) msgParts.push('Name: ' + name);
                if ( options.fields.phone.show && phone ) msgParts.push('Phone: ' + phone);
                if ( options.fields.city.show && city ) msgParts.push('City: ' + city);
                if ( options.fields.subject.show && subject ) msgParts.push('Subject: ' + subject);
                if ( options.fields.message.show && message ) msgParts.push('Message:\n' + message);

                msgParts.push('Page: ' + pageUrl);
                var fullMessage = msgParts.join('\n');

                if (!whatsappNumber || !validNumber.test(whatsappNumber)) {
                    alert(options.invalid_number_msg || 'WhatsApp number is not configured or invalid. Please contact the website administrator.');
                    return;
                }

                var link = 'https://wa.me/' + encodeURIComponent(whatsappNumber) + '?text=' + encodeURIComponent(fullMessage);
                window.open(link, '_blank');
            });
        } catch (err) {
            console.error('wafbp init error', err);
        }
    };
})();