<?php
  if (defined('INC_CLASS_DEBUG')) return
  define('INC_CLASS_DEBUG', TRUE);
  
  class Debug {
    
    // ----------------------------------------------------------------------------------------------------
    // [Trace]
    // ----------------------------------------------------------------------------------------------------
    
    public static function dumpVariable($variable) {
      ob_start();
      var_dump($variable);
      return ob_get_clean();
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Profiling]
    // ----------------------------------------------------------------------------------------------------
    
    /*
      http://allan914.logdown.com/posts/174888-php-profiling-xhprof
      http://php.net/manual/en/function.xhprof-disable.php
    */
    
    public static function startProfiling() {
      if (!function_exists('xhprof_enable')) {
        Logger::error("function xhprof_enable() not exist!");
        return;
      }
      xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    }
    
    // --------------------------------------------------
    
    public static function endProfiling() {
      if (!function_exists('xhprof_enable')) {
        Logger::error("function xhprof_disable() not exist!");
        return;
      }
      
      $profiling = xhprof_disable();
      
      $units = array('BYTE', 'KB', 'MB', 'GB', 'TB');
      $columns = array(
        'ct'  => 'Count',             // (function 的呼叫次數)
        'wt'  => 'Wall Time',         // (實際花費時間 ms)
        'cpu' => 'CPU ticks',         // (CPU 次數)
        'mu'  => 'Memory usage',      // (記憶體用量 bytes)
        'pmu' => 'Peak Memory Usage', // (尖峰記憶體用量 bytes)
      );
      
      $msg =
      "[Profilling] [" . Tool::now() . "]\n" .
      "--------------------------------------------------\n";
      
      foreach ($profiling as $title => $data) {
        $msg .= "  [{$title}]\n";
        
        foreach ($data as $key => $value) {
          $column = $columns[$key] ? $columns[$key] : $key;
          
          if (in_array($key, array('mu', 'pmu'))) {
            $index = 0;
            while ($value > 1024) {
              $index++;
              $value = $value / 1024;
            }
            $val = sprintf("%0.2f {$units[$index]}", $value);
          } else {
            $val = $value;
          }
          
          $msg .= sprintf("    %-20s {$val}\n", "[{$column}]");
        }
        $msg .= "\n";
      }
      
      $msg .=
      "--------------------------------------------------\n";
      
      Logger::msg($msg);
      
      return $profiling;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
  }
?>
