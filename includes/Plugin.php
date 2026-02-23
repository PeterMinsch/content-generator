<?php
/**
 * Main Plugin Class
 *
 * @package SEOGenerator
 */

namespace SEOGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin Class
 *
 * Singleton pattern implementation for plugin initialization.
 */
class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		$this->container = new Container();
		$this->initializeComponents();
		$this->registerHooks();
	}

	/**
	 * Get plugin instance (singleton).
	 *
	 * @return Plugin
	 */
	public static function getInstance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function initializeComponents(): void {
		// Register post types and taxonomies.
		$this->container->register( 'post_type.seo_page', PostTypes\SEOPage::class );
		$this->container->register( 'taxonomy.seo_topic', Taxonomies\SEOTopic::class );
		$this->container->register( 'taxonomy.image_tag', Taxonomies\ImageTag::class );

		// Register settings page.
		$settings_page = new Admin\SettingsPage();
		$this->container->set( 'admin.settings', $settings_page );

		// Register image library page.
		$image_library_page = new Admin\ImageLibraryPage();
		$this->container->set( 'admin.image_library', $image_library_page );

		// Register import page.
		$import_page = new Admin\ImportPage();
		$this->container->set( 'admin.import', $import_page );

		// Register queue status page.
		$queue_status_page = new Admin\QueueStatusPage();
		$this->container->set( 'admin.queue_status', $queue_status_page );

		// Register geographic title generator page.
		$geo_titles_page = new Admin\GeographicTitleGeneratorPage();
		$this->container->set( 'admin.geo_titles', $geo_titles_page );

		// Register internal linking test page.
		$test_links_page = new Admin\InternalLinkingTestPage();
		$this->container->set( 'admin.test_links', $test_links_page );

		// Register admin menu (depends on settings, image library, import, queue status, and geo titles pages).
		$admin_menu = new Admin\AdminMenu( $settings_page, $image_library_page, $import_page, $queue_status_page, $geo_titles_page );
		$this->container->set( 'admin.menu', $admin_menu );

		// Register page editor.
		$page_editor = new Admin\PageEditor();
		$this->container->set( 'admin.page_editor', $page_editor );

		// Register post list columns.
		$post_list_columns = new Admin\PostListColumns();
		$this->container->set( 'admin.post_list_columns', $post_list_columns );

		// Register post deletion handler.
		$post_deletion_handler = new Hooks\PostDeletionHandler();
		$this->container->set( 'hooks.post_deletion', $post_deletion_handler );

		// Register template loader.
		$template_loader = new Templates\TemplateLoader();
		$this->container->set( 'template.loader', $template_loader );

		// Register services.
		$settings_service    = new Services\SettingsService();
		$openai_service      = new Services\OpenAIService( $settings_service );
		$prompt_engine       = new Services\PromptTemplateEngine();
		$content_parser      = new Services\BlockContentParser();
		$image_matching      = new Services\ImageMatchingService( $openai_service );

		// Register cost tracking.
		$log_repository      = new Repositories\GenerationLogRepository();
		$cost_tracking       = new Services\CostTrackingService( $log_repository );

		$generation_service  = new Services\ContentGenerationService( $openai_service, $prompt_engine, $content_parser, $cost_tracking, $image_matching );

		// Register dashboard customizer (depends on settings service).
		$dashboard_customizer = new Admin\DashboardCustomizer( $settings_service );
		$this->container->set( 'admin.dashboard', $dashboard_customizer );

		$this->container->set( 'service.settings', $settings_service );
		$this->container->set( 'service.openai', $openai_service );
		$this->container->set( 'service.prompt_engine', $prompt_engine );
		$this->container->set( 'service.content_parser', $content_parser );
		$this->container->set( 'service.image_matching', $image_matching );
		$this->container->set( 'repository.generation_log', $log_repository );
		$this->container->set( 'service.cost_tracking', $cost_tracking );
		$this->container->set( 'service.generation', $generation_service );

		// Register cron jobs.
		$log_cleanup = new Cron\LogCleanup( $cost_tracking );
		$log_cleanup->register();
		$this->container->set( 'cron.log_cleanup', $log_cleanup );

		// Register import log cleanup cron job (Story 6.7).
		$import_log_cleanup = new Cron\ImportLogCleanup();
		$import_log_cleanup->register();
		$this->container->set( 'cron.import_log_cleanup', $import_log_cleanup );

		// Register queue cleanup cron job.
		$queue_cleanup = new Cron\QueueCleanup();
		$queue_cleanup->register();
		$this->container->set( 'cron.queue_cleanup', $queue_cleanup );

		// Register internal linking services (Story 8.1).
		$keyword_matcher = new Services\KeywordMatcher();
		$internal_linking_service = new Services\InternalLinkingService();
		$this->container->set( 'service.keyword_matcher', $keyword_matcher );
		$this->container->set( 'service.internal_linking', $internal_linking_service );

		// Register review services (Story 9.1, 9.2, 9.3).
		$google_business_service = new Services\GoogleBusinessService();
		$review_repository       = new Repositories\ReviewRepository();
		$review_fetch_service    = new Services\ReviewFetchService( $google_business_service, $review_repository );
		$this->container->set( 'service.google_business', $google_business_service );
		$this->container->set( 'repository.review', $review_repository );
		$this->container->set( 'service.review_fetch', $review_fetch_service );

		// Register link refresh cron job (Story 8.1).
		$link_refresh_handler = new Cron\LinkRefreshHandler();
		$link_refresh_handler->register();
		$this->container->set( 'cron.link_refresh', $link_refresh_handler );

		// Register WP-CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once SEO_GENERATOR_PLUGIN_DIR . 'includes/CLI/QueueCommand.php';
		}

		// Register REST API controllers.
		$generation_controller = new Controllers\GenerationController( $generation_service );
		$this->container->set( 'controller.generation', $generation_controller );

		$image_upload_controller = new Controllers\ImageUploadController();
		$this->container->set( 'controller.image_upload', $image_upload_controller );

		$image_diagnostic_controller = new Controllers\ImageDiagnosticController( $image_matching );
		$this->container->set( 'controller.image_diagnostic', $image_diagnostic_controller );
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function registerHooks(): void {
		// Initialize post types and taxonomies on init hook.
		add_action( 'init', array( $this, 'registerPostTypesAndTaxonomies' ) );

		// Configure ACF JSON save/load paths.
		add_filter( 'acf/settings/save_json', array( $this, 'acfJsonSavePath' ) );
		add_filter( 'acf/settings/load_json', array( $this, 'acfJsonLoadPath' ) );

		// Register ACF field groups.
		add_action( 'acf/init', array( $this, 'registerACFFieldGroups' ) );

		// Register settings page.
		$this->container->get( 'admin.settings' )->register();

		// Register dashboard customizer (before admin menu).
		$this->container->get( 'admin.dashboard' )->register();

		// Register admin menu.
		$this->container->get( 'admin.menu' )->register();

		// Register page editor.
		$this->container->get( 'admin.page_editor' )->register();

		// Register post list columns.
		$this->container->get( 'admin.post_list_columns' )->register();

		// Register post deletion handler.
		$this->container->get( 'hooks.post_deletion' )->register();

		// Register import page AJAX handlers.
		$this->container->get( 'admin.import' )->register();

		// Register queue status page AJAX handlers.
		$this->container->get( 'admin.queue_status' )->register();

		// Register geographic title generator page AJAX handlers.
		$this->container->get( 'admin.geo_titles' )->register();

		// Register internal linking test page.
		$this->container->get( 'admin.test_links' )->register();

		// Register template loader.
		$this->container->get( 'template.loader' )->register();

		// Enqueue global performance monitor on all admin pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueGlobalPerformanceMonitor' ) );

		// Register generation queue cron hook.
		add_action( 'seo_generate_queued_page', array( $this, 'processQueuedPage' ) );

		// Register background image generation cron hook.
		add_action( 'seo_generate_related_link_images', array( $this, 'generateRelatedLinkImages' ) );

		// Register queue cleanup cron hook.
		add_action( 'seo_cleanup_old_queue_jobs', array( $this, 'cleanupOldQueueJobs' ) );

		// Schedule daily cleanup if not already scheduled.
		if ( ! wp_next_scheduled( 'seo_cleanup_old_queue_jobs' ) ) {
			wp_schedule_event( time(), 'daily', 'seo_cleanup_old_queue_jobs' );
		}

		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'registerRestRoutes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function registerRestRoutes(): void {
		$this->container->get( 'controller.generation' )->register_routes();
		$this->container->get( 'controller.image_upload' )->register_routes();
		$this->container->get( 'controller.image_diagnostic' )->register_routes();
	}

	/**
	 * Register post types and taxonomies.
	 *
	 * @return void
	 */
	public function registerPostTypesAndTaxonomies(): void {
		// Register taxonomies first (recommended WordPress practice).
		$this->container->get( 'taxonomy.seo_topic' )->register();
		$this->container->get( 'taxonomy.image_tag' )->register();

		// Register post types.
		$this->container->get( 'post_type.seo_page' )->register();
	}

	/**
	 * Configure ACF JSON save path.
	 *
	 * @param string $path The default path.
	 * @return string
	 */
	public function acfJsonSavePath( string $path ): string {
		return SEO_GENERATOR_PLUGIN_DIR . 'acf-json';
	}

	/**
	 * Configure ACF JSON load paths.
	 *
	 * @param array $paths The default paths.
	 * @return array
	 */
	public function acfJsonLoadPath( array $paths ): array {
		$paths[] = SEO_GENERATOR_PLUGIN_DIR . 'acf-json';
		return $paths;
	}

	/**
	 * Register ACF field groups.
	 *
	 * @return void
	 */
	public function registerACFFieldGroups(): void {
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			$field_groups = new ACF\FieldGroups();
			$field_groups->register();
		}
	}

	/**
	 * Get container instance.
	 *
	 * @return Container
	 */
	public function getContainer(): Container {
		return $this->container;
	}

	/**
	 * Get ReviewFetchService instance (singleton).
	 *
	 * @return Services\ReviewFetchService
	 */
	public static function getReviewFetchService(): Services\ReviewFetchService {
		$instance = self::getInstance();
		return $instance->getContainer()->get( 'service.review_fetch' );
	}

	/**
	 * Process queued page (WordPress Cron handler).
	 *
	 * @param int $post_id Post ID to process.
	 * @return void
	 */
	public function processQueuedPage( int $post_id ): void {
		$generation_service = new Services\GenerationService();
		$generation_service->processQueuedPage( $post_id );
	}

	/**
	 * Generate images for related_links block (WordPress Cron handler).
	 *
	 * Runs in background AFTER page generation completes to avoid timeouts.
	 *
	 * @param int $post_id Post ID to generate images for.
	 * @return void
	 */
	public function generateRelatedLinkImages( int $post_id ): void {
		$image_service = new Services\RelatedLinksImageService();
		$image_service->generateImagesForPost( $post_id );
	}

	/**
	 * Clean up old queue jobs (WordPress Cron handler).
	 *
	 * Runs daily to remove completed/failed jobs older than 7 days.
	 *
	 * @return void
	 */
	public function cleanupOldQueueJobs(): void {
		$queue = new Services\GenerationQueue();
		$count = $queue->cleanupOldJobs( 7 );

		if ( $count > 0 ) {
			error_log( "[SEO Generator] Cleaned up {$count} old queue jobs" );
		}
	}

	/**
	 * Prevent cloning of the instance.
	 */
	private function __clone() {}

	/**
	 * Enqueue global performance monitor on all admin pages.
	 *
	 * @return void
	 */
	public function enqueueGlobalPerformanceMonitor(): void {
		wp_enqueue_script(
			'seo-global-perf-monitor',
			plugin_dir_url( __DIR__ ) . 'assets/js/src/global-perf-monitor.js',
			array(),
			'1.0.0',
			true
		);
	}

	/**
	 * Prevent unserializing of the instance.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
