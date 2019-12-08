<?php

namespace FsrImporter;


/**
 * Action is added to the Cron Event, hook
 */
add_action( 'weekly_error_email', '\FsrImporter\send_email' );
function send_email(  ): void
{
    $aggregator = new \FsrImporter\Errors\ErrorAggregation();
    $aggregator->intialize();
    $aggregator->sendErrorEmailsToUsers();
};