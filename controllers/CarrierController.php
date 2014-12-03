<?php

/**
 * Carrier Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class Billing_CarrierController extends Zend_Controller_Action {

    /**
     * List all Carrier
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Carrier"),
                    $this->view->translate("Carrier")));

        $this->view->url = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from("operadoras");
                

        if ($this->_request->getPost('filtro')) {
            $field = mysql_escape_string($this->_request->getPost('campo'));
            $query = mysql_escape_string($this->_request->getPost('filtro'));
            $select->where("`$field` like '%$query%'");
        }

        $this->view->order = Snep_Order::setSelect($select, array("codigo","nome", "tpm", "tdm"), $this->_request);

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );
        $this->view->filtro = $this->_request->getParam('filtro');

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage(Zend_Registry::get('config')->ambiente->linelimit);

        $this->view->carrier = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";

        $opcoes = array("codigo" => $this->view->translate("Code"),
            "nome" => $this->view->translate("Name"));

        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName() . '/index');
        $filter->setValue($this->_request->getPost('campo'));
        $filter->setFieldOptions($opcoes);
        $filter->setFieldValue($this->_request->getPost('filtro'));
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getModuleName()}/{$this->getRequest()->getControllerName()}/index/page/$page");

        $this->view->form_filter = $filter;
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getModuleName()}/{$this->getRequest()->getControllerName()}/add/",
                "display" => $this->view->translate("Add Carrier"),
                "css" => "include"));
    }

    /**
     *  Add Carrier
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Carrier"),
                    $this->view->translate("Add")));

        $this->view->objSelectBox = "carrier";

        $xml = new Zend_Config_Xml("modules/billing/forms/carrier.xml");
        $form = new Snep_Form($xml);
        $_idleCostCenter = Carrier_Manager::getIdleCostCenter();
        $idleCostCenter = array();

        foreach ($_idleCostCenter as $idle) {
            $idleCostCenter[$idle['codigo']] = $idle['codigo'] . " : " . $idle['tipo'] . " - " . $idle['nome'];
        }

        if ($idleCostCenter) {
            $form->setSelectBox($this->view->objSelectBox, $this->view->translate('Cost Center'), $idleCostCenter);
        }

        if ($this->_request->getPost()) {

            $form_isValid = $form->isValid($_POST);
            $dados = $this->_request->getParams();

            if ($form_isValid) {
                $idCarrier =  Carrier_Manager::add($dados);

                foreach ($dados['box_add'] as $costCenter) {
                    Carrier_Manager::setCostCenter($idCarrier, $costCenter);
                }
                $this->_redirect($this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName());
            }
        }
        $this->view->form = $form;
    }

    /**
     * Edit Carrier
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Carrier"),
                    $this->view->translate("Edit")));

        $this->view->objSelectBox = "carrier";
        $id = $this->_request->getParam("id");

        $xml = new Zend_Config_Xml("modules/billing/forms/carrier.xml");
        $carrier = Carrier_Manager::get($id);

        $form = new Snep_Form($xml);
        $form->getElement('name')->setValue($carrier['nome']);
        $form->getElement('ta')->setValue($carrier['tpm']);
        $form->getElement('tf')->setValue($carrier['tdm']);
        $form->getElement('tbf')->setValue($carrier['tbf']);
        $form->getElement('tbc')->setValue($carrier['tbc']);

        $_idleCostCenter = Carrier_Manager::getIdleCostCenter();
        $idleCostCenter = array();

        foreach ($_idleCostCenter as $idle) {
            $idleCostCenter[$idle['codigo']] = $idle['codigo'] . " : " . $idle['tipo'] . " - " . $idle['nome'];
        }

        if (isset($id)) {
            $_selectedCostCenter = Carrier_Manager::getCarrierCostCenter($id);
            $selectedCostCenter = array();
            foreach ($_selectedCostCenter as $selected) {
                $selectedCostCenter[$selected['codigo']] = $selected['codigo'] . " : " . $selected['tipo'] . " - " . $selected['nome'];
            }
        }

        $form->setSelectBox($this->view->objSelectBox, $this->view->translate('Cost Center'), $idleCostCenter, $selectedCostCenter);
        $formId = new Zend_Form_Element_Hidden('id');
        $formId->setValue($id);
        $form->addElement($formId);

        if ($this->_request->getPost()) {
            $form_isValid = $form->isValid($_POST);
            $dados = $this->_request->getParams();

            if ($form_isValid) {

                Carrier_Manager::edit($dados);
                if ($dados['box_add']) {
                    Carrier_Manager::clearCostCenter($dados['id']);
                    foreach ($dados['box_add'] as $costCenter) {
                        Carrier_Manager::setCostCenter($dados['id'], $costCenter);
                    }
                }
                $this->_redirect($this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName());
            }
        }
        $this->view->form = $form;
    }

    /**
     * Remove a Carrier
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Carrier"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');
        Carrier_Manager::remove($id);
        $this->_redirect($this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName());
    }

}
