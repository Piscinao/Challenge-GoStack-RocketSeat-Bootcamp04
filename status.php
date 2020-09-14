<?php

class Status extends TPage {

    function onInit() {
        $curso = new ARCurso();
        $buscaCurso = $curso->findAll("order by nome_curso");
        $dadosCurso = array();
        array_push($dadosCurso, array("nomeCurso" => "", "codCurso" => ""));

        foreach ($buscaCurso as $bc) {
            array_push($dadosCurso, array("nomeCurso" => $bc->nome_curso, "codCurso" => $bc->codigo_curso));
        }

        $this->curso->setDataTextField("nomeCurso");
        $this->curso->setDataValueField("codCurso");
        $this->curso->setDataSource($dadosCurso);
        $this->curso->dataBind();
        $this->buscaps->setIsDefaultButton(true);
    }

    public function buscaPs() {
        $this->enviarMensagem("", "");
        $nome = $this->nome_ps->getText();
        $opcao = $this->ddOpcao->getSelectedValue();
        switch ($opcao) {
            case "0":
                $sql = "sem_acentos(titulo_processo) ilike sem_acentos('%$nome%') and ativo = true and (listar = 1 OR listar = 2 OR listar = 5)";
                break;
            case "1":
                $sql = "sem_acentos(titulo_processo) ilike sem_acentos('%$nome%') and ativo = true and listar = 1";
                break;
            case "2":
                $sql = "sem_acentos(titulo_processo) ilike sem_acentos('%$nome%') and ativo = true and listar = 2";
                break;
            case "3":
                $sql = "sem_acentos(titulo_processo) ilike sem_acentos('%$nome%') and ativo = true and listar = 5";
                break;
            default:
                $sql = "sem_acentos(titulo_processo) ilike sem_acentos('%$nome%') and ativo = true and (listar = 1 OR listar = 2 OR listar = 5)";
                break;
        }
        $ps = new ARProcessoSeletivo();
        $busca = $ps->findAll($sql);
        $dados = array();
        if(count($busca) <= 0) {
            if($this->busca2($nome)==null) {
            //$this->mensagem->setVisible(true);
            //$this->mensagem->setText("A busca não retornou nenhum resultado.");
            }
        }
        else {
            $this->inscritos->setVisible("false");
            foreach($busca as $b) {
                array_push($dados, array("texto"=>$b->titulo_processo, "valor"=>$b->codigo_processo));
            }
        }
        if (count($dados) > 0) {
            $this->pnProcessos->setVisible("true");
        } else {
            $this->pnProcessos->setVisible("false");
        }

        $this->processos->setVisible("true");
        $this->processos->setDataValueField("valor");
        $this->processos->setDataTextField("texto");
        $this->processos->setDataSource($dados);
        $this->processos->DataBind();
    }

    public function cancelarConsultaInscritos() {
        $this->pnProcessos->setVisible(true);
        $this->Panel2->setVisible(true);
        $this->buscaPs();
    }

    public function cancelarConsultaPs() {
        $this->getResponse()->redirect("?page=Status");
    }

    public function buscaInscritos($sender, $param) {

        $cod = $sender->Items[$param->Index]->Value;
        $this->setViewState("codigo_ps", $cod);

        $this->busca();
    }

    public function busca() {

        $this->pnProcessos->setVisible("false");
        //$this->processos->setVisible("false");
        //$this->buscaps->setVisible("false");
        //$this->nome_ps->setVisible("false");
        //$this->ddOpcao->setVisible("false");
        $this->Panel2->setVisible("false");
        $ds = $this->getNomes();

        $processo = new ARProcessoSeletivo();
        $busca = $processo->findByPk($this->getViewState('codigo_ps'));


        if(count($ds) <= 0) {
            $this->inscritos->setVisible("false");
            $this->enviarMensagem("alert alert-error", "Não há inscritos a convocar no PS $busca->titulo_processo.");
            $this->btnCacelarConsultaPs->setVisible("false");
            $this->btnCacelarConsultaInscritos->setVisible("true");
        }
        else {
            $this->inscritos->setVisible("true");
            $this->inscritos2->setItemRenderer("Application.pages.StatusRepeater");
            $this->inscritos2->DataSource=$ds;
            $this->inscritos2->dataBind();
            $this->pagina->setText($busca->titulo_processo);
            //$this->caminho->setDisplay("Dynamic");
        }
    }

