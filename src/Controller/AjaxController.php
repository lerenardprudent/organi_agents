<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controller;

use Cake\Utility\Inflector;
use Cake\Event\Event;

class AjaxController extends AppController {
  
  public function initialize()
  {
    parent::initialize();
    $this->loadComponent('Paginator');
  }
    
  public function beforeFilter(Event $event)
  {
    $this->autoRender = false;
  }
  
  public function beforeRender(Event $event)
  {
    $this->setLang();
  }
  
  public function getClassifications($classiType, $classiCode, $periodStart, $periodEnd, $hint = "")
  {
    $classiCode = strtolower($classiCode);
    $hint = preg_replace('/\s+/', '%', $hint);
    $tablePfx = 'classi';
    $tblNameParts = [$tablePfx, $classiType, $classiCode];
    $tableName = implode('_', $tblNameParts);
    $camelizedTableName = Inflector::camelize($tableName);
    $model = $this->loadModel($camelizedTableName);
    $model->setPrimaryKey('code');
    $titleCol = 'title_'.$this->lang;
    $query = $model->find()->select(['code', $titleCol, 'description_'.$this->lang]);
    if ( strlen($hint) > 0 ) {
      $orCond = [ "$titleCol LIKE" => "%$hint%", 'code LIKE' => "%$hint%"];
      $query = $query->where(['OR' => $orCond]);
    }
    $ios = $query->toArray();
    $this->set(compact('ios', 'classiCode', 'periodStart', 'periodEnd'));
    $this->set('lang', $this->lang);
    $this->render();
  }
  
  public function showExposureData($classiStnd, $code, $yearIn, $yearOut)
  {
    $jei = $this->loadModel('JobExposureIndices');
    $this->paginate = ['limit' => 5];
    
    $classiInfo = $this->loadModel('Classifications')->get($classiStnd);
    $titleCol = "title_".$this->lang;
    $classiName = $classiInfo->$titleCol;
    $classiType = $classiInfo->type;
    $tablePfx = 'classi';
    $tblNameParts = [$tablePfx, $classiType, $classiStnd];
    $tableName = implode('_', $tblNameParts);
    $camelizedTableName = Inflector::camelize($tableName);
    $io = $this->loadModel($camelizedTableName)->setPrimaryKey('code')->get($code);
    $jobTitle = $io->$titleCol;
    $isMale = ['sex' => 'M'];
    $isFemale = ['sex' => 'F'];
    $concVar = 'c_final_cat';
    $relVar = 'r_final';
    $yearInVar = 'year_in';
    $yearOutVar = 'year_out';
    $cond = ["$classiStnd LIKE" => "$code%", "JobExposureIndices.subject_id is not null"];
    if ( $yearIn && $yearIn != "-" ) {
      $cond["$yearInVar >="] = $yearIn;
    }
    if ( $yearOut && $yearOut != "-" ) {
      $cond["$yearOutVar <="] = $yearOut;
    }
    if ( isset($_GET) && isset($_GET['refine_agents']) && !empty($_GET['refine_agents']) ) {
      $cond["chemical_agent_id IN"] = $_GET['refine_agents'];
    }
    $baseQuery = $jei->find()
                    ->contain(['JobOccupationCodes' =>
                                  function($q) use ($classiStnd) { return $q->select([strtolower($classiStnd)]); },
                               'ChemicalAgents',
                               'Subjects'])
                    ->select(['chemical_agent_id',
                              'ChemicalAgents.lbl_'.$this->lang,
                              'ChemicalAgents.definition_en',
                              'm_count' => $this->sqlSum($isMale),
                              'f_count' => $this->sqlSum($isFemale),
                              'm_c1_count' => $this->sqlSum(array_merge($isMale, [$concVar => '1'])),
                              'm_c2_count' => $this->sqlSum(array_merge($isMale, [$concVar => '2'])),
                              'f_c1_count' => $this->sqlSum(array_merge($isFemale, [$concVar => '1'])),
                              'f_c2_count' => $this->sqlSum(array_merge($isFemale, [$concVar => '2'])),
                              'm_r1_count' => $this->sqlSum(array_merge($isMale, [$relVar => '1'])),
                              'm_r2_count' => $this->sqlSum(array_merge($isMale, [$relVar => '2'])),
                              'f_r1_count' => $this->sqlSum(array_merge($isFemale, [$relVar => '1'])),
                              'f_r2_count' => $this->sqlSum(array_merge($isFemale, [$relVar => '2'])),
                              'm_freq' => "group_concat(case when sex = 'M' then f_final else '' end)",
                              'f_freq' => "group_concat(case when sex = 'F' then f_final else '' end)"
                        ])
                    ->where($cond);
                                  
    $vc = ['JobExposureIndices.subject_id',
                                'JobExposureIndices.job_no',
                                $yearInVar,
                                $yearOutVar,
                                'Subjects.sex'];
    $distinctJobs = $this->loadModel('JobOccupationCodes')->find()
                      ->contain(['JobExposureIndices', 'Subjects'])
                      ->select($vc)
                      ->distinct($vc)
                      /*->select(['m_count' => $this->sqlSum($isMale),
                                'f_count' => $this->sqlSum($isFemale),
                                $yearInVar => "min($yearInVar)",
                                $yearOutVar => "max($yearOutVar)"])*/
                      ->where($cond)
                      ->toArray();
    
    $groupQuery = $baseQuery->group(['chemical_agent_id']);
    
    $errCode = null;
    $errMsg = null;
    try {
      $jobExpoData = $groupQuery->toArray();
    } catch ( \Exception $e ) {
      $errCode = $e->getCode();
      $errMsg = $e->getMessage();
    }
    $paginating = false;
    
    $lblFld = "lbl_".$this->lang;
    $chemAgents = $this->loadModel('ChemicalAgents')->find('list', ['keyField' => 'id', 'valueField' => $lblFld])->order($lblFld)->toArray();
    $this->set(compact('code', 'jobCounts', 'jobExpoData', 'classiStnd', 'classiName', 'paginating', 'errCode', 'errMsg', 'distinctJobs', 'yearInVar', 'yearOutVar', 'yearIn', 'yearOut', 'jobTitle', 'chemAgents'));
    $this->set('lang', $this->lang);
    $this->render();
  }
  
