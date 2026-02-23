<?php
/**
 * Dashboard Customizer
 *
 * Customizes the WordPress admin dashboard for the Content Generator plugin.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

use SEOGenerator\Services\SettingsService;
use SEOGenerator\Services\GenerationQueue;

defined( 'ABSPATH' ) || exit;

/**
 * Handles custom dashboard page, sidebar cleanup, admin bar cleanup, and login branding.
 */
class DashboardCustomizer {
	/**
	 * Dashboard page slug.
	 *
	 * @var string
	 */
	private const DASHBOARD_SLUG = 'seo-dashboard';

	/**
	 * Settings service instance.
	 *
	 * @var SettingsService
	 */
	private $settings_service;

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings_service Settings service instance.
	 */
	public function __construct( SettingsService $settings_service ) {
		$this->settings_service = $settings_service;
	}

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Custom dashboard page.
		add_action( 'admin_menu', array( $this, 'addDashboardPage' ), 5 );

		// Sidebar cleanup (high priority to run after all menus registered).
		add_action( 'admin_menu', array( $this, 'cleanupSidebar' ), 999 );

		// Admin bar cleanup.
		add_action( 'wp_before_admin_bar_render', array( $this, 'cleanupAdminBar' ), 999 );

		// Login page branding.
		add_action( 'login_enqueue_scripts', array( $this, 'brandLoginPage' ) );
		add_filter( 'login_headerurl', array( $this, 'loginLogoUrl' ) );
		add_filter( 'login_headertext', array( $this, 'loginLogoText' ) );

		// Enqueue dashboard styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueDashboardStyles' ) );

		// Redirect default dashboard to custom dashboard (after menus are registered).
		add_action( 'current_screen', array( $this, 'redirectDefaultDashboard' ) );

