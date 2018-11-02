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
class AgentsTable extends Table
{
    public function initialize(array $config)
    {
      parent::initialize($config);

      $this->setTable('hierarchie_agents');
      
      $this->addAssociations([
        'belongsTo'    => [ 'SubFamilies' => [ 'className' => 'Agents',
                                               'foreignKey' => 'SubFamily',
                                               'bindingKey' => 'idchem'
                                           ],
                            'Families' => [ 'className' => 'Agents',
                                               'foreignKey' => 'Family',
                                               'bindingKey' => 'idchem'
                                           ],
                            'Groups' => [ 'className' => 'Agents',
                                               'foreignKey' => 'Group',
                                               'bindingKey' => 'idchem'
                                           ],
                            'Categories' => [ 'className' => 'Agents',
                                               'foreignKey' => 'Category',
                                               'bindingKey' => 'idchem'
                                           ]
                          ]
      ]);
    }
}