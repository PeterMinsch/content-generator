<?php
/**
 * Settings Page Manager
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

use SEOGenerator\Exceptions\OpenAIException;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the plugin settings page.
 */
class SettingsPage {
	/**
	 * Valid models for OpenAI.
	 */
	private const VALID_MODELS = array(
		'gpt-4-turbo-preview',
		'gpt-4',
		'gpt-3.5-turbo',
	);
	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'seo_generator_settings';

	/**
	 * Option name for storing image settings.
	 *
	 * @var string
	 */
	private const IMAGE_OPTION_NAME = 'seo_generator_image_settings';

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	private const OPTION_GROUP = 'seo_generator_options';

	/**
	 * Option group name for image settings.
	 *
	 * @var string
	 */
	private const IMAGE_OPTION_GROUP = 'seo_generator_image_options';

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'seo-generator-settings';

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
		add_action( 'admin_notices', array( $this, 'displayAdminNotices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
		add_action( 'wp_ajax_seo_generator_test_connection', array( $this, 'testConnection' ) );
	}

	/**
	 * Enqueue admin assets for settings page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueueAssets( string $hook ): void {
		// Only enqueue on our settings page.
		if ( 'seo-page_page_seo-generator-settings' !== $hook ) {
			return;
		}

		// Enqueue WordPress media library.
		wp_enqueue_media();

		// Enqueue admin CSS.
		wp_enqueue_style(
			'seo-generator-settings',
			plugins_url( 'assets/css/admin-settings.css', dirname( __DIR__ ) ),
			array(),
			'1.0.0'
		);

		// Enqueue admin JavaScript.
		wp_enqueue_script(
			'seo-generator-settings',
			plugins_url( 'assets/js/settings.js', dirname( __DIR__ ) ),
			array( 'jquery', 'media-upload', 'media-views' ),
			'1.0.0',
			true
		);

		// Localize script with AJAX URL and nonce.
		wp_localize_script(
			'seo-generator-settings',
			'seoGeneratorSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'seo_generator_nonce' ),
			)
		);
	}

	/**
	 * Register settings using WordPress Settings API.
	 *
	 * @return void
	 */
	public function registerSettings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitizeSettings' ),
			)
		);

		// Register image settings separately.
		register_setting(
			self::IMAGE_OPTION_GROUP,
			self::IMAGE_OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitizeImageSettings' ),
			)
		);

		// Register sections and fields for each tab (placeholders for now).
		$this->registerApiConfigSection();
		$this->registerDefaultContentSection();
		$this->registerPromptTemplatesSection();
		$this->registerImageLibrarySection();
		$this->registerLimitsTrackingSection();
		$this->registerReviewsSection();
	}

	/**
	 * Register API Configuration section.
	 *
	 * @return void
	 */
	private function registerApiConfigSection(): void {
		add_settings_section(
			'seo_api_section',
			__( 'OpenAI API Configuration', 'seo-generator' ),
			array( $this, 'renderApiSectionDescription' ),
			self::PAGE_SLUG . '_api'
		);

		// API Key field.
		add_settings_field(
			'openai_api_key',
			__( 'API Key', 'seo-generator' ),
			array( $this, 'renderApiKeyField' ),
			self::PAGE_SLUG . '_api',
			'seo_api_section'
		);

		// Model field.
		add_settings_field(
			'model',
			__( 'Model', 'seo-generator' ),
			array( $this, 'renderModelField' ),
			self::PAGE_SLUG . '_api',
			'seo_api_section'
		);

		// Temperature field.
		add_settings_field(
			'temperature',
			__( 'Temperature', 'seo-generator' ),
			array( $this, 'renderTemperatureField' ),
			self::PAGE_SLUG . '_api',
			'seo_api_section'
		);

		// Max Tokens field.
		add_settings_field(
			'max_tokens',
			__( 'Max Tokens', 'seo-generator' ),
			array( $this, 'renderMaxTokensField' ),
			self::PAGE_SLUG . '_api',
			'seo_api_section'
		);

		// Test Connection section.
		add_settings_field(
			'test_connection',
			__( 'Test Connection', 'seo-generator' ),
			array( $this, 'renderTestConnectionField' ),
			self::PAGE_SLUG . '_api',
			'seo_api_section'
		);
	}

	/**
	 * Register Default Content section (placeholder).
	 *
	 * @return void
	 */
	private function registerDefaultContentSection(): void {
		add_settings_section(
			'seo_defaults_section',
			__( 'Default Content', 'seo-generator' ),
			array( $this, 'renderSectionDescription' ),
			self::PAGE_SLUG . '_defaults'
		);
	}

	/**
	 * Register Prompt Templates section (placeholder).
	 *
	 * @return void
	 */
	private function registerPromptTemplatesSection(): void {
		add_settings_section(
			'seo_prompts_section',
			__( 'Prompt Templates', 'seo-generator' ),
			array( $this, 'renderSectionDescription' ),
			self::PAGE_SLUG . '_prompts'
		);
	}

	/**
	 * Register Image Library section.
	 *
	 * @return void
	 */
	private function registerImageLibrarySection(): void {
		add_settings_section(
			'seo_images_section',
			__( 'Image Library Settings', 'seo-generator' ),
			array( $this, 'renderImageLibrarySectionDescription' ),
			self::PAGE_SLUG . '_images'
		);

		// Auto-assignment checkbox.
		add_settings_field(
			'enable_auto_assignment',
			__( 'Auto-Assign Images', 'seo-generator' ),
			array( $this, 'renderAutoAssignmentField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// Default image picker.
		add_settings_field(
			'default_image_id',
			__( 'Default Hero Image', 'seo-generator' ),
			array( $this, 'renderDefaultImageField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// Folder organization section divider.
		add_settings_field(
			'folder_organization_divider',
			'<hr><h3>' . __( 'Folder Organization', 'seo-generator' ) . '</h3>',
			array( $this, 'renderDividerField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// Preserve folder structure checkbox.
		add_settings_field(
			'preserve_folder_structure',
			__( 'Preserve Folder Structure', 'seo-generator' ),
			array( $this, 'renderPreserveFolderField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// Use folder as primary tag checkbox.
		add_settings_field(
			'use_folder_as_primary_tag',
			__( 'Use Folder as Primary Tag', 'seo-generator' ),
			array( $this, 'renderUseFolderAsPrimaryTagField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// AI Alt Text section divider.
		add_settings_field(
			'ai_alt_text_divider',
			'<hr><h3>' . __( 'AI Alt Text Generation', 'seo-generator' ) . '</h3>',
			array( $this, 'renderDividerField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// AI Alt Text Generation checkbox.
		add_settings_field(
			'use_ai_alt_text',
			__( 'Use AI for Alt Text', 'seo-generator' ),
			array( $this, 'renderUseAIAltTextField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// AI Alt Text Model selection.
		add_settings_field(
			'ai_alt_text_model',
			__( 'AI Model for Alt Text', 'seo-generator' ),
			array( $this, 'renderAIAltTextModelField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// AI Alt Text Fallback checkbox.
		add_settings_field(
			'ai_alt_text_fallback',
			__( 'Fallback to Tag-Based Alt Text', 'seo-generator' ),
			array( $this, 'renderAIAltTextFallbackField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// Image Download section divider.
		add_settings_field(
			'image_download_divider',
			'<hr><h3>' . __( 'Image Downloads (CSV Import)', 'seo-generator' ) . '</h3>',
			array( $this, 'renderDividerField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// Image download timeout field.
		add_settings_field(
			'image_download_timeout',
			__( 'Download Timeout', 'seo-generator' ),
			array( $this, 'renderImageDownloadTimeoutField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// Max image size field.
		add_settings_field(
			'max_image_size',
			__( 'Maximum Image Size', 'seo-generator' ),
			array( $this, 'renderMaxImageSizeField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);

		// Skip duplicate images checkbox.
		add_settings_field(
			'skip_duplicate_images',
			__( 'Skip Duplicate Images', 'seo-generator' ),
			array( $this, 'renderSkipDuplicateImagesField' ),
			self::PAGE_SLUG . '_images',
			'seo_images_section'
		);
	}

	/**
	 * Register Limits & Tracking section (placeholder).
	 *
	 * @return void
	 */
	private function registerLimitsTrackingSection(): void {
		add_settings_section(
			'seo_limits_section',
			__( 'Limits & Tracking', 'seo-generator' ),
			array( $this, 'renderSectionDescription' ),
			self::PAGE_SLUG . '_limits'
		);
	}

	/**
	 * Register Review Integration section (Apify).
	 *
	 * @return void
	 */
	private function registerReviewsSection(): void {
		add_settings_section(
			'seo_reviews_section',
			__( 'Review Integration (Apify)', 'seo-generator' ),
			array( $this, 'renderReviewsSectionDescription' ),
			self::PAGE_SLUG . '_reviews'
		);

		// Apify API Token field.
		add_settings_field(
			'apify_api_token',
			__( 'Apify API Token', 'seo-generator' ),
			array( $this, 'renderApifyApiTokenField' ),
			self::PAGE_SLUG . '_reviews',
			'seo_reviews_section'
		);

		// Google Maps Place URL field.
		add_settings_field(
			'place_url',
			__( 'Google Maps Place URL', 'seo-generator' ),
			array( $this, 'renderPlaceUrlField' ),
			self::PAGE_SLUG . '_reviews',
			'seo_reviews_section'
		);

		// Max Reviews field.
		add_settings_field(
			'max_reviews',
			__( 'Maximum Reviews', 'seo-generator' ),
			array( $this, 'renderMaxReviewsField' ),
			self::PAGE_SLUG . '_reviews',
			'seo_reviews_section'
		);
	}

	/**
	 * Render API section description.
	 *
	 * @return void
	 */
	public function renderApiSectionDescription(): void {
		echo '<p>' . esc_html__( 'Configure your OpenAI API credentials and generation settings.', 'seo-generator' ) . '</p>';
	}

	/**
	 * Render section description (placeholder for other tabs).
	 *
	 * @return void
	 */
	public function renderSectionDescription(): void {
		echo '<p>' . esc_html__( 'Settings fields will be added in future stories.', 'seo-generator' ) . '</p>';
	}

	/**
	 * Render Image Library section description.
	 *
	 * @return void
	 */
	public function renderImageLibrarySectionDescription(): void {
		echo '<p>' . esc_html__( 'Configure automatic image assignment during content generation.', 'seo-generator' ) . '</p>';
	}

	/**
	 * Render Review Integration section description.
	 *
	 * @return void
	 */
	public function renderReviewsSectionDescription(): void {
		?>
		<p><?php esc_html_e( 'Configure Apify integration to fetch Google Maps reviews for your business.', 'seo-generator' ); ?></p>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: link to Apify setup guide */
					__( 'Need help? See the <a href="%s" target="_blank">Apify Setup Guide</a> for detailed instructions.', 'seo-generator' ),
					plugins_url( 'docs/apify-setup-guide.md', dirname( __DIR__ ) )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render Auto-Assignment field.
	 *
	 * @return void
	 */
	public function renderAutoAssignmentField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$enabled  = $settings['enable_auto_assignment'] ?? true; // Default enabled.

		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[enable_auto_assignment]"
				value="1"
				<?php checked( $enabled, true ); ?>
			/>
			<?php esc_html_e( 'Automatically assign images to content blocks during generation', 'seo-generator' ); ?>
		</label>
		<p class="description">
			<?php
			esc_html_e(
				'When enabled, the plugin will automatically find and assign relevant images from your image library based on content keywords and tags.',
				'seo-generator'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render Default Image field.
	 *
	 * @return void
	 */
	public function renderDefaultImageField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$image_id = $settings['default_image_id'] ?? null;

		?>
		<div class="default-image-picker">
			<input
				type="hidden"
				name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[default_image_id]"
				id="default_image_id"
				value="<?php echo $image_id ? esc_attr( $image_id ) : ''; ?>"
			/>

			<button type="button" class="button" id="select_default_image">
				<?php esc_html_e( 'Select Default Image', 'seo-generator' ); ?>
			</button>

			<?php if ( $image_id ) : ?>
				<button type="button" class="button" id="remove_default_image">
					<?php esc_html_e( 'Remove', 'seo-generator' ); ?>
				</button>
			<?php endif; ?>

			<div class="default-image-preview" style="margin-top: 10px;">
				<?php if ( $image_id && wp_get_attachment_image_url( $image_id, 'medium' ) ) : ?>
					<?php echo wp_get_attachment_image( $image_id, 'medium', false, array( 'style' => 'max-width: 300px; height: auto;' ) ); ?>
				<?php endif; ?>
			</div>

			<p class="description">
				<?php esc_html_e( 'This image will be used when no matching images are found in the library.', 'seo-generator' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render divider field (empty field for visual organization).
	 *
	 * @return void
	 */
	public function renderDividerField(): void {
		// Empty render - divider is in the label.
	}

	/**
	 * Render Preserve Folder Structure field.
	 *
	 * @return void
	 */
	public function renderPreserveFolderField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$enabled  = $settings['preserve_folder_structure'] ?? true; // Default enabled.

		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[preserve_folder_structure]"
				value="1"
				<?php checked( $enabled, true ); ?>
			/>
			<?php esc_html_e( 'Store folder information when uploading folders', 'seo-generator' ); ?>
		</label>
		<p class="description">
			<?php
			esc_html_e(
				'When enabled, images uploaded via folder upload will retain their folder name metadata for organization and filtering.',
				'seo-generator'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render Use Folder as Primary Tag field.
	 *
	 * @return void
	 */
	public function renderUseFolderAsPrimaryTagField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$enabled  = $settings['use_folder_as_primary_tag'] ?? true; // Default enabled.

		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[use_folder_as_primary_tag]"
				value="1"
				<?php checked( $enabled, true ); ?>
			/>
			<?php esc_html_e( 'Prioritize folder-based tags during image matching', 'seo-generator' ); ?>
		</label>
		<p class="description">
			<?php
			esc_html_e(
				'When enabled, folder names will be used as primary tags and prioritized when matching images to content. For example, images in a "wedding-bands" folder will be selected first when generating content about wedding bands.',
				'seo-generator'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render use AI for alt text field.
	 *
	 * @return void
	 */
	public function renderUseAIAltTextField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$enabled  = $settings['use_ai_alt_text'] ?? false; // Default disabled (opt-in).

		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[use_ai_alt_text]"
				value="1"
				<?php checked( $enabled, true ); ?>
			/>
			<?php esc_html_e( 'Generate natural, SEO-friendly alt text using AI', 'seo-generator' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Uses OpenAI to create descriptive alt text from image metadata (filename, folder, tags, focus keyword). Cost: ~$0.001 per image.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render AI alt text model selection field.
	 *
	 * @return void
	 */
	public function renderAIAltTextModelField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$model    = $settings['ai_alt_text_model'] ?? 'gpt-4o-mini'; // Default to cheapest model.

		?>
		<select name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[ai_alt_text_model]">
			<option value="gpt-4o-mini" <?php selected( $model, 'gpt-4o-mini' ); ?>>
				<?php esc_html_e( 'GPT-4o Mini (Recommended - ~$0.001/image)', 'seo-generator' ); ?>
			</option>
			<option value="gpt-4o" <?php selected( $model, 'gpt-4o' ); ?>>
				<?php esc_html_e( 'GPT-4o (~$0.005/image)', 'seo-generator' ); ?>
			</option>
			<option value="gpt-4-turbo-preview" <?php selected( $model, 'gpt-4-turbo-preview' ); ?>>
				<?php esc_html_e( 'GPT-4 Turbo (~$0.01/image)', 'seo-generator' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Choose AI model for alt text generation. GPT-4o Mini is recommended for cost efficiency.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render AI alt text fallback field.
	 *
	 * @return void
	 */
	public function renderAIAltTextFallbackField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$enabled  = $settings['ai_alt_text_fallback'] ?? true; // Default enabled.

		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[ai_alt_text_fallback]"
				value="1"
				<?php checked( $enabled, true ); ?>
			/>
			<?php esc_html_e( 'Use tag-based alt text if AI generation fails', 'seo-generator' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Recommended: Ensures alt text is always generated even if API fails.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render image download timeout field.
	 *
	 * @return void
	 */
	public function renderImageDownloadTimeoutField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$timeout  = $settings['image_download_timeout'] ?? 30; // Default 30 seconds.

		?>
		<input
			type="number"
			name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[image_download_timeout]"
			value="<?php echo esc_attr( $timeout ); ?>"
			min="10"
			max="120"
			step="5"
			class="small-text"
		/>
		<span><?php esc_html_e( 'seconds', 'seo-generator' ); ?></span>
		<p class="description">
			<?php esc_html_e( 'Maximum time to wait for image downloads during CSV import. Default: 30 seconds.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render maximum image size field.
	 *
	 * @return void
	 */
	public function renderMaxImageSizeField(): void {
		$settings      = get_option( self::IMAGE_OPTION_NAME, array() );
		$max_size_mb   = $settings['max_image_size'] ?? 5; // Default 5MB.

		?>
		<input
			type="number"
			name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[max_image_size]"
			value="<?php echo esc_attr( $max_size_mb ); ?>"
			min="1"
			max="50"
			step="1"
			class="small-text"
		/>
		<span><?php esc_html_e( 'MB', 'seo-generator' ); ?></span>
		<p class="description">
			<?php esc_html_e( 'Maximum file size for downloaded images. Images larger than this will be skipped. Default: 5MB.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render skip duplicate images field.
	 *
	 * @return void
	 */
	public function renderSkipDuplicateImagesField(): void {
		$settings = get_option( self::IMAGE_OPTION_NAME, array() );
		$enabled  = $settings['skip_duplicate_images'] ?? true; // Default enabled.

		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::IMAGE_OPTION_NAME ); ?>[skip_duplicate_images]"
				value="1"
				<?php checked( $enabled, true ); ?>
			/>
			<?php esc_html_e( 'Reuse existing images if the same URL has already been imported', 'seo-generator' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Recommended: Prevents duplicate downloads and saves bandwidth. Images are matched by source URL.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Apify API Token field.
	 *
	 * @return void
	 */
	public function renderApifyApiTokenField(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$has_key  = ! empty( $settings['apify_api_token'] );

		?>
		<input
			type="password"
			id="apify_api_token"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[apify_api_token]"
			value="<?php echo $has_key ? esc_attr( '****************************************' ) : ''; ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'apify_api_...', 'seo-generator' ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'Your Apify API token. Get it from Apify Console → Settings → Integrations. ', 'seo-generator' ); ?>
			<a href="https://console.apify.com/account/integrations" target="_blank">
				<?php esc_html_e( 'Get your token', 'seo-generator' ); ?>
			</a>
		</p>
		<?php if ( $has_key ) : ?>
			<p class="description">
				<em><?php esc_html_e( 'API token is already configured. Enter a new token to replace it.', 'seo-generator' ); ?></em>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Place URL field.
	 *
	 * @return void
	 */
	public function renderPlaceUrlField(): void {
		$settings  = get_option( self::OPTION_NAME, array() );
		$place_url = $settings['place_url'] ?? '';

		?>
		<input
			type="url"
			id="place_url"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[place_url]"
			value="<?php echo esc_attr( $place_url ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'https://www.google.com/maps/place/...', 'seo-generator' ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'Your business Google Maps URL. Go to Google Maps, search for your business, and copy the URL.', 'seo-generator' ); ?>
		</p>
		<p class="description">
			<strong><?php esc_html_e( 'Example:', 'seo-generator' ); ?></strong>
			<code>https://www.google.com/maps/place/Bravo+Jewelers/@40.7580,-73.9855,17z/...</code>
		</p>
		<?php
	}

	/**
	 * Render Max Reviews field.
	 *
	 * @return void
	 */
	public function renderMaxReviewsField(): void {
		$settings    = get_option( self::OPTION_NAME, array() );
		$max_reviews = $settings['max_reviews'] ?? 50;

		?>
		<input
			type="number"
			id="max_reviews"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[max_reviews]"
			value="<?php echo esc_attr( $max_reviews ); ?>"
			min="5"
			max="100"
			step="5"
			class="small-text"
		/>
		<span><?php esc_html_e( 'reviews', 'seo-generator' ); ?></span>
		<p class="description">
			<?php esc_html_e( 'Maximum number of reviews to fetch per request. Default: 50. Higher values may increase scraper run time and cost.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render API Key field.
	 *
	 * @return void
	 */
	public function renderApiKeyField(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$has_key  = ! empty( $settings['openai_api_key'] );

		?>
		<input
			type="password"
			id="api_key"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[openai_api_key]"
			value="<?php echo $has_key ? esc_attr( '****************************************' ) : ''; ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'sk-...', 'seo-generator' ); ?>"
		/>
		<p class="description">
			<?php
			esc_html_e( 'Your OpenAI API key. Keys start with "sk-". ', 'seo-generator' );
			?>
			<a href="https://platform.openai.com/api-keys" target="_blank">
				<?php esc_html_e( 'Get your API key', 'seo-generator' ); ?>
			</a>
		</p>
		<?php if ( $has_key ) : ?>
			<p class="description">
				<em><?php esc_html_e( 'API key is already configured. Enter a new key to replace it.', 'seo-generator' ); ?></em>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Model field.
	 *
	 * @return void
	 */
	public function renderModelField(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$model    = $settings['model'] ?? 'gpt-4-turbo-preview';

		?>
		<select id="model" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[model]">
			<option value="gpt-4-turbo-preview" <?php selected( $model, 'gpt-4-turbo-preview' ); ?>>
				GPT-4 Turbo (Recommended - Best balance of speed and quality)
			</option>
			<option value="gpt-4" <?php selected( $model, 'gpt-4' ); ?>>
				GPT-4 (Highest quality, slower)
			</option>
			<option value="gpt-3.5-turbo" <?php selected( $model, 'gpt-3.5-turbo' ); ?>>
				GPT-3.5 Turbo (Fastest, lower cost)
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the OpenAI model to use for content generation.', 'seo-generator' ); ?>
			<a href="https://platform.openai.com/docs/models" target="_blank">
				<?php esc_html_e( 'Learn more about models', 'seo-generator' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render Temperature field.
	 *
	 * @return void
	 */
	public function renderTemperatureField(): void {
		$settings    = get_option( self::OPTION_NAME, array() );
		$temperature = $settings['temperature'] ?? 0.7;

		?>
		<input
			type="range"
			id="temperature"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[temperature]"
			min="0.1"
			max="1.0"
			step="0.1"
			value="<?php echo esc_attr( $temperature ); ?>"
		/>
		<span id="temperature-value"><?php echo esc_html( $temperature ); ?></span>
		<p class="description">
			<?php esc_html_e( 'Controls randomness. Lower values (0.1-0.3) are more focused and deterministic. Higher values (0.7-1.0) are more creative and varied.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Max Tokens field.
	 *
	 * @return void
	 */
	public function renderMaxTokensField(): void {
		$settings   = get_option( self::OPTION_NAME, array() );
		$max_tokens = $settings['max_tokens'] ?? 1000;

		?>
		<input
			type="number"
			id="max_tokens"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[max_tokens]"
			value="<?php echo esc_attr( $max_tokens ); ?>"
			min="100"
			max="4000"
			step="100"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Maximum number of tokens to generate per request (100-4000). Roughly 1 token = 0.75 words.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Test Connection field.
	 *
	 * @return void
	 */
	public function renderTestConnectionField(): void {
		?>
		<button type="button" id="test-connection-btn" class="button">
			<?php esc_html_e( 'Test Connection', 'seo-generator' ); ?>
		</button>
		<div id="test-results" style="margin-top: 10px;"></div>
		<p class="description">
			<?php esc_html_e( 'Test your API key connection before saving settings.', 'seo-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input from form.
	 * @return array Sanitized settings.
	 */
	public function sanitizeSettings( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		$existing  = get_option( self::OPTION_NAME, array() );

		// API Key - encrypt before storage.
		if ( isset( $input['openai_api_key'] ) && ! empty( $input['openai_api_key'] ) ) {
			$api_key = sanitize_text_field( $input['openai_api_key'] );

			// Check if it's the masked value (already encrypted).
			if ( str_starts_with( $api_key, '***' ) ) {
				// Keep existing key.
				$sanitized['openai_api_key'] = $existing['openai_api_key'] ?? '';
			} elseif ( str_starts_with( $api_key, 'sk-' ) ) {
				// New key - encrypt it.
				$encrypted = seo_generator_encrypt_api_key( $api_key );
				if ( false !== $encrypted ) {
					$sanitized['openai_api_key'] = $encrypted;
				} else {
					add_settings_error(
						self::OPTION_NAME,
						'encryption_failed',
						__( 'Failed to encrypt API key. Please try again.', 'seo-generator' )
					);
					$sanitized['openai_api_key'] = $existing['openai_api_key'] ?? '';
				}
			} else {
				// Invalid key format.
				add_settings_error(
					self::OPTION_NAME,
					'invalid_api_key',
					__( 'Invalid API key format. OpenAI API keys start with "sk-".', 'seo-generator' )
				);
				$sanitized['openai_api_key'] = $existing['openai_api_key'] ?? '';
			}
		} else {
			// No key provided - keep existing.
			$sanitized['openai_api_key'] = $existing['openai_api_key'] ?? '';
		}

		// Model - validate against whitelist.
		if ( isset( $input['model'] ) && in_array( $input['model'], self::VALID_MODELS, true ) ) {
			$sanitized['model'] = $input['model'];
		} else {
			$sanitized['model'] = $existing['model'] ?? 'gpt-4-turbo-preview';
		}

		// Temperature - validate range.
		if ( isset( $input['temperature'] ) ) {
			$temperature              = floatval( $input['temperature'] );
			$sanitized['temperature'] = max( 0.1, min( 1.0, $temperature ) );
		} else {
			$sanitized['temperature'] = $existing['temperature'] ?? 0.7;
		}

		// Max Tokens - validate range.
		if ( isset( $input['max_tokens'] ) ) {
			$max_tokens              = intval( $input['max_tokens'] );
			$sanitized['max_tokens'] = max( 100, min( 4000, $max_tokens ) );
		} else {
			$sanitized['max_tokens'] = $existing['max_tokens'] ?? 1000;
		}

		// Auto-assignment checkbox.
		$sanitized['enable_auto_assignment'] = isset( $input['enable_auto_assignment'] ) && '1' === $input['enable_auto_assignment'];

		// Apify API Token - encrypt before storage (same as OpenAI key).
		if ( isset( $input['apify_api_token'] ) && ! empty( $input['apify_api_token'] ) ) {
			$apify_token = sanitize_text_field( $input['apify_api_token'] );

			// Check if it's the masked value (already encrypted).
			if ( str_starts_with( $apify_token, '***' ) ) {
				// Keep existing token.
				$sanitized['apify_api_token'] = $existing['apify_api_token'] ?? '';
			} elseif ( str_starts_with( $apify_token, 'apify_api_' ) ) {
				// New token - encrypt it.
				$encrypted = seo_generator_encrypt_api_key( $apify_token );
				if ( false !== $encrypted ) {
					$sanitized['apify_api_token'] = $encrypted;
				} else {
					add_settings_error(
						self::OPTION_NAME,
						'apify_encryption_failed',
						__( 'Failed to encrypt Apify API token. Please try again.', 'seo-generator' )
					);
					$sanitized['apify_api_token'] = $existing['apify_api_token'] ?? '';
				}
			} else {
				// Invalid token format.
				add_settings_error(
					self::OPTION_NAME,
					'invalid_apify_token',
					__( 'Invalid Apify API token format. Tokens start with "apify_api_".', 'seo-generator' )
				);
				$sanitized['apify_api_token'] = $existing['apify_api_token'] ?? '';
			}
		} else {
			// No token provided - keep existing.
			$sanitized['apify_api_token'] = $existing['apify_api_token'] ?? '';
		}

		// Place URL - validate URL format.
		if ( isset( $input['place_url'] ) && ! empty( $input['place_url'] ) ) {
			$place_url = sanitize_text_field( $input['place_url'] );

			// Validate it's a URL (either full Google Maps URL or Place ID).
			if ( filter_var( $place_url, FILTER_VALIDATE_URL ) || preg_match( '/^ChIJ[A-Za-z0-9_-]+$/', $place_url ) ) {
				$sanitized['place_url'] = $place_url;
			} else {
				add_settings_error(
					self::OPTION_NAME,
					'invalid_place_url',
					__( 'Invalid Place URL format. Enter a full Google Maps URL or Place ID.', 'seo-generator' )
				);
				$sanitized['place_url'] = $existing['place_url'] ?? '';
			}
		} else {
			$sanitized['place_url'] = $existing['place_url'] ?? '';
		}

		// Max Reviews - validate range.
		if ( isset( $input['max_reviews'] ) ) {
			$max_reviews              = intval( $input['max_reviews'] );
			$sanitized['max_reviews'] = max( 5, min( 100, $max_reviews ) );
		} else {
			$sanitized['max_reviews'] = $existing['max_reviews'] ?? 50;
		}

		// Preserve other existing settings (from other tabs/stories).
		foreach ( $existing as $key => $value ) {
			if ( ! isset( $sanitized[ $key ] ) ) {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize image settings before saving.
	 *
	 * @param array $input Raw input from form.
	 * @return array Sanitized settings.
	 */
	public function sanitizeImageSettings( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		// Default image ID - validate it's a valid attachment.
		if ( isset( $input['default_image_id'] ) && ! empty( $input['default_image_id'] ) ) {
			$image_id = intval( $input['default_image_id'] );

			// Verify the attachment exists and is an image.
			if ( $image_id > 0 && 'inherit' === get_post_status( $image_id ) ) {
				$mime_type = get_post_mime_type( $image_id );
				if ( $mime_type && str_starts_with( $mime_type, 'image/' ) ) {
					$sanitized['default_image_id'] = $image_id;
				} else {
					add_settings_error(
						self::IMAGE_OPTION_NAME,
						'invalid_image',
						__( 'Selected file is not a valid image.', 'seo-generator' )
					);
				}
			} else {
				add_settings_error(
					self::IMAGE_OPTION_NAME,
					'invalid_attachment',
					__( 'Selected image does not exist.', 'seo-generator' )
				);
			}
		} else {
			// No image selected or cleared.
			$sanitized['default_image_id'] = null;
		}

		// Auto-assignment checkbox.
		$sanitized['enable_auto_assignment'] = isset( $input['enable_auto_assignment'] ) && '1' === $input['enable_auto_assignment'];

		// Preserve folder structure checkbox.
		$sanitized['preserve_folder_structure'] = isset( $input['preserve_folder_structure'] ) && '1' === $input['preserve_folder_structure'];

		// Use folder as primary tag checkbox.
		$sanitized['use_folder_as_primary_tag'] = isset( $input['use_folder_as_primary_tag'] ) && '1' === $input['use_folder_as_primary_tag'];

		// AI alt text checkbox.
		$sanitized['use_ai_alt_text'] = isset( $input['use_ai_alt_text'] ) && '1' === $input['use_ai_alt_text'];

		// AI alt text model selection.
		$allowed_models = array( 'gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo-preview' );
		if ( isset( $input['ai_alt_text_model'] ) && in_array( $input['ai_alt_text_model'], $allowed_models, true ) ) {
			$sanitized['ai_alt_text_model'] = $input['ai_alt_text_model'];
		} else {
			$sanitized['ai_alt_text_model'] = 'gpt-4o-mini'; // Default.
		}

		// AI alt text fallback checkbox.
		$sanitized['ai_alt_text_fallback'] = isset( $input['ai_alt_text_fallback'] ) && '1' === $input['ai_alt_text_fallback'];

		// Image download timeout - validate range (10-120 seconds).
		if ( isset( $input['image_download_timeout'] ) ) {
			$timeout = intval( $input['image_download_timeout'] );
			$sanitized['image_download_timeout'] = max( 10, min( 120, $timeout ) );
		} else {
			$sanitized['image_download_timeout'] = 30; // Default.
		}

		// Max image size - validate range (1-50 MB).
		if ( isset( $input['max_image_size'] ) ) {
			$max_size_mb = intval( $input['max_image_size'] );
			$sanitized['max_image_size'] = max( 1, min( 50, $max_size_mb ) );
		} else {
			$sanitized['max_image_size'] = 5; // Default.
		}

		// Skip duplicate images checkbox.
		$sanitized['skip_duplicate_images'] = isset( $input['skip_duplicate_images'] ) && '1' === $input['skip_duplicate_images'];

		return $sanitized;
	}

	/**
	 * Handle Test Connection AJAX request.
	 *
	 * @return void
	 */
	public function testConnection(): void {
		check_ajax_referer( 'seo_generator_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Insufficient permissions.', 'seo-generator' ),
				),
				403
			);
		}

		$api_key = sanitize_text_field( $_POST['api_key'] ?? '' );

		if ( empty( $api_key ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'API key is required.', 'seo-generator' ),
				)
			);
		}

		// Validate API key format.
		if ( ! str_starts_with( $api_key, 'sk-' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid API key format. OpenAI API keys start with "sk-".', 'seo-generator' ),
				)
			);
		}

		try {
			// Create test request.
			$response = wp_remote_post(
				'https://api.openai.com/v1/chat/completions',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'model'      => 'gpt-3.5-turbo',
							'messages'   => array(
								array(
									'role'    => 'user',
									'content' => 'Say "Connection successful"',
								),
							),
							'max_tokens' => 50,
						)
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				error_log(
					sprintf(
						'[SEO Generator] Test connection failed: %s',
						$response->get_error_message()
					)
				);

				wp_send_json_error(
					array(
						'message' => sprintf(
							/* translators: %s: error message */
							__( 'Connection failed: %s', 'seo-generator' ),
							$response->get_error_message()
						),
					)
				);
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			$body        = wp_remote_retrieve_body( $response );
			$data        = json_decode( $body, true );

			if ( 401 === $status_code ) {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid API key. Please check your OpenAI API key.', 'seo-generator' ),
					)
				);
			}

			if ( $status_code >= 400 ) {
				$error_message = $data['error']['message'] ?? __( 'Unknown error', 'seo-generator' );

				error_log(
					sprintf(
						'[SEO Generator] Test connection API error: %d - %s',
						$status_code,
						$error_message
					)
				);

				wp_send_json_error(
					array(
						'message' => sprintf(
							/* translators: %s: error message */
							__( 'API error: %s', 'seo-generator' ),
							$error_message
						),
					)
				);
			}

			// Success.
			$model  = $data['model'] ?? 'unknown';
			$tokens = $data['usage']['total_tokens'] ?? 0;

			error_log(
				sprintf(
					'[SEO Generator] Test connection successful - Model: %s, Tokens: %d',
					$model,
					$tokens
				)
			);

			wp_send_json_success(
				array(
					'message' => __( 'Connection successful!', 'seo-generator' ),
					'details' => array(
						'model'  => $model,
						'tokens' => $tokens,
					),
				)
			);

		} catch ( \Exception $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Test connection exception: %s',
					$e->getMessage()
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Unexpected error: %s', 'seo-generator' ),
						$e->getMessage()
					),
				)
			);
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-generator' ) );
		}

		// Get active tab.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'api';

		// Include template.
		include SEO_GENERATOR_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * Display admin notices.
	 *
	 * @return void
	 */
	public function displayAdminNotices(): void {
		// Check if settings were just saved.
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			$screen = get_current_screen();
			if ( $screen && strpos( $screen->id, 'seo-generator-settings' ) !== false ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'seo-generator' ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Get the option name.
	 *
	 * @return string
	 */
	public static function getOptionName(): string {
		return self::OPTION_NAME;
	}

	/**
	 * Get the option group.
	 *
	 * @return string
	 */
	public static function getOptionGroup(): string {
		return self::OPTION_GROUP;
	}

	/**
	 * Get the image option group.
	 *
	 * @return string
	 */
	public static function getImageOptionGroup(): string {
		return self::IMAGE_OPTION_GROUP;
	}

	/**
	 * Get the page slug.
	 *
	 * @return string
	 */
	public static function getPageSlug(): string {
		return self::PAGE_SLUG;
	}
}
