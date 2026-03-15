<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Dashboard;

class DashboardController extends Controller
{
    public function index(): void
    {
        $model = new Dashboard();
        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $model->stats(),
            'salesByDay' => $model->chartSalesByDay(),
        ]);
    }
}