		// Redirect after login to custom dashboard.
		add_filter( 'login_redirect', array( $this, 'loginRedirect' ), 10, 3 );
	}

	/**
	 * Add the custom dashboard page to admin menu.
	 *
	 * @return void
	 */
	public function addDashboardPage(): void {
		add_submenu_page(
			'seo-content-generator',
			__( 'Dashboard', 'seo-generator' ),
			__( 'Dashboard', 'seo-generator' ),
			'edit_posts',
			self::DASHBOARD_SLUG,
			array( $this, 'renderDashboard' ),
			0
		);
	}

	/**
	 * Remove unwanted sidebar menu items.
	 *
	 * Keeps: Content Generator, Users, Plugins.
	 *
	 * @return void
	 */
	public function cleanupSidebar(): void {
		// Remove default WordPress menus.
		remove_menu_page( 'index.php' );           // Dashboard.
		remove_menu_page( 'edit.php' );             // Posts.
		remove_menu_page( 'upload.php' );           // Media.
		remove_menu_page( 'edit.php?post_type=page' ); // Pages.
		remove_menu_page( 'edit-comments.php' );    // Comments.
		remove_menu_page( 'themes.php' );           // Appearance.
		remove_menu_page( 'tools.php' );            // Tools.
		remove_menu_page( 'options-general.php' );  // Settings.
	}

	/**
	 * Remove unwanted admin bar items.
	 *
	 * Keeps: site name, user menu.
	 *
	 * @return void
	 */
	public function cleanupAdminBar(): void {
		global $wp_admin_bar;

		$wp_admin_bar->remove_node( 'wp-logo' );     // WordPress logo.
		$wp_admin_bar->remove_node( 'new-content' );  // "+ New" dropdown.
		$wp_admin_bar->remove_node( 'comments' );     // Comments link.
		$wp_admin_bar->remove_node( 'updates' );      // Updates link.
	}

	/**
	 * Brand the login page.
	 *
	 * @return void
	 */
	public function brandLoginPage(): void {
		$business_name = $this->settings_service->getBusinessName();

		if ( empty( $business_name ) ) {
			$business_name = 'Content Generator';
		}

		?>
		<style>
			body.login {
				background-color: #272521 !important;
			}
			#login h1 a {
				background-image: none !important;
				width: auto !important;
				height: auto !important;
				text-indent: 0 !important;
				font-size: 28px !important;
				font-weight: 700 !important;
				color: #CA9652 !important;
				letter-spacing: 1px;
				padding: 0 0 20px 0 !important;
			}
			.login form {
				background: #3D3935 !important;
				border: 1px solid rgba(202, 150, 82, 0.2) !important;
				border-radius: 12px !important;
				box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
			}
			.login form .input,
			.login form input[type="text"],
			.login form input[type="password"] {
				background: #272521 !important;
				border: 1px solid rgba(202, 150, 82, 0.3) !important;
				color: #E5C697 !important;
				border-radius: 8px !important;
			}
			.login form .input:focus,
			.login form input[type="text"]:focus,
			.login form input[type="password"]:focus {
				border-color: #CA9652 !important;
				box-shadow: 0 0 0 1px #CA9652 !important;
			}
			.login label {
				color: #E5C697 !important;
			}
			.wp-core-ui .button-primary {
				background: linear-gradient(135deg, #CA9652 0%, #A67835 100%) !important;
				border: none !important;
				border-radius: 8px !important;
				color: #fff !important;
				text-shadow: none !important;
				box-shadow: 0 2px 8px rgba(202, 150, 82, 0.3) !important;
				padding: 6px 24px !important;
				font-weight: 600 !important;
			}
			.wp-core-ui .button-primary:hover {
				background: linear-gradient(135deg, #E5C697 0%, #CA9652 100%) !important;
			}
			.login #backtoblog a,
			.login #nav a {
				color: #CA9652 !important;
			}
			.login #backtoblog a:hover,
			.login #nav a:hover {
				color: #E5C697 !important;
			}
			.login .message,
			.login .success {
				border-left-color: #CA9652 !important;
				background: rgba(202, 150, 82, 0.1) !important;
				color: #E5C697 !important;
			}
			#login_error {
				border-left-color: #FF3B30 !important;
				background: rgba(255, 59, 48, 0.1) !important;
				color: #FF3B30 !important;
			}
			.login .privacy-policy-page-link a {
				color: #CA9652 !important;
			}
		</style>
		<?php
	}

	/**
	 * Custom login logo URL.
	 *
	 * @return string
	 */
	public function loginLogoUrl(): string {
		return admin_url();
	}

	/**
	 * Custom login logo text.
	 *
	 * @return string
	 */
	public function loginLogoText(): string {
		$business_name = $this->settings_service->getBusinessName();
		return ! empty( $business_name ) ? $business_name : 'Content Generator';
	}

	/**
	 * Enqueue dashboard-specific styles.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueueDashboardStyles( string $hook_suffix ): void {
		if ( 'seo-content-generator_page_seo-dashboard' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'seo-dashboard-css',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/admin-dashboard.css',
			array(),
			'1.0.0'
		);
	}

	/**
	 * Redirect default WordPress dashboard to custom dashboard.
	 *
	 * Fires on 'current_screen' which is after admin_menu, so our page is registered.
	 *
	 * @return void
	 */
	public function redirectDefaultDashboard(): void {
		$screen = get_current_screen();

		if ( $screen && 'dashboard' === $screen->id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::DASHBOARD_SLUG ) );
			exit;
		}
	}

	/**
	 * Redirect to custom dashboard after login.
	 *
	 * @param string           $redirect_to The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect destination URL.
	 * @param \WP_User|\WP_Error $user WP_User object or WP_Error on login failure.
	 * @return string
	 */
	public function loginRedirect( string $redirect_to, string $requested_redirect_to, $user ): string {
		if ( $user instanceof \WP_User && $user->has_cap( 'edit_posts' ) ) {
			return admin_url( 'admin.php?page=' . self::DASHBOARD_SLUG );
		}

		return $redirect_to;
	}

	/**
	 * Render the custom dashboard page.
	 *
	 * @return void
	 */
	public function renderDashboard(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-generator' ) );
		}

		// Gather dashboard data.
		$data = $this->getDashboardData();

		// Load the template.
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/admin/dashboard.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Get all dashboard data.
	 *
	 * @return array Dashboard data.
	 */
	private function getDashboardData(): array {
		$post_counts = wp_count_posts( 'seo-page' );
		$queue       = new GenerationQueue();
		$queue_stats = $queue->getQueueStats();

		$recent_pages = get_posts(
			array(
				'post_type'      => 'seo-page',
				'posts_per_page' => 5,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'post_status'    => array( 'publish', 'draft', 'pending' ),
			)
		);

		$business_name = $this->settings_service->getBusinessName();

		return array(
			'business_name' => $business_name,
			'total_pages'   => isset( $post_counts->publish ) ? (int) $post_counts->publish + (int) $post_counts->draft : 0,
			'published'     => isset( $post_counts->publish ) ? (int) $post_counts->publish : 0,
			'drafts'        => isset( $post_counts->draft ) ? (int) $post_counts->draft : 0,
			'queue_pending' => $queue_stats['pending'] + $queue_stats['processing'],
			'queue_stats'   => $queue_stats,
			'recent_pages'  => $recent_pages,
			'current_user'  => wp_get_current_user(),
		);
	}
}
