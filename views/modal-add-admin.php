<?php
require_once MUSIC_FC__BASE_DIR . '/includes/music-fc-query-ref.php';
require_once MUSIC_FC__BASE_DIR . '/admin.php';

use MusicQueryRef as MQEnum;
?>

<div class="modal fade" id="add-admin-modal" tabindex="-1" role="dialog" aria-labelledby="add-admin-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-admin-modal-label">Add Administrator</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <form id="add-admin">
                        <div class="form-group">
                            <label for="add-nid">NID:</label>
                            <input type="text" name="nid" id="add-nid" class="form-control" placeholder="ab123456" maxlength="8">
                        </div>
                        <div class="form-group">
                            <label for="new-level">Level:</label>
                            <select id="new-level" name="level" class="form-control">
                                <?php
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
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" aria-label="Cancel">Cancel</button>
                <button type="submit" form="add-admin" id="add-admin-btn" class="btn btn-primary" aria-label="Add">Add</button>
            </div>
        </div>
    </div>
</div>