    public function enviarMensagem($tipo, $mensagem) {
        if ($mensagem == "") {
            $this->pnMensagem->setVisible(false);
        } else {
            $this->pnMensagem->setCssClass("alert alert-block alert-$tipo ");
            $this->mensagem->setText($mensagem);
            $this->pnMensagem->setVisible(true);
        }
    }

    public function busca2($cod) {
        $this->setViewState("codigo_insc", $cod);
        $this->pnProcessos->setVisible("false");
        $this->processos->setVisible("false");


        $ds = $this->getNomesPS();


        if(count($ds) <= 0) {
            $this->inscritos->setVisible("false");
            $this->enviarMensagem("alert alert-error", "Não há Processos Seletivos para a busca $cod .");
            $this->btnCacelarConsultaPs->setVisible("true");
            $this->btnCacelarConsultaInscritos->setVisible("false");
        }
        else {
            $this->enviarMensagem("", "");
            //$this->nome_ps->setVisible("false");
            //$this->buscaps->setVisible("false");
            $this->inscritos->setVisible("true");
            $this->inscritos2->setItemRenderer("Application.pages.StatusRepeater2");
            $this->inscritos2->DataSource=$ds;
            $this->inscritos2->dataBind();
            $this->pagina->setText("Busca por CPF=".$this->nome_ps->getText());
            //$this->caminho->setDisplay("Dynamic");
        }
    }

