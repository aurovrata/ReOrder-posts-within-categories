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
  function debug_msg($message,$prefix='') {
      if (true === WP_GURUS_DEBUG) {
        global $debug_msg_last_line,$debug_msg_last_file;
          $backtrace = debug_backtrace();
          $file = $backtrace[0]['file'];
          $files = explode('/',$file);
          $dirs = explode('/',plugin_dir_path( __FILE__ ));
          $files = array_diff($files,$dirs);
          $file = implode('/',$files);
          $line = $backtrace[0]['line'];
          if($file != $debug_msg_last_file && $line != $debug_msg_last_line){
            error_log("DEBUG_MSG: [".$line."]./".$file);
            $debug_msg_last_file=$file;
            $debug_msg_last_line=$line;
          }else{
            //error_log("CF7_2_POST: ");
          }
          if (is_array($message) || is_object($message)) {
              error_log("          + ".$prefix.print_r($message, true));
          } else {
              error_log("          + ".$prefix.$message);
          }
      }
  }
} ?>
