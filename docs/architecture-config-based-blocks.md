# Config-Based Block Architecture

## Overview

This plugin uses a **config-based approach** for managing content block definitions, making it easy to modify block structures without touching code across multiple files.

## Why Config-Based?

**Problem Solved:** Initially, the 12 content blocks were hardcoded in multiple locations (ACF fields, AI prompts, React components, frontend templates). When the block specification needs to change, this would require updates in 4+ locations, risking inconsistencies and requiring 20+ hours of rework.

**Solution:** All block definitions are centralized in a single configuration file (`config/block-definitions.php`). A parser converts this config to the formats needed by different parts of the system.

## Architecture Components

### 1. Config File (`config/block-definitions.php`)

**Single source of truth** for all 12 content blocks.

```php
return [
    'blocks' => [
        'hero' => [
            'label'             => __( 'Hero Section', 'seo-generator' ),
            'description'       => __( 'Main hero content', 'seo-generator' ),
            'order'             => 1,
            'enabled'           => true,
            'acf_wrapper_class' => 'acf-block-hero',
            'ai_prompt'         => 'Generate hero section for {page_title}...',
            'frontend_template' => 'blocks/hero.php',
            'fields'            => [
                'hero_title' => [
                    'label'     => __( 'Hero Title', 'seo-generator' ),
                    'type'      => 'text',
                    'required'  => true,
                    'maxlength' => 100,
                ],
                // More fields...
            ],
        ],
        // 11 more blocks...
    ],
    'settings' => [
        'allow_custom_blocks'   => false,
        'enable_block_ordering' => false,
    ],
];
```

**Contains:**
- Block metadata (label, description, order)
- ACF field definitions
- AI prompt templates
- Frontend template paths
- Global settings

### 2. Parser (`includes/ACF/BlockDefinitionParser.php`)

**Converts config to ACF field format** and provides helper methods.

```php
$parser = new BlockDefinitionParser();

// Get all blocks converted to ACF format
$acf_fields = $parser->convertAllBlocksToACFFields();

// Get AI prompt for specific block
$prompt = $parser->getAIPrompt( 'hero' );

// Get frontend template path
$template = $parser->getFrontendTemplate( 'hero' );

// Get all block IDs
$block_ids = $parser->getBlockIds();
```

**Features:**
- Automatic ACF field conversion
- Handles repeaters and nested sub_fields
- Supports WordPress filters for extensibility
- Validates config on load

### 3. Validator (`includes/ACF/BlockDefinitionValidator.php`)

**Ensures config integrity** before use.

```php
$validator = new BlockDefinitionValidator();

if ( ! $validator->validate( $blocks ) ) {
    $errors = $validator->getErrors();
    // Handle validation errors
}

// Get warnings for missing optional properties
$warnings = $validator->getWarnings( $blocks );
```

**Validates:**
- Required properties (label, fields, type)
- ACF field types
- Field name uniqueness
- Repeater sub_fields structure
- Block ID format

### 4. Field Groups (`includes/ACF/FieldGroups.php`)

**Registers ACF fields** using the parser.

```php
private function registerContentBlocksFieldGroup(): void {
    $parser = new BlockDefinitionParser();

    acf_add_local_field_group([
        'key'    => 'group_seo_page_content_blocks',
        'title'  => 'SEO Page Content Blocks',
        'fields' => $parser->convertAllBlocksToACFFields(), // From config!
        // ...
    ]);
}
```

**Before:** 702 lines of hardcoded field definitions
**After:** 112 lines using config parser (83% reduction)

## How Components Use the Config

### ACF Field Registration
```php
// FieldGroups.php
$parser = new BlockDefinitionParser();
$fields = $parser->convertAllBlocksToACFFields();
acf_add_local_field_group([ 'fields' => $fields ]);
```

### AI Prompt Templates
```php
// PromptTemplateEngine.php
$parser = new BlockDefinitionParser();
$default_prompt = $parser->getAIPrompt( 'hero' );
```

### Frontend Template Rendering
```php
// functions.php
$parser = new BlockDefinitionParser();
$template_path = $parser->getFrontendTemplate( 'hero' );
include $template_path;
```

### React Component Metadata
```php
// REST API endpoint
$parser = new BlockDefinitionParser();
$blocks = $parser->getEnabledBlocks();
return rest_ensure_response( $blocks );
```

## Benefits

### 1. Easy Block Updates
When final block specifications arrive:
1. Update `config/block-definitions.php`
2. Clear caches
3. Done! No code changes needed.

### 2. Single Source of Truth
- ACF fields: Generated from config
- AI prompts: Stored in config
- Frontend templates: Paths in config
- React metadata: Retrieved from config

