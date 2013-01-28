<?php
/**
 * AvatarPlus Tests
 */

require_once PLUGIN_BASE_PATH . '/avatarplus.php';

// init autoloader
AvatarPlus\init_autoloader();

class AvatarPlusTest extends WP_UnitTestCase {

    public $object;

    public function set_up() {

        parent::setUp();

        //$this->object = new My_Plugin();

    }

    /**
     * Test get_aplus_avatar
     */
    public function testget_aplus_avatar() {

    	global $comment, $post;

    	//$avatar_string = AvatarPlus\get_aplus_avatar( '<img src="" />', 'johndoe@example.com' );

    	//$this->assertNotEmpty( $avatar_string );

    	$this->markTestIncomplete( 'Need mocking' );

    }

}