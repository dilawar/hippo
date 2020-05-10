<?php

require_once BASEPATH . 'database.php';

return executeQueryReadonly($_GET['q']);
