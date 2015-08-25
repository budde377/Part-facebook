<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 4/17/15
 * Time: 4:39 PM
 */

namespace ChristianBudde\PartFacebook\controller\type_handler;


use ChristianBudde\PartFacebook\model\FacebookPage;
use ChristianBudde\Part\BackendSingletonContainer;
use ChristianBudde\Part\controller\ajax\type_handler\GenericObjectTypeHandlerImpl;
use ChristianBudde\Part\util\traits\TypeHandlerTrait;

class FacebookPageTypeHandlerImpl extends GenericObjectTypeHandlerImpl{

    use TypeHandlerTrait;

    function __construct(BackendSingletonContainer $container, FacebookPage $page)
    {
        parent::__construct($page);
            $this->whitelistFunction('FacebookPage',
                'getNumberOfLikes',
                'getStatus',
                'clearCache',
                'update');
            $this->addFunctionAuthFunction('FacebookPage', 'clearCache', $this->currentUserSitePrivilegesAuthFunction($container));
            $this->addFunctionAuthFunction('FacebookPage', 'update', $this->currentUserSitePrivilegesAuthFunction($container));
    }
}