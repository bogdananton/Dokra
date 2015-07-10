<?php
namespace DokraApplication\api\v2\element\assets;

class Basic
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
     * The Region's description.
     * @var string
     */
    public $Description;

    /**
     * @var string
     */
    public $ExtraKey;
}