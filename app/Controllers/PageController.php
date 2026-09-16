<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Setting;

final class PageController
{
    public function how(): void
    {
        View::render('pages/how', [
            'title' => 'Как это работает — WL Sales',
            'description' => 'Четыре шага: аккаунт, лот, оплата, доступы в кабинете.',
        ]);
    }

    public function faq(): void
    {
        View::render('pages/faq', [
            'title' => 'FAQ — WL Sales',
            'description' => 'Белый IP vs NAT, аренда и выкуп, замена IP, что выдаётся после оплаты.',
        ]);
    }

    public function contacts(): void
    {
        View::render('pages/contacts', [
            'title' => 'Контакты — WL Sales',
            'telegram' => Setting::get('site_telegram'),
            'email' => Setting::get('site_email'),
        ]);
    }

    public function offer(): void
    {
        View::render('legal/offer', [
            'title' => 'Публичная оферта — WL Sales',
            'html' => Setting::get('offer_html', '<p>Оферта будет опубликована.</p>'),
        ]);
    }

    public function privacy(): void
    {
        View::render('legal/privacy', [
            'title' => 'Политика персональных данных — WL Sales',
            'html' => Setting::get('privacy_html', '<p>Политика будет опубликована.</p>'),
        ]);
    }
}
