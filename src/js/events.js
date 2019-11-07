
"use strict";

// Setting some globals.
let limit = 20;         // The number of event entries to display per page.
let offset = 0;         // The index of the first entry.
let total = 0;          // The total number of events.
let currentPage = 0;    // The page we're on, for math purposes.

// RegExp pattern, for determining page number.
const pageIdPatt = /page\-(\d+)/;
// URL to use for AJAX requests.
const ajaxUrl = 'music-fc-ajax.php';

// Original values of elements when editing entries.
let eventDateStr, eventTimeStr, eventTitle;

// Runs on load.
$(document).ready(function() {

    // Sets reference fields properly.
    setInitialData();

    // Adds all the EventListeners.
    addClickListeners();
});


/**
 * Adds event listeners to the page, so we can handle all the things the user might
 * need to do.
 * 
 * @return {void}
 */
function addClickListeners() {

    // Setting the "Next" navigation button.
    $('#next-btn').on('click', function(e) {
        e.preventDefault();

        if(!$('.page-btn.active').is(':last-child')) {
            getEvents(offset + limit, limit, currentPage + 1);
        }
    })

    // Setting the "Previous" navigation button.
    $('#prev-btn').on('click', function(e) {
        e.preventDefault();

        if(!$('.page-btn.active').is(':first-child')) {
            getEvents( offset - limit, limit, currentPage - 1);
        }
    });

    // Setting the individual, numbered page buttons.
    $('.page-btn').on('click', function(e) {
        e.preventDefault();

        // Working out the new offset and limit and things, based on page number.
        let newPage = parseInt(pageIdPatt.exec($(this).attr('id'))[1]);
        let newOffset = limit * (newPage - 1);
        getEvents(newOffset, limit, newPage);
    });

    // Setting the "Edit" buttons for the various entries.
    $('.edit-btn').on('click', function(e) {
        e.preventDefault();

        // Change the action on the modal, since it serves dual purpose.
        $('#modal-action-type').data('action', 'edit');
        $('#new-event-modal-label').html('Edit Event');

        // Grab the information from the table, relative to the edit button that was clicked.
        $(this).parent().parent().siblings().each(function(index, elem) {

            // Date and time RegExp patterns, so we can tell which field we're dealing with.
            const datePatt = /\d{2}\/\d{2}\/\d{2}/;
            const timePatt = /\d{1,2}:\d{2}\s[ap]m/;

            // Check if it's the event date.
            if(datePatt.test($(elem).html())) {
                eventDateStr = $(elem).html();
            }
            // Check if the event time is "TBA," which corresponds to a DB time
            // of 12:00 am or 00:00:00.
            else if($(elem).html() == 'TBA' ) {
                eventTimeStr = '12:00 am';
            }
            // If it's not "TBA," grab the regular time.
            else if(timePatt.test($(elem).html())) {
                eventTimeStr = $(elem).html();
            }
            // Otherwise, it's the title of the event.
            else {
                eventTitle = $(elem).html();
            }
        });

        // Create a new Date object, for ease of math and formatting.
        let eventDate = new Date(`${eventDateStr} ${eventTimeStr}`);

        // Format date and time (functions defined below).
        eventDateStr = formatDate(eventDate);
        eventTimeStr = formatTime(eventDate);

        // Update the values in the modal.
        $('#new-event-date').val(eventDateStr);
        $('#new-event-time').val(eventTimeStr);
        $('#new-event-title').val(eventTitle);
        // Also give it the Event ID, so we can find the database entry.
        $('#edit-event-id').val($(this).parent().siblings('input').data('id'));
    });

    // Setting the "Delete" buttons for the various entries.
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();

        // Getting the Event ID and the Title of the event, so we can figure out
        // which table it's on.
        let delTarget = parseInt($(this).parent().siblings('input[type="hidden"]').data('id'));
        let delTitle = $(this).parents('td').siblings('td[colspan="3"]').html();

        // Decrement the total number of events, for the sake of accuracy.
        total--;

        // Delete the event.
        deleteEvent(delTarget, delTitle);
    });

    // Setting the "Add Event" button.
    $('#add-btn').on('click', function(e) {
        e.preventDefault();

        // Change the modal 'action' hidden input and the title of the dialog box.
        $('#modal-action-type').data('action', 'add');
        $('#new-event-modal-label').html('Add New Event');
    });

    // Setting the submit behavior for the modal form. We're hooking into the 'submit' event
    // here so that we can also capture the user hitting Enter/Return when they're done
    // filling out the form.
    $('#form-add-new-event').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // Grab all the values from the form and parse them into a generic Object.
        const rawData = $('#form-add-new-event').serializeArray();

        // Create an empty object to hold the parsed data.
        let data = {};

        // Loop through and give the data the appropriate keys, so that the AJAX Handler
        // will understand.
        rawData.forEach( item => {
            data[item.name] = item.value;
        });

        // Determine which function to use for the data.
        if($('#modal-action-type').data('action') == 'add') {
            // We're adding a new one, so we'll increment the total.
            total++;
            addEvent(data);
        }
        // Or we'll just edit an entry.
        else if($('#modal-action-type').data('action') == 'edit') {
            eventUpdate(data);
        }

        // Either way, we're done with the modal, so we'll hide it.
        $('#new-event-modal').modal('hide');
    });

    // Clears out the form fields once the modal has closed.
    $('#new-event-modal').on('hidden.bs.modal', function(e) {
        $('#form-add-new-event .form-group div input').each(function() {
            $(this).val('');
        });
    })
}


/**
 * Runs the AJAX to get the event list.
 * 
 * @param {int} newOffset   The new starting index for elements to display.
 * @param {int} resultLimit The result limit (should remain relatively constant)
 * @param {int} newPage     The new page number.
 * 
 * @return {void}
 */
