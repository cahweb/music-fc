
"use strict";

let limit = 20;
let offset = 0;
let total = 0;
let currentPage = 0;
const pageIdPatt = /page\-(\d+)/;
const ajaxUrl = 'music-fc-ajax.php';

let testElem;

// Original values of elements when editing entries.
let eventDateStr, eventTimeStr, eventTitle;

$(document).ready(function() {

    setInitialData();

    addClickListeners();
});


function addClickListeners() {

    $('#next-btn').on('click', function(e) {
        e.preventDefault();

        if(!$('.page-btn.active').is(':last-child')) {
            getEvents(offset + limit, limit, currentPage + 1);
        }
    })

    $('#prev-btn').on('click', function(e) {
        e.preventDefault();

        if(!$('.page-btn.active').is(':first-child')) {
            getEvents( offset - limit, limit, currentPage - 1);
        }
    });

    $('.page-btn').on('click', function(e) {
        e.preventDefault();

        let newPage = parseInt(pageIdPatt.exec($(this).attr('id'))[1]);
        let newOffset = limit * (newPage - 1);
        getEvents(newOffset, limit, newPage);
    });

    $('.edit-btn').on('click', function(e) {
        e.preventDefault();

        $('#modal-action-type').data('action', 'edit');
        $('#new-event-modal-label').html('Edit Event');

        $(this).parent().parent().siblings().each(function(index, elem) {

            const datePatt = /\d{2}\/\d{2}\/\d{2}/;
            const timePatt = /\d{1,2}:\d{2}\s[ap]m/;

            if(datePatt.test($(elem).html())) {
                eventDateStr = $(elem).html();
            }
            else if($(elem).html() == 'TBA' ) {
                eventTimeStr = '12:00 am';
            }
            else if(timePatt.test($(elem).html())) {
                eventTimeStr = $(elem).html();
            }
            else {
                eventTitle = $(elem).html();
            }
        });

        let eventDate = new Date(`${eventDateStr} ${eventTimeStr}`);

        eventDateStr = formatDate(eventDate);
        eventTimeStr = formatTime(eventDate);

        $('#new-event-date').val(eventDateStr);
        $('#new-event-time').val(eventTimeStr);
        $('#new-event-title').val(eventTitle);
        $('#edit-event-id').val($(this).parent().siblings('input').data('id'));
    });

    $('.delete-btn').on('click', function(e) {
        e.preventDefault();

        let delTarget = parseInt($(this).parent().siblings('input[type="hidden"]').data('id'));
        let delTitle = $(this).parents('td').siblings('td[colspan="3"]').html();

        total--;
        deleteEvent(delTarget, delTitle);
    });

    $('#add-btn').on('click', function(e) {
        e.preventDefault();

        $('#modal-action-type').data('action', 'add');
        $('#new-event-modal-label').html('Add New Event');
    });


    $('#form-add-new-event').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const rawData = $('#form-add-new-event').serializeArray();

        let data = {};

        rawData.forEach( item => {
            data[item.name] = item.value;
        });

        if($('#modal-action-type').data('action') == 'add') {
            total++;
            addEvent(data);
        }
        else if($('#modal-action-type').data('action') == 'edit') {
            eventUpdate(data);
        }

        $('#new-event-modal').modal('hide');
    });

    $('#new-event-modal').on('hidden.bs.modal', function(e) {
        $('#form-add-new-event .form-group div input').each(function() {
            $(this).val('');
        });
    })
}


function getEvents(newOffset, resultLimit, newPage) {

    const postData = {
        action: 'get-event',
        offset: newOffset,
        limit: resultLimit
    };

    doAjax(postData).then( response => updateHTML(response, newOffset, resultLimit, newPage));
}


function updateHTML(text, newOffset, resultLimit, newPage) {

    $(window).scrollTop(120);
    $('#events-table tbody').html(text);
    $('.page-btn').removeClass('active');
    $('#page-' + newPage).addClass('active');
    offset = newOffset;
    currentPage = newPage;
    $('#start-idx').html(newOffset + 1);
    $('#end-idx').html((newOffset + resultLimit < total ? newOffset + resultLimit : total));
    $('#event-count').html(total);

    addClickListeners();
}


function setInitialData() {
    offset = parseInt($("#start-idx").html()) - 1;
    limit = parseInt($('#limit').val());
    total = parseInt($('#event-count').html());
    currentPage = parseInt(pageIdPatt.exec($('#page-buttons .page-btn.active').attr('id'))[1]);
}


async function deleteEvent(delTarget, delTitle) {
    
    const postData = {
        action: 'delete-event',
        target: delTarget,
        title: delTitle
    };

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


function addEvent(formData) {

    formData['action'] = 'create-event';

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


function eventUpdate(formData) {

    formData['action'] = 'edit-event';
    formData['old-date'] = eventDateStr;
    formData['old-time'] = eventTimeStr;
    formData['old-title'] = eventTitle;

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


async function doAjax(postData) {

    let response = '';

    try {
        response = await $.ajax({
            url: ajaxUrl,
            method: 'post',
            data: postData
        });
    }
    catch(error) {
        console.error(error);
    }

    return response;
}


function formatDate(date) {

    console.log(date.getMonth());
    let dateStr = date.getFullYear() + '-';
    dateStr += date.getMonth() + 1 < 10 ? '0' + date.getMonth() : date.getMonth() + 1;
    dateStr += '-';
    dateStr += date.getDate() < 10 ? '0' + date.getDate() : date.getDate();

    return dateStr;
}


function formatTime(date) {
    let timeStr = date.getHours() < 10 ? '0' + date.getHours() : date.getHours();
    timeStr += ':';
    timeStr += date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes();

    return timeStr;
}