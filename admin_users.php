<?php
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: sign-in.html');
    exit;
}

$admin_name = ($_SESSION['first_name'] ?? 'Admin') . ' ' . ($_SESSION['last_name'] ?? '');

// Get all users
$sql = "SELECT user_id, email, first_name, last_name, is_admin, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
$users = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management - Trendify Admin</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    :root{--bg:#f5f7fb;--card:#fff;--muted:#6b7280;--accent:#111827}
    body{font-family:Inter,system-ui,Segoe UI,Arial,Helvetica,sans-serif;background:var(--bg);margin:0}
    .layout{display:flex;min-height:100vh}
    .sidebar{width:240px;background:#0f1724;color:#fff;padding:20px;box-sizing:border-box}
    .brand{font-weight:700;font-size:18px;margin-bottom:18px}
    .nav a{display:block;color:#cbd5e1;text-decoration:none;padding:10px;border-radius:6px;margin-bottom:6px;transition:all 0.2s}
    .nav a:hover{background:rgba(255,255,255,0.06);color:#fff}
    .nav a.active{background:rgba(59,130,246,0.2);color:#3b82f6}
    main{flex:1;padding:24px}
    .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
    .table-container{background:var(--card);padding:20px;border-radius:10px;overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    th,td{text-align:left;padding:12px;border-bottom:1px solid #eef2f7;color:#111827}
    th{font-size:13px;color:var(--muted);background:#f9fafb;font-weight:600}
    tr:hover{background:#f9fafb}
    .badge{display:inline-block;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600}
    .badge-admin{background:#fee2e2;color:#dc2626}
    .badge-user{background:#dbeafe;color:#0284c7}
    .btn{padding:8px 12px;border:none;border-radius:6px;cursor:pointer;font-size:13px;transition:all 0.2s}
    .btn-primary{background:#3b82f6;color:#fff}
    .btn-primary:hover{background:#2563eb}
    .btn-danger{background:#ef4444;color:#fff;padding:6px 10px}
    .btn-danger:hover{background:#dc2626}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:20px}
    .stat-card{background:var(--card);padding:16px;border-radius:10px;box-shadow:0 1px 2px rgba(0,0,0,0.04)}
    .stat-value{font-size:24px;font-weight:700;color:#111827}
    .stat-label{font-size:13px;color:var(--muted);margin-top:8px}
    @media (max-width:720px){.sidebar{display:none}.layout{flex-direction:column}main{padding:12px}}
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand"><img src="img/logo.png" alt="Trendify logo" style="width:32px;height:32px;object-fit:contain;vertical-align:middle;margin-right:10px;border-radius:4px">Trendify Admin</div>
      <nav class="nav">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_orders.php">Orders</a>
        <a href="admin_users.php" class="active">Users</a>
        <a href="logout.php">Sign Out</a>
      </nav>
    </aside>
    <main>
      <div class="header">
        <h1 style="margin:0;font-size:24px">User Management</h1>
        <div style="color:var(--muted)">Welcome back, <?= htmlspecialchars($admin_name) ?></div>
      </div>

      <div class="stats">
        <div class="stat-card">
          <div class="stat-value"><?= count($users) ?></div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= count(array_filter($users, fn($u) => $u['is_admin'] == 1)) ?></div>
          <div class="stat-label">Admin Accounts</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= count(array_filter($users, fn($u) => $u['is_admin'] == 0)) ?></div>
          <div class="stat-label">Regular Users</div>
        </div>
      </div>

      <div class="table-container">
        <h2 style="margin:0 0 16px 0;font-size:18px">Registered Users</h2>
        <?php if (count($users) > 0): ?>
          <table>
            <thead>
              <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Joined Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td><?= htmlspecialchars($user['user_id']) ?></td>
                  <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                  <td><?= htmlspecialchars($user['email']) ?></td>
                  <td>
                    <?php if ($user['is_admin'] == 1): ?>
                      <span class="badge badge-admin">Admin</span>
                    <?php else: ?>
                      <span class="badge badge-user">User</span>
                    <?php endif; ?>
                  </td>
                  <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                  <td>
                    <?php if ($_SESSION['user_id'] != $user['user_id']): ?>
                      <button class="delete-btn" onclick="deleteUser(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')" style="padding:6px 12px;background:#ef4444;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;font-weight:600">Remove</button>
                    <?php else: ?>
                      <span style="color:var(--muted);font-size:12px">You</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="text-align:center;color:var(--muted);padding:20px">No users found</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
  <script>
    function deleteUser(userId, userName) {
      if (confirm('Are you sure you want to delete user "' + userName + '"? This cannot be undone.')) {
        fetch('delete_user.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ user_id: userId })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('User deleted successfully');
            location.reload();
          } else {
            alert('Error deleting user: ' + data.message);
          }
        })
        .catch(err => {
          alert('Error: ' + err.message);
        });
      }
    }
  </script>
</body>
</html>
<?php
$conn->close();
?>
