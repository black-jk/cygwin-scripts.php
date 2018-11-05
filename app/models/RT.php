<?php
  if (defined('INC_CLASS_RT_MODEL')) return;
  define('INC_CLASS_RT_MODEL', TRUE);
  
  // ====================================================================================================
  
  define('RT_ROOT',          "/cygdrive/d/temp/server/R-T/");
  define('RT_TMP_ROOT',      RT_ROOT . "tmp/");
  define('RT_APP_DATA_ROOT', RT_ROOT . "AppData/");
  define('RT_INCOMING_ROOT', RT_ROOT . "Incoming/");
  define('RT_BAD_ROOT',      RT_ROOT . "bad/");
  
  /*
  ### http://videotools.12pings.net/#!form/getvideo
  ### http://dreamway.blog.51cto.com/1281816/1151886
  */
  define('RT_URL_FORMAT',    "https://www.redtube.com/%d");
  
  // ====================================================================================================
  
  class RT {
    
    protected static $_storage;
    
    public static function getStorage() {
      if (RT::$_storage) {
        RT::$_storage->read();
      } else {
        RT::$_storage = new Storage("R-T", TRUE, RT_APP_DATA_ROOT);
        if (!file_exists(RT::$_storage->getFilePath())) {
          RT::$_storage->save();
        }
      }
      return RT::$_storage;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public static function findById($id) {
      $storage = RT::getStorage();
      
      $data = $storage->data[$id];
      if ($data) {
        $rt = new RT($data);
      } else {
        $rt = NULL;
      }
      return $rt;
    }
    
    // --------------------------------------------------
    
    public static function findByConds($conds) {
      $objects = RT::findAllByConds($conds, 1);
      return $objects[0];
    }
    
    // --------------------------------------------------
    
    public static function findAllByConds($conds, $limit = 0) {
      $storage = RT::getStorage();
      
      $objects = array();
      foreach ($storage->data as $id => $data) {
        if (RT::_compare($data, $conds)) {
          $objects[] = new RT($data);
        }
        if ($limit > 0 && count($objects) >= $limit) {
          break;
        }
      }
      return $objects;
    }
    
    // --------------------------------------------------
    
    protected static function _compare($data, $conds) {
      foreach ($conds as $key => $value) {
        if ($data[$key] != $value) {
          return FALSE;
        }
      }
      return TRUE;
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    public static function clearTMP() {
      Console::out("[RT::clearTMP()] running...", OUTPUT_STDOUT, array('bol' => "  ", 'eol' => ""));
      $cmd = "find " . RT_TMP_ROOT . " -name '*.html' -cmin +1440 -exec rm {} \;";
      Console::out(" done", OUTPUT_STDOUT, array('bol' => "", 'eol' => "\n\n"));
      exec($cmd);
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public static $FILE_STATUS_TITLES = array(
      '-1' => 'unknow',
      '0'  => 'missing',
      '1'  => 'good',
      '2'  => 'repeat',
    );
    
    
    
    // ====================================================================================================
    
    function __construct($data = NULL) {
      if ($data) {
        $this->setByArray($data);
      }
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public $id;
    public $status = 'new';
    public $file_status = -1; // -1: unknow  0: not exist  1: exist  2: repeat
    
    public $file_type = "mp4"; // flv
    
    public $log = '';
    public $created_at;
    
    // --------------------------------------------------
    
    public $file_path = '';
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Methods]
    // ----------------------------------------------------------------------------------------------------
    
    public function setByArray($data) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->$key = $value;
        }
      }
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    public function toArray() {
      $data = array();
      foreach (array('id', 'status', 'file_status', 'log', 'created_at') as $key) {
        $data[$key] = $this->$key;
      }
      return $data;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function save() {
      if (!$this->id) {
        Console::out("[RT::save()] Missing id!", OUTPUT_STDOUT, array('bol' => "    ", 'eol' => "\n"));
        return;
      }
      
      if (!$this->created_at) {
        $this->created_at = Tool::now();
      }
      
      $storage = RT::getStorage();
      $storage->data[$this->id] = $this->toArray();
      $storage->save();
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    public function destroy() {
      Console::out("[DESTROY] [RT #{$this->id}] [status: {$this->status}] [file_status: {$this->file_status}] [{$this->created_at}]\n  [log]\n" . Tool::indent($this->log, 4), OUTPUT_STDOUT | OUTPUT_LOG_INFO, array('bol' => "    ", 'eol' => "\n"));
      
      $storage = RT::getStorage();
      unset($storage->data[$this->id]);
      $storage->save();
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Tools]
    // ----------------------------------------------------------------------------------------------------
    
    private static function _parseTitle($html) {
      $title_match = preg_match('/<title>(.*) (\||&#124;) Redtube/', $html, $match);
      $title = preg_replace(
        array('/\?/', '/ *\/ */', ),
        array('？',   ' - ',      ),
        $match[1]);
      
      $title = htmlspecialchars_decode(trim($title));
      
      $title = preg_replace(
        array("/&/",   "/[\\/:*?\"<>|]/"),
        array("[AND]", "_"),
        $title);
      
      return $title;
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    private static function _parseMovie($html, $id) {
      // https://ev.rdtcdn.com/media/videos/201210/08/284173/480p_600k_284173.mp4?validfrom=1541520491&validto=1541527691&rate=65k&burst=2700k&hash=ugE%2FUJdR8Ai6uguYqFzRV%2FS2zqc%3D
      preg_match('/playervars(.*)$/m', $html, $match);
      $src = $match[1];
      $src = preg_replace("/\\\\/m", "", $src);
      $src = preg_replace("/http/m", "\nhttp", $src);
      $src = preg_replace("/\".*\$/m", "", $src);
      
      preg_match('/^(https:.*videos.*\.mp4.*)$/m', $src, $match);
      $src = $match[1];
      
      if (!$src) {
        // <p class="unavailable_text">This video has been removed. Please enjoy one of our many other videos.</p>
        preg_match('/<p.*"unavailable_text">(.*)<\/p>/', $html, $match);
        $msg = $match[1];
        if ($msg) {
          throw new Exception("[" . __CLASS__ . "] {$msg}", 2);
        }
      }
      return $src;
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    private static function _parseThumb($html) {
      // https://ei-ph.rdtcdn.com/videos/201809/03/181386321/original/(m=eaAaGwFb)(mh=M_Jyd1ki29GeL_Pc)0.jpg
      // https://ci.rdtcdn.com/m=eaAaGwFb/media/videos/201709/28/2493022/original/15.jpg
      preg_match('/playervars(.*)$/m', $html, $match);
      $src = $match[1];
      $src = preg_replace("/\\\\/m", "", $src);
      $src = preg_replace("/http/m", "\nhttp", $src);
      $src = preg_replace("/\".*\$/m", "", $src);
      
      preg_match('/^(https:.*videos.*original.*jpg.*)$/m', $src, $match);
      $src = $match[1];
      return $src;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function setStatus($status) {
      $origin_status = $this->status;
      
      // status: new todo doing success fail bad repeat private
      switch ($status) {
        case 'todo':
          if (in_array($this->status, array('repeat', 'bad'))) {
            Console::out("[RT #{$this->id}] [setStatus({$status})] [SKIP] '{$this->status}'", OUTPUT_STDOUT, array('bol' => "    ", 'eol' => "\n"));
            return;
          }
          if (in_array($this->status, array('success', 'doing'))) {
            Console::out("[RT #{$this->id}] [setStatus({$status})] [SKIP] '{$this->status}'", OUTPUT_STDOUT, array('bol' => "    ", 'eol' => "\n"));
            return;
          }
          $this->status = 'todo';
          break;
          
        case 'retry':
          $this->remove();
          $this->status = 'todo';
          break;
          
        case 'doing':
          $this->status = $status;
          break;
          
        case 'success':
          $this->update();
          if ($this->file_status < 1) {
            Console::out("[RT #{$this->id}] [setStatus({$status})] [ERROR] Missing file!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('bol' => "    ", 'eol' => "\n"));
            $status = 'fail';
          }
          $this->status = $status;
          break;
          
        case 'fail':
          $this->status = $status;
          break;
          
        case 'bad':
        case 'repeat':
        case 'private':
          $this->remove();
          $this->status = $status;
          break;
          
        default:
          Console::out("[RT #{$this->id}] [setStatus({$status})] [SKIP] Unknow status!", OUTPUT_STDOUT, array('bol' => "    ", 'eol' => "\n"));
          return;
          break;
      }
      
      if ($this->status != $origin_status) {
        $this->addLog("[SET] status: {$origin_status} -> {$this->status}");
      }
      $this->save();
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Execute]
    // ----------------------------------------------------------------------------------------------------
    
    public function exec() {
      switch ($this->status) {
        case 'todo':
          $this->get();
          break;
          
        case 'bad':
        case 'repeat':
          $this->remove();
          break;
          
        default:
          // do nothing
          break;
      }
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Get]
    // ----------------------------------------------------------------------------------------------------
    
    public function get() {
      
      // ------------------------------
      // [Check]
      // ------------------------------
      
      $this->update();
      if ($this->file_status > 0) {
        Console::out("[RT #{$this->id}] [get] [skip] file exist! ", OUTPUT_STDOUT | OUTPUT_LOG_INFO, array('bol' => "    ", 'eol' => "\n"));
        $this->setStatus('success');
        return;
      }
      
      
      // ------------------------------
      // [Begin]
      // ------------------------------
      
      $this->setStatus('doing');
      
      
      $url = $this->getUrl();
      
      Console::out("[RT #{$this->id}] [get]", OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('bol' => "    ", 'eol' => "\n"));
      
      
      // ------------------------------
      // [HTML]
      // ------------------------------
      
      $options = array(
        'cache_path' => RT_TMP_ROOT . "{$this->id}.html",
        'cache_time' => 5,
      );
      
      $html = Tool::getHtml($url, $options);
      if (!$html) {
        $this->setStatus('fail');
        return;
      }
      
      
      // ------------------------------
      // [Parse]
      // ------------------------------
      
      // title
      $title = self::_parseTitle($html);
      if (!$title) {
        Console::out("[html] get title fail!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('bol' => "\n      ", 'eol' => "\n"));
        $this->setStatus('fail');
        return;
      }
      
      // movie
      $movie = self::_parseMovie($html, $this->id);
      if (!$movie) {
        Console::out("[html] get movie fail!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('bol' => "\n      ", 'eol' => "\n"));
        $this->setStatus('fail');
        return;
      }
      
      
      // ------------------------------
      // [Download]
      // ------------------------------
      
      $movie_path = RT_INCOMING_ROOT . "{$this->id}_{$title}.{$this->file_type}";
      
      $retry = 3;
      
      $c = 0;
      while ($c++ < $retry) {
        Console::out("[OPERATION] getting movie ..." . ($c > 1 ? "(retry: {$c})" : ""), OUTPUT_STDOUT | OUTPUT_LOG_DEBUG, array('bol' => "      ", 'eol' => "\n"));
        
        $connections = 8;
        $_movie_path = preg_replace("/'/", "'\\''", $movie_path);
        $useg_agent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36";
        $cmd = "axel -q -v -n {$connections} -U '$useg_agent' -o '{$_movie_path}' '{$movie}'";
        // $cmd = "wget --progress=bar -U '$useg_agent' -O '{$_movie_path}' '{$movie}'";
        Console::out("[DOWNLOAD] {$cmd}", OUTPUT_STDOUT | OUTPUT_LOG_INFO, array('indent' => 8, 'bol' => "", 'eol' => "\n"));
        $result = system("{$cmd} | awk '{print \"            \" \$0}'");
        
        if ($result === FALSE) {
          Console::out("[ERROR] execute command fail!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('bol' => "\n      ", 'eol' => "\n"));
          continue;
        }
        
        if (!file_exists($movie_path)) {
          Console::out("[ERROR] missing {$this->file_type} file! '{$movie_path}'", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('bol' => "\n      ", 'eol' => "\n"));
          continue;
        }
        
        Console::out("[SUCCESS] download success! '{$movie_path}'", OUTPUT_STDOUT | OUTPUT_LOG_INFO, array('bol' => "\n      ", 'eol' => "\n"));
        break;
      }
      
      if (file_exists($movie_path)) {
        $this->setStatus('success');
      } else {
        Console::out("[FAIL] Missing file! '{$movie_path}'", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('bol' => "\n      ", 'eol' => "\n"));
        $this->setStatus('fail');
      }
    }
    
    // --------------------------------------------------
    
    public function getUrl() {
      $url = sprintf(RT_URL_FORMAT, $this->id);
      return $url;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Remove]
    // ----------------------------------------------------------------------------------------------------
    
    public function remove() {
      $files = $this->findFiles();
      foreach ($files as $file_path) {
        if (file_exists($file_path)) {
          unlink($file_path);
          $this->addLog("remove file: {$file_path}");
          $this->save();
          Console::out("[RT #{$this->id}] remove file: {$file_path}", OUTPUT_STDOUT | OUTPUT_LOG_INFO, array('bol' => "    ", 'eol' => "\n"));
        }
      }
      
      $this->update();
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function findFiles() {
      $files = array();
      
      $cmd = "find " . RT_ROOT . " -name '{$this->id}_*.{$this->file_type}'";
      exec($cmd, $files);
      
      return $files;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function addLog($log) {
      $now = Tool::now();
      $this->log .= "[{$now}] {$log}\n";
    }
    
    // --------------------------------------------------
    
    public function editLog() {
      $this->log = Tool::editText($this->log);
      $this->save();
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function preview($getThumb) {
      $url = $this->getUrl();
      
      $options = array(
        'cache_path' => RT_TMP_ROOT . "{$this->id}.html",
        'cache_time' => 5,
      );
      
      $html = Tool::getHtml($url, $options);
      if (!$html) {
        $this->setStatus('fail');
        return;
      }
      
      
      // ------------------------------
      // [Parse]
      // ------------------------------
      
      // title
      $title = self::_parseTitle($html);
      if (!$title) {
        Console::out("[preview] GET TITLE FAIL!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('indent' => 6, 'bol' => "", 'eol' => "\n"));
        return;
      }
      
      // movie
      $movie = self::_parseMovie($html, $this->id);
      if (!$movie) {
        Console::out("[preview] GET MOVIE FAIL!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('indent' => 6, 'bol' => "", 'eol' => "\n"));
        return;
      }
      
      $filename = "{$this->id}_{$title}.{$this->file_type}";
      
      // thumb
      $thumb = self::_parseThumb($html);
      if (!$thumb) {
        Console::out("[preview] GET THUMB FAIL!", OUTPUT_STDOUT | OUTPUT_LOG_ERROR, array('indent' => 6, 'bol' => "", 'eol' => "\n"));
        if ($getThumb) {
          return;
        }
      }
      if ($getThumb) {
        exec("mkdir -p " . RT_TMP_ROOT . "thumbs/");
        
        $options = array(
          'tmp_path' => RT_TMP_ROOT . "thumb_{$this->id}.jpg",
        );
        $dest = RT_TMP_ROOT . "thumbs/{$this->id}.jpg";
        Tool::getFile($thumb, $dest, $options);
      }
      
      // ------------------------------
      
      Console::out("", OUTPUT_STDOUT, array('indent' => 6, 'bol' => "", 'eol' => "\n"));
      Console::out("[title]    {$title}",    OUTPUT_STDOUT, array('indent' => 6, 'bol' => "", 'eol' => "\n"));
      Console::out("[thumb]    {$thumb}",    OUTPUT_STDOUT, array('indent' => 6, 'bol' => "", 'eol' => "\n"));
      Console::out("[filename] {$filename}", OUTPUT_STDOUT, array('indent' => 6, 'bol' => "", 'eol' => "\n"));
      Console::out("[source]   {$movie}",    OUTPUT_STDOUT, array('indent' => 6, 'bol' => "", 'eol' => "\n"));
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function update() {
      $files = $this->findFiles();
      $files_count = count($files);
      if ($files_count > 1) {
        $this->file_status = 2;
      } else
      if ($files_count > 0) {
        $this->file_status = 1;
      } else {
        $this->file_status = 0;
      }
      $this->save();
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
    public function info($detail = FALSE, $update = FALSE) {
      if ($update) {
        $this->update();
      }
      Console::out($this->toString($detail), OUTPUT_STDOUT, array('indent' => 4, 'eol' => ""));
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    public function toString($detail) {
      if ($detail) {
        $files = $this->findFiles();
        $detail_string =
          Tool::indent("\n", 2) .
          Tool::indent("[log]\n", 2) .
          Tool::indent("{$this->log}\n", 4) .
          Tool::indent("[files]\n", 2) .
          Tool::indent(implode("\n", $files), 4) .
          Tool::indent("\n", 2);
      } else {
        $detail_string = "";
      }
      
      $url = $this->getUrl();
      
      $file_status_title = RT::$FILE_STATUS_TITLES[$this->file_status];
      
      $string = sprintf("[RT %8s] [%-30s] [status: %7s] [file_status: %8s %4s] [{$this->created_at}]\n%s",
        "#{$this->id}",
        $url,
        "{$this->status}",
        "{$file_status_title}", "({$this->file_status})",
        $detail_string
      );
      
      return $string;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
  }
?>
