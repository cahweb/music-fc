"use strict";

// Setting the URL for AJAX requests.
const ajaxURL = 'music-fc-ajax.php';

// Fires once the page loads.
$(document).ready(function() {
    addClickListeners();
    setupInputs();
});


/**
 * Adds event handlers to all the relevent DOM elements.
 * 
 * @return {void}
 */
function addClickListeners() {

    // Specifically add the listeners for Edit and Delete buttons, since we'll be doing that
    // every time the table updates.
    addEditDeleteListeners();

    // Handles the 'submit' event for the Student Entry modal form. We use 'submit' so it will
    // catch the user pressing Enter/Return to submit, as well.
    $('#student-entry').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // Gather and parse the form data.
        let data = getFormData('student-entry');

        if(data['studentNID'] == '') {
            alert("Please enter a student's NID.");
        }
        else {

            // Set the raw input.
            data['rawInput'] = "From Admin Panel";

            // Send all the relevant info to the adminUpdate function.
            adminUpdate(data, 'add-swipe', () => { alert('Student entry added successfully!'); });
        }
        // Hide the modal.
        $('#student-entry-modal').modal('hide');
    });

    // Handles the 'submit' event for the modal allowing the user to change an admin's
    // privilege level.
    $('#admin-level').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // Gather and parse the form data.
        let data = getFormData('admin-level');

        // Send all the relevant info to the adminUpdate function.
        adminUpdate(data, 'admin-chg-priv', refreshAdminList);

        // Hide the modal.
        $('#edit-admin-modal').modal('hide');
    });

    // Clear the form inputs when they hide themselves.
    $('#student-entry-modal, #edit-admin-modal').on('hidden.bs.modal', function(e) {
        $('#student-entry .form-group div input').each(function() {

            if( $(this).attr('id') == 'event' ) return;
            else $(this).val('');
        });
    });

    // Handles the 'submit' event for the Swipe Display tab.
    $('#swipe-display').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // Gather and parse the form data.
        let data = getFormData('swipe-display');

        // Send all the relevant info to the adminUpdate function.
        adminUpdate(data, 'swipe-list', updateHTML, 'result-div');
    });

    // Handles the 'submit' event for the modal for adding admin users.
    $('#add-admin').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // Gather and parse the form data.
        let data = getFormData('add-admin');

        // Pass all relevant info to the adminUpdate function.
        adminUpdate(data, 'admin-add', refreshAdminList);

        // Hide the modal.
        $('#add-admin-modal').modal('hide');
    });

    // Handles the "Get All" button on the Swipe tab.
    $('#all-btn').on('click', function(e) {
        e.preventDefault();

        let data = {};
        adminUpdate(data, 'swipe-list', updateHTML, 'result-div');
    });
}


/**
 * Handles adding the EventListeners to the "Edit" and "Delete" buttons on the Admins tab.
 * 
 * @return {void}
 */
function addEditDeleteListeners() {

    // Adds the listeners to the "Edit" buttons
    $('.edit-btn').on('click', function(e) {
        // Gets the hidden input value for their user ID.
        let target = parseInt($(this).parent().siblings('input').val());

        $('#edit-target').val(target);
    });

    // Adds the listeners to the "Delete" buttons
    $('.delete-btn').on('click', function(e) {

        // Gets the hidden input value for their user ID.
        let target = parseInt($(this).parent().siblings('input').val());

        // Creates an empty object and gives it the id.
        let data = {};

        data['id'] = target;

        // Sends all the relevant data to the adminUpdate function.
        adminUpdate(data, 'admin-delete', refreshAdminList);
    });
}


function setupInputs() {
    $('#term-select').on('change', function() {
        changeTerm();
    });

    changeTerm();
}


function changeTerm() {
    const termSplit = $('#term-select').val().split('-');
    const termData = {
        term: termSplit[0],
        year: termSplit[1],
    };

    console.log($('#term-select').siblings());

    $('#term-select').siblings().each(function() {
        const oldHref = $(this).attr('href').split('&');
        let href = oldHref[0];
        for (const [key, value] of Object.entries(termData)) {
            href += `&${key}=${value}`;
        }

        $(this).attr('href', href);
    });
}


