<?php
/**
 * SeoMetaResolver unit tests.
 *
 * @package SEO_Campaign_Hub
 */

namespace SEO_Campaign_Hub\Tests\Services;

use PHPUnit\Framework\TestCase;
use SEO_Campaign_Hub\Services\SeoMetaResolver;

/**
 * @covers \SEO_Campaign_Hub\Services\SeoMetaResolver
 */
class SeoMetaResolverTest extends TestCase {

	private SeoMetaResolver $resolver;

	protected function setUp(): void {
		$this->resolver = new SeoMetaResolver();
	}

	public function test_front_posts_uses_homepage_fields_then_site_name_tagline(): void {
		$custom = $this->resolver->resolve(
			[
				'context'               => 'front_posts',
				'homepage_title'        => 'Home SEO Title',
				'homepage_description'  => 'Home SEO description.',
				'site_name'             => 'My Shop',
				'tagline'               => 'Best deals',
			]
		);

		$this->assertSame( 'Home SEO Title', $custom['title'] );
		$this->assertSame( 'Home SEO description.', $custom['description'] );
		$this->assertTrue( $custom['title_is_custom'] );

		$fallback = $this->resolver->resolve(
			[
				'context'              => 'front_posts',
				'homepage_title'       => '',
				'homepage_description' => '',
				'site_name'            => 'My Shop',
				'tagline'              => 'Best deals',
			]
		);

		$this->assertSame( 'My Shop', $fallback['title'] );
		$this->assertSame( 'Best deals', $fallback['description'] );
		$this->assertFalse( $fallback['title_is_custom'] );
	}

	public function test_static_front_page_meta_wins_over_homepage_settings(): void {
		$resolved = $this->resolver->resolve(
			[
				'context'              => 'front_page',
				'custom_title'         => 'Page SEO Title',
				'custom_description'   => 'Page SEO description.',
				'homepage_title'       => 'Home SEO Title',
				'homepage_description' => 'Home SEO description.',
				'post_title'           => 'Welcome',
				'excerpt'              => 'Welcome excerpt',
			]
		);

		$this->assertSame( 'Page SEO Title', $resolved['title'] );
		$this->assertSame( 'Page SEO description.', $resolved['description'] );
		$this->assertTrue( $resolved['title_is_custom'] );
	}

	public function test_static_front_page_empty_meta_uses_homepage_settings(): void {
		$resolved = $this->resolver->resolve(
			[
				'context'              => 'front_page',
				'custom_title'         => '',
				'custom_description'   => '',
				'homepage_title'       => 'Home SEO Title',
				'homepage_description' => 'Home SEO description.',
				'post_title'           => 'Welcome',
				'excerpt'              => 'Welcome excerpt',
			]
		);

		$this->assertSame( 'Home SEO Title', $resolved['title'] );
		$this->assertSame( 'Home SEO description.', $resolved['description'] );
		$this->assertTrue( $resolved['title_is_custom'] );
	}

	public function test_singular_custom_meta_wins_empty_uses_post_title_excerpt(): void {
		$custom = $this->resolver->resolve(
			[
				'context'            => 'singular',
				'custom_title'       => 'Post SEO Title',
				'custom_description' => 'Post SEO description.',
				'post_title'         => 'Hello World',
				'excerpt'            => 'Hello excerpt',
			]
		);

		$this->assertSame( 'Post SEO Title', $custom['title'] );
		$this->assertSame( 'Post SEO description.', $custom['description'] );
		$this->assertTrue( $custom['title_is_custom'] );

		$fallback = $this->resolver->resolve(
			[
				'context'            => 'singular',
				'custom_title'       => '',
				'custom_description' => '',
				'post_title'         => 'Hello World',
				'excerpt'            => 'Hello excerpt',
			]
		);

		$this->assertSame( 'Hello World', $fallback['title'] );
		$this->assertSame( 'Hello excerpt', $fallback['description'] );
		$this->assertFalse( $fallback['title_is_custom'] );
	}

	public function test_singular_never_uses_homepage_fields(): void {
		$resolved = $this->resolver->resolve(
			[
				'context'              => 'singular',
				'custom_title'         => '',
				'custom_description'   => '',
				'homepage_title'       => 'Home SEO Title',
				'homepage_description' => 'Home SEO description.',
				'post_title'           => 'Inner Post',
				'excerpt'              => '',
				'content'              => 'Body copy for the inner post used as description fallback.',
			]
		);

		$this->assertSame( 'Inner Post', $resolved['title'] );
		$this->assertStringContainsString( 'Body copy', $resolved['description'] );
		$this->assertStringNotContainsString( 'Home SEO', $resolved['title'] );
		$this->assertStringNotContainsString( 'Home SEO', $resolved['description'] );
		$this->assertFalse( $resolved['title_is_custom'] );
	}

	public function test_empty_description_yields_empty_string(): void {
		$resolved = $this->resolver->resolve(
			[
				'context'              => 'singular',
				'custom_title'         => '',
				'custom_description'   => '',
				'post_title'           => 'No Desc',
				'excerpt'              => '',
				'content'              => '',
			]
		);

		$this->assertSame( '', $resolved['description'] );
	}
}
