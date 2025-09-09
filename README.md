# AGNSTK Core Framework

[![License: AGPL-v3](https://img.shields.io/badge/License-AGPLv3-yellow.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php)](https://www.php.net/)
[![CMS Agnostic](https://img.shields.io/badge/CMS-Agnostic-ff69b4)](https://agnstk.org)

This is the **AGNSTK Core Framework** - the foundational library that powers AGNSTK applications.

⚠️ **This repository should NOT be used directly!** 

## Installation

**For new projects:**
Use the main AGNSTK application template instead:
```bash
git clone https://github.com/magicoli/agnstk.git
cd agnstk
composer install
composer run dev
```

**For existing projects:**
This core is automatically included as a git subtree in AGNSTK applications.

## What is this?

This directory contains the core AGNSTK framework that should **NOT be modified directly**. It's managed as a git subtree from the [magicoli/agnstk-core](https://github.com/magicoli/agnstk-core) repository.

## For Application Developers

- **DON'T EDIT** files in this `core/` directory
- **PUT YOUR CODE** in the application root directories (`src/`, `config/`, etc.)  
- **CONFIGURE** your application by editing `core/config/bundle.php`
- **UPDATE THE CORE** using `git subtree pull --prefix=core https://github.com/magicoli/agnstk-core.git master --squash`

### Bundle Configuration Setup

After cloning your AGNSTK application, you need to enable bundle configuration tracking:

1. **Remove the ignore rule** from your main `.gitignore`:
   ```bash
   # Remove this line from .gitignore:
   core/config/bundle.php
   ```

2. **Track your bundle config**:
   ```bash
   git add core/config/bundle.php
   git commit -m "Add bundle configuration"
   ```

3. **Customize as needed** - edit `core/config/bundle.php` to match your application structure.

## For AGNSTK Core Contributors

If you want to contribute to the AGNSTK framework itself:

1. Work on the core repository: https://github.com/magicoli/agnstk-core
2. Changes will be pulled into applications via git subtree updates

## Architecture

```
your-agnstk-app/
├── src/                    ← YOUR APPLICATION CODE
├── core/                   ← AGNSTK FRAMEWORK (this directory)
│   ├── config/
│   │   ├── bundle.php     ← YOUR CONFIGURATION
│   │   └── bundle.example.php ← Template
│   ├── app/               ← Framework services
│   └── ...                ← Other framework files
└── ...
```

The core provides:
- Laravel 12 foundation
- AGNSTK services and helpers
- Default pages and blocks
- Multi-platform deployment adapters

## Updates

Update the core framework:
```bash
git subtree pull --prefix=core https://github.com/magicoli/agnstk-core.git master --squash
```

This uses git subtree to pull the latest changes from agnstk-core while preserving your application code.

---

**Need help?** Check the main README.md in the application root or visit https://agnstk.org
