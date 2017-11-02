<?php

use function CV\boundingRect;

class possibleChar
{

    public $contour;

    /**
     * @var \CV\Rect
     */
    public $boundingRect;

    /**
     * @var int
     */
    public $intCenterX;

    /**
     * @var int
     */
    public $intCenterY;

    /**
     * @var double
     */
    public $dblDiagonalSize;

    /**
     * @var double
     */
    public $dblAspectRatio;

    public function __construct(array $contour)
    {
        $this->contour = $contour;
        $this->boundingRect = boundingRect($contour);
        $this->intCenterX = ($this->boundingRect->x + $this->boundingRect->x + $this->boundingRect->width) / 2;
        $this->intCenterY = ($this->boundingRect->y + $this->boundingRect->y + $this->boundingRect->height) / 2;
        $this->dblDiagonalSize = sqrt(pow($this->boundingRect->width, 2) + pow($this->boundingRect->height, 2));
        $this->dblAspectRatio = (float)$this->boundingRect->width / (float)$this->boundingRect->height;
    }
}