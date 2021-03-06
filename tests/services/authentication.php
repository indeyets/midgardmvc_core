<?php
/**
 * @package midgardmvc_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Tests for the authentication service
 *
 * @package midgardmvc_core
 */
class midgardmvc_core_tests_services_authentication extends midgardmvc_core_tests_testcase
{
    public function test_user_anonymous()
    {
        $this->assertFalse(midgardmvc_core::get_instance()->authentication->is_user());
        $this->assertEquals(null, midgardmvc_core::get_instance()->authentication->get_user());
        $this->assertEquals(null, midgardmvc_core::get_instance()->authentication->get_person());
    }

    public function test_login_failed()
    {
        $this->assertFalse(midgardmvc_core::get_instance()->authentication->login('admin', 'wrongpassword'));
        $this->assertFalse(midgardmvc_core::get_instance()->authentication->is_user());
        $this->assertEquals(null, midgardmvc_core::get_instance()->authentication->get_user());
        $this->assertEquals(null, midgardmvc_core::get_instance()->authentication->get_person());
    }

    public function test_login()
    {
        $this->assertTrue(midgardmvc_core::get_instance()->authentication->login('admin', 'password'));
        $this->assertTrue(midgardmvc_core::get_instance()->authentication->is_user());
    }
}
