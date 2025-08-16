<?php

return [
    'recipe' => [
        'name' => [
            'required' => 'Recipe name is required.',
            'string' => 'Recipe name must be a string.',
            'max' => 'Recipe name must not exceed 255 characters.',
        ],
        'url' => [
            'string' => 'URL must be a string.',
            'max' => 'URL must not exceed 2048 characters.',
        ],
        'memo' => [
            'string' => 'Memo must be a string.',
        ],
        'steps' => [
            'id' => [
                'string' => 'Step ID must be a string.',
            ],
            'instruction' => [
                'string' => 'Step instruction must be a string.',
            ],
            'order' => [
                'integer' => 'Step order must be an integer.',
            ],
        ],
    ],
    'image' => [
        'images' => [
            'required' => 'First image file is required.',
            'file' => 'Please select a valid file.',
        ],
        'directory' => [
            'string' => 'Directory name must be a string.',
            'max' => 'Directory name must not exceed 255 characters.',
        ],
    ],
];
