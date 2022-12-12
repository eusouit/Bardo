<?php

use Adianti\Database\TRecord;

/**
 * SystemUnit
 *
 * @version    1.0
 * @package    model
 * @subpackage pivot pedido material
 * @author     PEDRO FELIPE FREIRE DE MEDEIROS
 * @copyright  Copyright (c) 2022 Barata
 * @license    http://www.adianti.com.br/framework-license
 */
class PivotPedidoMaterial extends TRecord
{
    const TABLENAME = 'pivot_pedido_material';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max'; // {max, serial}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id');
        parent::addAttribute('id_pedido_material');
        parent::addAttribute('id_item');
        parent::addAttribute('quantidade');
        parent::addAttribute('quantidade_fornecida');
    }
}
