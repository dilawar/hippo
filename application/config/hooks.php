<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

// NOTE: Using function here is not a great idea since function can't access
// $_SESSION etc.
$hook['post_controller_constructor'] = [
    'class' => 'HippoHooks', 'function' => 'PreController', 'filename' => 'HippoHooks.php', 'filepath' => 'hooks', 'params' => [],
];
