<?php
include 'preProcess.php';
include 'possibleChar.php';
include 'possiblePlate.php';

use CV\Mat;
use CV\Scalar;
use CV\Point;
use CV\Size;
use CV\RotatedRect;
use const CV\{
    CV_8UC3, RETR_LIST, CHAIN_APPROX_SIMPLE, CV_PI
};
use function CV\{
    imshow, findContoursWithoutHierarchy, drawContours, getRotationMatrix2D, warpAffine, getRectSubPix, line, waitKey
};

const PLATE_WIDTH_PADDING_FACTOR = 1.3;
const PLATE_HEIGHT_PADDING_FACTOR = 1.5;

function detectPlatesInScene(Mat $imgOriginalScene)
{
    $vectorOfPossiblePlates = [];// this will be the return value
    global $scalarWhite;
    global $scalarRed;
    $imgGraysCaleScene = null;
    $imgThreshScene = null;
    $imgContours = Mat::zerosBySize($imgOriginalScene->size(), CV_8UC3);

    if (SHOW_STEPS) {
        imshow('origin', $imgOriginalScene);
    }
    preProcess($imgOriginalScene, $imgGraysCaleScene, $imgThreshScene);
    if (SHOW_STEPS) {
        imshow('1a', $imgGraysCaleScene);
        imshow('1b', $imgThreshScene);
    }
    $arrayOfPossibleCharsInScene = findPossibleCharsInScene($imgThreshScene);

    if (SHOW_STEPS) {
        print_r("step 2 - arrayOfPossibleCharsInScene.Count = " . count($arrayOfPossibleCharsInScene) . "\r\n");
        $imgContours = Mat::zerosBySize($imgOriginalScene->size(), CV_8UC3);
        $contours = [];
        foreach ($arrayOfPossibleCharsInScene as $possibleChar) {
            $contours[] = $possibleChar->contour;
        }
        drawContours($imgContours, $contours, -1, $scalarWhite);
        imshow("2b", $imgContours);
    }
    //success
    $arrayOfArrayOfMatchingCharsInScene = findArrayOfArraysOfMatchingChars($arrayOfPossibleCharsInScene);

    if (SHOW_STEPS) {
        print_r('step 3 - vectorOfVectorsOfMatchingCharsInScene.size() = ' . count($arrayOfArrayOfMatchingCharsInScene) . "\r\n");        // 13 with MCLRNF1 image
        $imgContours = Mat::zerosBySize($imgOriginalScene->size(), CV_8UC3);

        foreach ($arrayOfArrayOfMatchingCharsInScene as $vectorOfMatchingChars) {
            $intRandomBlue = rand(0, 256);
            $intRandomGreen = rand(0, 256);
            $intRandomRed = rand(0, 256);

            $contours = [];

            foreach ($vectorOfMatchingChars as $matchingChar) {
                $contours[] = $matchingChar->contour;
            }
            drawContours($imgContours, $contours, -1, new Scalar((double)$intRandomBlue, (double)$intRandomGreen, (double)$intRandomRed));
        }
        imshow("3", $imgContours);
    }

    foreach ($arrayOfArrayOfMatchingCharsInScene as $arrayOfMatchingChars) {
        $possiblePlate = extractPlate($imgOriginalScene, $arrayOfMatchingChars);
        if ($possiblePlate->imgPlate->empty() == false) {                                              // if plate was found
            $vectorOfPossiblePlates [] = $possiblePlate;                                        // add to vector of possible plates
        }
    }

    print_r(count($vectorOfPossiblePlates) . ' possible plates found' . "\r\n");       // 13 with MCLRNF1 image
    if (SHOW_STEPS) {
        imshow("4a", $imgContours);
        $i = 0;
        foreach ($vectorOfPossiblePlates as $possiblePlates) {
            $pts = $possiblePlates->rrLocationOfPlateInScene->points();
            for ($j = 0; $j < 4; $j++) {
                line($imgContours, $pts[$j], $pts[($j + 1) % 4], $scalarRed, 2);
            }
            imshow("4a", $imgContours);
            imshow("4b", $vectorOfPossiblePlates[$i]->imgPlate);
            $i++;
            print_r("possible plate " . $i . ", click on any image and press a key to continue . . .\r\n");


            waitKey(0);
        }

    }
    print_r("plate detection complete, click on any image and press a key to begin char recognition . . .\r\n");
    waitKey(0);


    return $vectorOfPossiblePlates;


}


/**
 * 找出图片中所有可能是文字的区域
 * @param Mat $imgThresh
 * @return array
 */
