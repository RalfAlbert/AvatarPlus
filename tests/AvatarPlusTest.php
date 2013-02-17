<?php
namespace AvatarPlus\AvatarPlusTest;
use AvatarPlus as AvatarPlus;

require_once PLUGIN_BASE_PATH . '/avatarplus.php';

// init autoloader
\AvatarPlus\init_autoloader();

class AvatarPlusTest extends \WP_UnitTestCase {

    public $object;

    public function setup() {

        parent::setUp();

    }

    public function GetAvatar_DataProvider() {

    	$comment = array(
    		'comment_ID' => '1',
    		'comment_post_ID' => '1',
    		'comment_author' => 'Mr WordPress',
    		'comment_author_email' => '',
    		'comment_author_url' => 'http://wordpress.org/',
    	);

    	return array(
    		array(
    			$comment,
    			'/gravatar/i'
    		),

    		array(
    			array_merge( $comment, array( 'comment_author_url' => 'https://plus.google.com/116844107236111670286' ) ),
    			'/googleusercontent/i'
    		),

    		array(
    			array_merge( $comment, array( 'comment_author_url' => 'http://neun12.de/+' ) ),
    			'/googleusercontent/i'
    		),

    		array(
    			array_merge( $comment, array( 'comment_author_url' => 'https://www.facebook.com/OoroonBai' ) ),
    			'/facebook/i'
    		),

    		array(
    			array_merge( $comment, array( 'comment_author_url' => 'http://neun12.de/fb' ) ),
    			'/facebook/i'
    		),

    		array(
    			array_merge( $comment, array( 'comment_author_url' => 'https://twitter.com/OoroonBai' ) ),
    			'/twitter/i'
    		),

    	);
    }

    /**
     * @dataProvider getAvatar_DataProvider
     */
    public function testGetAvatar( $testcomment, $expectedUrl ) {

    	global $comment, $post;

    	$post = new \stdClass();
    	$post->ID = 1;

    	$comment = $testcomment;

    	$avatar_string = get_avatar( '' );

    	$this->assertNotEmpty( $avatar_string );
    	$this->assertRegExp( $expectedUrl, $avatar_string );

    }

}