  private function sqlSum($varVals)
  {
    $tmp = [];
    array_walk($varVals, function($val, $var) use (&$tmp) { 
            array_push($tmp, "$var = '$val'");
    });
    return "sum(case when (" . implode(' AND ', $tmp) . ") then 1 else 0 end)";
  }
  
  public function getTranslation($msgId)
  {
    $ret = new \stdClass;
    $ret->ok = true;
    $ret->msgid = $msgId;
    $ret->text = __($msgId);
    $respBody = json_encode($ret, JSON_FORCE_OBJECT);
    $this->response->body($respBody);
  }
  
  public function autocomplete($lang, $tableName = "chemical_agents")
  {
    $term = $_GET['term'];
    $fld = "lbl_$lang";
    $matches = [];
    $camelizeedTable = Inflector::camelize($tableName);
    $mod = $this->loadModel($tableName);
    $matches = $mod->find('list', ['keyField' => "id", 'valueField' => $fld])->where(["$fld LIKE" => "%$term%" ])->toArray();
    $respBody = json_encode($matches);
    $this->response->body($respBody);
  }
  
  public function buildTreeConfig()
  {
    $ret = new \stdClass;
    $ret->ok = true;
    $agentIds = $_GET['agents'];
    $items = $this->loadModel('Agents')->find()->contain(['SubFamilies', 'Families', 'Groups', 'Categories'])->where(['Agents.idchem IN' => $agentIds])->toArray();
    $nodes = [];
    $labelFld = "label".Inflector::camelize($this->lang);
    $allRows = [];
    foreach ( $items as $item ) {
      $chemNode = new \stdClass();
      $this->initNode($chemNode, $item, $labelFld);
      
      $parentNodeId = null;
      if ( isset($item->SubFamily) ) {
        $parentNodeId = "subFamily".$item->SubFamily;
      } else
      if ( isset($item->Family) ) {
        $parentNodeId = "family".$item->Family;
      } else
      if ( isset($item->Group) ) {
        $parentNodeId = "group".$item->Group;
      } else {
        $parentNodeId = "category".$item->Category;
      }
      $chemNode->nodeInfo['parent'] = $parentNodeId;
      $nodes[$chemNode->varname] = $chemNode;
      
      if ( isset($item->SubFamily) && !isset($nodes['subFamily'.$item->SubFamily]) ) {
        $subFamilyItem = $item->sub_family;
        $subFamNode = new \stdClass();
        $this->initNode($subFamNode, $subFamilyItem, $labelFld);
        if ( isset($subFamilyItem->Family) ) {
          $parentNodeId = "family".$subFamilyItem->Family;
        } else
        if ( isset($subFamilyItem->Group) ) {
          $parentNodeId = "group".$subFamilyItem->Group;
        } else {
          $parentNodeId = "category".$subFamilyItem->Category;
        }
        $subFamNode->nodeInfo['parent'] = $parentNodeId;
        $nodes[$subFamNode->varname] = $subFamNode;
      }
      
      if ( isset($item->Family) && !isset($nodes['family'.$item->Family]) ) {
        $familyItem = $item->family;
        $famNode = new \stdClass();
        $this->initNode($famNode, $familyItem, $labelFld);
        
        if ( isset($familyItem->Group) ) {
          $parentNodeId = "group".$familyItem->Group;
        } else {
          $parentNodeId = "category".$familyItem->Category;
        }
        $famNode->nodeInfo['parent'] = $parentNodeId;
        $nodes[$famNode->varname] = $famNode;
      }
      
      if ( isset($item->Group) && !isset($nodes['group'.$item->Group]) ) {
        $groupItem = $item->group;
        $groupNode = new \stdClass();
        $this->initNode($groupNode, $groupItem, $labelFld);
        $parentNodeId = "category".$familyItem->Category;
        $groupNode->nodeInfo['parent'] = $parentNodeId;
        $nodes[$groupNode->varname] = $groupNode;
      }
      
      if ( !isset($nodes['category'.$item->Category]) ) {
        $categoryItem = $item->category;
        $cateNode = new \stdClass();
        $this->initNode($cateNode, $categoryItem, $labelFld);
        $nodes[$cateNode->varname] = $cateNode;
      }
    }
    
    @uasort($nodes, array($this, "sortNodes"));
    
    $htmlRoot = "#tree-root";
    $configElemName = 'config';
    $config = [
      $configElemName => [
        'container' => $htmlRoot,
        'nodeAlign' => "BOTTOM",
        'connectors' => [ 'type' => 'step' ],
        'node' => ['HTMLclass' => 'nodeExample1']
      ]
    ];
    
    $topLevelConfigElemName = 'chart_config';
    $topLevelConfig = [
        $topLevelConfigElemName => array_merge([$configElemName], array_keys($nodes))
    ];
    
    $allConfig = array_merge($config, $nodes);
    $jsConfigStrs = [];
    foreach ( $allConfig as $varname => $conf ) {
      $obj = isset($conf->nodeInfo) ? $conf->nodeInfo : $conf;
      $js = "$varname = ".json_encode($obj);
      $js = preg_replace('/(?<!:)\"([^\"\,\:]+)\"/', "$1$2", $js);
      $js = preg_replace('/(parent:)\"([^\"\,\:]+)\"/', "$1$2", $js);
      array_push($jsConfigStrs, $js);
    }
    foreach ( $topLevelConfig as $varname => $conf ) {
      $js = "$varname = [".implode(', ', $conf)."]";
      array_push($jsConfigStrs, $js);
    }
    $jsConfigStr = "var ".implode(','.PHP_EOL, $jsConfigStrs).";";
    $outFile = 'js/tree-config3.js';
    file_put_contents($outFile, $jsConfigStr);
    $ret->rootElem = $htmlRoot;
    $ret->treeConfigFilename = $outFile;
    $respBody = json_encode($ret);
    $this->response->body($respBody);
  }
  
