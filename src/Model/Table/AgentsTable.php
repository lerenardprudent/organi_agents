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

      //$this->setTable('hierarchie_agents');
      $this->setTable('agnts');
      
      $this->addAssociations([
        'belongsTo'    => [ 'SubFamilies' => [ 'className' => 'sub_fams',
                                               'foreignKey' => 'SubFamily',
                                               'bindingKey' => 'idchem'
                                           ],
                            'Families' => [ 'className' => 'fams',
                                               'foreignKey' => 'Family',
                                               'bindingKey' => 'idchem'
                                           ],
                            'Groups' => [ 'className' => 'grps',
                                               'foreignKey' => 'Group',
                                               'bindingKey' => 'idchem'
                                           ],
                            'Categories' => [ 'className' => 'categs',
                                               'foreignKey' => 'Category',
                                               'bindingKey' => 'idchem'
                                           ]
                          ]
      ]);
    }
}