<?php

class SettingsTest extends TestCase
{
    public function testGettingAndSetting() {

        $testArray = [];
        $testDbArray = [];

        $settings = new \App\Components\Settings\SettingsBlock('global');
        $settings->testInit($testArray, $testDbArray);

        $this->assertEquals(null, $settings->get('resource_types.machine.id'));

        $settings->set('resource_types.machine.id', 13);
        $this->assertEquals(null, $settings->get('resource_types.machine.id'));

        $settings->set('resource_types.machine.id', 13, true);
        $this->assertEquals(13, $settings->get('resource_types.machine.id'));
    }

    public function testMergeArrays() {

        $testArray = [
            'resource_types' => [
                'machine'  => [
                    'id'   => 0,
                    'name' => '',
                    'icon' => ''
                ],
                'location' => [
                    'id'   => 1,
                    'name' => '',
                    'icon' => ''
                ],
                'external' => [
                    'id'   => 2,
                    'name' => '',
                    'icon' => ''
                ],
                'toBeAdded' => [
                    'id'   => 3,
                    'name' => '',
                    'icon' => 'added'
                ]
            ]
        ];
        $testDbArray = [
            'resource_types' => [
                'machine'  => [
                    'id'   => 0,
                    'name' => 'Maszyna',
                    'icon' => 'cogs'
                ],
                'location' => [
                    'id'   => 1,
                    'name' => 'Lokalizacja',
                    'icon' => 'map-o'
                ],
                'external' => [
                    'id'   => 2,
                    'name' => 'Zewnętrzny',
                    'icon' => 'truck'
                ],
                'toBeDeleted' => [
                    'id'   => 2,
                    'name' => 'Zewnętrzny',
                    'icon' => 'truck'
                ],
            ]
        ];

        $settings = new \App\Components\Settings\SettingsBlock('global');
        $settings->testInit($testArray, $testDbArray, true);

        $this->assertEquals('truck', $settings->get('resource_types.external.icon'));
        $this->assertEquals('added', $settings->get('resource_types.toBeAdded.icon'));
        $this->assertEquals(null, $settings->get('resource_types.toBeDeleted.icon'));
    }


    public function testSettingsFactory() {

        $settingBlock = $this->getTestSettingBlock('testBlock');

        $settingsFactory = $this->getMockBuilder('\App\Components\Settings\SettingsFactory')
                                ->setConstructorArgs([[],null,'',false])
                                ->setMethods(['instantiateBlock'])
                                ->getMock();

        $settingsFactory->method('instantiateBlock')->will($this->returnValue($settingBlock));

        //fwrite(STDERR, print_r($settingsFactory->getBlocksCollection()->has('global'), TRUE));

        //TEST 1 - Lazy Load wyłączony więc blok powinien być od razu załadowany
        $settingsFactory->addBlock('testBlock');
        $blocks = $settingsFactory->getBlocksCollection();
        $this->assertEquals(true, $blocks->has('testBlock'));

        //TEST 2 - Check hasBlock
        $this->assertEquals(true, $settingsFactory->hasBlock('testBlock'));
        $this->assertEquals(false, $settingsFactory->hasBlock('notExistingBlock'));

        //TEST 3 - Check getBlock
        $this->assertEquals('App\Components\Settings\SettingsBlock', get_class($settingsFactory->getBlock('testBlock')));
    }

    public function testSettingsFactoryLazyLoad()
    {
        $settingBlock = $this->getTestSettingBlock('testBlock');
        $settingsFactory = $this->getMockBuilder('\App\Components\Settings\SettingsFactory')
            ->setConstructorArgs([[], null, '', true])
            ->setMethods(['instantiateBlock'])
            ->getMock();

        $settingsFactory->method('instantiateBlock')->will($this->returnValue($settingBlock));

        //TEST 1 - Lazy Load włączony więc blok nie powinien być od razu załadowany
        $settingsFactory->addBlock('testBlock');
        $blocks = $settingsFactory->getBlocksCollection();
        $this->assertEquals(false, $blocks->has('testBlock'));

        //TEST 2 - Check hasBlock
        $this->assertEquals(true, $settingsFactory->hasBlock('testBlock'));
        $this->assertEquals(false, $settingsFactory->hasBlock('notExistingBlock'));

        //TEST 3 - Check getBlock
        $this->assertEquals('App\Components\Settings\SettingsBlock', get_class($settingsFactory->getBlock('testBlock')));

        //TEST 4 - Ponieważ w teście 3 został wywołany getBlock() - testBlock powinien być już załadowany
        $blocks = $settingsFactory->getBlocksCollection();
        $this->assertEquals(true, $blocks->has('testBlock'));
    }



