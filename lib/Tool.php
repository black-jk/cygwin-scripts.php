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
