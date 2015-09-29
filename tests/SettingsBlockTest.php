<?php

namespace Code4\Settings\Test;

class SettingsBlockTest extends \PHPUnit_Framework_TestCase
{

    public function testSettings() {

        $defaultData = [
            'foo' => '',
            'very' => [
                'deep' => [
                    'data' => 'here'
                ]
            ],
            'nullData' => null,
            'boolDataFalse' => false,
            'boolDataTrue' => true
        ];
        $dbData = [
            'foo' => 'bar',
            'toBeAdded' => 'added'
        ];

        $settingsBlock = $this->getMockBuilder('Code4\Settings\SettingsBlock')
            ->setConstructorArgs(['global', $defaultData, 10, true])
            ->setMethods(['loadFromDb'])
            ->getMock();

        $settingsBlock->method('loadFromDb')->will($this->returnValue($dbData));

        //fwrite(STDERR, print_r($settingsBlock->get('boolDataFalse'), TRUE));

        //Checking
        $this->assertEquals(true, $settingsBlock->has('nullData'));
        $this->assertEquals(true, $settingsBlock->has('boolDataFalse'));
        $this->assertEquals(true, $settingsBlock->has('boolDataTrue'));
        $this->assertEquals(true, $settingsBlock->has('very.deep.data'));

        //Sync default data flag
        $this->assertEquals(null, $settingsBlock->get('toBeAdded'));

        //Switch syncDefaultConfig flag and reInit all data to load missing fields from db
        $settingsBlock->syncDefaultConfig(false);
        $settingsBlock->reInit();
        $this->assertEquals('added', $settingsBlock->get('toBeAdded'));

        //Getting
        $this->assertEquals('bar', $settingsBlock->get('foo'));
        $this->assertEquals(null, $settingsBlock->get('toBeDeleted'));
        $this->assertEquals('here', $settingsBlock->get('very.deep.data'));

        $this->assertEquals(null, $settingsBlock->get('nullData'));
        $this->assertEquals(false, $settingsBlock->get('boolDataFalse'));
        $this->assertEquals(true, $settingsBlock->get('boolDataTrue'));

        //Setting
        $settingsBlock->set('foo', 'baz');
        $this->assertEquals('baz', $settingsBlock->get('foo'));

        $settingsBlock->set('notExisting', 'data');
        $this->assertEquals(null, $settingsBlock->get('notExisting'));

        $settingsBlock->set('notExisting', 'data', true);
        $this->assertEquals('data', $settingsBlock->get('notExisting'));

        $settingsBlock->set('notExisting.very.deep', 'data', true);
        $this->assertEquals('data', $settingsBlock->get('notExisting.very.deep'));
    }

}
