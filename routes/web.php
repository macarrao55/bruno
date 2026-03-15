<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CustomersController;
use App\Controllers\DashboardController;
use App\Controllers\ModuleController;
use App\Controllers\OrdersController;
use App\Controllers\PdvController;
use App\Controllers\ProductsController;
use App\Core\Auth;

/** @var \App\Core\Router $router */

$auth = [fn() => Auth::requireAuth()];

$router->get('/', 'DashboardController@index', $auth);
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout', $auth);
$router->get('/dashboard', 'DashboardController@index', $auth);

$router->get('/products', 'ProductsController@index', $auth);
$router->post('/products/store', 'ProductsController@store', $auth);

$router->get('/customers', 'CustomersController@index', $auth);
$router->post('/customers/store', 'CustomersController@store', $auth);

$router->get('/pdv', 'PdvController@index', $auth);
$router->post('/pdv/checkout', 'PdvController@checkout', $auth);

$router->get('/orders', 'OrdersController@index', $auth);
$router->post('/orders/status', 'OrdersController@updateStatus', $auth);
$router->get('/orders/print', 'OrdersController@print80mm', $auth);

$router->get('/cash', 'ModuleController@cash', $auth);
$router->get('/finance', 'ModuleController@finance', $auth);
$router->get('/dre', 'ModuleController@dre', $auth);
$router->get('/conciliation', 'ModuleController@conciliation', $auth);
$router->get('/stock', 'ModuleController@stock', $auth);
$router->get('/loyalty', 'ModuleController@loyalty', $auth);
$router->get('/marketing', 'ModuleController@marketing', $auth);
$router->get('/employees', 'ModuleController@employees', $auth);
$router->get('/reports', 'ModuleController@reports', $auth);
$router->get('/settings', 'ModuleController@settings', $auth);
$router->get('/audit', 'ModuleController@audit', $auth);
$router->get('/backup', 'ModuleController@backup', $auth);
