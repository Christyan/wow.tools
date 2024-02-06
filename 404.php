<?php

http_response_code(404);

require_once("inc/config.php");

global $twig;

print $twig->render('error/404.html.twig', [
]);