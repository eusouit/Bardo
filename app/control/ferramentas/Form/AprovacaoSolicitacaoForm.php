<?php

use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
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
class AprovacaoSolicitacaoForm extends TPage
{
    protected $form;
    protected $subFormFirst;
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
        $this->form->setFormTitle('Aprovar solicitação de material');

        $this->subFormFirst = new BootstrapFormBuilder('subFormFirst');

        TTransaction::open('bancodados');
        $emprestimo = new Emprestimo($param['id']);
        TTransaction::close();

        // create the form fields
        $id             = new TEntry('id');
        $id->setSize('50%');
        $id->setEditable(FALSE);
        $id->class = 'emprestimo';
        $id->style =
            'border-radius: 0.25rem;
        border-width: 1px;
        border-style: solid;';

        $created             = new TDateTime('created_at');
        $created->class = 'emprestimo';
        $created->setSize('95%');
        $created->setEditable(FALSE);

        $status             = new TCombo('status');
        $status->setSize('100%');
        $status->setDefaultOption(false);
        $status->class = 'emprestimo';
        $status->addItems(['PENDENTE' => 'PENDENTE', 'APROVADO' => 'APROVADO', 'DEVOLVIDO' => 'DEVOLVIDO']);

        $user             = new TEntry('id_usuario');
        $user->setSize('100%');
        $user->setEditable(false);
        $user->class = 'emprestimo';

        $ferramenta = new TDBCombo('ferramenta[]', 'bancodados', 'Ferramentas', 'id', '{id} - {nome}', 'id');
        $ferramenta->setSize('100%');
        $ferramenta->setEditable(FALSE);
        $ferramenta->class = 'emprestimo';

        $quantidade = new TEntry('quantidade[]'); //quantidade de ferramentas solicitadas
        $quantidade->setSize('100%');
        $quantidade->setEditable(FALSE);
        $quantidade->class = 'emprestimo';

        $qtdEmprestada = new TEntry('qtd_emprestada[]'); //quantidade de ferramentas a serem emprestadas. 
        $qtdEmprestada->class = 'emprestimo';
        $qtdEmprestada->setSize('50%');

        if ($emprestimo->status == "DEVOLVIDO") {
            $qtdEmprestada->class = 'emprestimo';
            $status->setEditable(FALSE);
            $qtdEmprestada->setEditable(FALSE);
        } elseif ($emprestimo->status == "APROVADO") {
            $qtdEmprestada->class = 'emprestimo';
            $qtdEmprestada->setEditable(FALSE);
        }

        //add field 
        $this->fieldlist = new TFieldList;
        $this->fieldlist->generateAria();
        $this->fieldlist->width = '100%';
        $this->fieldlist->name  = 'my_field_list';
        $this->fieldlist->addField('<b>Ferramenta</b>',  $ferramenta,  ['width' => '90%'], new TRequiredValidator);
        $this->fieldlist->addField('<b>Qtd solicitada</b>',   $quantidade,   ['width' => '100%'], new TRequiredValidator);
        $this->fieldlist->addField('<b>Qtd emprestada</b><font color="red">*</font>',   $qtdEmprestada,   ['width' => '10%'], new TRequiredValidator);

        $row = $this->form->addFields(
            [$labelInfo = new TLabel('Campos com asterisco (<font color="red">*</font>) são considerados campos obrigatórios')],
        );

        $row = $this->subFormFirst->addFields(
            [$label = new TLabel('<b>id</b>')],
            [$id],
            [$label =  new TLabel('<b>Status</b>')],
            [$status],
        );
        $row = $this->subFormFirst->addFields(
            [$label = new TLabel('<b>Usuário</b>')],
            [$user],
            [$label = new TLabel('<b>Data</b>')],
            [$created],
        );
        $row->style = 'margin-top:3rem;';
        $this->form->addContent([$this->subFormFirst]);
        //$status->setValue('EFETUADO');

        //add itens ao field list
        $this->form->addField($ferramenta);
        $this->form->addField($quantidade);
        $this->form->addField($qtdEmprestada);
        $this->fieldlist->disableRemoveButton();