  function initNode(&$node, $data, $labelFld) {
    
    $lvl = $data->level;
    $node->level = $lvl;
    if ( $lvl == "idchem" ) {
      $pfx = "agent";
      $type = __("Agent");
    } else
    if ( $lvl == "subfamily" ) {
      $pfx = "subFamily";
      $type = __("SubFamily");
    } else
    if ( $lvl == "family" ) {
      $pfx = "family";
      $type = __("Family");
    } else 
    if ( $lvl == "group") {
      $pfx = "group";
      $type = __("Group");
    } else {
      $pfx = "category";
      $type = __("Category");
    }
    $node->varname = $pfx.$data->idchem;
    $node->nodeInfo = ['text' => ['name' => $type, 'title' => $data->$labelFld], 'HTMLclass' => 'light-gray' ];
    
    $members = ["SubFamily", "Family", "Group", "Category"];
    foreach ( $members as $member ) {
      if ( isset($data->$member) ) {
        $node->$member = $data->$member;
      }
    }
  }
  
  function sortNodes($node1, $node2) {
    try {
    $levelOrder = ["category", "group", "family", "subfamily", "idchem"];
    
    $o1 = array_search($node1->level, $levelOrder);
    $o2 = array_search($node2->level, $levelOrder);
    
    if ( $o1 != $o2 ) {
      $ret = strcmp($o1, $o2);
    } else
    if ( $node1->Category != $node2->Category ) {
      $ret = strcmp($node1->Category, $node2->Category);
    } else
    if ( !isset($node1->Group) ) {
      $ret = -1;
    } else
    if ( !isset($node2->Group) ) {
      $ret = 1;
    } else if ( $node1->Group != $node2->Group ) {
      $ret = strcmp($node1->Group, $node2->Group);
    }
    else
    if ( !isset($node1->Family) ) {
      $ret = -1;
    } else
    if ( !isset($node2->Family) ) {
      $ret = 1;
    } else if ( $node1->Family != $node2->Family ) {
      $ret = strcmp($node1->Family, $node2->Family);
    }
    else
    if ( !isset($node1->SubFamily) ) {
      $ret = -1;
    } else
    if ( !isset($node2->SubFamily) ) {
      $ret = 1;
    } else if ( $node1->SubFamily != $node2->SubFamily ) {
      $ret = strcmp($node1->SubFamily, $node2->SubFamily);
    }
    else
    if ( $node1->idchem != $node2->idchem ) {
      $ret = strcmp($node1->idchem, $node2->idchem);
    } else {
      $ret = 0;
    }
    
    
    } catch( \Exception $e ) {
      $ret = 69;
    }
    return $ret;
  } 
}
