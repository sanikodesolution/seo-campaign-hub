<?php
/**
 * Standalone runner when PHPUnit is not installed.
 *
 * @package SEO_Campaign_Hub
 */

define( 'ABSPATH', sys_get_temp_dir() . '/sch-wp/' );
require dirname( __DIR__ ) . '/src/Services/SeoMetaResolver.php';

use SEO_Campaign_Hub\Services\SeoMetaResolver;

$resolver = new SeoMetaResolver();
$failed   = 0;

function sch_assert( $ok, $label ) {
	global $failed;
	if ( $ok ) {
		echo "OK   $label\n";
		return;
	}
	++$failed;
	echo "FAIL $label\n";
}

$custom = $resolver->resolve(
	[
		'context'              => 'front_posts',
		'homepage_title'       => 'Home SEO Title',
		'homepage_description' => 'Home SEO description.',
		'site_name'            => 'My Shop',
		'tagline'              => 'Best deals',
	]
);
sch_assert( $custom['title'] === 'Home SEO Title', 'front_posts custom title' );
sch_assert( $custom['description'] === 'Home SEO description.', 'front_posts custom description' );
sch_assert( $custom['title_is_custom'] === true, 'front_posts custom flag' );

$fallback = $resolver->resolve(
	[
		'context'              => 'front_posts',
		'homepage_title'       => '',
		'homepage_description' => '',
		'site_name'            => 'My Shop',
		'tagline'              => 'Best deals',
	]
);
sch_assert( $fallback['title'] === 'My Shop', 'front_posts fallback title' );
sch_assert( $fallback['description'] === 'Best deals', 'front_posts fallback description' );
sch_assert( $fallback['title_is_custom'] === false, 'front_posts fallback flag' );

$page_wins = $resolver->resolve(
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
sch_assert( $page_wins['title'] === 'Page SEO Title', 'static front page meta wins title' );
sch_assert( $page_wins['description'] === 'Page SEO description.', 'static front page meta wins description' );
sch_assert( $page_wins['title_is_custom'] === true, 'static front page meta custom flag' );

$page_home = $resolver->resolve(
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
sch_assert( $page_home['title'] === 'Home SEO Title', 'static front empty meta uses homepage title' );
sch_assert( $page_home['description'] === 'Home SEO description.', 'static front empty meta uses homepage description' );
sch_assert( $page_home['title_is_custom'] === true, 'static front homepage settings are custom' );

$singular_custom = $resolver->resolve(
	[
		'context'            => 'singular',
		'custom_title'       => 'Post SEO Title',
		'custom_description' => 'Post SEO description.',
		'post_title'         => 'Hello World',
		'excerpt'            => 'Hello excerpt',
	]
);
sch_assert( $singular_custom['title'] === 'Post SEO Title', 'singular custom title' );
sch_assert( $singular_custom['description'] === 'Post SEO description.', 'singular custom description' );
sch_assert( $singular_custom['title_is_custom'] === true, 'singular custom flag' );

$singular_fallback = $resolver->resolve(
	[
		'context'            => 'singular',
		'custom_title'       => '',
		'custom_description' => '',
		'post_title'         => 'Hello World',
		'excerpt'            => 'Hello excerpt',
	]
);
sch_assert( $singular_fallback['title'] === 'Hello World', 'singular fallback title' );
sch_assert( $singular_fallback['description'] === 'Hello excerpt', 'singular fallback excerpt' );
sch_assert( $singular_fallback['title_is_custom'] === false, 'singular fallback flag' );

$ignore_home = $resolver->resolve(
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
sch_assert( $ignore_home['title'] === 'Inner Post', 'singular ignores homepage title' );
sch_assert( str_contains( $ignore_home['description'], 'Body copy' ), 'singular uses content fallback' );
sch_assert( ! str_contains( $ignore_home['title'], 'Home SEO' ), 'singular title not homepage' );
sch_assert( ! str_contains( $ignore_home['description'], 'Home SEO' ), 'singular description not homepage' );
sch_assert( $ignore_home['title_is_custom'] === false, 'singular ignore-home flag' );

$empty_desc = $resolver->resolve(
	[
		'context'            => 'singular',
		'custom_title'       => '',
		'custom_description' => '',
		'post_title'         => 'No Desc',
		'excerpt'            => '',
		'content'            => '',
	]
);
sch_assert( $empty_desc['description'] === '', 'empty description stays empty' );

echo $failed === 0 ? "\nAll assertions passed.\n" : "\n$failed assertion(s) failed.\n";
exit( $failed === 0 ? 0 : 1 );
