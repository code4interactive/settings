<?php

namespace Code4\Settings;

use Code4\Settings\Models;

class SettingsBlock {

    protected $user_id = 0;

    protected $defaultSettings = [];
    protected $dbSettings = [];

    protected $settings = [];
    protected $settingsModel;

    private $syncDefaultConfig = false;

    private $changed = false;
    private $initialized = false;

    public function __construct($setting_name, $defaultSettings = [], $user_id = null, $syncDefaultConfig = false) {
        $this->setting_name = $setting_name;
        $this->defaultSettings = $defaultSettings;
        $this->user_id = $user_id;
        $this->syncDefaultConfig = (bool) $syncDefaultConfig;
    }

    /**
     * Inicjalizacja wywoływana dopiero przy pierwszym użyciu get, set, has lub save
     */
    public function init() {
        if (!$this->initialized)
        {
            $dbSettings = $this->loadFromDb();
            $this->settings = $this->mergeArrays($this->defaultSettings, $dbSettings);
            $this->initialized = true;
        }
    }

    /**
     * Pobieramy ustawienia z bazy. Jeżeli setting_name lub user_id nie istnieje - tworzymy dla niego nowy wpis.
     * @return mixed
     */
    public function loadFromDb() {

        $this->settingsModel = Models\SettingsModel::where('setting_name', $this->setting_name)->where('user_id', $this->user_id)->first();

        if (!$this->settingsModel) {
            $attributes = [
                'setting_name' => $this->setting_name,
                'user_id' => $this->user_id,
                'settings' => serialize($this->settings)
            ];
            $this->settingsModel = new Models\SettingsModel($attributes);
        }

        return unserialize($this->settingsModel->settings);
    }

    /**
     * Ponowna inicjalizacja bloku. Potrzebna przy zmianie użytkownika.
     * @param $defaultSettings
     */
    public function reInit($defaultSettings = null) {
        $this->initialized = false;

        if ($defaultSettings !== null) {
            $this->defaultSettings = $defaultSettings;
        }

        $this->dbSettings = [];
        $this->settings = [];
        $this->settingsModel = null;

        $this->init();
    }

    /**
     * Wpisuje wszystkie ustawienia z bazy do defaultowego configu i jego zwraca jako docelowy
     * Dzięki temu jeżeli coś się zmieniło w defaultowym to zmiany te zostaną uwzględnione przy następnym zapisie do bazy
     * @param array $defaultSettings
     * @param array $dbSettings
     * @param string $path
     * @return array
     */
    private function mergeArrays($defaultSettings, $dbSettings, $path="") {

        foreach ($dbSettings as $key => $value) {
            $settings_path = ltrim($path.'.'.$key, '.');

            //Zagłębiamy się tak głęboko aż value nie będzie tablicą asocjacyjną
            if (is_array($value) && $this->array_is_assoc($value)) {
                $defaultSettings = $this->mergeArrays($defaultSettings, $value, $settings_path);
            } else {
                $defaultSettings = $this->setRecursive($defaultSettings, $settings_path, $value, !$this->syncDefaultConfig);
            }
        }
        return $defaultSettings;
    }

    /**
     * UWAGA! Nadpisuje ustawienia i zaznacza je do ustawienia w bazie
     */
    public function setSettings($defaultSettings) {
        $this->settings = $defaultSettings;
        $this->changed = true;
    }

    /**
     * UWAGA! Przywraca domyślne ustawienia i zaznacza do zapisania w bazie
     */
    public function restoreDefault() {
        $this->setSettings($this->defaultSettings);
    }

    /**
     * Sprawdza rekurencyjnie czy szukany setting istnieje
     * @param $setting_path
     * @return bool|null
     */
    public function has($setting_path) {
        $this->init();
        if (is_array($setting_path)) {
            $setting_path = implode('.', $setting_path);
        }
        return $this->hasRecursive($this->settings, $setting_path);
    }