    protected function getNomes() {

        $cod = $this->getViewState("codigo_ps");

        $arps = new ARProcessoSeletivo();
        $arps = $arps->findByPk($cod);

        $inscrito = new ARInscrito();
        $busca = $inscrito->findAllBySql("select codigo_inscrito, nome_inscrito, cpf, rg, deferimento, observacoes_deferimento, email from sigma.inscrito where codigo_processo_seletivo = ? order by observacoes_deferimento,deferimento desc, nome_inscrito", $cod);

        $dados = array();
        $cont = 0;
       /* $categoria = 'nada';
        $btn = 'joao';*/
        $btnCancelar="None";
        // if($_SERVER['HTTPS']=='on')
        if(false)
            $url = "https://".$_SERVER['HTTP_HOST'].$this->Service->ConstructUrl("CadastroTutor");
        else
            $url = "http://".$_SERVER['HTTP_HOST'].$this->Service->ConstructUrl("CadastroTutor");

        foreach($busca as $b) {
            $mensagem = "Não há e-mail para este candidato";
            $btnCancelar="None";
            if($b->deferimento == "A") {  //se for aprovadoilike ''
                $categoria = "Convocar";
                $status = "Aprovado";
                $btn = "ativado";
                $class="success";
            //array_push($dados, array("codigo_inscrito"=>$b->codigo_inscrito, "nome"=>$b->nome_inscrito, "cpf"=>$b->cpf, "categoria"=>$categoria));
            }
            else if($b->deferimento == "R") {  //se for removido da convocacao ''
                    $categoria = "Nenhuma ação";
                    $status = "Reprovado";
                    $btn = "desativado";
                    $class="error";
                //array_push($dados, array("codigo_inscrito"=>$b->codigo_inscrito, "nome"=>$b->nome_inscrito, "cpf"=>$b->cpf, "categoria"=>$categoria));
                }
                else if($b->deferimento == "P") {  //se for removido da convocacao ''
                    $categoria = "Nenhuma ação";
                    $status = "Desclassificado";
                    $btn = "desativado";
                    $class="error";
                //array_push($dados, array("codigo_inscrito"=>$b->codigo_inscrito, "nome"=>$b->nome_inscrito, "cpf"=>$b->cpf, "categoria"=>$categoria));
                }
                else {
                    if($b->deferimento == "C") {
                        $class="warning";
                        //convocado aguardando cadastro

                        $cad = new ARCadastro();
                        $busca_cad = $cad->find("codigo_inscrito = $b->codigo_inscrito");
                        //die(print_r($busca_cad));
                        switch($busca_cad->status) {

                            case "1":
                                $categoria = "Cancelar convocação";
                                $status = "Aguardando Cadastro";
                                $btn = "ativado";
                                $mensagem ="CONVOCAÇÃO
Prezado Candidato $b->nome_inscrito,

CPF: $b->cpf
Processo Seletivo: ".$arps->titulo_processo."
Documento de Identidade (RG): $b->rg

Conforme convocação realizada pelo site do NUTEAD, pedimos que:
Preencha e envie o formulário disponível no link $url&cmh=$busca_cad->link
1- Imprimir e ler o Edital de Convocação que se encontra disponível no link http://ead.uepg.br/site/index.php/editais/

Cordialmente,
Núcleo de Tecnologia e Educação Aberta e a Distância - Nute@d - UEPG
";
                                break;
                            case "2":
                                $categoria = "Nenhuma ação";
                                $status = "Cadastro Preenchido";
                                $btn = "desativado";
                                break;
                            case "3":
                                $categoria = "Nenhuma ação";
                                $status = "Cadastro Homologado";
                                $btn = "desativado";
                                break;
                            case "4":
                                $categoria = "Nenhuma ação";
                                $status = "Correção de Dados";
                                $btn = "desativado";
                                break;
                            case "5":
                                $categoria = "Nenhuma ação";
                                $status = "Cadastro cancelado";
                                $class = "error";
                                $btn = "desativado";
                                break;
                            case "6":
                                $categoria = "Cancelar convocação";
                                $status = "Aguardando Cadastro Manual";
                                $btn = "ativado";
                                break;
                        }
                    }
                    else {
                    // if(($b->deferimento == null) or ($b->deferimento == ' ') ){
                        $btnCancelar="Dynamic";
                        $class="info";
                        $status="Aguardando aprovação";
                        $categoria = "Aprovar/Reprovar";   //inscrito aguardando aprovação
                        $btn = "ativado";
                    // }
                    }
                }
                if($b->observacoes_deferimento<>"")
                    $pos=$b->observacoes_deferimento;
                else
                    $pos="-";
            $cont++;
            array_push($dados, array("codigo_inscrito"=>$b->codigo_inscrito, "nome"=>$b->nome_inscrito, "cpf"=>$b->cpf, "categoria"=>$categoria, "btn"=>$btn, "contador"=>$cont, "status"=>$status, "class"=>$class, "cancelar"=>$btnCancelar, "posicao"=>$pos, "mensagem"=>$mensagem, "email"=>$b->email));
        }
        return $dados;
    }
    protected function getNomesPS() {

        $cod = $this->getViewState("codigo_insc");

        $inscrito = new ARInscrito();
        $arproc = new ARProcessoSeletivo();
        $arproc = $arproc->findAll("ativo = true and (listar = 1 OR listar = 2)");
        $processos = array();
        foreach ($arproc as $p){
            array_push($processos, $p->codigo_processo);
        }
        $cods = join(",", $processos);
        $busca = $inscrito->findAllBySql("select codigo_inscrito, nome_inscrito, cpf, rg, deferimento, codigo_processo_seletivo, observacoes_deferimento, email from sigma.inscrito where cpf ilike '%$cod%' and codigo_processo_seletivo in ($cods) order by deferimento, nome_inscrito");

        $dados = array();
        $cont = 0;
       /* $categoria = 'nada';
        $btn = 'joao';*/
        // if($_SERVER['HTTPS']=='on')
        if(false)
            $url = "https://".$_SERVER['HTTP_HOST'].$this->Service->ConstructUrl("CadastroTutor");
        else
            $url = "http://".$_SERVER['HTTP_HOST'].$this->Service->ConstructUrl("CadastroTutor");
        foreach($busca as $b) {
            $arps = new ARProcessoSeletivo();
            $arps = $arps->findByPk($b->codigo_processo_seletivo);
            $btnCancelar="None";
            $mensagem = "Não há e-mail para este candidato";
            if($b->deferimento == "A") {  //se for aprovadoilike ''
                $categoria = "Convocar";
                $status = "Aprovado";
                $btn = "ativado";
                $class="success";
            //array_push($dados, array("codigo_inscrito"=>$b->codigo_inscrito, "nome"=>$b->nome_inscrito, "cpf"=>$b->cpf, "categoria"=>$categoria));
            }
            else if($b->deferimento == "R") {  //se for removido da convocacao ''
                    $categoria = "Nenhuma ação";
                    $status = "Convocação cancelada";
                    $btn = "desativado";
                    $class="error";
                //array_push($dados, array("codigo_inscrito"=>$b->codigo_inscrito, "nome"=>$b->nome_inscrito, "cpf"=>$b->cpf, "categoria"=>$categoria));
                }
                else if($b->deferimento == "P") {  //se for removido da convocacao ''
                    $categoria = "Nenhuma ação";
                    $status = "Desclassificado";
                    $btn = "desativado";
                    $class="error";
                //array_push($dados, array("codigo_inscrito"=>$b->codigo_inscrito, "nome"=>$b->nome_inscrito, "cpf"=>$b->cpf, "categoria"=>$categoria));
                }
                else {
                    if($b->deferimento == "C") {
                        $class="warning";
                        //convocado aguardando cadastro

                        $cad = new ARCadastro();
                        $busca_cad = $cad->find("codigo_inscrito = $b->codigo_inscrito");
                        //die(print_r($busca_cad));
                        switch($busca_cad->status) {

                            case "1":
                                $categoria = "Cancelar convocação";
                                $status = "Aguardando Cadastro";
                                $btn = "ativado";
                                $mensagem ="CONVOCAÇÃO
Prezado Candidato $b->nome_inscrito,

CPF: $b->cpf
Processo Seletivo: ".$arps->titulo_processo."
Documento de Identidade (RG): $b->rg

Comunicamos que você foi convocado conforme os dados acima.
Siga agora as seguintes instruções:
1- Imprimir e ler o Edital de Convocação que se encontra disponível no link http://ead.uepg.br/site/index.php/editais/
2- Preencher e enviar o formulário disponível no link $url&cmh=$busca_cad->link
3- Aguarde retorno no seu e-mail para impressão do formulário e maiores informações quanto à documentação que deverá ser entregue em até 07 (sete) dias úteis a partir da data do Edital de Convocação mencionado no item 1.
4- O não atendimento ao prazo estabelecido no item 3, será entendido como não interesse em assumir a função de tutor, caracterizando assim a sua desistência.

Cordialmente,
Núcleo de Tecnologia e Educação Aberta e a Distância - Nute@d - UEPG
";
                                break;
                            case "2":
                                $categoria = "Nenhuma ação";
                                $status = "Cadastro Preenchido";
                                $btn = "desativado";
                                break;
                            case "3":
                                $categoria = "Nenhuma ação";
                                $status = "Cadastro Homologado";
                                $btn = "desativado";
                                break;
                            case "4":
                                $categoria = "Nenhuma ação";
                                $status = "Correção de Dados";
                                $btn = "desativado";
                                break;
                            case "5":
                                $categoria = "Nenhuma ação";
                                $status = "Cadastro cancelado";
                                $class = "error";
                                $btn = "desativado";
                                break;
                            case "6":
                                $categoria = "Cancelar convocação";
                                $status = "Aguardando Cadastro Manual";
                                $btn = "ativado";
                                break;
                        }
                    }
                    else {
                    // if(($b->deferimento == null) or ($b->deferimento == ' ') ){
                        $btnCancelar="Dynamic";
                        $class="info";
                        $status="Aguardando aprovação";
                        $categoria = "Aprovar/Reprovar";   //inscrito aguardando aprovação
                        $btn = "ativado";
                    // }
                    }
                }

                if($b->observacoes_deferimento<>"")
                    $pos=$b->observacoes_deferimento;
                else
                    $pos="-";
            $arps = new ARProcessoSeletivo();

            $buscaps=$arps->findByPk($b->codigo_processo_seletivo);
            $cont++;
            array_push($dados, array("codigo_inscrito"=>$b->codigo_inscrito, "nome"=>$b->nome_inscrito, "cpf"=>$b->cpf, "categoria"=>$categoria, "btn"=>$btn, "contador"=>$cont, "status"=>$status, "class"=>$class, "ps"=>$buscaps->titulo_processo, "cancelar"=>$btnCancelar, "posicao"=>$pos));
        }

        return $dados;
    }

