<?php 

require_once 'vendor/autoload.php';
use Ramsey\Uuid\Uuid;

session_start();
$dbh;
$stmt;
$init = false;
$error_message = null;

if (isset($_POST['init'])) {
  $_SESSION['username'] = $_POST['username'];
  $_SESSION['password'] = $_POST['password'];
  $_SESSION['db_name'] = $_POST['db_name'];
  $_SESSION['table_name'] = $_POST['table_name'];
} elseif (isset($_POST['reset'])) {
  session_destroy();
  header('Location: ' . $_SERVER['REQUEST_URI']);
  exit;
}

if (!empty($_SESSION)) {
  try {
    $dbh = new PDO("mysql:host=localhost;dbname=" . $_SESSION['db_name'], $_SESSION['username'], $_SESSION['password']);
    $init = true;
  } catch (PDOException $e) {
    $error_message = $e;
  }

  if (isset($_POST['inject'])) {
    $stmt = $dbh->prepare("SELECT {$_POST['key']} FROM {$_SESSION['table_name']}");
    $stmt->execute();
    $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($keys as $id) {
      $uuid = Uuid::uuid4()->toString();
      // echo "UPDATE {$_SESSION['table_name']}
      // SET {$_POST['field']} = '{$uuid}'
      // WHERE {$_POST['key']} = {$id} <br>";
      $stmt = $dbh->prepare(
        "UPDATE {$_SESSION['table_name']}
          SET {$_POST['field']} = '{$uuid}'
          WHERE {$_POST['key']} = {$id}"
      );
      $stmt->execute();
    }
  }
}

function checkSession($key) {
  if (!empty($_SESSION) ) {
    return $_SESSION[$key];
  } else {
    return "";
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHP UUID Injector</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <section class="info">
    <h1>Table Info</h1>
    <br>
    <hr>
    <br>
    <?php if ($init) : ?>
      <div class="container">
        <table>
          <thead>
            <?php 
              $stmt = $dbh->prepare(
                "SHOW COLUMNS FROM `{$_SESSION['table_name']}`"
              );
              $stmt->execute();
              $fields = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            ?>
            <tr>
              <?php foreach ($fields as $field) : ?>
                <th><?= $field ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php 
              $stmt = $dbh->prepare("SELECT * FROM {$_SESSION['table_name']}");
              $stmt->execute();
              $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php foreach ($data as $row) : ?>
              <tr>
                <?php foreach ($row as $col) : ?>
                  <td><?= $col ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <form action="" method="post" class="action">
        <span class="title">UUID Inject</span>
        <div class="input-inline">
          <label for="key">Old Primary Key :</label>
          <input type="text" name="key" id="key" required> <br>
        </div>
        <div class="input-inline">
          <label for="field">Field to Inject :</label>
          <input type="text" name="field" id="field" required>
        </div>
        <button type="submit" name="inject">Inject</button>
      </form>
    <?php elseif ($error_message != null) : ?>
      <pre>
        <?= $error_message ?>
      </pre>
    <?php else : ?>
      <p>Database aren't detected!</p>
    <?php endif; ?>
  </section>
  <form method="post" class="init">
    <span class="title">Database Init</span>
    <div class="input-inline">
      <label for="username">Username :</label>
      <input type="text" name="username" id="username" value="<?= checkSession('username') != '' ? checkSession('username') : 'root' ?>" required>
    </div>
    <div class="input-inline">
      <label for="password">Password :</label>
      <input type="text" name="password" id="password" value="<?= checkSession('password') ?>">
    </div>
    <br>
    <div class="input-inline">
      <label for="db_name">DB Name :</label>
      <input type="text" name="db_name" id="db_name" value="<?= checkSession('db_name') ?>" required>
    </div>
    <div class="input-inline">
      <label for="table_name">Table Name :</label>
      <input type="text" name="table_name" id="table_name" value="<?= checkSession('table_name') ?>" required>
    </div>
    <button type="submit" name="init">Connect</button>
    <button type="submit" name="reset">Reset</button>
  </form>
  <pre style="font-family: monospace; outline: 1px solid gray; padding: 8px;">
    <?php print_r($_SESSION) ?>
  </pre>
</body>
</html>