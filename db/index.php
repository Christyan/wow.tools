<?php

require_once("../inc/config.php");

global $twig;

print $twig->render('db/index.html.twig');
