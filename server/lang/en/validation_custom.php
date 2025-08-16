<?php

return [
    'recipe' => [
        'name' => [
            'required' => 'Recipe name is required.',
            'string' => 'Recipe name must be a string.',
            'max' => 'Recipe name must not exceed 255 characters.',
        ],
        'url' => [
            'url' => 'Please enter a valid URL.',
            'max' => 'URL must not exceed 2048 characters.',
        ],
        'memo' => [
            'string' => 'Memo must be a string.',
        ],
        'category_ids' => [
            'array' => 'Categories must be specified as an array.',
            'exists' => 'The specified category does not exist.',
        ],
        'ingredients' => [
            'array' => 'Ingredients must be specified as an array.',
            'required' => 'Ingredients are required.',
        ],
        'steps' => [
            'array' => 'Steps must be specified as an array.',
            'required' => 'Steps are required.',
            'id' => [
                'string' => 'Step ID must be a string.',
            ],
            'instruction' => [
                'string' => 'Step instruction must be a string.',
            ],
            'image' => [
                'array' => 'Step image must be an array.',
                'url' => [
                    'string' => 'Step image URL must be a string.',
                ],
                'width' => [
                    'integer' => 'Step image width must be an integer.',
                ],
                'height' => [
                    'integer' => 'Step image height must be an integer.',
                ],
            ],
            'order' => [
                'integer' => 'Step order must be an integer.',
            ],
        ],
    ],
    'shopping' => [
        'name' => [
            'required' => 'Item name is required.',
            'string' => 'Item name must be a string.',
            'max' => 'Item name must not exceed 255 characters.',
        ],
        'category_id' => [
            'required' => 'Category is required.',
            'exists' => 'The specified category does not exist.',
        ],
        'quantity' => [
            'numeric' => 'Quantity must be a number.',
            'min' => 'Quantity must be 0 or greater.',
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
    'user' => [
        'name' => [
            'required' => 'Name is required.',
            'string' => 'Name must be a string.',
            'max' => 'Name must not exceed 255 characters.',
        ],
        'email' => [
            'required' => 'Email address is required.',
            'email' => 'Please enter a valid email address.',
            'max' => 'Email address must not exceed 255 characters.',
            'unique' => 'This email address is already in use.',
        ],
        'password' => [
            'required' => 'Password is required.',
            'confirmed' => 'Password confirmation does not match.',
            'min' => 'Password must be at least 8 characters.',
        ],
    ],
];
