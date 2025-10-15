# SEO Content Generator

WordPress plugin that generates structured, SEO-optimized content pages for jewelry e-commerce using OpenAI's GPT-4 API.

## Requirements

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Advanced Custom Fields (ACF) Free plugin
- Composer
- Node.js 16+ and npm 8+ (for frontend development)

## Installation

### Development Setup

1. Clone the repository into your WordPress plugins directory:
```bash
cd wp-content/plugins/
git clone https://github.com/your-org/content-generator.git
cd content-generator
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies (for future React admin UI):
```bash
npm install
```

4. Install and activate Advanced Custom Fields (ACF):
```bash
wp plugin install advanced-custom-fields --activate
```

5. Activate the plugin:
```bash
wp plugin activate content-generator
```

## Features

- **Custom Post Type:** `seo-page` for AI-generated content
- **Taxonomies:**
  - `seo-topic` - Content categorization
  - `image_tag` - Image library tagging system
- **12 Content Blocks:** Structured content architecture
- **OpenAI Integration:** GPT-4 powered content generation
- **Cost Tracking:** Monitor API usage and costs
- **Image Library:** Tag-based image matching
- **CSV Import:** Bulk page creation from keyword lists

## Development

### Run Tests
```bash
composer test
```

### Check Coding Standards
```bash
composer phpcs
```

### Fix Coding Standards
```bash
composer phpcbf
```

### Build Frontend Assets
```bash
npm run build
```

### Development Mode (with hot reload)
```bash
npm run start
```

## Documentation

See the `docs/` directory for detailed documentation:
- `docs/prd.md` - Product Requirements Document
- `docs/architecture.md` - Technical Architecture
- `docs/stories/` - User stories and development tasks

## License

GPL v2 or later

## Credits

Developed by the Development Team
