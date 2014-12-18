<?php
  if (defined('INC_CLASS_LOGGER')) return;
  define('INC_CLASS_LOGGER', TRUE);
  
  class Logger {
    
    private static $_instance;
    
    public static function getInstance() {
      if (!self::$_instance) {
        $logger = new Logger(LOG_ROOT);
        self::$_instance = $logger;
      }
      return self::$_instance;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    /*
    private static $_disable = FALSE;
    
    public static function disable() {
      self::$_disable = TRUE;
    }
    
    public static function enable() {
      self::$_disable = FALSE;
    }
    */
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public static function debug($msg) {
      self::getInstance()->log('debug', $msg);
    }
    
    public static function info($msg) {
      self::getInstance()->log('info', $msg);
    }
    
    public static function msg($msg) {
      self::getInstance()->log('msg', $msg);
    }
    
    public static function warn($msg) {
      self::getInstance()->log('warn', $msg);
    }
    
    public static function error($msg) {
      self::getInstance()->log('error', $msg);
    }
    
    public static function fatal($msg) {
      self::getInstance()->log('fatal', $msg);
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public static function traceStack($title = '', $doLog = TRUE) {
      try {
        throw new Exception($title);
      } catch (Exception $e) {
        $stack = "[StackTrace] [{$title}]\n" .
                 "-----------------------------------------\n" .
                 $e->getTraceAsString() . "\n" .
                 "-----------------------------------------";
      }
      if ($doLog) {
        Logger::msg($stack);
      }
      
      return $stack;
    }
    
    
    
    // ====================================================================================================
    
    public $log_path;
    
    public $app_name = "PHP_SCRIPT";
    
    // --------------------------------------------------
    
    public function __construct($path) {
      if (!$path) {
        throw new Exception('Logger path empty');
      }
      $this->log_path = $path;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function log($type, $msg) {
      $full_path = "{$this->log_path}{$this->app_name}.{$type}.log";
      
      $date = date("Y-m-d H:i:s");
      $log = sprintf("[%s] %s\n", $date, $msg);
      
      file_put_contents($full_path, $log, FILE_APPEND);
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
  }
?>
