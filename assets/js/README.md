# React Build Environment

This directory contains the React-based admin interface for the SEO Content Generator plugin.

## Directory Structure

```
assets/js/
├── src/                  # Source files (edit these)
│   ├── components/       # React components
│   │   └── PageEditor/   # Main page editor component
│   └── index.js          # Entry point
└── build/                # Compiled files (auto-generated, do not edit)
    ├── index.js          # Bundled JavaScript
    └── index.asset.php   # WordPress dependencies manifest
```

## Setup

### Prerequisites

- Node.js >= 16.0.0
- npm >= 8.0.0

### Installation

```bash
# Install dependencies
npm install
```

## Development Workflow

### Development Mode (with Hot Reload)

```bash
npm run start
```

This command:
- Compiles React source files
- Watches for changes and recompiles automatically
- Generates source maps for debugging
- Outputs to `assets/js/build/`

**Keep this running while developing.** Changes to React components will trigger automatic recompilation.

### Production Build

```bash
npm run build
```

This command:
- Compiles and minifies source files
- Optimizes bundle size
- Removes development-only code
- Generates optimized build for deployment

**Run this before committing** or deploying to production.

### Code Quality

```bash
# Check code standards
npm run lint:js

# Auto-fix code style issues
npm run lint:js -- --fix

# Run JavaScript tests
npm run test:unit:js
```

## WordPress Integration

### Enqueuing the Script

The compiled JavaScript must be enqueued in WordPress using `wp_enqueue_script()`:

```php
wp_enqueue_script(
    'seo-generator-admin',
    plugin_dir_url( __FILE__ ) . 'assets/js/build/index.js',
    array( 'wp-element', 'wp-i18n' ), // Dependencies from index.asset.php
    filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/build/index.js' ),
    true
);
```

### Dependencies

WordPress dependencies are **externalized** (not bundled) to reduce file size and avoid conflicts:

- `wp-element` - React wrapper provided by WordPress
- `wp-i18n` - Internationalization functions
- `wp-components` - WordPress UI components
- `wp-api-fetch` - REST API client

These are automatically listed in `index.asset.php` and must be included in the `wp_enqueue_script()` dependencies array.

### Root Element

The React app mounts to an element with ID `seo-generator-root`:

```php
<div id="seo-generator-root"></div>
```

## Tech Stack

- **React**: 18.x (via `@wordpress/element`)
- **Build Tool**: Webpack 5.x (via `@wordpress/scripts`)
- **Linter**: ESLint with `@wordpress/eslint-plugin`
- **Testing**: Jest + React Testing Library

## Troubleshooting

### Build Fails

**Issue**: `npm run build` fails with errors

**Solutions**:
1. Delete `node_modules/` and run `npm install` again
2. Clear npm cache: `npm cache clean --force`
3. Ensure Node.js version >= 16.0.0: `node --version`

### Hot Reload Not Working

**Issue**: Changes to React components don't trigger recompilation

**Solutions**:
1. Stop (`Ctrl+C`) and restart `npm run start`
2. Check that files are being saved in `assets/js/src/`
3. Look for webpack errors in the terminal

### WordPress Dependencies Missing

**Issue**: `wp is not defined` error in browser console

**Solutions**:
1. Verify `index.asset.php` lists the correct dependencies
2. Ensure WordPress dependencies are enqueued before your script
3. Check that dependencies array in `wp_enqueue_script()` matches `index.asset.php`

### ESLint Errors

**Issue**: Linting fails with style errors

**Solution**:
```bash
# Auto-fix most style issues
npm run lint:js -- --fix
```

## File Size Reference

- **Development Build**: ~6-7 KB (includes source maps, not minified)
- **Production Build**: ~500 bytes (minified, optimized)

The small production size confirms WordPress dependencies are properly externalized.

## Additional Resources

- [WordPress Scripts Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
- [WordPress Element (React) Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/)
- [WordPress Components Documentation](https://developer.wordpress.org/block-editor/reference-guides/components/)
