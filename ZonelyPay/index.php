<?php
define("__ROOT__", __DIR__);
require_once __ROOT__ . "/Files/Controllers/ControllersAll.php";
$ctl = new ZonelyControllersAll();
$ctl->route();