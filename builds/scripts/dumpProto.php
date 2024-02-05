<?php

if (php_sapi_name() != "cli") {
    die("This script cannot be run outside of CLI.");
}

include(__DIR__ . "/../../inc/config.php");

foreach (glob(BACKEND_BASE_DIR . "/exes/*.exe") as $file) {
    $expl = explode("-", basename($file));
    $name = $expl[0] . "-" . $expl[1];
    echo $name . "\n";
    exec("#!/bin/bash
cd " . BACKEND_BASE_DIR . "/protodump/repo
git rm -rf \"WoW/*\"
cd " . BACKEND_BASE_DIR . "/protodump/dump
/usr/bin/dotnet ProtobufDumper.dll '" . $file . "' '../repo/WoW'
cd " . BACKEND_BASE_DIR . "/protodump/repo
git add .
test -n \"$(git status --porcelain)\" && git commit -m '" . $name . "'
");
}
