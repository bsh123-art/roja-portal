<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('behavior.core');
$token = HTMLHelper::_('form.token');
?>
<div class="container-fluid"><h1>ROJA Comments</h1><p>100 komentar terbaru. Gunakan database Joomla untuk mengubah status komentar melalui workflow admin.</p><table class="table table-striped"><thead><tr><th>ID</th><th>Artikel</th><th>Penulis</th><th>Komentar</th><th>Status</th><th>Dibuat</th></tr></thead><tbody><?php foreach ($this->comments as $comment) : ?><tr><td><?php echo (int) $comment->id; ?></td><td><?php echo (int) $comment->article_id; ?></td><td><?php echo htmlspecialchars($comment->user_id ? 'User #' . $comment->user_id : $comment->guest_name, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($comment->comment, ENT_QUOTES, 'UTF-8'); ?></td><td><code><?php echo htmlspecialchars($comment->status, ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars($comment->created, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?></tbody></table></div>
