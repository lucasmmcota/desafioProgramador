<?php

namespace App\Controllers;

class Conta extends BaseController
{
    // Mostra o número de todas as contas criadas
    public function index()
    {
        $contaModel = new \App\Models\ContaModel();
        $contas = $contaModel->find(); // acha todas as contas
        foreach ($contas as $conta) {
            echo "Numero da conta: " . $conta->numeroDaConta . "\n";
        }
    }

    // Retorna o valor da cotação de venda de uma determinada moeda
    public function cotacao($moeda)
    {
        $format = 'json';
        date_default_timezone_set('America/Sao_Paulo');
        $data = date('m-d-Y', strtotime("-1 days")); // Dia de fechamento anterior
        $ch = curl_init("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)?@moeda='$moeda'&@dataCotacao='$data'&$format=json");

        curl_setopt_array($ch, [
            CURLOPT_HEADER => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $resposta = curl_exec($ch);

        if (!(curl_error($ch))) {
            $resultado = json_decode($resposta, true);
            $valores = $resultado["value"][0];
        } else {
            echo curl_error($ch);
        }
        curl_close($ch);
        return $valores["cotacaoVenda"];
    }

    // Retorna um array com as moedas encotradas na API do Banco Central
    public function cadastrarMoedas()
    {
        $ch = curl_init("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/Moedas?format=json");

        curl_setopt_array($ch, [
            CURLOPT_HEADER => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $resposta = curl_exec($ch);

        if (!(curl_error($ch))) {
            $resultado = json_decode($resposta, true);
        } else {
            echo curl_error($ch);
        }

        curl_close($ch);
        return $resultado;
    }

    // Verifica se uma determinada conta foi cadastrada
    public function acharConta($numeroDaConta, $numContas)
    {
        if ($numeroDaConta > 0 and $numeroDaConta <= $numContas) {
            return 1;
        } else {
            return 0;
        }
    }

    // Cadastrar uma nova conta
    public function cadastrar()
    {
        $contaModel = new \App\Models\ContaModel();
        $contaModel->set('saldoTotalReais', 0);

        $valores = $this->cadastrarMoedas();
        $moedas = "BRL";
        $saldo = "0";
        // Preenchendo uma string de moedas e a outra do saldo em cada uma delas
        for ($i = 0; $i < 10; $i++) {
            $valor = $valores["value"][$i];
            $moedas = $moedas . " " . $valor["simbolo"];
            $saldo = $saldo . " 0";
        }

        $contaModel->set('moedas', $moedas);
        $contaModel->set('saldoMoedas', $saldo);

        if ($contaModel->insert()) {
            echo "Conta criada com sucesso !\n";
            echo "O número da sua conta é: " . count($contaModel->find()) . "\n";
        } else {
            echo "Não foi possível criar a conta !\n";
        }
    }

    // Verifica se a cotação é maior ou menor do que o BRL
    public function verificarCotacao($cotacao)
    {
        if ($cotacao >= 1) {
            return 1;
        } else {
            return 0;
        }
    }

    // Cadastra uma nova transação
    public function realizarTransacao($numeroDaConta, $valor, $moeda, $tipo)
    {
        $transacaoModel = new \App\Models\TransacaoModel();
        $transacaoModel->set('conta_numeroDaConta', $numeroDaConta);

        if ($tipo === 'deposito') {
            $transacaoModel->set('tipo', 'deposito');
        } else {
            $transacaoModel->set('tipo', 'saque');
        }

        $transacaoModel->set('valor', $valor);
        $transacaoModel->set('moeda', $moeda);
        date_default_timezone_set('America/Sao_Paulo');
        $data = date("Y-m-d"); // Modelo utilizado pelo MySQL
        $transacaoModel->set('data', $data);

        if ($transacaoModel->insert()) {
            echo "Transação realizada com sucesso !\n";
        } else {
            echo "Transação realizada com erro !\n";
        }
    }

    // Realiza o depósito em uma conta
    public function depositar($numeroDaConta, $valor, $moeda)
    {
        $contaModel = new \App\Models\ContaModel();

        if ($this->acharConta($numeroDaConta, count($contaModel->find()))) {
            if ($valor > 0) {
                $conta = $contaModel->find($numeroDaConta);
                $moedas = explode(" ", $conta->moedas); // string de moedas como array
                $saldo = explode(" ", $conta->saldoMoedas); // string de saldoMoedas como array
                $indice = 0;
                for ($i = 0; $i < count($moedas); $i++) {
                    if ($moeda === $moedas[$i]) {
                        $saldo[$i] += $valor; // Altera o saldo em uma determianda moeda
                        $indice = $i;
                        break;
                    }
                }

                $conta->saldoMoedas = implode(" ", $saldo); // array do saldoMoedas como string novamente

                // Atualiza o saldo total em reais na conta
                if ($moeda === 'BRL') {
                    $conta->saldoTotalReais += $valor;
                } else {
                    if ($this->verificarCotacao($this->cotacao($moedas[$i]))) {
                        $conta->saldoTotalReais += ($valor * $this->cotacao($moedas[$indice])); // Se a cotação da moeda for maior que BRL
                    } else {
                        $conta->saldoTotalReais += ($valor / $this->cotacao($moedas[$indice])); // Se a cotação da moeda for menor que BRL
                    }
                }

                // Realiza o depósito
                if ($contaModel->update($numeroDaConta, $conta)) {
                    $this->realizarTransacao($numeroDaConta, $valor, $moeda, 'deposito');
                    echo "Depósito realizado com sucesso !\n";
                } else {
                    echo "Não foi possível realizar o depósito !\n";
                }
            } else {
                echo "Valor inválido !\n";
            }
        } else {
            echo "Esta conta não existe !\n";
        }
    }

    // Função que procura uma determinada moeda entre as disponíveis
    public function acharMoeda($moeda, $moedas)
    {

        for ($i = 0; $i < count($moedas); $i++) {
            if ($moeda === $moedas[$i]) {
                return $i;
            }
        }
        return -1;
    }

    // Realiza o saque em uma conta, se possível
    public function sacar($numeroDaConta, $valor, $moeda)
    {
        $contaModel = new \App\Models\ContaModel();
        $valorSaque = $valor; // 'valorSaque' será o valor registrado na transação, visto que a variável 'valor' será alterada

        if ($this->acharConta($numeroDaConta, count($contaModel->find()))) {
            if ($valor > 0) {
                $conta = $contaModel->find($numeroDaConta);
                $moedas = explode(" ", $conta->moedas);
                $indice = $this->acharMoeda($moeda, $moedas); // 'indice' é a posição da moeda que será realizado o saque no array de moedas
                $value = $valor; // 'value' é o valor total em reais do saque
                if ($moeda != 'BRL') {
                    if ($this->verificarCotacao($this->cotacao($moedas[$indice]))) {
                        $value = $value * $this->cotacao($moedas[$indice]);
                    } else {
                        $value = $value / $this->cotacao($moedas[$indice]);
                    }
                }
                if ($conta->saldoTotalReais >= $value) { // Verificando se é possível realizar o saque
                    $saldo = explode(" ", $conta->saldoMoedas);
                    $excecao = 0;
                    if ($saldo[$indice] >= $valor) { // Se é possível realizar o saque somente na moeda determinada
                        $saldo[$indice] -= $valor;
                    } else {
                        $valor -= $saldo[$indice];
                        $saldo[$indice] = 0;
                        $excecao = 1; // Caindo nos outros dois casos relatados no PDF
                    }

                    if ($excecao) {
                        for ($i = 0; $i < count($moedas); $i++) {
                            // Realiza as conversões de moeda e os saques
                            if ($saldo[$i] > 0 and $valor > 0) {
                                if ($moedas[$i] != 'BRL') {
                                    $saldoReal = 0;
                                    $saldoFinal = 0;

                                    if ($this->verificarCotacao($this->cotacao($moedas[$i]))) {
                                        $saldoReal = $saldo[$i] * $this->cotacao($moedas[$i]);
                                    } else {
                                        $saldoReal = $saldo[$i] / $this->cotacao($moedas[$i]);
                                    }

                                    if ($moeda != 'BRL') {
                                        if ($this->verificarCotacao($this->cotacao($moedas[$i]))) {
                                            $saldoFinal = $saldoReal / $this->cotacao($moeda);
                                        } else {
                                            $saldoFinal = $saldoReal * $this->cotacao($moeda);
                                        }
                                        $valor -= $saldoFinal;
                                    } else {
                                        $valor -= $saldoReal;
                                    }
                                } else {
                                    $saldoFinal = 0;

                                    if ($this->verificarCotacao($this->cotacao($moedas[$i]))) {
                                        $saldoFinal = $saldo[$i] / $this->cotacao($moeda);
                                    } else {
                                        $saldoFinal = $saldo[$i] * $this->cotacao($moeda);
                                    }

                                    $valor -= $saldoFinal;
                                }
                                
                                if ($valor >= 0) {
                                    $saldo[$i] = 0;
                                } else {
                                    // Caso tenha sido sacado valor a mais, é feito o reembolso na mesma moeda da última retirada
                                    $attSaldo = - ($valor);
                                    if ($moeda === 'BRL') {
                                        if ($this->verificarCotacao($this->cotacao($moedas[$i]))) {
                                            $attSaldo =  $attSaldo / $this->cotacao($moedas[$i]);
                                        } else {
                                            $attSaldo = $attSaldo * $this->cotacao($moedas[$i]);
                                        }
                                    }
                                    $saldo[$i] = $attSaldo;
                                    break;
                                }
                            }
                        }
                    }

                    $conta->saldoMoedas = implode(" ", $saldo);

                    // Atualiza o saldo total da conta em reais
                    if ($moeda === 'BRL') {
                        $conta->saldoTotalReais -= $valorSaque;
                    } else {
                        if ($this->verificarCotacao($this->cotacao($moedas[$indice]))) {
                            $conta->saldoTotalReais -= ($valorSaque * $this->cotacao($moedas[$indice]));
                        } else {
                            $conta->saldoTotalReais -= ($valorSaque / $this->cotacao($moedas[$indice]));
                        }
                    }

                    // Realiza o saque
                    if ($contaModel->update($numeroDaConta, $conta)) {
                        $this->realizarTransacao($numeroDaConta, $valorSaque, $moeda, 'saque');
                        echo "Saque realizado com sucesso !\n";
                    } else {
                        echo "Não foi possível realizar o saque !\n";
                    }
                } else {
                    echo "A conta não possui saldo suficiente para este saque !\n";
                }
            } else {
                echo "Valor inválido !\n";
            }
        } else {
            echo "Esta conta não existe !\n";
        }
    }

    // Exibe o saldo da conta
    public function exibirSaldo($numeroDaConta, $moeda = NULL)
    {
        $contaModel = new \App\Models\ContaModel();

        if ($this->acharConta($numeroDaConta, count($contaModel->find()))) {
            $conta = $contaModel->find($numeroDaConta);
            $moedas = explode(" ", $conta->moedas);
            $saldo = explode(" ", $conta->saldoMoedas);
            echo "\nSaldo da conta " . $numeroDaConta . " :\n";
            // Se não for passado nenhuma moeda como parâmetro imprime o saldo em todas as moedas
            if ($moeda === NULL) {
                for ($i = 0; $i < count($moedas); $i++) {
                    echo $saldo[$i] . " " . $moedas[$i] . "\n";
                }
            } else {
                if ($moeda != 'BRL') {
                    $indice = 0;
                    $saldoFinal = 0;
                    // Convertendo o saldo das moedas para BRL e depois para a moeda desejada
                    for ($i = 1; $i < count($moedas); $i++) {
                        if ($moeda === $moedas[$i]) {
                            $indice = $i;
                        }
                    }
                    if ($this->verificarCotacao($this->cotacao($moedas[$indice]))) {
                        $saldoFinal = $conta->saldoTotalReais / $this->cotacao($moeda);
                    } else {
                        $saldoFinal = $conta->saldoTotalReais * $this->cotacao($moeda);
                    }
                    echo $saldoFinal . " " . $moedas[$indice] . "\n";
                } else {
                    echo $conta->saldoTotalReais . " BRL \n";
                }
            }
        } else {
            echo "Esta conta não existe !";
        }
    }

    // Função para imprimir as informações da transação
    public function imprimirTransacoes($transacao)
    {
        echo "\n\nNumero da conta: " . $transacao->conta_numeroDaConta .
            "\nValor: " . $transacao->valor .
            "\nMoeda: " . $transacao->moeda .
            "\nOperação Realizada: " . $transacao->tipo .
            "\nData: " . $transacao->data;
    }

    // Exibir todo o extrato de uma conta ou o extrato em um determinado período
    public function exibirExtrato($numeroDaConta, $inicio = NULL, $fim = NULL)
    {
        $contaModel = new \App\Models\ContaModel();

        // Exibir todo o extrato da conta
        if ($inicio === NULL and $fim === NULL) {
            if ($this->acharConta($numeroDaConta, count($contaModel->find()))) {
                $transacaoModel = new \App\Models\TransacaoModel();
                $transacoes = $transacaoModel->find(); // Acha todas as transações
                $conta = $contaModel->find($numeroDaConta);
                // Imprime cada uma das transações
                foreach ($transacoes as $transacao) {
                    if ($conta->numeroDaConta === $transacao->conta_numeroDaConta) {
                        $this->imprimirTransacoes($transacao);
                    }
                }
            } else {
                echo "Esta conta não existe !\n";
            }
        } else if ($inicio != NULL and $fim != NULL) { // Extrato de um determinado período
            if ($this->acharConta($numeroDaConta, count($contaModel->find()))) {
                $aux = 0;
                if (strtotime($inicio) > strtotime($fim)) { // Caso os períodos estejam trocados
                    $aux = $inicio;
                    $inicio = $fim;
                    $fim = $aux;
                }

                $transacaoModel = new \App\Models\TransacaoModel();
                $transacoes = $transacaoModel->find();
                $conta = $contaModel->find($numeroDaConta);
                $aux = 0;

                foreach ($transacoes as $transacao) {
                    if ($conta->numeroDaConta === $transacao->conta_numeroDaConta) {
                        // Confere se a transação esta no período determinado
                        if (strtotime($inicio) < strtotime($transacao->data) and strtotime($fim) > strtotime($transacao->data)) {
                            $aux = 1;
                            $this->imprimirTransacoes($transacao);
                        }
                    }
                }
                if ($aux === 0) {
                    echo "Nenhuma operação foi realizada no período informado !\n";
                }
            } else {
                echo "Esta conta não existe !\n";
            }
        } else {
            echo "Parâmetros incorretos !";
        }
    }
}
