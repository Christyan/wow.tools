<?php

use App\Kernel;

$classLoader = require_once(__DIR__ . '/../vendor/autoload.php');
require_once("../inc/config.php");

$kernel = new Kernel($classLoader);
$kernel->handleRequest();