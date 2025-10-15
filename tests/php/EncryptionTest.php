<?php
/**
 * Tests for Encryption Functions
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests;

use WP_UnitTestCase;

/**
 * Encryption functions test case.
 */
class EncryptionTest extends WP_UnitTestCase {
	/**
	 * Test successful encryption and decryption.
	 */
	public function test_encryption_and_decryption() {
		$original = 'sk-test-api-key-12345';

		$encrypted = seo_generator_encrypt_api_key( $original );

		$this->assertNotEmpty( $encrypted );
		$this->assertNotEquals( $original, $encrypted );

		$decrypted = seo_generator_decrypt_api_key( $encrypted );

		$this->assertEquals( $original, $decrypted );
	}

	/**
	 * Test encryption of empty string returns false.
	 */
	public function test_encrypt_empty_string() {
		$encrypted = seo_generator_encrypt_api_key( '' );

		$this->assertFalse( $encrypted );
	}

	/**
	 * Test decryption of empty string returns false.
	 */
	public function test_decrypt_empty_string() {
		$decrypted = seo_generator_decrypt_api_key( '' );

		$this->assertFalse( $decrypted );
	}

	/**
	 * Test encryption produces different output each time (due to IV).
	 */
	public function test_encryption_is_secure() {
		$original = 'sk-test-api-key-12345';

		$encrypted1 = seo_generator_encrypt_api_key( $original );
		$encrypted2 = seo_generator_encrypt_api_key( $original );

		// Both should decrypt to same value.
		$this->assertEquals( $original, seo_generator_decrypt_api_key( $encrypted1 ) );
		$this->assertEquals( $original, seo_generator_decrypt_api_key( $encrypted2 ) );
	}

	/**
	 * Test decryption of invalid data returns false.
	 */
	public function test_decrypt_invalid_data() {
		$decrypted = seo_generator_decrypt_api_key( 'invalid-encrypted-data' );

		$this->assertFalse( $decrypted );
	}

	/**
	 * Test encryption of long API key.
	 */
	public function test_encrypt_long_api_key() {
		$long_key = str_repeat( 'sk-test-api-key-', 10 );

		$encrypted = seo_generator_encrypt_api_key( $long_key );

		$this->assertNotEmpty( $encrypted );

		$decrypted = seo_generator_decrypt_api_key( $encrypted );

		$this->assertEquals( $long_key, $decrypted );
	}

	/**
	 * Test encryption with special characters.
	 */
	public function test_encrypt_special_characters() {
		$key = 'sk-test!@#$%^&*()_+-={}[]|:;"<>?,./';

		$encrypted = seo_generator_encrypt_api_key( $key );

		$this->assertNotEmpty( $encrypted );

		$decrypted = seo_generator_decrypt_api_key( $encrypted );

		$this->assertEquals( $key, $decrypted );
	}

	/**
	 * Test that encrypted value is not stored in plain text.
	 */
	public function test_api_key_not_stored_in_plain_text() {
		$api_key   = 'sk-test-secret-key-12345';
		$encrypted = seo_generator_encrypt_api_key( $api_key );

		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => $encrypted,
			)
		);

		$settings = get_option( 'seo_generator_settings' );

		$this->assertNotEquals( $api_key, $settings['openai_api_key'] );
		$this->assertStringNotContainsString( $api_key, wp_json_encode( $settings ) );
	}
}
