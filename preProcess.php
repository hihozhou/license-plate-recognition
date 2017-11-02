<?php
use CV\Mat;
use CV\Size;
use function CV\cvtColor;
use function CV\{
    getStructuringElement, morphologyEx, GaussianBlur, adaptiveThreshold
};
use const CV\{
    COLOR_BGR2HSV, MORPH_RECT, MORPH_TOPHAT, MORPH_BLACKHAT, ADAPTIVE_THRESH_GAUSSIAN_C, THRESH_BINARY_INV
};

/**
 * 图片预处理
 * @param Mat $imgOriginal
 * @param $imgGrayScale
 * @param $imgThreshScene
 */
function preProcess(Mat &$imgOriginal, &$imgGrayScale, &$imgThresh)
{
    global $gaussianSmoothFilterSize;
    $imgGrayScale = extractValue($imgOriginal);//获取图像灰度
    $imgMaxContrastGrayScale = maximizeContrast($imgGrayScale);

    $imgBlurred = null;
    GaussianBlur($imgMaxContrastGrayScale, $imgBlurred, $gaussianSmoothFilterSize, 0);

    adaptiveThreshold($imgBlurred, $imgThresh, 255.0, ADAPTIVE_THRESH_GAUSSIAN_C, THRESH_BINARY_INV, ADAPTIVE_THRESH_BLOCK_SIZE, ADAPTIVE_THRESH_WEIGHT);
}

/**
 * 获取图像灰度图
 * @param Mat $imgOriginal
 * @return Mat
 */
function extractValue(Mat &$imgOriginal)
{
    $imgHSV = cvtColor($imgOriginal, COLOR_BGR2HSV);

    $arrayOfHSVImages = CV\split($imgHSV);

    return $arrayOfHSVImages[2];
}

/**
 * @param Mat $imgGrayScale
 * @return Mat
 */
function maximizeContrast(Mat &$imgGrayScale)
{
    $imgTopHat = null;
    $imgBlackHat = null;
    $structuringElement = getStructuringElement(MORPH_RECT, new Size(3, 3));
    morphologyEx($imgGrayScale, $imgTopHat, MORPH_TOPHAT, $structuringElement);
    morphologyEx($imgGrayScale, $imgBlackHat, MORPH_BLACKHAT, $structuringElement);
    $imgGrayScalePlusTopHat = Mat::add($imgGrayScale, $imgTopHat);
    $imgGrayScalePlusTopHatMinusBlackHat = Mat::subtract($imgGrayScalePlusTopHat, $imgBlackHat);
    return $imgGrayScalePlusTopHatMinusBlackHat;
}