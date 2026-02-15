<?php
class Storage {
    private $file = 'data.json';
    private $deleted_file = 'deleted_data.json';
    private $tabs_file = 'tabs_data.json';
    private $users_file = 'users.json';
    private $replies_file = 'replies.json';
    private $data = [];
    private $deleted_data = [];
    private $tabs_data = [];
    private $users_data = [];
    private $replies_data = [];
    
    public function __construct() {
        if (file_exists($this->users_file)) {
            $this->users_data = json_decode(file_get_contents($this->users_file), true) ?: [];
        }
        if (!is_array($this->users_data)) $this->users_data = [];
        
        if (file_exists($this->replies_file)) {
            $this->replies_data = json_decode(file_get_contents($this->replies_file), true) ?: [];
        }
        if (!is_array($this->replies_data)) $this->replies_data = [];
        
        if (file_exists($this->tabs_file)) {
            $this->tabs_data = json_decode(file_get_contents($this->tabs_file), true) ?: [];
        }
        if (!is_array($this->tabs_data)) $this->tabs_data = [];
        
        if (file_exists($this->file)) {
            $this->data = json_decode(file_get_contents($this->file), true) ?: [];
        }
        if (!is_array($this->data)) $this->data = [];
        
        if (file_exists($this->deleted_file)) {
            $this->deleted_data = json_decode(file_get_contents($this->deleted_file), true) ?: [];
        }
        if (!is_array($this->deleted_data)) $this->deleted_data = [];
        
        if (empty($this->users_data)) {
            $client_id = $this->getClientID();
            $this->users_data[] = [
                'id' => 1,
                'username' => 'admin',
                'password' => md5('admin'),
                'role' => 'admin',
                'client_id' => $client_id,
                'permissions' => [
                    'view' => true,
                    'add' => true,
                    'edit' => true,
                    'edit_own' => false,
                    'delete' => true,
                    'delete_own' => false,
                    'comment' => true,
                    'change_status' => true,
                    'delete_comment' => true,
                    'delete_comment_own' => false,
                    'create_tab' => true,
                    'view_tab_all' => true
                ],
                'tab_permissions' => [],
                'created_at' => date('d.m.Y H:i')
            ];
            $this->saveUsers();
        }
        
        if (empty($this->tabs_data)) {
            $this->tabs_data[] = [
                'id' => 1,
                'name' => 'Все записи',
                'type' => 'all',
                'created_at' => date('d.m.Y H:i'),
                'created_by' => 'admin',
                'is_default' => true,
                'order' => 0,
                'permissions' => [
                    'add' => false,
                    'edit' => true,
                    'delete' => false
                ]
            ];
            $this->tabs_data[] = [
                'id' => 2,
                'name' => 'Корзина',
                'type' => 'trash',
                'created_at' => date('d.m.Y H:i'),
                'created_by' => 'admin',
                'is_default' => true,
                'order' => 999,
                'permissions' => [
                    'add' => false,
                    'edit' => false,
                    'delete' => false
                ]
            ];
            $this->saveTabs();
        }
    }
    
