<?php

use Adianti\Database\TRecord;

class Ferramentas extends TRecord
{
    const TABLENAME = 'ferramentas';
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
        parent::addAttribute('nome');
        parent::addAttribute('quantidade');
        parent::addAttribute('id_user');
        parent::addAttribute('created_at');
    }
    /**
     * Capturar usuario
     */
    public function get_User()
    {
        // loads the associated object
        if (empty($this->idUser))
            $this->idUser = new SystemUser($this->id_user);

        // returns the associated object
        return $this->idUser;
    }
}
