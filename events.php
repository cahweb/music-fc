<?php
session_start();

if( !isset( $_SESSION['username'] ) || empty( $_SESSION['username'] ) )
    header( "Location: index.php" );

require_once 'init.php';
require_once 'includes/music-fc-query-ref.php';

use MusicQueryRef as MQEnum;

define( 'CURRENT_PAGE', basename( __FILE__ ) );

require_once 'header.php';

if( $result_count = $mfhelp->query( MQEnum::EVENT_COUNT ) ) {

    $row_count = mysqli_fetch_all( $result_count );
    
    $event_count = 0;
    foreach( $row_count as $count ) {
        $event_count += intval( $count[0] );
    }
    mysqli_free_result( $result_count );
}
else {
    $event_count = "an indeterminate number of";
}

$show_limit = 20;
?>

<input type="hidden" id="mysqlnd-qc-enable-switch" value="qc=on">
<input type="hidden" id="limit" value="<?= $show_limit ?>">

<div id="main" class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 bg-faded flex-column justify-content-center mb-5 p-4">
            <h3 class="heading-underline">Event List</h3>

            <p class="mb-4">
                Viewing the existing events. <span id="start-idx">1</span>&ndash;<span id="end-idx"><?= $event_count > $show_limit ? $show_limit : $event_count ?></span> of <span id="event-count"><?= $event_count ?></span> events.
                
                <a href="addevent.php" class="float-right">
                    <button type="button" class="mb-0 p-2 btn btn-outline-complementary float-right align-self-flex-start" id="add-btn">Add Event</button>
                </a>
            </p>

            <table id="events-table" class="table table-striped table-hover table-responsive">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th colspan="3">Title</th>
                        <th>Time</th>
                        <th colspan="2">Options</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $result = $mfhelp->query( MQEnum::EVENT_LIST, $show_limit );
                if( $result ) {
                    $dt_fmt = "Y-m-d H:i:s";
                    $d_fmt = "m/d/y";
                    $t_fmt = "g:ia";
                    while( $row = mysqli_fetch_assoc( $result ) ) {
                        $datetime = date_create_from_format( $dt_fmt, $row['startdate'] );

                        $time = date_format( $datetime, $t_fmt );
                    ?>
                    <tr>
                        <td><?= date_format( $datetime, $d_fmt ); ?></td>
                        <td colspan="3"><?= strtoupper( $row['title'] ); ?></td>
                        <td><?= $time != "12:00am" ? $time : "TBA" ?></td>
                        <td colspan="2">
                            <div class="btn-group mx-auto">
                                <button type="button" class="btn btn-outline-primary btn-sm edit-btn mr-2">Edit</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm delete-btn">Delete</button>
                            </div>
                            <input type="hidden" class="event-meta" data-id="<?= $row['id'] ?>">
                        </td>
                    </tr>
                    <?php
                    }
                }
                else {
                    ?>
                    <h4>No results to display...</h4>
                    <?php
                }
                ?>  
                </tbody>
            </table>
            <div id="page-nav-div" class="d-flex justify-content-between align-items-center flex-row">
                <button type="button" id="prev-btn" class="btn btn-primary mb-0 p-2">&laquo; Prev</button>

                <div class="btn-group d-flex flex-nowrap justify-content-around" id="page-buttons">
                <?php
                $num_pages = $event_count % $show_limit == 0 ? $event_count / $show_limit : ( $event_count / $show_limit ) + 1;
                for( $i = 1; $i <= $num_pages; $i++ ) {
                    ?>
                    <button type="button" id="page-<?= $i ?>" class="btn btn-outline-secondary page-btn mx-1 mb-0 py-2<?= $i == 1 ? " active" : "" ?>"><?= $i ?></button>
                    <?php
                }
                ?>
                </div>

                <button type="button" id="next-btn" class="btn btn-primary mb-0 p-2">Next &raquo;</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>