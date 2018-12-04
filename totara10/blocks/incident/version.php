<?php

$plugin->version = 2015042801;  // YYYYMMDDHH (year, month, day, 24-hr time)
$plugin->requires = 2010112400; // YYYYMMDDHH (This is the release version for Moodle 2.0)
$plugin->cron = 600; #run every 5 minute
$plugin->component = 'block_incident';      // Full name of the plugin (used for diagnostics)