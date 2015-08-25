<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/14/15
 * Time: 5:04 PM
 */

namespace ChristianBudde\PartFacebook\model;



use ChristianBudde\Part\controller\ajax\TypeHandlerGenerator;
use ChristianBudde\Part\controller\json\JSONObjectSerializable;

interface FacebookPage extends JSONObjectSerializable, TypeHandlerGenerator{

    /**
     * @return int Number of likes
     */
    public function getNumberOfLikes();

    /**
     * @param string $id If empty will return the first status.
     * @return FacebookStatus
     */
    public function getStatus($id = "");

    /**
     * @return mixed
     */
    public function getId();

    /**
     * Clears all cache
     * @return void
     */
    public function clearCache();

    /**
     * Updates the cache
     * @return bool TRUE if something changed else FALSE
     */
    public function update();



}