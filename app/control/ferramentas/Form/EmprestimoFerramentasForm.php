<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Wrapper\TDBCombo;
use Sabberworm\CSS\Value\Value;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

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
class EmprestimoFerramentasForm extends TPage
{
    protected $form;
    protected $subFormFirst;
    protected $subFormSecound;
    protected $fieldlist;
    protected $html;


    public function __construct($param = null)
    {
        TPage::include_css('app/resources/styles.css');
        parent::__construct();

        // create form and table container
        $this->form = new BootstrapFormBuilder('form_Emprestimo');
        $this->form->setFormTitle("<b>Solicitação de emprestimo</b>");

        $this->subFormFirst = new BootstrapFormBuilder('subFormFirst');
        $this->subFormSecound = new BootstrapFormBuilder('subFormSecound2');

        $id             = new TEntry('id');
        $id->class = 'emprestimo';
        $id->setEditable(FALSE);
        $id->setSize('50%');

        $created             = new TDateTime('created_at');
        $created->setEditable(FALSE);
        $created->setSize('50%');
        $created->class = 'emprestimo';

        $status             = new TEntry('status');
        $status->setSize('50%');
        $status->setEditable(false);
        $status->class = 'emprestimo';

        $user             = new TEntry('id_usuario');
        $user->setSize('100%');
        $user->setEditable(false);
        $user->class = 'emprestimo';

        $ferramenta = new TDBCombo('ferramenta', 'bancodados', 'Ferramentas', 'id', '{id} - {nome}', 'id');
        $ferramenta->setChangeAction(new TAction(array($this, 'onChange')));
        $ferramenta->setSize('100%');
        $ferramenta->enableSearch();
        $ferramenta->class = 'emprestimo';

        $quantidade = new TEntry('quantidade');
        $quantidade->setSize('50%');
        $quantidade->style =
            'border-radius: 0.25rem;
            border-width: 1px;
            border-style: solid;';

        $quantidadeDisponivel = new TEntry('quantidadeDisponivel');
        $quantidadeDisponivel->setSize('50%');
        $quantidadeDisponivel->setEditable(FALSE);
        $quantidadeDisponivel->class = 'emprestimo';
        $quantidadeDisponivel->style =
            'border-radius: 0.25rem;
            border-width: 1px;
            border-style: solid;';

        TTransaction::open('bancodados');
        $userSession = TSession::getValue('userid');
        $isAdmin = SystemUserGroup::where('system_group_id', '=', 1)->load();

        $crit = new TCriteria();
        $crit->add(new TFilter('id_usuario', '=', $userSession));
        TTransaction::close();

        if (!empty($param['id'])) {
            $ferramenta = new TEntry('ferramenta');
            $ferramenta->setSize('100%');
            $ferramenta->class = 'emprestimo';
            $ferramenta->setEditable(FALSE);
            $quantidade->setEditable(FALSE);
        }

        $row = $this->form->addFields(
            [$labelInfo = new TLabel('Campos com asterisco (<font color="red">*</font>) são considerados campos obrigatórios')],
        );

        $row = $this->form->addFields(
            [$label = new TLabel('<b>id</b>')],
            [$id],
            [$label =  new TLabel('<b>Status</b>')],
            [$status],
        );
        $row = $this->form->addFields(
            [$label = new TLabel('<b>Usuário</b>')],
            [$user],
            [$label = new TLabel('<b>Data</b>')],
            [$created],
        );
        $this->form->id = 'Emprestimo';

        $row = $this->subFormFirst->addFields(
            [new TLabel('<b>Ferramenta</b>')],
            [$ferramenta],
        );
        $row = $this->subFormFirst->addFields(
            [new TLabel('<b>Quantidade</b>')],
            [$quantidade],
            [new TLabel('<b>Quantidade disponivel</b>')],
            [$quantidadeDisponivel],
        );

        if (empty($param['id'])) {
        $addMaterial = TButton::create('addMaterial', [$this, 'onMateriaAdd'], 'Adicionar ferramenta', 'fa:plus-circle green');
        $addMaterial->getAction()->setParameter('static', '1');
        $this->subFormFirst->addFields([], [$addMaterial]);
        $this->form->addContent([$this->subFormFirst]);
        }

        //Grade de materiais
        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->dataGrid->setHeight(150);
        $this->dataGrid->makeScrollable();
        $this->dataGrid->setId('listaFerramentas');
        $this->dataGrid->generateHiddenFields();
        $this->dataGrid->style = "min-width: 700px; width:100%;margin-bottom: 10px";

        $colunaId   = new TDataGridColumn('id', 'Codigo da ferramenta', 'center', '30%');
        $colunaFerramenta   = new TDataGridColumn('ferramenta', 'Ferramenta', 'center', '30%');
        $colunaQuantidade     = new TDataGridColumn('quantidade', 'Quantidade', 'center', '30%');

        $this->dataGrid->addColumn($colunaId);
        $this->dataGrid->addColumn($colunaFerramenta);
        $this->dataGrid->addColumn($colunaQuantidade);

        $action2 = new TDataGridAction([$this, 'onDeleteItem']);
        $action2->setField('ferramenta');
        //$this->dataGrid->addAction($action2, _t('Delete'), 'far:trash-alt red');

        $this->dataGrid->createModel();

        $panel = new TPanelGroup();
        $panel->add($this->dataGrid);
        $panel->getBody()->style = 'overflow-x:auto';
        $this->form->addContent([$panel]);

        // form actions
        $btnBack = $this->form->addActionLink(
            _t('Back'),
            new TAction(array('EmprestimoList', 'onReload')),
            'far:arrow-alt-circle-left white'
        );
        $btnBack->style = 'background-color:gray; color:white; border-radius: 0.5rem;';

        if (empty($param['id'])) {
            $btnSave = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save white');
            $btnSave->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';
        }
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%; margin-top: 2rem';
        $vbox->add($this->form);
        parent::add($vbox);
    }
    /**
     * Metodo identifica se criando ou editando e colocar itens no formulário.
     * @var param request
     * @return View forms 
     */
    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('bancodados');
                $emprestimo = Emprestimo::find($param['id']);
                $this->form->setData($emprestimo); //inserindo dados no formulario. 

