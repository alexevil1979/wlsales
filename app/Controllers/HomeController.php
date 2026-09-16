<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Product;

final class HomeController
{
    public function index(): void
    {
        View::render('home/index', [
            'title' => 'WL Sales — серверы с публичным IP',
            'description' => 'Аренда и выкуп VPS/выделенных серверов с проверенными российскими публичными IP. Selectel, Timeweb, MWS, Yandex Cloud.',
            'products' => Product::featured(6),
            'vendors' => Product::vendors(),
            'locations' => Product::locations(),
        ]);
    }
}
