<?php

use Adianti\Database\TRecord;

/**
 * SystemUnit
 *
 * @version    1.0
 * @package    model
 * @subpackage Atletas Olimpicos
 * @author     PEDRO FELIPE FREIRE DE MEDEIROS
 * @copyright  Copyright (c) 2021 Barata
 * @license    http://www.adianti.com.br/framework-license
 */
class Material extends TRecord
{
    const TABLENAME = 'estoque_gms';
    const PRIMARYKEY = 'id_item';
    const IDPOLICY = 'max'; // {max, serial}
    
    CONST CREATEDAT = 'created_at';
    CONST UPDATEDAT = 'updated_at';
    CONST DELETEDAT = 'deleted_at';

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_item');
        parent::addAttribute('descricao');
        parent::addAttribute('quantidade_estoque');
        parent::addAttribute('id_usuario');
        parent::addAttribute('id_admin');
        parent::addAttribute('created_at');
    }
    
}
