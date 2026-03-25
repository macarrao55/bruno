<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CashController;
use App\Controllers\CustomersController;
use App\Controllers\DashboardController;
use App\Controllers\FinanceController;
use App\Controllers\ModuleController;
use App\Controllers\OrdersController;
use App\Controllers\PdvController;
use App\Controllers\ProductsController;
use App\Controllers\ReportsController;
use App\Controllers\SettingsController;
use App\Controllers\UsersController;
use App\Core\Auth;

$auth = [fn() => Auth::requireAuth()];

$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout', $auth);
$router->get('/', 'DashboardController@index', $auth);
$router->get('/dashboard', 'DashboardController@index', $auth);

$router->get('/users', 'UsersController@index', $auth);
$router->post('/users/store', 'UsersController@store', $auth);

$router->get('/products', 'ProductsController@index', $auth);
$router->post('/products/store', 'ProductsController@store', $auth);
$router->post('/products/update', 'ProductsController@update', $auth);

$router->get('/customers', 'CustomersController@index', $auth);
$router->post('/customers/store', 'CustomersController@store', $auth);
$router->post('/customers/update', 'CustomersController@update', $auth);

$router->get('/cash', 'CashController@index', $auth);
$router->post('/cash/open', 'CashController@open', $auth);
$router->post('/cash/close', 'CashController@close', $auth);

$router->get('/pdv', 'PdvController@index', $auth);
$router->get('/pdv/addons', 'PdvController@addons', $auth);
$router->post('/pdv/customers/quick-store', 'PdvController@quickCustomerStore', $auth);
$router->post('/pdv/checkout', 'PdvController@checkout', $auth);

$router->get('/orders', 'OrdersController@index', $auth);
$router->post('/orders/status', 'OrdersController@status', $auth);
$router->get('/orders/print/client', 'OrdersController@printClient', $auth);
$router->get('/orders/print/kitchen', 'OrdersController@printKitchen', $auth);

$router->get('/finance', 'FinanceController@index', $auth);
$router->post('/finance/receive', 'FinanceController@receive', $auth);

$router->get('/reports', 'ReportsController@index', $auth);

$router->get('/settings', 'SettingsController@index', $auth);
$router->post('/settings/save', 'SettingsController@save', $auth);
$router->post('/settings/addon-groups/store', 'SettingsController@storeAddonGroup', $auth);
$router->post('/settings/addons/store', 'SettingsController@storeAddon', $auth);
$router->post('/settings/addons/attach', 'SettingsController@attachAddon', $auth);

$router->get('/dre', 'ModuleController@dre', $auth);
$router->get('/conciliation', 'ModuleController@conciliation', $auth);
$router->get('/stock', 'ModuleController@stock', $auth);
$router->post('/stock/store', 'ModuleController@stockStore', $auth);
$router->post('/stock/update', 'ModuleController@stockUpdate', $auth);
$router->post('/stock/delete', 'ModuleController@stockDelete', $auth);
$router->post('/stock/recipes/store', 'ModuleController@stockRecipeStore', $auth);
$router->post('/stock/recipes/delete', 'ModuleController@stockRecipeDelete', $auth);
$router->get('/loyalty', 'ModuleController@loyalty', $auth);
$router->get('/marketing', 'ModuleController@marketing', $auth);
$router->get('/employees', 'ModuleController@employees', $auth);
$router->get('/audit', 'ModuleController@audit', $auth);
$router->get('/backup', 'ModuleController@backup', $auth);
