<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/17/15
 * Time: 6:05 PM
 */

namespace ChristianBudde\PartFacebook\controller\json;


use ChristianBudde\PartFacebook\model\FacebookPage;
use ChristianBudde\Part\controller\json\ObjectImpl;

class FacebookPageJSONObjectImpl extends ObjectImpl{


    function __construct(FacebookPage $page)
    {
        parent::__construct('facebook_page');
        $this->setVariable('id', $page->getId());
        $this->setVariable('number_of_likes', $page->getNumberOfLikes());
        $this->setVariable('first_status', $page->getStatus());

    }
}