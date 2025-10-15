# Epic 2: Core AI Generation System

**Timeline:** Weeks 3-4 (24-28 hours)
**Status:** Not Started
**Priority:** Critical
**Dependencies:** Epic 1 (Foundation Setup)

## Epic Goal

Implement the core AI content generation system including OpenAI API integration, prompt template engine, single and bulk block generation, comprehensive error handling, and cost tracking infrastructure.

## Success Criteria

- OpenAI API integration functional with GPT-4 Turbo
- Prompt template system supports variable substitution
- Single block generation works via REST API
- Bulk generation processes all 12 blocks sequentially
- Cost tracking logs all generation requests to database
- Error handling covers all failure scenarios (API errors, timeouts, rate limits)
- Generation completes in under 5 minutes for full page
- Cost per page averages under $3

---

## Story 2.1: Implement OpenAI API Integration

**As a** plugin developer
**I want** a service class that communicates with OpenAI API
**So that** content can be generated using GPT-4

### Acceptance Criteria

1. `OpenAIService` class created with method `generateContent(prompt, options)`
2. Service correctly formats requests to OpenAI Chat Completions API:
   - Uses `gpt-4-turbo-preview` model (with fallback to `gpt-4`)
   - Sends correct parameters: temperature, max_tokens, top_p, frequency_penalty, presence_penalty
   - Includes proper Authorization header with API key
3. Service parses API responses and extracts generated content
4. Timeout set to 60 seconds with proper error handling
5. API key retrieved from encrypted settings (encryption implemented)
6. Service returns structured response with: content, prompt_tokens, completion_tokens, model used
7. Unit tests cover success and error scenarios

### Technical Requirements

- Use WordPress HTTP API (`wp_remote_post()`)
- Implement in `includes/Services/OpenAIService.php`
- API endpoint: `https://api.openai.com/v1/chat/completions`
- Return type: `GenerationResult` object
- Encrypt API key using WordPress salts (OpenSSL AES-256-CBC)
- Handle JSON parsing errors gracefully
- Source: PRD "OpenAI Integration" section, Architecture "External APIs" section

---

## Story 2.2: Create Prompt Template Engine

**As a** plugin developer
**I want** a system to manage and render prompt templates with variable substitution
**So that** content generation prompts can be customized per block type

### Acceptance Criteria

1. `PromptTemplateEngine` class created with methods:
   - `renderPrompt(blockType, context)` - renders prompt with variable substitution
   - `getTemplate(blockType)` - retrieves template for block
   - `updateTemplate(blockType, template)` - saves custom template
   - `resetTemplate(blockType)` - restores default template
2. Default templates created for all 12 blocks following PRD example structure:
   - System message defining AI persona/tone
   - User message with requirements and context variables
3. Variable substitution supports: `{page_title}`, `{page_topic}`, `{focus_keyword}`, `{page_type}`
4. Templates stored in database (wp_options) with fallback to default templates
5. Templates cached for 1 hour to reduce database queries
6. Template validation ensures required variables are present

### Technical Requirements

- File: `includes/Services/PromptTemplateEngine.php`
- Default templates in: `includes/Data/DefaultPrompts.php`
- Use regex or str_replace for variable substitution
- Store templates in option: `seo_generator_prompt_templates`
- Implement WordPress object cache for template caching
- Source: PRD "Prompt Template System" section

---

## Story 2.3: Build Single Block Generation REST API Endpoint

**As a** content manager
**I want** to generate content for a single block via API
**So that** I can regenerate individual blocks without affecting others

### Acceptance Criteria

1. REST endpoint registered: `POST /wp-json/seo-generator/v1/pages/{id}/generate`
2. Endpoint accepts parameters:
   - `blockType` (required): which block to generate
   - `context` (optional): additional context data
3. Endpoint performs validation:
   - Post ID exists and is type `seo-page`
   - Block type is valid (one of 12 blocks)
   - User has `edit_posts` capability
   - Nonce verification passes
4. Generation workflow:
   - Build full context (page title, topic, focus keyword, page type)
   - Render prompt using PromptTemplateEngine
   - Call OpenAIService to generate content
   - Parse and validate generated content
   - Save content to ACF fields
   - Log generation to database
   - Return success response with generated content and metadata
5. Error handling returns appropriate HTTP status codes and messages
6. Response includes: generated content, tokens used, cost, generation time

### Technical Requirements

- Controller: `includes/Controllers/GenerationController.php`
- Service: `includes/Services/ContentGenerationService.php`
- Use `register_rest_route()` with permission callback
- Validate block type against whitelist
- Return `WP_REST_Response` or `WP_Error`
- Log errors to WordPress debug log
- Source: PRD "Generation Flow" and "Admin Interface" sections, Architecture "API Specification"

---

## Story 2.4: Implement Bulk Block Generation

**As a** content manager
**I want** to generate all 12 blocks at once
**So that** I can create complete pages quickly

### Acceptance Criteria

