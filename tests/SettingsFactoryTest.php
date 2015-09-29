<?php

namespace Code4\Settings\Test;

class SettingsFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testFactorySettings() {

        $settingsBlock = $this->mockGlobal();
        $settingsBlock_user = $this->mockGlobalUser();

        $settingsFactory = $this->getMockBuilder('Code4\Settings\SettingsFactory')
            ->setConstructorArgs([[], 10, '', false])
            ->setMethods(['instantiateBlock'])
            ->getMock();

        $settingsFactory->method('instantiateBlock')->will($this->returnCallback(function($arg) use ($settingsBlock, $settingsBlock_user) {
            if ($arg == 'global') {
                return $settingsBlock;
            }
            if ($arg == 'global_user') {
                return $settingsBlock_user;
            }
        }));

        $settingsFactory->addBlock('global');
        $settingsFactory->addBlock('global_user');

        //fwrite(STDERR, print_r($settingsFactory->get('global'), TRUE));


        //Inheritance tests
        $this->assertEquals('bar', $settingsFactory->get('global.foo'));
        $this->assertEquals('bar', $settingsFactory->get('global.foo'));
        $this->assertEquals(false, $settingsFactory->get('global.boolDataTrue'));
        $this->assertEquals(true, $settingsFactory->get('global.boolDataFalse'));
        $this->assertEquals('inherited', $settingsFactory->get('global.very.deep.data.to.inherit'));
        $this->assertEquals('inherited', $settingsFactory->get('global.very.deep.data.to.nullInherit'));
        $this->assertEquals('data', $settingsFactory->get('global.very.deep.data.to.read'));
        $this->assertEquals('data', $settingsFactory->get('global.notExistingInUserData'));

        //Without inheritance
        $this->assertEquals('baz', $settingsFactory->get('global.foo', false));
        $this->assertEquals(true, $settingsFactory->get('global.boolDataTrue', false));
        $this->assertEquals(false, $settingsFactory->get('global.boolDataFalse', false));
        $this->assertEquals('global data', $settingsFactory->get('global.very.deep.data.to.read', false));

        //HasBlock
        $this->assertEquals(true, $settingsFactory->hasBlock('global'));
        $this->assertEquals(false, $settingsFactory->hasBlock('notExistingBlock'));

        //Setting
        $settingsFactory->set('global_user.foo', 'changed');
        $this->assertEquals('changed', $settingsFactory->get('global.foo'));

        $settingsFactory->set('global.foo', 'changed');
        $this->assertEquals('changed', $settingsFactory->get('global.foo', false));
    }

    public function testFactorySettingsLazy() {

        $settingsBlock = $this->mockGlobal();
        $settingsBlock_user = $this->mockGlobalUser();

        $settingsFactory = $this->getMockBuilder('Code4\Settings\SettingsFactory')
            ->setConstructorArgs([['global','global_user'], 10, '', true])
            ->setMethods(['instantiateBlock'])
            ->getMock();

        $settingsFactory->method('instantiateBlock')->will($this->returnCallback(function($arg) use ($settingsBlock, $settingsBlock_user) {
            if ($arg == 'global') {
                return $settingsBlock;
            }
            if ($arg == 'global_user') {
                return $settingsBlock_user;
            }
        }));

        //fwrite(STDERR, print_r($settingsFactory->get('global'), TRUE));

        //TEST 1 - Lazy Load włączony więc blok nie powinien być od razu załadowany
        $settingsFactory->addBlock('notLoadedBlock');
        $blocks = $settingsFactory->getBlocksCollection();
        $this->assertEquals(false, $blocks->has('notLoadedBlock'));

        //Simple get
        $this->assertEquals('bar', $settingsFactory->get('global.foo'));
        $this->assertEquals('data', $settingsFactory->get('global.very.deep.data.to.read'));
        //Boolean values
        $this->assertEquals(false, $settingsFactory->get('global.boolDataTrue'));
        $this->assertEquals(true, $settingsFactory->get('global.boolDataFalse'));
        //Inheritance tests
        $this->assertEquals('inherited', $settingsFactory->get('global.very.deep.data.to.inherit'));
        $this->assertEquals('inherited', $settingsFactory->get('global.very.deep.data.to.nullInherit'));
        $this->assertEquals('data', $settingsFactory->get('global.notExistingInUserData'));

        //Inheritance disabled
        $this->assertEquals('baz', $settingsFactory->get('global.foo', false));
        $this->assertEquals(true, $settingsFactory->get('global.boolDataTrue', false));
        $this->assertEquals(false, $settingsFactory->get('global.boolDataFalse', false));
        $this->assertEquals('global data', $settingsFactory->get('global.very.deep.data.to.read', false));

        //hasBlock
        $this->assertEquals(true, $settingsFactory->hasBlock('global'));
        $this->assertEquals(false, $settingsFactory->hasBlock('notExistingBlock'));

        //Setting
        $settingsFactory->set('global_user.foo', 'changed');
        $this->assertEquals('changed', $settingsFactory->get('global.foo'));

        $settingsFactory->set('global.foo', 'changed');
        $this->assertEquals('changed', $settingsFactory->get('global.foo', false));
    }

    private function mockGlobal() {
        $dbData = [
            'foo' => 'baz',
            'very' => [
                'deep' => [
                    'data' => [
                        'to' => [
                            'inherit' => 'inherited',
                            'nullInherit' => 'inherited',
                            'read' => 'global data'
                        ]
                    ]
                ]
            ],
            'notExistingInUserData' => 'data',
            'nullData' => null,
            'boolDataTrue' => true,
            'boolDataFalse' => false
        ];
        $settingsBlock = $this->getMockBuilder('Code4\Settings\SettingsBlock')
            ->setConstructorArgs(['global', $dbData])
            ->setMethods(['loadFromDb'])
            ->getMock();

        $settingsBlock->method('loadFromDb')->will($this->returnValue([]));
        return $settingsBlock;
    }

    private function mockGlobalUser() {
        $dbData_user = [
            'foo' => 'bar',
            'very' => [
                'deep' => [
                    'data' => [
                        'to' => [
                            'inherit' => 'inherit',
                            'nullInherit' => null,
                            'read' => 'data'
                        ]
                    ]
                ]
            ],
            'boolDataTrue' => false,
            'boolDataFalse' => true
        ];



        $settingsBlock_user = $this->getMockBuilder('Code4\Settings\SettingsBlock')
            ->setConstructorArgs(['global_user', $dbData_user])
            ->setMethods(['loadFromDb'])
            ->getMock();

        $settingsBlock_user->method('loadFromDb')->will($this->returnValue([]));
        return $settingsBlock_user;
    }

}
