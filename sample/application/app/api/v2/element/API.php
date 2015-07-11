<?php
namespace DokraApplication\api\v2\element;

use DokraApplication\api\v2\element\assets\Region;

class API extends \DokraApplication\api\BaseAPI
{
    /**
     * @param string $code
     *
     * @return Region
     */
    public function getRegion($code)
    {
        $object = new Region;
        $object->Name = 'Name ' . $code;
        $object->Code = $code;
        $object->Description = 'Description for ' . $code;
        $object->MaxCapacity = 1000;
        $object->Date = '2015-01-01 00:00:00';
        $object->Active = true;

        return $object;
    }

    /**
     * @param Region $region
     *
     * @return string
     */
    public function addRegion($region)
    {
        $object = new Region();
        foreach ($region as $key => $value) {
            $object->{$key} = $value;
        }

        // ...
        
        return uniqid();
    }

    /**
     * @param Region $region
     *
     * @return bool
     */
    public function deleteRegion($region)
    {
        // ...

        return true;
    }

    /**
     * @param Region $region
     *
     * @return bool
     */
    public function updateRegion($region)
    {
        // ...

        return true;
    }

    /**
     * @param string[] $codes
     *
     * @return Region[]
     */
    public function getRegions($codes = array())
    {
        $return = array();

        if (!empty($codes)) {
            foreach ($codes as $code) {
                $return[] = $this->getRegion($code);
            }
        }

        return $return;
    }
}
