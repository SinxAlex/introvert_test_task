<?php

require(__DIR__ . '/vendor/autoload.php');
use IntrovertTest\IntrovertTest;

$test=new IntrovertTest('2025-01-01','2025-05-10');
echo $test->renderHtml();