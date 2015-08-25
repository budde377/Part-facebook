<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/14/15
 * Time: 8:06 PM
 */

namespace ChristianBudde\PartFacebook\model;


use ChristianBudde\PartFacebook\controller\json\FacebookStatusJSONObjectImpl;
use ChristianBudde\Part\BackendSingletonContainer;
use ChristianBudde\Part\util\file\File;
use ChristianBudde\Part\util\file\FileImpl;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use stdClass;

class FacebookStatusImpl implements FacebookStatus
{

    private $id;
    private $session;
    private $page;
    private $container;
    private $images;
    private $message;
    private $number_of_likes;
    private $updated_time;
    private $prev_id;
    private $next_id;
    private $created_time;
    private $object;

    /**
     * @param BackendSingletonContainer $container
     * @param FacebookSession $session
     * @param FacebookPage $page
     * @param string $id
     * @param string $prev_id
     * @param string $next_id
     * @param null|stdClass $object
     */
    function __construct(BackendSingletonContainer $container, FacebookSession $session, FacebookPage $page, $id, $prev_id = "", $next_id = "", $object=null)
    {
        $this->object = $object;
        $this->container = $container;
        $this->session = $session;
        $this->id = $id;
        $this->page = $page;
        $this->prev_id = $prev_id;
        $this->next_id = $next_id;
    }


    /**
     * @return string The status id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string[] An array of absolute image urls.
     */
    public function getImages()
    {
        $this->setUp();
        return $this->images;
    }

    /**
     * @return String Status update message
     */
    public function getMessage()
    {
        $this->setUp();
        return $this->message;
    }

    /**
     * @return int Number of likes
     */
    public function getNumberOfLikes()
    {
        $this->setUp();
        return $this->number_of_likes;
    }

    /**
     * @return int unix timestamp in seconds since epoch
     */
    public function getUpdatedTime()
    {
        $this->setUp();
        return $this->updated_time;
    }

    /**
     * @return FacebookStatus
     */
    public function getNextStatus()
    {
        if ($this->next_id == "") {
            return null;
        }

        return $this->page->getStatus($this->next_id);
    }

    /**
     * @return FacebookStatus
     */
    public function getPreviousStatus()
    {
        if ($this->prev_id == "") {
            return null;
        }
        return $this->page->getStatus($this->prev_id);
    }

    /**
     * @param null $ar
     * @return bool
     * @internal param array $a
     */
    private function setUp($ar = null)
    {

        if ($this->created_time != null && $ar == null) {
            return true;
        }
        $f = null;
        $a = null;
        if (($t = $this->container->getTmpFolderInstance()) != null) {
            $f = new FileImpl("{$t->getAbsolutePath()}/FacebookStatusImpl/{$this->id}");
            if ($f->exists() && $ar == null) {
                $a = unserialize($f->getContents());
            }
        }

        if ($a == null) {
            $a = $ar == null?$this->buildStatus():$ar;
            if ($f != null) {
                $f->getParentFolder()->create(true);
                $f->setAccessMode(File::FILE_MODE_RW_TRUNCATE_FILE_TO_ZERO_LENGTH);
                $f->write(serialize($a));
            }

        }
        $this->created_time = $a['created_time'];
        $this->updated_time = $a['updated_time'];
        $this->message = $a['message'];
        $this->number_of_likes = $a['number_of_likes'];
        $this->images = $a['images'];
        return false;
    }

    /**
     * @return bool
     */
    public function update()
    {
        if (!$this->setUp()) {
            return false;
        }


        if ([
                'created_time' => $this->created_time,
                'updated_time' => $this->updated_time,
                'message' => $this->message,
                'number_of_likes' => $this->number_of_likes,
                'images' => $this->images] != ($a = $this->buildStatus())
        ) {
            $this->setUp($a);
            return true;
        }

        return false;
    }

    /**
     * @return int unix timestamp in seconds since epoch
     */
    public function getCreatedTime()
    {
        $this->setUp();
        return $this->created_time;
    }

    private function buildAttachments(\stdClass $attachments)
    {
        if (empty($attachments->data)) {
            return [];
        }
        $result = [];
        foreach ($attachments->data as $d) {
            switch ($d->type) {
                case 'album':
                    $result += $this->buildAttachments($d->subattachments);
                    break;
                case 'photo':
                    $result[] = $d->media->image;
                    break;

            }
        }
        return $result;
    }

    private function buildStatus()
    {
        if($this->object == null){
            $result = (new FacebookRequest($this->session, 'GET', "/{$this->getId()}?fields=message,attachments{type,url,media,subattachments},likes.summary(1),updated_time,created_time,link"))->execute()->getResponse();
        } else {
            $result = $this->object;
        }

        $resultArray = [];
        $resultArray['message'] = !empty($result->message) ? $result->message : "";
        $resultArray['created_time'] = !empty($result->created_time) ? strtotime($result->created_time) : -1;
        $resultArray['updated_time'] = !empty($result->updated_time) ? strtotime($result->updated_time) : -1;
        $resultArray['number_of_likes'] = !empty($result->likes) && !empty($result->likes->summary) && !empty($result->likes->summary->total_count) ?
            $result->likes->summary->total_count : -1;
        $resultArray['images'] = !empty($result->attachments) ? $this->buildAttachments($result->attachments) : [];

        return $resultArray;
    }

    /**
     * Clears cache
     * @return void
     */
    public function clearCache()
    {
        if (($t = $this->container->getTmpFolderInstance()) != null) {
            $f = new FileImpl("{$t->getAbsolutePath()}/FacebookStatusImpl/{$this->id}");
            $f->delete();
        }
    }

    /**
     * @return string
     */
    public function getLink()
    {

        return "https://www.facebook.com/{$this->page->getId()}/posts/{$this->getShortId()}";

    }

    public function getShortId(){
        $id_array = explode("_", $this->getId());
        return $id_array[1];
    }

    /**
     * Serializes the object to an instance of JSONObject.
     * @return Object
     */
    public function jsonObjectSerialize()
    {
        return new FacebookStatusJSONObjectImpl($this);
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
}
