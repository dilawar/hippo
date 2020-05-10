<?php

include_once 'header.php';
include_once 'methods.php';

$_SESSION['AUTHENTICATED'] = false;

echo printInfo('Successfully logged out');
goToPage('index.php', 0);
