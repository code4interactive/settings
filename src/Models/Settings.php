<?php

namespace Code4\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $fillable = [
        'setting_name', 'user_id', 'settings'
    ];

    public function user() {
        return $this->hasOne('users', 'id', 'user_id');
    }
}
