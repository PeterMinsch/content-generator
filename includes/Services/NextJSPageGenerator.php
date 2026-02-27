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

	// ─── Slot Schema Accessors ───────────────────────────────────

	/**
	 * Get the content_slots definition for a single block.
	 *
	 * @param string $block_id Block identifier.
	 * @return array Slot schema (empty array if none defined).
	 */
	public function getSlotSchema( string $block_id ): array {
		$all = $this->getAllBlocks();
		return $all[ $block_id ]['content_slots'] ?? [];
	}

	/**
	 * Get slot schemas for ALL blocks that have non-empty content_slots.
	 *
	 * @return array { block_id => { slot_name => { type, max_length, ai_hint } } }
	 */
	public function getAllSlotSchemas(): array {
		$schemas = [];
		foreach ( $this->getAllBlocks() as $id => $block ) {
			$slots = $block['content_slots'] ?? [];
			if ( ! empty( $slots ) ) {
				$schemas[ $id ] = $slots;
			}
		}
		return $schemas;
	}

	/**
	 * Convert a DB template row to page config format (for code generation).
	 */
	public function getPageConfigFromTemplate( array $template ): array {
		$wrapper = $template['wrapper_config'] ?? [];
		return [
			'label'            => $template['name'],
			'original_path'    => '',
			'preview_route'    => $wrapper['preview_route'] ?? '/preview',
			'use_client'       => ! empty( $wrapper['use_client'] ),
			'wrapper_open'     => $wrapper['wrapper_open'] ?? '',
			'wrapper_close'    => $wrapper['wrapper_close'] ?? '',
			'default_order'    => $template['block_order'] ?? [],
			'default_metadata' => $template['default_metadata'] ?? null,
		];
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

	public function publish( string $page_slug, array $block_order, string $output_slug, array $slot_content = [], ?array $metadata = null ): array {
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

		// If dynamic routing is set up, just write JSON — no build needed.
		if ( get_option( 'seo_nextjs_dynamic_setup_done', false ) ) {
			return $this->publishDynamic( $page_slug, $block_order, $output_slug, $slot_content, $metadata );
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

	// ─── Dynamic Route Methods ───────────────────────────────────

	/**
	 * Path to the published-pages.json file in the Next.js project.
	 */
	public function getPublishedPagesJsonPath(): string {
		$project_path = rtrim( $this->getProjectPath(), '/\\' );
		return $project_path . '/published-pages.json';
	}

	/**
	 * Convert a JSX props string into a JS object literal.
	 *
	 * " page='default'"              → { page: 'default' }
	 * " categories={ringsCategories}" → { categories: ringsCategories }
	 * ""                              → {}
	 */
	public function parsePropsToObject( string $props_string ): string {
		$props_string = trim( $props_string );
		if ( empty( $props_string ) ) {
			return '{}';
		}

		$pairs = [];
		if ( preg_match_all( "/(\w+)=(?:'([^']*)'|\{([^}]*)\})/", $props_string, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$name = $m[1];
				if ( isset( $m[3] ) && '' !== $m[3] ) {
					$pairs[] = "{$name}: {$m[3]}";
				} else {
					$pairs[] = "{$name}: '{$m[2]}'";
				}
			}
		}

		return empty( $pairs ) ? '{}' : '{ ' . implode( ', ', $pairs ) . ' }';
	}

	/**
	 * Generate src/lib/block-registry.tsx content.
	 *
	 * Imports all widget components and data dependencies, exports:
	 * - blockRegistry:  block ID → { Component, props }
	 * - pageWrappers:   template → className | null
	 * - pageMetadata:   template → { title, description } | null
	 */
	public function generateBlockRegistry(): string {
		$all_blocks = $this->getAllBlocks();

		// ── Pass 1: determine imports and detect name collisions ──
		$name_first_path  = [];
		$seen_imports     = [];
		$block_local_name = [];
		$path_imports     = [];

		foreach ( $all_blocks as $block_id => $block ) {
			$name = $block['export_name'];
			$path = $block['import_path'];
			$type = $block['import_type'] ?? 'named';
			$key  = "{$name}|{$path}";

			if ( isset( $seen_imports[ $key ] ) ) {
				$block_local_name[ $block_id ] = $seen_imports[ $key ];
				continue;
			}

			if ( ! isset( $name_first_path[ $name ] ) ) {
				$name_first_path[ $name ] = $path;
				$local_name = $name;
			} elseif ( $name_first_path[ $name ] === $path ) {
				$local_name = $name;
			} else {
				$local_name = $name . '_' . $block_id;
			}

			$seen_imports[ $key ]         = $local_name;
			$block_local_name[ $block_id ] = $local_name;

			if ( ! isset( $path_imports[ $path ] ) ) {
				$path_imports[ $path ] = [];
			}
			$path_imports[ $path ][] = [
				'name'       => $name,
				'local_name' => $local_name,
				'type'       => $type,
			];
		}

		// ── Build component import lines ─────────────────────────
		$import_lines = [];
		foreach ( $path_imports as $path => $entries ) {
			$defaults = [];
			$named    = [];

			foreach ( $entries as $entry ) {
				if ( 'default' === $entry['type'] ) {
					$defaults[] = $entry['local_name'];
				} else {
					$named[] = ( $entry['name'] !== $entry['local_name'] )
						? "{$entry['name']} as {$entry['local_name']}"
						: $entry['name'];
				}
			}

			if ( ! empty( $defaults ) && ! empty( $named ) ) {
				$import_lines[] = 'import ' . $defaults[0] . ', { ' . implode( ', ', $named ) . " } from '{$path}';";
			} elseif ( ! empty( $defaults ) ) {
				$import_lines[] = "import {$defaults[0]} from '{$path}';";
			} elseif ( ! empty( $named ) ) {
				$import_lines[] = 'import { ' . implode( ', ', array_unique( $named ) ) . " } from '{$path}';";
			}
		}

		// ── Data-dependency imports ──────────────────────────────
		$data_map = [];
		foreach ( $all_blocks as $block ) {
			if ( empty( $block['data_imports'] ) ) {
				continue;
			}
			foreach ( $block['data_imports'] as $di ) {
				if ( ! isset( $data_map[ $di['path'] ] ) ) {
					$data_map[ $di['path'] ] = [];
				}
				if ( ! in_array( $di['name'], $data_map[ $di['path'] ], true ) ) {
					$data_map[ $di['path'] ][] = $di['name'];
				}
			}
		}
		foreach ( $data_map as $path => $names ) {
			$import_lines[] = 'import { ' . implode( ', ', $names ) . " } from '{$path}';";
		}

		// ── Registry entries ─────────────────────────────────────
		$registry = [];
		foreach ( $all_blocks as $block_id => $block ) {
			$comp  = $block_local_name[ $block_id ];
			$props = $this->parsePropsToObject( $block['props'] ?? '' );
			$registry[] = "  {$block_id}: { Component: {$comp}, props: {$props} }";
		}

		// ── Page wrappers ────────────────────────────────────────
		$wrappers = [];
		foreach ( $this->getPages() as $slug => $page ) {
			$open = $page['wrapper_open'] ?? '';
			if ( ! empty( $open ) && preg_match( "/className='([^']*)'/", $open, $m ) ) {
				$wrappers[] = "  {$slug}: '{$m[1]}'";
			} else {
				$wrappers[] = "  {$slug}: null";
			}
		}

		// ── Page metadata ────────────────────────────────────────
		$meta_entries = [];
		foreach ( $this->getPages() as $slug => $page ) {
			$meta = $page['default_metadata'] ?? null;
			if ( $meta ) {
				$title = addslashes( $meta['title'] ?? '' );
				$desc  = addslashes( $meta['description'] ?? '' );
				$meta_entries[] = "  {$slug}: { title: '{$title}', description: '{$desc}' }";
			} else {
				$meta_entries[] = "  {$slug}: null";
			}
		}

		// ── Assemble file ────────────────────────────────────────
		$out  = "'use client';\n\n";
		$out .= implode( "\n", $import_lines ) . "\n";
		$out .= "\nexport const blockRegistry: Record<string, { Component: any; props: Record<string, any> }> = {\n";
		$out .= implode( ",\n", $registry ) . ",\n";
		$out .= "};\n";
		$out .= "\nexport const pageWrappers: Record<string, string | null> = {\n";
		$out .= implode( ",\n", $wrappers ) . ",\n";
		$out .= "};\n";
		$out .= "\nexport const pageMetadata: Record<string, { title: string; description: string } | null> = {\n";
		$out .= implode( ",\n", $meta_entries ) . ",\n";
		$out .= "};\n";

		return $out;
	}

	/**
	 * Generate block registry with templates from DB instead of hardcoded pages.
	 *
	 * @param array $db_templates Array of template rows from DB.
	 */
	public function generateBlockRegistryWithTemplates( array $db_templates ): string {
		$all_blocks = $this->getAllBlocks();

		// Import lines + registry entries are the same as generateBlockRegistry().
		// Only wrappers + metadata differ (come from DB templates).
		$base = $this->generateBlockRegistry();

		// Replace the pageWrappers and pageMetadata sections with DB template data.
		$wrappers = [];
		$meta_entries = [];

		foreach ( $db_templates as $template ) {
			$slug    = $template['slug'];
			$wrapper = $template['wrapper_config'] ?? [];
			$open    = $wrapper['wrapper_open'] ?? '';

			if ( ! empty( $open ) && preg_match( "/className='([^']*)'/", $open, $m ) ) {
				$wrappers[] = "  '{$slug}': '{$m[1]}'";
			} else {
				$wrappers[] = "  '{$slug}': null";
			}

			$meta = $template['default_metadata'] ?? null;
			if ( $meta ) {
				$title = addslashes( $meta['title'] ?? '' );
				$desc  = addslashes( $meta['description'] ?? '' );
				$meta_entries[] = "  '{$slug}': { title: '{$title}', description: '{$desc}' }";
			} else {
				$meta_entries[] = "  '{$slug}': null";
			}
		}

		// Replace the wrappers section in the base output.
		$new_wrappers = "export const pageWrappers: Record<string, string | null> = {\n"
			. implode( ",\n", $wrappers ) . ",\n};\n";
		$new_metadata = "export const pageMetadata: Record<string, { title: string; description: string } | null> = {\n"
			. implode( ",\n", $meta_entries ) . ",\n};\n";

		// Replace the sections in the generated output.
		$base = preg_replace(
			'/export const pageWrappers.*?};\n/s',
			$new_wrappers,
			$base
		);
		$base = preg_replace(
			'/export const pageMetadata.*?};\n/s',
			$new_metadata,
			$base
		);

		return $base;
	}

	/**
	 * Generate src/app/[slug]/page.tsx — the server component.
	 */
	public function generateCatchAllServerPage(): string {
		$project_path = rtrim( $this->getProjectPath(), '/\\' );
		$json_path    = str_replace( '\\', '/', $project_path . '/published-pages.json' );

		$out  = "import { notFound } from 'next/navigation';\n";
		$out .= "import fs from 'fs';\n";
		$out .= "import DynamicPage from './DynamicPage';\n";
		$out .= "import { pageMetadata } from '@/lib/block-registry';\n";
		$out .= "import type { Metadata } from 'next';\n\n";
		$out .= "export const dynamic = 'force-dynamic';\n\n";
		$out .= "const PAGES_JSON_PATH = process.env.PUBLISHED_PAGES_PATH || '{$json_path}';\n\n";
		$out .= "interface PageConfig {\n";
		$out .= "  pageTemplate: string;\n";
		$out .= "  blocks: string[];\n";
		$out .= "  metadata: { title: string; description: string } | null;\n";
		$out .= "  slotContent?: Record<string, Record<string, string>> | null;\n";
		$out .= "}\n\n";
		$out .= "function getPublishedPages(): Record<string, PageConfig> {\n";
		$out .= "  try {\n";
		$out .= "    const raw = fs.readFileSync(PAGES_JSON_PATH, 'utf-8');\n";
		$out .= "    return JSON.parse(raw);\n";
		$out .= "  } catch {\n";
		$out .= "    return {};\n";
		$out .= "  }\n";
		$out .= "}\n\n";
		$out .= "export async function generateMetadata({\n";
		$out .= "  params,\n";
		$out .= "}: {\n";
		$out .= "  params: Promise<{ slug: string }>;\n";
		$out .= "}): Promise<Metadata> {\n";
		$out .= "  const { slug } = await params;\n";
		$out .= "  const pages = getPublishedPages();\n";
		$out .= "  const page = pages[slug];\n";
		$out .= "  if (!page) return {};\n";
		$out .= "  if (page.metadata) return page.metadata;\n\n";
		$out .= "  const templateMeta = pageMetadata[page.pageTemplate];\n";
		$out .= "  return templateMeta || {};\n";
		$out .= "}\n\n";
		$out .= "export default async function SlugPage({\n";
		$out .= "  params,\n";
		$out .= "}: {\n";
		$out .= "  params: Promise<{ slug: string }>;\n";
		$out .= "}) {\n";
		$out .= "  const { slug } = await params;\n";
		$out .= "  const pages = getPublishedPages();\n";
		$out .= "  const page = pages[slug];\n";
		$out .= "  if (!page) notFound();\n\n";
		$out .= "  return (\n";
		$out .= "    <DynamicPage\n";
		$out .= "      blocks={page.blocks}\n";
		$out .= "      pageTemplate={page.pageTemplate}\n";
		$out .= "      slotContent={page.slotContent || {}}\n";
		$out .= "    />\n";
		$out .= "  );\n";
		$out .= "}\n";

		return $out;
	}

	/**
	 * Generate src/app/[slug]/DynamicPage.tsx — the client component.
	 */
	public function generateDynamicPageClientComponent(): string {
		$out  = "'use client';\n\n";
		$out .= "import { blockRegistry, pageWrappers } from '@/lib/block-registry';\n\n";
		$out .= "interface DynamicPageProps {\n";
		$out .= "  blocks: string[];\n";
		$out .= "  pageTemplate: string;\n";
		$out .= "  slotContent: Record<string, Record<string, string>>;\n";
		$out .= "}\n\n";
		$out .= "export default function DynamicPage({ blocks, pageTemplate, slotContent }: DynamicPageProps) {\n";
		$out .= "  const wrapperClass = pageWrappers[pageTemplate];\n\n";
		$out .= "  const content = blocks.map((blockId, i) => {\n";
		$out .= "    const entry = blockRegistry[blockId];\n";
		$out .= "    if (!entry) return null;\n";
		$out .= "    const { Component, props } = entry;\n";
		$out .= "    const slots = slotContent[blockId] || {};\n";
		$out .= "    return <Component key={`\${blockId}-\${i}`} {...props} {...slots} />;\n";
		$out .= "  });\n\n";
		$out .= "  if (wrapperClass) {\n";
		$out .= "    return <div className={wrapperClass}>{content}</div>;\n";
		$out .= "  }\n\n";
		$out .= "  return <>{content}</>;\n";
		$out .= "}\n";

		return $out;
	}

	/**
	 * Write all dynamic route files and mark setup as done.
	 */
	public function setupDynamicRoute(): array {
		$project_path = $this->getProjectPath();

		if ( empty( $project_path ) ) {
			return [
				'success' => false,
				'message' => 'Next.js project path is not configured.',
			];
		}

		$project_path = rtrim( $project_path, '/\\' );
		$errors       = [];

		// 1. Block registry.
		$registry_dir = $project_path . '/src/lib';
		if ( ! is_dir( $registry_dir ) ) {
			mkdir( $registry_dir, 0755, true );
		}
		$registry_path = $registry_dir . '/block-registry.tsx';
		if ( false === file_put_contents( $registry_path, $this->generateBlockRegistry() ) ) {
			$errors[] = "Failed to write {$registry_path}";
		}

		// 2. Server component.
		$slug_dir = $project_path . '/src/app/[slug]';
		if ( ! is_dir( $slug_dir ) ) {
			mkdir( $slug_dir, 0755, true );
		}
		$server_path = $slug_dir . '/page.tsx';
		if ( false === file_put_contents( $server_path, $this->generateCatchAllServerPage() ) ) {
			$errors[] = "Failed to write {$server_path}";
		}

		// 3. Client component.
		$client_path = $slug_dir . '/DynamicPage.tsx';
		if ( false === file_put_contents( $client_path, $this->generateDynamicPageClientComponent() ) ) {
			$errors[] = "Failed to write {$client_path}";
		}

		// 4. Empty JSON (only if not already present).
		$json_path = $this->getPublishedPagesJsonPath();
		if ( ! file_exists( $json_path ) ) {
			if ( false === file_put_contents( $json_path, '{}' ) ) {
				$errors[] = "Failed to write {$json_path}";
			}
		}

		if ( ! empty( $errors ) ) {
			return [
				'success' => false,
				'message' => 'Setup errors: ' . implode( '; ', $errors ),
			];
		}

		update_option( 'seo_nextjs_dynamic_setup_done', true );

		return [
			'success' => true,
			'message' => 'Dynamic route files generated. Run pnpm build on the server, then publish pages instantly.',
			'files'   => [ $registry_path, $server_path, $client_path, $json_path ],
		];
	}

	/**
	 * Publish a page by writing to published-pages.json (no build needed).
	 *
	 * @param string     $page_slug    Page template slug.
	 * @param array      $block_order  Ordered block IDs.
	 * @param string     $output_slug  URL slug for the page.
	 * @param array      $slot_content Per-block content overrides: { block_id: { slot: value } }.
	 * @param array|null $metadata     SEO metadata: { title, description }.
	 */
	private function publishDynamic( string $page_slug, array $block_order, string $output_slug, array $slot_content = [], ?array $metadata = null ): array {
		$json_path = $this->getPublishedPagesJsonPath();

		// Read existing pages.
		$pages = [];
		if ( file_exists( $json_path ) ) {
			$raw = file_get_contents( $json_path );
			if ( false !== $raw ) {
				$pages = json_decode( $raw, true ) ?: [];
			}
		}

		// Upsert.
		$pages[ $output_slug ] = [
			'pageTemplate' => $page_slug,
			'blocks'       => $block_order,
			'metadata'     => $metadata,
			'slotContent'  => ! empty( $slot_content ) ? $slot_content : null,
		];

		// Atomic write: .tmp → rename.
		$tmp_path = $json_path . '.tmp';
		$json     = json_encode( $pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === file_put_contents( $tmp_path, $json ) ) {
			return [
				'success' => false,
				'message' => "Failed to write temporary file: {$tmp_path}",
			];
		}

		if ( ! rename( $tmp_path, $json_path ) ) {
			@unlink( $tmp_path );
			return [
				'success' => false,
				'message' => "Failed to update JSON: {$json_path}",
			];
		}

		// Clean up old static page.tsx if it exists.
		$old_page = $this->getOutputFilePath( $output_slug );
		if ( file_exists( $old_page ) ) {
			copy( $old_page, $old_page . '.backup-dynamic-' . date( 'Y-m-d-His' ) );
			unlink( $old_page );
		}

		$this->saveSlug( $page_slug, $output_slug );
		update_option( "seo_nextjs_block_order_{$page_slug}", $block_order );

		return [
			'success'      => true,
			'message'      => "Published to /{$output_slug} — live now!",
			'path'         => $json_path,
			'build_status' => 'not_needed',
		];
	}
}
