<?php
  if (defined('INC_CLASS_PARAMS_PARSER')) return;
  define('INC_CLASS_PARAMS_PARSER', TRUE);
  
  class ParamsParser {
    
    // ----------------------------------------------------------------------------------------------------
    
    public static $params;
    
    // --------------------------------------------------
    
    public static function parseParams() {
      global $argv;
//var_dump($argv);
      
      $begin_index = 0;
      
      self::$params = array(
        'PHP_FILE' => $argv[$begin_index],
      );
      
      $options = array();
      
      $i = $begin_index + 1;
      while ($i < count($argv)) {
        $param = $argv[$i];
//echo "param: \$argv[{$i}] = '{$param}'\n";
        if (preg_match('/^--[0-9a-zA-Z]/', $param)) {
          $key = preg_replace('/^--/', '', $param);
          $val = $argv[$begin_index + $i];
          self::$params[$key] = $val;
//echo "> params[{$key}] = {$val}\n";
          $i += 2;
        } else
        if (preg_match('/^-[0-9a-zA-Z]/', $param)) {
          $key = preg_replace('/^-/', '', $param);
          self::$params[$key] = TRUE;
//echo "> params[{$key}] = TRUE\n";
          $i += 1;
        } else {
          $options[] = $param;
//echo "> params['_options'][] = {$param}\n";
          $i += 1;
        }
      }
      
      self::$params['_options'] = $options;
    }
    
    // --------------------------------------------------
    
    public static function getOptions() {
      return self::$params['_options'];
    }
    
    public static function getOption($index) {
      return self::$params['_options'][$index];
    }
    
    public static function getParam($key, $default='', $enable4empty=FALSE) {
      if (self::$params[$key] != "" || ($enable4empty && array_key_exists($key, self::$params))) {
        return self::$params[$key];
      } else {
        return $default;
      }
    }
    
    
    
    // ====================================================================================================
    
  }
?>
