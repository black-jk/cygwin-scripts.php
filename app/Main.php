<?php
  if (defined('INC_CLASS_MAIN')) return;
  define('INC_CLASS_MAIN', TRUE);
  
  class Main {
    
    public static $application;
    
    public static $applicationName;
    
    // --------------------------------------------------
    
    public static function execute($controlloerName) {
      try {
        ParamsParser::parseParams();
        $actionName = ParamsParser::getOption(0);
        if (!$actionName) {
          $actionName = 'main';
        }
        
        $controllerClass = "{$controlloerName}Controller";
        $controllerFile = "{$controllerClass}.php";
        
        require(CONTROLLER_ROOT . "{$controllerFile}");
        $application = new $controllerClass();
        self::$application = $application;
        $application->begin();
        $application->execute($actionName);
        $application->done();
        
      } catch (Exception $e) {
        $error_message = "[ERROR] {$e->getMessage()}";
        Logger::error($error_message);
        echo "  {$error_message}\n\n";
        
        if ($application) {
          $application->execute('help');
        }
      }
    }
    
    
    
    // ====================================================================================================
    
  }
?>
