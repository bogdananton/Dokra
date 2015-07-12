<?php
namespace tests\storage\FileStorage;

use Dokra\storage\FileStorage;

class extensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * When the input doesn't contain a valid extension then return null.
     * @dataProvider stringsWithNoExtensionFormat
     */
    public function testWhenTheInputDoesnTContainAValidExtensionThenReturnNull($input)
    {
        static::assertNull(FileStorage::extension($input));
    }

    /**
     * When input contains a valid extension then return lowercase extension.
     * @dataProvider stringsWithExtensionFormat
     */
    public function testWhenInputContainsAValidExtensionThenReturnLowercaseExtension($input, $expected)
    {
        static::assertEquals($expected, FileStorage::extension($input));
    }

    /**
     * returns arrays with 0 => input, 1 => expected lowercase extension
     * @return array
     */
    public function stringsWithExtensionFormat()
    {
        return [
            ['11111.222', '222'],
            ['/folder/index.PHp', 'php'],
            ['/folder/.htaccess', 'htaccess']
        ];
    }

    public function stringsWithNoExtensionFormat()
    {
        return [
            [true],
            [''],
            ['12345'],
            [555555],
            ['/project/Dokra/demo_php'],
            [['file' => '123']]
        ];
    }
}
