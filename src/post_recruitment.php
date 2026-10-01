<?php
require 'db_connect.php';
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = uniqid('TEAM');
    $team_name = $_POST['team_name'];
    $skills = explode(',', $_POST['skills']);
    $stmt = $pdo->prepare("INSERT INTO Team (TID, Team_Name, Leader) VALUES (?, ?, ?)");
    $stmt->execute([$tid, $team_name, $_SESSION['user_id']]);
    $stmt = $pdo->prepare("INSERT INTO TeamRecruitment (Team) VALUES (?)");
    $stmt->execute([$tid]);
    foreach ($skills as $skill) {
        $skill = trim($skill);
        if ($skill) {
            $stmt = $pdo->prepare("INSERT INTO TeamRequireSkill (Team, Skill) VALUES (?, ?)");
            $stmt->execute([$tid, $skill]);
        }
    }
    header("Location: student.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// 取得目前所有隊伍（我有參加且未離開）
$stmt = $pdo->prepare("SELECT t.TID, t.Team_Name, tmh.Join_Date
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE tmh.Member = ? AND tmh.Leave_Date IS NULL");
$stmt->execute([$user_id]);
$my_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得目前所有隊伍（只要隊名和TID，for sidebar）
$sidebar_teams = $my_teams;

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>發佈隊伍徵求</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">學生: <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link" href="update_profile.php">個人檔案</a></li>
                        <li class="nav-item">
                            <a class="nav-link<?php if ($page_mode === 'list') echo ' active'; ?>" href="my_team.php">我的隊伍</a>
                            <ul class="team-list" id="teamList">
                                <?php foreach ($sidebar_teams as $team): ?>
                                    <li>
                                        <a class="nav-link<?php if ($TID == $team['TID']) echo ' active'; ?>" href="my_team.php?TID=<?php echo $team['TID']; ?>">
                                            <?php echo htmlspecialchars($team['Team_Name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="create_team.php">創建隊伍</a></li>
                        <li class="nav-item"><a class="nav-link" href="apply_team.php">申請入隊</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_invitations.php">管理訊息</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">組隊紀錄</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_ratings.php">查看評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>發佈隊伍徵求</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label for="team_name" class="form-label">隊伍名稱</label>
                        <input type="text" class="form-control" id="team_name" name="team_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="skills" class="form-label">所需技能（以逗號分隔）</label>
                        <input type="text" class="form-control" id="skills" name="skills" placeholder="例如: Python, SQL, C++">
                    </div>
                    <button type="submit" class="btn btn-success">發佈</button>
                    <a href="student.php" class="btn btn-secondary">返回</a>
                </form>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>