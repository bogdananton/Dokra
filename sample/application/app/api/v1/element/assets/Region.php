<?php
namespace DokraApplication\api\v1\element\assets;

class Region
{
    /**
     * The Region's name.
     * @var string
     */
    public $Name;

    /**
     * The Region's code. Is unique.
     * @var string
     */
    public $Code;

    /**
     * Region's maximum capacity.
     * @var int
     */
    public $MaxCapacity;

    /**
     * The date when the element was created.
     */
    public $Date;

    /**
     * Marked true if is available for editing. 
     * @type bool
     */
    public $Active = false;
}