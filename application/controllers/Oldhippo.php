<?php

defined('BASEPATH') or exit('No direct script access allowed');

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis  There are traits AWS, Courses etc. which this class can use;
 * since multiple inherihence is not very straightforward in php.
 */
/* ----------------------------------------------------------------------------*/
class Oldhippo extends CI_Controller
{
    public function index()
    {
        echo 'hello';
    }
}
