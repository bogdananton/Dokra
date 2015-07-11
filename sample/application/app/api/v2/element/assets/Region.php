<?php
namespace DokraApplication\api\v2\element\assets;

class Region extends Basic
{
    /**
     * Region's maximum capacity.
     * @var int
     */
    public $MaxCapacity;

    /**
     * The date when the element was created.
     * @var string
     */
    public $Date;

    /**
     * Marked true if is available for editing. 
     * @type bool
     */
    public $Active = false;
}