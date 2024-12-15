#!/bin/env php
<?php

$contents = file_get_contents(realpath(dirname(__DIR__) . '/phpstan.neon'));
preg_match('/\n +level: (\d+)/', $contents, $match);

if ($match && ($match[1] ?? false)) {
    echo $match[1];
    exit();
}

echo 'N/A';
