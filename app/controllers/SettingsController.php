<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Product;
use App\Models\Settings;

class SettingsController extends Controller
{
    public function index(): void
    {
        $s = new Settings();
        $p = new Product();
        $this->view('settings/index', ['title' => 'Configurações', 'settings' => [
            'company_name' => $s->get('company_name'),
            'company_phone' => $s->get('company_phone'),
            'company_instagram' => $s->get('company_instagram'),
            'points_per_real' => $s->get('points_per_real', '1'),
            'crediario_due_days' => $s->get('crediario_due_days', '30'),
            'pix_entra_no_caixa' => $s->get('pix_entra_no_caixa', '1'),
        ],
            'products' => $p->all(),
            'addonGroups' => $p->addonGroups(),
            'addons' => $p->allAddons(),
        ]);
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

    public function storeAddonGroup(): void
    {
        validate_csrf();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash('danger', 'Nome do grupo é obrigatório.');
            $this->redirect('/settings');
        }

        $ok = (new Product())->createAddonGroup($name);
        flash($ok ? 'success' : 'danger', $ok ? 'Grupo de adicionais criado.' : 'Erro ao criar grupo.');
        $this->redirect('/settings');
    }

    public function storeAddon(): void
    {
        validate_csrf();
        $name = trim($_POST['name'] ?? '');
        $groupId = (int)($_POST['addon_group_id'] ?? 0);

        if ($name === '' || $groupId <= 0) {
            flash('danger', 'Preencha nome e grupo do adicional.');
            $this->redirect('/settings');
        }

        $ok = (new Product())->createAddon($groupId, $name, (float)($_POST['price'] ?? 0));
        flash($ok ? 'success' : 'danger', $ok ? 'Adicional cadastrado.' : 'Erro ao cadastrar adicional.');
        $this->redirect('/settings');
    }

    public function attachAddon(): void
    {
        validate_csrf();

        $ok = (new Product())->attachAddonToProduct((int)($_POST['product_id'] ?? 0), (int)($_POST['addon_id'] ?? 0));
        flash($ok ? 'success' : 'danger', $ok ? 'Adicional vinculado ao produto.' : 'Erro ao vincular adicional.');
        $this->redirect('/settings');
    }
}
