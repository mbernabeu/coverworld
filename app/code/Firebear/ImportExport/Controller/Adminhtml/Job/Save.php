<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Backend\Model\Session;
use Firebear\ImportExport\Model\Job\MappingFactory;
use Firebear\ImportExport\Helper\Data;
use Magento\Framework\Controller\Result\JsonFactory;

class Save extends JobController
{
    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var MappingFactory
     */
    protected $mappingFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    protected $additionalFields = ['platforms', 'type_file'];

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param EncoderInterface $jsonEncoder
     * @param MappingFactory $mappingFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JsonFactory $jsonFactory,
        JobRepositoryInterface $repository,
        EncoderInterface $jsonEncoder,
        MappingFactory $mappingFactory,
        Data $helper
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->jsonEncoder = $jsonEncoder;
        $this->session = $context->getSession();
        $this->mappingFactory = $mappingFactory;
        $this->helper = $helper;
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
    }

    /**
     * @return $this
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultJson = $this->jsonFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $jobId = $this->getRequest()->getParam('entity_id');
            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = Job::STATUS_ENABLED;
            }
            if (!$jobId) {
                $model = $this->jobFactory->create();
                $data['entity_id'] = null;
            } else {
                if (empty($data['entity_id'])) {
                    $data['entity_id'] = $jobId;
                }
                $model = $this->repository->getById($jobId);
                if (!$model->getId() && $jobId) {
                    $this->messageManager->addErrorMessage(__('This job no longer exists.'));

                    return $resultRedirect->setPath('*/*/');
                }
            }

            // Prepare Behavior Data
            $this->spliteBeahivorData($data);

            // Prepare Source Data
            $this->spliteSourceData($data);
            $model->addData($data);
            // init model and set data
            $this->spliteMapsData($data, $model);

            // try to save it
            try {
                // save the data
                $newModel = $this->repository->save($model);
                // display success message
                if (!$this->getRequest()->isAjax()) {
                    $this->messageManager->addSuccessMessage(__('Job was saved successfully.'));
                }
                // clear previously saved data from session
                $this->session->setFormData(false);
                // check if 'Save and Continue'
                if ($this->getRequest()->isAjax()) {
                    return $resultJson->setData($newModel->getId());
                }
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
                }

                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // save data in session
                $this->session->setFormData($data);

                if ($this->getRequest()->isAjax()) {
                    return $resultJson->setData(false);
                }
                // redirect to edit form
                return $resultRedirect->setPath(
                    '*/*/edit',
                    ['entity_id' => $this->getRequest()->getParam('entity_id')]
                );
            }
        }
        if ($this->getRequest()->isAjax()) {
            return $resultJson->setData(true);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $data
     */
    protected function spliteBeahivorData(&$data)
    {
        $behavior = $data['behavior'];
        $jobModel = $this->jobFactory->create();
        $behaviorFields = $jobModel->getBehaviorFormFields();
        $data['behavior_data']['behavior'] = $behavior;
        foreach ($behaviorFields as $field) {
            $data['behavior_data'][$field] = $data[$field];
            unset($data[$field]);
        }
        $data['behavior_data'] = $this->jsonEncoder->encode($data['behavior_data']);
        unset($data['behavior']);
    }

    /**
     * @param $data
     */
    protected function spliteSourceData(&$data)
    {
        $fields = $this->helper->getConfigFields();
        $data['source_data']['import_source'] = $data['import_source'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data['source_data'][$field] = $data[$field];
                unset($data[$field]);
            }
        }

        foreach ($this->additionalFields as $adField) {
            if (isset($data[$adField])) {
                $data['source_data'][$adField] = $data[$adField];
                unset($data[$adField]);
            }
        }

        $data['source_data'] = $this->jsonEncoder->encode($data['source_data']);
    }

    /**
     * @param $data
     * @param $model
     */
    protected function spliteMapsData(&$data, $model)
    {
        $model->deleteMap();
        if (isset($data['source_data_map'])) {
            foreach ($data['source_data_map'] as $row) {
                if (!(isset($row['delete']) && $row['delete'] == true)) {
                    $newMap = $this->mappingFactory->create();
                    $newMap->setImportCode($row['source_data_import']);
                    if (is_numeric($row['source_data_system'])) {
                        $newMap->setAttributeId((int)$row['source_data_system']);
                    } else {
                        $newMap->setSpecialAttribute($row['source_data_system']);
                    }
                    if (isset($row['source_data_replace']) && $row['source_data_replace']) {
                        $newMap->setDefaultValue($row['source_data_replace']);
                    }
                    $model->addMap($newMap);
                }
            }

            unset($data['source_data_map']);
        }
    }
}