    protected function getStatus() {
        return array(
        array('id'=>'3','nome'=>'Convocar'),    //aprovado
        array('id'=>'2','nome'=>'Convocado'),   //convocados
        array('id'=>'1','nome'=>'Aprovar'),      //inscrito
        );
    }

    public function altera() {

        $item=$param->Item;
        die(print_r($item));
    }

    public function cancelar() {

        $this->confirmacao->setDisplay("None");
        $this->selecionado->setText("");
        $this->cpf->setText("");
        $this->valor->setValue("");
        if($this->inscritos2->getItemRenderer()=="Application.pages.StatusRepeater")
            $this->busca();
        else
            $this->busca2 ($this->nome_ps->getText());
    }

    public function aplicar() {
        $a = $this->valor->getValue();

        $inscrito = new ARInscrito();
        $busca = $inscrito->findByPk($a);
        if($busca != "") {

            switch($busca->deferimento) {

                case null:
                        $this->aprovar($a, $this->classificacao->getSelectedValue());
                    break;

                case "A":
                    $this->convocar($a);
                    break;

                case "C":
                    $this->cancelarConvocacao($busca->codigo_inscrito);
                    break;

            }


        }
        $this->confirmacao->setDisplay("None");
        $this->selecionado->setText("");
        $this->cpf->setText("");
        $this->txtEmail->setText("");
        $this->valor->setValue("");
        $this->busca();
    }

