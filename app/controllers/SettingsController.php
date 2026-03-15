<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Settings;

class SettingsController extends Controller
{
    public function index(): void
    {
        $s = new Settings();
        $this->view('settings/index', ['title' => 'Configurações', 'settings' => [
            'company_name' => $s->get('company_name'),
            'company_phone' => $s->get('company_phone'),
            'company_instagram' => $s->get('company_instagram'),
            'points_per_real' => $s->get('points_per_real', '1'),
            'crediario_due_days' => $s->get('crediario_due_days', '30'),
            'pix_entra_no_caixa' => $s->get('pix_entra_no_caixa', '1'),
        ]]);
    }

    public function save(): void
    {
        Auth::requireRole(['ADMIN','GERENTE']);
        validate_csrf();
        $s = new Settings();
        foreach (['company_name','company_phone','company_instagram','points_per_real','crediario_due_days','pix_entra_no_caixa'] as $key) {
            $s->set($key, (string)($_POST[$key] ?? ''));
        }
        flash('success', 'Configurações salvas');
        $this->redirect('/settings');
    }
}