    private function save() {
        file_put_contents($this->file, json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function saveDeleted() {
        file_put_contents($this->deleted_file, json_encode($this->deleted_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function saveTabs() {
        usort($this->tabs_data, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        file_put_contents($this->tabs_file, json_encode($this->tabs_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function saveUsers() {
        file_put_contents($this->users_file, json_encode($this->users_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function saveReplies() {
        file_put_contents($this->replies_file, json_encode($this->replies_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function getClientIP() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    public function getClientID() {
        return substr(md5($this->getClientIP()), 0, 8);
    }
    
    public function getUserByUsername($username) {
        foreach ($this->users_data as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }
    
    public function bindClientToUser($username, $client_id) {
        foreach ($this->users_data as &$user) {
            if ($user['username'] === $username) {
                $user['client_id'] = $client_id;
                $this->saveUsers();
                return true;
            }
        }
        return false;
    }
    
    public function checkPassword($username, $password) {
        foreach ($this->users_data as $user) {
            if ($user['username'] === $username && $user['password'] === md5($password)) {
                return $user;
            }
        }
        return false;
    }
    
    public function getAllUsers() {
        return $this->users_data;
    }
    
    public function addUser($username, $password, $permissions, $tab_permissions = []) {
        foreach ($this->users_data as $user) {
            if ($user['username'] === $username) {
                return false;
            }
        }
        
        $id = 1;
        if (!empty($this->users_data)) {
            $id = max(array_column($this->users_data, 'id')) + 1;
        }
        
        $this->users_data[] = [
            'id' => $id,
            'username' => $username,
            'password' => md5($password),
            'role' => 'user',
            'permissions' => $permissions,
            'tab_permissions' => $tab_permissions,
            'client_id' => null,
            'created_at' => date('d.m.Y H:i')
        ];
        
        $this->saveUsers();
        return true;
    }
    
    public function updateUser($id, $username, $password, $permissions, $tab_permissions = null) {
        foreach ($this->users_data as &$user) {
            if ($user['id'] == $id) {
                $user['username'] = $username;
                if (!empty($password)) {
                    $user['password'] = md5($password);
                }
                $user['permissions'] = $permissions;
                if ($tab_permissions !== null) {
                    $user['tab_permissions'] = $tab_permissions;
                }
                $this->saveUsers();
                return true;
            }
        }
        return false;
    }
    
    public function deleteUser($id) {
        foreach ($this->users_data as $k => $user) {
            if ($user['id'] == $id && $user['username'] !== 'admin') {
                array_splice($this->users_data, $k, 1);
                $this->saveUsers();
                return true;
            }
        }
        return false;
    }
    
    public function checkPermission($user, $action, $item_author = null) {
        if (!$user) return false;
        if ($user['role'] === 'admin') return true;
        
        $perms = $user['permissions'] ?? [];
        
        if ($action === 'view') return $perms['view'] ?? false;
        if ($action === 'add') return $perms['add'] ?? false;
        if ($action === 'comment') return $perms['comment'] ?? false;
        if ($action === 'change_status') return $perms['change_status'] ?? false;
        if ($action === 'create_tab') return $perms['create_tab'] ?? false;
        if ($action === 'view_tab_all') return $perms['view_tab_all'] ?? false;
        
        if ($action === 'edit') {
            if (!($perms['edit'] ?? false)) return false;
            if ($perms['edit_own'] ?? false) {
                return $item_author && $item_author === $user['username'];
            }
            return true;
        }
        
        if ($action === 'delete') {
            if (!($perms['delete'] ?? false)) return false;
            if ($perms['delete_own'] ?? false) {
                return $item_author && $item_author === $user['username'];
            }
            return true;
        }
        
        if ($action === 'delete_comment') {
            if (!($perms['delete_comment'] ?? false)) return false;
            if ($perms['delete_comment_own'] ?? false) {
                return $item_author && $item_author === $user['username'];
            }
            return true;
        }
        
        return false;
    }
    
    public function checkTabPermission($user, $tab, $action = 'view') {
        if (!$user) return false;
        if ($user['role'] === 'admin') return true;
        
        $tab_permissions = $user['tab_permissions'] ?? [];
        
        if ($action === 'view' && ($this->checkPermission($user, 'view_tab_all') || in_array($tab['id'], $tab_permissions))) {
            return true;
        }
        
        if ($action !== 'view') {
            $tab_perms = $tab['permissions'] ?? [];
            return $tab_perms[$action] ?? false;
        }
        
        return false;
    }
    
    public function getUserTabs($user) {
        if (!$user) return [];
        if ($user['role'] === 'admin') return $this->tabs_data;
        
        $tabs = [];
        $tab_permissions = $user['tab_permissions'] ?? [];
        
        if ($this->checkPermission($user, 'view_tab_all')) {
            return $this->tabs_data;
        }
        
        foreach ($this->tabs_data as $tab) {
            if (in_array($tab['id'], $tab_permissions)) {
                $tabs[] = $tab;
            }
        }
        
        usort($tabs, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        return $tabs;
    }
    
    public function getTabs() {
        usort($this->tabs_data, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        return $this->tabs_data;
    }
    
    public function addTab($name, $permissions = null) {
        $id = 1;
        if (!empty($this->tabs_data)) {
            $ids = array_column($this->tabs_data, 'id');
            $id = !empty($ids) ? max($ids) + 1 : 1;
        }
        
        $max_order = 0;
        foreach ($this->tabs_data as $tab) {
            if (($tab['order'] ?? 0) > $max_order && $tab['type'] !== 'trash' && $tab['type'] !== 'all') {
                $max_order = $tab['order'] ?? 0;
            }
        }
        
        if ($permissions === null) {
            $permissions = [
                'add' => true,
                'edit' => true,
                'delete' => true
            ];
        }
        
        $this->tabs_data[] = [
            'id' => $id,
            'name' => $name,
            'type' => 'custom',
            'created_at' => date('d.m.Y H:i'),
            'created_by' => $_SESSION['user']['username'] ?? 'admin',
            'is_default' => false,
            'order' => $max_order + 10,
            'permissions' => $permissions
        ];
        
        $this->saveTabs();
        return $id;
    }
    
    public function updateTab($id, $name, $order = null) {
        foreach ($this->tabs_data as &$tab) {
            if ($tab['id'] == $id && !$tab['is_default']) {
                $tab['name'] = $name;
                if ($order !== null) {
                    $tab['order'] = $order;
                }
                $this->saveTabs();
                return true;
            }
        }
        return false;
    }
    
    public function deleteTab($id) {
        foreach ($this->tabs_data as $k => $tab) {
            if ($tab['id'] == $id && !$tab['is_default']) {
                array_splice($this->tabs_data, $k, 1);
                $this->saveTabs();
                return true;
            }
        }
        return false;
    }
    
    public function updateTabPermissions($id, $permissions) {
        foreach ($this->tabs_data as &$tab) {
            if ($tab['id'] == $id && $tab['type'] === 'custom') {
                $tab['permissions'] = $permissions;
                $this->saveTabs();
                return true;
            }
        }
        return false;
    }
    
    public function reorderTabs($order) {
        foreach ($this->tabs_data as &$tab) {
            if (isset($order[$tab['id']])) {
                $tab['order'] = $order[$tab['id']];
            }
        }
        $this->saveTabs();
        return true;
    }
    
    public function getItemsByTab($tab_id, $user = null) {
        $tab = null;
        foreach ($this->tabs_data as $t) {
            if ($t['id'] == $tab_id) {
                $tab = $t;
                break;
            }
        }
        
        if (!$tab) return [];
        
        if ($user && !$this->checkTabPermission($user, $tab, 'view')) {
            return [];
        }
        
        if ($tab['type'] === 'trash') {
            return array_reverse($this->deleted_data);
        } elseif ($tab['type'] === 'all') {
            return array_reverse($this->data);
        } else {
            return array_reverse(array_filter($this->data, function($item) use ($tab_id) {
                return isset($item['tab_id']) && $item['tab_id'] == $tab_id;
            }));
        }
    }
    
    public function getTabName($tab_id) {
        foreach ($this->tabs_data as $tab) {
            if ($tab['id'] == $tab_id) {
                return $tab['name'];
            }
        }
        return 'Неизвестно';
    }
    
    public function getTitles($tab_id = null, $user = null) {
        $items = $this->getItemsByTab($tab_id ?? 1, $user);
        $titles = [];
        foreach ($items as $item) {
            if (!in_array($item['title'], $titles)) {
                $titles[] = $item['title'];
            }
        }
        sort($titles);
        return $titles;
    }
    
    public function add($title, $desc, $tab_id = 1) {
        $tab = null;
        foreach ($this->tabs_data as $t) {
            if ($t['id'] == $tab_id) {
                $tab = $t;
                break;
            }
        }
        
        if (!$tab) return false;
        if ($tab['type'] === 'all' || $tab['type'] === 'trash') return false;
        
        $id = 1;
        if (!empty($this->data)) {
            $ids = array_column($this->data, 'id');
            $id = !empty($ids) ? max($ids) + 1 : 1;
        }
        
        $user = $_SESSION['user'] ?? null;
        $username = $user ? $user['username'] : 'guest';
        $client_id = $this->getClientID();
        
        $this->data[] = [
            'id' => $id,
            'tab_id' => $tab_id,
            'tab_name' => $tab['name'],
            'title' => $title,
            'description' => $desc,
            'created' => date('d.m.Y H:i'),
            'updated' => null,
            'updated_by' => null,
            'author' => $username,
            'author_id' => $client_id,
            'is_actual' => true,
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
            'order' => count($this->data)
        ];
        $this->save();
        return $id;
    }
    
    public function update($id, $title, $desc) {
        foreach ($this->data as &$item) {
            if ($item['id'] == $id) {
                $item['title'] = $title;
                $item['description'] = $desc;
                $item['updated'] = date('d.m.Y H:i');
                $item['updated_by'] = $_SESSION['user']['username'] ?? null;
                $item['updated_by_id'] = $this->getClientID();
                $this->save();
                return true;
            }
        }
        return false;
    }
    
    public function toggleActual($id) {
        foreach ($this->data as &$item) {
            if ($item['id'] == $id) {
                $item['is_actual'] = !$item['is_actual'];
                $item['updated'] = date('d.m.Y H:i');
                $item['updated_by'] = $_SESSION['user']['username'] ?? null;
                $item['updated_by_id'] = $this->getClientID();
                $this->save();
                return $item['is_actual'];
            }
        }
        return false;
    }
    
    public function toggleCompleted($id) {
        foreach ($this->data as &$item) {
            if ($item['id'] == $id) {
                $item['is_completed'] = !$item['is_completed'];
                $item['completed_at'] = $item['is_completed'] ? date('d.m.Y H:i') : null;
                $item['completed_by'] = $item['is_completed'] ? ($_SESSION['user']['username'] ?? null) : null;
                $item['completed_by_id'] = $item['is_completed'] ? $this->getClientID() : null;
                $item['updated'] = date('d.m.Y H:i');
                $item['updated_by'] = $_SESSION['user']['username'] ?? null;
                $item['updated_by_id'] = $this->getClientID();
                $this->save();
                return $item['is_completed'];
            }
        }
        return false;
    }
    
    public function delete($id) {
        foreach ($this->data as $k => $item) {
            if ($item['id'] == $id) {
                $item['deleted_at'] = date('d.m.Y H:i');
                $item['deleted_by'] = $_SESSION['user']['username'] ?? 'guest';
                $item['deleted_by_id'] = $this->getClientID();
                $item['original_order'] = $item['order'] ?? $k;
                $this->deleted_data[] = $item;
                array_splice($this->data, $k, 1);
                $this->save();
                $this->saveDeleted();
                return true;
            }
        }
        return false;
    }
    
    public function restore($id) {
        foreach ($this->deleted_data as $k => $item) {
            if ($item['id'] == $id) {
                $order = $item['original_order'] ?? count($this->data);
                unset($item['deleted_at']);
                unset($item['deleted_by']);
                unset($item['deleted_by_id']);
                unset($item['original_order']);
                $item['order'] = $order;
                $item['updated'] = date('d.m.Y H:i');
                $item['updated_by'] = $_SESSION['user']['username'] ?? null;
                $item['updated_by_id'] = $this->getClientID();
                
                array_splice($this->data, $order, 0, [$item]);
                array_splice($this->deleted_data, $k, 1);
                
                foreach ($this->data as $idx => &$data_item) {
                    $data_item['order'] = $idx;
                }
                
                $this->save();
                $this->saveDeleted();
                return true;
            }
        }
        return false;
    }
    
    public function get($id) {
        foreach ($this->data as $item) if ($item['id'] == $id) return $item;
        return null;
    }
    
    public function getReplies($item_id) {
        return array_reverse(array_filter($this->replies_data, function($reply) use ($item_id) {
            return $reply['item_id'] == $item_id && !isset($reply['deleted_at']);
        }));
    }
    
    public function addReply($item_id, $title, $content) {
        $id = 1;
        if (!empty($this->replies_data)) {
            $ids = array_column($this->replies_data, 'id');
            $id = !empty($ids) ? max($ids) + 1 : 1;
        }
        
        $user = $_SESSION['user'] ?? null;
        $username = $user ? $user['username'] : 'guest';
        $client_id = $this->getClientID();
        
        $this->replies_data[] = [
            'id' => $id,
            'item_id' => $item_id,
            'title' => $title,
            'content' => $content,
            'created_at' => date('d.m.Y H:i'),
            'author' => $username,
            'author_id' => $client_id
        ];
        
        $this->saveReplies();
        return $id;
    }
    
    public function deleteReply($id) {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return false;
        
        foreach ($this->replies_data as $k => $reply) {
            if ($reply['id'] == $id) {
                if ($this->checkPermission($user, 'delete_comment', $reply['author'] ?? null)) {
                    $reply['deleted_at'] = date('d.m.Y H:i');
                    $reply['deleted_by'] = $user['username'];
                    $reply['deleted_by_id'] = $this->getClientID();
                    $this->replies_data[$k] = $reply;
                    $this->saveReplies();
                    return true;
                }
            }
        }
        return false;
    }
}

session_start();
$db = new Storage();

// ========== HTML ЭКСПОРТ ==========
if (isset($_GET['export_html'])) {
    $tab_id = isset($_GET['tab']) ? (int)$_GET['tab'] : 1;
    $user = $_SESSION['user'] ?? null;
    $items = $db->getItemsByTab($tab_id, $user);
    
    $current_tab_name = 'Все записи';
    $tabs = $db->getTabs();
    foreach ($tabs as $tab) {
        if ($tab['id'] == $tab_id) {
            $current_tab_name = $tab['name'];
            break;
        }
    }
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="export.html"');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Экспорт - <?=htmlspecialchars($current_tab_name)?></title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 30px; 
            background: white; 
            color: #212529;
        }
        .header { 
            margin-bottom: 30px; 
            padding-bottom: 20px; 
            border-bottom: 2px solid #343a40;
        }
        h1 { 
            color: #212529; 
            font-size: 24px; 
            margin-bottom: 5px; 
        }
        .info { 
            color: #6c757d; 
            font-size: 14px; 
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th { 
            background: #343a40; 
            color: white; 
            padding: 12px; 
            text-align: left; 
            font-weight: 500;
        }
        td { 
            border: 1px solid #dee2e6; 
            padding: 10px; 
        }
        tr:hover td { 
            background: #f8f9fa; 
        }
        .badge { 
            background: #e9ecef; 
            padding: 2px 10px; 
            border-radius: 20px; 
            font-size: 12px; 
        }
        .footer { 
            margin-top: 30px; 
            color: #6c757d; 
            font-size: 12px; 
            text-align: center; 
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 <?=htmlspecialchars($current_tab_name)?></h1>
        <div class="info">
            📅 Дата экспорта: <?=date('d.m.Y H:i:s')?> • 
            👤 <?=htmlspecialchars($_SESSION['user']['username'] ?? 'guest')?> • 
            🖥️ <?=htmlspecialchars($db->getClientID())?> • 
            📊 Всего записей: <?=count($items)?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 15%;">Вкладка</th>
                <th style="width: 20%;">Заголовок</th>
                <th style="width: 25%;">Описание</th>
                <th style="width: 10%;">Автор</th>
                <th style="width: 10%;">ID</th>
                <th style="width: 10%;">Статус</th>
                <th style="width: 10%;">Дата</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($items as $item): ?>
            <tr>
                <td style="text-align: center;"><?=$i++?></td>
                <td><span class="badge"><?=htmlspecialchars($db->getTabName($item['tab_id'] ?? 1))?></span></td>
                <td><strong><?=htmlspecialchars($item['title'] ?? '')?></strong></td>
                <td><?=nl2br(htmlspecialchars($item['description'] ?? ''))?></td>
                <td><?=htmlspecialchars($item['author'] ?? 'guest')?></td>
                <td><span class="badge"><?=htmlspecialchars($item['author_id'] ?? '')?></span></td>
                <td>
                    <?php if (isset($item['is_completed']) && $item['is_completed']): ?>
                        ✅ Выполнено
                    <?php elseif (isset($item['is_actual']) && $item['is_actual']): ?>
                        ⭐ Актуально
                    <?php else: ?>
                        ⏳ Не актуально
                    <?php endif; ?>
                </td>
                <td style="font-size: 12px;">
                    📅 <?=$item['created'] ?? ''?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        Всего записей: <?=count($items)?> • Для печати: Ctrl+P
    </div>
</body>
</html>
<?php
    exit;
}

// ========== ПРОВЕРКА АВТОРИЗАЦИИ ==========
$current_user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

if (isset($_POST['login'])) {
    $user = $db->checkPassword($_POST['username'], $_POST['password']);
    if ($user) {
        $_SESSION['user'] = $user;
        $client_id = $db->getClientID();
        $db->bindClientToUser($user['username'], $client_id);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Неверное имя пользователя или пароль';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!$current_user) {
    $client_id = $db->getClientID();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 400px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .client-id {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 5px;
            font-size: 14px;
            color: #495057;
        }
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .info {
            margin-top: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔐 Вход в систему</h1>
        <div class="client-id">
            🖥️ Ваш ID: <?=htmlspecialchars($client_id)?>
        </div>
        <?php if (isset($login_error)): ?>
            <div class="error"><?=$login_error?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Имя пользователя" value="admin" required autofocus>
            <input type="password" name="password" placeholder="Пароль" value="admin" required>
            <button type="submit" name="login">Войти</button>
        </form>
        <div class="info">
            Админ: admin / admin
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// ========== ОБРАБОТКА POST ЗАПРОСОВ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $act = $_POST['action'];
    
    if ($act == 'add') echo json_encode(['ok'=>$db->add($_POST['title'], $_POST['description'], $_POST['tab_id'])]);
    if ($act == 'update') echo json_encode(['ok'=>$db->update($_POST['id'], $_POST['title'], $_POST['description'])]);
    if ($act == 'delete') echo json_encode(['ok'=>$db->delete($_POST['id'])]);
    if ($act == 'restore') echo json_encode(['ok'=>$db->restore($_POST['id'])]);
    if ($act == 'get') echo json_encode($db->get($_POST['id']));
    if ($act == 'getTitles') echo json_encode($db->getTitles($_POST['tab_id'], $current_user));
    if ($act == 'toggleActual') echo json_encode(['ok'=>$db->toggleActual($_POST['id'])]);
    if ($act == 'toggleCompleted') echo json_encode(['ok'=>$db->toggleCompleted($_POST['id'])]);
    if ($act == 'addTab') {
        $permissions = isset($_POST['permissions']) ? json_decode($_POST['permissions'], true) : null;
        echo json_encode(['ok'=>$db->addTab($_POST['name'], $permissions)]);
    }
    if ($act == 'updateTab') {
        $order = isset($_POST['order']) ? (int)$_POST['order'] : null;
        echo json_encode(['ok'=>$db->updateTab($_POST['id'], $_POST['name'], $order)]);
    }
    if ($act == 'deleteTab') echo json_encode(['ok'=>$db->deleteTab($_POST['id'])]);
    if ($act == 'updateTabPermissions') echo json_encode(['ok'=>$db->updateTabPermissions($_POST['id'], json_decode($_POST['permissions'], true))]);
    if ($act == 'reorderTabs') echo json_encode(['ok'=>$db->reorderTabs(json_decode($_POST['order'], true))]);
    if ($act == 'getReplies') echo json_encode(array_values($db->getReplies($_POST['item_id'])));
    if ($act == 'addReply') echo json_encode(['ok'=>$db->addReply($_POST['item_id'], $_POST['title'], $_POST['content'])]);
    if ($act == 'deleteReply') echo json_encode(['ok'=>$db->deleteReply($_POST['id'])]);
    if ($act == 'getUsers') echo json_encode($db->getAllUsers());
    if ($act == 'addUser') {
        $tab_permissions = isset($_POST['tab_permissions']) ? json_decode($_POST['tab_permissions'], true) : [];
        echo json_encode(['ok'=>$db->addUser($_POST['username'], $_POST['password'], json_decode($_POST['permissions'], true), $tab_permissions)]);
    }
    if ($act == 'updateUser') {
        $tab_permissions = isset($_POST['tab_permissions']) ? json_decode($_POST['tab_permissions'], true) : null;
        echo json_encode(['ok'=>$db->updateUser($_POST['id'], $_POST['username'], $_POST['password'], json_decode($_POST['permissions'], true), $tab_permissions)]);
    }
    if ($act == 'deleteUser') echo json_encode(['ok'=>$db->deleteUser($_POST['id'])]);
    exit;
}

// ========== ПОЛУЧАЕМ ДАННЫЕ ДЛЯ ОСНОВНОЙ СТРАНИЦЫ ==========
$all_tabs = $db->getTabs();
$user_tabs = $db->getUserTabs($current_user);
$current_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : (count($user_tabs) > 0 ? $user_tabs[0]['id'] : 1);

$current_tab_data = null;
foreach ($all_tabs as $tab) {
    if ($tab['id'] == $current_tab) {
        $current_tab_data = $tab;
        break;
    }
}

if (!$db->checkTabPermission($current_user, $current_tab_data, 'view')) {
    if (count($user_tabs) > 0) {
        header('Location: ?tab=' . $user_tabs[0]['id']);
        exit;
    }
}

$items = $db->getItemsByTab($current_tab, $current_user);
$titles = $db->getTitles($current_tab, $current_user);
$can_add_to_tab = $current_tab_data ? ($db->checkTabPermission($current_user, $current_tab_data, 'add') && $db->checkPermission($current_user, 'add')) : false;
$current_client_id = $db->getClientID();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Контейнеры - Менеджер записей</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-size: 14px; }
        body { 
            background: #f8f9fa; 
            font-family: 'Segoe UI', Arial, sans-serif;
            padding-top: 130px;
        }
        
        .header-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 8px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-bottom: 2px solid #343a40;
            z-index: 1000;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .header-left h1 {
            font-size: 20px;
            color: #212529;
            font-weight: 600;
            margin: 0;
        }
        
        .user-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .role-badge {
            background: #ffc107;
            color: #212529;
            padding: 3px 10px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .client-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .header-right {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn { 
            background: #4CAF50; 
            color: white; 
            border: 1px solid #45a049; 
            padding: 5px 12px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-weight: 500;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn:hover { 
            background: #45a049;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn.edit { background: #2196F3; border-color: #1e87db; }
        .btn.edit:hover { background: #1e87db; }
        .btn.del { background: #f44336; border-color: #d32f2f; }
        .btn.del:hover { background: #d32f2f; }
        .btn.excel { background: #217346; border-color: #1e5c3a; }
        .btn.excel:hover { background: #1e5c3a; }
        .btn.html { background: #ff9800; border-color: #f57c00; }
        .btn.html:hover { background: #f57c00; }
        .btn.restore { background: #28a745; border-color: #218838; }
        .btn.restore:hover { background: #218838; }
        .btn.logout { background: #dc3545; border-color: #c82333; }
        .btn.logout:hover { background: #c82333; }
        .btn.users { background: #6f42c1; border-color: #5e35b1; }
        .btn.users:hover { background: #5e35b1; }
        .btn.reply { background: #17a2b8; border-color: #138496; }
        .btn.reply:hover { background: #138496; }
        .btn.tab-edit { background: #fd7e14; border-color: #dc6b12; }
        .btn.tab-edit:hover { background: #dc6b12; }
        .btn.tab-move { background: #6c757d; border-color: #5a6268; }
        .btn.tab-move:hover { background: #5a6268; }
        
        .tabs-container {
            background: white;
            border-radius: 4px;
            padding: 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            border-top: 1px solid #dee2e6;
        }
        
        .tabs-list {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            flex: 1;
            align-items: center;
        }
        
        .tab-item {
            padding: 4px 14px;
            background: #e9ecef;
            border-radius: 16px;
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .tab-item:hover {
            background: #dee2e6;
        }
        
        .tab-item.active {
            background: #343a40;
            color: white;
        }
        
        .tab-item.trash {
            background: #f8d7da;
            color: #721c24;
        }
        
        .tab-item.trash.active {
            background: #dc3545;
            color: white;
        }
        
        .tab-item.all {
            background: #e3f2fd;
            color: #0d47a1;
        }
        
        .tab-item.all.active {
            background: #1976d2;
            color: white;
        }
        
        .tab-controls {
            display: flex;
            gap: 4px;
            margin-left: 10px;
        }
        
        .add-tab-btn {
            background: none;
            border: 1px dashed #6c757d;
            color: #6c757d;
            padding: 4px 12px;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 500;
            font-size: 11px;
        }
        
        .add-tab-btn:hover {
            background: #e9ecef;
        }
        
        .delete-tab-btn, .edit-tab-btn {
            background: none;
            border: none;
            margin-left: 4px;
            cursor: pointer;
            opacity: 0.7;
            font-size: 14px;
        }
        
        .delete-tab-btn {
            color: #dc3545;
        }
        
        .edit-tab-btn {
            color: #fd7e14;
        }
        
        .delete-tab-btn:hover, .edit-tab-btn:hover {
            opacity: 1;
        }
        
        .main-content {
            padding: 15px 20px;
        }
        
        .table-container {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white;
            min-width: 1300px;
        }
        
        .table th { 
            background: #343a40; 
            color: white; 
            padding: 8px; 
            text-align: left; 
            font-weight: 500;
            border-right: 1px solid #495057;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .table th:last-child { border-right: none; }
        
        .table td { 
            padding: 8px; 
            border: 1px solid #dee2e6;
            vertical-align: middle;
            background: white;
        }
        
        .table tr:hover td {
            background: #f1f3f5;
        }
        
        .completed-row td {
            background: #f8f9fa;
            color: #6c757d;
            text-decoration: line-through;
        }
        
        .not-actual-row td {
            background: #fff3e0;
            opacity: 0.8;
        }
        
        .deleted-row td {
            background: #f8d7da;
        }
        
        .tab-badge {
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            display: inline-block;
            white-space: nowrap;
        }
        
        .author-badge {
            background: #e3f2fd;
            padding: 2px 8px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            color: #0d47a1;
            display: inline-block;
            white-space: nowrap;
        }
        
        .author-badge.self {
            background: #c8e6c9;
            color: #1e4620;
        }
        
        .client-id-badge {
            background: #17a2b8;
            color: white;
            padding: 2px 8px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        
        .checkbox {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .checkbox:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .reply-badge {
            background: #17a2b8;
            color: white;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 10px;
            margin-left: 5px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .status-container {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #6c757d;
            white-space: nowrap;
        }
        
        .actions { 
            display: flex; 
            gap: 4px; 
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-sm { 
            padding: 3px 8px; 
            font-size: 11px; 
            border-radius: 3px;
        }
        
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 2000;
        }
        
        .modal-content { 
            background: white; 
            width: 450px; 
            margin: 100px auto; 
            padding: 20px; 
            border-radius: 8px; 
            border: 2px solid #343a40;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-large {
            width: 800px;
        }
        
        .title-selector select {
            width: 100%;
            padding: 6px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .or-divider {
            text-align: center;
            margin: 8px 0;
            color: #6c757d;
            font-size: 12px;
        }
        
        input, textarea, select { 
            width: 100%; 
            padding: 6px; 
            margin: 4px 0; 
            border: 1px solid #dee2e6; 
            border-radius: 4px; 
            font-size: 13px;
        }
        
        textarea { 
            height: 100px; 
            resize: vertical; 
        }
        
        h3 { 
            margin-bottom: 12px; 
            color: #212529;
            border-bottom: 2px solid #343a40;
            padding-bottom: 8px;
            font-size: 18px;
        }
        
        h4 { 
            margin: 15px 0 10px;
            color: #495057;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        .permissions-group {
            margin: 12px 0;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .permission-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .permission-row:last-child {
            border-bottom: none;
        }
        
        .permission-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .permission-own {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .permission-own input {
            width: 16px;
            height: 16px;
            margin: 0;
        }
        
        .tab-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            margin: 10px 0;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .tab-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px;
        }
        
        .tab-checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
        }
        
        .reply-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
        }
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .reply-title {
            font-weight: 600;
            color: #17a2b8;
            font-size: 13px;
        }
        
        .reply-meta {
            font-size: 11px;
            color: #6c757d;
        }
        
        .reply-content {
            color: #495057;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .tab-order-controls {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            align-items: center;
        }
        
        .tab-order-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        
        .tab-order-item:hover {
            background: #f1f3f5;
        }
        
        .tab-order-handle {
            cursor: move;
            color: #6c757d;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            body { padding-top: 180px; }
            .header-top { flex-direction: column; gap: 8px; }
            .header-left, .header-right { justify-content: center; }
            .header-left { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <div class="header-fixed">
        <div class="header-top">
            <div class="header-left">
                <h1>📋 Менеджер записей</h1>
                <span class="user-badge">
                    👤 <?=htmlspecialchars($current_user['username'])?>
                </span>
                <span class="role-badge">
                    <?=$current_user['role'] == 'admin' ? 'Администратор' : 'Пользователь'?>
                </span>
                <span class="client-badge">
                    🖥️ ID: <?=htmlspecialchars($current_client_id)?>
                </span>
            </div>
            <div class="header-right">
                <?php if ($can_add_to_tab): ?>
                <button class="btn" onclick="openModal()">➕ Добавить</button>
                <?php endif; ?>
                
                <?php if ($current_tab_data && $current_tab_data['type'] !== 'trash'): ?>
                <button class="btn excel" onclick="exportExcel()">📊 Excel</button>
                <button class="btn html" onclick="exportHTML(<?=$current_tab?>)">🌐 HTML</button>
                <?php endif; ?>
                
                <?php if ($db->checkPermission($current_user, 'create_tab')): ?>
                <button class="btn" onclick="addTab()">➕ Вкладка</button>
                <?php endif; ?>
                
                <?php if ($current_user['role'] == 'admin'): ?>
                <button class="btn users" onclick="showUsers()">👥 Пользователи</button>
                <button class="btn tab-edit" onclick="manageTabs()">📑 Управление</button>
                <?php endif; ?>
                
                <a href="?logout=1" class="btn logout">🚪 Выйти</a>
            </div>
        </div>
        
        <div class="tabs-container">
            <div class="tabs-list">
                <?php foreach ($user_tabs as $tab): 
                    $tab_class = 'tab-item';
                    if ($tab['type'] == 'trash') $tab_class .= ' trash';
                    if ($tab['type'] == 'all') $tab_class .= ' all';
                    if ($current_tab == $tab['id']) $tab_class .= ' active';
                ?>
                <div style="display: flex; align-items: center;">
                    <a href="?tab=<?=$tab['id']?>" class="<?=$tab_class?>">
                        <?php if ($tab['type'] == 'trash'): ?>🗑️<?php endif; ?>
                        <?php if ($tab['type'] == 'all'): ?>📋<?php endif; ?>
                        <?=htmlspecialchars($tab['name'])?>
                        <?php if ($tab['type'] == 'trash'): ?>
                            (<?=count($db->getItemsByTab($tab['id'], $current_user))?>)
                        <?php endif; ?>
                        <?php if ($tab['type'] == 'all'): ?>
                            (<?=count($db->getItemsByTab($tab['id'], $current_user))?>)
                        <?php endif; ?>
                    </a>
                    <?php if (!$tab['is_default'] && $current_user['role'] == 'admin' && $tab['type'] == 'custom'): ?>
                    <span class="edit-tab-btn" onclick="editTab(<?=$tab['id']?>, '<?=htmlspecialchars($tab['name'])?>')" title="Редактировать">✏️</span>
                    <span class="delete-tab-btn" onclick="deleteTab(event, <?=$tab['id']?>, '<?=htmlspecialchars($tab['name'])?>')" title="Удалить">×</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;">№</th>
                        <?php if ($current_tab_data && $current_tab_data['type'] == 'all'): ?>
                        <th style="width: 100px;">ВКЛАДКА</th>
                        <?php endif; ?>
                        <th style="width: 150px;">ЗАГОЛОВОК</th>
                        <th>ОПИСАНИЕ</th>
                        <th style="width: 80px;">АВТОР</th>
                        <th style="width: 80px;">ID</th>
                        <th style="width: 250px;">ДАТЫ / СТАТУС</th>
                        <th style="width: 140px;">ДЕЙСТВИЯ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$items): ?>
                    <tr>
                        <td colspan="<?=$current_tab_data && $current_tab_data['type'] == 'all' ? 8 : 7?>" style="padding: 0;">
                            <div class="empty-state">
                                <p style="font-size: 16px; margin-bottom: 10px;">
                                    <?php if ($current_tab_data && $current_tab_data['type'] == 'trash'): ?>🗑️ Корзина пуста<?php else: ?>📭 Нет записей<?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($items as $item): 
                            $is_self = ($item['author'] ?? '') == $current_user['username'];
                            $row_class = '';
                            if ($current_tab_data && $current_tab_data['type'] !== 'trash') {
                                if (isset($item['is_completed']) && $item['is_completed']) $row_class = 'completed-row';
                                elseif (isset($item['is_actual']) && !$item['is_actual']) $row_class = 'not-actual-row';
                            } else {
                                $row_class = 'deleted-row';
                            }
                            
                            $replies = $db->getReplies($item['id']);
                            $replies_count = count($replies);
                            $can_change_status = $db->checkPermission($current_user, 'change_status', $item['author'] ?? null);
                            $can_comment = $db->checkPermission($current_user, 'comment', $item['author'] ?? null);
                            $item_tab_name = $db->getTabName($item['tab_id'] ?? 1);
                        ?>
                        <tr id="row-<?=$item['id']?>" class="<?=$row_class?>">
                            <td style="text-align: center;"><?=$i++?></td>
                            
                            <?php if ($current_tab_data && $current_tab_data['type'] == 'all'): ?>
                            <td>
                                <span class="tab-badge" style="background: #6c757d;">
                                    <?=htmlspecialchars($item_tab_name)?>
                                </span>
                            </td>
                            <?php endif; ?>
                            
                            <td>
                                <?=htmlspecialchars($item['title'] ?? '')?>
                                <?php if ($replies_count > 0 && $current_tab_data && $current_tab_data['type'] !== 'trash' && $can_comment): ?>
                                <span class="reply-badge" onclick="openReplyModal(<?=$item['id']?>, '<?=htmlspecialchars($item['title'])?>')">
                                    💬 <?=$replies_count?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td><?=htmlspecialchars($item['description'] ?? '')?></td>
                            <td>
                                <span class="author-badge <?=$is_self ? 'self' : ''?>">
                                    <?=htmlspecialchars($item['author'] ?? $item['deleted_by'] ?? 'guest')?>
                                </span>
                            </td>
                            <td>
                                <span class="client-id-badge">
                                    <?=htmlspecialchars($item['author_id'] ?? $item['deleted_by_id'] ?? '')?>
                                </span>
                            </td>
                            <td>
                                <?php if ($current_tab_data && $current_tab_data['type'] !== 'trash'): ?>
                                    <div style="margin-bottom: 4px;">
                                        <span style="color: #28a745;">📅 <?=$item['created'] ?? ''?></span>
                                        <?php if (!empty($item['updated'])): ?>
                                            <span style="color: #2196F3; margin-left: 8px;">
                                                ✏️ <?=$item['updated']?>
                                                <?php if (!empty($item['updated_by'])): ?>
                                                (<?=htmlspecialchars($item['updated_by'])?>)
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="status-container">
                                        <span class="status-item">
                                            ✓ Выполнено
                                            <input type="checkbox" class="checkbox" 
                                                   onchange="toggleCompleted(<?=$item['id']?>, this)"
                                                   <?=isset($item['is_completed']) && $item['is_completed'] ? 'checked' : ''?>
                                                   <?=!$can_change_status ? 'disabled' : ''?>>
                                        </span>
                                        <span class="status-item">
                                            ⭐ Актуально
                                            <input type="checkbox" class="checkbox" 
                                                   onchange="toggleActual(<?=$item['id']?>, this)"
                                                   <?=isset($item['is_actual']) && $item['is_actual'] ? 'checked' : ''?>
                                                   <?=!$can_change_status ? 'disabled' : ''?>>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div>📅 <?=$item['created'] ?? ''?></div>
                                    <div style="color: #dc3545;">🗑️ <?=$item['deleted_at'] ?? ''?></div>
                                    <div>👤 <?=htmlspecialchars($item['deleted_by'] ?? '')?></div>
                                    <div>🖥️ <?=htmlspecialchars($item['deleted_by_id'] ?? '')?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="actions">
                                    <?php if ($current_tab_data && $current_tab_data['type'] !== 'trash'): ?>
                                        <?php if ($can_comment): ?>
                                        <button class="btn reply btn-sm" onclick="openReplyModal(<?=$item['id']?>, '<?=htmlspecialchars($item['title'])?>')">
                                            💬
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($db->checkPermission($current_user, 'edit', $item['author'] ?? null) && $db->checkTabPermission($current_user, $current_tab_data, 'edit')): ?>
                                        <button class="btn edit btn-sm" onclick="editItem(<?=$item['id']?>)">
                                            ✏️
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($db->checkPermission($current_user, 'delete', $item['author'] ?? null) && $db->checkTabPermission($current_user, $current_tab_data, 'delete')): ?>
                                        <button class="btn del btn-sm" onclick="deleteItem(<?=$item['id']?>)">
                                            🗑️
                                        </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($current_user['role'] == 'admin'): ?>
                                        <button class="btn restore btn-sm" onclick="restoreItem(<?=$item['id']?>)">
                                            ♻️
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">➕ Добавить запись</h3>
            <form onsubmit="saveItem(event)">
                <input type="hidden" id="itemId">
                <input type="hidden" id="tabId" value="<?=$current_tab?>">
                <div class="title-selector">
                    <select id="titleSelect" onchange="useSelectedTitle()">
                        <option value="">-- Выберите существующий заголовок --</option>
                        <?php foreach ($titles as $title): ?>
                            <option value="<?=htmlspecialchars($title)?>"><?=htmlspecialchars($title)?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="or-divider">ИЛИ</div>
                <input type="text" id="title" placeholder="Введите новый заголовок">
                <textarea id="description" placeholder="Введите описание" required></textarea>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn del" onclick="closeModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn">💾 Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <div id="tabModal" class="modal">
        <div class="modal-content">
            <h3 id="tabModalTitle">➕ Добавить вкладку</h3>
            <form onsubmit="saveTab(event)">
                <input type="hidden" id="tabId">
                <input type="text" id="tabName" placeholder="Введите название вкладки" required>
                
                <div class="permissions-group">
                    <h4 style="font-size: 14px; margin-bottom: 10px;">Права доступа к вкладке</h4>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="tab_perm_add" checked> ➕ Добавление записей
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="tab_perm_edit" checked> ✏️ Редактирование
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="tab_perm_delete" checked> 🗑️ Удаление
                        </span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn del" onclick="closeTabModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn">💾 Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <div id="tabManageModal" class="modal">
        <div class="modal-content modal-large">
            <h3>📑 Управление вкладками</h3>
            <div style="margin: 20px 0;">
                <h4 style="margin-bottom: 10px;">Порядок отображения</h4>
                <div id="tabOrderList" style="margin-bottom: 20px;">
                    <!-- Список вкладок для перетаскивания -->
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="saveTabOrder()">💾 Сохранить порядок</button>
                    <button type="button" class="btn del" onclick="closeTabManageModal()" style="background: #6c757d;">❌ Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <div id="usersModal" class="modal">
        <div class="modal-content modal-large">
            <h3>👥 Управление пользователями</h3>
            <div id="usersList"></div>
            <div style="display: flex; gap: 10px; justify-content: space-between; margin-top: 20px;">
                <button class="btn" onclick="openUserModal()">➕ Добавить пользователя</button>
                <button class="btn del" onclick="closeUsersModal()" style="background: #6c757d;">❌ Закрыть</button>
            </div>
        </div>
    </div>

    <div id="userModal" class="modal">
        <div class="modal-content modal-large">
            <h3 id="userModalTitle">➕ Добавить пользователя</h3>
            <form onsubmit="saveUser(event)">
                <input type="hidden" id="userId">
                <input type="text" id="username" placeholder="Имя пользователя" required>
                <input type="password" id="password" placeholder="Пароль (оставьте пустым если не меняете)">
                
                <div class="permissions-group">
                    <h4 style="font-size: 14px; margin-bottom: 10px;">Глобальные права</h4>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_view" checked> 👁️ Просмотр записей
                        </span>
                    </div>
                    
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_add"> ➕ Добавление записей
                        </span>
                    </div>
                    
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_edit"> ✏️ Редактирование
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_edit_own"> только свои
                        </span>
                    </div>
                    
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_delete"> 🗑️ Удаление
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_delete_own"> только свои
                        </span>
                    </div>
                    
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_comment" checked> 💬 Комментирование
                        </span>
                    </div>
                    
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_change_status"> ⚡ Изменение статусов
                        </span>
                    </div>
                    
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_delete_comment"> 🗑️ Удаление комментариев
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_delete_comment_own"> только свои
                        </span>
                    </div>
                    
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_create_tab"> ➕ Создание вкладок
                        </span>
                    </div>
                    
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_view_tab_all"> 👁️ Просмотр всех вкладок
                        </span>
                    </div>
                </div>
                
                <h4 style="margin-top: 15px;">📌 Доступные вкладки</h4>
                <div class="tab-checkbox-grid" id="tabPermissionsContainer">
                    <?php foreach ($all_tabs as $tab): ?>
                    <div class="tab-checkbox-item">
                        <input type="checkbox" class="tab-permission-checkbox" 
                               id="tab_perm_<?=$tab['id']?>" 
                               value="<?=$tab['id']?>">
                        <label for="tab_perm_<?=$tab['id']?>">
                            <?php if ($tab['type'] == 'all'): ?>📋<?php endif; ?>
                            <?php if ($tab['type'] == 'trash'): ?>🗑️<?php endif; ?>
                            <?=htmlspecialchars($tab['name'])?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn del" onclick="closeUserModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn">💾 Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <div id="replyModal" class="modal">
        <div class="modal-content">
            <h3 id="replyModalTitle">💬 Ответ на запись</h3>
            <form onsubmit="saveReply(event)">
                <input type="hidden" id="replyItemId">
                <div style="background: #e9ecef; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px;">
                    <strong>📌 <?=htmlspecialchars($current_user['username'])?> (<?=htmlspecialchars($current_client_id)?>), ответ на:</strong><br>
                    <span id="replyOriginalTitle"></span>
                </div>
                <input type="text" id="replyTitle" placeholder="Заголовок ответа" required>
                <textarea id="replyContent" placeholder="Текст ответа" required></textarea>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn del" onclick="closeReplyModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn reply">💬 Ответить</button>
                </div>
            </form>
            
            <div id="repliesList" class="replies-list" style="display: none; margin-top: 20px;">
                <h4 style="font-size: 14px; margin-bottom: 10px;">Существующие ответы</h4>
                <div id="repliesContainer"></div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        const modal = document.getElementById('modal');
        const tabModal = document.getElementById('tabModal');
        const tabManageModal = document.getElementById('tabManageModal');
        const usersModal = document.getElementById('usersModal');
        const userModal = document.getElementById('userModal');
        const replyModal = document.getElementById('replyModal');
        let sortableInstance = null;
        
        function addTab() {
            document.getElementById('tabModalTitle').textContent = '➕ Добавить вкладку';
            document.getElementById('tabId').value = '';
            document.getElementById('tabName').value = '';
            document.getElementById('tab_perm_add').checked = true;
            document.getElementById('tab_perm_edit').checked = true;
            document.getElementById('tab_perm_delete').checked = true;
            tabModal.style.display = 'block';
        }
        
        function editTab(id, name) {
            document.getElementById('tabModalTitle').textContent = '✏️ Редактировать вкладку';
            document.getElementById('tabId').value = id;
            document.getElementById('tabName').value = name;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getTabs'
            })
            .then(r => r.json())
            .then(tabs => {
                let tab = tabs.find(t => t.id == id);
                if (tab && tab.permissions) {
                    document.getElementById('tab_perm_add').checked = tab.permissions.add || false;
                    document.getElementById('tab_perm_edit').checked = tab.permissions.edit || false;
                    document.getElementById('tab_perm_delete').checked = tab.permissions.delete || false;
                }
                tabModal.style.display = 'block';
            });
        }
        
        function closeTabModal() {
            tabModal.style.display = 'none';
        }
        
        function saveTab(e) {
            e.preventDefault();
            let id = document.getElementById('tabId').value;
            let name = document.getElementById('tabName').value;
            
            let permissions = {
                add: document.getElementById('tab_perm_add').checked,
                edit: document.getElementById('tab_perm_edit').checked,
                delete: document.getElementById('tab_perm_delete').checked
            };
            
            let data = id ? 'action=updateTab&id=' + id : 'action=addTab';
            data += '&name=' + encodeURIComponent(name) + 
                    '&permissions=' + encodeURIComponent(JSON.stringify(permissions));
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data
            })
            .then(r => r.json())
            .then(() => location.reload());
        }
        
        function manageTabs() {
            tabManageModal.style.display = 'block';
            loadTabOrder();
        }
        
        function closeTabManageModal() {
            tabManageModal.style.display = 'none';
        }
        
        function loadTabOrder() {
            let tabs = <?=json_encode(array_filter($all_tabs, function($tab) {
                return $tab['type'] !== 'trash' && $tab['type'] !== 'all';
            }))?>;
            
            let html = '<div id="tabOrderContainer">';
            tabs.sort((a, b) => (a.order || 0) - (b.order || 0)).forEach(tab => {
                html += '<div class="tab-order-item" data-id="' + tab.id + '">';
                html += '<span class="tab-order-handle">☰</span>';
                html += '<span style="flex: 1;">' + escapeHtml(tab.name) + '</span>';
                html += '<span style="color: #6c757d; font-size: 11px;">порядок: ' + (tab.order || 0) + '</span>';
                html += '</div>';
            });
            html += '</div>';
            
            document.getElementById('tabOrderList').innerHTML = html;
            
            if (sortableInstance) {
                sortableInstance.destroy();
            }
            
            let container = document.getElementById('tabOrderContainer');
            if (container) {
                sortableInstance = new Sortable(container, {
                    handle: '.tab-order-handle',
                    animation: 150,
                    ghostClass: 'bg-light'
                });
            }
        }
        
        function saveTabOrder() {
            let order = {};
            let items = document.querySelectorAll('#tabOrderContainer .tab-order-item');
            items.forEach((item, index) => {
                let id = parseInt(item.dataset.id);
                order[id] = index * 10;
            });
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=reorderTabs&order=' + encodeURIComponent(JSON.stringify(order))
            })
            .then(r => r.json())
            .then(() => {
                alert('Порядок вкладок сохранен');
                closeTabManageModal();
                location.reload();
            });
        }
        
        function deleteTab(event, id, name) {
            event.preventDefault();
            event.stopPropagation();
            
            if (confirm('🗑️ Удалить вкладку "' + name + '"?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteTab&id=' + id
                })
                .then(r => r.json())
                .then(() => location.reload());
            }
        }
        
        function openModal() {
            modal.style.display = 'block';
            document.getElementById('modalTitle').textContent = '➕ Добавить запись';
            document.getElementById('itemId').value = '';
            document.getElementById('title').value = '';
            document.getElementById('titleSelect').value = '';
            document.getElementById('description').value = '';
        }
        
        function closeModal() {
            modal.style.display = 'none';
        }
        
        function useSelectedTitle() {
            let select = document.getElementById('titleSelect');
            let title = select.value;
            if (title) {
                document.getElementById('title').value = title;
            }
        }
        
        function editItem(id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get&id=' + id
            })
            .then(r => r.json())
            .then(d => {
                document.getElementById('modalTitle').textContent = '✏️ Редактировать запись';
                document.getElementById('itemId').value = id;
                document.getElementById('title').value = d.title || '';
                document.getElementById('titleSelect').value = d.title || '';
                document.getElementById('description').value = d.description || '';
                modal.style.display = 'block';
            });
        }
        
        function saveItem(e) {
            e.preventDefault();
            let id = document.getElementById('itemId').value;
            let title = document.getElementById('title').value;
            let desc = document.getElementById('description').value;
            let tab_id = document.getElementById('tabId').value;
            
            if (!title) {
                alert('Пожалуйста, введите заголовок');
                return;
            }
            
            let act = id ? 'update' : 'add';
            let data = 'action=' + act + '&title=' + encodeURIComponent(title) + 
                      '&description=' + encodeURIComponent(desc) + '&tab_id=' + tab_id;
            if (id) data += '&id=' + id;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data
            })
            .then(r => r.json())
            .then(() => location.reload());
        }
        
        function deleteItem(id) {
            if (confirm('🗑️ Удалить запись?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=delete&id=' + id
                })
                .then(() => location.reload());
            }
        }
        
        function restoreItem(id) {
            if (confirm('♻️ Восстановить запись?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=restore&id=' + id
                })
                .then(() => location.reload());
            }
        }
        
        function toggleCompleted(id, checkbox) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=toggleCompleted&id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) checkbox.checked = !checkbox.checked;
            });
        }
        
        function toggleActual(id, checkbox) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=toggleActual&id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) checkbox.checked = !checkbox.checked;
            });
        }
        
        function showUsers() {
            usersModal.style.display = 'block';
            loadUsers();
        }
        
        function closeUsersModal() {
            usersModal.style.display = 'none';
        }
        
        function loadUsers() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getUsers'
            })
            .then(r => r.json())
            .then(users => {
                let html = '<table class="table" style="margin-top: 15px;">';
                html += '<thead><tr><th>ID</th><th>Имя</th><th>Роль</th><th>Client ID</th><th>Права</th><th>Доступные вкладки</th><th>Действия</th></tr></thead><tbody>';
                
                users.forEach(user => {
                    html += '<tr>';
                    html += '<td>' + user.id + '</td>';
                    html += '<td><strong>' + escapeHtml(user.username) + '</strong></td>';
                    html += '<td><span class="role-badge">' + user.role + '</span></td>';
                    html += '<td><span class="client-id-badge">' + (user.client_id || '') + '</span></td>';
                    html += '<td style="font-size: 11px;">';
                    if (user.role == 'admin') {
                        html += 'Полный доступ';
                    } else {
                        let perms = user.permissions || {};
                        let items = [];
                        if (perms.view) items.push('👁️ просмотр');
                        if (perms.add) items.push('➕ добавление');
                        if (perms.edit) items.push('✏️ ред' + (perms.edit_own ? ' (свои)' : ''));
                        if (perms.delete) items.push('🗑️ удал' + (perms.delete_own ? ' (свои)' : ''));
                        if (perms.comment) items.push('💬 коммент');
                        if (perms.change_status) items.push('⚡ статус');
                        if (perms.delete_comment) items.push('🗑️ уд.комм' + (perms.delete_comment_own ? ' (свои)' : ''));
                        if (perms.create_tab) items.push('➕ созд.вкладок');
                        if (perms.view_tab_all) items.push('👁️ все вкладки');
                        html += items.join('<br>');
                    }
                    html += '</td>';
                    html += '<td style="font-size: 11px;">';
                    if (user.role == 'admin') {
                        html += 'Все вкладки';
                    } else {
                        let tabPerms = user.tab_permissions || [];
                        let tabNames = [];
                        <?php foreach ($all_tabs as $tab): ?>
                        if (tabPerms.includes(<?=$tab['id']?>)) {
                            tabNames.push('<?=htmlspecialchars($tab['name'])?>');
                        }
                        <?php endforeach; ?>
                        html += tabNames.join('<br>') || 'Нет доступа';
                    }
                    html += '</td>';
                    html += '<td class="actions">';
                    html += '<button class="btn edit btn-sm" onclick="editUser(' + user.id + ')">✏️</button>';
                    if (user.username !== 'admin') {
                        html += '<button class="btn del btn-sm" onclick="deleteUser(' + user.id + ')">🗑️</button>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                document.getElementById('usersList').innerHTML = html;
            });
        }
        
        function openUserModal() {
            userModal.style.display = 'block';
            document.getElementById('userModalTitle').textContent = '➕ Добавить пользователя';
            document.getElementById('userId').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            
            document.getElementById('perm_view').checked = true;
            document.getElementById('perm_add').checked = false;
            document.getElementById('perm_edit').checked = false;
            document.getElementById('perm_edit_own').checked = false;
            document.getElementById('perm_delete').checked = false;
            document.getElementById('perm_delete_own').checked = false;
            document.getElementById('perm_comment').checked = true;
            document.getElementById('perm_change_status').checked = false;
            document.getElementById('perm_delete_comment').checked = false;
            document.getElementById('perm_delete_comment_own').checked = false;
            document.getElementById('perm_create_tab').checked = false;
            document.getElementById('perm_view_tab_all').checked = false;
            
            document.querySelectorAll('.tab-permission-checkbox').forEach(cb => {
                cb.checked = false;
            });
        }
        
        function closeUserModal() {
            userModal.style.display = 'none';
        }
        
        function editUser(id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getUsers'
            })
            .then(r => r.json())
            .then(users => {
                let user = users.find(u => u.id == id);
                if (user) {
                    document.getElementById('userModalTitle').textContent = '✏️ Редактировать пользователя';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('password').value = '';
                    
                    let perms = user.permissions || {};
                    document.getElementById('perm_view').checked = perms.view ?? true;
                    document.getElementById('perm_add').checked = perms.add ?? false;
                    document.getElementById('perm_edit').checked = perms.edit ?? false;
                    document.getElementById('perm_edit_own').checked = perms.edit_own ?? false;
                    document.getElementById('perm_delete').checked = perms.delete ?? false;
                    document.getElementById('perm_delete_own').checked = perms.delete_own ?? false;
                    document.getElementById('perm_comment').checked = perms.comment ?? false;
                    document.getElementById('perm_change_status').checked = perms.change_status ?? false;
                    document.getElementById('perm_delete_comment').checked = perms.delete_comment ?? false;
                    document.getElementById('perm_delete_comment_own').checked = perms.delete_comment_own ?? false;
                    document.getElementById('perm_create_tab').checked = perms.create_tab ?? false;
                    document.getElementById('perm_view_tab_all').checked = perms.view_tab_all ?? false;
                    
                    let tabPerms = user.tab_permissions || [];
                    document.querySelectorAll('.tab-permission-checkbox').forEach(cb => {
                        cb.checked = tabPerms.includes(parseInt(cb.value));
                    });
                    
                    userModal.style.display = 'block';
                }
            });
        }
        
        function saveUser(e) {
            e.preventDefault();
            let id = document.getElementById('userId').value;
            let username = document.getElementById('username').value;
            let password = document.getElementById('password').value;
            
            let permissions = {
                view: document.getElementById('perm_view').checked,
                add: document.getElementById('perm_add').checked,
                edit: document.getElementById('perm_edit').checked,
                edit_own: document.getElementById('perm_edit_own').checked,
                delete: document.getElementById('perm_delete').checked,
                delete_own: document.getElementById('perm_delete_own').checked,
                comment: document.getElementById('perm_comment').checked,
                change_status: document.getElementById('perm_change_status').checked,
                delete_comment: document.getElementById('perm_delete_comment').checked,
                delete_comment_own: document.getElementById('perm_delete_comment_own').checked,
                create_tab: document.getElementById('perm_create_tab').checked,
                view_tab_all: document.getElementById('perm_view_tab_all').checked
            };
            
            let tabPermissions = [];
            document.querySelectorAll('.tab-permission-checkbox:checked').forEach(cb => {
                tabPermissions.push(parseInt(cb.value));
            });
            
            let data = 'action=' + (id ? 'updateUser' : 'addUser') +
                      '&username=' + encodeURIComponent(username) +
                      '&password=' + encodeURIComponent(password) +
                      '&permissions=' + encodeURIComponent(JSON.stringify(permissions)) +
                      '&tab_permissions=' + encodeURIComponent(JSON.stringify(tabPermissions));
            
            if (id) data += '&id=' + id;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data
            })
            .then(r => r.json())
            .then(() => {
                closeUserModal();
                loadUsers();
            });
        }
        
        function deleteUser(id) {
            if (confirm('🗑️ Удалить пользователя?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteUser&id=' + id
                })
                .then(r => r.json())
                .then(() => loadUsers());
            }
        }
        
        function openReplyModal(item_id, item_title) {
            replyModal.style.display = 'block';
            document.getElementById('replyItemId').value = item_id;
            document.getElementById('replyOriginalTitle').textContent = item_title;
            document.getElementById('replyTitle').value = '';
            document.getElementById('replyContent').value = '';
            loadReplies(item_id);
        }
        
        function closeReplyModal() {
            replyModal.style.display = 'none';
        }
        
        function loadReplies(item_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getReplies&item_id=' + item_id
            })
            .then(r => r.json())
            .then(replies => {
                let container = document.getElementById('repliesContainer');
                let listDiv = document.getElementById('repliesList');
                
                if (replies.length > 0) {
                    listDiv.style.display = 'block';
                    let html = '';
                    
                    replies.forEach(reply => {
                        html += '<div class="reply-item">';
                        html += '<div class="reply-header">';
                        html += '<span class="reply-title">' + escapeHtml(reply.title) + '</span>';
                        html += '<span class="reply-meta">' + reply.created_at + ' от ' + escapeHtml(reply.author) + ' (' + (reply.author_id || '') + ')</span>';
                        html += '</div>';
                        html += '<div class="reply-content">' + escapeHtml(reply.content) + '</div>';
                        
                        let canDelete = false;
                        if ('<?=$current_user['role']?>' === 'admin') {
                            canDelete = true;
                        } else if (reply.author === '<?=$current_user['username']?>' && <?=($db->checkPermission($current_user, 'delete_comment_own', null) ? 'true' : 'false')?>) {
                            canDelete = true;
                        } else if (<?=($db->checkPermission($current_user, 'delete_comment', null) ? 'true' : 'false')?>) {
                            canDelete = true;
                        }
                        
                        if (canDelete) {
                            html += '<div style="text-align: right; margin-top: 10px;">';
                            html += '<button class="btn del btn-sm" onclick="deleteReply(' + reply.id + ')">🗑️</button>';
                            html += '</div>';
                        }
                        
                        html += '</div>';
                    });
                    
                    container.innerHTML = html;
                } else {
                    listDiv.style.display = 'none';
                }
            });
        }
        
        function saveReply(e) {
            e.preventDefault();
            let item_id = document.getElementById('replyItemId').value;
            let title = document.getElementById('replyTitle').value;
            let content = document.getElementById('replyContent').value;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=addReply&item_id=' + item_id + 
                      '&title=' + encodeURIComponent(title) + 
                      '&content=' + encodeURIComponent(content)
            })
            .then(r => r.json())
            .then(() => location.reload());
        }
        
        function deleteReply(id) {
            if (confirm('🗑️ Удалить ответ?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteReply&id=' + id
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        location.reload();
                    } else {
                        alert('У вас нет прав на удаление этого комментария');
                    }
                });
            }
        }
        
        function exportExcel() {
            let data = [];
            let isAllTab = <?=$current_tab_data && $current_tab_data['type'] == 'all' ? 'true' : 'false'?>;
            
            if (isAllTab) {
                data.push(['№', 'Вкладка', 'Заголовок', 'Описание', 'Автор', 'ID автора', 'Выполнено', 'Актуально', 'Дата создания', 'Дата изменения', 'Кем изменено']);
            } else {
                data.push(['№', 'Заголовок', 'Описание', 'Автор', 'ID автора', 'Выполнено', 'Актуально', 'Дата создания', 'Дата изменения', 'Кем изменено']);
            }
            
            let rows = document.querySelectorAll('.table tbody tr');
            rows.forEach(row => {
                if (!row.querySelector('.empty-state')) {
                    let cols = row.querySelectorAll('td');
                    if (cols.length > 1) {
                        let num = cols[0].innerText.trim();
                        let colIndex = 1;
                        
                        if (isAllTab) {
                            let tabName = cols[colIndex]?.innerText.trim() || '';
                            colIndex++;
                            let title = cols[colIndex]?.innerText.split('💬')[0].trim() || '';
                            colIndex++;
                            let desc = cols[colIndex]?.innerText.trim() || '';
                            colIndex++;
                            let author = cols[colIndex]?.innerText.trim() || '';
                            colIndex++;
                            let authorId = cols[colIndex]?.innerText.trim() || '';
                            colIndex++;
                            let statusCells = cols[colIndex]?.querySelectorAll('.status-item') || [];
                            let completed = statusCells[0]?.querySelector('input')?.checked ? 'Да' : 'Нет';
                            let actual = statusCells[1]?.querySelector('input')?.checked ? 'Да' : 'Нет';
                            let dates = cols[colIndex]?.querySelector('div:first-child')?.innerHTML || '';
                            let created = dates.split('<span')[0]?.replace('📅', '').trim() || '';
                            let updatedInfo = dates.match(/✏️ (.*?)<\/span>/);
                            let updated = updatedInfo ? updatedInfo[1].split('(')[0].trim() : '';
                            let updatedBy = updatedInfo && updatedInfo[1].includes('(') ? updatedInfo[1].match(/\((.*?)\)/)?.[1] || '' : '';
                            data.push([num, tabName, title, desc, author, authorId, completed, actual, created, updated, updatedBy]);
                        } else {
                            let title = cols[1]?.innerText.split('💬')[0].trim() || '';
                            let desc = cols[2]?.innerText.trim() || '';
                            let author = cols[3]?.innerText.trim() || '';
                            let authorId = cols[4]?.innerText.trim() || '';
                            let statusCells = cols[5]?.querySelectorAll('.status-item') || [];
                            let completed = statusCells[0]?.querySelector('input')?.checked ? 'Да' : 'Нет';
                            let actual = statusCells[1]?.querySelector('input')?.checked ? 'Да' : 'Нет';
                            let dates = cols[5]?.querySelector('div:first-child')?.innerHTML || '';
                            let created = dates.split('<span')[0]?.replace('📅', '').trim() || '';
                            let updatedInfo = dates.match(/✏️ (.*?)<\/span>/);
                            let updated = updatedInfo ? updatedInfo[1].split('(')[0].trim() : '';
                            let updatedBy = updatedInfo && updatedInfo[1].includes('(') ? updatedInfo[1].match(/\((.*?)\)/)?.[1] || '' : '';
                            data.push([num, title, desc, author, authorId, completed, actual, created, updated, updatedBy]);
                        }
                    }
                }
            });
            
            let wb = XLSX.utils.book_new();
            let ws = XLSX.utils.aoa_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, 'Контейнеры');
            XLSX.writeFile(wb, 'containers_export.xlsx');
        }
        
        function exportHTML(tab_id) {
            window.open('?export_html=1&tab=' + tab_id, '_blank');
        }
        
        function escapeHtml(text) {
            let div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        window.onclick = (e) => { 
            if (e.target == modal) modal.style.display = 'none';
            if (e.target == tabModal) tabModal.style.display = 'none';
            if (e.target == tabManageModal) tabManageModal.style.display = 'none';
            if (e.target == usersModal) usersModal.style.display = 'none';
            if (e.target == userModal) userModal.style.display = 'none';
            if (e.target == replyModal) replyModal.style.display = 'none';
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
                closeTabModal();
                closeTabManageModal();
                closeUsersModal();
                closeUserModal();
                closeReplyModal();
            }
        });
    </script>
</body>
</html>