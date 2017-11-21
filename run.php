<?php

include 'detectChars.php';
include 'detectPlates.php';

use CV\Size;
use CV\Scalar;
use function CV\imread;
use function CV\{
    imshow, waitKey
};

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

const SHOW_STEPS = true;
$gaussianSmoothFilterSize = new Size(5, 5);
const ADAPTIVE_THRESH_BLOCK_SIZE = 19;
const ADAPTIVE_THRESH_WEIGHT = 9;
$scalarRed = new Scalar(0.0, 0.0, 255.0);

function start()
{
    if (!loadKNNDataAndTrainKNN()) {
        die('error: error: KNN traning was not successful');
    }

    $imgOriginalScene = imread("images/image1.png");         // open image
    imshow('origin', $imgOriginalScene);
    if ($imgOriginalScene->empty()) {                             // if unable to open image
        die("error: image not read from file\n\n");     // show error message on command line
    }

    $vectorOfPossiblePlates = detectPlatesInScene($imgOriginalScene);
}


start();