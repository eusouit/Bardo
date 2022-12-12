<?php

use Adianti\Base\TStandardList;

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
class MaterialList extends TStandardList
{

  protected $form;     // FORMULÁRIO DE REGISTRO
  protected $subform;     // FORMULÁRIO DE REGISTRO
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
    parent::__construct();

    parent::setDatabase('bancodados');            // DEFINE O BANCO DE DADOS
    parent::setActiveRecord('Material');   // DEFINE O REGISTRO ATIVO
    parent::setDefaultOrder('descricao', 'asc');         //  DEFINE A ORDEM PADRÃO
    parent::addFilterField('id_item', '=', 'id_item'); //  CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    parent::addFilterField('descricao', '=', 'descricao'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO

    // CRIA O FORMULÁRIO

    $this->form = new BootstrapFormBuilder('form_search');
    $this->form->setFormTitle('ESTOQUE UMAS UMES');
    
    $this->subform = new BootstrapFormBuilder('form_search');
    TTransaction::open('bancodados');
    $userSession = TSession::getValue('userid');
    $isAdmin = SystemUserGroup::where('system_group_id', '=', 1)->load();
    TTransaction::close();

    // CRIE OS CAMPOS DO FORMULÁRIO

    $id = new TQRCodeInputReader('id_item');
    $id->setSize('50%');
    $id->placeholder = '00000';
    $id->setMask('99999');
    $id->maxlength = 5;
    $id->setTip('Digite o codigo do item desejado');
    $id->id = "input-form";

    $descricao = new TDBCombo('descricao', 'bancodados', 'Material', 'descricao', 'descricao');
    $descricao->enableSearch();
    $descricao->setSize('50%');
    $descricao->setTip('Digite a descrição do item desejado');

    // ADICIONE OS CAMPOS

    $this->form->addFields([new TLabel('Codígo do item')], [$id]);
    $this->form->addFields([new TLabel('Descrição')], [$descricao]);

    // MANTENHA O FORMULÁRIO PREENCHIDO DURANTE A NAVEGAÇÃO COM OS DADOS DA SESSÃO
    $this->form->setData(TSession::getValue('cadastro_filter_data'));

    // ADICIONE AS AÇÕES DO FORMULÁRIO DE PESQUISA
    $btn = $this->form->addAction('Buscar', new TAction(array($this, 'onSearch')), 'fa:search white');
    $btn->style = 'background-color:#2c7097; color:white; border-radius: 0.5rem;';
    if ($userSession == $isAdmin[0]->system_user_id) {
      $btn = $this->form->addAction("Cadastrar material", new TAction(["CadastroMaterialForm", "onEdit"]), "fa:plus-circle white");
      $btn->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';
    } else {
    }

    // CRIA UMA GRADE DE DADOS
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->datatable = 'true';
    $this->datagrid->style = 'width: 100%';
    $this->datagrid->setHeight(320);

    // CRIA AS COLUNAS DA GRADE DE DADOS
    $column_id = new TDataGridColumn('id_item', 'Codigo do item', 'center', 50);
    $column_descricao = new TDataGridColumn('descricao', 'Descrição', 'left');
    $column_quantidade_estoque = new TDataGridColumn('quantidade_estoque', 'quantidade em estoque', 'left');
    $column_created = new TDataGridColumn('created_at', 'Data do cadastro', 'left');

    // ADICIONE AS COLUNAS À GRADE DE DADOS
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_descricao);
    $this->datagrid->addColumn($column_quantidade_estoque);
    $this->datagrid->addColumn($column_created);

    $column_created->setTransformer(array('helpers', 'formatDate'));

    // CRIA AS AÇÕES DA COLUNA DA GRADE DE DADOS
    $order_id = new TAction(array($this, 'onReload'));
    $order_id->setParameter('order', 'id_item');
    $column_id->setAction($order_id);

    $order_descricao = new TAction(array($this, 'onReload'));
    $order_descricao->setParameter('order', 'descricao');
    $column_descricao->setAction($order_descricao);

    $order_quantidade_estoque  = new TAction(array($this, 'onReload'));
    $order_quantidade_estoque->setParameter('order', 'quantidade_estoque');
    $column_quantidade_estoque->setAction($order_quantidade_estoque);

    $order_update_at  = new TAction(array($this, 'onReload'));
    $order_update_at->setParameter('order', 'updated_at');
    $column_created->setAction($order_update_at);

    // CRIAR AÇÃO EDITAR
    $action_edit = new TDataGridAction(array('CadastroMaterialForm', 'onEdit'));
    $action_edit->setButtonClass('btn btn-default');
    $action_edit->setLabel(_t('Edit'));
    $action_edit->setImage('far:edit blue');
    $action_edit->setField('id_item');
    $this->datagrid->addAction($action_edit);

    $this->form->addHeaderActionLink('Filtros de busca', new TAction(array($this, 'toggleSearch')), 'fa:filter green fa-fw');
    TScript::create('$(\'#' . self::$formName . '\').collapse(\'toggle\');');
    // CRIAR O MODELO DE GRADE DE DADOS
    $this->datagrid->createModel();

    // CRIAR A NAVEGAÇÃO DA PÁGINA
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
