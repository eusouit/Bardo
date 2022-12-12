<?php

use Adianti\Widget\Form\TEntry;

/**
 * LISTA DE FERRAMENTAS
 *
 * @version    1.0
 * @package    model
 * @subpackage deposito de materias umas e umes
 * @author     Italo Nogueira Coutinho Costa 
 * @copyright  Copyright (c) 2022 Italo Nogueira Coutinho Costa
 * @license    http://www.adianti.com.br/framework-license
 */
class FerramentasList extends TStandardList
{
  protected $form;
  protected $datagrid;
  protected $pageNavigation;
  protected $formgrid;
  protected $deleteButton;
  protected $transformCallback;

  public function __construct()
  {
    TStandardList::include_css('app/resources/styles.css');
    parent::__construct();

    parent::setDatabase('bancodados');            // Define o banco de dados
    parent::setActiveRecord('Ferramentas');   // Define o registro ativo
    parent::setDefaultOrder('id', 'desc');         //  Define a ordem padrão


    //Cria o formulário

    $this->form = new BootstrapFormBuilder('form_search');
    $this->form->setFormTitle('Lista de ferramentas');

    // Campos do formulário

    $unique = new TDBUniqueSearch('FerramentaList', 'bancodados', 'ferramentas', 'id', 'nome');
    $unique->setMinLength(1);
    $unique->setMask('{id} - {nome}');

    // Adicionando campos na página
    $this->form->addFields(
      [new TLabel('Campo de busca')],
      [$unique],
    );

    // Mantém o formulário preenchido durante a navegação com os dados da sessão
    $this->form->setData(TSession::getValue('cadastro_filter_data'));

    // Ações do fomulário
    $btn = $this->form->addAction('Buscar', new TAction(array($this, 'onSearch')), 'fa:search white');
    $btn->style = 'background-color:#2c7097; color:white; border-radius: 0.5rem;';
    $btn = $this->form->addAction("Cadastrar Ferramenta", new TAction(array('CadastroFerramentasForm', "onEdit")), "fa:plus-circle white");
    $btn->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';

    //Datagrid
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->datatable = 'true';
    $this->datagrid->style = 'width: 100%';
    $this->datagrid->setHeight(320);

    //Colunas do data gride
    $column_id = new TDataGridColumn('id', 'Id', 'center', 50);
    $column_nome = new TDataGridColumn('nome', 'Nome da ferramenta', 'center');
    $column_quantidade = new TDataGridColumn('quantidade', 'Quantidade', 'center');
    $column_usuario = new TDataGridColumn('User->name', 'Usuario', 'center');

    // Adicionando as colunas 
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_nome);
    $this->datagrid->addColumn($column_quantidade);
    $this->datagrid->addColumn($column_usuario);
    $this->datagrid->disableDefaultClick();

    $order_id = new TAction(array($this, 'onReload'));
    $order_id->setParameter('order', 'id');
    $column_id->setAction($order_id);

    $order_nome = new TAction(array($this, 'onReload'));
    $order_nome->setParameter('order', 'nome');
    $column_nome->setAction($order_nome);

    $order_quantidade  = new TAction(array($this, 'onReload'));
    $order_quantidade->setParameter('order', 'quantidade');
    $column_quantidade->setAction($order_quantidade);



    // Ação de edit no datagrid
    $edit = new TDataGridAction(array('CadastroFerramentasForm', 'onEdit'));
    $edit->setButtonClass('btn btn-default');
    $edit->setLabel(_t('Edit'));
    $edit->setImage('far:edit blue');
    $edit->setField('id');
    $this->datagrid->addAction($edit);
    
    // Cria o datagrid
    $this->datagrid->createModel();

    // Cria a navegação da página
    $this->pageNavigation = new TPageNavigation;
    $this->pageNavigation->enableCounters();
    $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
    $this->pageNavigation->setWidth($this->datagrid->getWidth());

    $panel = new TPanelGroup;
    $panel->add($this->datagrid);
    $panel->addFooter($this->pageNavigation);

    // recipiente de caixa vertical
    $container = new TVBox;
    $container->style = 'width: 100%';
    $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
    $container->add($this->form);
    $container->add($panel);

    parent::add($container);
  }
}