/**
 * Handles running the AJAX requests on the page.
 * 
 * @param {Object} formData An anonymous object that we'll be passing as our $.ajax() data attribute.
 * @param {string} action  A string representing the action we're trying to perform.
 * @param {function} callback A function to run with the returned results.
 * @param {...any} cbArgs Additional arguments that should be passed to the callback function.
 * 
 * @return {void}
 */
function adminUpdate(formData, action, callback = () => {}, ...cbArgs) {

    // Make sure we've got a function to run on success.
    if( typeof callback !== "function" ) {
        console.error('Parameter "callback" is not a function.');
        return;
    }

    // Add the action that will tell the AJAX Handler what to do.
    formData['action'] = action;

    // Do the AJAX, then handle the response.
    doAjax(formData).then( response => {

        console.log( response );
        switch(parseInt(response)) {
            // If it's a binary TRUE response, just run the callback. This will mostly
            // be for the refreshAdminList() function.
            case 1:
                callback();
                break;
            // Log that there was an error, if there's ap problem.
            case 0:
                console.error('There was a problem with your request.');
                break;
            // If the result of parseInt() is NaN, then we've probably got a string, which means
            // the callback is probably updateHTML(), and we'll need to pass on the additional
            // arguments.
            default:
                if(isNaN(parseInt(response))) {
                    callback(response, ...cbArgs);
                }
                break;
        }
    });
}


/**
 * Re-gets all the data for the admin list. Called as a callback after a change has been made, so
 * the page always has accurate data.
 * 
 * @return {void}
 */
function refreshAdminList() {

    // All we need is the action name, here.
    let data = {action: 'admin-get-all' };

    // Do the thing, then update the HTML.
    doAjax(data).then( response => {
        updateHTML(response, 'admin-result');
    });

    // Add EventListeners, since the old elements with them will have been destroyed.
    addEditDeleteListeners();
}


/**
 * Gets the swipe data that the user requests.
 * 
 * @param {Object} formData An anonymous object that we'll be passing as our $.ajax() data attribute.
 * 
 * @return {void}
 */
function getSwipeData(formData) {

    // Sets the action, so the AJAX Handler knows what to do with the data.
    formData['action'] = 'swipe-list';

    // Do the AJAX, then update the HTML with the response.
    doAjax(formData).then( response => {
        updateHTML(response, 'result-div');
    });
}


/**
 * Obtains and parses the data of a given form, in preparation for sending it it in an
 * AJAX request.
 * 
 * @param {string} formID The id property of a given form (without the "#").
 * 
 * @return {Object} An object that can be passed to $.ajax().
 */
function getFormData(formID) {

    // Get the raw, serialized data.
    const rawData = $('#' + formID).serializeArray();

    // Create an empty Object.
    let data = {};

    // Set the new Object's properties so that they're ready for AJAX handling.
    rawData.forEach( item => {
        data[item.name] = item.value;
    });

    // Return the new Object.
    return data;
}


/**
 * Updates a given HTML element (usually a <div>, but not always) with some new text.
 * Even though it's short, declaring it as a function lets us more easily use it as 
 * a callback to adminUpdate().
 * 
 * @param {string} text  The new text content for the HTML element.
 * @param {string} id    The id attribute of the HTML element (without the "#").
 * 
 * @return {void}
 */
function updateHTML(text, id) {

    $('#' + id ).html(text);
}


/**
 * The function that handles the AJAX processing.
 * 
 * @param {Object} postData An anonymous object that we'll be passing as our $.ajax() data attribute.
 * 
 * @return {mixed} Whatever the response is. Will usually be a string.
 */
async function doAjax(postData) {

    // Initialize the response.
    let response = '';

    // Try the AJAX
    try {
        response = await $.ajax({
            url: ajaxURL,
            method: 'post',
            data: postData
        });
    }
    // Report any errors.
    catch(error) {
        console.error(error);
    }

    // Return the response.
    return response;
}