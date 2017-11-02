<?php
include 'preProcess.php';
include 'possibleChar.php';

use CV\Mat;
use CV\Scalar;
use const CV\{
    CV_8UC3, RETR_LIST, CHAIN_APPROX_SIMPLE
};
use function CV\{
    imshow, findContoursWithoutHierarchy, drawContours
};

function detectPlatesInScene(Mat $imgOriginalScene)
{
    global $scalarWhite;
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
    $arrayOfArrayOfMatchingCharsInScene = findArrayOfArraysOfMatchingChars($arrayOfPossibleCharsInScene);
    if (SHOW_STEPS) {
        print_r('step 3 - vectorOfVectorsOfMatchingCharsInScene.size()' . count($arrayOfArrayOfMatchingCharsInScene));        // 13 with MCLRNF1 image

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
    \CV\waitKey(0);
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
        print_r("contours.size() = " . count($contours) . "\r\n");
        print_r("step 2 - intCountOfValidPossibleChars = " . $intCountOfPossibleChars . "\r\n");
        imshow("2a", $imgContours);
    }
    return ($arrayOfPossibleChars);

}