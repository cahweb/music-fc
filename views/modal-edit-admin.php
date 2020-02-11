<?php
/**
 * A modal for editing the privilege level of a given admin.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// Requires
require_once MUSIC_FC__BASE_DIR . "/includes/music-fc-query-ref.php";
require_once MUSIC_FC__BASE_DIR . "/admin.php";

// Aliasing because long classnames are long.
use MusicQueryRef as MQEnum;
?>

<div class="modal fade" id="edit-admin-modal" tabindex="-1" role="dialog" aria-labelledby="edit-admin-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="edit-admin-modal-label">Edit Privileges</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <form id="admin-level">
                        <div class="form-group">
                            <label for="new-level-edit">New Level:</label>
                            <select id="new-level-edit" name="new-level" class="form-control">
                                <?php
                                // Get the available admin levels.
                                $result = $mfhelp->query( MQEnum::ADMIN_GET_LEVELS );

                                if( $result instanceof mysqli_result && $result->num_rows > 0 ) {
                                    while( $row = mysqli_fetch_assoc( $result ) ) {
                                        ?>
                                        <option value="<?= $row['id'] ?>"><?= $row['level'] ?></option>
                                        <?php
                                    }

                                    mysqli_free_result( $result );
                                }
                                else {
                                    ?>
                                    <option value="0">-- No Levels Found --</option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" id="edit-target" name="edit-target">
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" aria-label="Cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" form="admin-level">Change</button>
            </div>
        </div>
    </div>
</div>