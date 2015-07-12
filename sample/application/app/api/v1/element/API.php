<?php
namespace DokraApplication\api\v1\element;

class API extends \DokraApplication\api\BaseAPI
{
    /**
     * @param string $code
     *
     * @return assets\Region
     */
    public function getRegion($code)
    {
        $object = new assets\Region;
        $object->Name = 'Name ' . $code;
        $object->Code = $code;
        $object->MaxCapacity = 1000;
        $object->Date = '2015-01-01 00:00:00';
        $object->Active = true;

        return $object;
    }

    /**
     * @param assets\Region $region
     *
     * @return string
     */
    public function addRegion($region)
    {
        $object = new assets\Region();
        foreach ($region as $key => $value) {
            $object->{$key} = $value;
        }

        // ...
        
        return uniqid();
    }

    /**
     * @param string[] $codes
     * @param string $Language
     *
     * @return assets\Region[]
     */
    public function getRegions($codes = array(), $Language = 'en')
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
