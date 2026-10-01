<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminDashboardController
{
    public function index(): View
    {
        return view('app', ['page' => 'admin/dashboard', 'props' => ['title' => 'Dashboard']]);
    }

    public function section(string $section): View
    {
        $titles = [
            'promotions' => 'Info Promosi',
            'achievements' => 'Achievement',
            'live-hosts' => 'Jadwal Host Live',
            'birthdays' => 'Birthday',
            'hosts' => 'Daftar Host',
            'channels' => 'Channel Live',
            'weekly-meetings' => 'Weekly Meeting',
            'stream-links' => 'Link Streaming',
            'display-preview' => 'Preview Display',
        ];

        abort_unless(isset($titles[$section]), 404);

        return view('app', [
            'page' => $section === 'display-preview' ? 'admin/preview' : 'admin/resource',
            'props' => ['title' => $titles[$section], 'resource' => $section],
        ]);
    }

    public function liveHostBoard(): View
    {
        return view('app', ['page' => 'admin/live-hosts', 'props' => ['title' => 'Jadwal Host Live']]);
    }

    public function create(string $resource): View
    {
        return $this->form($resource, null);
    }

    public function edit(int $id, string $resource): View
    {
        return $this->form($resource, $id);
    }

    private function form(string $resource, ?int $id): View
    {
        $titles = ['promotions' => 'Info Promosi', 'achievements' => 'Achievement', 'birthdays' => 'Birthday', 'hosts' => 'Daftar Host', 'channels' => 'Channel Live', 'weekly-meetings' => 'Weekly Meeting', 'stream-links' => 'Link Streaming'];
        abort_unless(isset($titles[$resource]), 404);

        return view('app', ['page' => 'admin/form', 'props' => [
            'title' => $titles[$resource], 'resource' => $resource, 'recordId' => $id,
        ]]);
    }
}
