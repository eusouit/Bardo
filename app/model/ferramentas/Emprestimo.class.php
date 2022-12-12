<?php

use Adianti\Database\TRecord;

class Emprestimo extends TRecord
{
    const TABLENAME = 'emprestimo';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max'; // {max, serial}

    const CREATEDAT = 'created_at';
    const UPDATEDAT = 'updated_at';
    const DELETEDAT = 'deleted_at';

    protected $idUser;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id');
        parent::addAttribute('id_usuario');
        parent::addAttribute('id_admin');
        parent::addAttribute('status');
        parent::addAttribute('created_at');
    }
    /**
     * Capturar usuario
     */
    public function get_User()
    {
        // loads the associated object
        if (empty($this->idUser))
            $this->idUser = new SystemUser($this->id_usuario);

        // returns the associated object
        return $this->idUser;
    }
}
