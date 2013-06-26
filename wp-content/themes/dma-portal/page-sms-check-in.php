<?php
/*
Template Name: SMS Check In
*/

if ($_REQUEST['From'])
	do_action( 'perform_checkin', $_REQUEST['From'], $_REQUEST['Body'] );
