<?php

// Instantiate the myform form from within the plugin.
//global $toform;
$mform = new \mod_nextblocks\form\grade_submit();

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    // If there is a cancel element on the form, and it was pressed,
    // then the `is_cancelled()` function will return true.
    // You can handle the cancel operation here.
    error_log(1, 3, "C:\wamp64\logs\php_error.log");
} else if ($fromform = $mform->get_data()) {
    // When the form is submitted, and the data is successfully validated,
    // the `get_data()` function will return the data posted in the form.
    error_log(2, 3, "C:\wamp64\logs\php_error.log");
} else {
    error_log(3, 3, "C:\wamp64\logs\php_error.log");
    // This branch is executed if the form is submitted but the data doesn't
    // validate and the form should be redisplayed or on the first display of the form.

    // Set anydefault data (if any).
    //$mform->set_data($toform);

    // Display the form.
    $mform->display();
}