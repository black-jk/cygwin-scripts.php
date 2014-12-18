<?php
  if (defined('INC_CLASS_FILE_OPERATION_CONTROLLER')) return;
  define('INC_CLASS_FILE_OPERATION_CONTROLLER', TRUE);
  
  class FileOperationController extends BaseController {
    
    // ====================================================================================================
    
    public $app_name = 'FileOperation';
    
    // --------------------------------------------------
    
    //function __construct() {
    //}
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Actions]
    // ----------------------------------------------------------------------------------------------------
    
    public function show() {
      $dirs = $this->getDirs();
      foreach ($dirs as $dir) {
        $this->_show($dir);
      }
    }
    
    // --------------------------------------------------
    
    public function hide() {
      $dirs = $this->getDirs();
      foreach ($dirs as $dir) {
        $this->_hide($dir);
      }
    }
    
    // --------------------------------------------------
    
    public function info() {
      $dirs = $this->getDirs();
      foreach ($dirs as $dir) {
        $this->_info($dir);
      }
    }
    
    // --------------------------------------------------
    
    public function help() {
      require(VIEW_ROOT . "FileOperation/help.txt");
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Methods]
    // ----------------------------------------------------------------------------------------------------
    
    public function getDirs() {
      $dirs = array();
      $options = ParamsParser::getOptions();
      for ($i = 1; $i < count($options); $i++) {
        $dir = realpath($options[$i]);
        if (is_dir($dir)) {
          $dirs[] = $dir;
        }
      }
      
      return $dirs;
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    protected function _show($dir) {
      echo "  [SHOW] {$dir}\n";
      
      $dry_run = ParamsParser::getParam('n', FALSE);
      $verbose = ParamsParser::getParam('v', FALSE);
      
      $output_mode = $verbose ? OUTPUT_STDOUT : 0;
      
      $storage = new Storage("mapping", TRUE, $dir);
      
      foreach ($storage->data as $orig_filename => $dest_filename) {
        Console::out("[{$orig_filename}] {$dest_filename}", $output_mode, array('bol' => "    "));
        
        $orig_path = "{$dir}/{$orig_filename}";
        $dest_path = "{$dir}/{$dest_filename}";
        if (!file_exists($orig_path)) {
          Console::out("orig: '{$orig_path}' not exist!", OUTPUT_STDOUT | OUTPUT_LOG_WARN, array('bol' => "    [WARN]", 'eof' => "\n"), array('bol' => "[SHOW] "));
          unset($storage->data[$orig_filename]);
          continue;
        }
        if (file_exists($dest_path)) {
          Console::out("[SKIP] file exist!", $output_mode, array('bol' => "    ", 'eol' => "\n"));
          continue;
        }
        
        if ($dry_run) {
          Console::out("[dry-run] {$orig_path} -> {$dest_path}", $output_mode, array('bol' => "\n      "));
        } else {
          Console::out("[rename] {$orig_path} -> {$dest_path}", $output_mode | OUTPUT_LOG_INFO, array('bol' => "\n      "), array('bol' => "[SHOW] "));
          rename($orig_path, $dest_path);
        }
        
        unset($storage->data[$orig_filename]);
        Console::out("\n", $output_mode);
      }
      
      if (!$dry_run) {
        if (!$storage->save()) {
          Console::out("[WARN] save mapping fail!", $output_mode, array('bol' => "    "));
        }
      }
      
      Console::out("\n", $output_mode);
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    public function _hide($dir) {
      echo "  [HIDE] {$dir}\n";
      
      $dry_run     = ParamsParser::getParam('n', FALSE);
      $verbose     = ParamsParser::getParam('v', FALSE);
      $include_dir = ParamsParser::getParam('dirs', FALSE);
      
      $output_mode = $verbose ? OUTPUT_STDOUT : 0;
      
      $storage = new Storage("mapping", TRUE, $dir);
      
      $orig_filenames = array();
      exec("ls '{$dir}/' | grep -Ev 'mapping.data.*|\.(map|contents)\$'", $orig_filenames);
      
      $index = 0;
      $todo = array();
      foreach ($orig_filenames as $orig_filename) {
        $orig_path = "{$dir}/{$orig_filename}";
        if (!file_exists($orig_path)) continue;
        if (!$include_dir && is_dir($orig_path)) continue;
        
        do {
          $index++;
          $dest_filename = sprintf("%04d.contents", $index);
          $dest_path = "{$dir}/{$dest_filename}";
        } while (isset($storage->data[$dest_filename]) || file_exists($dest_path));
        
        Console::out("[{$dest_filename}] {$orig_filename}", $output_mode, array('bol' => "    ", 'eol' => "\n"));
        $storage->data[$dest_filename] = $orig_filename;
      }
      
      Console::out("\n", $output_mode);
      
      // do hide
      if (!$dry_run) {
        if (!$storage->save()) {
          Console::out("[FAIL] save mapping fail!", $output_mode, array('bol' => "    ", 'eol' => "\n"));
          return;
        }
        
        Console::out("[RENAME]\n", $output_mode, array('bol' => "    "));
        foreach ($storage->data as $dest_filename => $orig_filename) {
          $orig_path = "{$dir}/{$orig_filename}";
          $dest_path = "{$dir}/{$dest_filename}";
          if (!file_exists($orig_path)) {
            continue;
          }
          if (file_exists($dest_path)) {
            Console::out("[WARN] skip rename {$orig_filename} -> {$dest_path} (file exist!)", $output_mode | OUTPUT_LOG_WARN, array('bol' => "      ", 'eol' => "\n"));
            continue;
          }
          
          Console::out("[rename] {$orig_path} -> {$dest_path}", $output_mode | OUTPUT_LOG_INFO, array('bol' => "      "), array('bol' => "[HIDE] "));
          rename($orig_path, $dest_path);
          Console::out("", $output_mode, array('bol' => "      ", 'eol' => "\n"));
        }
      }
      
      Console::out("\n", $output_mode);
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    protected function _info($dir) {
      echo "  [INFO] {$dir}\n";
      
      $storage = new Storage("mapping", TRUE, $dir);
      
      foreach ($storage->data as $orig_filename => $dest_filename) {
        $index = preg_replace('/\.contents$/', '', $orig_filename);
        Console::out("{$index}: {$dest_filename}", OUTPUT_STDOUT, array('bol' => "    ", 'eol' => "\n"));
      }
      
      Console::out("\n", OUTPUT_STDOUT);
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
  }
?>
