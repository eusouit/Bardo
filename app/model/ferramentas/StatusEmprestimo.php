<?php

use Adianti\Database\TRecord;

class StatusEmprestimo extends TRecord
{
    const TABLENAME = 'status_ferramentas';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max'; // {max, serial}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id');
        parent::addAttribute('nome');

    }
}
