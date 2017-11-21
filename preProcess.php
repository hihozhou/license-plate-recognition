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
    $imgMaxContrastGrayScale = maximizeContrast($imgGrayScale);//提高图像对比度

    $imgBlurred = null;
    GaussianBlur($imgMaxContrastGrayScale, $imgBlurred, $gaussianSmoothFilterSize, 0);//高斯滤波

    adaptiveThreshold($imgBlurred, $imgThresh, 255.0, ADAPTIVE_THRESH_GAUSSIAN_C, THRESH_BINARY_INV, ADAPTIVE_THRESH_BLOCK_SIZE, ADAPTIVE_THRESH_WEIGHT);//二值化图像
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
 * 最大限度地提高图像对比度
 * @param Mat $imgGrayScale
 * @return Mat
 */
function maximizeContrast(Mat &$imgGrayScale)
{
    $imgTopHat = null;
    $imgBlackHat = null;
    $structuringElement = getStructuringElement(MORPH_RECT, new Size(3, 3));
    morphologyEx($imgGrayScale, $imgTopHat, MORPH_TOPHAT, $structuringElement);//计算顶帽矩阵
    morphologyEx($imgGrayScale, $imgBlackHat, MORPH_BLACKHAT, $structuringElement);//计算黑帽矩阵
    $imgGrayScalePlusTopHat = Mat::add($imgGrayScale, $imgTopHat);//灰度图矩阵和顶帽矩阵相加
    $imgGrayScalePlusTopHatMinusBlackHat = Mat::subtract($imgGrayScalePlusTopHat, $imgBlackHat);//灰度顶帽相加矩阵减去黑帽举证
    return $imgGrayScalePlusTopHatMinusBlackHat;
}