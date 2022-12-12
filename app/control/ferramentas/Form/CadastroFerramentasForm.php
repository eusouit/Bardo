<?php

use Adianti\Base\TStandardForm;
use Adianti\Control\TPage;
use Adianti\Registry\TSession;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Form\TText;

/**
 * FormNestedBuilderView
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class CadastroFerramentasForm extends TPage
{
    protected $form;

    /**
     * Class constructor
     * Creates the page
     */
    public function __construct()
    {
        TPage::include_css('app/resources/styles.css');
        parent::__construct();

        $this->form = new BootstrapFormBuilder;
        $this->form->setFormTitle('Cadastro de ferramentas');
        $this->form->generateAria(); // automatic aria-label

        // create the form fields
        $id = new TEntry('id');
        $id->class = 'emprestimo';
        
        $created = new TEntry('created');
        $created->class = 'emprestimo';
        
        $nomeFerramenta = new TEntry('nome');
        $nomeFerramenta->class = 'emprestimo';
        
        $quantidade    = new TSpinner('quantidade');
        $quantidade->class = 'emprestimo';

        // add the fields inside the form
        $row = $this->form->addFields(
            [$labelInfo = new TLabel('Campos com asterisco (<font color="red">*</font>) são considerados campos obrigatórios')],
        );
        $row->style = 'text-align: center';
        
        $row = $this->form->addFields(

            [new TLabel('Id')],
            [$id],
            [new TLabel('Data de criação')],
            [$created],

        );
        $row->style = 'margin-top:3rem;';
        $id->setEditable(FALSE);
        $created->setEditable(FALSE);

        $row = $this->form->addFields(
            [$labelFerramenta = new TLabel('Ferramenta <font color="red">*</font>')],
            [$nomeFerramenta],
            [$labelQuantidade = new TLabel('Quantidade <font color="red">*</font>')],
            [$quantidade],
        );
        $row->style = 'align-items: center';


        //Style in form
        $labelFerramenta->style = 'left: -100%;';
        $id->setSize('20%');
        $created->setSize('60%');
        $nomeFerramenta->setSize('100%');
        $quantidade->setSize('20%');
        $nomeFerramenta->placeholder = 'Nome do ferramenta';
        $quantidade->setTip = ('Informe a quantidade de materiais');

        // define the form action 
        $btnBack = $this->form->addActionLink(_t('Back'), new TAction(array('FerramentasList', 'onReload')), 'far:arrow-alt-circle-left White');
        $btnBack->style = 'background-color:gray; color:white; border-radius: 0.5rem;';
        $btnClear = $this->form->addAction(_t('Clear'), new TAction([$this, 'onClear']), 'fa:eraser White');
        $btnClear->style = 'background-color:#c73927; color:white; border-radius: 0.5rem;';
        $btnSave = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save White');
        $btnSave->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';

        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%; margin-top:2rem';
        $vbox->add($this->form);
        parent::add($vbox);
    }
    /**
     * Save form 
     */
    public function onSave($param)
    {
        try {

            if (empty($param['nome']) or $param['quantidade'] == '0') {
                throw new Exception('Campos obrigatórios nao podem ser vazios');
            }

            TTransaction::open('bancodados');
            if (isset($param['id'])) {
                $user = TSession::getValue('userid'); //get user logged

                $this->form->validate();
                $object = new Ferramentas();
                $data = $this->form->getData(); // get form data as array
                $object->id_user = $user;

                $object->fromArray((array) $data); // load the object with data
                $object->store();

                // get the generated id
                $data->id = $object->id;
            }

            $this->form->setData($data); // fill form data


            TTransaction::close();
            $this->fireEvents($param);
            new TMessage('info', 'Ferramenta cadastrada');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData()); // keep form data
            TTransaction::rollback();
            $this->fireEvents($param);

        }
    }
    /**
     * Cria na view os forms para criar/editar
     */
    public function onEdit($param)
    {
        try {

            if (isset($param['id'])) {

                TTransaction::open('bancodados');
                $object = new Ferramentas($param['id']);


                $this->form->setData($object);
                TTransaction::close();
            } else {
                $this->form->clear();
            }
        } catch (Exception $e) {

            new TMessage('error', $e->getMessage());
            $this->fireEvents($param);

        }
    }
    public function onClear($param)
    {
    }

    /**
     * Fire form events
     * @param $param Request
     */
    public function fireEvents($param)
    {
        try {
            if (isset($param['id'])) {

                $id = $param['id'];
                TTransaction::open('bancodados');
                $object = new Ferramentas($id);

                $this->form->setData($object);
                TTransaction::close();
            } else {
                $this->form->clear();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

}