function getEvents(newOffset, resultLimit, newPage) {

    // Build the data object we'll pass to the AJAX request.
    const postData = {
        action: 'get-event',
        offset: newOffset,
        limit: resultLimit
    };

    // The AJAX function is ansynchronous, so we'll handle it this way.
    doAjax(postData).then( response => updateHTML(response, newOffset, resultLimit, newPage));
}


/**
 * Updates the various HTML elements once something in the events list has changed.
 * 
 * @param {string} text     The raw HTML to update the target with.
 * @param {int} newOffset   The new starting index, for updating the page display.
 * @param {int} resultLimit The result limit, for mathing and updating the page display.
 * @param {int} newPage     The new page number, so we can set the CSS classes properly.
 * 
 * @return {void}
 */
function updateHTML(text, newOffset, resultLimit, newPage) {

    // Scroll back to the top of the Events table.
    $(window).scrollTop(120);

    // Update the body of the table with the new HTML
    $('#events-table tbody').html(text);

    // Switch the active page
    $('.page-btn').removeClass('active');
    $('#page-' + newPage).addClass('active');

    // Set the new offset and currentPage values.
    offset = newOffset;
    currentPage = newPage;

    // Change the "Viewing ## through ## of ### entries" label.
    $('#start-idx').html(newOffset + 1);
    $('#end-idx').html((newOffset + resultLimit < total ? newOffset + resultLimit : total));
    $('#event-count').html(total);

    // Re-add the click listeners for the new elements.
    addClickListeners();
}


/**
 * Sets the initial values based on the <input> values set by PHP when the page was generated.
 * 
 * @return {void}
 */
function setInitialData() {
    offset = parseInt($("#start-idx").html()) - 1;
    limit = parseInt($('#limit').val());
    total = parseInt($('#event-count').html());
    currentPage = parseInt(pageIdPatt.exec($('#page-buttons .page-btn.active').attr('id'))[1]);
}


/**
 * Function to delete an event.
 * 
 * @param {int} delTarget   The Event ID of the event we want to delete.
 * @param {string} delTitle The title of the event, for comparison with any existing entries we find.
 * 
 * @return {void}
 */
function deleteEvent(delTarget, delTitle) {
    
    // Building the AJAX data.
    const postData = {
        action: 'delete-event',
        target: delTarget,
        title: delTitle
    };

    // Doing the AJAX, then doing stuff based on the server response.
    doAjax(postData).then(response => {
        switch(parseInt(response)) {
            case 1:
                getEvents(offset, limit, currentPage);
                break;
            case 0:
                alert("There was an error deleting the events.");
                break;
            case -1:
                alert("Target ID not set.");
                break;
            default:
                console.log(response);
                break;
        }
    });
}


/**
 * Adds a new event, based on the user-provided data from the Add New Event modal form.
 * 
 * @param {Object} formData An anonymous object containing the data we'll pass with the AJAX request.
 * 
 * @return {void}
 */
function addEvent(formData) {

    // Set the 'action' property so that the AJAX Handler knows what to do with it.
    formData['action'] = 'create-event';

    // Do the AJAX and do stuff according to the response.
    doAjax(formData).then(response => {
        switch(parseInt(response)) {
            case 1:
                getEvents(offset, limit, currentPage);
                break;
            case 0:
                alert("There was an error adding the event.");
                break;
            default:
                break;
        }
    });
}


/**
 * Function to edit an existing event, based on user data from the Add New Event modal form.
 * 
 * @param {Object} formData An anonymous object containing the data we'll pass with the AJAX request.
 * 
 * @return {void}
 */
function eventUpdate(formData) {

    // Setting the action AND the old values.
    formData['action'] = 'edit-event';
    formData['old-date'] = eventDateStr;
    formData['old-time'] = eventTimeStr;
    formData['old-title'] = eventTitle;

    // Do the AJAX and do stuff according to the response.
    doAjax(formData).then(response => {
        switch(parseInt(response)) {
            case 1:
                getEvents(offset, limit, currentPage);
                break;
            case 0:
                alert("There was a problem updating the event.");
                break;
            default:
                break;
        }
    });
}


/**
 * The core AJAX function. Ansynchronous, because AJAX.
 * 
 * @param {Object} postData An anonymous object containing the data we'll pass with the AJAX request.
 * 
 * @return {various}
 */
async function doAjax(postData) {

    // Initializing the return variable.
    let response = '';

    // Try the AJAX
    try {
        response = await $.ajax({
            url: ajaxUrl,
            method: 'post',
            data: postData
        });
    }
    // Catch any errors and log them.
    catch(error) {
        console.error(error);
    }

    // If we've got a return value, send it back.
    return response;
}


/**
 * Takes a Date object and formats a date string that will be readable by MySQL.
 * 
 * @param {Date} date A Date object, for use when formatting the information to send to the DB.
 * 
 * @return {string}
 */
function formatDate(date) {

    console.log(date.getMonth());
    let dateStr = date.getFullYear() + '-';
    dateStr += date.getMonth() + 1 < 10 ? '0' + date.getMonth() : date.getMonth() + 1;
    dateStr += '-';
    dateStr += date.getDate() < 10 ? '0' + date.getDate() : date.getDate();

    return dateStr;
}


/**
 * Takes a Date object and formats a time string that will be readable by MySQL.
 * 
 * @param {Date} date A Date object, for use when formatting the information to send to the DB.
 * 
 * @return {string}
 */
function formatTime(date) {
    let timeStr = date.getHours() < 10 ? '0' + date.getHours() : date.getHours();
    timeStr += ':';
    timeStr += date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes();

    return timeStr;
}