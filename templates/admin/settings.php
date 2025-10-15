<?php
/**
 * Settings Page Template
 *
 * @package SEOGenerator
 * @var string $active_tab The currently active tab
 */

defined( 'ABSPATH' ) || exit;

$tabs = array(
	'api'      => __( 'API Configuration', 'seo-generator' ),
	'defaults' => __( 'Default Content', 'seo-generator' ),
	'prompts'  => __( 'Prompt Templates', 'seo-generator' ),
	'images'   => __( 'Image Library', 'seo-generator' ),
	'limits'   => __( 'Limits & Tracking', 'seo-generator' ),
);

$page_slug = \SEOGenerator\Admin\SettingsPage::getPageSlug();
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Tab Navigation -->
	<h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=' . $tab_key ) ); ?>"
			   class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<!-- Settings Form -->
	<form method="post" action="options.php">
		<?php
		// Output security fields for main option group.
		settings_fields( \SEOGenerator\Admin\SettingsPage::getOptionGroup() );

		// For images tab, also register the image settings option group.
		if ( 'images' === $active_tab ) {
			settings_fields( \SEOGenerator\Admin\SettingsPage::getImageOptionGroup() );
		}

		// Output tab-specific settings sections.
		switch ( $active_tab ) {
			case 'api':
				do_settings_sections( $page_slug . '_api' );
				break;

			case 'defaults':
				do_settings_sections( $page_slug . '_defaults' );
				?>
				<div class="notice notice-info inline">
					<p>
						<strong><?php esc_html_e( 'Coming Soon:', 'seo-generator' ); ?></strong>
						<?php esc_html_e( 'Set default CTA button text, URLs, warranty information, and care instructions that will be used across all generated pages.', 'seo-generator' ); ?>
					</p>
				</div>
				<?php
				break;

			case 'prompts':
				do_settings_sections( $page_slug . '_prompts' );
				?>
				<div class="notice notice-info inline">
					<p>
						<strong><?php esc_html_e( 'Coming Soon:', 'seo-generator' ); ?></strong>
						<?php esc_html_e( 'Customize prompt templates for each of the 12 content blocks. Edit system messages and variable placeholders to fine-tune AI output.', 'seo-generator' ); ?>
					</p>
				</div>
				<?php
				break;

			case 'images':
				do_settings_sections( $page_slug . '_images' );
				break;

			case 'limits':
				do_settings_sections( $page_slug . '_limits' );
				?>
				<div class="notice notice-info inline">
					<p>
						<strong><?php esc_html_e( 'Coming Soon:', 'seo-generator' ); ?></strong>
						<?php esc_html_e( 'Set rate limits, concurrent generation limits, enable cost tracking, configure monthly budgets, and view current usage statistics.', 'seo-generator' ); ?>
					</p>
				</div>
				<?php
				break;
		}

		// Output save button.
		submit_button();
		?>
	</form>

	<!-- Help Information -->
	<div class="card" style="max-width: 100%; margin-top: 20px;">
		<h2><?php esc_html_e( 'About Settings', 'seo-generator' ); ?></h2>
		<p>
			<?php
			esc_html_e(
				'This settings page provides configuration options for the SEO Content Generator plugin. Settings will be added progressively in upcoming development stories.',
				'seo-generator'
			);
			?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Next Steps:', 'seo-generator' ); ?></strong>
		</p>
		<ul>
			<li><?php esc_html_e( 'Story 2.1: OpenAI API integration and configuration', 'seo-generator' ); ?></li>
			<li><?php esc_html_e( 'Story 2.2: Prompt template engine and customization', 'seo-generator' ); ?></li>
			<li><?php esc_html_e( 'Future: Default content, image settings, and usage limits', 'seo-generator' ); ?></li>
		</ul>
	</div>
</div>

<style>
	/* Ensure inline notices don't have excessive margin */
	.notice.inline {
		margin: 20px 0;
		padding: 12px;
	}

	.card {
		background: #fff;
		border: 1px solid #ccd0d4;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
		padding: 20px;
		margin-bottom: 20px;
	}

	.card h2 {
		margin-top: 0;
	}

	.card ul {
		margin-left: 20px;
	}
</style>
