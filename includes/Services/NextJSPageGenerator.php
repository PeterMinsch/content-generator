<?php
/**
 * Next.js Page Generator
 *
 * Generates page.tsx files at NEW routes based on user-specified slugs.
 * Uses a shared block catalog — any block can be used on any page tab.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

class NextJSPageGenerator {

	/**
	 * @var array
	 */
	private $config;

	public function __construct() {
		$this->config = require SEO_GENERATOR_PLUGIN_DIR . 'config/nextjs-block-definitions.php';
	}

	// ─── Config Accessors ─────────────────────────────────────────

	/**
	 * Get ALL block groups (the shared catalog).
	 */
	public function getBlockGroups(): array {
		return $this->config['groups'] ?? [];
	}

	/**
	 * Get a flat map of ALL block definitions across all groups.
	 */
	public function getAllBlocks(): array {
		$all = [];
		foreach ( $this->config['groups'] as $group ) {
			foreach ( $group['blocks'] as $id => $block ) {
				$all[ $id ] = $block;
			}
		}
		return $all;
	}

	/**
	 * Get page tab definitions.
	 */
	public function getPages(): array {
		return $this->config['pages'] ?? [];
	}

	public function getPageConfig( string $page_slug ): ?array {
		return $this->config['pages'][ $page_slug ] ?? null;
	}

	public function getDefaultOrder( string $page_slug ): array {
		$page = $this->getPageConfig( $page_slug );
		return $page ? ( $page['default_order'] ?? [] ) : [];
	}

	// ─── Slug Management ──────────────────────────────────────────

	public function getSavedSlug( string $page_slug ): string {
		return get_option( "seo_nextjs_output_slug_{$page_slug}", '' );
	}

	public function saveSlug( string $page_slug, string $output_slug ): void {
		update_option( "seo_nextjs_output_slug_{$page_slug}", sanitize_title( $output_slug ) );
	}

	public function getReservedSlugs(): array {
		return [
			'', 'about', 'contacts', 'custom-design', 'diamonds',
			'engagement-rings', 'preview', 'api', 'admin',
		];
	}

	public function isSlugSafe( string $slug ): bool {
		return ! in_array( $slug, $this->getReservedSlugs(), true );
	}

	// ─── Path Helpers ─────────────────────────────────────────────

	public function getProjectPath(): string {
		return get_option( 'seo_nextjs_project_path', '' );
	}

	public function getOutputFilePath( string $output_slug ): string {
		$project_path = rtrim( $this->getProjectPath(), '/\\' );
		return $project_path . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'app'
			. DIRECTORY_SEPARATOR . $output_slug . DIRECTORY_SEPARATOR . 'page.tsx';
	}

	// ─── Code Generation ──────────────────────────────────────────

	/**
	 * Generate page.tsx content.
	 *
	 * Block order can include ANY block from the shared catalog.
	 */
	public function generatePageContent( string $page_slug, array $block_order, string $output_slug ): string {
		$page       = $this->getPageConfig( $page_slug );
		$all_blocks = $this->getAllBlocks();

		if ( ! $page ) {
			return '';
		}

		$wrapper_open  = $page['wrapper_open'] ?? '';
		$wrapper_close = $page['wrapper_close'] ?? '';
		$metadata      = $page['default_metadata'] ?? null;

		$imports    = [];
		$components = [];

		if ( $metadata ) {
			$imports[] = "import type { Metadata } from 'next';";
		}

		foreach ( $block_order as $block_id ) {
			if ( ! isset( $all_blocks[ $block_id ] ) ) {
				continue;
			}

			$block       = $all_blocks[ $block_id ];
			$import_type = $block['import_type'] ?? 'named';

			if ( 'default' === $import_type ) {
				$import_line = "import {$block['export_name']} from '{$block['import_path']}';";
			} else {
				$import_line = "import { {$block['export_name']} } from '{$block['import_path']}';";
			}
			if ( ! in_array( $import_line, $imports, true ) ) {
				$imports[] = $import_line;
			}

			// Data dependency imports (e.g. category data for widgets).
			if ( ! empty( $block['data_imports'] ) ) {
				foreach ( $block['data_imports'] as $data_import ) {
					$data_line = "import { {$data_import['name']} } from '{$data_import['path']}';";
					if ( ! in_array( $data_line, $imports, true ) ) {
						$imports[] = $data_line;
					}
				}
			}

			$props        = $block['props'] ?? '';
			$components[] = "      <{$block['export_name']}{$props} />";
		}

		$imports_str    = implode( "\n", $imports );
		$components_str = implode( "\n", $components );

		$func_name = str_replace( ' ', '', ucwords( str_replace( '-', ' ', $output_slug ) ) );

		if ( empty( $wrapper_open ) ) {
			$wrapper_open  = '<>';
			$wrapper_close = '</>';
		}

		$content = '';
		if ( ! empty( $page['use_client'] ) ) {
			$content .= "'use client';\n\n";
		}
		$content .= $imports_str . "\n";

		if ( $metadata ) {
			$meta_title = addslashes( $metadata['title'] ?? '' );
			$meta_desc  = addslashes( $metadata['description'] ?? '' );
			$content .= "\nexport const metadata: Metadata = {\n";
			$content .= "  title: '{$meta_title}',\n";
			$content .= "  description: '{$meta_desc}',\n";
			$content .= "};\n";
		}

		$content .= "\nexport default function {$func_name}() {\n";
		$content .= "  return (\n";
		$content .= "    {$wrapper_open}\n";
		$content .= $components_str . "\n";
		$content .= "    {$wrapper_close}\n";
		$content .= "  );\n";
		$content .= "}\n";

		return $content;
	}

	// ─── Publish ──────────────────────────────────────────────────

	public function publish( string $page_slug, array $block_order, string $output_slug ): array {
		$project_path = $this->getProjectPath();

		if ( empty( $project_path ) ) {
			return [
				'success' => false,
				'message' => 'Next.js project path is not configured. Set it in Settings.',
			];
		}

		if ( empty( $output_slug ) ) {
			return [
				'success' => false,
				'message' => 'Output slug is required.',
			];
		}

		if ( ! $this->isSlugSafe( $output_slug ) ) {
			return [
				'success' => false,
				'message' => "The slug \"{$output_slug}\" is reserved. Choose a different slug.",
			];
		}

		if ( empty( $block_order ) ) {
			return [
				'success' => false,
				'message' => 'Cannot publish an empty page. Add at least one block.',
			];
		}

		$file_path = $this->getOutputFilePath( $output_slug );
		$dir       = dirname( $file_path );

		if ( ! is_dir( $dir ) ) {
			if ( ! mkdir( $dir, 0755, true ) ) {
				return [
					'success' => false,
					'message' => "Failed to create directory: {$dir}",
				];
			}
		}

		if ( file_exists( $file_path ) ) {
			copy( $file_path, $file_path . '.backup-' . date( 'Y-m-d-His' ) );
		}

		$content = $this->generatePageContent( $page_slug, $block_order, $output_slug );
		$result  = file_put_contents( $file_path, $content );

		if ( false === $result ) {
			return [
				'success' => false,
				'message' => "Failed to write file: {$file_path}",
			];
		}

		$this->saveSlug( $page_slug, $output_slug );
		update_option( "seo_nextjs_block_order_{$page_slug}", $block_order );

		// Attempt automatic build + PM2 restart.
		$build_status = 'manual';
		if ( function_exists( 'exec' ) ) {
			$escaped_path = escapeshellarg( rtrim( $project_path, '/\\' ) );
			exec(
				"sudo bash -lc 'cd {$escaped_path} && echo \"[Build started: \$(date)]\" > /tmp/nextjs-build.log && NODE_OPTIONS=\"--max-old-space-size=1536\" pnpm build >> /tmp/nextjs-build.log 2>&1 && cp -r .next/static .next/standalone/frontend/.next/static && cp -r public .next/standalone/frontend/public && pm2 restart bravo-nextjs >> /tmp/nextjs-build.log 2>&1 && echo \"[Build complete: \$(date)]\" >> /tmp/nextjs-build.log' &"
			);
			$build_status = 'started';
		}

		return [
			'success'      => true,
			'message'      => "Published to /{$output_slug} successfully!",
			'path'         => $file_path,
			'build_status' => $build_status,
		];
	}
}
