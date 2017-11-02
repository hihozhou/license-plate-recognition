<?php
use CV\FileStorage;
use CV\ML\KNearest;
use const CV\CV_PI;

const MIN_PIXEL_WIDTH = 2;
const MIN_PIXEL_HEIGHT = 8;

const MIN_ASPECT_RATIO = 0.25;
const MAX_ASPECT_RATIO = 1.0;

const MIN_PIXEL_AREA = 80;

const MAX_DIAG_SIZE_MULTIPLE_AWAY = 5.0;

const MAX_ANGLE_BETWEEN_CHARS = 12.0;
const MAX_CHANGE_IN_AREA = 0.5;
const MAX_CHANGE_IN_WIDTH = 0.8;
const MAX_CHANGE_IN_HEIGHT = 0.2;

const MIN_NUMBER_OF_MATCHING_CHARS = 3;


/**
 * 加载KNN
 * @return bool
 */
function loadKNNDataAndTrainKNN()
{
    //读取训练分类器
    $fsClassifications = new FileStorage('classifications.xml', FileStorage::READ);//读取分配器文件
    if (!$fsClassifications->isOpened()) {
        echo "错误, 无法打开训练分类文件, 程序退出\n\n";
        return false;
    }
    $matClassificationInts = $fsClassifications->read('classifications', 5);
    $fsClassifications->release();

    $fsTrainingImages = new FileStorage('images.xml', FileStorage::READ);
    if (!$fsTrainingImages->isOpened()) {
        echo "错误, 无法打开训练图片文件, 程序退出\n\n";
        return false;
    }
    $matTrainingImagesAsFlattenedFloats = $fsTrainingImages->read('images', 5);
    $fsTrainingImages->release();

    $KNearest = KNearest::create();
    $KNearest->setDefaultK(1);
    return $KNearest->train($matTrainingImagesAsFlattenedFloats, CV\ML\ROW_SAMPLE, $matClassificationInts);

}


function checkIfPossibleChar($possibleChar)
{
    if ($possibleChar->boundingRect->area() > MIN_PIXEL_AREA &&
        $possibleChar->boundingRect->width > MIN_PIXEL_WIDTH &&
        $possibleChar->boundingRect->height > MIN_PIXEL_HEIGHT &&
        MIN_ASPECT_RATIO < $possibleChar->dblAspectRatio &&
        $possibleChar->dblAspectRatio < MAX_ASPECT_RATIO
    ) {
        return true;
    }
    return false;
}

function findArrayOfArraysOfMatchingChars(array $arrayOfPossibleChars)
{
    $arrayOfArraysOfMatchingChars = [];
    foreach ($arrayOfPossibleChars as $possibleChar) {
        $arrayOfMatchingChars = findVectorOfMatchingChars($possibleChar, $arrayOfPossibleChars);
        $arrayOfMatchingChars[] = $possibleChar;
        if (count($arrayOfMatchingChars) < MIN_NUMBER_OF_MATCHING_CHARS) {
            continue;
        }
        $arrayOfArraysOfMatchingChars[] = $arrayOfMatchingChars;
        $arrayOfPossibleCharsWithCurrentMatchesRemoved = [];
        foreach ($arrayOfPossibleChars as $possChar) {
            if (in_array($possChar, $arrayOfMatchingChars) && $possChar == end($arrayOfMatchingChars)) {//todo
                $arrayOfPossibleCharsWithCurrentMatchesRemoved[] = $possChar;
            }
        }
        $recursiveVectorOfVectorsOfMatchingChars = findArrayOfArraysOfMatchingChars($arrayOfPossibleCharsWithCurrentMatchesRemoved);
        foreach ($recursiveVectorOfVectorsOfMatchingChars as $recursiveVectorOfMatchingChars) {      // for each vector of matching chars found by recursive call
            $arrayOfArraysOfMatchingChars[] = $recursiveVectorOfMatchingChars;               // add to our original vector of vectors of matching chars
        }

        break;

    }
    return $arrayOfArraysOfMatchingChars;
}


function findVectorOfMatchingChars($possibleChar, $arrayOfChars)
{

    $arrayOfMatchingChars = [];
    foreach ($arrayOfChars as $possibleMatchingChar) {
        if ($possibleMatchingChar->contour == $possibleChar->contour) {
            continue;
        }
        $dblDistanceBetweenChars = distanceBetweenChars($possibleChar, $possibleMatchingChar);
        $dblAngleBetweenChars = angleBetweenChars($possibleChar, $possibleMatchingChar);
        $dblChangeInArea = (double)abs($possibleMatchingChar->boundingRect->area() - $possibleChar->boundingRect->area()) / (double)$possibleChar->boundingRect->area();
        $dblChangeInWidth = (double)abs($possibleMatchingChar->boundingRect->width - $possibleChar->boundingRect->width) / (double)$possibleChar->boundingRect->width;
        $dblChangeInHeight = (double)abs($possibleMatchingChar->boundingRect->height - $possibleChar->boundingRect->height) / (double)$possibleChar->boundingRect->height;

        // check if chars match
        if ($dblDistanceBetweenChars < ($possibleChar->dblDiagonalSize * MAX_DIAG_SIZE_MULTIPLE_AWAY) &&
            $dblAngleBetweenChars < MAX_ANGLE_BETWEEN_CHARS &&
            $dblChangeInArea < MAX_CHANGE_IN_AREA &&
            $dblChangeInWidth < MAX_CHANGE_IN_WIDTH &&
            $dblChangeInHeight < MAX_CHANGE_IN_HEIGHT
        ) {
            $arrayOfMatchingChars[] = $possibleMatchingChar;      // if the chars are a match, add the current char to vector of matching chars
        }

    }
    return $arrayOfMatchingChars;
}


function distanceBetweenChars(PossibleChar $firstChar, PossibleChar $secondChar)
{
    $intX = abs($firstChar->intCenterX - $secondChar->intCenterX);
    $intY = abs($firstChar->intCenterY - $secondChar->intCenterY);

    return (sqrt(pow($intX, 2) + pow($intY, 2)));
}

function angleBetweenChars(PossibleChar $firstChar, PossibleChar $secondChar)
{
    $dblAdj = abs($firstChar->intCenterX - $secondChar->intCenterX);
    $dblOpp = abs($firstChar->intCenterY - $secondChar->intCenterY);

    $dblAngleInRad = atan($dblOpp / $dblAdj);

    $dblAngleInDeg = $dblAngleInRad * (180.0 / CV_PI);

    return ($dblAngleInDeg);
}
