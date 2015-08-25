<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/14/15
 * Time: 5:16 PM
 */

namespace ChristianBudde\PartFacebook\model;


use ChristianBudde\PartFacebook\controller\json\FacebookPageJSONObjectImpl;
use ChristianBudde\PartFacebook\controller\type_handler\FacebookPageTypeHandlerImpl;
use ChristianBudde\Part\BackendSingletonContainer;

use ChristianBudde\Part\controller\ajax\type_handler\TypeHandler;
use ChristianBudde\Part\util\file\File;
use ChristianBudde\Part\util\file\FileImpl;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;

class FacebookPageImpl implements FacebookPage
{
    private $session;
    private $container;
    private $site;
    private $page_id;
    private $statuses = [];
    private $id_list = [];
    private $id_list_cache_path = '/FacebookPageImpl/id_list';
    private $status_objects;
    private $typeHandler;

    function __construct(BackendSingletonContainer $container, $page_id)
    {
        $this->page_id = $page_id;
        $this->container = $container;
        $this->site = $container->getSiteInstance();
        $this->session = new FacebookSession($container->getConfigInstance()->getFacebookAppCredentials()['permanent_access_token']);

    }


    private function getRemoteNumberOfLikes()
    {
        $request = new FacebookRequest($this->session, "GET", "/{$this->page_id}/?fields=likes");
        $result = $request->execute()->getResponse();

        return empty($result->likes) ? -1 : $result->likes;
    }

    /**
     * @return int Number of likes
     */
    public function getNumberOfLikes()
    {
        $vars = $this->site->getVariables();
        if ($v = $vars->hasKey('facebook_number_of_likes') != null) {
            return $vars->getValue('facebook_number_of_likes');
        }
        $likes = $this->getRemoteNumberOfLikes();
        $vars->setValue('facebook_number_of_likes', $likes);
        return $likes;
    }

    /**
     * @param string $id If empty will return the first status.
     * @return FacebookStatus
     */
    public function getStatus($id = "")
    {

        $this->initializeIdList();

        if (empty($this->id_list)) {
            return null;
        }

        if (empty($id)) {
            $id = $this->id_list[0];
            $index = 0;
        } else if (($index = array_search($id, $this->id_list)) === false) {
            return null;
        }


        return isset($this->statuses[$id]) ? $this->statuses[$id] : $this->statuses[$id] =
            new FacebookStatusImpl(
                $this->container,
                $this->session,
                $this,
                $id,
                isset($this->id_list[$index - 1]) ? $this->id_list[$index - 1] : "",
                isset($this->id_list[$index + 1]) ? $this->id_list[$index + 1] : "",
                isset($this->status_objects[$id]) ? $this->status_objects[$id] : null);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->page_id;
    }

    private function initializeIdList($list = null)
    {
        if (!empty($this->id_list) && $list == null) {
            return true;
        }
        $tmpFolder = $this->container->getTmpFolderInstance();
        if ($tmpFolder == null) {
            $this->id_list = $list == null ? $this->getRemoteIdList() : $list;
            return $list == null;
        }
        $f = new FileImpl($tmpFolder->getAbsolutePath() . $this->id_list_cache_path);
        $f->setAccessMode(File::FILE_MODE_RW_TRUNCATE_FILE_TO_ZERO_LENGTH);
        if ($list == null && $f->exists()) {
            $this->id_list = unserialize($f->getContents());
            return true;
        }

        $this->id_list = $list == null ? $this->getRemoteIdList() : $list;

        $f->getParentFolder()->create(true);
        $f->write(serialize($this->id_list));

        return $list == null;
    }


    private function getRemoteIdList()
    {
        $result = (new FacebookRequest($this->session, 'GET', "/{$this->page_id}/feed?fields=message,attachments{type,url,media,subattachments},likes.summary(1),updated_time,created_time,link"))->execute()->getResponse();

        if (empty($result->data)) {
            return [];
        }
        $resultData = array_filter($result->data, function (\stdClass $o) {
            return isset($o->message, $o->attachments);

        });

        $l = [];
        foreach ($resultData as $d) {
            $this->status_objects[$l[] = $d->id] = $d;
        }
        return $l;
    }

    /**
     * Clears all cache
     * @return void
     */
    public function clearCache()
    {
        $this->container->getSiteInstance()->getVariables()->removeKey('facebook_number_of_likes');
        $status = $this->getStatus();
        while ($status != null) {
            if ($status->update()) {
                $status->clearCache();
            }
            $status = $status->getNextStatus();
        }
        $tmpFolder = $this->container->getTmpFolderInstance();
        if ($tmpFolder == null) {
            return;
        }
        $f = new FileImpl($tmpFolder->getAbsolutePath() . $this->id_list_cache_path);
        $f->delete();
    }

    /**
     * Indicates whether something has changed
     * @return bool
     */
    public function update()
    {
        $r = false;
        $vars = $this->container->getSiteInstance()->getVariables();
        if (!$vars->hasKey('facebook_number_of_likes')) {
            $r = true;
        }
        if ($vars->getValue('facebook_number_of_likes') != ($n = $this->getRemoteNumberOfLikes())) {
            $r = true;
            $vars->setValue('facebook_number_of_likes', $n);
        }
        if ($this->initializeIdList() && ($l = $this->getRemoteIdList()) != $this->id_list) {
            $r = true;
            $this->initializeIdList($l);
        }

        $status = $this->getStatus();
        while ($status != null) {
            if ($status->update()) {
                $r = true;
            }

            $status = $status->getNextStatus();
        }

        return $r;
    }


    /**
     * Serializes the object to an instance of JSONObject.
     * @return Object
     */
    public function jsonObjectSerialize()
    {
        return new FacebookPageJSONObjectImpl($this);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize()
    {
        return $this->jsonObjectSerialize()->jsonSerialize();
    }

    /**
     * @return TypeHandler
     */
    public function generateTypeHandler()
    {
        return $this->typeHandler == null ?
            $this->typeHandler = new FacebookPageTypeHandlerImpl($this->container, $this) :
            $this->typeHandler;
    }
}