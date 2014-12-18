#!/bin/env php
<?php
  require(__DIR__ . "/../config/config.php");
  Main::execute('Application');
  
  // ====================================================================================================
  
  /* examples
  $storage = new Storage("TEST");
  $storage->data['hahaha'] = 'ok';
  $storage->save();
  */
  
?>
