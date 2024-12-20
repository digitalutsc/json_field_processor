<?php

/**
 * @file
 */

use Composer\XdebugHandler\XdebugHandler;
use JmesPath\CompilerRuntime;
use JmesPath\Env;

?>
#!/usr/bin/env php
<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
  require __DIR__ . '/../vendor/autoload.php';
}
elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
  require __DIR__ . '/../../../autoload.php';
}
else {
  throw new RuntimeException('Unable to locate autoload.php file.');
}

$xdebug = new XdebugHandler('perf.php');
$xdebug->check();
unset($xdebug);

$dir = $argv[1] ?? __DIR__ . '/../tests/compliance/perf';
is_dir($dir) or die('Dir not found: ' . $dir);
// Warm up the runner.
Env::search('foo', []);

$total = 0;
foreach (glob($dir . '/*.json') as $file) {
  $total += runSuite($file);
}
echo "\nTotal time: {$total}\n";

/**
 *
 */
function runSuite($file) {
  $contents = file_get_contents($file);
  $json = json_decode($contents, TRUE);
  $total = 0;
  foreach ($json as $suite) {
    foreach ($suite['cases'] as $case) {
      $total += runCase(
            $suite['given'],
            $case['expression'],
            $case['name']
        );
    }
  }
  return $total;
}

/**
 *
 */
function runCase($given, $expression, $name) {
  $best = 99999;
  $runtime = Env::createRuntime();

  for ($i = 0; $i < 100; $i++) {
    $t = microtime(TRUE);
    $runtime($expression, $given);
    $tryTime = (microtime(TRUE) - $t) * 1000;
    if ($tryTime < $best) {
      $best = $tryTime;
    }
    if (!getenv('CACHE')) {
      $runtime = Env::createRuntime();
      // Delete compiled scripts if not caching.
      if ($runtime instanceof CompilerRuntime) {
        array_map('unlink', glob(sys_get_temp_dir() . '/jmespath_*.php'));
      }
    }
  }

  printf("time: %07.4fms name: %s\n", $best, $name);

  return $best;
}
