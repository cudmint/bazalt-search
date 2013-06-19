<?php
/**
 * ElasticaPlugin.php
 *
 * @category   System
 * @package    ORM
 * @subpackage Plugin
 * @copyright  2010 Equalteam
 * @license    GPLv3
 * @version    $Revision: 133 $
 */

namespace Bazalt\Search;

use Bazalt\ORM as ORM;

/**
 * ElasticaPlugin 
 * Плагін, що надає змогу автоматично серіалізувати поля в базі даних 
 * @link http://wiki.bazalt.org.ua/ORMSerializable
 *
 * @category   System
 * @package    ORM
 * @subpackage Plugin
 * @copyright  2010 Equalteam
 * @license    GPLv3
 * @version    $Revision: 133 $
 */
class ElasticaPlugin extends ORM\Plugin\AbstractPlugin
{

    /**
     * @var \Elastica\Client Elastic search client
     */
    private static $_client = null;

    /**
     * Ініціалізує плагін
     * 
     * @param ORM\Record $model   Модель, для якої викликано initFields
     * @param array      $options Масив опцій, передається з базової моделі при ініціалізації плагіна
     *
     * @return void 
     */
    public function init(ORM\Record $model, $options)
    {
        ORM\BaseRecord::registerEvent($model->getModelName(), ORM\BaseRecord::ON_RECORD_SAVE, array($this,'onSave'));
    }

    /**
     *
     *
     * @param Record $record  Поточний запис
     * @param bool       &$return Флаг, який зупиняє подальше виконання save()
     *
     * @return void
     */
    public function onSave(ORM\Record $record, &$return)
    {
        $options = $this->getOptions();
        if (!array_key_exists($record->getModelName(), $options)) {
            return;
        }
        $options = $options[$record->getModelName()];

        $index = self::$_client->getIndex($options['index']);
        $type = $index->getType($options['type']);
        $newsDoc = $type->createDocument($record->id, $record->toArray());
        try {
            $type->getDocument($record->id);
            $type->updateDocument($newsDoc);
        } catch (\Elastica\Exception\NotFoundException $e) {
            $type->addDocument($newsDoc);
        }
        $index->refresh();
    }

    /**
     * Set static client for all plugin instances
     *
     * @param \Elastica\Client $client
     */
    public static function setClient(\Elastica\Client $client)
    {
        self::$_client = $client;
    }
}