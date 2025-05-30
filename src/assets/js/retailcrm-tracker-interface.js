jQuery(document).ready(function ($) {
    const $input = $('#woocommerce_integration-retailcrm_online_assistant');
    const $textarea = $('#woocommerce_integration-retailcrm_tracker_settings');
    const trackerCheckboxId = 'retailcrm_activation_tracker';
    const eventsContainerId = 'retailcrm_events_container';

    let $trackerContainer = $('<div id="retailcrm_tracker_container" style="margin-top:10px;"></div>');
    let $eventsContainer = $('<div id="' + eventsContainerId + '" style="margin-top:10px;"></div>');

    $input.after($trackerContainer);
    $trackerContainer.after($eventsContainer);

    function getSavedData() {
        try {
            const text = $textarea.val().trim();
            return text ? JSON.parse(text) : { tracker_enabled: false, tracked_events: [] };
        } catch (e) {
            return { tracker_enabled: false, tracked_events: [] };
        }
    }

    function renderMainCheckbox() {
        if ($('#' + trackerCheckboxId).length === 0) {
            const savedData = getSavedData();
            const trackerCheckbox = `
                <label>
                    <input type="checkbox" id="${trackerCheckboxId}" ${savedData.tracker_enabled ? 'checked' : ''}> 
                    Активировать передачу событий
                </label>
            `;
            $trackerContainer.html(trackerCheckbox);
        }
    }

    function renderEventCheckboxes() {
        const savedData = getSavedData();
        const events = [
            {value: 'page_view', label: 'Page View', title: 'Трекает просмотр страниц пользователем'},
            {value: 'cart', label: 'Cart', title: 'Трекает изменения в корзине (добавление/удаление товара)'},
            {value: 'open_cart', label: 'Open Cart', title: 'Трекает момент, когда пользователь открыл корзину'}
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
        
        $eventsContainer.html(checkboxes);
        updateTextarea();
    }

    function updateTextarea() {
        const isTrackerEnabled = $('#' + trackerCheckboxId).is(':checked');
        const selectedEvents = $('.retailcrm-event:checked').map(function() {
            return this.value;
        }).get();
        
        const data = {
            tracker_enabled: isTrackerEnabled,
            tracked_events: selectedEvents
        };
        
        $textarea.val(JSON.stringify(data));
    }

    function clearAll() {
        $trackerContainer.empty();
        $eventsContainer.empty();
        $textarea.val('');
    }

    function updateUI() {
        const val = $input.val().trim();
        if (val === '') {
            clearAll();
        } else {
            renderMainCheckbox();
            if ($('#' + trackerCheckboxId).is(':checked')) {
                renderEventCheckboxes();
            } else {
                $eventsContainer.empty();
            }
        }
    }

    $input.on('input', updateUI);
    $trackerContainer.on('change', '#' + trackerCheckboxId, function() {
        if ($(this).is(':checked')) {
            renderEventCheckboxes();
        } else {
            $eventsContainer.empty();
        }
        updateTextarea();
    });
    
    $eventsContainer.on('change', '.retailcrm-event', updateTextarea);

    updateUI();
});