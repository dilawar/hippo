<?php

include_once BASEPATH . 'autoload.php';
echo userHTML();
$ref = $controller;

echo p('NOTE TO DEVELOPER: We are using bmvadmin interface for this task.');

echo goBackToPageLink('user/jcadmin');
