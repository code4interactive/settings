<?php

namespace Code4\Settings;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider {

    public function register() {
        $this->app->singleton('settings', function($app) {
           return new SettingsFactory([]);
        });
    }

    public function boot() {
        $this->publishes([ __DIR__ . '/migrations' => base_path('database/migrations')], 'migrations');
        $this->publishes([ __DIR__ . '/config' => base_path('config')], 'config');
    }

    public function terminate() {
        $this->app->make('settings')->save();
    }

}