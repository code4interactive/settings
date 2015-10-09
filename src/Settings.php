<?php
namespace Code4\Settings;

class Settings {

    protected $settings;

    public function init($configsToLoad, $userId = null, $prefix = '', $lazyLoad = true) {
        $this->settings = new SettingsFactory($configsToLoad, $userId, $prefix, $lazyLoad);
    }

    public function __call($name, $arg) {
        //return call_user_func($this->settings->$name(), $arg);
        return call_user_func_array(array($this->settings, $name), $arg);
    }


}