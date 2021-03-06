<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job;

use Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    /**
     * @var
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $importCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param PoolInterface $pool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $importCollectionFactory,
        DataPersistorInterface $dataPersistor,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        PoolInterface $pool,
        array $meta = [],
        array $data = []
    ) {
        $this->jsonDecoder   = $jsonDecoder;
        $this->collection    = $importCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->pool = $pool;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items      = $this->collection->getItems();
        $jsonFields = [
            ImportInterface::BEHAVIOR_DATA,
            ImportInterface::SOURCE_DATA
        ];
        foreach ($items as $job) {
            $data = $job->getData();
            if ($maps = $job->getMap()) {
                $map = $this->scopeMaps($maps);
                $data = array_merge($data, $map);
                $data = array_merge($data, ['special_map' =>$map]);
            }

            foreach ($jsonFields as $name) {
                if ($data[$name]) {
                    $tempData = $this->jsonDecoder->decode($data[$name]);
                    unset($data[$name]);
                    $data += $tempData;
                }
            }
            
            $this->loadedData[$job->getId()] = $data;
        }

        $data = $this->dataPersistor->get('job');

        if (!empty($data)) {
            $job = $this->collection->getNewEmptyItem();
            $job->setData($data);
            $this->loadedData[$job->getId()] = $job->getData();
            $this->dataPersistor->clear('job');
        }

        return $this->loadedData;
    }

    /**
     * @return mixed
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * @param $maps
     *
     * @return mixed
     */
    protected function scopeMaps($maps)
    {
        $map['source_data_map'] = [];
        $count                  = 0;
        foreach ($maps as $field) {
            $map['source_data_map'][] = [
                'source_data_system' => $field->getAttributeId()
                    ? $field->getAttributeId() : $field->getSpecialAttribute(),
                'source_data_import' => $field->getImportCode(),
                'source_data_replace' => $field->getDefaultValue(),
                'record_id' => $count++
            ];
        }

        return $map;
    }
}
