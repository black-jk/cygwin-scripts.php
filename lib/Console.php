<?php
  if (defined('INC_CLASS_CONSOLE')) return
  define('INC_CLASS_CONSOLE', TRUE);
  
  // ====================================================================================================
  
  define('OUTPUT_STDOUT',      1);
  define('OUTPUT_DEBUG',       2);
  
  define('OUTPUT_LOG_DEBUG',   4);
  define('OUTPUT_LOG_INFO',    8);
  define('OUTPUT_LOG_MSG',    16);
  define('OUTPUT_LOG_WARN',   32);
  define('OUTPUT_LOG_ERROR',  64);
  define('OUTPUT_LOG_FATAL', 128);
  
  
  
  // ====================================================================================================
  
  class Console {
    
    // ----------------------------------------------------------------------------------------------------
    // [Output]
    // ----------------------------------------------------------------------------------------------------
    
    public static function out($msg, $mode = OUTPUT_STDOUT, $output_options = array(), $log_options = array()) {
      if ($mode & OUTPUT_STDOUT || ($mode & OUTPUT_DEBUG && ParamsParser::getParam('debug', FALSE))) {
        $indent = $output_options['indent'];
        if (empty($indent) || $indent < 0) {
          $_msg = $msg;
        } else {
          $_msg = Tool::indent($msg, $indent);
        }
        echo "{$output_options['bol']}{$_msg}{$output_options['eol']}";
      }
      
      if ($mode & (OUTPUT_LOG_DEBUG | OUTPUT_LOG_INFO | OUTPUT_LOG_MSG | OUTPUT_LOG_WARN | OUTPUT_LOG_ERROR | OUTPUT_LOG_FATAL)) {
        $indent = $log_options['indent'];
        if (empty($indent) || $indent < 0) {
          $_msg = $msg;
        } else {
          $_msg = Tool::indent($msg, $indent);
        }
        
        $log = "{$log_options['bol']}{$_msg}{$log_options['eol']}";
        
        if ($mode & OUTPUT_LOG_DEBUG) {
          Logger::debug($log);
        }
        if ($mode & OUTPUT_LOG_INFO) {
          Logger::info($log);
        }
        if ($mode & OUTPUT_LOG_MSG) {
          Logger::msg($log);
        }
        if ($mode & OUTPUT_LOG_WARN) {
          Logger::warn($log);
        }
        if ($mode & OUTPUT_LOG_ERROR) {
          Logger::error($log);
        }
        if ($mode & OUTPUT_LOG_FATAL) {
          Logger::fatal($log);
        }
      }
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
  }
?>
