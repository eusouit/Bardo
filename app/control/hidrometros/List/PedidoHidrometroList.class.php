<?php

use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TDateTime;

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
class PedidoHidrometroList extends TStandardList
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
    TTransaction::open('bancodados');
    $userSession = TSession::getValue('userid');
    $isAdmin = SystemUserGroup::where('system_group_id', '=', 1)->load();

    $crit = new TCriteria();
    $crit->add(new TFilter('id_usuario', '=', $userSession));
    TTransaction::close();
    parent::__construct();

    parent::setDatabase('bancodados');            // DEFINE O BANCO DE DADOS
    parent::setActiveRecord('pedidohd');   // DEFINE O REGISTRO ATIVO
    parent::setDefaultOrder('id', 'desc');         //  DEFINE A ORDEM PADRÃO
    parent::addFilterField('id', '=', 'id'); //  CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    parent::addFilterField('created_at', '=', 'created_at'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    parent::addFilterField('cast(data_time as date)', '=', 'updated_at'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    parent::addFilterField('id_usuario', '=', 'id_usuario');
    if ($userSession == $isAdmin[0]->system_user_id) {
      parent::addFilterField('id_usuario', '=', 'userid'); // CAMPO DE FILTRO, OPERADOR, CAMPO DE FORMULÁRIO
    } else {
      parent::setCriteria($crit);
    }

    // CRIA O FORMULÁRIO

    $this->form = new BootstrapFormBuilder('form_search');
    $this->form->setFormTitle('ESTOQUE UMAS UMES');
   
    TTransaction::open('bancodados');
    $userSession = TSession::getValue('userid');
    $isAdmin = SystemUserGroup::where('system_group_id', '=', 1)->load();
    TTransaction::close();

    // CRIE OS CAMPOS DO FORMULÁRIO

    $id = new TEntry('id');
    $id_usuario = new TDBCombo('id_usuario', 'bancodados', 'SystemUser', 'id', 'matricula');
    $data_pedido = new TDate('created_at');
    $data_aprovacao = new TDate('updated_at');


    // ADICIONE OS CAMPOS

    $this->form->addFields([new TLabel('CODIGO DO PEDIDO')], [$id]);

    $this->form->addFields([new TLabel('MATRICULA')], [$id_usuario]);
    $this->form->addFields([new TLabel('DATA DO PEDIDO')], [$data_pedido]);
    $this->form->addFields([new TLabel('DATA DA APROVAÇÃO')], [$data_aprovacao]);


    $id->setTip('Digite codigo do pedido que voce procura');

    $id_usuario->setTip('Digite a matricula do usuario para efetuar a busca');
    $data_pedido->setTip('Selecione a data do pedido para efetuar a busca');
    $data_aprovacao->setTip('Selecione a data da aprovação do pedido para efetuar a busca');

  
    $id->placeholder = '00000';
    $id->setMask('99999');
    $id->maxlength = 5;
    $id->setSize('35%');


    $id_usuario->setSize('35%');
    $data_pedido->setSize('35%');
    $data_aprovacao->setSize('35%');
    $id_usuario->enableSearch();

    $data_pedido->setMask('dd/mm/yyyy');
    $data_aprovacao->setMask('dd/mm/yyyy');
    
    // MANTENHA O FORMULÁRIO PREENCHIDO DURANTE A NAVEGAÇÃO COM OS DADOS DA SESSÃO
    $this->form->setData(TSession::getValue('cadastro_filter_data'));

    // ADICIONE AS AÇÕES DO FORMULÁRIO DE PESQUISA
    $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
    $this->form->addAction("Novo Item", new TAction(["PedidoHidrometro", "onEdit"]), "fa:plus-circle green");
    $this->form->addAction('Save as PDF', new TAction([$this, 'exportAsPDF'], ['register_state' => 'false']), 'far:file-pdf red');



    $btn->class = 'btn btn-sm btn-primary';

    // CRIA UMA GRADE DE DADOS
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->datatable = 'true';
    $this->datagrid->style = 'width: 100%';
    $this->datagrid->setHeight(320);


    
  
    

    // CRIA AS COLUNAS DA GRADE DE DADOS
    $column_id = new TDataGridColumn('id', 'CODIGO DO PEDIDO', 'center');
    if ($userSession == $isAdmin[0]->system_user_id) {
      $column_id_usuario = new TDataGridColumn('user->name', 'USUÁRIO', 'center');
    } else {
      $column_id_usuario = new TDataGridColumn('user->name', 'USUÁRIO', 'center');
    }

    $column_data_pedido = new TDataGridColumn('created_at', 'DATA DO PEDIDO' , 'center');
    $column_data_aprovacao = new TDataGridColumn('updated_at', 'DATA DA APROVACAO', 'center');
    

    $column_data_pedido->setTransformer(array('helpers', 'formatDate'));
   $column_data_aprovacao->setTransformer(array('helpers', 'formatDate'));
    

    // ADICIONE AS COLUNAS À GRADE DE DADOS
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_id_usuario);
    $this->datagrid->addColumn($column_data_pedido);
    $this->datagrid->addColumn($column_data_aprovacao);
    
   
    // CRIA AS AÇÕES DA COLUNA DA GRADE DE DADOS
    $order_id = new TAction(array($this, 'onReload'));
    $order_id->setParameter('order', 'id');
    $column_id->setAction($order_id);

    $order_id_status = new TAction(array($this, 'onReload'));
    $order_id_status->setParameter('order', 'status');



    $order_id_usuario = new TAction(array($this, 'onReload'));
    $order_id_usuario->setParameter('order', 'id_usuario');
    $column_id_usuario->setAction($order_id_usuario);

    $order_data_pedido = new TAction(array($this, 'onReload'));
    $order_data_pedido->setParameter('created_at', 'created_at');
    $column_data_pedido->setAction($order_data_pedido);

    $order_data_aprovacao = new TAction(array($this, 'onReload'));
    $order_data_aprovacao->setParameter('updated_at', 'updated_at');
    $column_data_aprovacao->setAction($order_data_aprovacao);


   

    $action1 = new TDataGridAction(['PedidoHidrometro', 'onEdit']);
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

    $panel = new TPanelGroup;
    $panel->add($this->datagrid);
    $panel->addFooter($this->pageNavigation);
    
    TScript::create('$(\'#' . self::$formName . '\').collapse(\'toggle\');');
    $this->form->addHeaderActionLink('Filtros de busca', new TAction(array($this, 'toggleSearch')), 'fa:filter green fa-fw');
    
    
    // recipiente de caixa vertical
    $container = new TVBox;
    $container->style = 'width: 100%';
    $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
    $container->add($this->form);
    $container->add($panel);
    $this->datagrid->disableDefaultClick();
    parent::add($container);

    
  }
  public static function onDeleteSessionVar($param)
  {
    $action1 = new TAction(array(__CLASS__, 'deleteSessionVar'));
    $action1->setParameters($param);
    new TQuestion('Tem certeza que quer apagar ?', $action1);
  }

  /**
   * Delete session var
   */
  public static function deleteSessionVar($param)
  {
    try {
      TTransaction::open('bancodados');
      $pedido = pedido::find($param['id']);
      $pedido->Delete();
      AdiantiCoreApplication::gotoPage('PedidoList');
      TTransaction::close();
      new TMessage('info', TAdiantiCoreTranslator::translate('Record deleted')); // success message

    } catch (Exception $e) {
      new TMessage('error', $e->getMessage()); // shows the exception error message
    }
  }
  public function exportAsPDF($param)
  {
    try {
      // string with HTML contents
      $html = clone $this->datagrid;
      $contents = file_get_contents('app/resources/styles-print.html') . $html->getContents();

      // converts the HTML template into PDF
      $dompdf = new \Dompdf\Dompdf();
      $dompdf->loadHtml($contents);
      $dompdf->setPaper('A4', 'landscape');
      $dompdf->render();

      $file = 'app/output/cash-register.pdf';

      // write and open file
      file_put_contents($file, $dompdf->output());

      $window = TWindow::create('Invoice', 0.8, 0.8);
      $object = new TElement('object');
      $object->data  = $file;
      $object->type  = 'application/pdf';
      $object->style = "width: 100%; height:calc(100% - 10px)";
      $object->add('O navegador não suporta a exibição deste conteúdo, <a style="color:#007bff;" target=_newwindow href="' . $object->data . '"> clique aqui para baixar</a>...');

      $window->add($object);
      $window->show();
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
    }
  }
static function toggleSearch()
{
    // também pode apagar esses blocos if/else se não quiser usar a "memória" de estado do form
    if (TSession::getValue('toggleSearch_'.self::$formName) == 1) {
        TSession::setValue('toggleSearch_'.self::$formName,0);
    } else {
        TSession::setValue('toggleSearch_'.self::$formName,1);
    }

    // esta linha é a responsável por abrir/fechar o form
    TScript::create('$(\'#' . self::$formName . '\').collapse(\'toggle\');');
    // caso retire a função de "memória", copie a linha acima para dentro do onSearch,
    // para que o form "permaneça aberto" (reabra automaticamente) ao realizar buscas
}
}


