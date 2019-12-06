<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Description of PatientsTable
 *
 * @author p0070611
 */
class CategoriesTable extends Table
{
    public function initialize(array $config)
    {
      parent::initialize($config);

      $this->addAssociations([
        'hasMany'   => [ 'Jobs' =>        [ 'className' => 'agent_jobs',
                                            'foreignKey' => 'chemical_agent_id',
                                            'bindingKey' => 'idchem' ] ]
      ]);
    }
}