                $pivot = PivotEmprestimoFerramentas::where('id_emprestimo', '=', $emprestimo->id)
                    ->load();

                foreach ($pivot as $key) {
                    $ferramenta = Ferramentas::where('id', '=', $key->id_ferramenta)
                        ->load();

                    $id_ferramenta = !empty($ferramenta[0]->id) ? $ferramenta[0]->id : uniqid();
                    $grid_data = [
                        'id'      => $id_ferramenta,
                        'ferramenta'      => $ferramenta[0]->nome,
                        'quantidade'          => $key->quantidade,
                    ];

                    $grid = array_map(function ($value) {
                        return (string)$value;
                    }, $grid_data);

                    // insert row dynamically
                    $row = $this->dataGrid->addItem((object) $grid);
                    $row->id = $id_ferramenta;
                }
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    /**
     * Metodo para salvar solicitação
     * @var param
     * @return void 
     */
    public function onSave($param)
    {
        try {
            $this->form->validate();

            if (empty($param['listaFerramentas_id'])) {
                throw new Exception('O formúlario não pode ser salvo vazio');
            }
            TTransaction::open('bancodados');

            $usuarioLogado = TSession::getValue('userid');

            //Verificando se é uma edição ou criação
            if (isset($param["id"]) && !empty($param["id"])) {
                $emprestimo = new Emprestimo($param["id"]);
                $emprestimo->id_usuario = $usuarioLogado;
                $emprestimo->status = 'PENDENTE';
            } else {
                $emprestimo = new Emprestimo();
                $emprestimo->id_usuario = $usuarioLogado;
                $emprestimo->status = 'PENDENTE';
            }
            $emprestimo->store();

            //Delete emprestimo se existe.
            PivotEmprestimoFerramentas::where('id_emprestimo', '=', $emprestimo->id)
                ->delete();

            $ferramentas = array_map(function ($value) {
                return (int)$value;
            }, $param['listaFerramentas_id']);

            //Salvando items na tela pivot. 
            if (isset($ferramentas)) {
                for ($i = 0; $i < count($ferramentas); $i++) {
                    $pivot =  new PivotEmprestimoFerramentas();
                    $pivot->id_emprestimo = $emprestimo->id;
                    $pivot->id_ferramenta = $param['listaFerramentas_id'][$i];
                    $tools = Ferramentas::where('id', 'in', $ferramentas)
                        ->load();

                    $qtdTools = [];
                    foreach ($tools as $key) {
                        $qtdTools[] = $key->quantidade;
                    }
                    //Verifica se a quantidade solicitada for maior que a do estoque 
                    if (
                        ($param['listaFerramentas_quantidade'][$i] > $qtdTools[$i])
                        or ($param['listaFerramentas_quantidade'][$i] < 0)
                    ) {
                        throw new Exception(
                            'A quantidade na ' . ($i + 1) .
                                '° linha não pode ser maior que a disponível no estoque que é: '
                                . $qtdTools[$i]
                        );
                    } else {
                        $pivot->quantidade = $param['listaFerramentas_quantidade'][$i];
                        $result = $qtdTools[$i] - $param['listaFerramentas_quantidade'][$i]; //valor subtraido.
                        $this->updateQuantidade($param['listaFerramentas_id'][$i], $result);
                    }
                    $pivot->store();
                }
            }

            TTransaction::close();
            $action = new TAction(array('EmprestimoList', 'onReload'));
            new TMessage('info', 'Salvo com sucesso', $action);
        } catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    /**
     * Atualizar a quantidade de ferramentas
     * @var id id da ferramenta
     * @var value valor da ferramenta a ser atualizado
     */
    public function updateQuantidade($id, $value)
    {
        try {
            TTransaction::open('bancodados');
            Ferramentas::where('id', '=', $id)
                ->set('quantidade', $value)
                ->update();
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    public static function onChange($param)
    {
        if (!empty($param['key'])) {
            $obj = new stdClass;
            try {
                TTransaction::open('bancodados');
                $ferramenta = Ferramentas::find($param['key']);
                if (!$ferramenta) {
                    throw new Exception('Material não existe');
                }
                $obj->quantidade = 1;
                $obj->quantidadeDisponivel = $ferramenta->quantidade;
                TForm::sendData('form_Emprestimo', $obj, false, false);
                TTransaction::close();
            } catch (Exception $e) {
                TTransaction::rollback();
                new TMessage('error', $e->getMessage());
            }
        }
    }
    /**
     * Adiciona material na lista
     * @param $param Parametros do request
     */
    public function onMateriaAdd($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            if ((!$data->ferramenta)) {
                throw new Exception('Erro ao adicionar material ao campo');
            }

            TTransaction::open('bancodados');
            $ferramenta = Ferramentas::find($param['ferramenta']);
            TTransaction::close();

            if ($ferramenta->quantidade < $param['quantidade']) {
                throw new Exception(
                    'quantidade nao pode ser maior que a disponivel no estoque'
                );
            }

            $id = !empty($data->ferramenta) ? $data->ferramenta : uniqid();
            $grid_data = [
                'id'      => $id,
                'ferramenta'      => $ferramenta->nome,
                'quantidade'          => $param['quantidade'],
            ];

            $grid = array_map(function ($value) {
                return (string)$value;
            }, $grid_data);

            // insert row dynamically
            $row = $this->dataGrid->addItem((object) $grid);
            $row->id = $id;

            TDataGrid::replaceRowById('listaFerramentas', $id, $row);

            // clear product form fields after add
            $data->id_pedido_material     = '';
            $data->id_item     = '';
            $data->quantidade         = '';

            TForm::sendData('form_Emprestimo', $data, false, false);
        } catch (Exception $e) {
            $action = new TAction(array('EmprestimoFerramentasForm', 'onEdit'));
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage(), $action);
        }
    }

    /**
     * Deleta item da lista. 
     * @param $param Parametros do request
     */
    public static function onDeleteItem($param)
    {
        $data = new stdClass;
        $data->id_item     = '';
        $data->descricao     = '';
        $data->quantidade         = '';

        // send data, do not fire change/exit events
        TForm::sendData('form_Emprestimo', $data, false, false);
        // remove row
        TDataGrid::removeRowById('listaFerramentas', $param['key']);
    }
}
