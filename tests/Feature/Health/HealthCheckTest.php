<?php

declare(strict_types=1);

it('reports healthy on /up', function (): void {
    $this->get('/up')->assertOk();
});

it('serves the RTL Arabic home page', function (): void {
    $this->artisan('migrate');

    $this->get('/')
        ->assertOk()
        ->assertSee('dir="rtl"', escape: false)
        ->assertSee('lang="ar"', escape: false)
        ->assertSee(__('site.name'));
});
