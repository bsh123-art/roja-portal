<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$stats = $this->stats ?? [];
?>
<div class="container-fluid">
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="small text-muted"><?php echo Text::_('COM_ROJACOMMENTS_TOTAL_COMMENTS'); ?></div>
                    <div class="display-6"><?php echo (int) ($stats['total'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="small text-muted"><?php echo Text::_('COM_ROJACOMMENTS_PENDING'); ?></div>
                    <div class="display-6"><?php echo (int) ($stats['pending'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="small text-muted"><?php echo Text::_('COM_ROJACOMMENTS_PUBLISHED'); ?></div>
                    <div class="display-6"><?php echo (int) ($stats['published'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="small text-muted"><?php echo Text::_('COM_ROJACOMMENTS_REPORTED'); ?></div>
                    <div class="display-6"><?php echo (int) ($stats['reported'] ?? 0); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h2 class="h4 mb-3">ROJA Comments</h2>
            <p class="mb-0"><?php echo Text::_('COM_ROJACOMMENTS_DASHBOARD_HELP'); ?></p>
        </div>
    </div>
</div>
