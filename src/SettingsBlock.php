<?php

namespace Code4\Settings;

use App\Components\Settings\Models\Settings;
use Cartalyst\Sentinel\Users\UserInterface;

class SettingsBlock {

    protected $user_id = 0;

    protected $defaultSettings = [];
    protected $dbSettings = [];

    protected $settings = [];
    protected $settingsModel;

    protected $hasDefaultConfig = false;

    protected $changed = false;
    private $initialized = false;

    public function __construct($setting_name, $user_id = null) {

        $this->setting_name = $setting_name;

        $this->user_id = $user_id;

    }

    /**
     * Ustawia użytkownika dla którego mają być wczytane ustawienia.
     * Jeżeli blok był już wcześniej zainicjalizowany - nie zezwalamy na zmianę.
     * @param int|null $user_id
     * @throws \Exception
     */
    public function setUser($user_id) {
        if ($this->initialized) {
            throw new \Exception('Cannot set user. SettingBlock already initialized!');
            die();
        }
        $this->user_id = $user_id;
    }

    /**
     * Wymuszenie zmiany użytkownika. Wymaga reinicjalizacji aby nie nadpisać danych!!
     * @param $user_id
     * @throws \Exception
     */
    public function forceSetUser($user_id) {

        if ($this->initialized) {
            $this->initialized = false;
        }

        $this->setUser($user_id);
    }

    /**
     * Ładuje domyślny config i config z bazy i łączy oba
     */
    public function init() {
        if (!$this->initialized)
        {
            //Sprawdzamy czy istnieje domyślny config
            $defaultSettings = [];
            if (\Config::has($this->setting_name))
            {
                $this->hasDefaultConfig = true;
                $defaultSettings = \Config::get($this->setting_name);
            }

            //Ładujemy config z bazy
            $dbSettings = $this->loadFromDb();

            $this->load($defaultSettings, $dbSettings);
            $this->initialized = true;
        }
    }

    /**
     * Ponowna inicjalizacja bloku. Potrzebna przy zmianie użytkownika.
     */
    public function reInit() {
        $this->defaultSettings = [];
        $this->dbSettings = [];
        $this->settings = [];
        $this->settingsModel = null;

        $this->init();
    }


    /**
     * Metoda init dla UnitTestów
     * @param array $testArray
     * @param array $dbArray
     * @param bool $hasDefaultConfig
     */
    public function testInit($testArray = [], $dbArray = [], $hasDefaultConfig = false) {
        $this->hasDefaultConfig = $hasDefaultConfig;
        $this->load($testArray, $dbArray);
        $this->initialized = true;
    }


    /**
     * Ładowanie ustawień do klasy z przekazanych tablic
     * @param $defaultSettings
     * @param $dbSettings
     */
    private function load($defaultSettings, $dbSettings) {
        $this->defaultSettings = $defaultSettings;
        $this->dbSettings = $dbSettings;

        if ($this->hasDefaultConfig) {
            $this->settings = $this->mergeArrays($defaultSettings, $dbSettings);
        } else {
            $this->settings = $dbSettings;
        }
    }

    /**
     * Pobieramy ustawienia z bazy. Jeżeli setting_name lub user_id nie istnieje - tworzymy dla niego nowy wpis.
     * @return mixed
     */
    public function loadFromDb() {

        $this->settingsModel = Settings::where('setting_name', $this->setting_name)->where('user_id', $this->user_id)->first();

        if (!$this->settingsModel) {
            $attributes = [
                'setting_name' => $this->setting_name,
                'user_id' => $this->user_id,
                'settings' => serialize($this->settings)
            ];
            $this->settingsModel = new Settings($attributes);
        }

        return unserialize($this->settingsModel->settings);
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
            if (is_array($value) && isAssoc($value)) {
                $defaultSettings = $this->mergeArrays($defaultSettings, $value, $settings_path);
            } else {
                $defaultSettings = $this->setRecursive($defaultSettings, $settings_path, $value);
            }
        }
        return $defaultSettings;
    }


    /**
     * Przywraca domyślne wartości z pliku konfiguracyjnego (o ile istnieje)
     */
    public function restoreDefaultSettings() {
        if ($this->hasDefaultConfig)
        {
            $this->settings = $this->defaultSettings;
            $this->changed = true;
        }
    }


    public function has($setting_path) {
        if (is_array($setting_path)) {
            $setting_path = implode('.', $setting_path);
        }
        return $this->hasRecursive($this->settings, $setting_path);
    }

    public function get($setting_path) {
        if (is_array($setting_path)) {
            $setting_path = implode('.', $setting_path);
        }
        return $this->getRecursive($this->settings, $setting_path);
    }

    public function set($setting_path, $value, $create=false) {
        if (is_array($setting_path)) {
            $setting_path = implode('.', $setting_path);
        }
        $this->changed = true;
        $this->settings = $this->setRecursive($this->settings, $setting_path, $value, $create);
    }

    /**
     * Zwraca całą konfigurację
     * @return array
     */
    public function all() {
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

    public function save() {

        if ($this->changed) {
            $this->dbSettings->settings = serialize($this->settings);
            $this->dbSettings->save();
        }

    }

}
