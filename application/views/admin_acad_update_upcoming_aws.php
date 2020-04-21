<?php

include_once BASEPATH.'autoload.php';
echo userHTML();

$ref = $controller;

$awsId = $_POST[ 'id' ];
echo alertUser("You are updating AWS with id $awsId");
$aws = getUpcomingAWSById($awsId);

echo '<form method="post" action="'.site_url("$ref/update_upcoming_aws_submit"). '">';
echo editableAWSTable(-1, $aws);
echo '<input type="hidden", name="id" value="' . $awsId . '">';
echo '</form>';

echo goBackToPageLink($ref, 'Go Back');
