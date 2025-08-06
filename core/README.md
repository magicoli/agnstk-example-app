# AGNSTK Core Framework

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
- **CONFIGURE** your application via `config/app-defaults.php`
- **UPDATE THE CORE** using `composer run agnstk-update`

## For AGNSTK Core Contributors

If you want to contribute to the AGNSTK framework itself:

1. Work on the main repository: https://github.com/magicoli/agnstk-core
2. Changes will be pulled into applications via git subtree updates

## Architecture

```
your-agnstk-app/
├── src/                    ← YOUR APPLICATION CODE
├── config/app-defaults.php ← YOUR CONFIGURATION
├── core/                   ← AGNSTK FRAMEWORK (this directory)
│   ├── app/               ← Framework services
│   ├── config/            ← Framework configuration
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
composer run agnstk-update
```

This uses git subtree to pull the latest changes from agnstk-core while preserving your application code.

---

**Need help?** Check the main README.md in the application root or visit https://agnstk.org
