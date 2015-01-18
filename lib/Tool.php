<?php
  if (!defined('INC_CLASS_TOOL')) return
  define('INC_CLASS_TOOL', TRUE);
  
  class Tool {
    
    // ----------------------------------------------------------------------------------------------------
    // [Date, Time]
    // ----------------------------------------------------------------------------------------------------
    
    public static function now($t = '') {
      $time = $t ? $t : time();
      return strftime("%Y-%m-%d %H:%M:%S", $time);
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    public static function date() {
      return substr(self::now(), 0, 10);
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Text]
    // ----------------------------------------------------------------------------------------------------
    
    public static function indent($text, $indent) {
      return preg_replace('/^/m', str_repeat(' ', $indent), $text);
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Download]
    // ----------------------------------------------------------------------------------------------------
    
    public static function getHtml($url, $options = array()) {
      Console::out("[OPERATION] getting html '{$url}' ... " . ($c > 1 ? "(retry: {$c})" : ""), OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('indent' => 6, 'bol' => "\n", 'eol' => "\n"));
      
      $cache_path = $options['cache_path']; // cache path of html
      $cache_time = $options['cache_time']; // cache time (minute)
      
      if ($cache_path) {
        $html_path = $cache_path;
      } else {
        $html_path = TMP_ROOT . "tool.get_html_tmp.html";
        if (file_exists($html_path)) {
          unlink($html_path);
        }
      }
      
      if (!is_numeric($cache_time)) {
        $cache_time = 30;
      }
      
      if (file_exists($cache_path)) {
        Console::out("(clear tmp file ...)", OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('indent' => 8, 'eol' => "\n"));
        $cmd = "find {$cache_path} -cmin +{$cache_time} -exec rm -v {} \\;";
        system("{$cmd} | awk '{print \"        \" \$0}'");
      }
      
      // ------------------------------
      
      $retry = $options['retry'];
      if (!is_numeric($retry)) {
        $retry = 3;
      }
      
      $c = 0;
      while ($c++ < $retry) {
        if (file_exists($html_path)) {
          Console::out("(cache found! {$html_path})", OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('indent' => 8, 'eol' => "\n"));
        } else {
          
          if (file_exists($html_path)) {
            unlink($html_path);
          }
          
          $useg_agent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36";
          $cmd = "axel.exe -q -n 1 -U '$useg_agent' -o '{$html_path}' '{$url}'";
          Console::out("[cmd] {$cmd}", OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('indent' => 8, 'bol' => "", 'eol' => "\n"));
          $result = system("{$cmd} | awk '{print \"        \" \$0}'");
          
          if ($result === FALSE) {
            Console::out("[ERROR] execute command fail!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('indent' => 8, 'bol' => "", 'eol' => "\n"));
            continue;
          }
          
          if (!file_exists($html_path)) {
            Console::out("[ERROR] missing html file! '{$html_path}'", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('indent' => 8, 'bol' => "", 'eol' => "\n"));
            continue;
          }
        }
        
        $html = file_get_contents($html_path);
        if (!$html) {
          Console::out("[ERROR] missing html contents! '{$html_path}'", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('indent' => 8, 'bol' => "", 'eol' => "\n"));
          if (file_exists($html_path)) {
            unlink($html_path);
          }
          continue;
        }
        
        Console::out("[SUCCESS] get html success!", OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('indent' => 8, 'bol' => "", 'eol' => "\n"));
        break;
      }
      
      return $html;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Console]
    // ----------------------------------------------------------------------------------------------------
    
    public static function editText($text) {
      $tmp_path = TMP_ROOT . "php_script_edit_text.txt";
      file_put_contents($tmp_path, $text);
      
      Console::out("Edit '{$tmp_path}'", OUTPUT_STDOUT, array('indent' => 4, 'eol' => "\n"));
      $input = readline("    Press ENTER when done. [Ready] ?");
      
      $text = file_get_contents($tmp_path);
      return $text;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
  }
?>
