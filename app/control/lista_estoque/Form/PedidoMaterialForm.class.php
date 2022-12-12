<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TQRCodeInputReader;
use Adianti\Wrapper\BootstrapDatagridWrapper;
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
class PedidoMaterialForm extends TPage
{
    protected $form; //  FORMULÁRIO
    protected $subFormFirst;
    protected $subFormSecound;
    protected $dataGrid;

    function __construct($param)
    {
        TPage::include_css('app/resources/styles.css');
        parent::__construct();

        // cria o formulário
        $this->form = new BootstrapFormBuilder('pedido_Material');
        $this->form->setFormTitle('<b>FORMULARIO DE PEDIDO DE MATERIAL</b>');

        $this->subFormFirst = new BootstrapFormBuilder('subFormFirst');
        $this->subFormSecound = new BootstrapFormBuilder('subFormSecound');

        $id = new TEntry('id');
        $id->setEditable(FALSE);
        $id->setSize('20%');

        $id_item = new TQRCodeInputReader('id_item');
        $id_item->setChangeAction(new TAction(array($this, 'onDecricaoChange')));
        $id_item->placeholder = '00000';
        $id_item->setSize('100%');
        $id_item->setMask('99999');
        $id_item->maxlength = 5;
        $id_item->setSize('50%');

        $status = new TEntry('status');
        $status->setSize('50%');
        $status->setEditable(false);
        $status->class = 'form';

        $descricao = new TDBCombo('descricao', 'bancodados', 'Material', 'id_item', '{id_item} - {descricao}', 'id_item');
        $descricao->setChangeAction(new TAction(array($this, 'onQuantidadeChange')));
        $descricao->setSize('100%');
        $descricao->enableSearch();

        $quantidade = new TSpinner('quantidade');
        $quantidade->setSize('50%');
        $quantidade->setRange(0, 1000, 1);

        $quantidadeDisponivel = new TEntry('quantidadeDisponivel');
        $quantidadeDisponivel->setEditable(FALSE);
        $quantidadeDisponivel->setSize('50%');
        $quantidadeDisponivel->class = 'emprestimo';
        $quantidadeDisponivel->style =
            'border-radius: 0.25rem;
            border-width: 1px;
            border-style: solid;';

        TTransaction::open('bancodados');
        $userSession = TSession::getValue('userid');
        $isAdmin = SystemUserGroup::where('system_group_id', '=', 1)->load();
        TTransaction::close();

        if (
            (!empty($param['id']))
        ) {
            $descricao = new TEntry('descricao');
            $descricao->setSize('100%');
            $id_item->setEditable(false);
            $descricao->setEditable(false);
            $quantidade->setEditable(false);
        }


        $row = $this->form->addFields(
            [$labelInfo = new TLabel(
                '<b>Campos com asterisco (<font color="red">*</font>) são considerados campos obrigatórios</b>'
            )],
        );

        $row = $this->form->addFields(
            [$label = new TLabel('<b>Id</b>')],
            [$id],
            [$label =  new TLabel('<b>Status</b>')],
            [$status],
        );
        $row = $this->subFormFirst->addFields(
            [$label = new TLabel('<b>Codigo item</b>')],
            [$id_item],
            [$label = new TLabel('<b>Material</b>')],
            [$descricao],
        );
        $row = $this->subFormFirst->addFields(
            [$label =  new TLabel('<b>Quantidade</b>')],
            [$quantidade],
            [$label =  new TLabel('<b>Quantidade disponivel</b>')],
            [$quantidadeDisponivel],
        );
        if (empty($param['id'])) {
        $addMaterial = TButton::create('addMaterial', [$this, 'onMateriaAdd'], 'Adicionar material', 'fa:plus-circle green');
        $addMaterial->getAction()->setParameter('static', '1');
        $this->subFormFirst->addFields([], [$addMaterial]);
        $this->form->addContent([$this->subFormFirst]);
        }
        //Grade de materiais
        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->dataGrid->setHeight(150);
        $this->dataGrid->makeScrollable();
        $this->dataGrid->setId('listaMaterial');
        $this->dataGrid->generateHiddenFields();
        $this->dataGrid->style = "min-width: 700px; width:100%;margin-bottom: 10px";

        $colunaIditem   = new TDataGridColumn('id_item', 'Codigo item', 'center', '30%');
        $colunaDescicao   = new TDataGridColumn('descricao', 'Descricao', 'center', '30%');
        $colunaQuantidade     = new TDataGridColumn('quantidade', 'Quantidade', 'center', '30%');

        $this->dataGrid->addColumn($colunaIditem);
        $this->dataGrid->addColumn($colunaDescicao);
        $this->dataGrid->addColumn($colunaQuantidade);

        $action2 = new TDataGridAction([$this, 'onDeleteItem']);
        $action2->setField('descricao');
        //$this->dataGrid->addAction($action2, _t('Delete'), 'far:trash-alt red');

        $this->dataGrid->createModel();

        $panel = new TPanelGroup();
        $panel->add($this->dataGrid);
        $panel->getBody()->style = 'overflow-x:auto';
        $this->form->addContent([$panel]);

        // form actions
        $btnBack = $this->form->addActionLink(
            _t('Back'),
            new TAction(array('PedidoList', 'onReload')),
            'far:arrow-alt-circle-left white'
        );
        $btnBack->style = 'background-color:gray; color:white; border-radius: 0.5rem;';

        if (empty($param['id'])) {
            $btnSave = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save white');
            $btnSave->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';
        }

        $vbox = new TVBox;
        $vbox->style = 'width: 100%; margin-top: 2rem';
        $vbox->add($this->form);
        parent::add($vbox);
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('bancodados');
                $pedidoMaterial = PedidoMaterial::find($param['id']);
                $this->form->setData($pedidoMaterial); //inserindo dados no formulario. 

                $pivot = PivotPedidoMaterial::where('id_pedido_material', '=', $pedidoMaterial->id)
                    ->load();

                foreach ($pivot as $key) {
                    $material = Material::where('id_item', '=', $key->id_item)
                        ->load();

                    $id_item = !empty($material[0]->id_item) ? $material[0]->id_item : uniqid();
                    $grid_data = [
                        'id_item'      => $id_item,
                        'descricao'      => $material[0]->descricao,
                        'quantidade'          => $pivot[0]->quantidade,
                    ];

                    $grid = array_map(function ($value) {
                        return (string)$value;
                    }, $grid_data);

                    // insert row dynamically
                    $row = $this->dataGrid->addItem((object) $grid);
                    $row->id = $id_item;
                }

                TDataGrid::replaceRowById('listaMaterial', $id_item, $row);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onSave($param)
    {
        try {
            $data = $this->form->getData();
            $this->form->validate();

            if (
                empty($param['listaMaterial_descricao'])
                or empty($param['listaMaterial_id_item'])
                ) {
                throw new Exception('O formúlario não pode ser salvo vazio');
            }
            TTransaction::open('bancodados');
            $usuarioLogado = TSession::getValue('userid');

            if (isset($param["id"]) && !empty($param["id"])) {
                $object = new PedidoMaterial($param["id"]);
                $object->id_usuario = $usuarioLogado;
                $object->status = 'PENDENTE';
            } else {
                $object = new PedidoMaterial();
                $object->id_usuario = $usuarioLogado;
                $object->status = 'PENDENTE';
            }
            $object->store();

            PivotPedidoMaterial::where('id_pedido_material', '=', $object->id)
                ->delete();

            $descricao = array_map(function ($value) {
                return (int)$value;
            }, $param['listaMaterial_descricao']);

            if (isset($descricao)) {
                for ($i = 0; $i < count($descricao); $i++) {
                    $pivot = new PivotPedidoMaterial();
                    $pivot->id_pedido_material = $object->id;
                    $pivot->id_item = $param['listaMaterial_id_item'][$i];

                    $tools = Material::where('id_item', 'in', $param['listaMaterial_id_item'])
                        ->load();
                    $qtdTools = [];
                    foreach ($tools as $key) {
                        $qtdTools[] = $key->quantidade_estoque;
                    }

                    //Verifica se a quantidade solicitada for maior que a do estoque 
                    if (
                        $param['listaMaterial_quantidade'][$i] > $qtdTools[$i]
                        or $param['listaMaterial_quantidade'][$i] < 0
                    ) {
                        throw new Exception(
                            'A quantidade na ' . ($i + 1) .
                                '° linha não pode ser maior que a disponível no estoque que é: '
                                . $qtdTools[$i]
                        );
                    } else {
                        $pivot->quantidade = $param['listaMaterial_quantidade'][$i];
                        $result = $qtdTools[$i] - $param['listaMaterial_quantidade'][$i]; //valor subtraido.
                        $this->updateQuantidade($param['listaMaterial_id_item'][$i], $result);
                    }
                    $pivot->store();
                }
            }
            TForm::sendData('pedido_Material', (object) ['id_item' => $object->id_item]);

            TTransaction::close(); // close the transaction
            $action = new TAction(array('PedidoList', 'onReload'));
            new TMessage('info', 'Salvo com sucesso', $action);
        } catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollbackAll();

            $action = new TAction(array('PedidoMaterialForm', 'onEdit'));
            new TMessage('error', $e->getMessage(), $action);
        }
    }
    /**
     * Atualiza a quantidade de material no estoque.
     * @var $id Id do material
     * @var $value valor da nova quantidade.
     * @return void
     */
    public function updateQuantidade($id, $value)
    {
        try {
            TTransaction::open('bancodados');
            Material::where('id_item', '=', $id)
                ->set('quantidade_estoque', $value)
                ->update();
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    /**
     * Preenche os campos do primeiro formulario.
     * @param $param Parametros do request
     * @return void
     */
    public function onDecricaoChange($param)
    {
        if (!empty($param['key'])) {
            $obj = new stdClass;

            try {
                TTransaction::open('bancodados');
                $material = Material::find($param['key']);

                if (!$material) {
                    throw new Exception('Material não existe');
                }
                $obj->descricao = $material->id_item;
                $obj->quantidade = 1;
                $obj->quantidadeDisponivel = $material->quantidade_estoque;
                TForm::sendData('pedido_Material', $obj, false, false);
                TTransaction::close();
            } catch (Exception $e) {
                TTransaction::rollback();
                new TMessage('error', $e->getMessage());
            }
        }
    }
    /**
     * Preenche os campos do primeiro formulario.
     * @param $param Parametros do request
     * @return void
     */
    public static function onQuantidadeChange($param)
    {
        if (!empty($param['key'])) {
            $obj = new stdClass;

            try {
                TTransaction::open('bancodados');
                $material = Material::find($param['key']);
                if (!$material->id_item) {
                    throw new Exception('Material não existe');
                }
                $obj->id_item = $material->id_item;
                $obj->quantidade = 1;
                $obj->quantidadeDisponivel = $material->quantidade_estoque;
                TForm::sendData('pedido_Material', $obj, false, false);
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
            if ((!$data->descricao)) {
                throw new Exception('Erro ao adicionar material ao campo');
            }

            TTransaction::open('bancodados');
            $material = Material::find($param['descricao']);
            TTransaction::close();

            //Verificando se a quantidade solicitada é maior que a do estoque
            if ($material->quantidade_estoque < $param['quantidade']) {
                throw new Exception(
                    'quantidade nao pode ser maior que a disponivel no estoque'
                );
            }

            $uniqid = !empty($data->descricao) ? $data->descricao : uniqid();
            //Adicionando paramentros no array 
            $grid_data = [
                'id_item'      => $uniqid,
                'descricao'      => $material->descricao,
                'quantidade'          => $param['quantidade'],
            ];

            //Convertendo array para string 
            $grid = array_map(function ($value) {
                return (string)$value;
            }, $grid_data);

            // insert row dynamically
            $row = $this->dataGrid->addItem((object) $grid);
            $row->id = $uniqid;

            TDataGrid::replaceRowById('listaMaterial', $uniqid, $row);

            // Limpando campos do formulario
            $data->id_item     = '';
            $data->descricao     = '';
            $data->quantidade         = '';
            $data->quantidadeDisponivel         = '';

            // send data, do not fire change/exit events
            TForm::sendData('pedido_Material', $data, false, false);
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
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
        TForm::sendData('pedido_Material', $data, false, false);
        // remove row
        TDataGrid::removeRowById('listaMaterial', $param['key']);
    }
}
