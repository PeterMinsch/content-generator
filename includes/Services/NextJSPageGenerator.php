<?php
/**
 * Next.js Page Generator
 *
 * Generates page.tsx files for the Bravo Jewellers Next.js site
 * based on a given block order from the page builder.
 * Supports multiple pages (homepage, about, etc.).
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

class NextJSPageGenerator {

	/**
	 * Full config loaded from the definitions file.
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->config = require SEO_GENERATOR_PLUGIN_DIR . 'config/nextjs-block-definitions.php';
	}

	/**
	 * Get the Next.js project path from settings.
	 *
	 * @return string
	 */
	public function getProjectPath(): string {
		return get_option( 'seo_nextjs_project_path', '' );
	}

	/**
	 * Get all page definitions.
	 *
	 * @return array
	 */
	public function getPages(): array {
		return $this->config['pages'] ?? [];
	}

	/**
	 * Get a single page definition by slug.
	 *
	 * @param string $page_slug The page slug (e.g. 'homepage', 'about').
	 * @return array|null
	 */
	public function getPageConfig( string $page_slug ): ?array {
		return $this->config['pages'][ $page_slug ] ?? null;
	}

	/**
	 * Get block definitions for a specific page.
	 *
	 * @param string $page_slug The page slug.
	 * @return array
	 */
	public function getBlockDefinitions( string $page_slug = 'homepage' ): array {
		$page = $this->getPageConfig( $page_slug );
		return $page['blocks'] ?? [];
	}

	/**
	 * Get the default block order for a page.
	 *
	 * @param string $page_slug The page slug.
	 * @return array
	 */
	public function getDefaultOrder( string $page_slug = 'homepage' ): array {
		$page = $this->getPageConfig( $page_slug );
		return $page['default_order'] ?? [];
	}

	/**
	 * Get the target page.tsx file path for a page.
	 *
	 * @param string $page_slug The page slug.
	 * @return string
	 */
	public function getPageFilePath( string $page_slug = 'homepage' ): string {
		$project_path = rtrim( $this->getProjectPath(), '/\\' );
		$page         = $this->getPageConfig( $page_slug );
		$relative     = $page['file_path'] ?? 'src/app/page.tsx';

		return $project_path . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative );
	}

	/**
	 * Generate the page.tsx content from a block order array.
	 *
	 * @param array  $block_order Array of block IDs in desired order.
	 * @param string $page_slug   The page slug.
	 * @return string The full page.tsx file content.
	 */
	public function generatePageContent( array $block_order, string $page_slug = 'homepage' ): string {
		$page   = $this->getPageConfig( $page_slug );
		$blocks = $page['blocks'] ?? [];

		$imports    = [];
		$components = [];

		foreach ( $block_order as $block_id ) {
			if ( ! isset( $blocks[ $block_id ] ) ) {
				continue;
			}

			$block = $blocks[ $block_id ];

			// Build import line — deduplicate by import_path.
			$import_line = "import { {$block['export_name']} } from '{$block['import_path']}';";
			if ( ! in_array( $import_line, $imports, true ) ) {
				$imports[] = $import_line;
			}

			// Build component JSX tag.
			$props        = $block['props'] ?? '';
			$components[] = "      <{$block['export_name']}{$props} />";
		}

		$imports_str    = implode( "\n", $imports );
		$components_str = implode( "\n", $components );

		// Determine wrapper.
		$wrapper_open  = $page['wrapper_open'] ?? '';
		$wrapper_close = $page['wrapper_close'] ?? '';

		// Determine function name from page slug.
		$func_name = $page_slug === 'homepage' ? 'Home' : ucfirst( $page_slug );

		// Build metadata export if present.
		$metadata_str = '';
		if ( ! empty( $page['metadata'] ) ) {
			$meta = $page['metadata'];
			$meta_title = addslashes( $meta['title'] ?? '' );
			$meta_desc  = addslashes( $meta['description'] ?? '' );
			$metadata_str = <<<TSX

import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: '{$meta_title}',
  description: '{$meta_desc}',
};

TSX;
		}

		if ( ! empty( $wrapper_open ) ) {
			$content = <<<TSX
{$imports_str}
{$metadata_str}
export default function {$func_name}() {
  return (
    {$wrapper_open}
{$components_str}
    {$wrapper_close}
  );
}
TSX;
		} else {
			$content = <<<TSX
{$imports_str}
{$metadata_str}
export default function {$func_name}() {
  return (
    <>
{$components_str}
    </>
  );
}
TSX;
		}

		return $content;
	}

	/**
	 * Write the generated page.tsx to disk.
	 *
	 * @param array  $block_order Array of block IDs in desired order.
	 * @param string $page_slug   The page slug.
	 * @return array [ 'success' => bool, 'message' => string, 'path' => string ]
	 */
	public function publish( array $block_order, string $page_slug = 'homepage' ): array {
		$project_path = $this->getProjectPath();

		if ( empty( $project_path ) ) {
			return [
				'success' => false,
				'message' => 'Next.js project path is not configured. Go to Settings to set it.',
				'path'    => '',
			];
		}

		$page_config = $this->getPageConfig( $page_slug );
		if ( ! $page_config ) {
			return [
				'success' => false,
				'message' => "Unknown page: {$page_slug}",
				'path'    => '',
			];
		}

		$file_path = $this->getPageFilePath( $page_slug );

		$dir = dirname( $file_path );
		if ( ! is_dir( $dir ) ) {
			return [
				'success' => false,
				'message' => "Target directory does not exist: {$dir}",
				'path'    => $file_path,
			];
		}

		if ( empty( $block_order ) ) {
			return [
				'success' => false,
				'message' => 'Cannot publish an empty page. Add at least one block.',
				'path'    => $file_path,
			];
		}

		$content = $this->generatePageContent( $block_order, $page_slug );

		// Backup existing file.
		if ( file_exists( $file_path ) ) {
			$backup_path = $file_path . '.backup-' . date( 'Y-m-d-His' );
			copy( $file_path, $backup_path );
		}

		$result = file_put_contents( $file_path, $content );

		if ( false === $result ) {
			return [
				'success' => false,
				'message' => "Failed to write file. Check permissions for: {$file_path}",
				'path'    => $file_path,
			];
		}

		$label = $page_config['label'] ?? $page_slug;

		return [
			'success' => true,
			'message' => "{$label} published successfully! Next.js will hot-reload automatically.",
			'path'    => $file_path,
		];
	}

	/**
	 * Preview what the generated content would look like (without writing).
	 *
	 * @param array  $block_order Array of block IDs.
	 * @param string $page_slug   The page slug.
	 * @return string The generated TSX content.
	 */
	public function preview( array $block_order, string $page_slug = 'homepage' ): string {
		return $this->generatePageContent( $block_order, $page_slug );
	}
}
