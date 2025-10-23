<?php
/**
 * AppVersion Cell
 * 
 * @var \Cake\View\View $this
 * @var string $version
 * @var string $logo
 * @var string $logoAlt
 * @var string $logoClass
 * @var bool $showHiddenText
 * @var string $versionText
 */
?>
<div class="header-item-content appversion">
    <div class="header-item-text no-link">
        <?= $this->Html->image($logo, [
            'alt' => $logoAlt, 
            'class' => $logoClass
        ]) ?>
        
        <?php if ($showHiddenText): ?>
            <span class="visually-hidden">
                <?= sprintf($versionText, $version) ?>
            </span>
        <?php endif; ?>
        
        <span aria-hidden="true"><?= h($version) ?></span>
    </div>
</div>
