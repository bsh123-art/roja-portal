<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$items = $this->items ?? [];
$pagination = $this->pagination ?? null;
?>
<form action="index.php?option=com_rojacomments&view=comments" method="post" name="adminForm" id="adminForm">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="btn-toolbar" role="toolbar">
                <button type="submit" name="task" value="comments.publish" class="btn btn-success"><?php echo Text::_('COM_ROJACOMMENTS_APPROVE'); ?></button>
                <button type="submit" name="task" value="comments.reject" class="btn btn-warning"><?php echo Text::_('COM_ROJACOMMENTS_REJECT'); ?></button>
                <button type="submit" name="task" value="comments.spam" class="btn btn-danger"><?php echo Text::_('COM_ROJACOMMENTS_MARK_SPAM'); ?></button>
                <button type="submit" name="task" value="comments.delete" class="btn btn-outline-danger"><?php echo Text::_('COM_ROJACOMMENTS_DELETE'); ?></button>
            </div>
        </div>
    </div>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" name="checkall-toggle" value="" title="Check All" onclick="Joomla.checkAll(this)" /></th>
                <th><?php echo Text::_('COM_ROJACOMMENTS_USER'); ?></th>
                <th><?php echo Text::_('COM_ROJACOMMENTS_COMMENT'); ?></th>
                <th><?php echo Text::_('COM_ROJACOMMENTS_ARTICLE'); ?></th>
                <th><?php echo Text::_('COM_ROJACOMMENTS_STATUS'); ?></th>
                <th><?php echo Text::_('COM_ROJACOMMENTS_RECOMMEND'); ?></th>
                <th><?php echo Text::_('COM_ROJACOMMENTS_DATE'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php $userName = $item->user_id ? 'Joomla User #' . (int) $item->user_id : ($item->guest_name ?: 'Guest'); ?>
                <tr>
                    <td><input type="checkbox" name="cid[]" value="<?php echo (int) $item->id; ?>" /></td>
                    <td><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo nl2br(htmlspecialchars((string) $item->comment, ENT_QUOTES, 'UTF-8')); ?></td>
                    <td><?php echo (int) $item->article_id; ?></td>
                    <td><?php echo htmlspecialchars((string) $item->status, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int) $item->recommend_count; ?></td>
                    <td><?php echo htmlspecialchars((string) $item->created, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pagination): echo $pagination->getListFooter(); endif; ?>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
