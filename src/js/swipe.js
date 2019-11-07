"use strict";

// The URL for AJAX requests.
const ajaxURL = 'music-fc-ajax.php';

// The RegExp pattern for parsing the card swipe input data.
const patt = /^%[a-z](\d+)\^([\w\s]+)\/([\w\s]+)\^(\d+)[\s\d]+\?$/i;

// Some useful globals.
let isSwiping = false;
let swipeID = null;

// Fires on page load.
$(document).ready(function() {
    addClickListeners();
});


/**
 * Adds all the EventListeners we need.
 * 
 * @return {void}
 */
function addClickListeners() {

    // Handles clicking the "Begin Swiping" button.
    $('#start-btn').on('click', function(e) {
        e.preventDefault();

        // We don't want to do this if we're already swiping.
        if(!isSwiping) {
            isSwiping = true;

            // Disable the button, so the user can't keep clicking on it.
            $(this).prop('disabled', true);

            // Enable the "Done Swiping" button.
            $('#done-btn').prop('disabled', false);

            // Parse the selected event and assign it to swipeID.
            swipeID = parseInt( $('#event-select').val() );

            // Start the swiping.
            startSwipe(swipeID);
        }
    });

    // Handles clicking the "Done Swiping" button.
    $('#done-btn').on('click', function(e) {
        e.preventDefault();

        // We don't want to do this if we're not swiping.
        if(isSwiping) {
            isSwiping = false;

            // Disable this button.
            $(this).prop('disabled', true);

            // Re-enable the "Begin Swiping" button.
            $('#start-btn').prop('disabled', false);

            // End the swiping.
            endSwipe(swipeID);

            // Reset the swipeID variable.
            swipeID = null;
            
        }
    });

    // Handles the 'input' event, which will fire when a user swipes a card.
    $('#swipe').on('input', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // The raw text value of the swipe.
        const rawText = $(this).val();

        // Run the RegExp pattern and extract the data we need.
        let results = patt.exec(rawText);

        // If it works, set the data object to pass to the AJAX handler.
        if( Array.isArray(results) ) {
            let data = {
                action: 'add-swipe',
                eventID: swipeID,
                fname: results[3].trim(),
                lname: results[2].trim(),
                cardNum: results[1].trim(),
                rawInput: rawText
            };

            // Do the AJAX, then handle the response and empty out the input.
            doAjax(data).then(response => {
                switch(parseInt(response)) {
                    case 1:
                        setResultStatus(true);
                        $(this).val('');
                        break;
                    case 0:
                        setResultStatus(false);
                        $(this).val('');
                        break;
                    default:
                        break;
                }
            });
        }

    });

    // We don't actually want the 'input' event to cause anything to submit, either, so
    // we'll disable that.
    $('#swipe-card').on('submit', () => { return false; } );
}


/**
 * Gets everything ready to start swiping.
 * 
 * @param {int} target The Event ID, just in case we need it.
 * 
 * @return {void}
 */
function startSwipe(target) {

    // Changes the help text.
    $('#swipe-state-1').html('Done');
    $('#swipe-state-2').html('disable');

    // Disable the event <select> box.
    $('#event-select').prop('disabled', true);

    // Enable the swipe <input> and give it focus, so the user can move straight to swiping.
    $('#swipe').prop('disabled', false);
    $('#swipe').focus();
}


/**
 * Shuts down the swiping process and resets everything.
 * 
 * @param {int} target The Event ID, just in case we need it.
 * 
 * @return {void}
 */
function endSwipe(target) {

    // Clears out the CSS classes that give the user swipe feedback.
    clearResultStatus();

    // Resets the help text.
    $('#swipe-state-1').html('Begin');
    $('#swipe-state-2').html('enable');

    // Disable the swiping <input>.
    $('#swipe').prop('disabled', true);

    // Re-enable the event <select> box.
    $('#event-select').prop('disabled', false);
}


/**
 * Changes the CSS classes of the swipe <input> element to show success or failure of the
 * swipe.
 * 
 * @param {bool} success Whether or not the swipe update was successful.
 * 
 * @return {void}
 */
function setResultStatus(success) {
    
    // Get rid of existing classes.
    clearResultStatus();

    // Add the success classes if we succeeded.
    if(success) {
        $('#swipe').addClass('form-control-success').parent().addClass('has-success');
    }
    // Or the danger classes if we failed.
    else {
        $('#swipe').addClass('form-control-danger').parent().addClass('has-danger');
    }
}


/**
 * Clears the CSS classes for the swipe <input> that provide feedback for the user.
 * 
 * @return {void}
 */
function clearResultStatus() {
    $('#swipe').removeClass('form-control-success form-control-danger').parent().removeClass('has-success has-danger');
}


/**
 * The AJAX function that creates an AJAX request and returns the response.
 * 
 * @param {Object} postData An anonymous object that we'll be passing as our $.ajax() data attribute.
 * 
 * @return {mixed} The server response, usually a string.
 */
async function doAjax(postData) {

    // Initialize the return variable.
    let response = '';

    // Try the AJAX request.
    try {
        response = await $.ajax({
            url: ajaxURL,
            method: 'post',
            data: postData,
        });
    }
    // Catch and log errors.
    catch(error) {
        console.error(error);
    }

    // Return the response.
    return response;
}