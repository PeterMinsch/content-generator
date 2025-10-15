# Block Configuration Format

This document describes the format for defining content blocks in `config/block-definitions.php`.

## Overview

The config file returns an array with two main sections:
- `blocks`: Array of block definitions
- `settings`: Global settings for block system

## Structure

```php
return [
    'blocks' => [
        'block_id' => [
            // Block definition
        ],
        // More blocks...
    ],
    'settings' => [
        'allow_custom_blocks'   => false,
        'enable_block_ordering' => false,
    ],
];
```

## Block Definition Schema

Each block in the `blocks` array has the following structure:

### Required Properties

| Property | Type | Description |
|----------|------|-------------|
| `label` | string | Human-readable block name (translatable) |
| `fields` | array | ACF field definitions (see Field Schema below) |

### Optional Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `description` | string | '' | Block description for documentation |
| `order` | int | 999 | Display order (lower numbers first) |
| `enabled` | bool | true | Whether block is active |
| `acf_wrapper_class` | string | null | CSS class added to first field wrapper |
| `ai_prompt` | string | null | Template for AI content generation |
| `frontend_template` | string | null | Path to frontend template file |

### Example Block Definition

```php
'hero' => [
    'label'             => __( 'Hero Section', 'seo-generator' ),
    'description'       => __( 'Main hero content at the top of the page', 'seo-generator' ),
    'order'             => 1,
    'enabled'           => true,
    'acf_wrapper_class' => 'acf-block-hero',
    'ai_prompt'         => 'Generate a compelling hero section for {page_title}...',
    'frontend_template' => 'blocks/hero.php',
    'fields'            => [
        'hero_title' => [
            'label'     => __( 'Hero Title', 'seo-generator' ),
            'type'      => 'text',
            'required'  => true,
            'maxlength' => 100,
        ],
        'hero_subtitle' => [
            'label'     => __( 'Hero Subtitle', 'seo-generator' ),
            'type'      => 'text',
            'maxlength' => 150,
        ],
    ],
],
```

## Field Schema

Each field in the `fields` array has the following structure:

### Required Field Properties

| Property | Type | Description |
|----------|------|-------------|
| `type` | string | ACF field type (text, textarea, image, repeater, etc.) |

### Optional Field Properties

All standard ACF field properties are supported. Common ones include:

| Property | Type | Description |
|----------|------|-------------|
| `label` | string | Field label (auto-generated from field name if omitted) |
| `required` | bool | Whether field is required |
| `maxlength` | int | Maximum character length |
| `rows` | int | Number of rows for textarea |
| `max` | int | Maximum value/items for repeater |
| `return_format` | string | Return format for image/date fields |
| `preview_size` | string | Preview size for image fields |
| `layout` | string | Layout for repeater (table, row, block) |
| `wrapper` | array | Wrapper settings (class, width, id) |

### Repeater Fields

Repeater fields must include a `sub_fields` property:

```php
'answer_bullets' => [
    'label'      => __( 'Answer Bullets', 'seo-generator' ),
    'type'       => 'repeater',
    'layout'     => 'table',
    'sub_fields' => [
        'bullet_text' => [
            'label'     => __( 'Bullet Text', 'seo-generator' ),
            'type'      => 'text',
            'maxlength' => 150,
        ],
    ],
],
```

## ACF Conversion

The `BlockDefinitionParser` class converts config format to ACF format:

### Auto-Generated Properties

- **Field Key**: Generated as `field_{field_name}`
- **Field Label**: Generated from field name if not provided (e.g., `hero_title` → "Hero Title")

### Wrapper Class Behavior

The `acf_wrapper_class` from block config is applied **only to the first field** of each block. This creates a visual separator between blocks in the WordPress admin.

## Global Settings

The `settings` array supports these options:

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `allow_custom_blocks` | bool | false | Allow users to add custom blocks via filters |
| `enable_block_ordering` | bool | false | Enable drag-and-drop block ordering in admin |

### Using Filters

Settings can be modified using WordPress filters:

```php
add_filter( 'seo_generator_allow_custom_blocks', '__return_true' );
```

## Extending with Filters

### Filter: `seo_generator_block_definitions`

