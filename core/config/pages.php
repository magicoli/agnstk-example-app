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
        'title' => 'Developers',
        'source' => 'core/DEVELOPERS.md', // Show README content on home page
        'menu' => true,
    ],
    
    'dashboard' => [
        'title' => 'Dashboard',
        'uri' => '/user/dashboard',
        'view' => 'dashboard-content',
        'menu' => 'user',
        // 'auth_required' => true,
        'enabled' => 'auth_required', // Only enabled for logged-in users
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
            <p>
                Post-processing (like shortcodes) must be applied to any block, regardless of the type 
                of source or the view() used.
            </p>
            <p>
                This is a static page, but it can still use shortcodes, blocks, or any other dynamic content
                that is registered in the system.
            </p>
            <p>
                We might need to create a Trait for renderable classes, to make sure we have a common default
                render post-processiig method, applied to the content of render().
            </p>
            <div class="col-lg-6 mx-auto card">
                <div class="card-header">
                    <h3 class="p-0 m-0">Testing shortcode processing in static HTML:</h3>
                </div>
                <div class="card-body">
                    <div class="text-bold">
                        [hello title="Static Page Hello" class="bg-primary text-white"]
                    </div>
                </div>
            </div>
        ',
        'menu' => true,
    ],

    'rendered-service' => [
        'title' => 'Rendered Service',
        'callback' => 'HelloService@render',
        'menu' => true,
    ],

    'hello-page-md' => [
        'source' => 'HELLO.md', // Show README content on this page
        'menu' => true,
    ],
];
