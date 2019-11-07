<?php
/**
 * The header for the Music Forum Credit web app. Sets the document header and builds the navbar.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// Requires
require_once 'init.php';
?>
<!DOCTYPE html>
<html lang="en">
<html>

<head>
    <title><?= $title ?></title>
    <link rel="icon" href="https://www.ucf.edu/img/pegasus-icon.png" type="image/png">
    
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="Author" content="University of Central Florida, College of Arts and Humanities, Mike W. Leavitt">

    <!-- Athena CSS -->
    <link rel="stylesheet" href="lib/athena/css/framework.min.css">

    <!-- Site CSS -->
    <link rel="stylesheet" href="static/css/style.min.css">

</head>
<body>
    
    <div class="container-fluid media-background-container">
    
    <img class="media-background object-fit-cover filter-blur" src="img/front_splash.jpg">
    <nav class="navbar navbar-inverse navbar-toggleable-md bg-default">
        <div class="container flex-row justify-content-between">
            <a href="<?= $address ?>" class="navbar-brand mh-100">
                <img src="img/UL_Music_short.png" class="img-fluid" alt="UCF Music Unit Lockup" title="UCF Music">
            </a>

            <button class="navbar-toggler collapsed navbar-inverse ml-auto" id="menu-toggle" type="button" data-toggle="collapse" data-target="#header-menu" aria-controls="header-menu" aria-expanded="false" aria-label="Toggle Navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div id="header-menu" class="collapse navbar-collapse">
                <div class="navbar-nav">
                    <?= $mfhelp->menu_gen(); ?>
                </div>
            </div>
        </div>
    </nav>
