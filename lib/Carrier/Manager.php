<?php
/**
 * Classe to manager a Carrier.
 *
 * @see Carrier_Manager
 *
 * @category  Snep
 * @package   Billing
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 * 
 */
class Carrier_Manager {

    public function __construct() {}

    /**
     * Get all carrier
     */
    public function getAll() {

        $db = Zend_registry::get('db');

        $select = $db->select()
            ->from("operadoras");
            
        $stmt = $db->query($select);
        $carrier = $stmt->fetchAll();

        return $carrier;        
    }

    /**
     * Get a carrier by id
     * @param int $id
     * @return Array
     */
    public function get($id) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
            ->from('operadoras')
            ->where("operadoras.codigo = ?", $id);

        $stmt = $db->query($select);
        $carrier = $stmt->fetch();

        return $carrier;
    }

    /**
     * Add a carrier.
     * @param array $contact
     * @return int
     */
    public function add($carrier) {

        $db = Zend_Registry::get('db');

        $insert_data = array('nome'     => $carrier['name'],
                             'tpm'      => $carrier['ta'],
                             'tdm'      => $carrier['tf'],
                             'tbf'      => $carrier['tbf'],
                             'tbc'      => $carrier['tbc'] );

        $db->insert('operadoras', $insert_data);

        return $db->lastInsertId();   
    }

    /**
     * Remove a carrier.
     * @param int $id
     */
    public function remove($id) {

            $db = Zend_Registry::get('db');

            $db->beginTransaction();
            $db->delete('operadoras', "codigo = '$id'");
            $db->delete('oper_ccustos', "operadora = '$id'");

            try {
                $db->commit();

            } catch (Exception $e) {
                $db->rollBack();
                
            }
    }

    /**
     * Update a carrier data
     * @param Array $data
     */
    public function edit($carrier) {

        $db = Zend_Registry::get('db');

        $update_data = array('nome'     => $carrier['name'],
                             'tpm'      => $carrier['ta'],
                             'tdm'      => $carrier['tf'],
                             'tbf'      => $carrier['tbf'],
                             'tbc'      => $carrier['tbc'] );


        $db->update("operadoras", $update_data, "codigo = '{$carrier['id']}'");

    }

    /**
     * Set CostCenter to Carrier
     * @param int $idCarrier
     * @param int $costCenter
     */
    public function setCostCenter($idCarrier, $costCenter) {

        $db = Zend_Registry::get('db');

        $db->insert('oper_ccustos', array('operadora' => $idCarrier,
                                          'ccustos'   => $costCenter));

    }

    /**
     * Clear all CostCenter associate with a Carrier
     * @param int $idCarrier
     */
    public function clearCostCenter($idCarrier) {

            $db = Zend_Registry::get('db');

            $db->beginTransaction();

            $db->delete('oper_ccustos', "operadora = '$idCarrier'");

            try {
                $db->commit();

            } catch (Exception $e) {
                $db->rollBack();

            }

    }

    /**
     * Return Carrier Cost Center's
     * @param int $idCarrier
     * @return Array $_used
     */
    public function getCarrierCostCenter($idCarrier) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
            ->from('ccustos', array('codigo', 'tipo', 'nome'))
            ->from('oper_ccustos', array())
            ->where('ccustos.codigo = oper_ccustos.ccustos')
            ->where('oper_ccustos.operadora = ?', $idCarrier);

        $stmt = $db->query($select);
        $_used = $stmt->fetchAll();

        return $_used;

        $usedCostCenter = array();
        foreach($_used as $used) {
            $usedCostCenter[] = $used['ccustos'];
        }        
        
    }

    /**
     * Return all idle Cost Centers
     * @return Array
     */
    public function getIdleCostCenter() {

        $db = Zend_Registry::get('db');

        $select = $db->select()
            ->from('oper_ccustos', array('ccustos'));

        $stmt = $db->query($select);
        $_used = $stmt->fetchAll();

        $usedCostCenter = array();
        foreach($_used as $used) {
            $usedCostCenter[] = $used['ccustos'];
        }
        
        $select = $db->select()
            ->from('ccustos', array('codigo', 'tipo', 'nome'));

        if($usedCostCenter) {
            $select->where('ccustos.codigo NOT IN (?)', $usedCostCenter);
        }

        $stmt = $db->query($select);
        $idleCostCenter = $stmt->fetchAll();

        return $idleCostCenter;

    }
}
