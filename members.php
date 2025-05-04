<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'header.php';

$members = $pdo->query("SELECT * FROM users WHERE graduate = 0")->fetchAll(PDO::FETCH_ASSOC);

function calculateGeneration($joinYearSem) {
  if (empty($joinYearSem)) return "-";
  if (!preg_match('/(\d{4})/', $joinYearSem, $m)) return "-";
  $year = intval($m[1]);
  if ($year < 1978) return "-";
  return ($year - 2020 + 39) . "기";
}

function maskName($name) {
  $len = mb_strlen($name, 'UTF-8');
  return $len >= 2 ? mb_substr($name, 0, 1, 'UTF-8') . '*' . mb_substr($name, 2, null, 'UTF-8') : $name;
}


function formatStudentId($studentId) {
  if (empty($studentId)) return "-";
  if (preg_match('/^(\d{2})(\d{2})/', $studentId, $m)) {
    return $m[2] . "학번";
  }
  return "-";
}

$executive_order = ['지휘자', '회장', '부회장', '총무'];
$executives = array_filter($members, fn($m) => in_array($m['position'], $executive_order));
usort($executives, fn($a, $b) =>
  array_search($a['position'], $executive_order) - array_search($b['position'], $executive_order)
);

$part_members = [];
foreach ($members as $m) {
  // 임원이어도 파트별 목록에 포함시킴 (중복 표시)
  $part = $m['instrument'] ?: '기타';
  $part_members[$part][] = $m;
}

// Define instrument order
$instrument_order = [
  '바이올린', '비올라', '첼로', '콘트라베이스',
  '피콜로', '플룻', '클라리넷', '오보에', '바순', '색소폰',
  '호른', '트럼펫', '트롬본', '튜바',
  '팀파니', '스네어드럼', '베이스드럼', '심벌', '마림바', '비브라폰',
  '피아노', '비연주단원', '기타'
];

// 파트 내에서 정렬: 파트장 먼저, 그 다음 기수 순
foreach ($part_members as &$group) {
  usort($group, function ($a, $b) {
    // 파트장이 최우선
    if ($a['is_leader'] && !$b['is_leader']) return -1;
    if (!$a['is_leader'] && $b['is_leader']) return 1;
    
    // 다음은 임원진
    $executive_order = ['지휘자', '회장', '부회장', '총무'];
    $a_is_exec = in_array($a['position'], $executive_order);
    $b_is_exec = in_array($b['position'], $executive_order);
    
    if ($a_is_exec && !$b_is_exec) return -1;
    if (!$a_is_exec && $b_is_exec) return 1;
    
    // 마지막으로 기수 순
    $gen_a = (int)calculateGeneration($a['join_year_sem']);
    $gen_b = (int)calculateGeneration($b['join_year_sem']);
    return $gen_a - $gen_b;
  });
}
unset($group);

// Mark as leader if only one member in part
foreach ($part_members as $part => &$group) {
  if (count($group) === 1 && !$group[0]['is_leader']) {
    $group[0]['is_solo_member'] = true;
  }
}
unset($group);

// Sort parts by instrument order
$sorted_part_members = [];
foreach ($instrument_order as $instrument) {
  if (isset($part_members[$instrument])) {
    $sorted_part_members[$instrument] = $part_members[$instrument];
  }
}

// Add any instruments that weren't in our predefined order
foreach ($part_members as $part => $members) {
  if (!in_array($part, $instrument_order)) {
    $sorted_part_members[$part] = $members;
  }
}

$part_members = $sorted_part_members;
?>

<style>
.member-section {
  padding: 120px 1.5rem 4rem;
  max-width: var(--max-width);
  margin: 0 auto;
}

.section-divider {
  margin: 3rem 0;
  height: 2px;
  background: linear-gradient(to right, rgba(0,0,0,0.1), rgba(0,0,0,0.05), rgba(0,0,0,0));
  border: none;
}

.section-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 1.8rem;
  color: #333;
  padding-bottom: 0.5rem;
  border-bottom: 3px solid var(--blue);
  display: inline-block;
}

.section-subtitle {
  font-size: 1.4rem;
  font-weight: 600;
  margin: 2.5rem 0 1.2rem;
  color: #444;
  display: flex;
  align-items: center;
}

.section-subtitle::after {
  content: "";
  flex: 1;
  height: 1px;
  background: #ddd;
  margin-left: 1rem;
}

/* 악기 그룹별 구분선 스타일 */
.instrument-group-divider {
  margin: 3rem 0;
  height: 1px;
  background: linear-gradient(to right, rgba(0,0,0,0.08), rgba(0,0,0,0.02), rgba(0,0,0,0));
  border: none;
}

.executives-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.8rem;
  margin-bottom: 2rem;
}

.executive-card {
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 10px 20px rgba(0,0,0,0.08);
  transition: transform 0.2s, box-shadow 0.2s;
}

.executive-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0,0,0,0.12);
}

.exec-img-container {
  height: 230px;
  overflow: hidden;
  position: relative;
  background: #f5f5f5;
}

.executive-card img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.executive-card:hover img {
  transform: scale(1.05);
}

.exec-info {
  padding: 1.2rem;
  text-align: center;
  background: #fff;
}

.exec-name {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
  color: #333;
}

.exec-position {
  font-size: 1rem;
  font-weight: 600;
  color: var(--blue);
  margin-bottom: 0.6rem;
}

.exec-details {
  font-size: 0.9rem;
  color: #666;
  line-height: 1.5;
}

/* 단원 테이블 스타일 */
.members-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin-bottom: 2.5rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border-radius: 12px;
  overflow: hidden;
}

.members-table thead th {
  background: var(--blue);
  color: white;
  font-weight: 600;
  padding: 1rem;
  text-align: left;
  font-size: 0.95rem;
  position: relative;
}

