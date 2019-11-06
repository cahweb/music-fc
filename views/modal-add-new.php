<?php
/**
 * An Athena-powered modal for adding or editing events.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */
?>

<div class="modal fade" id="new-event-modal" tabindex="-1" role="dialog" aria-labelledby="new-event-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="new-event-modal-label">Add New Event</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <form id="form-add-new-event">
                        <input type="hidden" id="modal-action-type" data-action="" value="">
                        <input type="hidden" id="edit-event-id" name="event-id" value="">
                        <div class="form-group row">
                            <label for="new-event-date" class="col-4 col-md-2 col-form-label">Date</label>
                            <div class="col-8 col-md-4">
                                <input type="date" id="new-event-date" name="event-date" class="form-control" required>
                            </div>
                            <label for="new-event-time" class="col-4 col-md-2 col-form-label">Time</label>
                            <div class="col-8 col-md-4">
                                <input type="time" id="new-event-time" name="event-time" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="new-event-title" class="col-4 col-md-2 col-form-label">Title</label>
                            <div class="col-8 col-md-10">
                                <input type="text" id="new-event-title" name="event-title" class="form-control" placeholder="Event Title" required>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" aria-label="Cancel">Cancel</button>
                <button type="submit" form="form-add-new-event" class="btn btn-primary submit-btn" id="event-submit-btn" aria-label="Add">Add</button>
            </div>
        </div>
    </div>
</div>