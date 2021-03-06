<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\SeoXTemplates\Block\Adminhtml\Template\Category\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic as GenericForm;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Store\Model\System\Store;
use MageWorx\SeoXTemplates\Model\Template\Source\IsUseCron as IsUseCronOptions;
use MageWorx\SeoXTemplates\Model\Template\Source\Scope     as ScopeOptions;
use MageWorx\SeoXTemplates\Model\Template\Category\Source\AssignType as AssignTypeOptions;
use MageWorx\SeoXTemplates\Model\Template\Category\Source\Type as TypeOptions;

class Main extends GenericForm implements TabInterface
{
    /**
     * @var  \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $store;

    /**
     * @var ScopeOptions
     */
    protected $scopeOptions;

    /**
     * @var IsUseCronOptions
     */
    protected $isUseCronOptions;

    /**
     *
     * @var AssignTypeOptions
     */
    protected $assignTypeOptions;

    /**
     *
     * @var TypeOptions
     */
    protected $typeOptions;

    /**
     *
     * @param Store $store
     * @param TypeOptions $typeOptions
     * @param IsUseCronOptions $isUseCronOptions
     * @param ScopeOptions $scopeOptions
     * @param AssignTypeOptions $assignTypeOptions
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        Store $store,
        TypeOptions $typeOptions,
        IsUseCronOptions $isUseCronOptions,
        ScopeOptions $scopeOptions,
        AssignTypeOptions $assignTypeOptions,
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        array $data = []
    ) {
        $this->registry              = $registry;
        $this->store                 = $store;
        $this->scopeOptions          = $scopeOptions;
        $this->isUseCronOptions      = $isUseCronOptions;
        $this->assignTypeOptions     = $assignTypeOptions;
        $this->typeOptions           = $typeOptions;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \MageWorx\SeoXTemplates\Model\Template\Category $template */
        $template = $this->registry->registry('mageworx_seoxtemplates_template');

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('template_');
        $form->setFieldNameSuffix('template');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => $this->getLegendText(),
                'class'  => 'fieldset-wide'
            ]
        );

        if ($template->getId()) {
            $fieldset->addField(
                'template_id',
                'hidden',
                ['name' => 'template_id']
            );
        }

        $fieldset->addField(
            'type_id',
            'hidden',
            [
                'name'      => 'type_id',
                'value'     => $this->getTemplateTypeId()
            ]
        );

        $fieldset->addField(
            'store_id',
            'hidden',
            [
                'name'      => 'store_id',
                'value'     => $this->getTemplateStoreId()
            ]
        );

        $fieldset->addField('assign_type', 'radios', array(
          'label'              => 'Assign Type',
          'name'               => 'assign_type',
          'values'             => $this->getAssignTypes(),
          'disabled'           => false,
          'readonly'           => false,
          'after_element_html' => '<br><small>' . __('See the changed tab in the tab list') . '</small>',
        ));

        $fieldset->addField(
            'name',
            'text',
            [
                'name'      => 'name',
                'label'     => __('Name'),
                'title'     => __('Name'),
                'required'  => true,
            ]
        );

        $fieldset->addField(
            'code',
            'text',
            [
                'name'      => 'code',
                'label'     => __('Template Rule'),
                'title'     => __('Template Rule'),
                'required'  => true,
            ]
        );

        $fieldset->addField(
            'scope',
            'select',
            [
                'name'      => 'scope',
                'label'     => __('Apply For'),
                'title'     => __('Apply For'),
                'required'  => true,
                'options'   => $this->scopeOptions->toArray()
            ]
        );

         $fieldset->addField(
            'is_use_cron',
            'select',
            [
                'name'      => 'is_use_cron',
                'label'     => __('Apply By Cron'),
                'title'     => __('Apply By Cron'),
                'required'  => true,
                'options'   => $this->isUseCronOptions->toArray()
            ]
        );

        $templateData = $this->_session->getData('mageworx_seoxtemplates_template_data', true);

        if ($templateData) {
            $template->addData($templateData);
        } else {
            if (!$template->getId()) {
                $template->addData($template->getDefaultValuesForEdit());
            }
        }

        $form->addValues($template->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Category Templates');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Retrive legend text
     *
     * @return string
     */
    protected function getLegendText()
    {
        $storeId        = $this->getTemplateStoreId();
        $storeViewName  = $this->store->getStoreName($storeId);

        $templateTypes  = $this->typeOptions->toArray();
        $templateName   = $templateTypes[$this->getTemplateTypeId()];

        return __('Edit "%1" Template for "%2" Store View', $templateName, $storeViewName);
    }

    /**
     *
     * @return int
     */
    protected function getTemplateStoreId()
    {
        return $this->getCategoryTemplate()->getStoreId();
    }

    /**
     *
     * @return int
     */
    protected function getTemplateTypeId()
    {
        return $this->getCategoryTemplate()->getTypeId();
    }

    /**
     *
     * @return \MageWorx\SeoXTemplates\Model\Template\Category
     */
    protected function getCategoryTemplate()
    {
        return $this->registry->registry('mageworx_seoxtemplates_template');
    }

    /**
     * Retrive filtered by same template type assign options
     *
     * @return array
     */
    protected function getAssignTypes()
    {
        $options = $this->assignTypeOptions->toOptionArray();

        if ($this->getCategoryTemplate()->getDuplicateTemplateAssignedForAll()) {
            foreach ($options as $key => $option) {
                if ($option['value'] == \MageWorx\SeoXTemplates\Model\Template\Category::ASSIGN_ALL_ITEMS) {
                    unset($options[$key]);
                }
            }
        }
        return $options;
    }

    /**
     * Add JS tab switcher in accordin template's assigned type
     *
     * @return string
     */
    protected function _toHtml() {

        return parent::_toHtml() .
        "<script>
             require([
            'jquery'
        ], function($) {
            $('#template_assign_type1').on('change', function() {

                if ($('#template_assign_type1:checked')) {
                    $('#template_category_tabs_categories').parent().hide();
                    $('#template_category_tabs_attributeset').parent().hide();
                }
            });

            $('#template_assign_type2').on('change', function() {

                if ($('#template_assign_type2:checked')) {
                    $('#template_category_tabs_categories').parent().show();
                    $('#template_category_tabs_attributeset').parent().hide();
                }
            });
        });
        </script>";
    }

}
