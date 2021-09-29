# Desafio Técnico

## Detalhes

O desafio consiste em desenvolver uma API RESTful que simule operações bancárias (depósito, saque, saldo e extrato) para diferentes moedas. Para realizar as transações financeiras, a aplicação deverá utilizar as moedas e a taxa cambial (PTAX) de fechamento disponibilizadas pela API do Banco Central.

## Especificações

Este projeto foi criado utilizando a linguagem PHP e o framework CodeIgniter 4 seguindo o padrão MVC. A criação do servidor web juntamente com o banco de dados MySQL foi através do programa XAMPP e a construção e uso da API foi realizada pelo Postman.

Respeitando o padrão MVC as principais partes do projeto estão nos pacotes Models, Views e Controllers.

📁 App\Models

Pacote que contém os objetos do sistema. Os dados que são recebidos através da URL são encapsulados nesses objetos. São eles:

* ContaModel -> Representa a conta de uma pessoa, seus atributos são: 
    * numeroDaConta -> Número da conta, único e auto incremental;
    * saldoTotalReais -> Saldo total da conta em reais;
    * moedas -> BRL mais as moedas disponibilizadas pela API do Banco Central;
    * saldoMoedas -> Saldo em cada uma das moedas existentes.

* TransacaoModel -> Representa as transações realizadas pelo banco, seus atributos são:
    * id -> ID da transação, único e auto incremental;
    * conta_numeroDaConta -> Chave estrangeira, se relaciona com a conta em que a transação é realizada;
    * tipo -> Depósito ou saque;
    * valor -> Valor da transação;
    * moeda -> Moeda em que a transação será realizada;
    * data -> Data em que a transação foi realizada (YYYY-mm-dd).

📁 App\Views

Nesta aplicação não foi realizada nenhuma alteração no pacote Views.

📁 App\Controllers

Pacote que recebe os objetos criados e encapsulados no pacote Models e interage com o banco de dados. Nele são implementados os métodos necessários para o funcionamento correto das operações do Banco, para isto foi criada a classe Conta. Esta classe será responsável por pegar as moedas e a cotação do último dia da API do Banco Central, por cadastrar uma conta, realizar as operações de saque e depósito e também de exibir o saldo e o extrato de uma conta.

## Instalação

Tendo todos os programas citados devidamente instalados na sua máquina:

1. Após criar o seu projeto com CodeIgniter 4 na pasta "htdocs" do XAMPP substitua as pastas "public" e "app" pelas pastas de mesmo nome que estão no repositório.
2. Altere o nome do arquivo "env" para ".env" e escreva "CI_ENVIRONMENT = development" nele.
3. Execute o script "bank.sql" no MySQL.
4. Inicie os servidores Apache e MySQL no painel de controle do XAMPP.
5. Pronto ! Você já está apto a criar a sua primeira conta, podendo ser feita acessando o servidor pelo navegador ou pelo Postman através do link:

```
http://localhost/desafio/public/conta/cadastrar;
```

## Como utilizar

Após criar a sua primeira conta a API também disponibilizada outras operações. Para realizar o seu primeiro depósito, por exemplo:

```
http://localhost/desafio/public/conta/depositar/$numeroDaConta/$valor/$moeda
```

Os parâmetros com "$" devem ser substituídos, respectivamente, pelo número da sua conta, o valor e em que moeda você deseja depositar.

O saque é feito de maneira análoga ao depósito:

```
http://localhost/desafio/public/conta/sacar/$numeroDaConta/$valor/$moeda
```

Mas enquanto o depósito é feito de maneira mais simples o saque tem algumas regras. Se a conta não possuir saldo suficiente para o saque na moeda solicitada, deverá ser realizada a conversão dos saldos das outras moedas para a moeda solicitada da seguinte forma:
* Caso o saldo na conta seja em Real, converter com a taxa de venda PTAX
para a moeda solicitada no saque;
* Caso contrário, converter o saldo na conta primeiro para Real a partir da
taxa de compra PTAX, e depois converter o saldo em Real para a moeda
solicitada no saque a partir da taxa de venda PTAX.

A operação de saldo pode ser realizada acessando o endereço abaixo:

```
http://localhost/desafio/public/conta/exibirSaldo/$numeroDaConta/?$moeda
```

A exibição do saldo é realizada de duas formas diferentes:
1. Saldo em cada uma das moedas, caso não seja passado o parâmetro da moeda;
1. Saldo na moeda passado por parâmetro.

Para o saldo de uma determinada moeda passada por parâmetro, a operação deverá retornar o montante total da conta na moeda no qual o saldo está sendo solicitado, sendo necessário converter o valor caso a moeda do saldo na conta seja diferente da moeda solicitada.

A última operação disponibilizada pela API é a de exibir o extrato da conta, onde são exibidas as informações sobre as transações de uma conta em um determinado período. Abaixo é visto o link para realizar esta operação.


```
http://localhost/desafio/public/conta/exibirExtrato/$numeroDaConta/?$periodo1/?$periodo2
```

O parâmetro do período a ser exibido o extrato é opcional, caso ele não seja escolhido é mostrado todas as transações da conta. Caso queria o extrato referente a um determinado período, dois períodos devem ser informados no modelo YYYY-mm-dd, não sendo necessário estarem em ordem.
