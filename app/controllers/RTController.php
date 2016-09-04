<?php
  if (defined('INC_CLASS_RT_CONTROLLER')) return;
  define('INC_CLASS_RT_CONTROLLER', TRUE);
  
  require(MODEL_ROOT . "RT.php");
  
  class RTController extends BaseController {
    
    // ====================================================================================================
    
    public $app_name = 'RT';
    
    // --------------------------------------------------
    
    public $dry_run;
    public $verbose;
    public $update;
    
    // --------------------------------------------------
    
    function __construct() {
      parent::__construct();
      
      RT::clearTMP();
      
      $this->dry_run = ParamsParser::getParam('n', FALSE);
      $this->verbose = ParamsParser::getParam('v', FALSE);
      $this->update  = ParamsParser::getParam('u', FALSE);
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Methods]
    // ----------------------------------------------------------------------------------------------------
    
    public function getSrc() {
      $options = ParamsParser::getOptions();
      $sources = array_slice($options, 1);
      return $sources;
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    protected function _batchAction($action, $src) {
      if (is_array($src)) {
        $lines = $src;
      } else if (is_string($src)) {
        $src = preg_replace('/ +/', ' ', trim($src));
        Console::out("[_batchAction] [action: {$action}] [src: '{$src}']", OUTPUT_DEBUG, array('bol' => "  ", 'eol' => "\n\n"));
        $lines = explode(' ', $src);
      } else {
        Console::out("[_batchAction] [action: {$action}] Unknow src type: " . Debug::dumpVariable($src), OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('bol' => "  ", 'eol' => "\n"));
        return;
      }
      
      foreach ($lines as $line) {
        $id = preg_replace(array('/http.*\//', '/[^0-9].*$/'), '', $line);
        if ($id) {
          Console::out("[{$action}] [#{$id}] ({$line})", OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('bol' => "  ", 'eol' => "\n"));Console::out("[{$action}] [#{$id}] ({$line})", OUTPUT_DEBUG | OUTPUT_LOG_DEBUG, array('bol' => "  ", 'eol' => "\n"));
        } else {
          Console::out("[{$action}] id empty! ({$line})", OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('bol' => "  ", 'eol' => "\n"));
          continue;
        }
        
        $rt = RT::findById($id);
        if ($rt) {
          $is_new = FALSE;
        } else {
          $is_new = TRUE;
          $rt = new RT(array('id' => $id));
        }
        
        switch ($action) {
          case 'add':
            $rt->setStatus('todo');
            break;
            
          case 'bad':
          case 'retry':
          case 'repeat':
          case 'private':
            $rt->setStatus($action);
            break;
            
          case 'reset':
            if (!$is_new) {
              $rt->destroy();
            }
            break;
            
          case 'edit':
            if (!$is_new) {
              $rt->editLog();
            }
            break;
            
          case 'preview':
            $rt->preview();
            break;
            
          case 'info':
            if ($is_new) {
              Console::out("[NEW]", OUTPUT_STDOUT, array('indent' => 4, 'eol' => "\n"));
            }
            $rt->info($this->verbose, $this->update && !$is_new);
            break;
            
          case 'update':
            if (!$is_new) {
              $rt->update();
            }
            break;
            
          default:
            Console::out("[{$action}] [#{$id}] [skip] Unknow action!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('indent' => 4, 'eol' => "\n"));
            break;
        }
        
        Console::out("--------------------------------------------------", OUTPUT_STDOUT, array('indent' => 2, 'eol' => "\n"));
      }
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Actions]
    // ----------------------------------------------------------------------------------------------------
    
    public function main() {
      $this->run();
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function add() {
      $src = $this->getSrc();
      $this->_batchAction('add', $src);
    }
    
    public function retry() {
      $src = $this->getSrc();
      $this->_batchAction('retry', $src);
    }
    
    public function bad() {
      $src = $this->getSrc();
      $this->_batchAction('bad', $src);
    }
    
    public function repeat() {
      $src = $this->getSrc();
      $this->_batchAction('repeat', $src);
    }
    
    public function privat() {
      $src = $this->getSrc();
      $this->_batchAction('private', $src);
    }
    
    public function reset() {
      $src = $this->getSrc();
      $this->_batchAction('reset', $src);
    }
    
    // --------------------------------------------------
    
    public function edit() {
      $src = $this->getSrc();
      $this->_batchAction('edit', $src);
    }
    
    // --------------------------------------------------
    
    public function preview() {
      $src = $this->getSrc();
      $this->_batchAction('preview', $src);
    }
    
    // --------------------------------------------------
    
    public function category() {
      // http://www.redtube.com/?search=givemepink
      // http://www.redtube.com/sandrashinelive?page=1
      // http://www.redtube.com/pornstar/taylor+vixen
      
      $action = ParamsParser::getOption(1);
      
      $url = ParamsParser::getOption(2);
      
      $limit = ParamsParser::getParam('limit', 0);
      
      if (preg_match('/\?search=/', $url)) {
        $glue = "&";
        $tmp_filename = preg_replace(array('/^.*com\/?\?search=/'), array('search-'), $url);
      } else {
        $glue = "?";
        $url = preg_replace('/\?.*$/', '', $url);
        $tmp_filename = preg_replace(array('/^.*com\//', '/\//'), array('', '-'), $url);
      }
      
      echo "[action] {$action}\n";
      echo "\n";
      echo "[url] {$url}\n";
      echo "  [tmp_filename] {$tmp_filename}\n";
      echo "\n";
      echo "--------------------------------------------------\n";
      
      $ids = array();
      
      $page = 1;
      while (TRUE) {
        if ($page < 2) {
          $_url = "{$url}";
        } else {
          $_url = "{$url}{$glue}page={$page}";
        }
        $html_path = RT_TMP_ROOT . "{$tmp_filename}.p{$page}.html";
        $options = array(
          'retry'      => 2,
          'cache_path' => $html_path,
          'cache_time' => /**/ NULL /*/ 1 /**/,
        );
        $html = Tool::getHtml($_url, $options);
        if (!$html) {
          $page += 1;
          continue;
        }
        
        $match = preg_match_all('/href="\/(\d+)".*class="video-thumb"/i', $html, $matches);
        $ids_count = count($matches[1]);
        if (!$match || $ids_count == 0) {
          break;
        }
        
        Console::out("[INFO] {$ids_count} ids found!", OUTPUT_STDOUT, array('indent' => 8, 'eol' => "\n"));
        
        foreach ($matches[1] as $id) {
          $ids[] = $id;
        }
        
        $match_end = preg_match_all('/( class="navigate notActiveNextLink"| id="navNext"){2}/i', $html);
        if ($match_end) {
          Console::out("[END]", OUTPUT_STDOUT, array('indent' => 6, 'bol' => "\n", 'eol' => "\n"));
          break;
        }
        
        if ($limit > 0 && $page >= $limit) {
          break;
        }
        
        $page += 1;
      }
      
      Console::out("", OUTPUT_STDOUT, array('indent' => 2, 'eol' => "\n"));
      Console::out("--------------------------------------------------", OUTPUT_STDOUT, array('indent' => 2, 'eol' => "\n"));
      Console::out("[{$action}]", OUTPUT_STDOUT, array('indent' => 2, 'eol' => "\n"));
      Console::out("--------------------------------------------------", OUTPUT_STDOUT, array('indent' => 2, 'eol' => "\n"));
      
      $this->_batchAction($action, $ids);
    }
    
    // --------------------------------------------------
    
    public function info() {
      $src = $this->getSrc();
      $this->_batchAction('info', $src);
    }
    
    // --------------------------------------------------
    
    public function update() {
      $src = $this->getSrc();
      $this->_batchAction('update', $src);
    }
    
    // --------------------------------------------------
    
    public function state() {
      $state = array();
      
      $specified_status = ParamsParser::getOption(1);
      
      $objects = RT::findAllByConds(array());
      foreach ($objects as $object) {
        $status = $object->status;
        if ($specified_status && $specified_status != $status) continue;
        if (!isset($state[$status])) {
          $state[$status] = array();
        }
        $state[$status][$object->id] = $object;
      }
      
      foreach ($state as $status => $objects) {
        ksort($objects);
        $count = count($objects);
        Console::out(sprintf("%-10s count: %d", "[{$status}]", "{$count}"), OUTPUT_STDOUT, array('bol' => "  ", 'eol' => "\n"));
        if ($this->verbose) {
          foreach ($objects as $object) {
            $object->info(FALSE, $this->update);
          }
          Console::out("", OUTPUT_STDOUT, array('bol' => "  ", 'eol' => "\n"));
        }
      }
    }
    
    // --------------------------------------------------
    
    public function loop() {
      $action = ParamsParser::getOption(1);
      if (!in_array($action, array('info', 'update', 'add', 'retry', 'bad', 'repeat', 'private', 'reset'))) {
        $this->help();
        return;
      }
      
      while (TRUE) {
        $line = readline("\n  [INPUT] {$action}: ");
        if (in_array($line, array('q', 'Q'))) break;
        
        $this->_batchAction($action, $line);
      }
    }
    
    // --------------------------------------------------
    
    public function run() {
      $break_when_done = !ParamsParser::getParam('t', FALSE);
      
      while (TRUE) {
        $rt = RT::findByConds(array('status' => 'todo'));
        if ($rt) {
          $rt->exec();
          continue;
        }
        if ($break_when_done) {
          Console::out("[DONE]", OUTPUT_STDOUT, array('indent' => 2, 'bol' => "\n", 'eol' => "\n"));
          break;
        }
        
        Console::out("sleep", OUTPUT_STDOUT, array('indent' => 6));
        for ($i=0; $i<5; $i++) {
          Console::out(".", OUTPUT_STDOUT);
          sleep(1);
        }
        Console::out(".", OUTPUT_STDOUT, array('eol' => "\n"));
      }
    }
    
    
    
    // --------------------------------------------------
    
    public function help() {
      $msg =
      "[Usage] ./rt-operation.php [loop] <action>\n" .
      "\n" .
      "  action:\n" .
      "    \n" .
      "    help\n" .
      "    \n" .
      "    info    <number>   \n" .
      "    update  <number>   \n" .
      "    add     <number>   \n" .
      "    retry   <number>   \n" .
      "    bad     <number>   \n" .
      "    repeat  <number>   \n" .
      "    privat  <number>   \n" .
      "    reset   <number>   \n" .
      "    \n" .
      "    edit    <number>   \n" .
      "    \n" .
      "    preview <number>   \n" .
      "    \n" .
      "    category <action> <url> [--limit <number>]\n" .
      "    \n" .
      "    state [status]\n" .
      "    \n" .
      "    run       \n" .
      "";
      Console::out(Tool::indent($msg, 2), OUTPUT_STDOUT, array('eol' => "\n"));
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    
  }
?>
