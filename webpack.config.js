/**
 * Webpack Configuration
 *
 * Extends @wordpress/scripts default webpack config for custom directory structure.
 *
 * @package
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve(process.cwd(), 'assets/js/src', 'index.js'),
		'image-upload': path.resolve(process.cwd(), 'assets/js/src', 'image-upload.js'),
		'image-tags': path.resolve(process.cwd(), 'assets/js/src', 'image-tags.js'),
		'column-mapping': path.resolve(process.cwd(), 'assets/js/src', 'column-mapping.js'),
		'queue-status': path.resolve(process.cwd(), 'assets/js/src', 'queue-status.js'),
		'block-ordering': path.resolve(process.cwd(), 'assets/js/src', 'block-ordering.js'),
		'block-preview': path.resolve(process.cwd(), 'assets/js/src', 'block-preview.js'),
	},
	output: {
		path: path.resolve(process.cwd(), 'assets/js/build'),
		filename: '[name].js',
	},
};
