/**
 * SEO Content Generator - Main Entry Point
 *
 * This is the main entry point for the React admin interface.
 *
 * @package
 */

import { render, createElement } from '@wordpress/element';
import PageEditor from './components/PageEditor';

/**
 * Initialize the React application.
 */
const init = () => {
	const rootElement = document.getElementById('seo-generator-page-editor');

	if (rootElement) {
		render(createElement(PageEditor), rootElement);
	}
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', init);
} else {
	init();
}
