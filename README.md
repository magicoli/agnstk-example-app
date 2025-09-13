# Example App - Agnostic Glue for Non-Specific ToolKits

**One Core. Any CMS.**

[![License: AGPL-v3](https://img.shields.io/badge/License-AGPLv3-yellow.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php)](https://www.php.net/)
[![CMS Agnostic](https://img.shields.io/badge/CMS-Agnostic-ff69b4)](https://agnstk-example-app.org)

---

## What is AGNSTK?

AGNSTK (pronounced *"ag-nostic"*) is the **duct tape for your CMS plugins**—except it’s reusable, elegant, and won’t leave sticky residue. It’s a **single codebase** that adapts to WordPress, Drupal, October CMS, Laravel, and more.

Think of it as:
- A **Swiss Army knife** for CMS development.
- A **universal adapter** for your PHP tools.
- The **glue** that binds your logic to any platform.

---

## Why Use AGNSTK?

- ✅ **Write once, deploy anywhere** – No more rewriting for each platform.
- ✅ **Multiple deployment targets** – CMS plugins, desktop apps, mobile apps, CLI tools, web apps.
- ✅ **Lightweight core** – Only ~50KB of glue code (the rest is your logic).
- ✅ **No lock-in** – Your business logic stays clean and portable.
- ✅ **Cross-platform** – Windows, macOS, Linux support across all deployment targets.
- ✅ **Fun to say** – "AGNSTK" sounds like a robot sneezing.

**Why "TransKit"?**
Because "ToolKit" was too boring. AGNSTK transforms your code to fit anywhere—like a chameleon, but for PHP.

---

## How It Works

AGNSTK is built **on Laravel**, providing a robust core for your logic. Multiple deployment targets then make this core available across different platforms and interfaces.

```plaintext
agnstk-example-app/
├── index.php              # Standalone web entry
├── console                # Command-line tool entry
├── exampleapp.php         # WordPress plugin entry
├── exampleapp.module      # Drupal module entry  
├── Plugin.php             # October CMS plugin entry
...                        # Other CMS-specific entry points
├── src/                   # Developer application code (YOUR CODE GOES HERE)
│   ├── Blocks/            # HTML blocks for embedding
│   ├── Pages/             # Page content providers
│   ├── Menus/             # Menu definitions
│   ├── Shortcodes/        # Shortcode handlers
│   ├── Users/             # User management
│   └── Services/          # Cache, sync, external services, etc.
├── core/                   # Laravel core (DO NOT MODIFY except core/config/bundle.php)
│   ├── config/
|   |   ├── bundle.php     #  (YOUR APP INITIAL SETTINGS)
|   |   ├── bundle.example.php     # bundle.php template
│   ...                    # Other core files (DO NOT MODIFY)
├── database/
├── routes/
...                        
```

(Want another CMS or deployment target? Open an issue!)

## Deployment Targets

Choose how to deploy your AGNSTK application:

### 🌐 **Web Applications**
- **Standalone Laravel app**: `composer run dev` → `http://localhost:8000`
- **CMS Plugins**: WordPress, Drupal, October CMS, Joomla
- **REST API**: JSON API for data sync across deployment targets

### 🖥️ **Desktop Applications** 
- **Cross-platform native**: Tauri + embedded PHP runtime
- **Truly standalone**: No PHP installation required, works offline
- **Data sync**: Optional API integration for shared data
- **All major OS**: Windows, macOS, Linux

### 📱 **Mobile Applications**
- **Progressive Web Apps (PWA)**: Optimized web app experience, works offline
- **Native mobile apps**: iOS and Android via Tauri Mobile
- **App store ready**: Distribute through official app stores
- **Offline-first**: Sync when connected, work when offline

### ⚡ **Command Line Tools**
- **CLI interface**: Access core features via terminal
- **Automation friendly**: Perfect for scripts and DevOps workflows
- **API integration**: Bulk operations and data management
- **Cross-platform**: Same commands work everywhere

Developers can enable any combination of these deployment targets for their application.

**Data Sync Architecture:**
- **Standalone mode**: Each deployment works independently with local data
- **Connected mode**: Optional API sync allows shared data across all deployments
- **Hybrid approach**: Mix standalone and connected deployments as needed
- **Offline-first**: Apps work without internet, sync when available

## Installation

**For new projects:**
Clone this repository (which includes the core as a git subtree):

```bash
git clone https://github.com/magicoli/agnstk-example-app.git my-app
cd my-app
composer install
```

**Enable bundle configuration tracking:**
```bash
# Remove this line from .gitignore:
core/config/bundle.php

# Then track your bundle config:
git add core/config/bundle.php
git commit -m "Add bundle configuration"
```

Then start the development server (Laravel-based standalone app):
```bash
composer run dev
```
The app will be available at `http://localhost:8000`.

**To update the core framework:**
```bash
git subtree pull --prefix=core https://github.com/magicoli/agnstk.git master --squash
```

### Example: Simple Service

Creating a basic AGNSTK service is straightforward - just extend BaseService and implement the essentials:

```php
// src/Services/Example.php
namespace ExampleApp\Services;

use App\Services\BaseService;

class Example extends BaseService {
    /**
     * Configure your service
     */
    public static function init(): void {
        if (static::$initialized) return;
        
        static::$label = _('Example');
        static::$uri = '/example';
        static::$defaultTitle = _('Example Page');
        static::$defaultContent = _('Welcome to AGNSTK!');
        
        parent::init();
    }

    /**
     * Define what your service provides
     */
    public static function provides(): array {
        if (!empty(static::$provides)) return static::$provides;
        
        static::init();
        static::$provides = [
            'shortcode' => 'example',      // Enables {{example}} shortcodes
            'page' => [                    // Creates /example page
                'title' => static::$defaultTitle,
                'uri' => static::$uri,
            ],
            'menu' => [                    // Adds menu item
                'label' => static::$label,
                'uri' => static::$uri,
                'enabled' => true,
            ],
        ];
        
        return static::$provides;
    }

    /**
     * Your core functionality
     */
    public function render(array $options = []): array {
        $content = $options['content'] ?? $this->content ?? static::$defaultContent;
        
        return [
            'title' => $this->title,
            'content' => $content,
        ];
    }
}
```

That's it! Your service now automatically provides:
- **{{example}}** shortcodes in content
- **/example** page route  
- **Example** menu item
- **Block embedding** capability

Now use **{{example}}** in any CMS content!

## ExampleApp - AGNSTK Proof of Concept

This is a CMS-agnostic application framework that allows you to write your core business logic once and deploy it across multiple platforms.

### Current Status
AGNSTK is currently implemented as a **Laravel 12** application with multiple deployment targets:
- **Laravel standalone app**: Available at `http://localhost:8000` when running `composer run dev`
- **WordPress plugin**: Install as plugin with `exampleapp.php` as main file
- **Drupal module**: Available in `deploy/adapters/drupal/` 
- **October CMS plugin**: Available in `deploy/adapters/octobercms/`
- **Desktop app**: Tauri-based native application (in development)
- **Mobile PWA**: Progressive Web App with offline capabilities (in development)  
- **Mobile native**: iOS/Android apps via Tauri Mobile (in development)
- **REST API**: JSON API for cross-deployment data sync (in development)
- **CLI tool**: Command-line interface (in development)

### Quick Test
1. **Standalone Laravel**: Run `composer run dev` and visit `http://localhost:8000`
2. **WordPress**: Copy the full project folder to your plugins directory and activate (for production, delete irrelevant deployment targets)

### Core Features
- **Laravel 12** framework providing robust AGNSTK core (hands-off for developers)
- **Configurable objects**: Pages, Menus, Blocks, Shortcodes, Users, Services
- **Developer-friendly**: Put your code in `src/`, configure via `core/config/bundle.php`
- **Bootstrap UI**: Clean, responsive interface for web deployments
- **Markdown support**: Content rendering with syntax highlighting
- **Cross-platform deployment**: Same codebase runs on web, desktop, and CLI
- Platform-specific adapters handle CMS integration and user authentication

## Contributing

- Found a bug? [github.com/magicoli/agnstk-example-app/issues](https://github.com/magicoli/agnstk-example-app/issues)
- Want help or discuss a related matter? [github.com/magicoli/agnstk-example-app/discussions](https://github.com/magicoli/agnstk-example-app/discussions)
- Want to add a CMS? Open a an issue on GitHub.

**Code of Conduct**: Be nice. We’re all just trying to glue things together.
License: MIT (use it, break it, fix it).


---

Made with ❤️ and duct tape by [magicoli](https://github.com/magicoli).
