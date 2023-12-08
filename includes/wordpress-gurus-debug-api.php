<?php
/**
 * A debugging trace function used in the code and enabled with WPGURUS_DEBUG constant.
 *
 * @link       https://profiles.wordpress.org/aurovrata/
 * @since      2.0.0
 *
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

defined( 'WPGURUS_DEBUG' ) || define( 'WPGURUS_DEBUG', false );

if ( ! function_exists( 'wpg_debug' ) ) {
	if ( true === WPGURUS_DEBUG ) {
		$debug_msg_last_line = '';
		$debug_msg_last_file = '';
	}
	/**
	 * Error logging and notices
	 *
	 * @since 1.0.0
	 * @param mixed  $message to log if in debug mode, either a string or an object.
	 * @param string $prefix a string to prefix to the message.
	 * @param int    $trace the depth of function trace to print.
	 */
	function wpg_debug( $message, $prefix = '', $trace = 0 ) {
		if ( true === WPGURUS_DEBUG ) {
			global $debug_msg_last_line,$debug_msg_last_file;
			$backtrace = debug_backtrace();
			$file      = $backtrace[0]['file'];
			$files     = explode( '/', $file );
			$dirs      = explode( '/', plugin_dir_path( __FILE__ ) );
			$files     = array_diff( $files, $dirs );
			$file      = implode( '/', $files );
			$line      = $backtrace[0]['line'];
			$msg       = 'DEBUG_MSG: ' . PHP_EOL;
			if ( true === $trace || ( $file != $debug_msg_last_file && $line != $debug_msg_last_line ) ) {
				if ( true === $trace ) {
					$trace = count( $backtrace );
				}
				for ( $idx = $trace; $idx > 0; $idx-- ) {
					$msg .= '   [' . $backtrace[ $idx ]['line'] . ']->/' . $backtrace[ $idx ]['file'] . PHP_EOL;
				}
				$msg                .= '   [' . $line . ']./' . $file . PHP_EOL;
				$debug_msg_last_file = $file;
				$debug_msg_last_line = $line;
			}

			if ( is_array( $message ) || is_object( $message ) ) {
				$msg .= '          + ' . $prefix . print_r( $message, true );
			} else {
				$msg .= '          + ' . $prefix . $message;
			}
			error_log( $msg );
		}
	}
}
