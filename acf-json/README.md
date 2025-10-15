# ACF JSON Directory

This directory is used by Advanced Custom Fields to store and load field group definitions in JSON format.

## Purpose

- **Version Control**: Field groups are saved as JSON files for version control
- **Synchronization**: Enables field groups to be synchronized across different environments
- **Portability**: Makes it easy to deploy field configurations with the plugin

## How It Works

### Automatic JSON Export
When field groups are modified in the WordPress admin (if using ACF UI), ACF automatically exports them to this directory as JSON files.

### Programmatic Registration
This plugin registers field groups programmatically via PHP in `includes/ACF/FieldGroups.php`. The JSON files serve as a backup and for reference.

### Configuration
The plugin configures ACF to use this directory via filters in `includes/Plugin.php`:
- `acf/settings/save_json` - Where to save JSON files
- `acf/settings/load_json` - Where to load JSON files from

## Field Groups

This plugin includes two main field groups:

1. **SEO Page Content Blocks** - Contains all 12 content block fields
2. **SEO Meta Fields** - Contains SEO metadata fields

## Notes

- JSON files in this directory are automatically loaded by ACF
- Programmatically registered field groups (via `acf_add_local_field_group()`) take precedence
- This directory should be committed to version control