.members-table thead th:not(:last-child)::after {
  content: "";
  position: absolute;
  right: 0;
  top: 20%;
  height: 60%;
  width: 1px;
  background-color: rgba(255,255,255,0.3);
}

.members-table tbody td {
  padding: 0.9rem 1rem;
  border-bottom: 1px solid #eee;
  font-size: 0.95rem;
  vertical-align: middle;
  position: relative;
}

.members-table tbody tr:last-child td {
  border-bottom: none;
}

.members-table tbody tr:hover {
  background-color: #f9f9f9;
}

.members-table td:not(:last-child)::after {
  content: "";
  position: absolute;
  right: 0;
  top: 20%;
  height: 60%;
  width: 1px;
  background-color: #eee;
}

.profile-cell {
  display: flex;
  align-items: center;
  gap: 1.5rem; /* 증가된 간격 */
}

.profile-img {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #eee;
}

.leader-badge {
  display: inline-block;
  background-color: var(--blue);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-right: 0.5rem;
}

.solo-badge {
  display: inline-block;
  background-color: #00b894;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-right: 0.5rem;
}

.executive-badge {
  display: inline-block;
  background-color: #6c5ce7;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-right: 0.5rem;
}

/* 반응형 스타일 */
@media (max-width: 768px) {
  .executives-grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
  
  .members-table thead {
    display: none;
  }
  
  .members-table, 
  .members-table tbody, 
  .members-table tr, 
  .members-table td {
    display: block;
    width: 100%;
  }
  
  .members-table tr {
    margin-bottom: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
  }
  
  .members-table td {
    text-align: right;
    padding: 0.7rem 1rem;
    position: relative;
    padding-left: 40%;
  }
  
  .members-table td:before {
    content: attr(data-label);
    position: absolute;
    left: 1rem;
    width: 35%;
    white-space: nowrap;
    font-weight: 600;
    text-align: left;
  }
  
  .members-table td:not(:last-child)::after {
    display: none;
  }
  
  .profile-cell {
    justify-content: flex-end;
  }
}
</style>

<main class="member-section">
  <!-- 임원진 섹션 -->
  <h2 class="section-title"><br>임원진</h2>
  
  <div class="executives-grid">
    <?php foreach ($executives as $exec): ?>
      <div class="executive-card">
        <div class="exec-img-container">
          <img src="./img/profile/<?= $exec['profile_img'] ?? 'logo.png' ?>" alt="프로필">
        </div>
        <div class="exec-info">
          <div class="exec-position"><?= htmlspecialchars($exec['position']) ?></div>
          <div class="exec-name"><?= htmlspecialchars($exec['name']) ?></div>
          <div class="exec-details">
            <?= calculateGeneration($exec['join_year_sem']) ?><br>
            <?= htmlspecialchars($exec['dept']) ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  
  <hr class="section-divider">
  
  <!-- 파트별 단원 섹션 -->
  <h2 class="section-title">전체 단원</h2>
  
  <?php 
  // Group instruments by family
  $currentGroup = "";
  $groupMapping = [
    '바이올린' => 'strings', '비올라' => 'strings', '첼로' => 'strings', '콘트라베이스' => 'strings',
    '피콜로' => 'woodwinds', '플룻' => 'woodwinds', '클라리넷' => 'woodwinds', '오보에' => 'woodwinds', '바순' => 'woodwinds', '색소폰' => 'woodwinds',
    '호른' => 'brass', '트럼펫' => 'brass', '트롬본' => 'brass', '튜바' => 'brass',
    '팀파니' => 'percussion', '스네어드럼' => 'percussion', '베이스드럼' => 'percussion', '심벌' => 'percussion', '마림바' => 'percussion', '비브라폰' => 'percussion',
    '피아노' => 'others', '비연주단원' => 'others', '기타' => 'others'
  ];
  
  foreach ($part_members as $part => $members): 
    $currentInstrumentGroup = $groupMapping[$part] ?? 'others';
    
    // 악기군이 바뀔 때 구분선 추가
    if ($currentGroup && $currentGroup !== $currentInstrumentGroup):
  ?>
    <hr class="instrument-group-divider">
  <?php 
    endif;
    $currentGroup = $currentInstrumentGroup;
  ?>
    
    <h3 class="section-subtitle"><?= htmlspecialchars($part) ?></h3>
    
    <table class="members-table">
      <thead>
        <tr>
          <th width="20%">이름</th>
          <th width="20%">기수</th>
          <th width="20%">학번</th>
          <th width="20%">학과</th>
          <th width="20%">직책</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $member): ?>
          <tr>
            <td data-label="이름" class="profile-cell">
              <img src="./img/profile/<?= $member['profile_img'] ?? 'logo.png' ?>" alt="프로필" class="profile-img">
              <div>
                <?php if ($member['is_leader']): ?>
                  <span class="leader-badge">파트장</span>
                <?php elseif (isset($member['is_solo_member']) && $member['is_solo_member']): ?>
                  <span class="solo-badge">파트장</span>
                <?php endif; ?>
                <?= maskName($member['name']) ?>
              </div>
            </td>
            <td data-label="기수"><?= calculateGeneration($member['join_year_sem']) ?></td>
            <td data-label="학번"><?= formatStudentId($member['student_id']) ?></td>
            <td data-label="학과"><?= htmlspecialchars($member['dept']) ?></td>
            <td data-label="직책">
              <?php if (in_array($member['position'], $executive_order)): ?>
                <span class="executive-badge"><?= htmlspecialchars($member['position']) ?></span>
              <?php else: ?>
                <?= htmlspecialchars($member['position']) ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>
</main>

<?php require_once 'footer.php'; ?>