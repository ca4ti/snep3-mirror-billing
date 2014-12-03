<?php

/**
 * Billing Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class Billing_BillingController extends Zend_Controller_Action {

    /**
     * indexAction - List all Billing
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Carrier"),
                    $this->view->translate("Billing")
        ));

        $this->view->url = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from("tarifas_valores", array('DATE_FORMAT(data,\'%d/%m/%Y %T\') as data', 'vcel', 'vfix'))
                ->from("tarifas")
                ->from("operadoras", array('nome'))
                ->where("operadoras.codigo = tarifas.operadora")
                ->where("tarifas_valores.codigo = tarifas.codigo");

        if ($this->_request->getPost('filtro')) {
            $field = mysql_escape_string($this->_request->getPost('campo'));
            $query = mysql_escape_string($this->_request->getPost('filtro'));
            $select->where("`$field` like '%$query%'");
        }

        $this->view->order = Snep_Order::setSelect($select, array("nome","pais","estado","cidade","ddd","prefixo","data","vcel","vfix"), $this->_request);

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );
        $this->view->filtro = $this->_request->getParam('filtro');

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage(Zend_Registry::get('config')->ambiente->linelimit);

        $this->view->billing = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getModuleName()}/{$this->getRequest()->getControllerName()}/index/";

        $opcoes = array("nome" => $this->view->translate("Carrier"),
            "pais" => $this->view->translate("Country"),
            "estado" => $this->view->translate("State"),
            "cidade" => $this->view->translate("City"),
            "prefixo" => $this->view->translate("Prefix"),
            "ddd" => $this->view->translate("City Code"));

        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName() . '/index');
        $filter->setValue($this->_request->getPost('campo'));
        $filter->setFieldOptions($opcoes);
        $filter->setFieldValue($this->_request->getPost('filtro'));
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getModuleName()}/{$this->getRequest()->getControllerName()}/index/page/$page");

        $this->view->form_filter = $filter;
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getModuleName()}/{$this->getRequest()->getControllerName()}/add/",
                "display" => $this->view->translate("Incluir Tarifa"),
                "css" => "include"));
    }

    /**
     *  Add billing
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Carrier"),
                    $this->view->translate("Add")
        ));

        $form = new Snep_Form(new Zend_Config_Xml("modules/billing/forms/billing.xml"));
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName() . '/add');

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName();

        foreach (Carrier_Manager::getAll() as $_carrier) {
            $carriers[$_carrier['codigo']] = $_carrier['nome'];
        }
        $this->view->carriers = $carriers;

        $states['--'] = '--';
        foreach (Billing_Manager::getStates() as $state) {
            $states[$state['cod']] = $state['name'];
        }
        $this->view->states = $states;

        $cities['--'] = '--';
        foreach (Billing_Manager::getCity('AC') as $city) {
            $cities[$city['name']] = $city['name'];
        }
        $this->view->cities = $cities;
        $dados = $this->_request->getParams();

        if ($this->_request->getPost()) {

            $form_isValid = true;
            $this->view->error = array();

            if (!preg_match('/[0-9]+$/', $dados['ddd']) || $dados['ddd'] == "") {
                $form_isValid = false;
                $this->view->error['ddd'] = $this->view->translate("City Code not numeric");
            }

            if (!preg_match('/[0-9]+$/', $dados['ddi']) || $dados['ddi'] == "") {
                $form_isValid = false;
                $this->view->error['ddi'] = $this->view->translate("Country Code not numeric");
            }

            if (!preg_match('/[0-9]+$/', $dados['prefixo']) || $dados['prefixo'] == "") {
                $form_isValid = true;
                $this->view->error['prefixo'] = $this->view->translate("Prefix not numeric");
            }
            if ($dados['operadora'] == "") {
                $form_isValid = false;
                $this->view->error['operadora'] = $this->view->translate("Carrier not selected ");
            }

            if ($form_isValid) {

                if ($_POST['ddd'] == "") {
                    $_POST['ddd'] = 0;
                }
                if ($_POST['ddi'] == "") {
                    $_POST['ddi'] = 0;
                }

                $billing = Billing_Manager::getPrefix($_POST);

                if ($billing) {
                    $form_isValid = false;
                    $this->view->message = $this->view->translate("This bill is already set");
                }
            }

            if ($form_isValid) {

                $xdados = array('data' => $_POST['data'],
                    'carrier' => $_POST['operadora'],
                    'country_code' => $_POST['ddi'],
                    'country' => $_POST['pais'],
                    'city_code' => $_POST['ddd'],
                    'city' => $_POST['cidade'],
                    'state' => $_POST['estado'],
                    'prefix' => $_POST['prefixo'],
                    'tbf' => $_POST['vfix'],
                    'tbc' => $_POST['vcel']);

                Billing_Manager::add($xdados);

                //log-user
                if (class_exists("Loguser_Manager")) {

                    $id = Billing_Manager::getId($xdados["city"]);
                    $add = Billing_Manager::getTarifaLog($id['codigo']);
                    $lastId = Billing_Manager::getLastId();
                    $add["codigo"] = $lastId;
                    Billing_Manager::insertLogTarifa("ADD", $add);
                    Snep_LogUser::salvaLog("Adicionou tarifa", $lastId, 4);
                }

                $this->_redirect($this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName());
            }
            $this->view->dados = ( isset($dados) ? $dados : null);
        }
    }

    /**
     * Edit Billing
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Carrier"),
                    $this->view->translate("Edit")));

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');
        $id = $this->_request->getParam("id");
        $_carriers = Carrier_Manager::getAll();

        foreach ($_carriers as $_carrier) {
            $carriers[$_carrier['codigo']] = $_carrier['nome'];
        }

        $this->view->carriers = $carriers;
        $this->view->Carrier = Billing_Manager::get($id);
        $this->view->billingValues = Billing_Manager::getBillingValues($id);

        $_estado = Billing_Manager::getStates();
        foreach ($_estado as $estado) {
            if ($estado['cod'] == $this->view->Carrier['estado']) {
                $this->view->billingState = $estado;
            }
        }

        if ($this->_request->getPost()) {

            if (isset($_POST['action'])) {
                foreach ($_POST['action'] as $ida => $num) {
                    if ($num < count($this->view->billingValues)) {

                        $values = array('data' => $_POST['data'][$num],
                            'vcel' => $_POST['vcel'][$num],
                            'vfix' => $_POST['vfix'][$num]);

                        //log-user      
                        if (class_exists("Loguser_Manager")) {

                            $old = Billing_Manager::getTarifaLog($id);
                            $old['codigo'] = $id;
                            Billing_Manager::insertLogTarifa("OLD", $old);
                        }

                        Billing_Manager::editBilling($id, $values);

                        if (class_exists("Loguser_Manager")) {

                            $new = Billing_Manager::getTarifaLog($id);
                            $new['codigo'] = $id;
                            Billing_Manager::insertLogTarifa("NEW E", $new);
                            LogUser::salvaLog("Editou tarifa", $id, 4);
                        }
                    } else {

                        $values = array('data' => $_POST['data'][$num],
                            'vcel' => $_POST['vcel'][$num],
                            'vfix' => $_POST['vfix'][$num]);

                        Billing_Manager::addBilling($id, $values);

                        if (class_exists("Loguser_Manager")) {

                            $new = Billing_Manager::getTarifaLog($id);
                            $new['codigo'] = $id;
                            Billing_Manager::insertLogTarifa("NEW N", $new);
                            Snep_LogUser::salvaLog("Adicionou nova tarifa", $id, 4);
                        }
                    }
                }
            }
            $this->_redirect($this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName());
        }
    }

    /**
     * Remove a Billing
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Billing"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');

        //log-user
        if (class_exists("Loguser_Manager")) {

            Snep_LogUser::salvaLog("Excluiu tarifa", $id, 4);
            $del = Billing_Manager::getTarifaLog($id);
            $del["codigo"] = $id;
            Billing_Manager::insertLogTarifa("DEL", $del);
        }

        Billing_Manager::remove($id);
        $this->_redirect($this->getRequest()->getModuleName() .'/'. $this->getRequest()->getControllerName());
    }

    /**
     * Get cities from state
     * POST Array state
     */
    public function dataAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $data = $_POST;

        if (isset($data['state'])) {
            $_states = Billing_Manager::getCity($data['state']);

            $states = array();
            foreach ($_states as $state) {
                $states[] = $state['name'];
            }
        }
        echo Zend_Json::encode($states);
    }

    /**
     * cidadeAction
     */
    public function cidadeAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $estado = isset($_POST['uf']) && $_POST['uf'] != "" ? $_POST['uf'] : display_error($LANG['msg_nostate'], true);
        $municipios = Snep_Cnl::get($estado);

        $options = '';
        if (count($municipios > 0)) {
            foreach ($municipios as $cidades) {
                $options .= "<option  value='{$cidades['municipio']}' > {$cidades['municipio']} </option> ";
            }
        } else {
            $options = "<option> {$LANG['select']} </option>";
        }

        echo $options;
    }

}