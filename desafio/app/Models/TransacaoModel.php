<?php

namespace App\Models;

use CodeIgniter\Model;

class TransacaoModel extends Model {

    protected $table = 'transacao';
    protected $primaryKey = 'id';
    protected $allowedFields = ['conta_numeroDaConta', 'tipo', 'valor', 'moeda', 'data'];
    protected $returnType = 'object';
}

?>