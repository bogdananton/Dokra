<?php
namespace DokraApplication\api\v2\element;

class API extends \DokraApplication\api\BaseAPI
{
    public function getRegion($code)
    {
        $object = new assets\Region;
        $object->Name = 'Name ' . $code;
        $object->Code = $code;
        $object->Description = 'Description for ' . $code;
        $object->MaxCapacity = 1000;
        $object->Date = '2015-01-01 00:00:00';
        $object->Active = true;

        return $object;
    }

    public function addRegion($region)
    {
        $object = new assets\Region();
        foreach ($region as $key => $value) {
            $object->{$key} = $value;
        }

        // ...
        
        return uniqid();
    }

    public function deleteRegion($region)
    {
        // ...

        return true;
    }

    public function updateRegion($region)
    {
        // ...

        return true;
    }

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