        // form actions
        $btnBack = $this->form->addActionLink(_t('Back'), new TAction(array('EmprestimoList', 'onReload')), 'far:arrow-alt-circle-left white');
        $btnBack->style = 'background-color:gray; color:white; border-radius: 0.5rem;';
        $btnSave = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save white');
        $btnSave->style = 'background-color:#218231; color:white; border-radius: 0.5rem;';

        if ($emprestimo->status == "DEVOLVIDO") {
            $btnSave->style = 'display:none;';
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
                $emprestimo = Emprestimo::find($param['key']);
                $this->form->setData($emprestimo); //inserindo dados no formulario. 

                $pivot = PivotEmprestimoFerramentas::where('id_emprestimo', '=', $emprestimo->id)->load();
                if ($pivot) {
                    $this->fieldlist->addHeader();
                    foreach ($pivot as $itens => $value) {
                        $obj = new stdClass;
                        $obj->ferramenta = intval($value->id_ferramenta);
                        $obj->quantidade = $value->quantidade;

                        if (
                            ($value->qtd_emprestada == $value->quantidade)
                            or ($value->qtd_emprestada == 0)
                        ) {
                            $obj->qtd_emprestada = $value->quantidade;
                        } else {
                            $obj->qtd_emprestada = $value->qtd_emprestada;
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
                    $emprestimo = new Emprestimo($param["id"]);
                    $emprestimo->id_usuario = $emprestimo->id_usuario;
                    $emprestimo->id_admin = $usuarioLogado;

                    if (($emprestimo->status == "PENDENTE") and ($param['status'] == "DEVOLVIDO")) {
                        throw new Exception('Não pode aprovar uma solicitação com status "DEVOLVIDO" antes de ser "APROVADO"');
                    }
                    $emprestimo->status = $param['status'];
                }
                $emprestimo->fromArray($param);
                $emprestimo->store();

                //Delete emprestimo se existe.
                PivotEmprestimoFerramentas::where('id_emprestimo', '=', $emprestimo->id)->delete();

                $ferramentas = array_map(function ($value) {
                    return (int)$value;
                }, $param['ferramenta']);


                $count = count($ferramentas);
                //Salvando items na tela pivot. 
                if (isset($ferramentas)) {
                    for ($i = 0; $i < $count; $i++) {
                        $pivot =  new PivotEmprestimoFerramentas();
                        $pivot->id_emprestimo = $emprestimo->id;
                        $pivot->id_ferramenta = $param['ferramenta'][$i];
                        $pivot->quantidade = $param['quantidade'][$i];

                        $tools = Ferramentas::where('id', 'in', $ferramentas)->load();
                        $tool = [];
                        foreach ($tools as $key) {
                            $tool[] = $key->quantidade;
                        }
                        if ($tool[$i] < $param['qtd_emprestada'][$i]) {
                            throw new Exception(
                                'A quantidade na linha ' . ($i + 1) .
                                    ' não pode ser maior que a disponível no estoque que é: '
                                    . $tool[$i]
                            );
                        } elseif ($param['quantidade'][$i] < $param['qtd_emprestada'][$i]) {
                            throw new Exception(
                                'A quantidade emprestada na linha ' . ($i + 1) .
                                    ' não pode ser maior que a quantidade solicitada'
                            );
                        } else {
                            $pivot->qtd_emprestada = $param['qtd_emprestada'][$i];

                            if ($param['quantidade'][$i] != $param['qtd_emprestada'][$i]) {
                                $result = ($tool[$i] + ($param['quantidade'][$i] - $param['qtd_emprestada'][$i])); //valor subtraido.
                                $this->updateQuantidade($pivot->id_ferramenta, $result);
                            }
                            if ($param['status'] == "DEVOLVIDO") {
                                $result = $tool[$i] + $param['qtd_emprestada'][$i]; //Devolvendo valor para banco.
                                $this->updateQuantidade($pivot->id_ferramenta, $result);
                            }
                        }
                        $pivot->store();
                    }
                }
            }
            TTransaction::close();
            $action = new TAction(array('EmprestimoList', 'onReload'));
            new TMessage('info', 'Salvo com sucesso', $action);
        } catch (Exception $e) // in case of exception
        {
            $action = new TAction(array('EmprestimoList', 'onReload'));
            new TMessage('error', $e->getMessage(), $action);
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
            new TMessage('error', 'Erro ao atualizar valor do banco <br>' . $e->getMessage());
            TTransaction::rollback();
        }
    }
}
