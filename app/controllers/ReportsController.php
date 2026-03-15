<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Report;

class ReportsController extends Controller
{
    public function index(): void
    {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-d');
        $m = new Report();
        $this->view('reports/index', [
            'title' => 'Relatórios',
            'start' => $start,
            'end' => $end,
            'salesPeriod' => $m->salesByPeriod($start, $end),
            'salesProduct' => $m->salesByProduct($start, $end),
            'salesCategory' => $m->salesByCategory($start, $end),
            'salesPayment' => $m->salesByPayment($start, $end),
            'receivables' => $m->receivablesOpen(),
            'lowStock' => $m->lowStock(),
            'dre' => $m->dre($start, $end),
        ]);
    }
}