function findPossibleCharsInScene(Mat &$imgThresh)
{
    global $scalarWhite;
    $imgContours = Mat::zerosBySize($imgThresh->size(), CV_8UC3);
    $intCountOfPossibleChars = 0;
    $imgThreshCopy = $imgThresh->clone();
    $arrayOfPossibleChars = [];
    $contours = null;
    $point = null;
    findContoursWithoutHierarchy($imgThreshCopy, $contours, RETR_LIST, CHAIN_APPROX_SIMPLE, $point);        // find all contours
    if (SHOW_STEPS) {
        drawContours($imgContours, $contours, -1, $scalarWhite);
    }
    foreach ($contours as $key => $contour) {
        $possibleChar = new possibleChar($contour);
        if (checkIfPossibleChar($possibleChar)) {
            $intCountOfPossibleChars++;
            $arrayOfPossibleChars[] = $possibleChar;
        }
    }
    if (SHOW_STEPS) {
        print_r("step 2 - intCountOfValidPossibleChars = " . $intCountOfPossibleChars . "\r\n");
        imshow("2a", $imgContours);
    }
    return $arrayOfPossibleChars;

}


function sortCharsLeftToRight(PossibleChar $pcLeft, PossibleChar $pcRight)
{
    return $pcLeft->intCenterX > $pcRight->intCenterX;
}

function extractPlate(Mat $imgOriginal, array $vectorOfMatchingChars)
{
    $possiblePlate = new PossiblePlate();

    usort($vectorOfMatchingChars, "sortCharsLeftToRight");//字从左到又排序（数组按照矩阵从左到又排序)
    $vectorOfMatchingCharsLen = count($vectorOfMatchingChars);

    //计算车牌中心点
    $dblPlateCenterX = ($vectorOfMatchingChars[0]->intCenterX + $vectorOfMatchingChars[$vectorOfMatchingCharsLen - 1]->intCenterX) / 2.0;
    $dblPlateCenterY = ($vectorOfMatchingChars[0]->intCenterY + $vectorOfMatchingChars[$vectorOfMatchingCharsLen - 1]->intCenterY) / 2.0;

    $p2dPlateCenter = new Point($dblPlateCenterX, $dblPlateCenterY);

    //计算车牌长和宽
    $intPlateWidth = (int)(($vectorOfMatchingChars[$vectorOfMatchingCharsLen - 1]->boundingRect->x + $vectorOfMatchingChars[$vectorOfMatchingCharsLen - 1]->boundingRect->width - $vectorOfMatchingChars[0]->boundingRect->x) * PLATE_WIDTH_PADDING_FACTOR);

    $intTotalOfCharHeights = 0;
    foreach ($vectorOfMatchingChars as $matchingChar) {
        $intTotalOfCharHeights = $intTotalOfCharHeights + $matchingChar->boundingRect->height;
    }
    $dblAverageCharHeight = (double)$intTotalOfCharHeights / count($vectorOfMatchingChars);
    $intPlateHeight = (int)($dblAverageCharHeight * PLATE_HEIGHT_PADDING_FACTOR);

    //计算车牌修正的角度
    $dblOpposite = $vectorOfMatchingChars[$vectorOfMatchingCharsLen - 1]->intCenterY - $vectorOfMatchingChars[0]->intCenterY;
    $dblHypotenuse = distanceBetweenChars($vectorOfMatchingChars[0], $vectorOfMatchingChars[$vectorOfMatchingCharsLen - 1]);
    $dblCorrectionAngleInRad = asin($dblOpposite / $dblHypotenuse);
    $dblCorrectionAngleInDeg = $dblCorrectionAngleInRad * (180.0 / CV_PI);

    // assign rotated rect member variable of possible plate
    // 获取可能是车牌中可旋转矩阵
    $possiblePlate->rrLocationOfPlateInScene = new RotatedRect($p2dPlateCenter, new Size($intPlateWidth, $intPlateHeight), $dblCorrectionAngleInDeg);
    $dblCorrectionAngleInDeg = (double)number_format($dblCorrectionAngleInDeg, 6);

    $rotationMatrix = getRotationMatrix2D($p2dPlateCenter, $dblCorrectionAngleInDeg, 1.0);
    $imgRotated = null;
//    $rotationMatrix->print(\CV\Formatter::FMT_PYTHON);
    warpAffine($imgOriginal, $imgRotated, $rotationMatrix, $imgOriginal->size());//旋转变换图像
    $imgCropped = null;
    //裁剪旋转图像的实际板部分。
    getRectSubPix($imgRotated, $possiblePlate->rrLocationOfPlateInScene->size, $possiblePlate->rrLocationOfPlateInScene->center, $imgCropped);

    // 将裁剪后的板图像复制到$possiblePlate的成员变量imgPlate中。
    $possiblePlate->imgPlate = $imgCropped;
    return $possiblePlate;


}