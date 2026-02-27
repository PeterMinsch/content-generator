<?php
/**
 * Admin Menu Manager
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the admin menu structure for the plugin.
 */
class AdminMenu {
	/**
	 * Main menu slug.
	 *
	 * @var string
	 */
	private const MENU_SLUG = 'seo-content-generator';

	/**
	 * Settings page instance.
	 *
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * Image library page instance.
	 *
	 * @var ImageLibraryPage
	 */
	private $image_library_page;

	/**
	 * Import page instance.
	 *
	 * @var ImportPage
	 */
	private $import_page;

	/**
	 * Queue status page instance.
	 *
	 * @var QueueStatusPage
	 */
	private $queue_status_page;

	/**
	 * Geographic title generator page instance.
	 *
	 * @var GeographicTitleGeneratorPage
	 */
	private $geo_titles_page;

	/**
	 * Constructor.
	 *
	 * @param SettingsPage                  $settings_page Settings page instance.
	 * @param ImageLibraryPage              $image_library_page Image library page instance.
	 * @param ImportPage                    $import_page Import page instance.
	 * @param QueueStatusPage               $queue_status_page Queue status page instance.
	 * @param GeographicTitleGeneratorPage  $geo_titles_page Geographic title generator page instance.
	 */
	public function __construct( SettingsPage $settings_page, ImageLibraryPage $image_library_page, ImportPage $import_page, QueueStatusPage $queue_status_page, GeographicTitleGeneratorPage $geo_titles_page ) {
		$this->settings_page       = $settings_page;
		$this->image_library_page  = $image_library_page;
		$this->import_page         = $import_page;
		$this->queue_status_page   = $queue_status_page;
		$this->geo_titles_page     = $geo_titles_page;
	}

	/**
	 * Register the admin menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenuItems' ) );
		add_action( 'admin_init', array( $this, 'handleRedirects' ) );
	}

	/**
	 * Add menu items to WordPress admin.
	 *
	 * @return void
	 */
	public function addMenuItems(): void {
		// Add top-level menu.
		add_menu_page(
			__( 'Content Generator', 'seo-generator' ),
			__( 'Content Generator', 'seo-generator' ),
			'edit_posts',
			self::MENU_SLUG,
			array( $this, 'renderNewPageRedirect' ),
			'dashicons-edit-large',
			30
		);

		// Add "New Page" submenu (default page).
		add_submenu_page(
			self::MENU_SLUG,
			__( 'New SEO Page', 'seo-generator' ),
			__( 'New Page', 'seo-generator' ),
			'edit_posts',
			self::MENU_SLUG,
			array( $this, 'renderNewPageRedirect' )
		);

		// Add "All SEO Pages" submenu.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'All SEO Pages', 'seo-generator' ),
			__( 'All SEO Pages', 'seo-generator' ),
			'edit_posts',
			'edit.php?post_type=seo-page'
		);