    public function testSettingsInheritance() {
        $testArray = [
            'resource_types' => [
                'machine'  => [
                    'id'   => 0,
                    'name' => '',
                    'icon' => ''
                ],
                'location' => [
                    'id'   => 1,
                    'name' => '',
                    'icon' => ''
                ],
                'external' => [
                    'id'   => 2,
                    'name' => '',
                    'icon' => ''
                ],
                'toBeAdded' => [
                    'id'   => 3,
                    'name' => '',
                    'icon' => 'added'
                ]
            ]
        ];
        $testDbArray = [
            'resource_types' => [
                'machine'  => [
                    'id'   => 0,
                    'name' => 'Maszyna',
                    'icon' => 'cogs'
                ],
                'location' => [
                    'id'   => 1,
                    'name' => 'Lokalizacja',
                    'icon' => 'map-o'
                ],
                'external' => [
                    'id'   => 2,
                    'name' => 'Zewnętrzny',
                    'icon' => 'truck'
                ],
                'toBeDeleted' => [
                    'id'   => 2,
                    'name' => 'Zewnętrzny',
                    'icon' => 'truck'
                ],
            ]
        ];

        $settingBlock1 = new \App\Components\Settings\SettingsBlock('testBlock');
        $settingBlock1->testInit($testArray, $testDbArray, true);

        $settingBlock2 = new \App\Components\Settings\SettingsBlock('testBlock_user');
        $settingBlock2->testInit($testArray, $testDbArray, true);

        //$settingBlock = $this->getTestSettingBlock('testBlock');
        $settingsFactory = $this->getMockBuilder('\App\Components\Settings\SettingsFactory')
            ->setConstructorArgs([[], 10, '', false])
            ->setMethods(['instantiateBlock'])
            ->getMock();

        //$settingsFactory->method('instantiateBlock')->will($this->returnValue($settingBlock1));
        $settingsFactory->method('instantiateBlock')->will($this->returnCallback(function($arg) use ($settingBlock1, $settingBlock2) {
            if ($arg == 'testBlock') {
                return $settingBlock1;
            } else {
                return $settingBlock2;
            }
        }));

        $settingsFactory->addBlock('testBlock');
        $settingsFactory->addBlock('testBlock_user');

        $i = $settingsFactory->get('testBlock.resource_types.machine.icon');
        $this->assertEquals('cogs', $i);

        //Ustawiamy wartość dla globalnego i sprawdzamy czy wartosc usera nadpisuje
        $settingsFactory->set('testBlock.resource_types.machine.icon', 'globalValue');
        $i = $settingsFactory->get('testBlock.resource_types.machine.icon');
        $this->assertEquals('cogs', $i);

        //Sprawdzamy czy dla globalnego wpis jest poprawny
        $i = $settingsFactory->get('testBlock.resource_types.machine.icon', false);
        $this->assertEquals('globalValue', $i);

        //Ustawiamy wartość usera na inherit i sprawdzamy czy pokazuje globalna
        $settingsFactory->set('testBlock_user.resource_types.machine.icon', 'inherit');
        $i = $settingsFactory->get('testBlock.resource_types.machine.icon');
        $this->assertEquals('globalValue', $i);
    }


    private function getTestSettingBlock($name) {
        $testArray = [
            'resource_types' => [
                'machine'  => [
                    'id'   => 0,
                    'name' => '',
                    'icon' => ''
                ],
                'location' => [
                    'id'   => 1,
                    'name' => '',
                    'icon' => ''
                ],
                'external' => [
                    'id'   => 2,
                    'name' => '',
                    'icon' => ''
                ],
                'toBeAdded' => [
                    'id'   => 3,
                    'name' => '',
                    'icon' => 'added'
                ]
            ]
        ];
        $testDbArray = [
            'resource_types' => [
                'machine'  => [
                    'id'   => 0,
                    'name' => 'Maszyna',
                    'icon' => 'cogs'
                ],
                'location' => [
                    'id'   => 1,
                    'name' => 'Lokalizacja',
                    'icon' => 'map-o'
                ],
                'external' => [
                    'id'   => 2,
                    'name' => 'Zewnętrzny',
                    'icon' => 'truck'
                ],
                'toBeDeleted' => [
                    'id'   => 2,
                    'name' => 'Zewnętrzny',
                    'icon' => 'truck'
                ],
            ]
        ];

        $settings = new \App\Components\Settings\SettingsBlock($name);
        $settings->testInit($testArray, $testDbArray, true);
        return $settings;
    }

}
