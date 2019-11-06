"use strict";

const ajaxURL = 'music-fc-ajax.php';

$(document).ready(function() {
    addClickListeners();
});


function addClickListeners() {

    addEditDeleteListeners();

    $('#student-entry').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        let data = getFormData('student-entry');

        data['rawInput'] = "From Admin Panel";
        console.log(data);

        adminUpdate(data, 'add-swipe', () => { alert('Student entry added successfully!'); });

        $('#student-entry-modal').modal('hide');
    });

    $('#admin-level').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        let data = getFormData('admin-level');

        adminUpdate(data, 'admin-chg-priv', refreshAdminList);

        $('#edit-admin-modal').modal('hide');
    });

    $('#student-entry-modal, #edit-admin-modal').on('hidden.bs.modal', function(e) {
        $('#student-entry .form-group div input').each(function() {

            if( $(this).attr('id') == 'event' ) return;
            else $(this).val('');
        });
    });

    $('#swipe-display').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        let data = getFormData('swipe-display');

        adminUpdate(data, 'swipe-list', updateHTML, 'result-div');
    });

    $('#add-admin').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        let data = getFormData('add-admin');

        adminUpdate(data, 'admin-add', refreshAdminList);

        $('#add-admin-modal').modal('hide');
    });

    $('#all-btn').on('click', function(e) {
        e.preventDefault();

        let data = {};
        adminUpdate(data, 'swipe-list', updateHTML, 'result-div');
    });
}


function addEditDeleteListeners() {

    $('.edit-btn').on('click', function(e) {
        let target = parseInt($(this).parent().siblings('input').val());

        $('#edit-target').val(target);
    });

    $('.delete-btn').on('click', function(e) {

        let target = parseInt($(this).parent().siblings('input').val());

        let data = {};

        data['id'] = target;

        adminUpdate(data, 'admin-delete', refreshAdminList);
    });
}


function adminUpdate(formData, action, callback = () => {}, ...cbArgs) {

    if( typeof callback !== "function" ) {
        console.error('Parameter "callback" is not a function.');
        return;
    }

    formData['action'] = action;

    doAjax(formData).then( response => {

        switch(parseInt(response)) {
            case 1:
                callback();
                break;
            case 0:
                console.error('There was a problem with your request.');
                break;
            default:
                if(isNaN(parseInt(response))) {
                    callback(response, ...cbArgs);
                }
                break;
        }
    });
}


function refreshAdminList() {

    let data = {action: 'admin-get-all' };

    doAjax(data).then( response => {
        updateHTML(response, 'admin-result');
    });

    addEditDeleteListeners();
}


function getSwipeData(formData) {

    formData['action'] = 'swipe-list';

    doAjax(formData).then( response => {
        updateHTML(response, 'result-div');
    });
}


function getFormData(formID) {

    const rawData = $('#' + formID).serializeArray();

    let data = {};

    rawData.forEach( item => {
        data[item.name] = item.value;
    });

    return data;
}


function updateHTML(text, id) {

    console.log('firing updateHTML()');

    console.log(arguments);

    $('#' + id ).html(text);
}


async function doAjax(postData) {
    let response = '';
    try {
        response = await $.ajax({
            url: ajaxURL,
            method: 'post',
            data: postData
        });
    }
    catch(error) {
        console.error(error);
    }

    return response;
}