    public function naoaprovar() {
        $a = $this->valor->getValue();

        $inscrito = new ARInscrito();
        $busca = $inscrito->findByPk($a);
        if($busca != "") {
            $busca->deferimento = "R";
            //$index = $this->classificacao->getItemCount()-1;
            //$busca->observacoes_deferimento=str_pad($this->classificacao->Items[$index]->Value, 4, "0", STR_PAD_LEFT);
            $busca->save();
        }
        $this->confirmacao->setDisplay("None");
        $this->selecionado->setText("");
        $this->cpf->setText("");
        $this->valor->setValue("");
        $this->busca();
    }
    public function desclassificar() {
        $a = $this->valor->getValue();

        $inscrito = new ARInscrito();
        $busca = $inscrito->findByPk($a);
        if($busca != "") {
            $busca->deferimento = "P";
            $busca->save();
        }
        $this->confirmacao->setDisplay("None");
        $this->selecionado->setText("");
        $this->cpf->setText("");
        $this->valor->setValue("");
        $this->busca();
    }

    private function cancelarConvocacao($cod_insc) {
        $cad = new ARCadastro();
        $busca_cad = $cad->find("codigo_inscrito = $cod_insc");
        //die(print_r($busca_cad));
        if($busca_cad->status==1||$busca_cad->status==6) {
            $busca_cad->status=5;
            $busca_cad->save();
        }
    }

    private function aprovar($codigo, $pos) {

        $inscrito = new ARInscrito();
        $busca = $inscrito->findByPk($codigo);

        if($busca != "") {

            $busca->deferimento = "A";
            $busca->observacoes_deferimento=str_pad($pos, 4, "0", STR_PAD_LEFT);
            $busca->email = $this->txtEmail->getText();
            $busca->save();
        }
    }
    private function reprovar($codigo, $pos) {

        $inscrito = new ARInscrito();
        $busca = $inscrito->findByPk($codigo);

        if($busca != "") {

            $busca->deferimento = "R";
            //$busca->observacoes_deferimento=$pos;
            $busca->save();
        }
    }

