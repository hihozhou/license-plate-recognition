<?php


class PossiblePlate
{

    /**
     * @var CV\Mat
     */
    public $imgPlate;

    /**
     * @var CV\Mat
     */
    public $imgGrayScale;

    /**
     * @var CV\Mat
     */
    public $imgThresh;


    /**
     *
     * @var CV\RotatedRect
     */
    public $rrLocationOfPlateInScene;
}