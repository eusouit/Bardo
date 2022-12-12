<?php

use Adianti\Base\TStandardForm;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Sabberworm\CSS\Value\Value;
use Adianti\Util\AdiantiUIBuilder;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Form\TBarCodeInputReader;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;

/**
 * FORMULÁRIO DE CADASTRO DE MATERIAL
 *
 * @version    1.0
 * @package    model
 * @subpackage DEPOSITO DE MATERIAS UMAS E UMES
 * @author     PEDRO FELIPE FREIRE DE MEDEIROS
 * @copyright  Copyright (c) 2021 Barata
 * @license    http://www.adianti.com.br/framework-license
 */
class PedidoHidrometro extends TPage
{
    protected $form; //  FORMULÁRIO
    protected $fieldlist; //  FORMULÁRIO
    //  FORMULÁRIO
    // CONSTRUTOR DE CLASSE
    // CRIA A PÁGINA E O FORMULÁRIO DE INSCRIÇÃO

    function __construct($param)
    {
        parent::__construct();

        $ini  = AdiantiApplicationConfig::get();

        // CRIA O FORMULÁRIO
        $this->form = new BootstrapFormBuilder('my_form');
        $this->form->setFormTitle('<b>FORMULARIO DE PEDIDO DE HIDROMETROS</b>');

        $id = new TEntry('id');
        $id->setEditable(FALSE);
        $id->setSize('100%');

        $hidrometro = new TBarCodeInputReader('hidrometro[]');
        $hidrometro->setSize('100%');
        $hidrometro->setTip('Digite o codigo do Hidrometro');
        $hidrometro->placeholder = 'Y22ZZZZZZ';
        $hidrometro->maxlength = 10;
    
        $this->fieldlist = new TFieldList;
        $this->fieldlist->generateAria();
        $this->fieldlist->width = '100%';
        $this->fieldlist->name  = 'my_field_list';
        $this->fieldlist->addField('<b>HIDROMETRO</b><font color="red"> *</font>',  $hidrometro,  ['width' => '100%']);
        $this->form->addField($hidrometro);
        if($param['id'] != null){
            $hidrometro->setEditable(FALSE);
            $this->fieldlist->disableRemoveButton();
            
        }else{  $btnSave = $this->form->addAction('SALVAR', new TAction([$this, 'onSave']), 'fa:save white');
            $btnSave->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';
            $btnClear = $this->form->addAction('LIMPAR', new TAction([$this, 'onClear']), 'fa:eraser white');
            $btnClear->style = 'background-color:#c73927; color:white; border-radius: 0.5rem;';}
        
        $this->form->addFields(
            [new TLabel('<b>Codigo Pedido Hidrometro</b>')],
            [$id]
        );
      
        $btnBack = $this->form->addActionLink(_t('Back'), new TAction(array('PedidoHidrometroList', 'onReload')), 'far:arrow-alt-circle-left white');
        $btnBack->style = 'background-color:gray; color:white; border-radius: 0.5rem;';
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave($param)
    {
        try {
            $this->form->validate();
            // open a transaction with database 'samples'
            TTransaction::open('bancodados');

            $usuarioLogado = TSession::getValue('userid');

            $duplicates = $this->getDuplicates($param['hidrometro']);
            if ($param['hidrometro'] == [""]) {
                throw new Exception('Campo Hidrometro é obrigatorio não pode ser vazio');
            }
            if ($param['descricao'] == [""]) {
                throw new Exception('Campo Descrição é obrigatorio não pode ser vazio');
            }
            if ($param['quantidade'] == ['0']) {
                throw new Exception('Campo Quantidade não pode ser vazio');
            } else {

                if (isset($param["id"]) && !empty($param["id"])) {
                    $object = new pedidohd($param["id"]);
                    $object->id_usuario = $usuarioLogado;
                    $object->status = 'PENDENTE';
                } else {
                    $object = new pedidohd();
                    $object->id_usuario = $usuarioLogado;
                    $object->status = 'PENDENTE';
                }

                $object->fromArray($param);
                $object->store();

                pivothd::where('id_pedido_hidrometro', '=', $object->id)->delete();


                $hidrometro = array_map(function ($value) {
                    return (string)$value;
                }, $param['hidrometro']);


                if (isset($hidrometro)) {
                    for ($i = 0; $i < count($hidrometro); $i++) {



                        if (!empty($duplicates[$i])) {
                            throw new Exception('Item e repetido na linha ' . ($i + 1) . '. Uma ferramentas nao poder ser solicitada mais de uma vez');
                        }

                        $pivot = new pivothd();
                        $pivot->id_pedido_hidrometro = $object->id;
                        $pivot->hidrometro  = $hidrometro[$i];

                        //Verifica se a quantidade solicitada for maior que a do estoque 

                        $pivot->store();
                    }
                }
            }

            TTransaction::close(); // close the transaction


            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        } catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    public function onClear($param)
    {
    }
    function getDuplicates($param)
    {
        return array_unique(array_diff_assoc($param, array_unique($param)));
    }
    public function onEdit($param)
    {
       if(isset($param['key'])){
        TTransaction::open('bancodados');
        $object = new pedidohd($param["key"]);
        $this->form->setData($object);
        $pivot = pivothd::where('id_pedido_hidrometro', '=', $object->id)->load();
        if ($pivot){
            $this->fieldlist->addHeader();
            foreach($pivot as $key){
                $obj = new stdClass;
                $obj->hidrometro = $key->hidrometro;
                $this->fieldlist->addDetail($obj);
            }         
        }
        $this->form->addContent([$this->fieldlist]);
        TTransaction::close();
      
       }else {
        $this->fieldlist->addHeader();
        $this->fieldlist->addDetail(new stdClass);
        $this->fieldlist->addCloneAction();
        $this->form->addContent([$this->fieldlist]);
       }
      
      
    }
}
