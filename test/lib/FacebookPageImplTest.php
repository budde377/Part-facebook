<?php
namespace ChristianBudde\PartFacebook\test;

use ChristianBudde\PartFacebook\model\FacebookPageImpl;
use ChristianBudde\PartFacebook\util\FacebookSessionInitializePreScriptImpl;
use ChristianBudde\Part\test\stub\StubBackendSingletonContainerImpl;
use ChristianBudde\Part\test\stub\StubConfigImpl;
use ChristianBudde\Part\test\stub\StubSiteImpl;
use ChristianBudde\Part\test\stub\StubVariablesImpl;
use ChristianBudde\Part\util\file\Folder;
use ChristianBudde\Part\util\file\FolderImpl;
use ChristianBudde\Part\Website;
use PHPUnit_Framework_TestCase;

/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/14/15
 * Time: 6:40 PM
 */
class FacebookPageImplTest extends PHPUnit_Framework_TestCase
{

    /** @var  FacebookPageImpl */
    private $page;

    public function setup()
    {
        $backend = new StubBackendSingletonContainerImpl();
        $site = new StubSiteImpl();
        $site->setVariables(new StubVariablesImpl());
        $backend->setSiteInstance($site);

        $config = new StubConfigImpl();
        $config->setFacebookAppCredentials(['id' => $GLOBALS['FB_APP_ID'], 'secret' => $GLOBALS['FB_APP_SECRET'], 'permanent_access_token' => $GLOBALS['FB_ACCESS_TOKEN']]);
        $config->setVariables(['facebook_page_id' => $GLOBALS['FB_PAGE_ID']]);
        $backend->setConfigInstance($config);
        $backend->setTmpFolder($f = new FolderImpl("/tmp/lenevemb.dk-test/"));
        $f->delete(Folder::DELETE_FOLDER_RECURSIVE);
        $script = new FacebookSessionInitializePreScriptImpl($backend);
        $script->run(Website::WEBSITE_SCRIPT_TYPE_PRESCRIPT, null);
        $this->page = new FacebookPageImpl($backend, $GLOBALS['FB_PAGE_ID']);

    }

    public function testCount()
    {
        $n = $this->page->getNumberOfLikes();
        $this->assertGreaterThan(0, $n);

    }

    protected function tearDown()
    {
        parent::tearDown();
        serialize($this->page);
    }

    public function testGetStatusWithoutId()
    {
        $n = $this->page->getStatus();
        $this->assertInstanceOf('ChristianBudde\lenevemb_dk\model\FacebookStatusImpl', $n);

    }

    public function testGetStatusReturnsSameInstance()
    {
        $this->assertTrue($this->page->getStatus() === $this->page->getStatus());

    }
}