<?php

namespace Code4\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class SettingsModel extends Model
{
    protected $fillable = [
        'setting_name', 'user_id', 'settings'
    ];

    protected $table = 'settings';

    public $timestamps = false;

    public function user() {
        return $this->hasOne('users', 'id', 'user_id');
    }
}
