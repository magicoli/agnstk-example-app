<?php
/*
|--------------------------------------------------------------------------
| Default Pages Configuration
|--------------------------------------------------------------------------
|
| This configuration defines all default pages for the AGNSTK application.
| Developers can easily add, modify, or disable pages here without altering
| the core application code.
|
*/

return [
    'about' => [
        // 'title' => 'About',
        'uri' => '/',                 // Defaults to key (page id)
        'source' => 'README.md',            // Markdown file to render
        // 'menu' => true,                     // boolean, string or array, defaults to true
        // 'menu' => 'main',                // string: menu_id to add to, defaults to 'main'
        // 'menu' => [                      // array: menu configuration
        //     'menu_id' => 'main',         // Defaults to 'main'
        //     'label' => 'About AGNSTK',   // Defaults to title
        //     'order' => 10,               // Defaults to 10
        //     'enabled' => true,           // Defaults to false
        // ],
        // 'enabled' => false,              // boolean|logged_in|logged_out|auth_required
    ],
    'developers' => [
        'title' => 'Developers (core)',
        'source' => 'lib/agnstk/DEVELOPERS.md', // Show content from inside library in main app
        // 'title' => 'Developers (main app)',
        // 'source' => 'DEVELOPERS.md', // This should fail since DEVELOPERS.md doesn't exist in app root
        'menu' => true,
    ],
    'non-existent' => [
        'title' => 'Non Existent',
        'source' => 'NON-EXISTENT.md', // This file does not exist
        'menu' => true,
    ],
    
    'callback-page' => [
        'title' => 'Callback Page',
        'callback' => 'HelloService@render',
        'menu' => true,
    ],

    'static-page' => [
        'title' => 'Static Page',
        'content' => '
            <p>
                This page is entirely defined in pages config, including its content.
                It should not be confused with a service or dynamic content.
            </p>
            <hr>
            <p>It could contain any <span class="tag">html code</span> and should be rendered as is.</p>
            <p>
                It should be post-procesed like any other page content for shortcodes, or standard 
                Laravel layout tags.
            </p>            
            <div class="col-lg-6 mx-auto card">
                <div class="card-header">
                    <h3 class="p-0 m-0">Testing shortcode processing in static HTML:</h3>
                </div>
                <div class="card-body">
                    <div class="text-bold">
                        [hello title="Hello (with shortcode)" class=""]
                    </div>
                    <hr>
                    <div class="text-bold">
                        {{hello title="Hello (with double bracked tag)" class=""}}
                    </div>
                </div>
            </div>
        ',
        'menu' => true,
    ],

    'markdown-page' => [
        'source' => 'HELLO.md', // Show README content on this page
        'menu' => true,
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'uri' => '/user/dashboard',
        'view' => 'dashboard-content',
        'menu' => [
            'menu_id' => 'user', // Defaults to 'main'
            'label' => 'Dashboard',
            'order' => 90, // Defaults to 10
            'enabled' => true, // Defaults to false
        ],
        'auth_required' => true,
        'enabled' => 'auth_required', // Only enabled for logged-in users
    ],
];