		// Add "Image Library Manager" submenu (placeholder).
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Image Library Manager', 'seo-generator' ),
			__( 'Image Library', 'seo-generator' ),
			'edit_posts',
			'seo-image-library',
			array( $this, 'renderImageLibraryPage' )
		);

		// Add "Import Keywords" submenu.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Import Keywords', 'seo-generator' ),
			__( 'Import Keywords', 'seo-generator' ),
			'edit_posts',
			'seo-import-keywords',
			array( $this, 'renderImportPage' )
		);

		// Add "Geographic Title Generator" submenu.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Geographic Title Generator', 'seo-generator' ),
			__( 'Geographic Titles', 'seo-generator' ),
			'edit_posts',
			'seo-geographic-titles',
			array( $this, 'renderGeoTitlesPage' )
		);

		// Add "Generation Queue" submenu.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Generation Queue', 'seo-generator' ),
			__( 'Generation Queue', 'seo-generator' ),
			'edit_posts',
			'seo-generation-queue',
			array( $this, 'renderQueueStatusPage' )
		);

		// Add "Settings" submenu (placeholder).
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'seo-generator' ),
			__( 'Settings', 'seo-generator' ),
			'manage_options',
			'seo-generator-settings',
			array( $this, 'renderSettingsPage' )
		);

		// Add "Template Builder" submenu (replaces Page Builder).
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Template Builder', 'seo-generator' ),
			__( 'Template Builder', 'seo-generator' ),
			'edit_posts',
			'seo-template-builder',
			array( $this, 'renderTemplateBuilderPage' )
		);

		// Keep old Page Builder slug as redirect for bookmarks.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Page Builder', 'seo-generator' ),
			__( 'Page Builder', 'seo-generator' ),
			'edit_posts',
			'seo-page-builder',
			array( $this, 'renderPageBuilderPage' )
		);

		// Add "Bulk Publish" submenu.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Bulk Publish', 'seo-generator' ),
			__( 'Bulk Publish', 'seo-generator' ),
			'edit_posts',
			'seo-bulk-publish',
			array( $this, 'renderBulkPublishPage' )
		);

		// Add "Analytics" submenu (placeholder).
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Analytics', 'seo-generator' ),
			__( 'Analytics', 'seo-generator' ),
			'edit_posts',
			'seo-generator-analytics',
			array( $this, 'renderAnalyticsPage' )
		);
	}

	/**
	 * Handle redirects early before headers are sent.
	 *
	 * @return void
	 */
	public function handleRedirects(): void {
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		// Redirect main menu to dashboard.
		if ( $_GET['page'] === self::MENU_SLUG ) {
			wp_safe_redirect( admin_url( 'admin.php?page=seo-dashboard' ) );
			exit;
		}

		// Redirect old Page Builder bookmarks to Template Builder.
		if ( $_GET['page'] === 'seo-page-builder' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=seo-template-builder' ) );
			exit;
		}
	}

	/**
	 * Redirect to new SEO page creation (fallback).
	 *
	 * @return void
	 */
	public function renderNewPageRedirect(): void {
		// This should never be reached due to handleRedirects(), but kept as fallback.
		echo '<div class="wrap"><p>Redirecting...</p></div>';
	}

	/**
	 * Render the Image Library Manager page.
	 *
	 * @return void
	 */
	public function renderImageLibraryPage(): void {
		$this->image_library_page->render();
	}

	/**
	 * Render the Settings page.
	 *
	 * @return void
	 */
	public function renderSettingsPage(): void {
		$this->settings_page->render();
	}

	/**
	 * Render the Analytics page (placeholder).
	 *
	 * @return void
	 */
	public function renderAnalyticsPage(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-generator' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'Analytics & Reporting - Coming Soon', 'seo-generator' ); ?></p>
				<p><?php esc_html_e( 'This page will display generation statistics, API costs, success rates, and usage trends.', 'seo-generator' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Template Builder page.
	 *
	 * @return void
	 */
	public function renderTemplateBuilderPage(): void {
		$template_repo    = new \SEOGenerator\Repositories\TemplateRepository();
		$template_service = new \SEOGenerator\Services\TemplateService( $template_repo );
		$template_builder = new TemplateBuilderPage( $template_service );
		$template_builder->render();
	}

	/**
	 * Render the Page Builder page.
	 *
	 * @return void
	 */
	public function renderPageBuilderPage(): void {
		// Redirect handled in handleRedirects(), this is a fallback.
		$page_builder = new PageBuilderPage();
		$page_builder->render();
	}

	/**
	 * Render the Import page.
	 *
	 * @return void
	 */
	public function renderImportPage(): void {
		$this->import_page->render();
	}

	/**
	 * Render the Queue Status page.
	 *
	 * @return void
	 */
	public function renderQueueStatusPage(): void {
		$this->queue_status_page->render();
	}

	/**
	 * Render the Geographic Title Generator page.
	 *
	 * @return void
	 */
	public function renderGeoTitlesPage(): void {
		$this->geo_titles_page->render();
	}

	/**
	 * Render the Bulk Publish page.
	 *
	 * @return void
	 */
	public function renderBulkPublishPage(): void {
		$bulk_publish = new BulkPublishPage();
		$bulk_publish->render();
	}
}
