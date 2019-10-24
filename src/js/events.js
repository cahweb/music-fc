
let limit = 20;
let offset = 0;
let total = 0;
let currentPage = 0;
let testVal = 500;
const pageIdPatt = /page\-(\d+)/;

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

    $('.delete-btn').on('click', function(e) {
        e.preventDefault();

        const delTarget = $(this).siblings('input[type="hidden"]').val();
        const delTitle = $(this).parents('td').siblings('td[colspan="3"').html();

        $.ajax({
            url: 'music-fc-ajax.php',
            method: 'post',
            data: {
                action: 'delete-event',
                target: delTarget,
                title: delTitle
            },
            success: response => {
                let result = parseInt(response);

                if( result > 0 ) {
                    getEvents( offset, limit, currentPage );
                }
                else if( result == 0 ) {
                    console.log( "There was an error deleting the event." );
                }
                else {
                    alert( "Target ID not set." );
                }
            }
        });
    });
}


function getEvents(newOffset, resultLimit, newPage) {

    $.ajax({
        url: 'music-fc-ajax.php',
        method: 'post',
        data: {
            action: 'get-event',
            offset: newOffset,
            limit: resultLimit
        },
        success: function(result) {
            $(window).scrollTop(120);
            $('#events-table tbody').html(result);
            $('.page-btn').removeClass('active');
            $('#page-' + newPage).addClass('active');
            offset = newOffset;
            currentPage = newPage;
            $('#start-idx').html(newOffset + 1);
            $('#end-idx').html(( newOffset + resultLimit < total ? newOffset + resultLimit : total ));
        }
    });
}

function setInitialData() {
    offset = parseInt($("#start-idx").html()) - 1;
    limit = parseInt($('#limit').val());
    total = parseInt($('#event-count').html());
    currentPage = parseInt(pageIdPatt.exec($('#page-buttons .page-btn.active').attr('id'))[1]);
}