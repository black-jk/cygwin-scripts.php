#!/bin/env php
<?php
  error_reporting(E_ALL & ~E_NOTICE);
  require(__DIR__ . "/../config/config.php");
  
  Main::execute('RT');
?>