    /**
     * @param $setting_path
     * @return null
     */
    public function get($setting_path) {
        $this->init();
        if (is_array($setting_path)) {
            $setting_path = implode('.', $setting_path);
        }
        return $this->getRecursive($this->settings, $setting_path);
    }

    /**
     * Odnajduje ustawienie i wprowadza tam dane. Jeżeli jest ustawiona flaga $create - tworzy dane jeśli nie istnieją
     * @param $setting_path
     * @param $value
     * @param bool $create
     */
    public function set($setting_path, $value, $create=false) {
        $this->init();
        if (is_array($setting_path)) {
            $setting_path = implode('.', $setting_path);
        }
        $this->settings = $this->setRecursive($this->settings, $setting_path, $value, $create);
        $this->changed = true;
    }

    /**
     * Zwraca całą konfigurację
     * @return array
     */
    public function all() {
        $this->init();
        return $this->settings;
    }

    /**
     * Rekursywnie przeszukuje tablicę ustawień i zwraca wartość pod szukanym kluczem lub null jeśli nie znaleziono
     * @param array $setting
     * @param string $path
     * @return null
     */
    private function getRecursive($setting, $path) {
        $path = explode('.', $path);
        $key = array_shift($path);

        if (array_key_exists($key, $setting)) {
            if (is_array($setting[$key]) && count($path)) {
                $path = implode('.', $path);
                return $this->getRecursive($setting[$key], $path);
            } else {
                return $setting[$key];
            }
        }
        return null;
    }

    /**
     * Rekursywnie przeszukuje tablicę ustawień sprawdzając czy szukany klucz występuje
     * @param array $setting
     * @param string $path
     * @return bool|null
     */
    private function hasRecursive($setting, $path) {
        $path = explode('.', $path);
        $key = array_shift($path);

        if (array_key_exists($key, $setting)) {
            if (is_array($setting[$key]) && count($path)) {
                $path = implode('.', $path);
                return $this->hasRecursive($setting[$key], $path);
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Ustawia wartość pod wskazanym kluczem. Jeżeli $create = true i klucz nie istnieje to zostanie utworzony.
     * @param array $setting
     * @param string $path
     * @param mixed $value
     * @param bool $create
     * @return mixed
     */
    private function setRecursive($setting, $path, $value, $create = false) {
        $path = explode('.', $path);
        $key = array_shift($path);

        if ($create && !array_key_exists($key, $setting)) {
            $setting[$key] = [];
        }

        if (array_key_exists($key, $setting)) {
            if (is_array($setting[$key]) && count($path)) {
                $path = implode('.', $path);
                $setting[$key] = $this->setRecursive($setting[$key], $path, $value, $create);
            } else {
                $setting[$key] = $value;
            }
        }
        return $setting;
    }

    /**
     * Ustawia użytkownika dla którego mają być wczytane ustawienia.
     * Jeżeli blok był już wcześniej zainicjalizowany - nie zezwalamy na zmianę.
     * @param int|null $user_id
     * @throws \Exception
     */
    public function setUser($user_id) {
        if ($this->initialized) {
            throw new \Exception('SettingBlock already initialized!');
        } else {
            $this->user_id = $user_id;
        }
    }

    /**
     * Flaga oznacza że blok ustawień ma być synchronizowany z defaultową konfiguracyją. Oznacza to że nie można
     * tworzyć nowych ustawień ponad te które są default.
     * @param $sync
     */
    public function syncDefaultConfig($sync) {
        $this->syncDefaultConfig = (bool) $sync;
    }

    /**
     * Wymuszenie zmiany użytkownika. Wymaga reinicjalizacji aby nie nadpisać danych!!
     * @param $user_id
     * @throws \Exception
     */
    public function forceSetUser($user_id) {
        $this->initialized = false;
        $this->setUser($user_id);
    }

    /**
     * Zapisuje zmiany do bazy
     */
    public function save() {
        if ($this->changed && $this->settingsModel) {
            $this->init();
            $this->settingsModel->settings = serialize($this->settings);
            $this->settingsModel->save();
        }
    }

    private function array_is_assoc($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
