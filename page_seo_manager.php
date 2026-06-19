<?php
include_once('../_common.php');
include_once('./_auth_check.php');

if (!$is_admin) { header('Location: /bbs/login.php?url='.urlencode($_SERVER['REQUEST_URI'])); exit; }

global $connect_db;
$db = $connect_db;

$table = 'dm_page_seo';

// ── 테이블 자동 생성 ─────────────────────────────────────
mysqli_query($db, "CREATE TABLE IF NOT EXISTS `{$table}` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `path`        VARCHAR(255) NOT NULL DEFAULT '',
    `seo_title`   VARCHAR(255) NOT NULL DEFAULT '',
    `seo_desc`    TEXT,
    `og_image`    VARCHAR(500) DEFAULT '',
    `schema_type` VARCHAR(100) DEFAULT '',
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `path` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// schema_type 컬럼이 짧으면 늘려두기 (여러 타입 합쳐 저장하므로)
$col = mysqli_fetch_assoc(mysqli_query($db, "SHOW COLUMNS FROM `{$table}` LIKE 'schema_type'"));
if ($col && stripos($col['Type'], 'varchar(100)') !== false) {
    mysqli_query($db, "ALTER TABLE `{$table}` MODIFY `schema_type` VARCHAR(255) DEFAULT ''");
}

// schema_json 컬럼 자동 추가 (생성된 JSON-LD 본문 저장 — head_sub이 경로별로 출력)
$col_json = mysqli_fetch_assoc(mysqli_query($db, "SHOW COLUMNS FROM `{$table}` LIKE 'schema_json'"));
if (!$col_json) {
    mysqli_query($db, "ALTER TABLE `{$table}` ADD `schema_json` LONGTEXT NULL AFTER `schema_type`");
}

// ── 저장 (AJAX) ──────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'save') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    // ── 스키마만 동기화 (자동 갱신용) — 다른 필드는 절대 건드리지 않음 ──
    if (!empty($data['_sync_only'])) {
        $id          = (int)($data['id'] ?? 0);
        $schema_type = mysqli_real_escape_string($db, trim($data['schema_type'] ?? ''));
        if ($id) {
            mysqli_query($db, "UPDATE `{$table}` SET schema_type='{$schema_type}' WHERE id={$id}");
        }
        echo json_encode(['ok' => true, 'sync' => true]);
        exit;
    }

    // ── 일반 전체 저장 ──
    $id          = (int)($data['id'] ?? 0);
    $path        = trim($data['path'] ?? '');
    // 경로 정규화: pathname + (id 파라미터 있으면 ?id=값만). head_sub 조회 규칙과 반드시 동일.
    if ($path !== '') {
        $p_parts = explode('?', $path, 2);
        $p_path  = $p_parts[0];
        $p_id    = '';
        if (isset($p_parts[1]) && $p_parts[1] !== '') {
            parse_str($p_parts[1], $p_q);
            if (isset($p_q['id']) && $p_q['id'] !== '') {
                $p_id = '?id=' . preg_replace('/[^0-9A-Za-z_-]/', '', $p_q['id']);
            }
        }
        $path = $p_path . $p_id;
    }
    $path        = mysqli_real_escape_string($db, $path);
    $seo_title   = mysqli_real_escape_string($db, trim($data['seo_title']   ?? ''));
    $seo_desc    = mysqli_real_escape_string($db, trim($data['seo_desc']    ?? ''));
    $og_image    = mysqli_real_escape_string($db, trim($data['og_image']    ?? ''));
    $schema_type = mysqli_real_escape_string($db, trim($data['schema_type'] ?? ''));
    $schema_json = mysqli_real_escape_string($db, trim($data['schema_json'] ?? ''));

    if (!$path) { echo json_encode(['ok'=>false,'err'=>'경로 없음']); exit; }

    if ($id) {
        $ok = mysqli_query($db, "UPDATE `{$table}` SET
            path='{$path}', seo_title='{$seo_title}', seo_desc='{$seo_desc}',
            og_image='{$og_image}', schema_type='{$schema_type}', schema_json='{$schema_json}', updated_at=NOW()
            WHERE id={$id}");
    } else {
        $ok = mysqli_query($db, "INSERT INTO `{$table}` (path,seo_title,seo_desc,og_image,schema_type,schema_json,updated_at)
            VALUES('{$path}','{$seo_title}','{$seo_desc}','{$og_image}','{$schema_type}','{$schema_json}',NOW())
            ON DUPLICATE KEY UPDATE
            seo_title='{$seo_title}', seo_desc='{$seo_desc}', og_image='{$og_image}',
            schema_type='{$schema_type}', schema_json='{$schema_json}', updated_at=NOW()");
    }

    echo json_encode(['ok' => (bool)$ok, 'err' => $ok ? '' : mysqli_error($db), 'insert_id' => mysqli_insert_id($db)]);
    exit;
}

// ── 삭제 (AJAX) ──────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    if ($id) mysqli_query($db, "DELETE FROM `{$table}` WHERE id={$id}");
    echo json_encode(['ok' => true]);
    exit;
}

// ── 목록 조회 ────────────────────────────────────────────
$rows = [];
$r = mysqli_query($db, "SELECT * FROM `{$table}` ORDER BY updated_at DESC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;

// ── 전역 Dentist @id 기준 도메인 (site_config와 동일 규칙: 정식도메인 우선) ──
$__cfg = mysqli_fetch_assoc(mysqli_query($db, "SELECT cf_official_domain, cf_site_name FROM dm_config WHERE cf_id = 1"));
$official_domain = trim($__cfg['cf_official_domain'] ?? '');
$cfg_site_name   = trim($__cfg['cf_site_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SEO 관리</title>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Pretendard','Apple SD Gothic Neo',sans-serif;background:#f5f6fa;color:#1a1a2e;}
#admin_wrapper{display:flex;min-height:100vh;}
.content_area{flex:1;padding:40px;overflow-x:hidden;display:flex;flex-direction:column;gap:20px;}
.page_header{display:flex;align-items:center;justify-content:space-between;}
.page_title{font-size:22px;font-weight:700;display:flex;align-items:center;gap:10px;}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #e8eaf0;overflow:hidden;}
.card_hd{padding:14px 20px;border-bottom:1px solid #e8eaf0;display:flex;align-items:center;justify-content:space-between;}
.card_hd_title{font-size:14px;font-weight:700;display:flex;align-items:center;gap:6px;}
.card_hd_title .material-symbols-outlined{font-size:16px;color:#4f46e5;}
.card_body{padding:20px;}
.btn_primary{padding:9px 20px;background:#4f46e5;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:5px;font-family:inherit;}
.btn_primary:hover{background:#4338ca;}
.btn_primary:disabled{opacity:.5;cursor:not-allowed;}
.btn_neutral{padding:9px 16px;background:#f5f6fa;color:#5c5c7a;border:1px solid #e8eaf0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:5px;font-family:inherit;}
.btn_neutral:hover{background:#e8eaf0;}
.btn_green{padding:9px 16px;background:#059669;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:5px;font-family:inherit;}
.btn_green:hover{background:#047857;}
.btn_refresh{padding:7px 14px;background:#fff;color:#4f46e5;border:1px solid #c7d2fe;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:5px;font-family:inherit;}
.btn_refresh:hover{background:#eef2ff;}
.btn_refresh:disabled{opacity:.5;cursor:not-allowed;}
.btn_refresh .material-symbols-outlined{font-size:14px;}
.spin{animation:spin 1s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
.btn_del{padding:5px 10px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;}
.btn_edit{padding:5px 10px;background:#4f46e5;color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;}
.form_group{margin-bottom:14px;}
.form_group label{display:block;font-size:12px;font-weight:700;color:#9999bb;margin-bottom:5px;}
.form_input{width:100%;padding:9px 12px;border:1px solid #e8eaf0;border-radius:8px;font-size:13px;background:#f5f6fa;color:#1a1a2e;font-family:inherit;transition:all .2s;outline:none;}
.form_input:focus{border-color:#4f46e5;background:#fff;box-shadow:0 0 0 3px rgba(79,70,229,.1);}
textarea.form_input{resize:vertical;min-height:72px;line-height:1.6;}
.url-row{display:flex;gap:8px;margin-bottom:20px;}
.schema-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.schema-badge.has{background:#f0fdf4;color:#059669;border:1px solid #bbf7d0;}
.schema-badge.none{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.schema-badge.loading{background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;}
.schema-cell{display:inline-flex;align-items:center;transition:opacity .2s;}
.og-preview{width:60px;height:40px;object-fit:cover;border-radius:5px;border:1px solid #e8eaf0;background:#f5f6fa;}
.eq_table{width:100%;border-collapse:collapse; overflow-x: scroll;}
.eq_table thead tr{background:#fafafa;border-bottom:1px solid #e8eaf0;}
.eq_table th{padding:10px 14px;font-size:11px;font-weight:700;color:#9999bb;text-transform:uppercase;letter-spacing:.05em;text-align:left;}
.eq_table td{padding:11px 14px;border-bottom:1px solid #f5f5f5;font-size:13px;vertical-align:middle;     text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    max-width: 200px;}
.eq_table tbody tr:hover{background:#fafbff;}
.eq_table tbody tr:last-child td{border-bottom:none;}
.path-cell{font-family:monospace;font-size:12px;color:#4f46e5;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.title-cell{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;}
.desc-cell{max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#6b7280;font-size:12px;}
.empty_state{text-align:center;padding:48px;color:#9999bb;font-size:14px;}
/* 모달 */
.seo-modal-backdrop{display:none!important;position:fixed!important;inset:0!important;background:rgba(0,0,0,.5)!important;z-index:999999!important;align-items:center!important;justify-content:center!important;}
.seo-modal-backdrop.show{display:flex!important;}
.seo-modal-box{background:#fff!important;border-radius:14px!important;padding:28px!important;width:100%!important;max-width:1080px!important;max-height:92vh!important;overflow-y:auto!important;box-shadow:0 20px 60px rgba(0,0,0,.2)!important;position:relative!important;z-index:1000000!important;}
.modal-title{font-size:16px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;}
.modal-footer{display:flex;gap:8px;justify-content:flex-end;margin-top:20px;}
/* 2단 레이아웃: 왼쪽 입력 | 오른쪽 미리보기 */
.modal-cols{display:flex;gap:24px;align-items:flex-start;}
.modal-col-left{flex:1 1 0;min-width:0;}
.modal-col-right{flex:1 1 0;min-width:0;position:sticky;top:0;}
@media(max-width:880px){.modal-cols{flex-direction:column;}.seo-modal-box{max-width:600px!important;}}
/* 정확도 게이지 */
.acc-wrap{margin-top:10px;padding:12px 14px;border-radius:10px;border:1px solid #e8eaf0;background:#fafbff;}
.acc-head{display:flex;align-items:center;justify-content:space-between;font-size:12px;font-weight:700;margin-bottom:8px;}
.acc-bar{height:8px;border-radius:6px;background:#eef0f6;overflow:hidden;}
.acc-fill{height:100%;border-radius:6px;transition:width .4s,background .4s;}
.acc-detail{font-size:11px;color:#6b7280;margin-top:8px;line-height:1.7;}
.acc-actions{display:none;gap:8px;margin-top:12px;}
.acc-actions.show{display:flex;}
.btn_warn{flex:1;padding:9px 12px;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;background:#fef3c7;color:#92400e;display:flex;align-items:center;justify-content:center;gap:5px;}
.btn_warn:hover{background:#fde68a;}
.btn_warn.reduce{background:#e0e7ff;color:#3730a3;}
.btn_warn.reduce:hover{background:#c7d2fe;}
.crawl-status{font-size:12px;color:#4f46e5;margin-top:8px;min-height:18px;}
.og-thumb{width:100%;max-height:120px;object-fit:cover;border-radius:8px;border:1px solid #e8eaf0;margin-top:6px;display:none;}
.toast{position:fixed;top:20px;right:20px;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:7px;z-index:1000001!important;box-shadow:0 4px 16px rgba(0,0,0,.12);animation:tin .22s ease;}
.toast.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#059669;}
.toast.error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}
@keyframes tin{from{opacity:0;transform:translateX(12px)}to{opacity:1;transform:none}}
.sync-hint{font-size:11px;color:#9999bb;display:inline-flex;align-items:center;gap:4px;}
</style>
</head>
<body>
<div id="admin_wrapper">
    <?php include_once('../aside.php'); ?>
    <main class="content_area">

        <div class="page_header">
            <h2 class="page_title">
                <span class="material-symbols-outlined" style="font-size:22px;color:#4f46e5;">manage_search</span>
                SEO 관리
            </h2>
            <button class="btn_primary" onclick="openModal()">
                <span class="material-symbols-outlined" style="font-size:14px;">add</span>페이지 추가
            </button>
        </div>

        <!-- 안내 -->
        <div style="background:#ede9fe;border-radius:10px;padding:14px 18px;font-size:13px;color:#4f46e5;line-height:1.7;">
            <strong>💡 사용법</strong> — URL을 입력하면 제목·설명·이미지·스키마를 자동으로 가져옵니다.
            저장하면 해당 페이지 접속 시 <code style="background:#c4b5fd;padding:1px 6px;border-radius:4px;">head_sub.php</code>에서 자동으로 적용됩니다.
            <br>스키마 상태는 <strong>페이지를 열 때마다 실시간으로 다시 확인</strong>되어 최신 상태로 표시됩니다.
        </div>

        <!-- 목록 -->
        <div class="card">
            <div class="card_hd">
                <span class="card_hd_title">
                    <span class="material-symbols-outlined">list</span>등록된 페이지
                </span>
                <span style="display:inline-flex;align-items:center;gap:12px;">
                    <span class="sync-hint" id="syncHint"></span>
                    <button class="btn_refresh" id="refreshBtn" onclick="refreshAllSchemas(true)">
                        <span class="material-symbols-outlined" id="refreshIcon">refresh</span>스키마 새로고침
                    </button>
                    <span style="font-size:12px;color:#9999bb;"><?= count($rows) ?>개</span>
                </span>
            </div>
            <?php if (empty($rows)): ?>
            <div class="empty_state">등록된 페이지가 없습니다.<br>우측 상단 [페이지 추가] 버튼을 눌러 시작하세요.</div>
            <?php else: ?>
            <table class="eq_table">
                <thead>
                    <tr>
                        <th width="50">OG</th>
                        <th>경로</th>
                        <th>타이틀</th>
                        <th>디스크립션</th>
                        <th width="140">스키마</th>
                        <th width="95">수정일</th>
                        <th width="90" style="text-align:center;">관리</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <?php if (!empty($row['og_image'])): ?>
                        <img src="<?= htmlspecialchars($row['og_image']) ?>" class="og-preview" onerror="this.style.display='none'">
                        <?php else: ?>
                        <div style="width:60px;height:40px;background:#f5f6fa;border-radius:5px;border:1px solid #e8eaf0;display:flex;align-items:center;justify-content:center;">
                            <span class="material-symbols-outlined" style="font-size:14px;color:#9999bb;">image_not_supported</span>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><span class="path-cell" title="<?= htmlspecialchars($row['path']) ?>"><?= htmlspecialchars($row['path']) ?></span></td>
                    <td><span class="title-cell" title="<?= htmlspecialchars($row['seo_title']) ?>"><?= htmlspecialchars($row['seo_title'] ?: '—') ?></span></td>
                    <td><span class="desc-cell" title="<?= htmlspecialchars($row['seo_desc']) ?>"><?= htmlspecialchars($row['seo_desc'] ?: '—') ?></span></td>
                    <td>
                        <span class="schema-cell"
                              data-id="<?= (int)$row['id'] ?>"
                              data-path="<?= htmlspecialchars($row['path'], ENT_QUOTES) ?>">
                        <?php if (!empty($row['schema_type'])): ?>
                            <span class="schema-badge has"><span class="material-symbols-outlined" style="font-size:12px;">check_circle</span><?= htmlspecialchars($row['schema_type']) ?></span>
                        <?php else: ?>
                            <span class="schema-badge none"><span class="material-symbols-outlined" style="font-size:12px;">warning</span>없음</span>
                        <?php endif; ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:#9999bb;"><?= date('Y.m.d', strtotime($row['updated_at'])) ?></td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:5px;justify-content:center;">
                            <button class="btn_edit" onclick='openModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)'>
                                <span class="material-symbols-outlined" style="font-size:11px;">edit</span>수정
                            </button>
                            <button class="btn_del" onclick="deletePage(<?= (int)$row['id'] ?>, '<?= htmlspecialchars($row['path']) ?>')">
                                <span class="material-symbols-outlined" style="font-size:11px;">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- 모달 -->
<div class="seo-modal-backdrop" id="modalBackdrop">
    <div class="seo-modal-box">
        <div class="modal-title">
            <span id="modalTitle">페이지 SEO 추가</span>
            <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;font-size:20px;color:#9999bb;">×</button>
        </div>

        <input type="hidden" id="editId" value="0">

      <div class="modal-cols">
       <div class="modal-col-left">

        <!-- URL 크롤링 -->
        <div class="url-row">
            <input type="text" id="crawlUrl" class="form_input" placeholder="https://example.com/sub/ems_scale.php 또는 /sub/ems_scale.php" style="flex:1;">
            <button class="btn_green" id="crawlBtn" onclick="doCrawl()">
                <span class="material-symbols-outlined" style="font-size:14px;">travel_explore</span>자동 가져오기
            </button>
        </div>
        <div class="crawl-status" id="crawlStatus"></div>

        <hr style="border:none;border-top:1px solid #e8eaf0;margin:16px 0;">

        <!-- 경로 -->
        <div class="form_group">
            <label>경로 *</label>
            <input type="text" id="fPath" class="form_input" placeholder="/sub/ems_scale.php">
        </div>

        <!-- 타이틀 -->
        <div class="form_group">
            <label>SEO 타이틀</label>
            <input type="text" id="fTitle" class="form_input" placeholder="EMS 스케일링 | 베스트플란트치과">
            <div id="titleLen" style="font-size:11px;color:#9999bb;margin-top:3px;text-align:right;"></div>
        </div>

        <!-- 디스크립션 -->
        <div class="form_group">
            <label>메타 디스크립션</label>
            <textarea id="fDesc" class="form_input" rows="3" placeholder="EMS 에어플로우로 통증 없이..."></textarea>
            <div id="descLen" style="font-size:11px;color:#9999bb;margin-top:3px;text-align:right;"></div>
        </div>

        <!-- OG 이미지 -->
        <div class="form_group">
            <label>OG 이미지 URL <span style="font-weight:400;color:#9999bb;">— 자동 감지 또는 직접 입력</span></label>
            <input type="text" id="fOgImage" class="form_input" placeholder="https://..." oninput="previewOg(this.value)">
            <img id="ogThumb" class="og-thumb" src="" alt="OG 이미지 미리보기">
        </div>

        <!-- 기존 스키마 감지 경고 -->
        <div id="hardcodeWarn" style="display:none;margin-bottom:12px;padding:10px 14px;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;font-size:12px;color:#854d0e;line-height:1.6;"></div>

        <!-- breadcrumb 섹션 -->
        <div class="form_group">
            <label>빵부스러기(Breadcrumb) 섹션</label>
            <div id="bcAuto" style="display:none;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;color:#059669;margin-bottom:6px;"></div>
            <select id="fBcSection" class="form_input">
                <option value="">— 메뉴에서 섹션 선택 (선택) —</option>
            </select>
            <small style="display:block;font-size:11px;color:#9999bb;margin-top:4px;">홈 &gt; <b>[이 섹션]</b> &gt; [페이지 제목] 순으로 BreadcrumbList가 만들어집니다. 메뉴에 있는 페이지면 자동 선택됩니다.</small>
        </div>

        <!-- 스키마 타입 셀렉트 -->
        <div class="form_group">
            <label>스키마 타입 <span style="font-weight:400;color:#9999bb;">— 페이지 성격에 맞게 선택</span></label>
            <select id="fSchemaTypeSel" class="form_input" onchange="onTypeChange()">
                <option value="">— 선택하세요 —</option>
                <option value="MedicalProcedure">MedicalProcedure (진료·시술)</option>
                <option value="MedicalCondition">MedicalCondition (질환·증상)</option>
                <option value="Article">Article (개별 칼럼·글)</option>
                <option value="Blog">Blog (칼럼·사례 목록)</option>
                <option value="MedicalWebPage">MedicalWebPage (일반 안내)</option>
            </select>
            <small id="typeDesc" style="display:block;font-size:11px;color:#6b7280;margin-top:6px;line-height:1.6;min-height:16px;"></small>
            <div id="typeRecommend" style="display:none;font-size:11px;color:#b45309;margin-top:4px;"></div>
        </div>

       </div><!-- /modal-col-left -->

       <div class="modal-col-right">

        <!-- 스키마 생성/결과 -->
        <div class="form_group">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                <label style="margin-bottom:0;">스키마</label>
                <button class="btn_green" id="genSchemaBtn" onclick="generatePageSchema()" style="padding:6px 12px;font-size:12px;">
                    <span class="material-symbols-outlined" style="font-size:13px;">auto_awesome</span>스키마 생성
                </button>
            </div>
            <div id="schemaResult" style="padding:9px 12px;background:#f5f6fa;border-radius:8px;font-size:13px;color:#6b7280;border:1px solid #e8eaf0;">
                타입 선택 후 [스키마 생성]을 누르세요
            </div>
            <input type="hidden" id="fSchemaType" value="">
            <input type="hidden" id="fSchemaJson" value="">
            <div id="schemaJsonPreview" style="display:none;margin-top:8px;">
                <div style="font-size:11px;font-weight:700;color:#9999bb;margin-bottom:4px;">생성된 JSON-LD <span style="font-weight:400;">— 저장 시 head_sub이 이 페이지에 자동 출력</span></div>
                <pre id="schemaJsonContent" style="background:#1a1a2e;color:#a5f3fc;border-radius:8px;padding:12px 14px;font-family:monospace;font-size:11px;line-height:1.5;max-height:360px;overflow:auto;white-space:pre-wrap;word-break:break-all;"></pre>
            </div>

            <!-- 추출 정확도 게이지 -->
            <div class="acc-wrap" id="accWrap" style="display:none;">
                <div class="acc-head">
                    <span>추출 정확도</span>
                    <span id="accPct" style="font-size:14px;">0%</span>
                </div>
                <div class="acc-bar"><div class="acc-fill" id="accFill" style="width:0%;background:#dc2626;"></div></div>
                <div class="acc-detail" id="accDetail"></div>
                <div class="acc-actions" id="accActions">
                    <button type="button" class="btn_warn" onclick="doCrawl(true)">
                        <span class="material-symbols-outlined" style="font-size:14px;">refresh</span>재크롤링
                    </button>
                    <button type="button" class="btn_warn reduce" onclick="reduceSchema()">
                        <span class="material-symbols-outlined" style="font-size:14px;">compress</span>스키마 축소
                    </button>
                </div>
            </div>
        </div>

       </div><!-- /modal-col-right -->
      </div><!-- /modal-cols -->

        <div class="modal-footer">
            <button class="btn_neutral" onclick="closeModal()">취소</button>
            <button class="btn_primary" id="saveBtn" onclick="savePage()">
                <span class="material-symbols-outlined" style="font-size:14px;">save</span>저장
            </button>
        </div>
    </div>
</div>

<script>
// ── 전역 Dentist @id 기준 (site_config와 동일 규칙: 정식도메인 우선 + 퓨니코드) ──
var OFFICIAL_DOMAIN = <?= json_encode($official_domain) ?>;
var CFG_SITE_NAME   = <?= json_encode($cfg_site_name) ?>;

function schemaHost() {
    var base = (OFFICIAL_DOMAIN && OFFICIAL_DOMAIN.trim()) ? OFFICIAL_DOMAIN.trim() : location.origin;
    base = base.replace(/^http:\/\//i, 'https://').replace(/\/+$/, '');
    try {
        var m = base.match(/^(https?:\/\/)(.+)$/i);
        if (m && /[^\x00-\x7F]/.test(m[2])) {
            var enc = new URL('http://' + m[2]).hostname;
            if (enc) base = m[1] + enc;
        }
    } catch(e) {}
    return base;
}

// 경로 정규화: pathname + (id 있으면 ?id=값만). head_sub/PHP 저장 규칙과 동일.
function normalizePath(rawPath) {
    var p = rawPath || '';
    if (p.indexOf('http') === 0) {
        try { var u = new URL(p); p = u.pathname + (u.search || ''); } catch(e) {}
    }
    var parts = p.split('?');
    var pathOnly = parts[0];
    var idPart = '';
    if (parts[1]) {
        var sp = new URLSearchParams(parts[1]);
        var idv = sp.get('id');
        if (idv) idPart = '?id=' + idv.replace(/[^0-9A-Za-z_-]/g, '');
    }
    return pathOnly + idPart;
}

var _toastTimer;
function showToast(msg, type) {
    var el = document.createElement('div');
    el.className = 'toast ' + (type || 'ok');
    el.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">' + (type === 'error' ? 'error' : 'check_circle') + '</span>' + msg;
    document.body.appendChild(el);
    setTimeout(function() { el.remove(); }, 3000);
}

// ════════════════════════════════════════════════════════
// 공용: JSON-LD에서 @type 재귀 수집 (객체 / 배열 / @graph 모두 대응)
// ════════════════════════════════════════════════════════
function collectSchemaTypes(doc) {
    var out = [];
    function walk(node) {
        if (!node || typeof node !== 'object') return;
        if (Array.isArray(node)) { node.forEach(walk); return; }
        if (node['@type']) {
            if (Array.isArray(node['@type'])) out.push.apply(out, node['@type']);
            else out.push(node['@type']);
        }
        if (node['@graph']) walk(node['@graph']);
    }
    doc.querySelectorAll('script[type="application/ld+json"]').forEach(function(s) {
        try { walk(JSON.parse(s.textContent)); } catch(e) {}
    });
    return [].concat.apply([], out.map(function(t){ return t; })); // flat
}

// 타입 배열 → 정리된 문자열 (중복 제거, @type 단일/배열 모두 평탄화)
function typesToStr(doc) {
    var types = collectSchemaTypes(doc);
    var flat = [];
    types.forEach(function(t) {
        if (Array.isArray(t)) flat = flat.concat(t);
        else flat.push(t);
    });
    return [...new Set(flat)].join(', ');
}

// ── 모달 ─────────────────────────────────────────────────
function openModal(row) {
    document.getElementById('editId').value    = row ? row.id : 0;
    document.getElementById('modalTitle').textContent = row ? '페이지 SEO 수정' : '페이지 SEO 추가';
    document.getElementById('crawlUrl').value  = row ? (location.origin + row.path) : '';
    document.getElementById('fPath').value     = row ? row.path : '';
    document.getElementById('fTitle').value    = row ? row.seo_title : '';
    document.getElementById('fDesc').value     = row ? row.seo_desc : '';
    document.getElementById('fOgImage').value  = row ? row.og_image : '';
    document.getElementById('fSchemaType').value = row ? row.schema_type : '';
    document.getElementById('fSchemaJson').value = row ? (row.schema_json || '') : '';
    document.getElementById('crawlStatus').textContent = '';

    // 타입 셀렉트/섹션/경고 초기화 (수정 모드면 기존 schema_type 첫 타입으로 복원 시도)
    var tsel = document.getElementById('fSchemaTypeSel');
    if (tsel) {
        var firstType = row && row.schema_type ? (row.schema_type.split(',')[0] || '').trim() : '';
        var validTypes = ['MedicalProcedure','MedicalCondition','Article','Blog','MedicalWebPage'];
        tsel.value = validTypes.indexOf(firstType) !== -1 ? firstType : '';
        onTypeChange();
    }
    var rec = document.getElementById('typeRecommend'); if (rec) rec.style.display = 'none';
    var warn = document.getElementById('hardcodeWarn'); if (warn) warn.style.display = 'none';
    var bcAuto = document.getElementById('bcAuto'); if (bcAuto) bcAuto.style.display = 'none';

    updateLens();
    previewOg(row ? row.og_image : '');
    renderSchemaResult(row ? row.schema_type : '', row ? !!row.schema_type : null);
    renderSchemaJsonPreview(row ? (row.schema_json || '') : '');

    document.getElementById('modalBackdrop').classList.add('show');
    var aw = document.getElementById('accWrap'); if (aw) aw.style.display = 'none';
    var aa = document.getElementById('accActions'); if (aa) aa.classList.remove('show');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

// 모달 밖(backdrop) 클릭으로는 닫지 않음 — 입력 중 실수 방지
// (닫기는 X 버튼 / 취소 버튼으로만)

// ── 글자수 카운터 ─────────────────────────────────────────
function updateLens() {
    var t = document.getElementById('fTitle').value.length;
    var d = document.getElementById('fDesc').value.length;
    document.getElementById('titleLen').textContent = t + '자' + (t > 60 ? ' ⚠️ 60자 초과' : '');
    document.getElementById('descLen').textContent  = d + '자' + (d > 160 ? ' ⚠️ 160자 초과' : '');
}
document.getElementById('fTitle').addEventListener('input', updateLens);
document.getElementById('fDesc').addEventListener('input', updateLens);

// ── OG 이미지 미리보기 ────────────────────────────────────
function previewOg(url) {
    var thumb = document.getElementById('ogThumb');
    if (url && url.startsWith('http')) {
        thumb.src = url;
        thumb.style.display = 'block';
        thumb.onerror = function() { thumb.style.display = 'none'; };
    } else {
        thumb.style.display = 'none';
    }
}

// ── 모달 내 스키마 결과 렌더링 ────────────────────────────
function renderSchemaResult(typeStr, has) {
    var el = document.getElementById('schemaResult');
    if (has === null) {
        el.textContent = '자동 가져오기 후 표시됩니다';
        el.style.color = '#6b7280';
        return;
    }
    if (has && typeStr) {
        el.innerHTML = '<span style="color:#059669;font-weight:700;">✅ 스키마 있음</span> — ' + typeStr;
    } else {
        el.innerHTML = '<span style="color:#dc2626;font-weight:700;">⚠️ 스키마 없음</span> — head_sub.php 공통 스키마만 적용됩니다';
    }
}

// ── 크롤링 (모달: 자동 가져오기) ──────────────────────────
async function doCrawl(autoGen) {
    var rawUrl = document.getElementById('crawlUrl').value.trim();
    if (!rawUrl) { showToast('URL을 입력해주세요', 'error'); return; }

    var btn    = document.getElementById('crawlBtn');
    var status = document.getElementById('crawlStatus');
    btn.disabled = true;
    status.style.color = '#4f46e5';
    status.textContent = '페이지 불러오는 중...';

    var url = rawUrl;
    if (url.startsWith('/')) url = location.origin + url;

    try {
        var res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) {
            var reason = '';
            if (res.status === 403)      reason = '접근이 거부되었습니다 (403 Forbidden)';
            else if (res.status === 404) reason = '페이지를 찾을 수 없습니다 (404 Not Found)';
            else if (res.status === 500) reason = '서버 오류가 발생했습니다 (500 Internal Server Error)';
            else                         reason = 'HTTP 오류: ' + res.status;
            status.textContent = '❌ 실패 — ' + reason;
            status.style.color = '#dc2626';
            btn.disabled = false;
            return;
        }

        var html   = await res.text();
        var parser = new DOMParser();
        var doc    = parser.parseFromString(html, 'text/html');
        window._lastCrawlDoc = doc;   // 스키마 생성기가 재사용

        // 제목
        var title = '';
        var ogTitle = doc.querySelector('meta[property="og:title"]');
        if (ogTitle) title = ogTitle.getAttribute('content') || '';
        if (!title) title = doc.title || '';
        title = title.trim();

        // 디스크립션
        var desc = '';
        var ogDesc = doc.querySelector('meta[property="og:description"]');
        if (ogDesc) desc = ogDesc.getAttribute('content') || '';
        if (!desc) {
            var metaDesc = doc.querySelector('meta[name="description"]');
            if (metaDesc) desc = metaDesc.getAttribute('content') || '';
        }
        desc = desc.replace(/\s+/g, ' ').trim();

        // OG 이미지 → 없으면 첫 번째 img
        var ogImg = '';
        var ogImgEl = doc.querySelector('meta[property="og:image"]');
        if (ogImgEl) ogImg = ogImgEl.getAttribute('content') || '';
        if (!ogImg) {
            var firstImg = doc.querySelector('main img, article img, #content img, img');
            if (firstImg) {
                var src = firstImg.getAttribute('src') || '';
                if (src.startsWith('http')) ogImg = src;
                else if (src.startsWith('/')) ogImg = location.origin + src;
            }
        }

        // 스키마 타입 감지 (객체 / 배열 / @graph 모두 대응)
        var schemaStr = typesToStr(doc);

        // path 추출
        var parsed  = new URL(url);
        var path    = parsed.pathname + (parsed.search || '');

        // 입력 필드 채우기
        document.getElementById('fPath').value       = path;
        document.getElementById('fTitle').value      = title;
        document.getElementById('fDesc').value        = desc;
        document.getElementById('fOgImage').value    = ogImg;

        // ── 기존 하드코딩 스키마 감지 경고 ──
        var warnEl = document.getElementById('hardcodeWarn');
        if (schemaStr) {
            warnEl.style.display = 'block';
            warnEl.innerHTML = '⚠️ <b>이 페이지에 이미 스키마가 있습니다</b> — ' + schemaStr
                + '<br>툴로 일원화하려면 페이지(또는 head)에 하드코딩된 스키마를 제거하세요. 그대로 두면 중복 출력됩니다.';
        } else {
            warnEl.style.display = 'none';
        }

        // ── 메뉴 추출 → 섹션 셀렉트 채우기 + 자동 매칭 ──
        var menus = extractMenusFromDoc(doc);
        window._lastMenus = menus;
        var sel = document.getElementById('fBcSection');
        sel.innerHTML = '<option value="">— 메뉴에서 섹션 선택 (선택) —</option>';
        var seen = {};
        menus.forEach(function(m){
            var secName = m.parentLabel || m.label;
            if (secName && !seen[secName]) { seen[secName] = 1;
                var o = document.createElement('option'); o.value = secName; o.textContent = secName; sel.appendChild(o);
            }
        });
        var mm = matchMenu(menus, path);
        var bcAuto = document.getElementById('bcAuto');
        if (mm.matched && mm.section) {
            sel.value = mm.section;
            bcAuto.style.display = 'block';
            bcAuto.innerHTML = '✅ 메뉴에서 자동 확정: <b>' + mm.section + (mm.leaf && mm.leaf!==mm.section ? ' &gt; ' + mm.leaf : '') + '</b>';
        } else {
            bcAuto.style.display = 'block';
            bcAuto.style.background = '#fef9c3'; bcAuto.style.borderColor = '#fde68a'; bcAuto.style.color = '#854d0e';
            bcAuto.innerHTML = '⚠️ 메뉴에 없는 페이지입니다(개별 칼럼 등). 상위 섹션을 직접 선택하세요.';
        }

        // ── 타입 약한 추천 ──
        var recType = '';
        if (/[?&]id=/.test(path)) recType = 'Article';            // 개별 글
        else if (/_list|list\.php|\?cat=/.test(path)) recType = 'Blog'; // 목록
        else if (mm.matched) recType = 'MedicalProcedure';        // 메뉴 등록된 진료 페이지(다수)
        // 추천 적용 (사용자가 덮어쓸 수 있음)
        var typeSel = document.getElementById('fSchemaTypeSel');
        var recEl   = document.getElementById('typeRecommend');
        if (recType) {
            typeSel.value = recType;
            onTypeChange();
            recEl.style.display = 'block';
            recEl.dataset.recType = recType;
            recEl.innerHTML = '⚠️ <b>' + recType + '</b> 추천값입니다 — 맞는지 확인 후 생성하세요. (충치치료처럼 진료/질환이 모호하면 위 설명 참고)';
        } else {
            typeSel.value = '';
            onTypeChange();
            recEl.style.display = 'none';
        }

        updateLens();
        previewOg(ogImg);

        status.textContent = '✓ 자동 완성되었습니다. 타입을 확인하고 [스키마 생성] → 저장하세요.';
        status.style.color = '#059669';

        // 재크롤링 모드: 크롤 직후 스키마 자동 재생성
        if (autoGen === true && document.getElementById('fSchemaTypeSel').value) {
            generatePageSchema();
        }

    } catch(e) {
        var errMsg = e.message || '알 수 없는 오류';
        if (errMsg.includes('Failed to fetch') || errMsg.includes('NetworkError')) {
            errMsg = 'CORS 오류 — 외부 도메인은 직접 입력 후 저장하세요';
        } else if (errMsg.includes('net::ERR')) {
            errMsg = '네트워크 오류 — 도메인 주소를 확인하세요';
        }
        status.textContent = '❌ 오류 — ' + errMsg;
        status.style.color = '#dc2626';
    } finally {
        btn.disabled = false;
    }
}

// ── 저장 ─────────────────────────────────────────────────
async function savePage() {
    var path = document.getElementById('fPath').value.trim();
    if (!path) { showToast('경로를 입력하거나 자동 가져오기를 실행하세요', 'error'); return; }

    var btn = document.getElementById('saveBtn');
    btn.disabled = true;

    try {
        var res = await fetch('?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id:          parseInt(document.getElementById('editId').value),
                path:        path,
                seo_title:   document.getElementById('fTitle').value.trim(),
                seo_desc:    document.getElementById('fDesc').value.trim(),
                og_image:    document.getElementById('fOgImage').value.trim(),
                schema_type: document.getElementById('fSchemaType').value.trim(),
                schema_json: document.getElementById('fSchemaJson').value.trim(),
            })
        });
        var data = await res.json();
        if (data.ok) {
            showToast('저장되었습니다', 'ok');
            setTimeout(function() { location.reload(); }, 600);
        } else {
            showToast('저장 실패: ' + data.err, 'error');
        }
    } catch(e) {
        showToast('오류: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
    }
}

// ── 삭제 ─────────────────────────────────────────────────
async function deletePage(id, path) {
    if (!confirm('[' + path + ']\n이 페이지 SEO 설정을 삭제하시겠습니까?')) return;
    try {
        var res  = await fetch('?action=delete&id=' + id);
        var data = await res.json();
        if (data.ok) {
            showToast('삭제되었습니다', 'ok');
            setTimeout(function() { location.reload(); }, 500);
        }
    } catch(e) {
        showToast('오류: ' + e.message, 'error');
    }
}

// ════════════════════════════════════════════════════════
// 타입별 설명문 (모호 케이스 대조로 오선택 방지)
// ════════════════════════════════════════════════════════
var TYPE_DESC = {
    'MedicalProcedure': "받는 <b>치료·시술</b> 중심이면 이것. 예: '충치치료'가 '어떻게 치료하나(레진·인레이)'를 설명하면 진료. ↔ 병태 설명 위주면 MedicalCondition.",
    'MedicalCondition': "<b>질환·증상</b> 중심이면 이것. 예: '충치'가 '왜 생기나·증상이 뭔가'를 설명하면 질환. ↔ 치료법 위주면 MedicalProcedure.",
    'Article': "<b>개별 칼럼·글 1편</b>에 사용. ↔ 여러 글이 나열된 목록 페이지면 Blog.",
    'Blog': "<b>칼럼·임상사례 목록</b> 페이지용. ↔ 글 1편 상세는 Article.",
    'MedicalWebPage': "위 항목에 안 맞는 <b>일반 안내</b> 페이지용 폴백.",
    '': ""
};

function onTypeChange() {
    var t = document.getElementById('fSchemaTypeSel').value;
    document.getElementById('typeDesc').innerHTML = TYPE_DESC[t] || "";
    // 추천 표시는 사용자가 직접 바꾸면 숨김
    var rec = document.getElementById('typeRecommend');
    if (rec && rec.dataset.recType && rec.dataset.recType !== t) rec.style.display = 'none';
}

// 크롤한 DOM의 헤더 메뉴(gnb 대분류 + mega 하위)에서 {label, href, parent} 추출
function extractMenusFromDoc(doc) {
    var menus = []; // {label, href, parentLabel}
    var gnbLinks = doc.querySelectorAll('.gnb-item .gnb-link, .gnb .gnb-link');
    var gnbLabels = [];
    gnbLinks.forEach(function(a){
        var label = (a.textContent || '').trim();
        var href  = a.getAttribute('href') || '';
        gnbLabels.push(label);
        menus.push({ label: label, href: href, parentLabel: '', isParent: true });
    });
    // mega-col[data-col=i] → i번째 대분류의 children
    doc.querySelectorAll('.mega-col').forEach(function(col){
        var idx = parseInt(col.getAttribute('data-col'));
        var parentLabel = (!isNaN(idx) && gnbLabels[idx]) ? gnbLabels[idx] : '';
        col.querySelectorAll('.mega-sub-link').forEach(function(a){
            menus.push({
                label: (a.textContent || '').trim(),
                href: a.getAttribute('href') || '',
                parentLabel: parentLabel,
                isParent: false
            });
        });
    });
    // 모바일 메뉴 폴백 (mega가 비어있는 경우)
    if (!menus.some(function(m){ return !m.isParent; })) {
        doc.querySelectorAll('#mobileMenuHeader .mob-menu-link').forEach(function(a){
            var href = a.getAttribute('href') || '';
            if (href && href !== '#') {
                menus.push({ label: (a.textContent||'').trim(), href: href, parentLabel: '', isParent: false });
            }
        });
    }
    return menus;
}

// 현재 경로와 메뉴 href 매칭 → {section, type추천} 반환
function matchMenu(menus, path) {
    var pathOnly = path.split('?')[0];
    for (var i = 0; i < menus.length; i++) {
        var mHref = (menus[i].href || '').split('?')[0].split('#')[0];
        if (!mHref || mHref === '/') continue;
        if (mHref === pathOnly) {
            return {
                section: menus[i].parentLabel || menus[i].label,
                leaf: menus[i].label,
                matched: true
            };
        }
    }
    return { section: '', leaf: '', matched: false };
}


// ════════════════════════════════════════════════════════
// 페이지 스키마 생성기 (셀렉트 기반 — 경로 추측 없음)
// - 전역 Dentist/WebSite는 만들지 않음 (site_config가 cf_add_script로 이미 전 페이지에 출력)
// - 페이지 고유 타입만: BreadcrumbList + 선택타입(Procedure/Condition/Article/Blog/WebPage) + FAQPage(#faq)
// - provider/author/publisher/about는 전역 Dentist @id(host+'/#organization') 참조만 → 엔티티 그래프 연결
// ════════════════════════════════════════════════════════
// ════════════════════════════════════════════════════════
// 순서신호 기반 단계/항목 추출 (마크업 무관 — 텍스트 패턴만)
//  STEP 01 / CASE 01 / POINT 01 / 01. / 1) / 1단계  등
// ════════════════════════════════════════════════════════
function extractOrderedSteps(doc) {
    var root = doc.querySelector('main, #content, article, body') || doc.body;
    if (!root) return { steps: [], indications: [], cases: 0, faqExist: false };

    // 공백/줄바꿈 정규화 (<br>도 공백 처리)
    function norm(s) {
        return (s || '').replace(/\s+/g, ' ').trim();
    }
    // 엘리먼트 텍스트를 <br> 공백 분리해서 가져오기
    function elText(el) {
        if (!el) return '';
        var html = el.innerHTML || '';
        return norm(html.replace(/<br\s*\/?>/gi, ' ').replace(/<[^>]+>/g, ' '));
    }
    // 순번/라벨 접두 제거 (STEP 01 / 1단계 / CASE 01 / 01. / 1) / 단독 01 02)
    function stripLabel(s) {
        return norm(s)
            .replace(/^(STEP|POINT)\s*0?\d{1,2}\s*[\.\):]?\s*/i, '')
            .replace(/^\d{1,2}\s*단계\s*[\.\):]?\s*/, '')
            .replace(/^CASE\s*0?\d{1,2}\s*[\.\):]?\s*/i, '')
            .replace(/^0?\d{1,2}\s*[\.\)]\s*/, '')
            .replace(/^0?\d{1,2}\s+/, '')   // "01 텍스트" 처럼 점 없이 숫자만
            .trim();
    }

    var reStep  = /^\s*(STEP|POINT)\s*0?(\d{1,2})/i;
    var reStage = /^\s*(\d{1,2})\s*단계/;
    var reCase  = /^\s*CASE\s*0?(\d{1,2})/i;
    var reNum   = /^\s*(0?\d{1,2})\s*[\.\)]\s+\S/;

    var blocks = root.querySelectorAll('h2,h3,h4,h5,h6,li,p,div,span,strong');
    var seen = {};
    var rawSteps = [];

    // 신호가 든 "스텝 박스"를 찾아 그 안에서만 제목/설명 추출 (컨테이너 격리)
    function stepContainer(el) {
        // el 자체가 이미 스텝 박스면(자식 2개↑ + 라벨 외 제목/설명 보유) 그대로 사용
        if (el.children && el.children.length >= 2) return el;
        // 아니면 부모로 올라가며 자식 2개↑ 첫 컨테이너
        var p = el.parentElement;
        for (var up = 0; up < 3 && p; up++) {
            if (p.children.length >= 2) return p;
            p = p.parentElement;
        }
        return el.parentElement || el;
    }
    // 컨테이너 안에서 라벨을 뺀 제목/설명 뽑기 (컨테이너 텍스트만 사용 → 옆칸 침범 없음)
    function extractFromContainer(box, labelEl) {
        var title = '', desc = '';
        // 제목 후보: 컨테이너 안 h3~h6 (라벨 엘리먼트 제외)
        var heads = box.querySelectorAll('h3,h4,h5,h6');
        for (var i = 0; i < heads.length; i++) {
            if (heads[i] === labelEl) continue;
            var ht = stripLabel(elText(heads[i]));
            if (ht.length >= 2) { title = ht; break; }
        }
        // 설명 후보: 컨테이너 안 p (라벨/제목과 다른 것)
        var ps = box.querySelectorAll('p');
        for (var j = 0; j < ps.length; j++) {
            if (ps[j] === labelEl) continue;
            var pt = stripLabel(elText(ps[j]));
            if (pt.length >= 2 && pt !== title) { desc = pt; break; }
        }
        // 제목 못 찾으면: 라벨 뺀 컨테이너 첫 텍스트
        if (!title) {
            var own = stripLabel(elText(labelEl));
            if (own.length >= 2) title = own;
            else {
                var bt = stripLabel(elText(box));
                title = bt.split(' ').slice(0, 12).join(' ') || ('단계 ');
            }
        }
        return { title: title, desc: desc };
    }

    var blocks = root.querySelectorAll('h2,h3,h4,h5,h6,li,p,div,span,strong');
    var seen = {};
    blocks.forEach(function(el){
        var t = elText(el);
        if (!t || t.length > 400) return;

        var m, idx = null, signal = null;
        if ((m = t.match(reStep)))  { idx = parseInt(m[2],10); signal='step'; }
        else if ((m = t.match(reStage))) { idx = parseInt(m[1],10); signal='step'; }
        else if ((m = t.match(reCase)))  { idx = parseInt(m[1],10); signal='case'; }
        else if ((m = t.match(reNum)))   { idx = parseInt(m[1],10); signal='weak'; }
        if (idx === null) return;

        var key = signal + ':' + idx + ':' + t.slice(0, 30);
        if (seen[key]) return; seen[key] = 1;

        var box = stepContainer(el);
        var ext = extractFromContainer(box, el);
        var title = ext.title || ('단계 ' + idx);
        var desc  = (ext.desc && ext.desc !== title) ? ext.desc : '';
        rawSteps.push({ idx: idx, signal: signal, title: title, desc: desc, order: rawSteps.length });
    });

    var strong = rawSteps.filter(function(s){ return s.signal === 'step'; });
    var cases  = rawSteps.filter(function(s){ return s.signal === 'case'; });
    var chosen = strong.length ? strong : rawSteps.filter(function(s){ return s.signal === 'weak'; });

    chosen.sort(function(a,b){ return a.idx - b.idx || a.order - b.order; });
    var byIdx = {}, steps = [];
    chosen.forEach(function(s){
        if (byIdx[s.idx]) return; byIdx[s.idx] = 1;
        if (steps.length < 8) steps.push(s);
    });

    // indication: "필요/대상/이런 분/추천" 헤딩 근처 li (순번 라벨 제거)
    var indications = [];
    root.querySelectorAll('h2,h3,h4').forEach(function(h){
        if (/필요|이런\s*분|대상|추천/.test(elText(h))) {
            // 헤딩 바로 다음 형제 ul/ol만 (다른 섹션 침범 방지)
            var sib = h.nextElementSibling, hop = 0;
            while (sib && hop < 3) {
                if (sib.tagName === 'UL' || sib.tagName === 'OL') {
                    sib.querySelectorAll('li').forEach(function(li){
                        var lt = stripLabel(elText(li));
                        if (lt.length >= 4 && lt.length < 120 && indications.indexOf(lt) === -1)
                            indications.push(lt);
                    });
                    break;
                }
                sib = sib.nextElementSibling; hop++;
            }
        }
    });

    var faqExist = doc.querySelectorAll('details summary').length > 0;

    return { steps: steps, indications: indications.slice(0,8), cases: cases.length, faqExist: faqExist };
}

// ── 추출 정확도 계산 (채워진 핵심필드 가중합) ──
function computeAccuracy(parts) {
    var score = 0, detail = [];
    // name 20
    if (parts.name && parts.name.length >= 2) { score += 20; detail.push('이름 ✓'); }
    else detail.push('이름 ✗');
    // desc 20 (50자↑)
    if (parts.desc && parts.desc.length >= 50) { score += 20; detail.push('설명 ✓'); }
    else if (parts.desc && parts.desc.length > 0) { score += 10; detail.push('설명 △(짧음)'); }
    else detail.push('설명 ✗');
    // steps 30 (2개↑ 만점, 1개 절반)
    if (parts.stepCount >= 2) { score += 30; detail.push('치료단계 ' + parts.stepCount + '개 ✓'); }
    else if (parts.stepCount === 1) { score += 15; detail.push('치료단계 1개 △'); }
    else detail.push('치료단계 ✗');
    // indication 15 (1개↑)
    if (parts.indCount >= 1) { score += 15; detail.push('적응증 ' + parts.indCount + '개 ✓'); }
    else detail.push('적응증 ✗');
    // faq 15
    if (parts.faq) { score += 15; detail.push('FAQ ✓'); }
    else detail.push('FAQ ✗');
    return { pct: score, detail: detail.join(' · ') };
}

function renderAccuracy(acc) {
    var wrap = document.getElementById('accWrap');
    var fill = document.getElementById('accFill');
    var pctEl = document.getElementById('accPct');
    var detEl = document.getElementById('accDetail');
    var actEl = document.getElementById('accActions');
    if (!wrap) return;
    wrap.style.display = 'block';
    var color = acc.pct >= 80 ? '#16a34a' : (acc.pct >= 70 ? '#ca8a04' : '#dc2626');
    fill.style.width = acc.pct + '%';
    fill.style.background = color;
    pctEl.textContent = acc.pct + '%';
    pctEl.style.color = color;
    detEl.textContent = acc.detail;
    // 70% 미만 → 선택 버튼 노출
    if (acc.pct < 70) actEl.classList.add('show');
    else actEl.classList.remove('show');
}

// 스키마 축소: 불확실한 본체 타입을 MedicalWebPage로 낮춰 안전하게 (Breadcrumb/FAQ만 유지)
function reduceSchema() {
    var sel = document.getElementById('fSchemaTypeSel');
    if (sel) sel.value = 'MedicalWebPage';
    onTypeChange();
    window._forceReduce = true;
    generatePageSchema();
    window._forceReduce = false;
    showToast('스키마를 안전 모드(MedicalWebPage)로 축소했습니다', 'ok');
}

function renderSchemaJsonPreview(jsonStr) {
    var box = document.getElementById('schemaJsonPreview');
    var pre = document.getElementById('schemaJsonContent');
    if (jsonStr && jsonStr.trim()) {
        // <script> 래퍼 벗겨서 본문만 미리보기
        var inner = jsonStr.replace(/<script[^>]*>/i, '').replace(/<\/script>/i, '').trim();
        pre.textContent = inner;
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}

function generatePageSchema() {
    var doc = window._lastCrawlDoc;
    if (!doc) { showToast('먼저 [자동 가져오기]로 페이지를 불러오세요', 'error'); return; }

    var host = schemaHost();
    var orgId = host + '/#organization';
    var path  = normalizePath(document.getElementById('fPath').value.trim());
    var pageUrl = host + path;
    var title = document.getElementById('fTitle').value.trim();
    var desc  = document.getElementById('fDesc').value.trim();

    var graph = [];

    // ── 타입: 셀렉트에서 (필수) ──
    var bodyType = document.getElementById('fSchemaTypeSel').value;
    if (!bodyType) { showToast('스키마 타입을 선택하세요', 'error'); return; }

    // 추천값 그대로면 한 번 확인 (오선택 방지)
    var recEl = document.getElementById('typeRecommend');
    if (recEl && recEl.style.display !== 'none' && recEl.dataset.recType === bodyType) {
        if (!confirm(bodyType + ' 타입으로 스키마를 생성합니다.\n페이지 성격과 맞습니까?\n(충치치료처럼 진료/질환이 모호하면 취소 후 설명을 확인하세요)')) return;
    }

    // 페이지 제목/이름: h1 우선, 없으면 title
    var h1 = doc.querySelector('h1');
    var name = (h1 ? h1.textContent.trim() : '') || title;

    // ── 1. BreadcrumbList — 섹션 셀렉트 기반 (홈 > 섹션 > 페이지명) ──
    var section = document.getElementById('fBcSection').value.trim();
    if (section) {
        var bcItems = [
            { "@type":"ListItem", "position":1, "name":"홈", "item": host + "/" },
            { "@type":"ListItem", "position":2, "name": section }
        ];
        bcItems.push({ "@type":"ListItem", "position":3, "name": name, "item": pageUrl });
        graph.push({
            "@type": "BreadcrumbList",
            "@id": pageUrl + "#breadcrumb",
            "itemListElement": bcItems
        });
    }

    // ── 2. 본체 타입 (셀렉트 값 기준) ──
    if (bodyType === 'Article') {
        var article = {
            "@type": "Article",
            "@id": pageUrl + "#article",
            "headline": name || title,
            "mainEntityOfPage": pageUrl,
            "publisher": { "@id": orgId },
            "author": { "@id": orgId }
        };
        if (desc) article.description = desc;
        var ogImg = document.getElementById('fOgImage').value.trim();
        if (ogImg) article.image = ogImg;
        graph.push(article);
    } else if (bodyType === 'Blog') {
        var blog = {
            "@type": "Blog",
            "@id": pageUrl + "#blog",
            "name": name || title,
            "url": pageUrl,
            "publisher": { "@id": orgId }
        };
        if (desc) blog.description = desc;
        graph.push(blog);
    } else if (bodyType === 'MedicalProcedure') {
        var proc = {
            "@type": "MedicalProcedure",
            "@id": pageUrl + "#procedure",
            "name": name,
            "url": pageUrl,
            "provider": { "@id": orgId }
        };
        if (desc) proc.description = desc;
        // ── 순서신호 추출 → howPerformed / indication (축소 모드면 생략) ──
        if (!window._forceReduce) {
            var ex = extractOrderedSteps(doc);
            window._lastExtract = ex;
            if (ex.steps && ex.steps.length) {
                // howPerformed: 서술형 문자열
                proc.howPerformed = ex.steps.map(function(s, i){
                    var line = (i+1) + '단계: ' + s.title;
                    if (s.desc && s.desc !== s.title) line += ' — ' + s.desc;
                    return line;
                }).join(' ');
                // HowTo 노드도 함께 (step 배열) — implant_SL 수준 지향
                graph.push({
                    "@type": "HowTo",
                    "@id": pageUrl + "#howto",
                    "name": name + ' 치료 과정',
                    "about": { "@id": pageUrl + "#procedure" },
                    "step": ex.steps.map(function(s, i){
                        var st = { "@type":"HowToStep", "position": i+1, "name": s.title };
                        if (s.desc && s.desc !== s.title) st.text = s.desc;
                        return st;
                    })
                });
            }
            if (ex.indications && ex.indications.length) {
                proc.indication = ex.indications.map(function(t){
                    return { "@type":"MedicalIndication", "name": t };
                });
            }
        }
        graph.push(proc);
    } else if (bodyType === 'MedicalCondition') {
        var cond = {
            "@type": "MedicalCondition",
            "@id": pageUrl + "#condition",
            "name": name,
            "url": pageUrl
        };
        if (desc) cond.description = desc;
        graph.push(cond);
    } else {
        var wp = {
            "@type": "MedicalWebPage",
            "@id": pageUrl + "#webpage",
            "name": name || title,
            "url": pageUrl,
            "about": { "@id": orgId }
        };
        if (desc) wp.description = desc;
        graph.push(wp);
    }

    // ── 3. FAQPage — DOM에서 Q/A 추출 (details/summary, .faq, dt/dd) ──
    var faqs = [];
    // 공백 정규화 + Q./A 접두 제거
    function faqNorm(s, isQ) {
        s = (s || '').replace(/\s+/g, ' ').trim();
        if (isQ) s = s.replace(/^Q[\.\:]?\s*/i, '').replace(/^질문[\.\:]?\s*/, '');
        else     s = s.replace(/^A[\.\:]?\s*/i, '').replace(/^답변[\.\:]?\s*/, '');
        return s.trim();
    }
    // 패턴 A: <details><summary>Q</summary>A</details>
    doc.querySelectorAll('details').forEach(function(d){
        var q = d.querySelector('summary');
        if (!q) return;
        var qt = faqNorm(q.textContent, true);
        var at = faqNorm(d.textContent.replace(q.textContent, ''), false);
        if (qt && at) faqs.push({ q: qt, a: at });
    });
    // 패턴 B: .faq-item / .faq 안의 질문·답변 클래스
    if (!faqs.length) {
        doc.querySelectorAll('[class*="faq"]').forEach(function(item){
            var q = item.querySelector('[class*="quest"], [class*="title"], dt, h3, h4');
            var a = item.querySelector('[class*="answer"], [class*="cont"], dd, p');
            if (q && a) {
                var qt = faqNorm(q.textContent, true), at = faqNorm(a.textContent, false);
                if (qt && at && qt.length < 200) faqs.push({ q: qt, a: at });
            }
        });
    }
    if (faqs.length) {
        graph.push({
            "@type": "FAQPage",
            "@id": pageUrl + "#faq",
            "mainEntity": faqs.map(function(f){
                return {
                    "@type": "Question",
                    "name": f.q,
                    "acceptedAnswer": { "@type": "Answer", "text": f.a }
                };
            })
        });
    }

    if (!graph.length) {
        showToast('생성할 스키마를 찾지 못했습니다 (breadcrumb/콘텐츠 확인)', 'error');
        return;
    }

    // ── 출력: @graph로 묶어 <script> 블록 생성 ──
    var ldObj = { "@context": "https://schema.org", "@graph": graph };
    var jsonStr = JSON.stringify(ldObj, null, 2);
    var scriptBlock = '<script type="application/ld+json">\n' + jsonStr + '\n<\/script>';

    // 타입 요약 문자열
    var typeStr = graph.map(function(n){ return n['@type']; }).join(', ');

    document.getElementById('fSchemaJson').value = scriptBlock;
    document.getElementById('fSchemaType').value = typeStr;
    renderSchemaResult(typeStr, true);
    renderSchemaJsonPreview(scriptBlock);

    // ── 추출 정확도 표시 ──
    var ex = window._lastExtract || { steps: [], indications: [], faqExist: false };
    var faqInGraph = graph.some(function(n){ return n['@type'] === 'FAQPage'; });
    var acc = computeAccuracy({
        name: name,
        desc: desc,
        stepCount: (ex.steps || []).length,
        indCount: (ex.indications || []).length,
        faq: faqInGraph || ex.faqExist
    });
    renderAccuracy(acc);

    var msg = '스키마 생성 완료 — 정확도 ' + acc.pct + '%';
    if (acc.pct < 70) msg += ' (낮음: 재크롤링/축소 권장)';
    showToast(msg, acc.pct < 70 ? 'error' : 'ok');
    window._lastExtract = null;
}

// ════════════════════════════════════════════════════════
// 실시간 스키마 갱신 — 페이지를 열거나 새로고침할 때 자동 실행
// ════════════════════════════════════════════════════════

// 단일 경로 크롤 → 스키마 타입 문자열 반환 (실패 시 null)
async function fetchSchemaFor(path) {
    var url = path;
    if (url.indexOf('http') !== 0) {
        url = location.origin + (url.charAt(0) === '/' ? url : '/' + url);
    }
    try {
        var res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) return null;
        var html = await res.text();
        var doc  = new DOMParser().parseFromString(html, 'text/html');
        return typesToStr(doc);
    } catch (e) {
        return null;
    }
}

// 뱃지 DOM 갱신
function renderSchemaBadge(cell, schemaStr) {
    if (schemaStr) {
        cell.innerHTML = '<span class="schema-badge has">'
            + '<span class="material-symbols-outlined" style="font-size:12px;">check_circle</span>'
            + schemaStr + '</span>';
    } else {
        cell.innerHTML = '<span class="schema-badge none">'
            + '<span class="material-symbols-outlined" style="font-size:12px;">warning</span>없음</span>';
    }
}

function setCellLoading(cell) {
    cell.innerHTML = '<span class="schema-badge loading">'
        + '<span class="material-symbols-outlined spin" style="font-size:12px;">progress_activity</span>확인 중</span>';
}

var _refreshing = false;

// 모든 행 백그라운드 재크롤 → 뱃지 갱신 + DB 동기화
async function refreshAllSchemas(manual) {
    if (_refreshing) return;
    _refreshing = true;

    var cells = [...document.querySelectorAll('.schema-cell')];
    var btn   = document.getElementById('refreshBtn');
    var icon  = document.getElementById('refreshIcon');
    var hint  = document.getElementById('syncHint');

    if (btn)  btn.disabled = true;
    if (icon) icon.classList.add('spin');

    var done = 0, changed = 0, total = cells.length;
    if (hint) hint.textContent = total ? ('0 / ' + total + ' 확인 중…') : '';

    // 로딩 표시
    cells.forEach(setCellLoading);

    for (var i = 0; i < cells.length; i++) {
        var cell = cells[i];
        var path = cell.getAttribute('data-path');
        var id   = cell.getAttribute('data-id');
        var prev = cell.getAttribute('data-prev') || '';

        if (!path) { done++; continue; }

        var schemaStr = await fetchSchemaFor(path);
        done++;
        if (hint) hint.textContent = done + ' / ' + total + ' 확인 중…';

        if (schemaStr === null) {
            // 크롤 실패 → 기존 DB값 복원 (data-prev가 없으니 서버 초기 렌더로 되돌릴 수 없어 '없음' 대신 물음표 처리)
            cell.innerHTML = '<span class="schema-badge none" title="페이지를 불러오지 못했습니다">'
                + '<span class="material-symbols-outlined" style="font-size:12px;">help</span>확인 불가</span>';
            continue;
        }

        renderSchemaBadge(cell, schemaStr);

        // DB에 조용히 동기화 (다른 필드는 건드리지 않음)
        try {
            await fetch('?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    _sync_only: 1,
                    id: parseInt(id),
                    schema_type: schemaStr
                })
            });
            changed++;
        } catch (e) {}
    }

    if (icon) icon.classList.remove('spin');
    if (btn)  btn.disabled = false;
    if (hint) {
        hint.innerHTML = '<span class="material-symbols-outlined" style="font-size:13px;color:#059669;">check_circle</span>'
            + '최신 상태 (' + total + '개 확인됨)';
        setTimeout(function(){ if (hint) hint.textContent = ''; }, 4000);
    }
    if (manual) showToast('스키마 ' + total + '개 새로고침 완료', 'ok');

    _refreshing = false;
}

// 페이지 로드(새로고침)될 때마다 자동 실행
window.addEventListener('DOMContentLoaded', function() {
    refreshAllSchemas(false);
});
</script>
</body>
</html>