1. REST endpoint registered: `POST /wp-json/seo-generator/v1/pages/{id}/generate-all`
2. Endpoint processes all 12 blocks sequentially:
   - Loops through blocks in defined order
   - Generates each block using single block generation logic
   - Updates progress after each block
   - Continues on error (doesn't stop entire process)
3. Returns progress updates during generation:
   - Current block being processed
   - Completion percentage
   - Time elapsed
   - Estimated time remaining
4. Final response includes:
   - Total blocks generated successfully
   - List of failed blocks (if any)
   - Total cost
   - Total tokens used
   - Total generation time
5. Rate limiting enforced (max 3 concurrent bulk generations)
6. Bulk generation completes in under 5 minutes

### Technical Requirements

- Method: `ContentGenerationService::generateAllBlocks(postId)`
- Sequential processing (not parallel) to avoid rate limits
- Use WordPress transients for rate limiting
- Calculate time remaining based on average block generation time
- Handle OpenAI rate limits (429 errors) with backoff
- Source: PRD "Bulk Generation" section

---

## Story 2.5: Implement Cost Tracking and Logging

**As a** site administrator
**I want** all generation requests logged with cost data
**So that** I can monitor spending and optimize usage

### Acceptance Criteria

1. `CostTrackingService` class created with methods:
   - `logGeneration(logData)` - saves log entry to database
   - `getCurrentMonthCost()` - calculates current month spend
   - `checkBudgetLimit()` - validates against monthly budget
2. Every generation request logged to `wp_seo_generation_log` table with:
   - post_id, block_type, prompt_tokens, completion_tokens, total_tokens
   - cost (calculated), model, status, error_message (if failed)
   - user_id, created_at
3. Cost calculation accurate for GPT-4 Turbo pricing:
   - Prompt tokens: $0.01 per 1K tokens
   - Completion tokens: $0.03 per 1K tokens
4. Budget limit checking:
   - Retrieve monthly budget from settings
   - Calculate current month spend
   - Block generation if over budget
   - Send email alert at 80% threshold
5. Old logs (30+ days) cleaned up via daily WP Cron job

### Technical Requirements

- File: `includes/Services/CostTrackingService.php`
- Repository: `includes/Repositories/GenerationLogRepository.php`
- Use `wpdb->insert()` for logging
- Index on `created_at` and `cost` for analytics queries
- WP Cron hook: `seo_generator_cleanup_old_logs`
- Email alerts use `wp_mail()`
- Source: PRD "Cost Tracking" and "Database Schema" sections

---

## Story 2.6: Implement Comprehensive Error Handling

**As a** content manager
**I want** clear error messages when generation fails
**So that** I understand what went wrong and how to fix it

### Acceptance Criteria

1. All error scenarios handled with user-friendly messages:
   - **API key missing:** "OpenAI API key not configured. Please add your API key in Settings."
   - **Rate limit (429):** "OpenAI rate limit reached. Retrying in 60 seconds..."
   - **Timeout:** "Generation timed out. Please try again."
   - **Invalid response:** "Failed to parse AI response. Please try regenerating."
   - **Network error:** "Unable to connect to OpenAI. Check your internet connection."
   - **Budget exceeded:** "Monthly budget limit reached ($X of $Y). Please increase limit in Settings."
2. Errors logged to WordPress debug.log with full context
3. Failed generations marked in database with error_message
4. Retry logic implemented for transient errors (timeout, network)
5. User shown appropriate action to resolve error
6. 95%+ success rate maintained (per PRD success metrics)

### Technical Requirements

- Create exception classes: `RateLimitException`, `BudgetExceededException`, `OpenAIException`
- Error messages translatable (use `__()` function)
- Log format: `[SEO Generator] {context}: {error message}`
- Implement exponential backoff for rate limit retries
- Return `WP_Error` objects with appropriate error codes
- Source: PRD "Error Handling" section

---

## Story 2.7: Create Settings for API Configuration

**As a** site administrator
**I want** to configure OpenAI API settings
**So that** the plugin can connect to my OpenAI account

### Acceptance Criteria

1. Settings page "API Configuration" tab functional with fields:
   - OpenAI API Key (password field, encrypted storage)
   - Model selection (dropdown: gpt-4-turbo-preview, gpt-4, gpt-3.5-turbo)
   - Temperature (slider: 0.1-1.0, default 0.7)
   - Max Tokens (number input, default 1000)
   - Test Connection button
2. API key encrypted before storage using WordPress salts (AES-256-CBC)
3. "Test Connection" button validates API key:
   - Sends test request to OpenAI
   - Shows success message with account info
   - Shows error message if invalid
4. Settings saved to `wp_options` table
5. Settings properly sanitized and validated on save
6. Encrypted API key never exposed in frontend JavaScript

### Technical Requirements

- Settings option name: `seo_generator_api_settings`
- Encryption functions: `encrypt_api_key()`, `decrypt_api_key()` in `includes/functions.php`
- Test connection AJAX endpoint: `seo_generator_test_api_connection`
- Use `sanitize_text_field()` for all text inputs
- Validate model selection against whitelist
- Source: PRD "Settings Page" and "Security" sections

---

## Epic Dependencies

- Epic 1: Foundation Setup (required for post types, ACF fields, admin menu)

## Risks & Mitigations

**Risk:** OpenAI API rate limits during bulk generation
**Mitigation:** Implement retry logic with exponential backoff, sequential processing

**Risk:** Cost overruns exceeding user budget
**Mitigation:** Budget limit enforcement, email alerts at 80% threshold

**Risk:** Generation quality inconsistent
**Mitigation:** Comprehensive prompt engineering, temperature tuning, content validation

**Risk:** Timeout on slower hosting environments
**Mitigation:** Increase PHP max_execution_time, optimize prompts to reduce token usage
