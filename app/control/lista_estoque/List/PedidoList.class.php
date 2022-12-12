<?php

use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

/**
 * LISTA DE MATERIAS EM ESTOQUE
 *
 * @version    1.0
 * @package    model
 * @subpackage DEPOSITO DE MATERIAS UMAS E UMES
 * @author     PEDRO FELIPE FREIRE DE MEDEIROS
 * @copyright  Copyright (c) 2021 Barata
 * @license    http://www.adianti.com.br/framework-license
 */
class PedidoList extends TStandardList
{
  protected $form;     // FORMULÁRIO DE REGISTRO
  protected $datagrid; //  LISTAGEM
  protected $pageNavigation;
  protected $formgrid;
  protected $deleteButton;
  protected $transformCallback;
  private static $formName = 'form_search';
  // CONSTRUTOR DE PÁGINA
  public function __construct()
  {
    TStandardList::include_css('app/resources/styles.css');
    TTransaction::open('bancodados');
    $userSession = TSession::getValue('userid');
    $isAdmin = SystemUserGroup::where('system_group_id', '=', 1)->load();

    $crit = new TCriteria();
    $crit->add(new TFilter('id_usuario', '=', $userSession));
    TTransaction::close();
    parent::__construct();

    parent::setDatabase('bancodados');            // DEFINE O BANCO DE DADOS
    parent::setActiveRecord('PedidoMaterial');   // DEFINE O REGISTRO ATIVO
    parent::setDefaultOrder('id', 'desc');         //  DEFINE A ORDEM PADRÃO
    parent::addFilterField('id', '=', 'id'); //  CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    parent::addFilterField('status', '=', 'status'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    parent::addFilterField('created_at', 'like', 'created_at'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    //parent::addFilterField('update_at', '=', 'updated_at'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    parent::addFilterField('id_usuario', '=', 'id_usuario');
    if ($userSession == $isAdmin[0]->system_user_id) {
      parent::addFilterField('id_usuario', '=', 'userid'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    } else {
      parent::setCriteria($crit);
    }
    parent::addFilterField('update_at', 'like', 'update_at'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO

    // CRIA O FORMULÁRIO

    $this->form = new BootstrapFormBuilder('form_search');
    $this->form->setFormTitle('ESTOQUE UMAS UMES');

    TTransaction::open('bancodados');
    $userSession = TSession::getValue('userid');
    $isAdmin = SystemUserGroup::where('system_group_id', '=', 1)->load();
    TTransaction::close();

    // CRIE OS CAMPOS DO FORMULÁRIO

    $id = new TEntry('id');
    $id->id = "input-form";
    $id->setMask('99999');
    $id->maxlength = 5;
    $id->setSize('50%');

    $status = new TCombo('status');
    $status->id = "input-form";
    $status->addItems(array('PENDENTE' => 'PENDENTE', 'APROVADO' => 'APROVADO', 'REPROVADO' => 'REPROVADO'));
    $status->setDefaultOption('Selecionar');
    $status->setSize('50%');

    if ($userSession == $isAdmin[0]->system_user_id) {
      $id_usuario = new TDBCombo('id_usuario', 'bancodados', 'SystemUser', 'id', 'matricula');
      $id_usuario->enableSearch();
    } else {
      $id_usuario = new THidden('id_usuario');
    }
    $id_usuario->id = "input-form";
    $id_usuario->setSize('100%');

    $data_pedido = new TDate('created_at');
    $data_pedido->id = "input-form";
    $data_pedido->setSize('50%');
    $data_pedido->setMask('dd/mm/yyyy');

    $data_aprovacao = new TDate('updated_at');
    $data_aprovacao->id = "input-form";
    $data_aprovacao->setSize('50%');
    $data_aprovacao->setMask('dd/mm/yyyy');

    // ADICIONE OS CAMPOS
    if ($userSession == $isAdmin[0]->system_user_id) {
      $this->form->addFields(
        [new TLabel('Matricula')],
        [$id_usuario]
      );
    }
    $this->form->addFields(
      [new TLabel('Codigo do pedido')],
      [$id],
      [new TLabel('Status')],
      [$status],

    );
    $this->form->addFields(
      [new TLabel('Data do pedido')],
      [$data_pedido],
      [new TLabel('Data da aprovação')],
      [$data_aprovacao]
    );

    // MANTENHA O FORMULÁRIO PREENCHIDO DURANTE A NAVEGAÇÃO COM OS DADOS DA SESSÃO
    $this->form->setData(TSession::getValue('cadastro_filter_data'));

    // ADICIONE AS AÇÕES DO FORMULÁRIO DE PESQUISA
    $btnFind = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
    $btnFind->style = 'background-color:#2c7097; color:white; border-radius: 0.5rem;';
    $btn = $this->form->addAction("Solicitar material", new TAction(["PedidoMaterialForm", "onEdit"]), "fa:plus-circle white");
    $btn->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';

    // CRIA UMA GRADE DE DADOS
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->datatable = 'true';
    $this->datagrid->style = 'width: 100%';
    $this->datagrid->setHeight(320);

    // CRIA AS COLUNAS DA GRADE DE DADOS
    $column_id = new TDataGridColumn('id', 'Codigo do pedido', 'center');
    $column_status = new TDataGridColumn('status', 'Status', 'center');
    if ($userSession == $isAdmin[0]->system_user_id) {
      $column_id_usuario = new TDataGridColumn('user->name', 'Usuário', 'center');
    } else {
      $column_id_usuario = new TDataGridColumn('user->name', 'Usuário', 'center');
    }

    $column_data_pedido = new TDataGridColumn('created_at', 'Data do pedido', 'center');
    $column_data_aprovacao = new TDataGridColumn('updated_at', 'Data da aprovacao', 'center');

    $column_data_pedido->setTransformer(array('helpers', 'formatDate'));
    $column_data_aprovacao->setTransformer(array('helpers', 'formatDate'));

    // ADICIONE AS COLUNAS À GRADE DE DADOS
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_status);
    $this->datagrid->addColumn($column_id_usuario);
    $this->datagrid->addColumn($column_data_pedido);
    $this->datagrid->addColumn($column_data_aprovacao);

    // CRIA AS AÇÕES DA COLUNA DA GRADE DE DADOS
    $order_id = new TAction(array($this, 'onReload'));
    $order_id->setParameter('order', 'id');
    $column_id->setAction($order_id);

    $order_id_status = new TAction(array($this, 'onReload'));
    $order_id_status->setParameter('order', 'status');
    $column_status->setAction($order_id_status);

    $order_id_usuario = new TAction(array($this, 'onReload'));
    $order_id_usuario->setParameter('order', 'id_usuario');
    $column_id_usuario->setAction($order_id_usuario);

    $order_data_pedido = new TAction(array($this, 'onReload'));
    $order_data_pedido->setParameter('created_at', 'created_at');
    $column_data_pedido->setAction($order_data_pedido);

    $order_data_aprovacao = new TAction(array($this, 'onReload'));
    $order_data_aprovacao->setParameter('updated_at', 'updated_at');
    $column_data_aprovacao->setAction($order_data_aprovacao);

    // CRIAR AÇÃO EDITAR
    $action_edit = new TDataGridAction(array('PedidoMaterialForm', 'onEdit'));
    $action_edit->setButtonClass('btn btn-default');
    $action_edit->setLabel('Visusalizar Pedido');
    $action_edit->setImage('fas:eye blue');
    $action_edit->setField('id');
    $this->datagrid->addAction($action_edit);


    $action1 = new TDataGridAction(['PedidoAprovacaoForm', 'onEdit']);
    $action1->setField('id');
    if ($userSession == $isAdmin[0]->system_user_id)
      $this->datagrid->addAction($action1, 'Aprovar solicitação', 'fa:check-circle background-color:#218231');

    // CRIAR O MODELO DE GRADE DE DADOS
    $this->datagrid->createModel();

    // CRIAR A NAVEGAÇÃO DA PÁGINA
    $this->pageNavigation = new TPageNavigation;
    $this->pageNavigation->enableCounters();
    $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
    $this->pageNavigation->setWidth($this->datagrid->getWidth());

    $panel = new TPanelGroup();
    $panel->add($this->datagrid);
    $panel->addFooter($this->pageNavigation);

    $this->form->addHeaderActionLink('Filtros de busca', new TAction(array($this, 'toggleSearch')), 'fa:filter green fa-fw');
    TScript::create('$(\'#' . self::$formName . '\').collapse(\'toggle\');');

    // recipiente de caixa vertical
    $container = new TVBox;
    $container->style = 'width: 100%';
    $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
    $container->add($this->form);
    $container->add($panel);
    $this->datagrid->disableDefaultClick();
    parent::add($container);
  }
  /**
   * Funçao para ocultar o campo de busca
   */
  static function toggleSearch()
  {
    // também pode apagar esses blocos if/else se não quiser usar a "memória" de estado do form
    if (TSession::getValue('toggleSearch_' . self::$formName) == 1) {
      TSession::setValue('toggleSearch_' . self::$formName, 0);
    } else {
      TSession::setValue('toggleSearch_' . self::$formName, 1);
    }

    // esta linha é a responsável por abrir/fechar o form
    TScript::create('$(\'#' . self::$formName . '\').collapse(\'toggle\');');
    // caso retire a função de "memória", copie a linha acima para dentro do onSearch,
    // para que o form "permaneça aberto" (reabra automaticamente) ao realizar buscas
  }
}