### 3. Validation & Safety
- Config validated on load
- Errors logged to debug.log
- Warnings for missing optional properties
- Field name uniqueness enforced

### 4. Extensibility
```php
// Modify blocks via WordPress filter
add_filter( 'seo_generator_block_definitions', function( $blocks ) {
    $blocks['hero']['enabled'] = false;
    $blocks['custom_block'] = [...];
    return $blocks;
} );
```

### 5. Migration Ready
```bash
# Backup current config
cp config/block-definitions.php config/block-definitions.backup.php

# Replace with new spec
cp new-block-spec.php config/block-definitions.php

# Refresh WordPress admin
```

## File Structure

```
content-generator/
├── config/
│   └── block-definitions.php          # Single source of truth
├── includes/
│   └── ACF/
│       ├── BlockDefinitionParser.php  # Config → ACF converter
│       ├── BlockDefinitionValidator.php # Config validator
│       └── FieldGroups.php            # ACF field registration
├── docs/
│   ├── block-config-format.md         # Config schema documentation
│   └── architecture-config-based-blocks.md # This file
└── tests/
    └── php/
        └── ACF/
            ├── BlockDefinitionParserTest.php
            └── BlockDefinitionValidatorTest.php
```

## Common Workflows

### Adding a New Block
1. Edit `config/block-definitions.php`
2. Add new block definition with all properties
3. Config will be validated automatically
4. Refresh WordPress admin

### Modifying Existing Block
1. Edit `config/block-definitions.php`
2. Update field properties, add/remove fields
3. Save file
4. Clear ACF cache if needed

### Disabling a Block
```php
// In config/block-definitions.php
'comparison' => [
    'enabled' => false, // Disable block
    // ... rest of config
],
```

### Changing Block Order
```php
// In config/block-definitions.php
'hero' => [
    'order' => 1, // First block
],
'cta' => [
    'order' => 12, // Last block
],
```

## Testing

The config system has comprehensive test coverage:

- **BlockDefinitionParserTest.php** (17 tests)
  - Config loading
  - ACF conversion
  - Repeater handling
  - Helper methods

- **BlockDefinitionValidatorTest.php** (20 tests)
  - Required property validation
  - Field type validation
  - Uniqueness checks
  - Nested sub_fields

- **ACFFieldsTest.php** (Integration tests)
  - Field saving/retrieval
  - Repeater functionality
  - Character limits

## Troubleshooting

### Blocks Not Showing
1. Check `enabled` is `true` in config
2. Verify no PHP syntax errors in config file
3. Check WordPress debug.log for validation errors
4. Clear ACF cache

### Fields Not Saving
1. Verify field names are unique across all blocks
2. Check ACF field types are valid
3. Ensure repeaters have `sub_fields` array
4. Review WordPress debug.log

### Changes Not Appearing
1. Hard refresh browser (Ctrl+F5)
2. Clear object cache if using caching plugin
3. Deactivate/reactivate ACF plugin
4. Verify file permissions on config directory

## Documentation References

- **Config Format:** `docs/block-config-format.md`
- **Migration Guide:** `docs/block-config-format.md#migration-guide`
- **Story 1.2:** Updated with config-based implementation
- **Story 2.2:** Prompt templates from config
- **Story 3.3:** React components use config metadata
- **Story 4.2:** Frontend templates from config

## WordPress Filters

### Modify Block Definitions
```php
add_filter( 'seo_generator_block_definitions', function( $blocks ) {
    // Your modifications
    return $blocks;
}, 10, 1 );
```

### Allow Custom Blocks
```php
add_filter( 'seo_generator_allow_custom_blocks', '__return_true' );
```

## Performance Notes

- Config loaded once per request
- Validation only runs on config load
- Results can be cached by WordPress object cache
- Minimal performance impact vs hardcoded approach

## Future Enhancements

1. **Admin UI:** Interface to edit config without touching files
2. **Import/Export:** Export config as JSON for sharing
3. **Block Templates:** Pre-made block configurations
4. **Version Control:** Track config changes in database
5. **Dynamic Fields:** Runtime field generation based on rules

## Summary

The config-based architecture provides:
- ✅ **Flexibility:** Easy to update when specs change
- ✅ **Maintainability:** Single source of truth
- ✅ **Validation:** Automatic error checking
- ✅ **Extensibility:** WordPress filter support
- ✅ **Performance:** Minimal overhead
- ✅ **Testing:** Comprehensive test coverage

This architecture ensures the plugin can adapt to changing requirements with minimal effort, reducing 20+ hours of rework to minutes.