Modify or add block definitions programmatically:

```php
add_filter( 'seo_generator_block_definitions', function( $blocks ) {
    // Add a new block
    $blocks['custom_block'] = [
        'label'  => 'Custom Block',
        'order'  => 100,
        'fields' => [
            'custom_field' => [
                'label' => 'Custom Field',
                'type'  => 'text',
            ],
        ],
    ];

    // Modify existing block
    $blocks['hero']['enabled'] = false;

    return $blocks;
} );
```

## Validation

Future implementation will include validation for:
- Required properties (label, fields)
- Valid ACF field types
- Proper structure for repeater sub_fields
- Unique field names across all blocks

## Migration Guide

### Replacing Entire Block Set

To completely replace blocks when final spec arrives:

1. **Backup current config**:
   ```bash
   cp config/block-definitions.php config/block-definitions.backup.php
   ```

2. **Update config file** with new block definitions

3. **Clear ACF cache** (if using ACF JSON):
   ```bash
   rm -rf acf-json/*
   ```

4. **Re-save** field groups in WordPress admin

### Modifying Individual Blocks

To modify specific blocks without touching code:

1. Edit `config/block-definitions.php`
2. Change field properties, add/remove fields
3. Save file
4. Refresh WordPress admin to see changes

## Best Practices

1. **Always use translation functions** for user-facing strings (`__()`, `_x()`)
2. **Keep block IDs simple** (lowercase, underscores only)
3. **Use semantic field names** (e.g., `hero_title` not `field_1`)
4. **Document AI prompts** with placeholder syntax (e.g., `{page_title}`)
5. **Set reasonable defaults** for optional properties
6. **Test thoroughly** after making changes

## Example: Complete Block

```php
'comparison' => [
    'label'             => __( 'Comparison Table', 'seo-generator' ),
    'description'       => __( 'Side-by-side comparison of two options', 'seo-generator' ),
    'order'             => 6,
    'enabled'           => true,
    'acf_wrapper_class' => 'acf-block-comparison',
    'ai_prompt'         => 'Create a comparison table for {topic} highlighting key differences',
    'frontend_template' => 'blocks/comparison.php',
    'fields'            => [
        'comparison_heading' => [
            'label' => __( 'Comparison Heading', 'seo-generator' ),
            'type'  => 'text',
        ],
        'comparison_left_label' => [
            'label' => __( 'Left Label', 'seo-generator' ),
            'type'  => 'text',
        ],
        'comparison_right_label' => [
            'label' => __( 'Right Label', 'seo-generator' ),
            'type'  => 'text',
        ],
        'comparison_rows' => [
            'label'      => __( 'Comparison Rows', 'seo-generator' ),
            'type'       => 'repeater',
            'layout'     => 'table',
            'sub_fields' => [
                'attribute' => [
                    'label' => __( 'Attribute', 'seo-generator' ),
                    'type'  => 'text',
                ],
                'left_text' => [
                    'label'     => __( 'Left Text', 'seo-generator' ),
                    'type'      => 'text',
                    'maxlength' => 200,
                ],
                'right_text' => [
                    'label'     => __( 'Right Text', 'seo-generator' ),
                    'type'      => 'text',
                    'maxlength' => 200,
                ],
            ],
        ],
    ],
],
```

## Troubleshooting

### Blocks Not Showing in Admin

1. Check that `enabled` is set to `true`
2. Verify config file syntax (no PHP errors)
3. Clear ACF cache
4. Check WordPress debug log for errors

### Fields Not Saving

1. Verify field names are unique across all blocks
2. Check ACF field type is valid
3. Ensure repeater fields have `sub_fields` array
4. Review WordPress debug log

### Changes Not Appearing

1. Hard refresh WordPress admin (Ctrl+F5)
2. Clear browser cache
3. Deactivate/reactivate ACF plugin
4. Check file permissions on config directory

## Related Files

- **Config File**: `config/block-definitions.php`
- **Parser Class**: `includes/ACF/BlockDefinitionParser.php`
- **Field Registration**: `includes/ACF/FieldGroups.php`
- **Tests**: `tests/php/ACF/BlockDefinitionParserTest.php`
