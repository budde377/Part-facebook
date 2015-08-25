<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/14/15
 * Time: 4:03 PM
 */

namespace ChristianBudde\PartFacebook\util;


use ChristianBudde\PartFacebook\model\FacebookPage;
use ChristianBudde\PartFacebook\model\FacebookPageImpl;
use ChristianBudde\Part\BackendSingletonContainer;
use ChristianBudde\Part\util\script\Script;
use ChristianBudde\Part\Website;
use Facebook\FacebookSession;

class FacebookSessionInitializePreScriptImpl implements Script
{


    private $backendContainer;

    public function __construct(BackendSingletonContainer $backendContainer)
    {
        $this->backendContainer = $backendContainer;
    }

    /**
     * This function runs the script
     * @param $name string
     * @param $args array | null
     */
    public function run($name, $args)
    {
        if ($name != Website::WEBSITE_SCRIPT_TYPE_PRESCRIPT) {
            return;
        }

        $credentials = $this->backendContainer->getConfigInstance()->getFacebookAppCredentials();

        if (($id = $credentials['id']) == "" || ($secret = $credentials['secret']) == "" || !isset($this->backendContainer->getConfigInstance()['facebook_page_id'])) {
            return;
        }

        FacebookSession::setDefaultApplication($id, $secret);
        $this->backendContainer->facebookPage = function (BackendSingletonContainer $container) {
            return new FacebookPageImpl($container, $this->backendContainer->getConfigInstance()['facebook_page_id']);
        };
        $vars = $this->backendContainer->getSiteInstance()->getVariables();
        $k = 'FacebookSessionInitializePreScriptImpl_last_updated';
        if($vars->hasKey($k) && $vars->getValue($k) > time()-86400){
            return;
        }

        $vars->setValue($k, time());
        /** @var FacebookPage $fb */
        $fb = $this->backendContainer->facebookPage;
        if($fb->update()){
            $this->backendContainer->getSiteInstance()->modify();
        }


    }
}