<?php

use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Sabberworm\CSS\Value\Value;

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
class PedidoAprovacaoForm extends TPage
{
    protected $form;
    protected $fieldlist;
    protected $datagrid;
    protected $pageNavigation;

    /**
     * Class constructor
     * Creates the page
     */
    function __construct($param = null)
    {
        TPage::include_css('app/resources/styles.css');
        parent::__construct();

        // creates the form
        $this->form = new BootstrapFormBuilder('form_SaleMultiValue');
        $this->form->setFormTitle('<b>Aprovar solicitação de material</b>');

        TTransaction::open('bancodados');
        $pedido = PedidoMaterial::find($param['id']);
        TTransaction::close();

        // create the form fields
        $id             = new TEntry('id');
        $id->setSize('50%');
        $id->setEditable(FALSE);

        $created             = new TDateTime('created_at');
        $created->setSize('50%');
        $created->setEditable(FALSE);

        $updated             = new TDateTime('updated_at');
        $updated->setSize('50%');
        $updated->setEditable(FALSE);

        $id_item = new TDBCombo('id_item[]', 'bancodados', 'Material', 'id_item', '{id_item} {descricao}');
        $id_item->setSize('90%');
        $id_item->setEditable(FALSE);

        $quantidade = new TEntry('quantidade[]');
        $quantidade->setSize('100%');
        $quantidade->setEditable(FALSE);

        $quantidade_fornecida = new TEntry('quantidade_fornecida[]');

        $status = new TCombo('status');
        $status->addItems(array('PENDENTE' => 'PENDENTE', 'APROVADO' => 'APROVADO', 'REPROVADO' => 'REPROVADO'));
        $status->setSize('50%');

        if ($pedido->status != "PENDENTE") {
            $status->setEditable(FALSE);
            $quantidade_fornecida->setEditable(FALSE);
        }

        //add field 
        $this->fieldlist = new TFieldList;
        $this->fieldlist->generateAria();
        $this->fieldlist->width = '100%';
        $this->fieldlist->name  = 'my_field_list';
        $this->fieldlist->addField('<b>ITEM</b><font color="red">*</font>',  $id_item,  ['width' => '90%']);
        $this->fieldlist->addField('<b>Qtd Solicitada</b><font color="red">*</font>',   $quantidade,   ['width' => '100%']);
        $this->fieldlist->addField('<b>Qtd Fornecida</b><font color="red">*</font>',   $quantidade_fornecida,   ['width' => '10%']);
        $this->form->addField($id_item);
        $this->form->addField($quantidade);
        $this->form->addField($quantidade_fornecida);
        $this->fieldlist->disableRemoveButton();

        $row = $this->form->addFields(
            [$labelInfo = new TLabel('Campos com asterisco (<font color="red">*</font>) são considerados campos obrigatórios')],
        );

        $row = $this->form->addFields(
            [new TLabel('<b>Codigo da solicitação</b>')],
            [$id],
            [new TLabel('<b>Status da solicitação</b>')],
            [$status],
        );
        $row1 = $this->form->addFields(
            [new TLabel('<b>Data da solicitação</b>')],
            [$created],
            [new TLabel('<b>Data da Aprovação</b>')],
            [$updated],
        );
        $row->style = 'margin-top:3rem;';

        // form actions
        $btnBack = $this->form->addActionLink(_t('Back'), new TAction(array('PedidoList', 'onReload')), 'far:arrow-alt-circle-left white');
        $btnBack->style = 'background-color:gray; color:white; border-radius: 0.5rem;';
        if ($pedido->status == "PENDENTE") {
            $btnSave = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save white');
            $btnSave->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';
        }

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%; margin:40px';
        $container->add($this->form);
        parent::add($container);
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('bancodados');
                $pedido = PedidoMaterial::find($param['key']);
                $this->form->setData($pedido); //inserindo dados no formulario. 

                $pivot = PivotPedidoMaterial::where('id_pedido_material', '=', $pedido->id)
                    ->load();

                if ($pivot) {
                    $this->fieldlist->addHeader();
                    foreach ($pivot as $itens => $value) {
                        $obj = new stdClass;
                        $obj->id_item =  $value->id_item;
                        $obj->descricao =  $value->id_item;
                        $obj->quantidade = $value->quantidade;
                        if (
                            ($value->quantidade_fornecida == $value->quantidade)
                            or ($value->quantidade_fornecida == 0)
                        ) {
                            $obj->quantidade_fornecida = $value->quantidade;
                        } else {
                            $obj->quantidade_fornecida = $value->quantidade_fornecida;
                        }

                        $this->fieldlist->addDetail($obj);
                    }
                }
                // add field list to the form
                $this->form->addContent([$this->fieldlist]);
                TTransaction::close();
            } else {
                $this->onClear($param);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onSave($param)
    {
        try {
            $this->form->validate();
            // open a transaction with database 'samples'
            TTransaction::open('bancodados');
            $usuarioLogado = TSession::getValue('userid');
            if ($param['status'] == "PENDENTE") {
                throw new Exception('Não pode aprovar uma solicitação com status "PENDENTE"');
            } else {
                //Verificando se é uma edição ou criação
                if (isset($param["id"]) && !empty($param["id"])) {
                    $pedido = new PedidoMaterial($param["id"]);
                    $pedido->id_usuario = $pedido->id_usuario;
                    $pedido->id_admin = $usuarioLogado;
                    $pedido->status = $param['status'];
                }
                $pedido->store();

                //Delete emprestimo se existe.
                PivotPedidoMaterial::where('id_pedido_material', '=', $pedido->id)->delete();

                $id_item = array_map(function ($value) {
                    return (int)$value;
                }, $param['id_item']);
                $count = count($id_item);

                //Salvando items na tela pivot. 
                if (isset($id_item)) {
                    for ($i = 0; $i < $count; $i++) {
                        $pivot =  new PivotPedidoMaterial();
                        $pivot->id_pedido_material = $pedido->id;
                        $pivot->id_item = $param['id_item'][$i];
                        $pivot->quantidade = $param['quantidade'][$i];
                        $pivot->quantidade_fornecida = $param['quantidade_fornecida'][$i];

                        $items = Material::where('id_item', 'in', $id_item)->load();
                        $item = [];
                        foreach ($items as $key) {
                            $item[] = $key->quantidade_estoque;
                        }
                        if ($item[$i] < $param['quantidade_fornecida'][$i]) {
                            throw new Exception(
                                'A quantidade na linha ' . ($i + 1) .
                                    ' não pode ser maior que a disponível no estoque que é: '
                                    . $item[$i]
                            );
                        } elseif ($param['quantidade'][$i] < $param['quantidade_fornecida'][$i]) {
                            throw new Exception(
                                'A quantidade emprestada na linha ' . ($i + 1) .
                                    ' não pode ser maior que a quantidade solicitada'
                            );
                        } else {
                            $pivot->quantidade_fornecida = $param['quantidade_fornecida'][$i];

                            if ($param['status'] == "REPROVADO") {
                                $result = $item[$i] + $param['quantidade_fornecida'][$i]; //Devolvendo valor para banco.
                                $this->updateQuantidade($pivot->id_item, $result);
                            }
                            if ($param['quantidade'][$i] != $param['quantidade_fornecida'][$i]) {
                                $result = ($item[$i] +
                                    ($param['quantidade'][$i] - $param['quantidade_fornecida'][$i])
                                ); //valor subtraido.
                                $this->updateQuantidade($pivot->id_item, $result);
                            }
                        }
                        $pivot->store();
                    }
                }
            }
            TTransaction::close();
            $action = new TAction(array('PedidoList', 'onReload'));
            new TMessage('info', 'Salvo com sucesso', $action);
        } catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    public function updateQuantidade($id, $value)
    {
        try {
            TTransaction::open('bancodados');
            Material::where('id_item', '=', $id)
                ->set('quantidade_estoque', $value)
                ->update();
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao atualizar valor do banco <br>' . $e->getMessage());
            TTransaction::rollback();
        }
    }
}
