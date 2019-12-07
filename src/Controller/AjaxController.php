<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controller;

use Cake\Utility\Inflector;
use Cake\Event\Event;
use Cake\Log\Log;

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
  
  public function getTranslation($msgId)
  {
    $ret = new \stdClass;
    $ret->ok = true;
    $ret->msgid = $msgId;
    $ret->text = __($msgId);
    $respBody = json_encode($ret, JSON_FORCE_OBJECT);
    $this->response->withStringBody($respBody);
  }
  
  public function buildTreeConfig()
  {
    $ret = new \stdClass;
    $ret->ok = true;
    $agentIds = $_GET[$this->agents_param];
    $labelFld = "label".Inflector::camelize($this->lang);
    $models = ['Agents', 'SubFamilies', 'Families', 'Groups', 'Categories'];
    $relModel = "Related";
    $cols = ['SubFamily', "Family", 'Group', 'Category'];
    $items = [];
    
    foreach ( $agentIds as $agentId ) {
      $selectFlds = [];

      $lookup = $this->loadModel('HierarchieAgents')
                  ->find()
                  ->where(['idchem IN ' => $agentId, 'dupl_agent' => false])
                  ->toArray()[0];
      $startLvl = $lookup->lvl;
      $startModel = $models[$startLvl];
      $modelsAbove = array_slice($models, $startLvl+1);

      foreach ( $cols as $lv_i => $lv_n ) {
        if ( $lookup->$lv_n && $startLvl < $lv_i+1 ) {
          $relModelCond = "$startModel.$lv_n = $relModel.$lv_n";
          break;
        }
      }

      foreach (array_merge([$startModel, $relModel], $modelsAbove) as $mod) {
        foreach (['idchem', $labelFld, 'level', 'lvl', 'canjem_agent', 'SubFamily', 'Family', 'Group', 'Category'] as $memb) {
          $selectFlds[] = "$mod.$memb";
        }
      }

      $mod = $this->loadModel($startModel);
      if ( $startModel == "Families" ) {
        $assoc = $mod->getAssociation($modelsAbove[0]);
        Log::write('error', "HERE IT IS:");
        Log::write('error', print_r($assoc, true));
      }
      $its = $mod
                        ->find()
                        ->join([$relModel => ['table' => Inflector::tableize($startModel),
                                                'type' => 'LEFT',
                                                'conditions' => $relModelCond ] ])
                        ->select($selectFlds, false)
                        ->contain($modelsAbove)
                        ->where(["$startModel.idchem" => $agentId,
                                 "$relModel.idchem <> $startModel.idchem"])
                        ->toArray();
      
      $items = array_merge( $items, $its );
    }
    
    $rootNodeName = 'root';
    $childrenDropLevels = [];
    
    $rootNode = new \stdClass();
    $nodes = [$rootNodeName => $rootNode];
    
    $canjemChains = [];
    
    foreach ( $items as $item ) {
      $parentNodeId = null;
      
      $baseNode = $this->initNode($item, $labelFld, true);
      if ( $item->lvl == 0 ) {
        $canjemChains[$item->idchem] = [$item->idchem];
      }
      
      if ( $item->SubFamily && $item->lvl < 1 ) {
        $parentNodeId = "subFamily".$item->SubFamily;
        $childDropLevel = 0;
      } else
      if ( $item->Family && $item->lvl < 2 ) {
        $parentNodeId = "family".$item->Family;
        $childDropLevel = 1;
      } else
      if ( $item->Group && $item->lvl < 3 ) {
        $parentNodeId = "group".$item->Group;
        $childDropLevel = 2;
      } else {
        $parentNodeId = "category".$item->Category;
        $childDropLevel = 3;
      }
      $baseNode->nodeInfo['parent'] = $parentNodeId;
      $nodes[$baseNode->varname] = $baseNode;
      $this->updateDropLevels($childrenDropLevels, $parentNodeId, $childDropLevel);
      
      $parentNodeId = null;
      if ( $item->$relModel && !isset($nodes['agent'.$item->$relModel['idchem']]) ) {
        $relItem = $item->$relModel;
        $relNode = $this->initNode($relItem, $labelFld);
        if ( $relItem->SubFamily && $relItem->lvl < 1 ) {
          $parentNodeId = "subFamily".$relItem->SubFamily;
          $childDropLevel = 0;
        } else
        if ( $relItem->Family && $relItem->lvl < 2 ) {
          $parentNodeId = "family".$relItem->Family;
          $childDropLevel = 1;
        } else
        if ( $relItem->Group && $relItem->lvl < 3  ) {
          $parentNodeId = "group".$relItem->Group;
          $childDropLevel = 2;
        } else {
          $parentNodeId = "category".$relItem->Category;
          $childDropLevel = 3;
        }
        $relNode->nodeInfo['parent'] = $parentNodeId;
        $nodes[$relNode->varname] = $relNode;
        $this->updateDropLevels($childrenDropLevels, $parentNodeId, $childDropLevel);
      } 
       
      $parentNodeId = null;
      if ( $item->SubFamily && $item->lvl < 1  ) {
        if ( !isset($nodes['subFamily'.$item->SubFamily]) ) {
          $subFamilyItem = $item->sub_family;
          $subFamNode = $this->initNode($subFamilyItem, $labelFld);
          if ( isset($subFamilyItem->Family) ) {
            $parentNodeId = "family".$subFamilyItem->Family;
            $childDropLevel = 0;
          } else
          if ( isset($subFamilyItem->Group) ) {
            $parentNodeId = "group".$subFamilyItem->Group;
            $childDropLevel = 1;
          } else {
            $parentNodeId = "category".$subFamilyItem->Category;
            $childDropLevel = 2;
          }
          $subFamNode->nodeInfo['parent'] = $parentNodeId;
          $nodes[$subFamNode->varname] = $subFamNode;
          $this->updateDropLevels($childrenDropLevels, $parentNodeId, $childDropLevel);
        }
      }
      
      $parentNodeId = null;
      if ( $item->Family && $item->lvl < 2 ) {
        if ( !isset($nodes['family'.$item->Family]) ) {
          $familyItem = $item->family;
          $famNode = $this->initNode($familyItem, $labelFld);

          if ( isset($familyItem->Group) ) {
            $parentNodeId = "group".$familyItem->Group;
            $childDropLevel = 0;
          } else {
            $parentNodeId = "category".$familyItem->Category;
            $childDropLevel = 1;
          }
          $famNode->nodeInfo['parent'] = $parentNodeId;
          $nodes[$famNode->varname] = $famNode;
          $this->updateDropLevels($childrenDropLevels, $parentNodeId, $childDropLevel);
        }
      }
      
      $parentNodeId = null;
      if ( $item->Group && $item->lvl < 3  ) {
        if ( !isset($nodes['group'.$item->Group]) ) {
          $groupItem = $item->group;
          $groupNode = $this->initNode($groupItem, $labelFld);
          $parentNodeId = "category".$groupItem->Category;
          $groupNode->nodeInfo['parent'] = $parentNodeId;
          $nodes[$groupNode->varname] = $groupNode;
        }
      }
      
      $parentNodeId = null;
      if ( $item->category ) {
        if ( !isset($nodes['category'.$item->Category]) ) {
          $categoryItem = $item->category;
          $cateNode = $this->initNode($categoryItem, $labelFld);
          $cateNode->nodeInfo['parent'] = $rootNodeName;
          $nodes[$cateNode->varname] = $cateNode;
        }
      }
    }
    
    foreach( $childrenDropLevels as $nodeid => $cdl ) {
      $cdl = array_unique($cdl);
      if ( count($cdl) == 1 && $cdl[0] > 0 ) {
        $nodes[$nodeid]->nodeInfo['childrenDropLevel'] = $cdl[0];
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
        'node' => ['HTMLclass' => 'nodeExample1'],
        'hideRootNode' => true
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
    $outFile = 'js/tree-config.js';
    
    file_put_contents($outFile, $jsConfigStr);
    $ret->rootElem = $htmlRoot;
    $ret->treeConfigFilename = $outFile;
    $ret->chains = $canjemChains;
    
    $refererUrl = $_SERVER['HTTP_REFERER'];
    $parsedUrl = parse_url($refererUrl);
        
    $pfx = $this->agents_param."=";
    $repl = $pfx.urlencode(implode(',', $agentIds));
    if ( strpos($refererUrl, $pfx) === false ) {
      if ( !isset($parsedUrl['query']) ) {
      $parsedUrl['query'] = '';
      } else {
        $parsedUrl['query'] .= '&';
      }
      $parsedUrl['query'] .= $repl;
    } else {
      $regex = '/'.$pfx.'[^&]+/';
      $parsedUrl['query'] = preg_replace($regex, $repl, $parsedUrl['query']);
    }
    $ret->updatedUrl = $parsedUrl['scheme']."://".$parsedUrl['host'].$parsedUrl['path'].'?'.$parsedUrl['query'];
    
    $respBody = json_encode($ret);
    $this->response->withStringBody($respBody);
  }
  
  function initNode(&$data, $labelFld, $selected = false)
  {
    $node = new \stdClass();
    $relatedNode = false;
    if ( is_array($data) ) {
      $data = (object) $data;
      $relatedNode = true;
    }
    $lvls = explode("," , $data->level);
    $canjemOrig = false;
    
    $pos = array_search("idchem", $lvls);
    if ( $pos !== false ) {
      $canjemOrig = true;
      if ( count($lvls) == 2 ) {
        $lvl = $lvls[1-$pos];
      } else
      if ( count($lvls) == 1 ) {
        $lvl = $lvls[0];
      } else {
        throw new \Exception("Types d'agents non-reconnus !");
      }
    } else {
      $lvl = $data->level;
    }
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
    
    $htmlClasses = [$canjemOrig ? 'blue' : 'light-gray'];
    if ( $selected ) {
      $htmlClasses[] = 'selected-agent';
    } else
    if ( $relatedNode ) {
      $htmlClasses[] = 'related-agent';
    }
    $node->varname = $pfx.$data->idchem;
    $nodeInfo = ['text' => ['name' => $type." -- ".$data->idchem, 'title' => $data->$labelFld], 'HTMLclass' => implode(' ', $htmlClasses) ];
    if ( $canjemOrig && !$relatedNode ) {
      $nodeInfo['text']['contact'] = $nodeInfo['text']['desc'] = $data->idchem;
    }
    $node->nodeInfo = $nodeInfo;
    
    $members = ["SubFamily", "Family", "Group", "Category"];
    foreach ( $members as $member ) {
      if ( isset($data->$member) ) {
        $node->$member = $data->$member;
      }
    }
    
    return $node;
  }
  
  function sortNodes($node1, $node2) {
    $levelOrder = ["category", "group", "family", "subfamily", "idchem"];
    
    $o1 = array_search($node1->level, $levelOrder);
    $o2 = array_search($node2->level, $levelOrder);
    $node1IsRoot = !isset($node1->nodeInfo);
    $node2IsRoot = !isset($node2->nodeInfo);
    
    if ( $node1IsRoot ) {
      $ret = -1;
    } else
    if ( $node2IsRoot ) {
      $ret = 1;
    } else
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
    } else
    if ( !isset($node1->Family) ) {
      $ret = -1;
    } else
    if ( !isset($node2->Family) ) {
      $ret = 1;
    } else
    if ( $node1->Family != $node2->Family ) {
      $ret = strcmp($node1->Family, $node2->Family);
    } else
    if ( !isset($node1->SubFamily) ) {
      $ret = -1;
    } else
    if ( !isset($node2->SubFamily) ) {
      $ret = 1;
    }
    else if ( $node1->SubFamily != $node2->SubFamily ) {
      $ret = strcmp($node1->SubFamily, $node2->SubFamily);
    } else
    if ( $node1->idchem != $node2->idchem ) {
      $ret = strcmp($node1->idchem, $node2->idchem);
    } else {
      $ret = 0;
    }
    
    $sortLog = ($node1->varname ? $node1->varname : "root")." vs ".($node2->varname ? $node2->varname : "root")." : ".$ret.PHP_EOL;
    //error_log($sortLog, 3, "C:/Users/p0070611/Documents/NetBeansProjects/OrganigrammeAgentsChimiques/logs/error.log");
    return $ret;
  } 
  
  function updateDropLevels(&$childrenDropLevels, $nodeId, $dropLevel)
  {
    if ( isset($childrenDropLevels[$nodeId]) ) {
      array_push($childrenDropLevels[$nodeId], $dropLevel);
    } else {
      $childrenDropLevels[$nodeId] = [$dropLevel];
    }
  }
  
  function getTreeLegend()
  {
    $this->autoRender = true;
  }
  
  public function getJobCounts()
  {
    $ret = new \stdClass;
    $ret->ok = true;
    
    $curr = [];
    $chain = $_GET['chain'];
        
    $allCounts = [];
    
    $tbl = 'dummy';
    $mod = $this->loadModel($tbl);
    $jobsPre = $mod->find();
    $jobsPost = $mod->find();
    $first = true;
    $resPre = $resPost = [];
        
    foreach ( $chain as $ch ) {
      $curr[] = $ch;
      $jobsPre = $this->joinWith($jobsPre, 'agent_jobs_pre_auto', $ch, $resPre, $first);
      $jobsPost = $this->joinWith($jobsPost, 'agent_jobs', $ch, $resPost, $first);
      $first = false;
        
      if ( !empty($resPre) && !empty($resPost) ) {
        $resPre = $resPre[0];      
        $resPost = $resPost[0];      
        $labels[$ch] = $resPre->label;
        $crr = $curr;
        array_pop($crr);
        $allCounts[] =['idchem' => $ch,
                       'count_pre' => intval($resPre->cnt),
                       'count_post' => intval($resPost->cnt),
                       'lbl' => array_map(function($c) use ($labels) { return $labels[$c]; }, $crr)];
      }
    }
    $ret->counts = $allCounts;
    $respBody = json_encode($ret);
    $this->response->withStringBody($respBody);
  }
  
  public function joinWith($jobs, $table, $ch, &$res, $first = false)
  {
    $tableCamel = Inflector::camelize($table);
    $tbl = $first ? $tableCamel : "$tableCamel$ch";
    $tableInfo = ['table' => "(select * from $table where caid = $ch)"];
    $tableInfo['conditions'] = $first ? "1" : "$tableCamel.sjnid = $tbl.sjnid";
    $joinInfo = [$tbl => $tableInfo];
    $jobs = $jobs->join($joinInfo);
    $res = $jobs->select(['label' => "$tbl.lbl_".$this->lang, 'cnt' => 'COUNT(*)'])
                ->group("$tableCamel.caid")
                ->toArray();
    return $jobs;
  }
}
