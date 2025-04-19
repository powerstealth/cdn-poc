<?php

return [

    'profiles' => [

        'standard' => [
            'video' => [
                'max_upload_size' => (500 * 1024 * 1024), // Bytes
                'max_transcoding_quality' => \Modules\Asset\Domain\Enums\TranscodingQualityBitrateEnum::FHD,
                'max_length' => (60 * 1) // Seconds
            ]
        ],

        'early_adopter' => [
            'video' => [
                'max_upload_size' => (500 * 1024), // Bytes
                'max_transcoding_quality' => \Modules\Asset\Domain\Enums\TranscodingQualityBitrateEnum::FHD,
                'max_length' => (60 * 1) // Seconds
            ]
        ]

    ],

    'default' => 'standard'

];
