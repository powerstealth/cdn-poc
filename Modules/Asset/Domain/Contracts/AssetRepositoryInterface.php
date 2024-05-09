<?php

namespace Modules\Asset\Domain\Contracts;

use Modules\Asset\Domain\Models\Asset;

interface AssetRepositoryInterface
{
    public function createAssetFromUpload(
        string $status,
        string $title,
        string $description,
        string $s3SessionKey,
        string $fileType,
        int $fileLength,
        bool $clyUpTv,
        bool $clyUpFrontStore
    ): Asset|\Exception;
}