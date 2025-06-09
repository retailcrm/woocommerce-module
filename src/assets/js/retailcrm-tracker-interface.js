jQuery(document).ready(function () {
    const assistantCode = jQuery('#woocommerce_integration-retailcrm_online_assistant');
    const textarea = jQuery('#woocommerce_integration-retailcrm_tracker_settings');
    const trackerCheckboxId = 'retailcrm_activation_tracker';
    const eventsContainerId = 'retailcrm_events_container';

    let trackerContainer = jQuery('<div id="retailcrm_tracker_container" style="margin-top:10px;"></div>');
    let eventsContainer = jQuery('<div id="' + eventsContainerId + '" style="margin-top:10px;"></div>');

    assistantCode.after(trackerContainer);
    trackerContainer.after(eventsContainer);

    function getSavedData() {
        try {
            const text = textarea.val().trim();
            return text ? JSON.parse(text) : { tracker_enabled: false, tracked_events: [] };
        } catch (exception) {
            return { tracker_enabled: false, tracked_events: [] };
        }
    }

    function renderMainCheckbox() {
        if (jQuery('#' + trackerCheckboxId).length === 0) {
            const savedData = getSavedData();
            const trackerCheckbox = `
                <label>
                    <input type="checkbox" id="${trackerCheckboxId}" ${savedData.tracker_enabled ? 'checked' : ''}> 
                    ${retailcrm_localized.tracker_activity}
                </label>
            `;
            
            trackerContainer.html(trackerCheckbox);
        }
    }

    function renderEventCheckboxes() {
        const savedData = getSavedData();
        const events = [
            {value: 'page_view', label: retailcrm_localized.page_view, title: retailcrm_localized.page_view_desc},
            {value: 'cart', label: retailcrm_localized.cart, title: retailcrm_localized.cart_desc},
            {value: 'open_cart', label: retailcrm_localized.open_cart, title: retailcrm_localized.open_cart_desc}
        ];

        let checkboxes = '';
        events.forEach(event => {
            const isChecked = savedData.tracked_events.includes(event.value);
            checkboxes += `
                <label>
                    <input type="checkbox" class="retailcrm-event" value="${event.value}" 
                           title="${event.title}" ${isChecked ? 'checked' : ''}>
                    ${event.label}
                </label><br/>
            `;
        });
        
        eventsContainer.html(checkboxes);
    }

    function updateTextarea() {
        const isTrackerEnabled = jQuery('#' + trackerCheckboxId).is(':checked');
        const selectedEvents = jQuery('.retailcrm-event:checked').map(function() {
            return this.value;
        }).get();
        
        const data = {
            tracker_enabled: isTrackerEnabled,
            tracked_events: selectedEvents
        };
        
        textarea.val(JSON.stringify(data));
    }

    function clearAll() {
        trackerContainer.empty();
        eventsContainer.empty();
        textarea.val('');
    }

    function updateDisplay() {
        const value = assistantCode.val().trim();

        if (value === '') {
            clearAll();
        } else {
            renderMainCheckbox();
            renderEventCheckboxes();
            
            if (!jQuery('#' + trackerCheckboxId).is(':checked')) {
                eventsContainer.hide();
            } else {
                eventsContainer.show();
            }
        }
    }

    assistantCode.on('input', updateDisplay);
    trackerContainer.on('change', '#' + trackerCheckboxId, function() {
        if (jQuery(this).is(':checked')) {
            eventsContainer.show();
        } else {
            eventsContainer.hide();
        }
        updateTextarea();
    });
    
    eventsContainer.on('change', '.retailcrm-event', updateTextarea);

    updateDisplay();
});