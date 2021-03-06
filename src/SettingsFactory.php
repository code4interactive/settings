<?php

namespace Code4\Settings;

use Illuminate\Support\Collection;

class SettingsFactory {

    protected $configFilePrefix = '';

    protected $settings;
    protected $blocksList = [];
    protected $lazyLoading = false;

    protected $user_id;

    public function __construct($settingBlocks = [], $user_id = null, $configFilePrefix = '', $lazyLoading = false) {
        $this->user_id = $user_id;
        $this->configFilePrefix = $configFilePrefix;
        $this->settings = new Collection();
        $this->lazyLoading = $lazyLoading;

        $this->setSettingBlocks($settingBlocks);
    }

    /**
     * @param bool $lazyLoad
     * @return $this
     */
    public function setLazyLoad($lazyLoad) {
        $this->lazyLoading = (bool) $lazyLoad;
        return $this;
    }

    /**
     * @param $user_id
     * @return $this
     */
    public function setUserId($user_id) {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @param string $configFilePrefix
     * @return $this
     */
    public function setConfigFilePrefix($configFilePrefix) {
        $this->configFilePrefix = $configFilePrefix;
        return $this;
    }

    public function setSettingBlocks($settingBlocks) {
        foreach ($settingBlocks as $block) {
            $this->addBlock($block);
        }
        return $this;
    }

    /**
     * Dodaje nowy blok konfiguracyjny do zestawu. Jeżeli włączone LazyLoading to nie ładujemy go od razu.
     * @param $name
     */
    public function addBlock($name) {
        if (!in_array($name, $this->blocksList)) {
            $this->blocksList[] = $name;
        }

        if (!$this->lazyLoading) {
            $this->loadBlock($name, $this->user_id);
        }
    }

    /**
     * Ładuje i inicjalizuje blok
     * @param $oBlockName - bez prefixu
     * @param int|null $user_id
     */
    public function loadBlock($oBlockName, $user_id = null) {

        //Jeżeli nazwa bloku nie zawiera "_user" nie przekazujemy user_id do instancji
        if (strpos($oBlockName, '_user') === false) {
            $user_id = null;
        }

        $block = $this->instantiateBlock($this->configFilePrefix.$oBlockName, $user_id);
        $this->settings->put($this->configFilePrefix.$oBlockName, $block);
    }

    /**
     * Instancjonuje obiekt bloku
     * @param $oBlockName - z prefixem
     * @param int|null $user_id
     * @return SettingsBlock
     */
    public function instantiateBlock($oBlockName, $user_id = null) {
        $defaultData = \Config::has($oBlockName) ? \Config::get($oBlockName) : [];
        return new SettingsBlock($oBlockName, $defaultData, $user_id);
    }

    /**
     * Pobiera blok ustawień sprawdzając czy został załadowany.
     * Jeżeli nie to próbujemy go najpierw załadować.
     * @param string $oBlockName - bez prefixu
     * @return mixed
     * @throws \Exception
     */
    public function getBlock($oBlockName) {

        if (!$this->settings->has($this->configFilePrefix.$oBlockName)
            && in_array($oBlockName, $this->blocksList)) {
                $this->loadBlock($oBlockName, $this->user_id);
        }

        if ($this->settings->has($this->configFilePrefix.$oBlockName)) {
            return $this->settings->get($this->configFilePrefix.$oBlockName);
        }

        throw new \Exception('Block '.$this->configFilePrefix.$oBlockName.' not found or can\'t be loaded!');
    }

    /**
     * Sprawdzamy czy blok jast załadowany lub czy jest na liście do załadowania
     * @param string $oBlockName
     * @return bool
     */
    public function hasBlock($oBlockName) {
        if ($this->settings->has($this->configFilePrefix.$oBlockName)) {
            return true;
        }

        if ($this->lazyLoading && in_array($oBlockName, $this->blocksList)) {
            return true;
        }
        return false;
    }

    /**
     * Pobiera ustawienie z odpowiedniego bloku
     * Domyślnie zachowując dziedziczenie ale można je wyłączyć ustawiając flagę $inheritance na false
     * @param string $oSettingPath
     * @param bool $inheritance
     * @return mixed
     * @throws \Exception
     */
    public function get($oSettingPath, $inheritance=true) {
        $settingPath = explode('.', $oSettingPath);
        $oBlockName = array_shift($settingPath);

        //Jeżeli $oSettingPath miał tylko nazwę bloku - zwracamy cały blok
        if (count($settingPath) == 0) {
            if ($this->hasBlock($oBlockName)) {
                return $this->getBlock($oBlockName);
            } else {
                throw new \Exception('Block ' . $oBlockName . ' not found!');
            }
        }

        $settingPath = implode('.', $settingPath);
        $setting = null;

        //Czy uwzględniamy dziedziczenie opcji przez usera?
        if ($inheritance)
        {
            //Sprawdzamy najpierw czy istnieją ustawienia usera (user_id może być null - wtedy nie sprawdzamy)
            $uBlockName = $oBlockName . '_user';

            if ($this->user_id && $this->hasBlock($uBlockName) && $this->getBlock($uBlockName)->has($settingPath))
            {
                $setting = $this->getBlock($uBlockName)->get($settingPath);
            }

            //Obsługa dziedziczenia
            if ($setting === null || $setting === 'inherit')
            {
                $setting = $this->getBlock($oBlockName)->get($settingPath);
            }
        } else {
            $setting = $this->getBlock($oBlockName)->get($settingPath);
        }

        if ($setting !== null) {
            return $setting;
        }

        throw new \Exception('Setting '.$oSettingPath.' not found');
    }

    /**
     * Zapisując settingsy musimy wyraźnie wskazać czy zapisujemy ustawienie dla usera czy globalne dlatego nie robimy
     * automatycznego dziedziczenia
     * @param $oSettingPath
     * @param $value
     * @param bool $create
     * @throws \Exception
     */
    public function set($oSettingPath, $value, $create=false) {
        $settingPath = explode('.', $oSettingPath);
        $oBlockName = array_shift($settingPath);
        $settingPath = implode('.', $settingPath);

        if ($this->hasBlock($oBlockName)) {
            $this->getBlock($oBlockName)->set($settingPath, $value, $create);
        }
    }

    /**
     * Zwraca kolekcję wszystkich wczytanych ustawień
     * @return Collection
     */
    public function getBlocksCollection() {
        return $this->settings;
    }

    /**
     * Przelatujemy po wszystkich załadowanych settingsach i zapisujemy je
     * Settingsy same pilnują czy były modyfikowane więc zapisane będą tylko te które się zmieniły od załadowania
     */
    public function save() {
        foreach($this->settings as $setting) {
            $setting->save();
        }
    }

    public function __destruct() {
        $this->save();
    }
}