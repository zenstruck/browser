<?php

use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Browser\Tests\Fixture\Kernel;

require \dirname(__DIR__).'/../../vendor/autoload.php';

$kernel = new Kernel('test', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
