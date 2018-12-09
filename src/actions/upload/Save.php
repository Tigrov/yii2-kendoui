<?php

namespace tigrov\kendoui\actions\upload;

use tigrov\kendoui\components\UploadedFile;
use yii\base\Action;
use yii\helpers\FileHelper;

class Save extends Action
{
    /** @var string[] allowed input names (any names if null) */
    public $allowedNames;

    /** @var string path where files will be uploaded */
    public $uploadPath;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $files = UploadedFile::getInstancesByNames($this->allowedNames);
        if (isset($_POST['metadata'])) {
            $this->uploadChunk($files[0]);
        } else {
            $this->uploadFiles($files);
        }
    }

    /**
     * Uploads a chunk of file
     * @param UploadedFile $file
     * @return array
     */
    public function uploadChunk($file)
    {
        $metaData = json_decode($_POST['metadata']);
        $content = file_get_contents($file->tempName);
        $fullPath = $this->uploadPath . DIRECTORY_SEPARATOR . $metaData->fileName;

        file_put_contents($fullPath, $content, FILE_APPEND);

        return [
            'uploaded' => $metaData->totalChunks - 1 <= $metaData->chunkIndex,
            'fileUid' => $metaData->uploadUid,
        ];
    }

    /**
     * Uploads all files
     * @param UploadedFile[] $files
     */
    public function uploadFiles($files)
    {
        foreach ($files as $file) {
            FileHelper::createDirectory($this->uploadPath);

            $filename = $file->baseName . ($file->extension ? '.' . $file->extension : '');

            $fullPath = $this->uploadPath . DIRECTORY_SEPARATOR . $filename;
            $file->saveAs($fullPath);
        }
    }
}