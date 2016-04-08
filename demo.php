<?php
require 'vendor/autoload.php';

$uuid = new CustomUUID(1,1);


echo $uuid->init();



$i=0;
while($i<100) {
	$i++;
	echo $uuid->generate();
	echo PHP_EOL;
}
echo PHP_EOL;