<?php
namespace AvatarPlus\FrontendTest;
use AvatarPlus as AvatarPlus;
use AvatarPlus\Cache\Cache as Cache;
use AvatarPlus\Backend\Backend as Backend;

require_once PLUGIN_BASE_PATH . '/avatarplus.php';
require_once PLUGIN_BASE_PATH . '/classes/class-cache.php';

// init autoloader
\AvatarPlus\init_autoloader();

class BasicTest extends \WP_UnitTestCase {

	protected $post_id = 1;
	protected $cache = null;

	public function setup() {

		parent::setUp();

		$this->cache = new Cache( $this->post_id );

/* not needed as long as used a 'real' blog */
// 		$this->insert_comments();

// 		// default options
// 		$options = array(
// 				'metakey'                  => 'avatarplus_profile_url',
// 				'cachingkey'               => 'avatarplus_caching',
// 				'use_extra_field'          => false,
// 				'cache_expiration_value'   => 0,
// 				'cache_expiration_periode' => 'days',
// 		);

// 		add_option( Backend::OPTION_KEY, $options );

	}

	public function teardown() {}

	protected function insert_comments() {

		$time = current_time( 'mysql' );

		$comment_base = array(
				'comment_post_ID' => $this->post_id,
				'comment_author' => 'TestHansel',
				'comment_author_email' => 'test@hansel.tld',
				'comment_author_url' => '',
				'comment_content' => '',
				'comment_type' => 'comment',
				'comment_parent' => 0,
				'user_id' => 1,
				'comment_author_IP' => '127.0.0.1',
				'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
				'comment_date' => $time,
				'comment_approved' => 1,
		);

		$comment_data = array(
			array(
				'comment_author_email' => 'neun12@gmail.com',
				'comment_author_url'   => 'http://neun12.de',
				'comment_content'      => 'Plain, Gravatar',
			),

			array(
				'comment_author_url'   => 'https://plus.google.com/116844107236111670286',
				'comment_content'      => 'GooglePlus direct',
			),

			array(
				'comment_author_url'   => 'http://neun12.de/+',
				'comment_content'      => 'GooglePlus with redirect',
			),

			array(
				'comment_author_url'   => 'https://www.facebook.com/OoroonBai',
				'comment_content'      => 'Facebook direct',
			),

			array(
				'comment_author_url'   => 'http://neun12.de/fb',
				'comment_content'      => 'Facebook with redirect',
			),

			array(
				'comment_author_url'   => 'https://www.twitter.com/OoroonBai',
				'comment_content'      => 'Twitter direct',
			),

		);

		foreach( $comment_data as $c_data ) {
			$id = wp_insert_comment( array_merge( $comment_base, $c_data ) );
		}

	}


	public function testIsPluginActive() {
		$plugins = get_option('active_plugins');
		$this->assertTrue( in_array( 'avatar-plus/avatarplus.php', $plugins ) );
	}

	public function testCommentsExists() {
		$num_comments = wp_count_comments( $this->post_id );
		$this->assertEquals( 7, $num_comments->approved );
	}

	public function testAccessBackend() {
		$cachekey = Backend::get_option( 'cachingkey' );
		$this->assertNotEmpty( $cachekey );
	}

	public function testCacheExists() {
		$cache = $this->cache->read_cache( $this->post_id );
		$this->assertNotEmpty( $cache );
	}

	public function testGetCommentHTML() {
		ob_start();
		$this->go_to( get_permalink( $this->post_id ) );
		var_dump( ob_get_clean() );
	}
}