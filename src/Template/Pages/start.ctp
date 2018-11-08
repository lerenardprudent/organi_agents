<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Configure;
use Cake\Network\Exception\NotFoundException;

$this->layout = false;

if (!Configure::read('debug')) :
    throw new NotFoundException(
        'Please replace src/Template/Pages/home.ctp with your own version or re-enable debug mode.'
    );
endif;

$currLang = $this->request->session()->read('Config.language');;
$description = __("title");

$this->Form->templates([
    'inputContainer' => '<div class="input {{type}}{{required}} {{extraclasses}}">{{content}}</div>'
  ]);
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $description ?>
    </title>

    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css('base.css') ?>
    <?= $this->Html->css('style.css') ?>
    <?= $this->Html->css('home.css') ?>
    <?= $this->Html->css('extra.css') ?>
    <?= $this->Html->css('perfect-scrollbar.css') ?>
    <?= $this->Html->css('tree-config.css') ?>
    <?= $this->Html->css('Treant.css') ?>
    <?= $this->Html->css('chosen.css') ?>
    
    <?= $this->Html->script('jquery-3.3.1.min.js') ?>
    <?= $this->Html->script('jquery.easing.js') ?>
    <?= $this->Html->script('jquery.mousewheel.js') ?>
    <?= $this->Html->script('raphael.js') ?>
    <?= $this->Html->script('perfect-scrollbar.js') ?>
    <?= $this->Html->script('Treant.js') ?>
    <?= $this->Html->script('chosen.jquery.js') ?>
    
    <?= $this->Html->script('utils.js') ?>
    <?= $this->Html->script('jq-init.js') ?>
    
    <link href="https://fonts.googleapis.com/css?family=Raleway:500i|Roboto:300,400,700|Roboto+Mono" rel="stylesheet">
</head>
<body class="home" data-lang='<?= $lang ?>'>
  <header class="row">
    <div class='links'>
      <?= $this->Html->link($currLang == "en" ? "Français" : "English", "/?lang=".($currLang == "en" ? "fr" : "en"), ['class' => 'lang-link', 'data-trans-url-base' => $this->Url->build(['controller' => 'Ajax', 'action' => 'get-translation']), 'data-translations' => "{}" ]) ?>
    </div>
  </header>

  <div class="row header-title">
    <div class="columns large-12 text-center">
      <span class="title-text"><?= mb_strtoupper(__("title")) ?></span>
    </div>
  </div>
  
  <div class="row header-title">
    <div class="columns large-12 text-center">
      <div class="inline-b width-90pc my-chosen">
        <?= $this->Form->input('choice_agents', ['options' => $chemAgents, 'label' => false, 'type' => 'select', 'class' => 'chosen-select', 'templateVars' => ['extraclasses' => 'inline-b minwidth-50'], 'multiple' => true, 'data-placeholder' => __("select_agents"), 'data-no-results-text' => __("no_agents_found"), 'data-unselect-all-text' => __("Unselect all"), 'data-init-selected' => $selectedAgents, 'data-selected' => [], 'data-url-build-tree-config' => $this->Url->build(['controller' => 'Ajax', 'action' => 'build-tree-config']), 'data-url-get-tree-legend' => $this->Url->build(['controller' => 'Ajax', 'action' => 'get-tree-legend']), 'data-url-get-job-counts' => $this->Url->build(['controller' => 'Ajax', 'action' => 'get-job-counts'])]) ?>
        <?= $this->Form->button(__('draw_hierarchy'), ['name' => 'draw-button', 'type' => 'button', 'class' => 'draw-tree my-button', 'disabled' => true, 'templateVars' => ['extraclasses' => 'inline-b']]) ?>
      </div>
    </div>
  </div>

  <div class='agents'>  
  </div>
  <div id="tree-root"></div>
  <div id='tree-legend'></div>
  
  <div id="loading-ajax" style="display:none">
    <span class="loading-info"></span>
    <span class='loading-text'><?= __("Processing")."..." ?></span>
  </div>
</body>
</html>
