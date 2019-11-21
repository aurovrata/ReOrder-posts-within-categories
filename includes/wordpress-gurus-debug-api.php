<?php
/**
* Error logging and notices
* @since 1.0.0
* @var string $message to log if in debug mode
*/
defined('WP_GURUS_DEBUG') || define('WP_GURUS_DEBUG', false);

if( !function_exists('debug_msg') ){
  if (true === WP_GURUS_DEBUG) {
     $debug_msg_last_line='';
     $debug_msg_last_file='';
   }
  function debug_msg($message, $prefix='', $trace=0) {
      if (true === WP_GURUS_DEBUG) {
        global $debug_msg_last_line,$debug_msg_last_file;
          $backtrace = debug_backtrace();
          $file = $backtrace[0]['file'];
          $files = explode('/',$file);
          $dirs = explode('/',plugin_dir_path( __FILE__ ));
          $files = array_diff($files,$dirs);
          $file = implode('/',$files);
          $line = $backtrace[0]['line'];
          $msg='DEBUG_MSG: '.PHP_EOL;
          if(true===$trace || ($file != $debug_msg_last_file && $line != $debug_msg_last_line)){
            if($trace===true) $trace = sizeof($backtrace);
            for($idx=$trace; $idx>0; $idx--){
              $msg.="   [".$backtrace[$idx]['line']."]->/".$backtrace[$idx]['file'].PHP_EOL;
            }
            $msg.= "   [".$line."]./".$file.PHP_EOL;
            $debug_msg_last_file=$file;
            $debug_msg_last_line=$line;
          }

          if (is_array($message) || is_object($message)) {
              $msg.="          + ".$prefix.print_r($message, true);
          } else {
              $msg.="          + ".$prefix.$message;
          }
          error_log($msg);
      }
  }
} ?>
