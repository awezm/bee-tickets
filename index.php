<?php
require __DIR__.'/src/config.php';
require __DIR__.'/src/db.php';
require __DIR__.'/src/auth.php';
require __DIR__.'/src/view.php';

$route = $_GET['route'] ?? 'home';

switch ($route) {
  case 'home':
    tpl_header('Submit Ticket'); ?>
    <div class="grid grid-2">
      <div class="card">
        <h2>Submit a ticket</h2>
        <form method="post" action="/?route=submit"><?php csrf_field(); ?>
          <label>Email</label><input type="email" name="email" required>
          <label>Subject</label><input name="subject" required>
          <label>Message</label><textarea name="body" required></textarea>
          <button>Create</button>
        </form>
      </div>
      <div class="card"><h2>Have a ticket?</h2><p>Open the <a href="/?route=portal">ticket portal</a>.</p></div>
    </div><?php tpl_footer(); break;

  case 'submit':
    csrf_check(); $pdo=db();
    $email=trim($_POST['email']??''); $subject=trim($_POST['subject']??''); $body=trim($_POST['body']??'');
    if(!$email||!$subject||!$body){ http_response_code(400); die('Missing'); }
    $token=bin2hex(random_bytes(16)); $now=gmdate('c');
    $pdo->prepare("INSERT INTO tickets(email,subject,body,status,public_token,created_at,updated_at) VALUES (?,?,?,?,?,?,?)")
        ->execute([$email,$subject,$body,'open',$token,$now,$now]);
    $id=$pdo->lastInsertId(); $link='/?route=portal&token='.urlencode($token);
    tpl_header('Created'); echo "<div class='card'><h2>Ticket #".e($id)." created</h2><p>Save your link: <a href='".e($link)."'>".e($link)."</a></p></div>"; tpl_footer(); break;

  case 'portal':
    $pdo=db(); $token=$_GET['token']??''; tpl_header('Portal');
    if(!$token){ echo "<div class='card'><h2>Enter token</h2><form method=get><input type=hidden name=route value=portal><input name=token placeholder='paste token'><button>Open</button></form></div>"; tpl_footer(); break; }
    $t=$pdo->prepare("SELECT * FROM tickets WHERE public_token=?"); $t->execute([$token]); $ticket=$t->fetch();
    if(!$ticket){ echo "<div class='card'>Invalid token.</div>"; tpl_footer(); break; }
    $r=$pdo->prepare("SELECT * FROM replies WHERE ticket_id=? ORDER BY id ASC"); $r->execute([$ticket['id']]); $replies=$r->fetchAll(); ?>
    <div class="card">
      <h2>#<?=e($ticket['id'])?> — <?=e($ticket['subject'])?></h2>
      <p class="muted"><?=e($ticket['email'])?> · <span class="badge <?=e($ticket['status'])?>"><?=e($ticket['status'])?></span></p>
      <p><?=nl2br(e($ticket['body']))?></p>
      <h3>Replies</h3>
      <?php foreach($replies as $rep): ?><div class="card" style="background:#12151c">
        <div class="muted"><?=e($rep['author'])?> · <?=e($rep['created_at'])?></div><div><?=nl2br(e($rep['body']))?></div></div><?php endforeach; ?>
      <?php if($ticket['status']==='open'): ?>
      <h3>Add reply</h3>
      <form method="post" action="/?route=portal_reply&token=<?=e(urlencode($token))?>"><?php csrf_field(); ?>
        <textarea name="body" required></textarea><button>Post reply</button>
      </form><?php else: ?><p class="muted">This ticket is closed.</p><?php endif; ?>
    </div><?php tpl_footer(); break;

  case 'portal_reply':
    csrf_check(); $pdo=db(); $token=$_GET['token']??'';
    $t=$pdo->prepare("SELECT * FROM tickets WHERE public_token=?"); $t->execute([$token]); $ticket=$t->fetch();
    if(!$ticket){ http_response_code(404); die('Not found'); }
    $body=trim($_POST['body']??''); if(!$body){ http_response_code(400); die('Empty'); }
    $pdo->prepare("INSERT INTO replies(ticket_id,author,body,created_at) VALUES (?,?,?,?)")->execute([$ticket['id'],'user',$body,gmdate('c')]);
    $pdo->prepare("UPDATE tickets SET updated_at=? WHERE id=?")->execute([gmdate('c'),$ticket['id']]);
    header("Location: /?route=portal&token=".urlencode($token)); exit;

  case 'admin/login':
    if($_SERVER['REQUEST_METHOD']==='POST'){ csrf_check(); if(admin_login($_POST['user']??'', $_POST['pass']??'')){ header('Location: /?route=admin'); exit; } $err='Invalid'; }
    tpl_header('Admin Login'); ?>
    <div class="card" style="max-width:460px">
      <h2>Admin Login</h2>
      <?php if(!empty($err)) echo "<p class='muted'>$err</p>"; ?>
      <form method="post"><?php csrf_field(); ?><label>User</label><input name="user" required><label>Pass</label><input type="password" name="pass" required><button>Login</button></form>
    </div><?php tpl_footer(); break;

  case 'admin/logout': admin_logout(); header('Location: /?route=admin/login'); exit;

  case 'admin':
    admin_require(); $pdo=db(); $status=$_GET['status']??'open';
    if(!in_array($status,['open','closed','all'])) $status='open';
    if($status==='all') $stmt=$pdo->query("SELECT * FROM tickets ORDER BY updated_at DESC");
    else { $stmt=$pdo->prepare("SELECT * FROM tickets WHERE status=? ORDER BY updated_at DESC"); $stmt->execute([$status]); }
    $tickets=$stmt->fetchAll(); tpl_header('Admin'); ?>
    <div class="actions">
      <a class="badge <?= $status==='open'?'open':''?>" href="/?route=admin&status=open">Open</a>
      <a class="badge <?= $status==='closed'?'closed':''?>" href="/?route=admin&status=closed">Closed</a>
      <a class="badge" href="/?route=admin&status=all">All</a>
      <a class="badge" href="/?route=admin/logout">Logout</a>
    </div>
    <div class="card">
      <table><tr><th>ID</th><th>Subject</th><th>Email</th><th>Status</th><th>Updated</th><th></th></tr>
        <?php foreach($tickets as $t): ?><tr>
          <td><?=e($t['id'])?></td><td><?=e($t['subject'])?></td><td class="muted"><?=e($t['email'])?></td>
          <td><span class="badge <?=e($t['status'])?>"><?=e($t['status'])?></span></td>
          <td class="muted"><?=e($t['updated_at'])?></td>
          <td><a href="/?route=admin/ticket&id=<?=e($t['id'])?>">Open</a></td></tr><?php endforeach; ?>
      </table>
    </div><?php tpl_footer(); break;

  case 'admin/ticket':
    admin_require(); $pdo=db(); $id=(int)($_GET['id']??0);
    $t=$pdo->prepare("SELECT * FROM tickets WHERE id=?"); $t->execute([$id]); $ticket=$t->fetch();
    if(!$ticket){ http_response_code(404); die('Not found'); }
    $r=$pdo->prepare("SELECT * FROM replies WHERE ticket_id=? ORDER BY id ASC"); $r->execute([$ticket['id']]); $replies=$r->fetchAll();
    tpl_header('Ticket #'.$ticket['id']); ?>
    <div class="card">
      <h2>#<?=e($ticket['id'])?> — <?=e($ticket['subject'])?></h2>
      <p class="muted"><?=e($ticket['email'])?> · token: <code><?=e($ticket['public_token'])?></code></p>
      <p><?=nl2br(e($ticket['body']))?></p>
      <h3>Replies</h3>
      <?php foreach($replies as $rep): ?><div class="card" style="background:#12151c"><div class="muted"><?=e($rep['author'])?> · <?=e($rep['created_at'])?></div><div><?=nl2br(e($rep['body']))?></div></div><?php endforeach; ?>
      <h3>Add admin reply</h3>
      <form method="post" action="/?route=admin/reply&id=<?=e($ticket['id'])?>"><?php csrf_field(); ?><textarea name="body" required></textarea>
        <div class="actions"><button>Reply</button>
        <?php if($ticket['status']==='open'): ?><a class="badge closed" href="/?route=admin/close&id=<?=e($ticket['id'])?>">Close</a>
        <?php else: ?><a class="badge open" href="/?route=admin/open&id=<?=e($ticket['id'])?>">Reopen</a><?php endif; ?>
      </div></form>
    </div><?php tpl_footer(); break;

  case 'admin/reply':
    admin_require(); csrf_check(); $pdo=db(); $id=(int)($_GET['id']??0);
    $body=trim($_POST['body']??''); if(!$body){ http_response_code(400); die('Empty'); }
    $pdo->prepare("INSERT INTO replies(ticket_id,author,body,created_at) VALUES (?,?,?,?)")->execute([$id,'admin',$body,gmdate('c')]);
    $pdo->prepare("UPDATE tickets SET updated_at=? WHERE id=?")->execute([gmdate('c'),$id]);
    header("Location: /?route=admin/ticket&id=".$id); exit;

  case 'admin/close':
    admin_require(); $pdo=db(); $id=(int)($_GET['id']??0);
    $pdo->prepare("UPDATE tickets SET status='closed', updated_at=? WHERE id=?")->execute([gmdate('c'),$id]);
    header("Location: /?route=admin/ticket&id=".$id); exit;

  case 'admin/open':
    admin_require(); $pdo=db(); $id=(int)($_GET['id']??0);
    $pdo->prepare("UPDATE tickets SET status='open', updated_at=? WHERE id=?")->execute([gmdate('c'),$id]);
    header("Location: /?route=admin/ticket&id=".$id); exit;

  default: http_response_code(404); echo "Not found";
}
// End of file