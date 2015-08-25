<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/14/15
 * Time: 5:05 PM
 */

namespace ChristianBudde\PartFacebook\model;


use ChristianBudde\Part\controller\json\JSONObjectSerializable;

interface FacebookStatus extends JSONObjectSerializable{

    // {id}/?fields=likes,message,id,updated_time

    /**
     * @return string The status id.
     */
    public function getId();

    /**
     * @return string[] An array of absolute image urls.
     */
    public function getImages();

    /**
     * @return String Status update message
     */
    public function getMessage();

    /**
     * @return int Number of likes
     */
    public function getNumberOfLikes();

    /**
     * @return int unix timestamp in seconds since epoch
     */
    public function getUpdatedTime();
    /**
     * @return int unix timestamp in seconds since epoch
     */
    public function getCreatedTime();

    /**
     * @return FacebookStatus
     */
    public function getNextStatus();

    /**
     * @return FacebookStatus
     */
    public function getPreviousStatus();

    /**
     * @return string
     */
    public function getLink();


    /**
     * Updates the cache
     * @return bool TRUE if something changed else FALSE
     */
    public function update();

    /**
     * Clears cache
     * @return void
     */
    public function clearCache();

}