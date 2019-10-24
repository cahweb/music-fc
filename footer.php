<?php
require_once 'init.php';
?>
    <div class="container-fluid fixed-bottom">
        <div class="row">
            <div class="col-12 bg-inverse hidden-xs-down d-flex flex-row justify-content-center">
                <p class="text-inverse text-center m-1">&copy; <a href="https://music.cah.ucf.edu" class="text-inverse">School of Performing Arts - Music</a></p> | 
                <p class="text-inverse text-center m-1"><a href="https://cah.ucf.edu" class="text-inverse">College of Arts and Humanities</a></p> | 
                <p class="text-inverse text-center m-1"><a href="https://www.ucf.edu" class="text-inverse">University of Central Florida</a></p>
            </div>
            <div class="col-12 bg-inverse d-flex flex-column hidden-sm-up justify-content-center">
                <small class="text-inverse text-center m-1">&copy; <a href="https://music.cah.ucf.edu" class="text-inverse">School of Performing Arts - Music</a></small>
                <small class="text-inverse text-center m-1"><a href="https://cah.ucf.edu" class="text-inverse">College of Arts and Humanities</a></small>
                <small class="text-inverse text-center m-1"><a href="https://www.ucf.edu" class="text-inverse">University of Central Florida</a></small>
            </div>
        </div>
    </div>
    <!-- UCF Header -->
    <script type="text/javascript" id="ucfhb-script" src="//universityheader.ucf.edu/bar/js/university-header.js?use-1200-breakpoint=1"></script>
    
    <!-- Athena Scripts: JQuery, Tether, then Athena JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.3/js/tether.min.js" integrity="sha256-mIiWebTG82x+OcV3vUA49ffGDIAJ53uC9jflw5/+REs=" crossorigin="anonymous"></script>
    <script src="lib/athena/js/framework.min.js"></script>

    <?php if( CURRENT_PAGE == 'events.php' ) : ?>
    <script src="static/js/events.min.js"></script>
    <?php endif; ?>

    </div>
    </div>

<?php
unset( $mfhelp );
?>
</body>
</html>