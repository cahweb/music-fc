"use strict";

const ajaxURL = 'music-fc-ajax.php';
const patt = /^%[a-z](\d+)\^([\w\s]+)\/([\w\s]+)\^(\d+)[\s\d]+\?$/i;
let isSwiping = false;
let swipeID = null;

$(document).ready(function() {
    addClickListeners();
});


function addClickListeners() {
    $('#start-btn').on('click', function(e) {
        e.preventDefault();

        if(!isSwiping) {
            isSwiping = true;

            $(this).prop('disabled', true);
            $('#done-btn').prop('disabled', false);
            swipeID = parseInt( $('#event-select').val() );

            startSwipe(swipeID);
        }
    });

    $('#done-btn').on('click', function(e) {
        e.preventDefault();

        if(isSwiping) {
            isSwiping = false;

            $(this).prop('disabled', true);
            $('#start-btn').prop('disabled', false);
            endSwipe(swipeID);
            swipeID = null;
            
        }
    });

    $('#swipe').on('input', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const rawText = $(this).val();

        let results = patt.exec(rawText);

        if( Array.isArray(results) ) {
            let data = {
                action: 'add-swipe',
                eventID: swipeID,
                fname: results[3].trim(),
                lname: results[2].trim(),
                cardNum: results[1].trim(),
                rawInput: rawText
            };

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

    $('#swipe-card').on('submit', () => { return false; } );
}


function startSwipe(target) {

    $('#swipe-state-1').html('Done');
    $('#swipe-state-2').html('disable');
    $('#event-select').prop('disabled', true);
    $('#swipe').prop('disabled', false);
    $('#swipe').focus();
}


function endSwipe(target) {

    clearResultStatus();
    $('#swipe-state-1').html('Begin');
    $('#swipe-state-2').html('enable');
    $('#swipe').prop('disabled', true);
    $('#event-select').prop('disabled', false);
}


function setResultStatus(success) {
    
    clearResultStatus();

    if(success) {
        $('#swipe').addClass('form-control-success').parent().addClass('has-success');
    }
    else {
        $('#swipe').addClass('form-control-danger').parent().addClass('has-danger');
    }
}


function clearResultStatus() {
    $('#swipe').removeClass('form-control-success form-control-danger').parent().removeClass('has-success has-danger');
}


async function doAjax(postData) {
    let response = '';
    try {
        response = await $.ajax({
            url: ajaxURL,
            method: 'post',
            data: postData,
        });
    }
    catch(error) {
        console.error(error);
    }

    return response;
}