--TEST--
Size limit check using FileSystem cache engine
--FILE--
<?php
namespace cs;
include __DIR__.'/../custom_loader.php';
Core::instance_stub([
	'cache_engine'	=> 'FileSystem',
	'cache_size'	=> 5 / 1024 / 1024
]);
$Cache	= Cache::instance();
if (!$Cache->set('test', 5)) {
	die('::set() failed');
}
if (!$Cache->set('test', '111')) {
	die('second ::set() method does not work');
}
if ($Cache->set('test', '111111') !== false) {
	die('Size limit does not works');
}
echo 'Done';
?>
--EXPECT--
Done
--CLEAN--
<?php
include __DIR__.'/../custom_loader.php';
exec('rm -r '.CACHE.'/*');
?>
