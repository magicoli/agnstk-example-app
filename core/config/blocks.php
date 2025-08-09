<?php
/*
|--------------------------------------------------------------------------
| Default Blocks Configuration
|--------------------------------------------------------------------------
|
| Default blocks that can be used by pages
|
*/

return [
    'example_block' => [
        'title' => 'Example Block',
        'description' => 'This is an example block that can be used in pages.',
        'slug' => 'example-block',
        'shortcode' => 'example_block', // Shortcode to use in content
        'content' => '<p>Example block content goes here.</p>',
        'view' => 'example-block',
        'callback' => 'App\\Blocks\\ExampleBlock',
        'source' => 'example.md', // Optional source file
        'enabled' => true,
        'attributes' => [
            'class' => 'example-block',
            'data-example' => 'true',
        ],
    ],
];
