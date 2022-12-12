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
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

/**
 * LISTA DE EMPRESTIMO
 *
 * @version    1.0
 * @package    model
 * @subpackage DEPOSITO DE MATERIAS UMAS E UMES
 * @author     PEDRO FELIPE FREIRE DE MEDEIROS
 * @copyright  Copyright (c) 2021 Barata
 * @license    http://www.adianti.com.br/framework-license
 */
class EmprestimoList extends TStandardList
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
    parent::setActiveRecord('Emprestimo');   // DEFINE O REGISTRO ATIVO
    parent::setDefaultOrder('id', 'desc');         //  DEFINE A ORDEM PADRÃO


    parent::addFilterField('id', '=', 'id'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    if ($userSession == $isAdmin[0]->system_user_id) {
      parent::addFilterField('id_usuario', '=', 'id_usuario'); //  CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    } else {
      parent::setCriteria($crit);
    }
    parent::addFilterField('status', '=', 'status'); //  CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    parent::addFilterField('created_at', 'like', 'created_at');


    // CRIA O FORMULÁRIO
    $this->form = new BootstrapFormBuilder('form_search');
    $form = $this->form->setFormTitle('Emprestimo de ferramentas');

    // CRIE OS CAMPOS DO FORMULÁRIO
    $unique = new TDBUniqueSearch('FerramentaList', 'bancodados', 'emprestimo', 'id', 'id');
    $unique->id = "input-form";
    $unique->setMinLength(0);
    $unique->setMask('{id}');
    $unique->setSize('150%');
    $unique->placeholder = 'Númeração da solicitação';
    
    $status = new TCombo('status');
    $status->addItems(['PENDENTE' => 'PENDENTE', 'APROVADO' => 'APROVADO', 'DEVOLVIDO' => 'DEVOLVIDO']);
    $status->id = "input-form";
    
    $data = new TDate('created_at');
    $data->id = "input-form";
    $data->placeholder = 'Data de criação';
    $data->setMask('dd/mm/yyyy');
    $data->setSize('100%');

    // ADICIONE OS CAMPOS
    $row = $this->form->addFields(
      [new TLabel('Número da solicitação')],
      [$unique],
      [new Tlabel('Status')],
      [$status],
      [new Tlabel('Data')],
      [$data],
    );

    $row = $this->form->addFields();

    // MANTENHA O FORMULÁRIO PREENCHIDO DURANTE A NAVEGAÇÃO COM OS DADOS DA SESSÃO
    $this->form->setData(TSession::getValue('cadastro_filter_data'));

    // ADICIONE AS AÇÕES DO FORMULÁRIO DE PESQUISA
    $btn = $this->form->addAction('Buscar', new TAction(array($this, 'onSearch')), 'fa:search white');
    $btn->style = 'background-color:#2c7097; color:white; border-radius: 0.5rem;';
    $btn = $this->form->addAction("Solicitar emprestimo", new TAction(array('EmprestimoFerramentasForm', "onEdit")), "fa:plus-circle white");
    $btn->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';

    // CRIA UMA GRADE DE DADOS
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->datatable = 'true';
    $this->datagrid->style = 'width: 100%; border-radius: 20rem;';
    $this->datagrid->setHeight(320);

    // CRIA AS COLUNAS DA GRADE DE DADOS

    $column_id = new TDataGridColumn('id', 'Numeração', 'center', 50);
    $column_usuario = new TDataGridColumn('User->name', 'Usuário', 'center');
    $column_status = new TDataGridColumn('status', 'Status', 'center');
    $column_created = new TDataGridColumn('created_at', 'Data da solicitação', 'center');

    $column_created->setTransformer(array('helpers', 'formatDate'));

    // ADICIONE AS COLUNAS À GRADE DE DADOS
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_usuario);
    $this->datagrid->addColumn($column_status);
    $this->datagrid->addColumn($column_created);
    $this->datagrid->disableDefaultClick();

    // Action edit
    $action_edit = new TDataGridAction(array('EmprestimoFerramentasForm', 'onEdit'));
    $action_edit->setField('id');
    $this->datagrid->addAction($action_edit, 'Visualizar solicitação', 'fas:eye blue');

    // Visualização da solicitação para o admin. 
    $action1 = new TDataGridAction(['AprovacaoSolicitacaoForm', 'onEdit']);
    $action1->setField('id');
    if ($userSession == $isAdmin[0]->system_user_id)
      $this->datagrid->addAction($action1, 'Visualizar solicitação', 'fa:check-circle background-color:#218231');

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
    TScript::create('$(\'#' . self::$formName . '\').collapse(\'toggle\');');//aberto

    // recipiente de caixa vertical
    $container = new TVBox;
    $container->style = 'width: 100%';
    $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
    $container->add($this->form);
    $container->add($panel);

    parent::add($container);
    TTransaction::close(); // fecha a transação.
  }

  static function toggleSearch()
  {
    // também pode apagar esses blocos if/else se não quiser usar a "memória" de estado do form
    if (TSession::getValue('toggleSearch_' . self::$formName) == 1) {
      TSession::setValue('toggleSearch_' . self::$formName, 0);
    } else {
      TSession::setValue('toggleSearch_' . self::$formName, 1);
    }

    // esta linha é a responsável por abrir/fechar o form
    TScript::create('$(\'#' . self::$formName . '\').collapse(\'toggle\');');//aberto
    //TScript::create('$(\'#' . self::$formName . '\').addClass(\'collapse\');');//fechado
    // caso retire a função de "memória", copie a linha acima para dentro do onSearch,
    // para que o form "permaneça aberto" (reabra automaticamente) ao realizar buscas
  }
}
