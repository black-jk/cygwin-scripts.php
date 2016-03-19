<?php
  
  error_reporting(E_ALL & ~E_NOTICE);
  
  // ====================================================================================================
  
  define('ROOT', __DIR__ . "/../");
  define('CONFIG_ROOT', __DIR__);
  define('APP_DATA_ROOT',   '/cygdrive/d/App/AppData/cygwin-scripts.php-logs/');
  
  define('TMP_ROOT',        ROOT . "tmp/");
  
  //define('LOG_ROOT',        ROOT . "logs/");
  define('LOG_ROOT',        APP_DATA_ROOT . "logs/");
  
  define('STORAGE_ROOT',    TMP_ROOT . "storage/");
  
  define('LIB_ROOT',        ROOT . "lib/");
  
  define('APP_ROOT',        ROOT . "app/");
  define('MODEL_ROOT',      APP_ROOT . "models/");
  define('VIEW_ROOT',       APP_ROOT . "views/");
  define('CONTROLLER_ROOT', APP_ROOT . "controllers/");
  
  // --------------------------------------------------
  
  require(LIB_ROOT . "ParamsParser.php");
  require(LIB_ROOT . "Tool.php");
  require(LIB_ROOT . "Debug.php");
  require(LIB_ROOT . "Logger.php");
  require(LIB_ROOT . "Console.php");
  require(LIB_ROOT . "Storage.php");
  require(LIB_ROOT . "BaseController.php");
  
  require(APP_ROOT . "Main.php");
  
  
  
  // ====================================================================================================
  
?>
