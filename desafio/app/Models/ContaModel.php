<?php

namespace App\Models;

use CodeIgniter\Model;

class ContaModel extends Model {

    protected $table = 'conta';
    protected $primaryKey = 'numeroDaConta';
    protected $allowedFields = ['saldoTotalReais', 'moedas', 'saldoMoedas'];
    protected $returnType = 'object';
}

?>
