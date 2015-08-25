<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/17/15
 * Time: 6:00 PM
 */

namespace ChristianBudde\PartFacebook\controller\json;


use ChristianBudde\PartFacebook\model\FacebookStatus;
use ChristianBudde\Part\controller\json\ObjectImpl;

class FacebookStatusJSONObjectImpl extends ObjectImpl{


    function __construct(FacebookStatus $status)
    {
        parent::__construct('facebook_status');
        $this->setVariable('id', $status->getId());
        $this->setVariable('created_time', $status->getCreatedTime());
        $this->setVariable('updated_time', $status->getUpdatedTime());
        $this->setVariable('images', array_map(function($element){
            return get_object_vars($element);
        }, $status->getImages()));
        $this->setVariable('link', $status->getLink());
        $this->setVariable('message', $status->getMessage());
        $this->setVariable('number_of_likes', $status->getNumberOfLikes());
        $next_status = $status->getNextStatus();
        $this->setVariable('next_id', $next_status == null?null:$next_status->getId());
        $prev_status = $status->getPreviousStatus();
        $this->setVariable('prev_id', $prev_status == null?null:$prev_status->getId());

    }
}