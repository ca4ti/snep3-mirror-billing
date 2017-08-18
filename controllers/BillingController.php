<?php

class Billing_BillingController extends Zend_Controller_Action {

    /**
     * getForm - Monta formulário
     * @return \Snep_Form_Simple
     */
    protected function getForm() {

        //$form_config = new Zend_Config_Xml('./modules/loguser/forms/log-user.xml', 'general', true);
        //$form = new Snep_Form_Simple($form_config);
        $form = new Snep_Form_Simple();
        $form->setAction($this->getFrontController()->getBaseUrl() . '/billing/billing');


        $form->getElement('submit')->setLabel('Enviar');
        $form->getElement('submit')->removeDecorator('DtDdWrapper');
        $form->getElement('submit')->addDecorator(array("opentd" => 'HtmlTag'), array('class' => 'form_control', "colspan" => 2, 'tag' => 'td'));
        $form->getElement('submit')->addDecorator(array("opentr" => 'HtmlTag'), array('tag' => 'tr'));
        $form->removeElement('cancel');

        return $form;
    }

    /**
     * IndexAction - Monta tela principal
     */
    public function indexAction() {

        $session = new Zend_Session_Namespace("billing");

        $this->view->breadcrumb = $this->view->translate("Billing >> Billing");
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/billing/billing';
        $this->view->urlTelco = $this->getFrontController()->getBaseUrl() . '/billing/telcos';

        $this->view->bills = Billing_Manager::getAll();
    }

    /**
    * Add Bill Action
    */
    public function addAction(){
      $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                  $this->view->translate("Billing"),
                  $this->view->translate("Add")));

      $db = Zend_Registry::get('db');

      $this->view->bill_type = Billing_Manager::getBillTypes();
      $this->view->telcos = Telcos_Manager::getAll();
      $this->view->action = "add" ;
      $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

      if ($this->_request->getPost()) {

        $id = Billing_Manager::add($_POST);
        if(is_int($id)){
          $this->_redirect($this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName());
        }else{
          $this->view->error_message = $id;
          $this->renderScript('error/sneperror.phtml');
        }


      }

    }

    /**
    * Edit Bill Action
    */
    public function editAction(){
      $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                  $this->view->translate("Billing"),
                  $this->view->translate("Edit")));

      $db = Zend_Registry::get('db');

      $this->view->action = "edit" ;
      $id = $this->_request->getParam('id');
      $this->view->bill_type = Billing_Manager::getBillTypes();
      $this->view->telcos = Telcos_Manager::getAll();
      $this->view->bill = Billing_Manager::get($id);

      $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

      if ($this->_request->getPost()) {

        $id = Billing_Manager::update($_POST);
        if($id){
          $this->_redirect($this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName());
        }else{
          $this->view->error_message = $id;
          $this->renderScript('error/sneperror.phtml');
        }


      }

    }

    /**
    * Remove Telco Action
    */
    public function removeAction(){
      $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                  $this->view->translate("Billing"),
                  $this->view->translate("Remove")));

      $id = $this->_request->getParam('id');


      $this->view->id = $id;
      $this->view->action = 'remove';
      $this->view->remove_title = $this->view->translate('Delete a Bill');
      $this->view->remove_message = $this->view->translate('The Bill will be deleted. After that, you have no way get it back.');
      $this->view->remove_form = 'billing';
      $this->renderScript('remove/remove.phtml');


        if ($this->_request->getPost()) {

            $result = Billing_Manager::get($id);
            Billing_Manager::remove($_POST['id']);
            //log-user
            if (class_exists("Loguser_Manager")) {
                $loguser = array(
                  'table' => 'billing',
                  'registerid' => $id,
                  'description' => "Deleted Bill $id - {$result['area']}"
                );
                Snep_LogUser::log("delete", $loguser);
            }
            $this->_redirect($this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName());
            //$this->_redirect($this->getFrontController()->getBaseUrl() . '/billing/telcos');

        }

    }

}
