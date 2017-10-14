<?php

use CV\FileStorage;
use CV\ML\KNearest;

/**
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