    private function convocar($codigo) {

        $insc = new ARInscrito();
        //$busca = new ARInscrito();
        $busca = $insc->findByPk($codigo);
        $email = $this->txtEmail->getText();
        if($busca != "") {
            $link = md5($busca->codigo_inscrito);
            if($this->ck_email->getChecked()){
                $busca->deferimento = "C";
                $busca->email = $email;
                $busca->save();
                // if($_SERVER['HTTPS']=='on')
                if(false)
                    $url = "https://".$_SERVER['HTTP_HOST'].$this->Service->ConstructUrl("CadastroTutor");
                else
                    $url = "http://".$_SERVER['HTTP_HOST'].$this->Service->ConstructUrl("CadastroTutor");

                //$msg = "Convocação de bolsista pelo seguinte processo: ".$this->pagina->getText().". Preencha seu formulário de cadastro de bolsistas no link: $url&cmh=$link e aguarde o retorno neste e-mail para impressao do mesmo";

                $cadastro = new ARCadastro();
                $cadastro->codigo_inscrito = $busca->codigo_inscrito;
                $cadastro->link = $link;
                $cadastro->entrada = $this->entrada->getSelectedValue();
                $cadastro->status = 1; //1.Aguardando cadastro - 2.Preenchido - 3.Aprovado - 4.Correção - 6.Aguardando Cadastro Manual
                $cadastro->tipo_curso_vinculado = $this->tipo_curso->getSelectedValue();
                $cadastro->curso_vinculado=  $this->curso->getSelectedValue();
                $cadastro->save();
                $msg="CONVOCAÇÃO
Prezado Candidato $busca->nome_inscrito,

CPF: $busca->cpf
Processo Seletivo: ".$this->pagina->getText()."
Documento de Identidade (RG): $busca->rg

Comunicamos que você foi convocado conforme os dados acima.
Siga agora as seguintes instruções:
1- Imprimir e ler o Edital de Convocação que se encontra disponível no link http://ead.uepg.br/site/index.php/editais/
2- Preencher e enviar o formulário disponível no link $url&cmh=$link
3- Aguarde retorno no seu e-mail para impressão do formulário e maiores informações quanto à documentação que deverá ser entregue em até 07 (sete) dias úteis a partir da data do Edital de Convocação mencionado no item 1.
4- O não atendimento ao prazo estabelecido no item 3, será entendido como não interesse em assumir a função de tutor, caracterizando assim a sua desistência.

Cordialmente,
Núcleo de Tecnologia e Educação Aberta e a Distância - Nute@d - UEPG
";

                mail($busca->email, "Link para preenchimento de formulário - Nutead", $msg, "From: Nute@d - Alpha - Sistema de Cadastro de Bolsistas <nao_responder@nutead.org>");
            }else{
                $busca->deferimento = "C";
                $busca->email = $email;
                $busca->save();
                $cadastro = new ARCadastro();
                $cadastro->codigo_inscrito = $busca->codigo_inscrito;
                $cadastro->link = $link;
                $cadastro->entrada = $this->entrada->getSelectedValue();
                $cadastro->status = 6; //1.Aguardando cadastro - 2.Preenchido - 3.Aprovado - 4.Correção - 6.Aguardando Cadastro Manual
                $cadastro->tipo_curso_vinculado = $this->tipo_curso->getSelectedValue();
                $cadastro->curso_vinculado=  $this->curso->getSelectedValue();
                $cadastro->save();
            }
        }

    }
    public function closeModInsc() {
        $this->confirmacao->setDisplay("None");
        $this->busca();
    }
    public function pgSituacao() {
        $url = $this->Service->ConstructUrl('Status');
        $this->getResponse()->redirect($url);
    }
    public function fecharModalMsg(){
        $this->email_mensagem->setDisplay("None");
        $this->texto_modal_msg->setText("");
        $this->busca();
    }


}

?>
