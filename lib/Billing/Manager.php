<?php

/**
 * Classe to manager a Billing.
 *
 * @see Billing_Manager
 *
 * @category  Snep
 * @package   Billing
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 * 
 */
class Billing_Manager {

    public function __construct() {
        
    }

    /**
     * Get all Billing
     * @return array
     */
    public function getAll() {

        $db = Zend_registry::get('db');

        $select = $db->select()
                ->from("tarifas_valores", array('DATE_FORMAT(data,\'%d/%m/%Y %T\') as data', 'vcel', 'vfix'))
                ->from("tarifas")
                ->from("operadoras", array('nome'))
                ->where("operadoras.codigo = tarifas.operadora")
                ->where("tarifas_valores.codigo = tarifas.codigo");

        $stmt = $db->query($select);
        $billing = $stmt->fetchAll();

        return $billing;
    }

    /**
     * Get a billing by id
     * @param int $id
     * @return Array
     */
    public function get($id) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('tarifas')
                ->where("tarifas.codigo = ?", $id);

        $stmt = $db->query($select);
        $billing = $stmt->fetch();

        return $billing;
    }

    public function getPrefix($data) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('tarifas')
                ->where("tarifas.ddd = ?", $data['ddd'])
                ->where("tarifas.estado = ?", $data['estado'])
                ->where("tarifas.cidade = ?", $data['cidade'])
                ->where("tarifas.prefixo = ?", $data['prefixo']);

        $stmt = $db->query($select);
        $billing = $stmt->fetch();

        if (count($billing) > 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a Billing.
     * @param array $billing
     * @return int
     */
    public function add($billing) {

        $db = Zend_Registry::get('db');

        $insert_data = array('operadora' => $billing['carrier'],
            'ddi' => $billing['country_code'],
            'pais' => $billing['country'],
            'ddd' => $billing['city_code'],
            'cidade' => $billing['city'],
            'estado' => $billing['state'],
            'prefixo' => $billing['prefix']);

        $db->insert('tarifas', $insert_data);

        $idBilling = $db->lastInsertId();

        $insert_data = array('codigo' => $idBilling,
            'data' => new Zend_Db_Expr('NOW()'),
            'vcel' => $billing['tbc'],
            'vfix' => $billing['tbf']);

        $db->insert('tarifas_valores', $insert_data);
    }

    /**
     * Add a billing item
     * @param <type> $idBilling
     * @param <type> $values
     */
    public function addBilling($idBilling, $values) {

        $db = Zend_Registry::get('db');

        $insert_data = array('codigo' => $idBilling,
            'data' => new Zend_Db_Expr('NOW()'), /* $values['data'] */
            'vcel' => $values['vcel'],
            'vfix' => $values['vfix']);

        $db->insert('tarifas_valores', $insert_data);
    }

    /**
     * Update a billing item values
     * @param <type> $idBilling
     * @param <type> $values
     */
    public function editBilling($idBilling, $values) {

        $db = Zend_Registry::get('db');

        $update_data = array('codigo' => $idBilling,
            'data' => $values['data'],
            'vcel' => $values['vcel'],
            'vfix' => $values['vfix']);

        $db->update('tarifas_valores', $update_data, "tarifas_valores.data =  '{$values['data']}' and tarifas_valores.codigo = '$idBilling'");
    }

    /**
     * Remove a billing and all bill tax.
     * @param int $id
     */
    public function remove($id) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->delete('tarifas_valores', "codigo = '$id'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }

        $db->beginTransaction();
        $db->delete('tarifas', "codigo = '$id'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    /**
     * Get all billing values
     * @param int $idCarrier
     * @param int $costCenter
     */
    public function getBillingValues($idBilling) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('tarifas_valores')
                ->where("tarifas_valores.codigo = ?", $idBilling);

        $stmt = $db->query($select);
        $billingValues = $stmt->fetchAll();

        return $billingValues;
    }

    /**
     * Return all States
     * @return Array
     */
    public function getStates() {

        $db = Zend_registry::get('db');

        $select = $db->select()
                ->from("ars_estado", array('cod', 'name'));

        $stmt = $db->query($select);
        $states = $stmt->fetchAll();

        return $states;
    }

    /**
     * Return all cities by state
     * @return Array
     */
    public function getCity($state) {

        $db = Zend_registry::get('db');

        $select = $db->select()
                ->from("ars_cidade", array('name'))
                ->from("ars_ddd", array('estado'))
                ->where("ars_ddd.cidade = ars_cidade.id")
                ->where("ars_ddd.estado = ?", $state)
                ->order("ars_cidade.name");

        $stmt = $db->query($select);
        $cities = $stmt->fetchAll();

        return $cities;
    }

    /**
     * getTarifa - Monta array com todos dados da tarifa
     * @param <int> $id - Código da tarifa
     * @return <array> $tarifa - Dados da tarifa
     */
    function getTarifaLog($id) {

        $db = Zend_Registry::get("db");
        $sql = "SELECT operadora, ddi, pais, ddd, cidade, estado, prefixo, data, vcel, vfix from  tarifas, tarifas_valores where tarifas.codigo = '$id' AND tarifas_valores.codigo ='$id' order by tarifas_valores.data desc";

        $stmt = $db->query($sql);
        $tarifa = $stmt->fetch();
        return $tarifa;
    }

    /**
     * getLastId - Busca ID da ultima tarifa adicionada
     * @return <int> $result - Código da última tarifa
     */
    function getLastId() {

        $db = Zend_Registry::get("db");

        $select = $db->select()
                ->from("tarifas", array('codigo'))
                ->order('tarifas.codigo DESC')
                ->limit(1);

        $stmt = $db->query($select);
        $result = $stmt->fetch();

        return $result["codigo"];
    }

    /**
     * insertLogTarifa - insere na tabela logs_tarifas as tarifas
     * @global <int> $id_user
     * @param <array> $add
     */
    public function insertLogTarifa($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $operadora = $add["operadora"];

        $operadora = self::getOperadora($operadora);

        $insert_data = array('hora' => $hora,
            'ip' => $ip,
            'idusuario' => $username,
            'operadora' => $operadora['nome'],
            'ddi' => $add["ddi"],
            'pais' => $add["pais"],
            'ddd' => $add["ddd"],
            'cidade' => $add["cidade"],
            'estado' => $add["estado"],
            'prefixo' => $add["prefixo"],
            'codigo' => $add["codigo"],
            'data' => $add["data"],
            'vcel' => $add["vcel"],
            'vfix' => $add["vfix"],
            'tipo' => $acao
        );

        $db->insert('logs_tarifas', $insert_data);
    }

    /**
     * getOperadora - Busca dados da operadora
     * @param <int> $operadora
     * @return <array>
     */
    public function getOperadora($operadora) {

        $db = Zend_Registry::get("db");

        $select = $db->select()
                ->from("operadoras", array('nome'))
                ->where("operadoras.codigo =?", $operadora);

        $stmt = $db->query($select);
        $operadora = $stmt->fetch();

        return $operadora;
    }

    /**
     * getId
     * @param <string> $cidade
     * @return <array>
     */
    public function getId($cidade) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('tarifas', array('codigo'))
                ->where("tarifas.cidade = ?", $cidade);

        $stmt = $db->query($select);
        $id = $stmt->fetch();

        return $id;
    }

}
