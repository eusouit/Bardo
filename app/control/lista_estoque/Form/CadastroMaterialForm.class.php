<?php

use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;

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
class CadastroMaterialForm extends TStandardForm
{
  protected $form; //  FORMULÁRIO
  protected $subform; //  FORMULÁRIO

  // CONSTRUTOR DE CLASSE
  // CRIA A PÁGINA E O FORMULÁRIO DE INSCRIÇÃO

  function __construct()
  {
    TStandardForm::include_css('app/resources/styles.css');
    parent::__construct();

    $ini  = AdiantiApplicationConfig::get();

    $this->setDatabase('bancodados');              // DEFINE O BANCO DE DADOS
    $this->setActiveRecord('Material');               // DEFINE O REGISTRO ATIVO

    // CRIA O FORMULÁRIO
    $this->form = new BootstrapFormBuilder('form_material');
    $this->subform = new BootstrapFormBuilder('subform_material');
    $this->form->setFormTitle('<b>CADASTRO ITENS DEPOSITO</b>');

    // CRIE OS CAMPOS DO FORMULÁRIO
    $codigo = new TEntry('id_item');
    $codigo->id = "input-form";
    $descricao = new TEntry('descricao');
    $descricao->id = "input-form";
    $quantidadeEstoque = new TEntry('quantidade_estoque');
    $quantidadeEstoque->id = "input-form";
    $colaborador_responsavel = new TEntry('id_usuario');
    $colaborador_responsavel->id = "input-form";

    $row = $this->form->addFields(
      [$labelInfo = new TLabel('<b>Campos com asterisco (<font color="red">*</font>) são considerados campos obrigatórios</b>')],
    );

    // ADICIONE OS CAMPOS
    $row = $this->form->addFields(
      [new TLabel('Codigo do item <font color="red">*</font>')],
      [$codigo],
      [new TLabel('Colaborador responsável')],
      [$colaborador_responsavel]
    );
    $this->form->addFields(
      [new TLabel('Descrição <font color="red">*</font>')],
      [$descricao],
      [new TLabel('Quantidade <font color="red">*</font>')],
      [$quantidadeEstoque],
    );

    $codigo->addValidation('Codigo do item <font color="red">*</font>', new TRequiredValidator);
    $descricao->addValidation('Descrição <font color="red">*</font>', new TRequiredValidator);
    $quantidadeEstoque->addValidation('Quantidade <font color="red">*</font>', new TRequiredValidator);
    $colaborador_responsavel->addValidation('Colaborador responsável <font color="red">*</font>', new TRequiredValidator);

    $codigo->setTip('Digite o codigo do item que deseja cadastrar');
    $codigo->placeholder = '00000';
    $codigo->setSize('25%');
    $codigo->setMask('99999');
    $codigo->maxlength = 5;

    $descricao->setTip('Digite a descrição do item desejado');
    $descricao->setSize('70%');
    $descricao->placeholder = 'Descrição do Item';

    $quantidadeEstoque->setTip('Digite a quantidade do item desejado');
    $quantidadeEstoque->setSize('70%');
    $quantidadeEstoque->placeholder = 'Descrição do Item';
    $quantidadeEstoque->setMask('99999');
    $quantidadeEstoque->placeholder = '00000';

    $colaborador_responsavel->setSize('70%');
    $colaborador_responsavel->setValue(TSession::getValue('userid'));
    $colaborador_responsavel->setEditable(FALSE);

    // CRIE AS AÇÕES DO FORMULÁRIO
    $btnBack = $this->form->addActionLink(_t('Back'), new TAction(array('MaterialList', 'onReload')), 'far:arrow-alt-circle-left white');
    $btnBack->style = 'background-color:gray; color:white; border-radius: 0.5rem;';
    $btnClear = $this->form->addActionLink(_t('Clear'),  new TAction(array($this, 'onEdit')), 'fa:eraser white');
    $btnClear->style = 'background-color:#c73927; color:white; border-radius: 0.5rem;';
    $btnSave = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
    $btnSave->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';

    // RECIPIENTE DE CAIXA VERTICAL
    $container = new TVBox;
    $container->style = 'width: 100%';
    $container->add(new TXMLBreadCrumb('menu.xml', 'MaterialList'));
    $container->add($this->form);

    parent::add($container);
  }
}