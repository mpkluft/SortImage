<?php

require __DIR__ . "/vendor/autoload.php";

require_once('SortController.php');

$s = new FileSortControllerNas();
$s->index();
