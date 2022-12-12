<?php

use Adianti\Database\TRecord;

class PivotEmprestimoFerramentas extends TRecord
{
    const TABLENAME = 'pivot_emprestimo_ferramentas';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max'; // {max, serial}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id');
        parent::addAttribute('id_emprestimo');
        parent::addAttribute('id_ferramenta');
        parent::addAttribute('quantidade');
        parent::addAttribute('qtd_emprestada');

    }
}
