<?php
namespace tests\formats\PHP\Importer;


use Dokra\formats\PHP\Importer;

class getClassDetailsTest extends \PHPUnit_Framework_TestCase
{
    public $contents = [];

    public function setUp()
    {
        $this->contents[0] = "<?php\n\$a = 123;\n?>";
        $this->contents[1] = <<<EOF
<?php
class Region {

}
EOF;

        $this->contents[2] = <<<EOF
<?php
    use \common\CountryA as CountryA;
    use \core\Time;
    use storage\FileStorage_0;

    class Country extends CountryA {

    }
EOF;

        $this->contents[3] = <<<EOF
<?php
    class Country extends assets\CountryA {

    }
EOF;

    $this->contents[4] = <<<EOF
    <?php
        class Country extends \\elements\\assets\\CountryA {

        }
EOF;
    }

    /**
     * When no class definition is found in text then return empty object.
     */
    public function testWhenNoClassDefinitionIsFoundInTextThenReturnEmptyObject()
    {
        $response = Importer::getClassDetails($this->contents[0]);

        static::assertNull($response->className);
        static::assertNull($response->extendsClass);
        static::assertNull($response->extendsAlias);
    }

    /**
     * When class definition is found in contents then return class name in response.
     */
    public function testWhenClassDefinitionIsFoundInContentsThenReturnClassNameInResponse()
    {
        $response = Importer::getClassDetails($this->contents[1]);
        static::assertEquals('Region', $response->className);
    }

    /**
     * When extends a class then get extended class name in response.
     */
    public function testWhenExtendsAClassThenGetExtendedClassNameInResponse()
    {
        $response = Importer::getClassDetails($this->contents[2]);

        static::assertEquals('Country', $response->className);
        static::assertEquals('CountryA', $response->extendsAlias);
    }

    /**
     * When extends a class with namespaces then get extended class name in response.
     */
    public function testWhenExtendsAClassWithNamespacesThenGetExtendedClassNameInResponse()
    {
        $response = Importer::getClassDetails($this->contents[3]);

        static::assertEquals('Country', $response->className);
        static::assertEquals('assets\CountryA', $response->extendsAlias);
    }

    /**
     * When extends a class with root namespace then get extended class name in response.
     */
    public function testWhenExtendsAClassWithRootNamespaceThenGetExtendedClassNameInResponse()
    {
        $response = Importer::getClassDetails($this->contents[4]);
        static::assertEquals('\elements\assets\CountryA', $response->extendsAlias);
    }

    /**
     * When has uses return entries.
     */
    public function testWhenHasUsesReturnEntries()
    {
        $response = Importer::getClassDetails($this->contents[2]);
         static::assertEquals('\common\CountryA', $response->extendsClass);
    }
}