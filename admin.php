<?php
/**
 * The administration page, for managing things on the back-end. The user has to be
 * a Super Admin (music_fc.admins.level == 1) in order to view this page.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// Start the session
session_start();

// Check to make sure the user is logged in AND has the appropriate privileges. If not, kick them
// back to the home page.
if( !isset( $_SESSION['username'] ) || empty( $_SESSION['username'] ) || $_SESSION['level'] > 1 )
    header( "Location: index.php" );

// Requires
require_once 'init.php';
require_once 'includes/music-fc-query-ref.php';

// Aliasing, because long classnames are long.
use MusicQueryRef AS MQEnum;

// This helps the footer load the right JavaScript.
define( 'CURRENT_PAGE', basename( __FILE__ ) );

// Get the header.
require_once 'header.php';

// Build the rest of the page:
?>
<div id="main" class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12">
            <ul class="nav nav-tabs bg-faded" id="tab-list" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tools-tab" data-toggle="tab" href="#tools" role="tab" aria-controls="tools" aria-selected="true">Tools</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab" aria-controls="data" aria-selected="false">Data</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="admins-tab" data-toggle="tab" href="#admins" role="tab" aria-controls="admins" aria-selected="false">Admins</a>
                </li>
            </ul>
            <div class="tab-content bg-faded p-3" id="admin-tabs">
                <div class="tab-pane fade show active" id="tools" role="tabpanel" aria-labelledby="tools-tab">
                    <div class="d-flex flex-row justify-content-around mb-3">
                        <button type="button" id="add-student-btn" class="btn btn-primary" data-toggle="modal" data-target="#student-entry-modal">Add Student Entry</button>
                        <a href="csv/export.php?canvas=0" target="_blank"><button type="button" id="get-csv" class="btn btn-primary">Export CSV</button></a>
                        <a href="csv/export.php?canvas=1" target="_blank"><button type="button" id="get-csv-canvas" class="btn btn-primary">Canvas CSV</button></a>
                    </div>
                    <small>Click "Add Student Entry" to manually add a student's swipe record.
                        <br />
                        Click "Export CSV" to get the most recent swipe data in a spreadsheet format.
                        <br />
                        Click "Canvas CSV" to get the same data, only in a format compatible with the Canvas gradebook.
                    </small>
                </div>

                <div class="tab-pane fade" id="data" role="tabpanel" aria-labelledby="data-tab">
                    <form id="swipe-display">
                        <div class="form-group row">
                            <label for="event-select" class="col-2 col-md-1 col-form-label">Event: </label>
                            <div class="col-11 col-md-7">
                                <select id="event-select" name="event-select" class="form-control">
                                    <?php
                                    //  Getting a list of events to format as <option>s.
                                    $result_event = $mfhelp->query( MQEnum::EVENT_LIST, -1, 0, -5, 'ASC' );

                                    if( $result_event instanceof mysqli_result && $result_event->num_rows > 0 ) {
                                            
                                        $dt_fmt = "Y-m-d H:i:s";
                                        $d_fmt = "m/d/y";
                                        $t_fmt = "g:i a";
                                        $today = date_create();
                                        $selected = FALSE;
        
                                        while( $event = mysqli_fetch_assoc( $result_event ) ) {
                                            $datetime = date_create_from_format( $dt_fmt, $event['startdate'] );
        
                                            $selected_str = "";
        
                                            if( !$selected && $today <= $datetime ) {
                                                $selected_str = " selected";
                                                $selected = TRUE;
                                            }
                                            ?>
                                            <option value="<?= $event['id'] ?>" class="my-1"<?= $selected_str ?>>
                                                <?= date_format( $datetime, $d_fmt ); ?> | <?= strtoupper( trim( $event['title'] ) ); ?> | <?= strpos( $event['startdate'], '00:00:00' ) !== FALSE ? 'TBA' : date_format( $datetime, $t_fmt ) ?>
                                            </option>
                                            <?php
                                        }

                                        mysqli_free_result( $result_event );
                                    }
                                    else {
                                        ?>
                                        <option value="0" class="my-1" selected>-- No Events Found --</option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-4 col-md-2">
                                <button type="submit" class="btn btn-outline-secondary">Get</button>
                            </div>
                            <div class="col-4 col-md-2">
                                <button type="button" id="all-btn" class="btn btn-primary">Get All</button>
                            </div>
                        </div>
                    </form>
                    <div id="result-div" class="p-3"></div>
                </div>

                <div class="tab-pane fade" id="admins" role="tabpanel" aria-labelledby="admins-tab">
                    <div class="row justify-content-center">
                        <div class="col-10">
                            <table id="admin-table" class="table table-striped table-hover table-responsive">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>NID</th>
                                        <th>Level</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-result">
                                    <?php
                                    // Getting the list of admins.
                                    $result = $mfhelp->query( MQEnum::ADMIN_GET_ALL );

                                    if( $result instanceof mysqli_result && $result->num_rows > 0 ) {

                                        while( $row = mysqli_fetch_assoc( $result ) ) {

                                            ?>
                                            <tr>
                                                <td><?= $row['fullname'] ?></td>
                                                <td><?= $row['nid'] ?></td>
                                                <td><?= $row['level'] ?></td>
                                                <td>
                                                    <div class="admin-action-btns">
                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" data-toggle="modal" data-target="#edit-admin-modal">Edit</button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn">Delete</button>
                                                    </div>
                                                    <input type="hidden" value="<?= $row['user_id'] ?>">
                                                </td>
                                            </tr>
                                            <?php
                                        }

                                        mysqli_free_result( $result );
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-3">
                            <button type="button" class="btn btn-sm btn-outline-complementary float-right" id="add-btn" data-toggle="modal" data-target="#add-admin-modal">Add Admin</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
// Get the footer.
require_once 'footer.php';
?>
