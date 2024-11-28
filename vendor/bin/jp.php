#!/usr/bin/env php
<?php

namespace Composer;

$GLOBALS['_composer_bin_dir'] = __DIR__;
$GLOBALS['_composer_autoload_path'] = __DIR__ . '/..' . '/autoload.php';

if (PHP_VERSION_ID < 80000) {
  if (!class_exists('Composer\BinProxyWrapper')) {
    /**
     * @internal
     */
    final class BinProxyWrapper {
      private $handle;
      private $position;
      private $realpath;

      /**
       *
       */
      public function stream_open($path, $mode, $options, &$opened_path) {
        // Get rid of phpvfscomposer:// prefix for __FILE__ & __DIR__ resolution.
        $opened_path = substr($path, 17);
        $this->realpath = realpath($opened_path) ?: $opened_path;
        $opened_path = $this->realpath;
        $this->handle = fopen($this->realpath, $mode);
        $this->position = 0;

        return (bool) $this->handle;
      }

      /**
       *
       */
      public function stream_read($count) {
        $data = fread($this->handle, $count);

        if ($this->position === 0) {
          $data = preg_replace('{^#!.*\r?\n}', '', $data);
        }

        $this->position += strlen($data);

        return $data;
      }

      /**
       *
       */
      public function stream_cast($castAs) {
        return $this->handle;
      }

      /**
       *
       */
      public function stream_close() {
        fclose($this->handle);
      }

      /**
       *
       */
      public function stream_lock($operation) {
        return $operation ? flock($this->handle, $operation) : TRUE;
      }

      /**
       *
       */
      public function stream_seek($offset, $whence) {
        if (0 === fseek($this->handle, $offset, $whence)) {
          $this->position = ftell($this->handle);
          return TRUE;
        }

        return FALSE;
      }

      /**
       *
       */
      public function stream_tell() {
        return $this->position;
      }

      /**
       *
       */
      public function stream_eof() {
        return feof($this->handle);
      }

      /**
       *
       */
      public function stream_stat() {
        return [];
      }

      /**
       *
       */
      public function stream_set_option($option, $arg1, $arg2) {
        return TRUE;
      }

      /**
       *
       */
      public function url_stat($path, $flags) {
        $path = substr($path, 17);
        if (file_exists($path)) {
          return stat($path);
        }

        return FALSE;
      }

    }
  }

  if (
        (function_exists('stream_get_wrappers') && in_array('phpvfscomposer', stream_get_wrappers(), TRUE))
        || (function_exists('stream_wrapper_register') && stream_wrapper_register('phpvfscomposer', 'Composer\BinProxyWrapper'))
    ) {
    return include "phpvfscomposer://" . __DIR__ . '/..' . '/mtdowling/jmespath.php/bin/jp.php';
  }
}

return include __DIR__ . '/..' . '/mtdowling/jmespath.php/bin/jp.php';