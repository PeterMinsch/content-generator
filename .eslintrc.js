/**
 * ESLint Configuration
 *
 * Extends WordPress coding standards for JavaScript and React.
 *
 * @package
 */

module.exports = {
	extends: ['plugin:@wordpress/eslint-plugin/recommended'],
	env: {
		browser: true,
		es2021: true,
	},
	parserOptions: {
		ecmaVersion: 2021,
		sourceType: 'module',
		ecmaFeatures: {
			jsx: true,
		},
	},
	rules: {
		// Custom rules can be added here
		'no-console': 'warn',
		'react/react-in-jsx-scope': 'off', // Not needed with @wordpress/element
	},
	ignorePatterns: [
		'assets/js/build/',
		'assets/css/build/',
		'node_modules/',
		'vendor/',
		'*.min.js',
	],
};
